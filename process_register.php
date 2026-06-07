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
    $password = $_POST['password'] ?? '';
    
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
        
        // Hash password (SECURITY)
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $password_safe = mysqli_real_escape_string($conn, $password_hashed);
        
        $nib_safe = mysqli_real_escape_string($conn, $nib);
        $npwp_safe = mysqli_real_escape_string($conn, $npwp);

        $sql = "INSERT INTO vendors (nama_perusahaan, spesialisasi, alamat, email, pic_nama, pic_kontak, password, nib, npwp, status) 
                VALUES ('$nama_safe', '$spesialisasi_safe', '$alamat_safe', '$email_safe', '$pic_nama_safe', '$pic_kontak_safe', '$password_safe', '$nib_safe', '$npwp_safe', 'Pending')";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Pendaftaran berhasil! Tim kami akan meninjau profil Anda dalam waktu 1x24 jam sebelum Anda dapat login.";
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