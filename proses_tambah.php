<?php
session_start();
require 'database.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $stok_kritis = (int)($_POST['stok_kritis'] ?? 0);

    if (empty($nama_barang) || empty($unit) || $stok_kritis <= 0) {
        $_SESSION['error'] = "Input tidak valid. Nama barang, unit, dan stok kritis wajib diisi.";
        header("Location: dashboard_gudang.php?pesan=gagal");
        exit;
    }

    // Stok awal selalu 0 — barang baru langsung masuk antrian pengadaan
    $stok_awal = 0;

    mysqli_begin_transaction($conn);

    try {
        // 1. GENERATE SKU OTOMATIS (Misal: SKU-004) dengan SELECT FOR UPDATE untuk mencegah race conditions
        $query_id = mysqli_query($conn, "SELECT MAX(id) as max_id FROM inventory FOR UPDATE");
        $row_id = mysqli_fetch_assoc($query_id);
        $next_id = ($row_id['max_id'] ?? 0) + 1;
        $sku_baru = "SKU-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

        // 2. STATUS LABEL — selalu Kritis karena stok_awal = 0
        $status_label = 'Kritis';

        // 3. INPUT KE DATABASE
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO inventory (sku, nama_barang, unit, stok_saat_ini, stok_kritis, status_label) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_insert) {
            mysqli_stmt_bind_param($stmt_insert, "sssiss", $sku_baru, $nama_barang, $unit, $stok_awal, $stok_kritis, $status_label);
            $success = mysqli_stmt_execute($stmt_insert);
            mysqli_stmt_close($stmt_insert);
            
            if (!$success) {
                throw new Exception("Gagal menyimpan barang baru.");
            }
        } else {
            throw new Exception("Gagal mempersiapkan penyimpanan barang baru.");
        }

        mysqli_commit($conn);
        header("Location: dashboard_gudang.php?pesan=sukses");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Gagal menambah barang: " . $e->getMessage();
        header("Location: dashboard_gudang.php?pesan=gagal");
        exit;
    }
} else {
    header("Location: dashboard_gudang.php");
    exit;
}
?>