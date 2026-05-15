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
$sku_safe = mysqli_real_escape_string($conn, $sku);
$barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM inventory WHERE sku = '$sku_safe'"));

if (!$barang) {
    header("Location: scan_barcode_inbound.php?error=not_found");
    exit;
}

mysqli_begin_transaction($conn);

try {
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
    mysqli_stmt_bind_param($stmt_inv, "isi", $stok_baru, $status_baru, $barang['id']);
    mysqli_stmt_execute($stmt_inv);

    // 2. Catat di inbound_history
    $user_gudang = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Gudang Staff';
    $stmt_log = mysqli_prepare($conn, "INSERT INTO inbound_history (sku, nama_barang, jumlah_masuk, user_gudang, tanggal_masuk, po_id) VALUES (?, ?, ?, ?, NOW(), ?)");
    mysqli_stmt_bind_param($stmt_log, "ssisi", $sku, $barang['nama_barang'], $jumlah_masuk, $user_gudang, $po_id);
    mysqli_stmt_execute($stmt_log);

    // 3. Update PO jika ada
    if ($po_id > 0) {
        $po_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, status FROM purchase_orders WHERE id = $po_id AND status = 'Shipped'"));
        if ($po_check) {
            $user_id = $_SESSION['user_id'] ?? 0;
            $stmt_po = mysqli_prepare($conn, "UPDATE purchase_orders SET status = 'Goods Received', received_at = NOW(), received_by = ?, jumlah_diterima = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_po, "iii", $user_id, $jumlah_masuk, $po_id);
            mysqli_stmt_execute($stmt_po);
        }
    }

    mysqli_commit($conn);
    header("Location: scan_barcode_inbound.php?success=1&nama=" . urlencode($barang['nama_barang']) . "&jumlah=$jumlah_masuk&stok_baru=$stok_baru");
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: scan_barcode_inbound.php?error=db_error");
}
exit;
?>
