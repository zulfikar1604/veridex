<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$inventory_id = intval($_POST['inventory_id'] ?? 0);
$vendor_id = intval($_POST['vendor_id'] ?? 0);
$jumlah_pesan = intval($_POST['jumlah_pesan'] ?? 0);
$harga_satuan = floatval($_POST['harga_satuan'] ?? 0);

if ($inventory_id <= 0 || $vendor_id <= 0 || $jumlah_pesan <= 0 || $harga_satuan <= 0) {
    echo json_encode(['success' => false, 'message' => 'Input tidak valid. Pastikan jumlah pesan dan harga satuan lebih dari 0.']);
    exit;
}

$total_harga = $jumlah_pesan * $harga_satuan;

$rfq_id = intval($_POST['rfq_id'] ?? 0);

mysqli_begin_transaction($conn);

try {
    // Ambil SPH data untuk RFQ dan vendor terpilih ini
    $sph_data = null;
    if ($rfq_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT payment_term, biaya_pengiriman, diskon_jenis, diskon_nilai FROM sph_submissions WHERE rfq_id = ? AND vendor_id = ? AND status = 'Selected' LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $rfq_id, $vendor_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $sph_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Gagal memproses data SPH.");
        }
    } else {
        // Fallback jika rfq_id tidak dikirim
        $stmt = mysqli_prepare($conn, "SELECT rfq_id, payment_term, biaya_pengiriman, diskon_jenis, diskon_nilai FROM sph_submissions WHERE inventory_id = ? AND vendor_id = ? AND status = 'Selected' ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $inventory_id, $vendor_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $sph_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            $rfq_id = $sph_data ? intval($sph_data['rfq_id']) : 0;
        } else {
            throw new Exception("Gagal memproses data SPH.");
        }
    }

    $payment_term = $sph_data['payment_term'] ?? 'Net 30';
    $biaya_pengiriman = floatval($sph_data['biaya_pengiriman'] ?? 0);
    
    $subtotal = $jumlah_pesan * $harga_satuan;
    $diskon = 0;
    $diskon_jenis = $sph_data['diskon_jenis'] ?? 'Nominal';
    $diskon_nilai = floatval($sph_data['diskon_nilai'] ?? 0);
    
    if ($diskon_jenis === 'Persentase') {
        $diskon = ($diskon_nilai / 100) * $subtotal;
    } else {
        $diskon = $diskon_nilai;
    }
    
    $total_harga = $subtotal - $diskon + $biaya_pengiriman;

    // Cek apakah sudah ada PO untuk RFQ dan vendor ini
    $stmt_check = mysqli_prepare($conn, "SELECT id FROM purchase_orders WHERE rfq_id = ? AND vendor_id = ?");
    if ($stmt_check) {
        mysqli_stmt_bind_param($stmt_check, "ii", $rfq_id, $vendor_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $has_po = mysqli_num_rows($result_check) > 0;
        mysqli_stmt_close($stmt_check);
        
        if ($has_po) {
            throw new Exception('Purchase Order untuk vendor ini dalam RFQ yang sama sudah dibuat sebelumnya.');
        }
    } else {
        throw new Exception("Gagal memeriksa data Purchase Order.");
    }

    // Generate PO number dengan daily auto-increment (anti duplikat)
    $today = date('Ymd');
    $prefix = 'PO-' . $today . '-';
    
    // Cari nomor urut tertinggi hari ini
    $stmt_seq = mysqli_prepare($conn, "SELECT po_number FROM purchase_orders WHERE po_number LIKE CONCAT(?, '%') ORDER BY po_number DESC LIMIT 1 FOR UPDATE");
    if ($stmt_seq) {
        mysqli_stmt_bind_param($stmt_seq, "s", $prefix);
        mysqli_stmt_execute($stmt_seq);
        $result_seq = mysqli_stmt_get_result($stmt_seq);
        $last_po = mysqli_fetch_assoc($result_seq);
        mysqli_stmt_close($stmt_seq);
    } else {
        throw new Exception("Gagal menghitung sequence PO.");
    }

    $next_seq = 1;
    if ($last_po) {
        // Ambil angka setelah prefix terakhir, misal PO-20260611-003 → 3
        $last_num = intval(substr($last_po['po_number'], strlen($prefix)));
        $next_seq = $last_num + 1;
    }
    
    $po_number = $prefix . str_pad($next_seq, 3, '0', STR_PAD_LEFT);

    $procurement_id = $_SESSION['user_id'];

    $stmt_po = mysqli_prepare($conn, "INSERT INTO purchase_orders (po_number, procurement_id, vendor_id, inventory_id, rfq_id, total_harga, nilai_kontrak, jumlah_pesan, payment_term, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending Manager', NOW())");
    if ($stmt_po) {
        mysqli_stmt_bind_param($stmt_po, "siiiiddis", $po_number, $procurement_id, $vendor_id, $inventory_id, $rfq_id, $total_harga, $total_harga, $jumlah_pesan, $payment_term);
        $execute_success = mysqli_stmt_execute($stmt_po);
        mysqli_stmt_close($stmt_po);
        
        if (!$execute_success) {
            throw new Exception('Gagal membuat Purchase Order.');
        }
    } else {
        throw new Exception("Gagal mempersiapkan data Purchase Order.");
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Purchase Order berhasil dibuat dan menunggu approval Manager.']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
