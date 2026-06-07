<?php
session_start();
require 'database.php';
require 'functions.php'; // Pakai fungsi calculateStatusLabel punya kamu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = mysqli_real_escape_string($conn, $_POST['sku_outbound']);
    $jumlah_keluar = (int)$_POST['jumlah_keluar'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $user = $_SESSION['username'] ?? 'Staf Gudang';

    // 1. Ambil data stok saat ini
    $query = mysqli_query($conn, "SELECT nama_barang, stok_saat_ini, stok_kritis FROM inventory WHERE sku = '$sku'");
    $barang = mysqli_fetch_assoc($query);

    $stok_baru = $barang['stok_saat_ini'] - $jumlah_keluar;
    
    if ($stok_baru < 0) {
        $_SESSION['error'] = "Gagal! Jumlah pengeluaran melebihi stok yang tersedia (Maksimal: " . $barang['stok_saat_ini'] . ").";
        header("Location: scan_barcode_outbound.php");
        exit;
    }

    // 2. Hitung status label baru pakai fungsi milikmu (Aman, Warning, Kritis)
    $status_baru = calculateStatusLabel($stok_baru, $barang['stok_kritis']);

    // 3. Update stok di tabel inventory
    mysqli_query($conn, "UPDATE inventory SET stok_saat_ini = $stok_baru, status_label = '$status_baru' WHERE sku = '$sku'");

    // 4. Catat ke history outbound
    $nama = $barang['nama_barang'];
    mysqli_query($conn, "INSERT INTO outbound_history (sku, nama_barang, jumlah_keluar, keterangan, user_gudang) 
                         VALUES ('$sku', '$nama', $jumlah_keluar, '$keterangan', '$user')");

    $_SESSION['sukses'] = "Barang berhasil dikeluarkan! Stok tersisa: " . $stok_baru;
    header("Location: scan_barcode_outbound.php");
    exit;
}
?>