<?php
session_start();
require 'database.php'; // Pastikan file koneksi mysqli kamu namanya benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $unit = $_POST['unit'];
    $stok_awal = (int)$_POST['stok_awal'];
    $stok_kritis = (int)$_POST['stok_kritis'];

    // 1. GENERATE SKU OTOMATIS (Misal: SKU-004)
    // Cari ID paling besar di tabel saat ini
    $query_id = mysqli_query($conn, "SELECT MAX(id) as max_id FROM inventory");
    $row_id = mysqli_fetch_assoc($query_id);
    $next_id = $row_id['max_id'] + 1;
    
    // Format angka jadi 3 digit (contoh: 1 jadi 001, 12 jadi 012)
    $sku_baru = "SKU-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

    // 2. TENTUKAN STATUS LABEL OTOMATIS
    if ($stok_awal < $stok_kritis) {
        $status_label = 'Kritis';
    } elseif ($stok_awal <= ($stok_kritis + 3)) { // Jarak 3 angka dari kritis masuk Warning
        $status_label = 'Warning';
    } else {
        $status_label = 'Aman';
    }

    // 3. INPUT KE DATABASE
    $query_insert = "INSERT INTO inventory (sku, nama_barang, unit, stok_saat_ini, stok_kritis, status_label) 
                     VALUES ('$sku_baru', '$nama_barang', '$unit', $stok_awal, $stok_kritis, '$status_label')";

    if (mysqli_query($conn, $query_insert)) {
        // Jika sukses, kembalikan ke dashboard gudang
        header("Location: dashboard_gudang.php?pesan=sukses");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit;
}