<?php
session_start();
require 'database.php'; // Menggunakan koneksi $conn (mysqli)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil input dan bersihkan dari karakter aneh (Security)
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];

    // 2. Query cari user di database
    $query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // 3. Verifikasi Password (Plaintext)
        if ($password == $user['password']) {
            // Set Session Data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // 4. Redirect Berdasarkan Role
            if ($user['role'] === 'Manager Procurement') {
                header("Location: kinerja_vendor.php");
            } elseif ($user['role'] === 'Procurement') {
                header("Location: dashboard_procurement.php");
            } elseif ($user['role'] === 'Gudang') {
                header("Location: dashboard_gudang.php");
            } elseif ($user['role'] === 'Finance') {
                header("Location: dashboard_finance.php");
            } elseif ($user['role'] === 'Vendor') {
                $_SESSION['vendor_id'] = $user['vendor_id'];
                header("Location: dashboard_vendor.php");
            } else {
                $_SESSION['error'] = "Akses ditolak: Role tidak dikenali.";
                header("Location: login.php");
            }
            exit; // WAJIB: Menghentikan script agar tidak buffering

        } else {
            $_SESSION['error'] = "Password salah! Pastikan Caps Lock mati.";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Username tidak ditemukan di sistem.";
        header("Location: login.php");
        exit;
    }
} else {
    // Jika akses file ini tanpa POST, tendang balik ke login
    header("Location: login.php");
    exit;
}