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
    error_log("Koneksi gagal: " . mysqli_connect_error());
    die("Maaf, koneksi ke database gagal. Silakan coba beberapa saat lagi.");
}

/**
 * Menangani kesalahan database dengan logging dan pesan user-friendly.
 */
function handle_db_error($conn) {
    error_log("Database Error: " . mysqli_error($conn));
    die("Maaf, terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.");
}
?>