<?php
// config/database.php (Versi LOKAL XAMPP - Gaya MySQLi)

// Kredensial Database LOKAL (XAMPP)
$hostname = "localhost"; 
$username = "root";           // Default bawaan XAMPP
$password = "";               // Default bawaan XAMPP (kosongkan)
$database = "veridex_db";     // Nama database kamu

// Eksekusi koneksi menggunakan mysqli ($conn)
$conn = mysqli_connect($hostname, $username, $password, $database);

// Cek apakah koneksi berhasil
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>