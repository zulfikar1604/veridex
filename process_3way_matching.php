<?php
session_start();
require 'database.php';

// Proteksi: Hanya Finance
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$po_id = intval($_POST['po_id'] ?? 0);

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'PO ID tidak valid']);
    exit;
}

// Pastikan PO berstatus 'Invoice Submitted'
$po = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, i.nama_barang, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.id = $po_id AND p.status = 'Invoice Submitted'
"));

if (!$po) {
    echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau belum lengkap']);
    exit;
}

// Validasi 3-Way: PO ada, Goods Received ada (received_at not null), Invoice ada (invoice_number not null)
$errors = [];
if (empty($po['received_at'])) {
    $errors[] = 'Barang belum diterima gudang';
}
if (empty($po['invoice_number'])) {
    $errors[] = 'Invoice belum disubmit vendor';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => 'Validasi gagal: ' . implode(', ', $errors)]);
    exit;
}

// 3-Way Match passed — update status
$finance_user = $_SESSION['user_id'] ?? 0;
$stmt = mysqli_prepare($conn, "UPDATE purchase_orders SET status = 'Payment Approved', payment_matched_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $po_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => '3-Way Matching berhasil! PO siap untuk pembayaran.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses matching']);
}
?>
