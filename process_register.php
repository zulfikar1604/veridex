<?php
session_start();
require_once '../config/database.php';

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nama_perusahaan = trim($_POST['nama_perusahaan']);
    $spesialisasi = trim($_POST['spesialisasi']);
    
    // --- PROSES UPLOAD FILE ---
    $file_name = $_FILES['dokumen_legal']['name'];
    $file_tmp = $_FILES['dokumen_legal']['tmp_name'];
    $file_size = $_FILES['dokumen_legal']['size'];
    $file_error = $_FILES['dokumen_legal']['error'];
    
    // Ambil ekstensi file
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Ekstensi yang diizinkan
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    
    // Validasi file
    if (in_array($file_ext, $allowed_ext)) {
        if ($file_error === 0) {
            if ($file_size <= 2000000) { // Maksimal 2MB
                
                // Buat nama file unik agar tidak tertimpa
                $file_name_new = uniqid('DOC_', true) . "." . $file_ext;
                
                // Tentukan folder tujuan (Pastikan folder 'uploads' ini ada di project kamu!)
                // Path: veridex_app/uploads/vendors/
                $upload_dir = '../uploads/vendors/';
                
                // Cek apakah folder ada, jika tidak, buat otomatis
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_destination = $upload_dir . $file_name_new;
                
                // Pindahkan file dari temp ke folder tujuan
                if (move_uploaded_file($file_tmp, $file_destination)) {
                    
                    try {
                        // Simpan data ke Database
                        // Status default-nya adalah 'Pending' dari skema SQL kita
                        $stmt = $pdo->prepare("INSERT INTO vendors (nama_perusahaan, spesialisasi, dokumen_legal) VALUES (?, ?, ?)");
                        $stmt->execute([$nama_perusahaan, $spesialisasi, $file_name_new]);
                        
                        $_SESSION['success'] = "Pendaftaran berhasil! Tim kami akan meninjau dokumen Anda.";
                        header("Location: register_vendor.php");
                        exit;
                        
                    } catch(PDOException $e) {
                        $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
                        header("Location: register_vendor.php");
                        exit;
                    }
                    
                } else {
                    $_SESSION['error'] = "Gagal mengunggah file. Silakan coba lagi.";
                    header("Location: register_vendor.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Ukuran file terlalu besar! Maksimal 2MB.";
                header("Location: register_vendor.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Terjadi error saat mengunggah file.";
            header("Location: register_vendor.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Format file tidak didukung! Gunakan PDF, JPG, atau PNG.";
        header("Location: register_vendor.php");
        exit;
    }
} else {
    header("Location: register_vendor.php");
    exit;
}
?>