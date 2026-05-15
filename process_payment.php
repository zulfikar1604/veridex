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
$payment_notes = trim($_POST['payment_notes'] ?? '');

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'PO ID tidak valid']);
    exit;
}

// Pastikan PO berstatus 'Payment Approved'
$check = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT id, status FROM purchase_orders WHERE id = $po_id AND status = 'Payment Approved'"
));

if (!$check) {
    echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau belum di-approve untuk pembayaran']);
    exit;
}

// Handle file upload bukti bayar
$bukti_path = null;
if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/payments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.']);
        exit;
    }

    if ($_FILES['bukti_bayar']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB']);
        exit;
    }

    $filename = 'payment_' . $po_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $filepath)) {
        $bukti_path = 'uploads/payments/' . $filename;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File bukti pembayaran wajib diunggah']);
    exit;
}

// Update PO
$notes_safe = mysqli_real_escape_string($conn, $payment_notes);
$bukti_safe = mysqli_real_escape_string($conn, $bukti_path);

$sql = "UPDATE purchase_orders SET 
    status = 'Payment Completed', 
    bukti_bayar_file = '$bukti_safe',
    payment_notes = '$notes_safe',
    payment_completed_at = NOW()
    WHERE id = $po_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil diproses! Siklus P2P selesai.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pembayaran: ' . mysqli_error($conn)]);
}
?>
