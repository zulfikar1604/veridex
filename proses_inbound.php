<?php
session_start();
require 'database.php';

// Proteksi: Hanya Gudang
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: scan_barcode_inbound.php");
    exit;
}

$sku = trim($_POST['sku_inbound'] ?? '');
$jumlah_masuk = intval($_POST['jumlah_masuk'] ?? 0);
$po_id = intval($_POST['po_id'] ?? 0);

if (empty($sku) || $jumlah_masuk <= 0) {
    header("Location: scan_barcode_inbound.php?error=invalid_input");
    exit;
}

// Cari barang
$stmt_barang = mysqli_prepare($conn, "SELECT * FROM inventory WHERE sku = ?");
$barang = null;
if ($stmt_barang) {
    mysqli_stmt_bind_param($stmt_barang, "s", $sku);
    mysqli_stmt_execute($stmt_barang);
    $result_barang = mysqli_stmt_get_result($stmt_barang);
    $barang = mysqli_fetch_assoc($result_barang);
    mysqli_stmt_close($stmt_barang);
} else {
    header("Location: scan_barcode_inbound.php?error=db_error");
    exit;
}

if (!$barang) {
    header("Location: scan_barcode_inbound.php?error=not_found");
    exit;
}

if ($po_id > 0) {
    $stmt_po_check = mysqli_prepare($conn, "SELECT status FROM purchase_orders WHERE id = ?");
    $check_po = null;
    if ($stmt_po_check) {
        mysqli_stmt_bind_param($stmt_po_check, "i", $po_id);
        mysqli_stmt_execute($stmt_po_check);
        $res_po = mysqli_stmt_get_result($stmt_po_check);
        $check_po = mysqli_fetch_assoc($res_po);
        mysqli_stmt_close($stmt_po_check);
    } else {
        header("Location: scan_barcode_inbound.php?error=db_error");
        exit;
    }
    
    if ($check_po && $check_po['status'] !== 'Shipped') {
        header("Location: scan_barcode_inbound.php?error=already_received");
        exit;
    }
}

mysqli_begin_transaction($conn);

