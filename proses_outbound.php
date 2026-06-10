<?php
session_start();
require 'database.php';
require 'functions.php'; // Pakai fungsi calculateStatusLabel punya kamu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku_outbound'] ?? '');
    $jumlah_keluar = (int)($_POST['jumlah_keluar'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $user = $_SESSION['username'] ?? 'Staf Gudang';

    if (empty($sku) || $jumlah_keluar <= 0) {
        $_SESSION['error'] = "Input tidak valid. SKU dan jumlah keluar wajib diisi.";
        header("Location: scan_barcode_outbound.php");
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        // 1. Ambil data stok saat ini
        $stmt_select = mysqli_prepare($conn, "SELECT nama_barang, stok_saat_ini, stok_kritis FROM inventory WHERE sku = ?");
        $barang = null;
        if ($stmt_select) {
            mysqli_stmt_bind_param($stmt_select, "s", $sku);
            mysqli_stmt_execute($stmt_select);
            $result = mysqli_stmt_get_result($stmt_select);
            $barang = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt_select);
        } else {
            throw new Exception("Gagal memeriksa stok barang.");
        }

        if (!$barang) {
            throw new Exception("Barang dengan SKU tersebut tidak ditemukan.");
        }

        $stok_baru = $barang['stok_saat_ini'] - $jumlah_keluar;
        
        if ($stok_baru < 0) {
            throw new Exception("Jumlah pengeluaran melebihi stok yang tersedia (Maksimal: " . $barang['stok_saat_ini'] . ").");
        }

        // 2. Hitung status label baru pakai fungsi milikmu (Aman, Warning, Kritis)
        $status_baru = calculateStatusLabel($stok_baru, $barang['stok_kritis']);

        // 3. Update stok di tabel inventory
        $stmt_update = mysqli_prepare($conn, "UPDATE inventory SET stok_saat_ini = ?, status_label = ? WHERE sku = ?");
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "iss", $stok_baru, $status_baru, $sku);
            $success = mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
            
            if (!$success) {
                throw new Exception("Gagal mengupdate stok inventory.");
            }
        } else {
            throw new Exception("Gagal mempersiapkan perbaruan stok.");
        }

        // 4. Catat ke history outbound
        $nama = $barang['nama_barang'];
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO outbound_history (sku, nama_barang, jumlah_keluar, keterangan, user_gudang) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_insert) {
            mysqli_stmt_bind_param($stmt_insert, "ssiss", $sku, $nama, $jumlah_keluar, $keterangan, $user);
            $success = mysqli_stmt_execute($stmt_insert);
            mysqli_stmt_close($stmt_insert);
            
            if (!$success) {
                throw new Exception("Gagal mencatat history outbound.");
            }
        } else {
            throw new Exception("Gagal mempersiapkan pencatatan history.");
        }

        mysqli_commit($conn);
        $_SESSION['sukses'] = "Barang berhasil dikeluarkan! Stok tersisa: " . $stok_baru;
        header("Location: scan_barcode_outbound.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Gagal! " . $e->getMessage();
        header("Location: scan_barcode_outbound.php");
        exit;
    }
} else {
    header("Location: scan_barcode_outbound.php");
    exit;
}
?>