<?php
session_start();
require 'database.php';

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nama_perusahaan = trim($_POST['nama_perusahaan']);
    $spesialisasi = trim($_POST['spesialisasi']);
    $alamat = trim($_POST['alamat']);
    $email = trim($_POST['email']);
    $pic_nama = trim($_POST['pic_nama']);
    $pic_kontak = trim($_POST['pic_kontak']);
    
    // Tangkap data NIB & NPWP
    $nib = trim($_POST['nib']);
    $npwp = trim($_POST['npwp']);
    
    try {
        $nama_safe = mysqli_real_escape_string($conn, $nama_perusahaan);
        $spesialisasi_safe = mysqli_real_escape_string($conn, $spesialisasi);
        $alamat_safe = mysqli_real_escape_string($conn, $alamat);
        $email_safe = mysqli_real_escape_string($conn, $email);
        $pic_nama_safe = mysqli_real_escape_string($conn, $pic_nama);
        $pic_kontak_safe = mysqli_real_escape_string($conn, $pic_kontak);
        $nib_safe = mysqli_real_escape_string($conn, $nib);
        $npwp_safe = mysqli_real_escape_string($conn, $npwp);

        $sql = "INSERT INTO vendors (nama_perusahaan, spesialisasi, alamat, email, pic_nama, pic_kontak, nib, npwp, status) 
                VALUES ('$nama_safe', '$spesialisasi_safe', '$alamat_safe', '$email_safe', '$pic_nama_safe', '$pic_kontak_safe', '$nib_safe', '$npwp_safe', 'Pending')";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Pendaftaran berhasil! Tim kami akan meninjau profil Anda.";
            header("Location: register_vendor.php");
            exit;
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
        header("Location: register_vendor.php");
        exit;
    }
} else {
    header("Location: register_vendor.php");
    exit;
}
?>