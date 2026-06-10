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
$jumlah_kebutuhan = intval($_POST['jumlah_kebutuhan'] ?? 0);
$catatan = trim($_POST['catatan'] ?? '');
$deadline = $_POST['deadline'] ?? '';
$vendor_ids_json = $_POST['vendor_ids'] ?? '[]';
$vendor_ids = json_decode($vendor_ids_json, true);

// Validasi
if ($inventory_id <= 0 || $jumlah_kebutuhan <= 0 || empty($deadline)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

if (empty($vendor_ids) || !is_array($vendor_ids)) {
    echo json_encode(['success' => false, 'message' => 'Pilih minimal 1 vendor']);
    exit;
}

// Cek item exists
$stmt_item = mysqli_prepare($conn, "SELECT id FROM inventory WHERE id = ?");
$item = null;
if ($stmt_item) {
    mysqli_stmt_bind_param($stmt_item, "i", $inventory_id);
    mysqli_stmt_execute($stmt_item);
    $res = mysqli_stmt_get_result($stmt_item);
    $item = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt_item);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pengecekan barang.']);
    exit;
}

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
    exit;
}

// Cek apakah sudah ada RFQ Open untuk item ini
$stmt_exist = mysqli_prepare($conn, "SELECT id FROM rfq_requests WHERE inventory_id = ? AND status = 'Open'");
$existing = null;
if ($stmt_exist) {
    mysqli_stmt_bind_param($stmt_exist, "i", $inventory_id);
    mysqli_stmt_execute($stmt_exist);
    $res = mysqli_stmt_get_result($stmt_exist);
    $existing = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt_exist);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pengecekan RFQ aktif.']);
    exit;
}

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Sudah ada RFQ terbuka untuk barang ini']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // Generate RFQ number
    $rfq_number = 'RFQ-' . date('Ymd') . '-' . str_pad($inventory_id, 3, '0', STR_PAD_LEFT);
    
    $created_by = $_SESSION['user_id'];

    // Insert RFQ
    $stmt = mysqli_prepare($conn, "INSERT INTO rfq_requests (rfq_number, inventory_id, jumlah_kebutuhan, catatan, deadline, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, 'Open', ?, NOW())");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "siissi", $rfq_number, $inventory_id, $jumlah_kebutuhan, $catatan, $deadline, $created_by);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if (!$success) {
            throw new Exception("Gagal menyimpan data RFQ.");
        }
    } else {
        throw new Exception("Gagal mempersiapkan data RFQ.");
    }
    
    $rfq_id = mysqli_insert_id($conn);

    // Insert vendor invitations
    $stmt_vendor = mysqli_prepare($conn, "INSERT INTO rfq_vendors (rfq_id, vendor_id, invited_at) VALUES (?, ?, NOW())");
    if ($stmt_vendor) {
        foreach ($vendor_ids as $vid) {
            $vid = intval($vid);
            if ($vid > 0) {
                mysqli_stmt_bind_param($stmt_vendor, "ii", $rfq_id, $vid);
                $success_vendor = mysqli_stmt_execute($stmt_vendor);
                if (!$success_vendor) {
                    mysqli_stmt_close($stmt_vendor);
                    throw new Exception("Gagal menyimpan undangan vendor.");
                }
            }
        }
        mysqli_stmt_close($stmt_vendor);
    } else {
        throw new Exception("Gagal mempersiapkan undangan vendor.");
    }

    mysqli_commit($conn);
    
    $vendor_count = count($vendor_ids);
    echo json_encode([
        'success' => true, 
        'message' => "RFQ $rfq_number berhasil dibuat dan dikirim ke $vendor_count vendor. Menunggu penawaran masuk."
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