try {
    // 0. Double check PO Status dengan lock
    if ($po_id > 0) {
        $stmt_lock = mysqli_prepare($conn, "SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE");
        $check_po_lock = null;
        if ($stmt_lock) {
            mysqli_stmt_bind_param($stmt_lock, "i", $po_id);
            mysqli_stmt_execute($stmt_lock);
            $res_lock = mysqli_stmt_get_result($stmt_lock);
            $check_po_lock = mysqli_fetch_assoc($res_lock);
            mysqli_stmt_close($stmt_lock);
        } else {
            throw new Exception("Gagal mengunci status PO.");
        }

        if (!$check_po_lock || $check_po_lock['status'] !== 'Shipped') {
            throw new Exception("already_received");
        }
    }

    // 1. Update stok di inventory
    $stok_baru = $barang['stok_saat_ini'] + $jumlah_masuk;
    
    // Hitung status label baru
    if ($stok_baru <= 0) {
        $status_baru = 'Kritis';
    } elseif ($stok_baru < $barang['stok_kritis']) {
        $status_baru = 'Kritis';
    } elseif ($stok_baru <= $barang['stok_kritis'] + 3) {
        $status_baru = 'Warning';
    } else {
        $status_baru = 'Aman';
    }

    $stmt_inv = mysqli_prepare($conn, "UPDATE inventory SET stok_saat_ini = ?, status_label = ?, last_update = NOW() WHERE id = ?");
    if ($stmt_inv) {
        mysqli_stmt_bind_param($stmt_inv, "isi", $stok_baru, $status_baru, $barang['id']);
        mysqli_stmt_execute($stmt_inv);
        mysqli_stmt_close($stmt_inv);
    } else {
        throw new Exception("Gagal mengupdate stok inventory.");
    }

    // 2. Catat di inbound_history
    $user_gudang = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Gudang Staff';
    $stmt_log = mysqli_prepare($conn, "INSERT INTO inbound_history (sku, nama_barang, jumlah_masuk, user_gudang, tanggal_masuk, po_id) VALUES (?, ?, ?, ?, NOW(), ?)");
    if ($stmt_log) {
        mysqli_stmt_bind_param($stmt_log, "ssisi", $sku, $barang['nama_barang'], $jumlah_masuk, $user_gudang, $po_id);
        mysqli_stmt_execute($stmt_log);
        mysqli_stmt_close($stmt_log);
    } else {
        throw new Exception("Gagal mencatat history inbound.");
    }

    // 3. Update PO jika ada
    if ($po_id > 0) {
        $stmt_po_check2 = mysqli_prepare($conn, "SELECT id, status, vendor_id, inventory_id, total_harga, jumlah_pesan, payment_term FROM purchase_orders WHERE id = ? AND status = 'Shipped'");
        $po_check = null;
        if ($stmt_po_check2) {
            mysqli_stmt_bind_param($stmt_po_check2, "i", $po_id);
            mysqli_stmt_execute($stmt_po_check2);
            $res_po_check2 = mysqli_stmt_get_result($stmt_po_check2);
            $po_check = mysqli_fetch_assoc($res_po_check2);
            mysqli_stmt_close($stmt_po_check2);
        } else {
            throw new Exception("Gagal mengambil detail PO.");
        }

        if ($po_check) {
            $user_id = $_SESSION['user_id'] ?? 0;
            $stmt_po = mysqli_prepare($conn, "UPDATE purchase_orders SET status = 'Goods Received', received_at = NOW(), received_by = ?, jumlah_diterima = ? WHERE id = ?");
            if ($stmt_po) {
                mysqli_stmt_bind_param($stmt_po, "iii", $user_id, $jumlah_masuk, $po_id);
                mysqli_stmt_execute($stmt_po);
                mysqli_stmt_close($stmt_po);
            } else {
                throw new Exception("Gagal mengupdate Purchase Order.");
            }

            $inv_number = 'INV-' . date('Ymd') . '-' . str_pad($po_id, 4, '0', STR_PAD_LEFT);
            $vendor_id = $po_check['vendor_id'];
            $inventory_id = $po_check['inventory_id'];
            $po_payment_term = $po_check['payment_term'] ?? 'Net 30';

            // Kalkulasi jatuh tempo berdasarkan Payment Term
            $due_days = 30; // default
            $pt_lower = strtolower(trim($po_payment_term));
            if ($pt_lower === 'cbd' || $pt_lower === 'cash before delivery') {
                $due_days = 0;
            } elseif (preg_match('/net\s*(\d+)/i', $pt_lower, $m)) {
                $due_days = intval($m[1]);
            }
            $jatuh_tempo = date('Y-m-d', strtotime("+{$due_days} days"));
            
            $unit_price = $po_check['jumlah_pesan'] > 0 ? $po_check['total_harga'] / $po_check['jumlah_pesan'] : 0;
            $total_tagihan = $unit_price * $jumlah_masuk;

            $stmt_inv_ins = mysqli_prepare($conn, "INSERT INTO invoices (po_id, invoice_number, vendor_id, inventory_id, jumlah_barang, total_tagihan, payment_term, tanggal_terima_gudang, tanggal_jatuh_tempo, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'Unpaid', NOW())");
            if ($stmt_inv_ins) {
                mysqli_stmt_bind_param($stmt_inv_ins, "isiiidss", $po_id, $inv_number, $vendor_id, $inventory_id, $jumlah_masuk, $total_tagihan, $po_payment_term, $jatuh_tempo);
                mysqli_stmt_execute($stmt_inv_ins);
                mysqli_stmt_close($stmt_inv_ins);
            } else {
                throw new Exception("Gagal mencatat Invoice.");
            }

            // Sync invoice number ke purchase_orders agar 3-Way Matching Finance tidak 'WAITING'
            $invoice_file_path = 'print_auto_invoice.php?po_id=' . $po_id;
            $stmt_sync_po = mysqli_prepare($conn, "UPDATE purchase_orders SET invoice_number = ?, invoice_file = ?, invoice_submitted_at = NOW() WHERE id = ?");
            if ($stmt_sync_po) {
                mysqli_stmt_bind_param($stmt_sync_po, "ssi", $inv_number, $invoice_file_path, $po_id);
                mysqli_stmt_execute($stmt_sync_po);
                mysqli_stmt_close($stmt_sync_po);
            } else {
                throw new Exception("Gagal mensinkronisasikan Purchase Order.");
            }
        }
    }

    mysqli_commit($conn);
    header("Location: scan_barcode_inbound.php?success=1&nama=" . urlencode($barang['nama_barang']) . "&jumlah=$jumlah_masuk&stok_baru=$stok_baru");
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    if ($e->getMessage() === "already_received") {
        header("Location: scan_barcode_inbound.php?error=already_received");
    } else {
        header("Location: scan_barcode_inbound.php?error=db_error");
    }
    exit;
}
?>
