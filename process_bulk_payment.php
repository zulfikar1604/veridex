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

    // Gunakan transaksi agar PO dan Invoice selalu sinkron
    mysqli_begin_transaction($conn);

    try {
        // 1. Update status PO ke Payment Completed
        $query_po = "UPDATE purchase_orders 
                  SET status = 'Payment Completed', 
                      payment_completed_at = NOW() 
                  WHERE id IN ($ids_string) AND status = 'Payment Approved'";
                  
        if (!mysqli_query($conn, $query_po)) {
            throw new Exception("Gagal mengupdate status Purchase Order.");
        }
        
        $affected = mysqli_affected_rows($conn);
        
        if ($affected === 0) {
            throw new Exception("Tidak ada PO dengan status 'Payment Approved' yang ditemukan.");
        }
        
        // 2. Sinkronisasi status invoice ke 'Paid'
        $user_id = intval($_SESSION['user_id'] ?? 0);
        $query_inv = "UPDATE invoices 
                      SET status = 'Paid', 
                          paid_at = NOW(), 
                          paid_by = $user_id 
                      WHERE po_id IN ($ids_string) AND status = 'Unpaid'";
        
        if (!mysqli_query($conn, $query_inv)) {
            throw new Exception("Gagal mengupdate status Invoice.");
        }
        
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => "$affected PO berhasil dirilis pembayarannya."]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
