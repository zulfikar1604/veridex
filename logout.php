<?php
// Mulai session agar sistem tahu tiket mana yang mau dihancurkan
session_start();

// Hapus semua data di dalam session
session_unset();

// Hancurkan session secara total
session_destroy();

// Tendang kembali ke halaman login
header("Location: login.php");
exit;
?>