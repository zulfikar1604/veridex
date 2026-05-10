<?php
session_start();
require 'database.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $unit = $_POST['unit'];
    $stok_kritis = (int)$_POST['stok_kritis'];

    // Stok awal selalu 0 — barang baru langsung masuk antrian pengadaan
    $stok_awal = 0;

    // 1. GENERATE SKU OTOMATIS (Misal: SKU-004)
    $query_id = mysqli_query($conn, "SELECT MAX(id) as max_id FROM inventory");
    $row_id = mysqli_fetch_assoc($query_id);
    $next_id = $row_id['max_id'] + 1;
    $sku_baru = "SKU-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

    // 2. STATUS LABEL — selalu Kritis karena stok_awal = 0
    $status_label = 'Kritis';

    // 3. INPUT KE DATABASE
    $query_insert = "INSERT INTO inventory (sku, nama_barang, unit, stok_saat_ini, stok_kritis, status_label) 
                     VALUES ('$sku_baru', '$nama_barang', '$unit', $stok_awal, $stok_kritis, '$status_label')";

    if (mysqli_query($conn, $query_insert)) {
        header("Location: dashboard_gudang.php?pesan=sukses");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit;
}