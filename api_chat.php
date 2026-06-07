<?php
session_start();
require 'database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Logika Menyimpan Pesan
if ($action == 'send' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['role'])) {
        http_response_code(401);
        exit;
    }

    $pesan = mysqli_real_escape_string($conn, trim($_POST['message']));
    $po_number = mysqli_real_escape_string($conn, $_POST['po_number']);
    $sender_role = mysqli_real_escape_string($conn, $_SESSION['role']);
    $sender_name = mysqli_real_escape_string($conn, $_SESSION['username']);

    // Validasi kepemilikan PO jika pengirim adalah Vendor
    if ($sender_role === 'Vendor') {
        $vendor_id = intval($_SESSION['vendor_id'] ?? 0);
        $check_po = mysqli_query($conn, "SELECT id FROM purchase_orders WHERE po_number = '$po_number' AND vendor_id = $vendor_id");
        if (mysqli_num_rows($check_po) === 0) {
            http_response_code(403);
            echo "unauthorized";
            exit;
        }
    }

    if (!empty($pesan)) {
        mysqli_query($conn, "INSERT INTO chat_messages (po_number, sender_role, sender_name, message) VALUES ('$po_number', '$sender_role', '$sender_name', '$pesan')");
    }
    echo "terkirim";
    exit;
}

// 2. Logika Mengambil Pesan
if ($action == 'fetch') {
    if (!isset($_SESSION['role'])) {
        http_response_code(401);
        exit;
    }

    $po_number = mysqli_real_escape_string($conn, $_GET['po_number']);
    
    // Validasi kepemilikan PO jika penerima adalah Vendor
    if ($_SESSION['role'] === 'Vendor') {
        $vendor_id = intval($_SESSION['vendor_id'] ?? 0);
        $check_po = mysqli_query($conn, "SELECT id FROM purchase_orders WHERE po_number = '$po_number' AND vendor_id = $vendor_id");
        if (mysqli_num_rows($check_po) === 0) {
            http_response_code(403);
            echo json_encode([]);
            exit;
        }
    }

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