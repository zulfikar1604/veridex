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
        // Hash password (SECURITY)
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "INSERT INTO vendors (nama_perusahaan, spesialisasi, alamat, email, pic_nama, pic_kontak, password, nib, npwp, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssssss", $nama_perusahaan, $spesialisasi, $alamat, $email, $pic_nama, $pic_kontak, $password_hashed, $nib, $npwp);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                $_SESSION['success'] = "Pendaftaran berhasil! Tim kami akan meninjau profil Anda dalam waktu 1x24 jam sebelum Anda dapat login.";
                header("Location: register_vendor.php");
                exit;
            } else {
                throw new Exception("Gagal menyimpan data pendaftaran vendor. Silakan periksa kembali data Anda.");
            }
        } else {
            throw new Exception("Gagal mempersiapkan sistem pendaftaran.");
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: register_vendor.php");
        exit;
    }
} else {
    header("Location: register_vendor.php");
    exit;
}
?>