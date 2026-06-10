<?php
session_start();
require 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_ids_json = $_POST['po_ids'] ?? '[]';
    $po_ids = json_decode($po_ids_json, true);

    if (!is_array($po_ids) || count($po_ids) === 0) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada PO yang dipilih.']);
        exit;
    }

    // Sanitize IDs
    $safe_ids = [];
    foreach ($po_ids as $id) {
        $safe_ids[] = intval($id);
    }
    
    $ids_string = implode(',', $safe_ids);

    // Lakukan bulk update
    $query = "UPDATE purchase_orders 
              SET status = 'Payment Completed', 
                  payment_completed_at = NOW() 
              WHERE id IN ($ids_string) AND status = 'Payment Approved'";
              
    if (mysqli_query($conn, $query)) {
        $affected = mysqli_affected_rows($conn);
        echo json_encode(['success' => true, 'message' => "$affected PO berhasil dirilis pembayarannya."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem saat memproses.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
