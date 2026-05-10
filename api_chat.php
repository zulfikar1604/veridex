<?php
session_start();
require 'database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Logika Menyimpan Pesan
if ($action == 'send' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $pesan = mysqli_real_escape_string($conn, trim($_POST['message']));
    $po_number = mysqli_real_escape_string($conn, $_POST['po_number']);
    $sender_role = mysqli_real_escape_string($conn, $_SESSION['role']);
    $sender_name = mysqli_real_escape_string($conn, $_SESSION['username']);

    if (!empty($pesan)) {
        mysqli_query($conn, "INSERT INTO chat_messages (po_number, sender_role, sender_name, message) VALUES ('$po_number', '$sender_role', '$sender_name', '$pesan')");
    }
    echo "terkirim";
    exit;
}

// 2. Logika Mengambil Pesan
if ($action == 'fetch') {
    $po_number = mysqli_real_escape_string($conn, $_GET['po_number']);
    $query = mysqli_query($conn, "SELECT * FROM chat_messages WHERE po_number = '$po_number' ORDER BY created_at ASC");
    
    $chats = [];
    while($row = mysqli_fetch_assoc($query)) {
        $chats[] = $row;
    }
    
    // Kembalikan data dalam format JSON
    header('Content-Type: application/json');
    echo json_encode($chats);
    exit;
}
?>