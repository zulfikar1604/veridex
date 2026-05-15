<?php
session_start();
require 'database.php';

// Proteksi: Hanya Vendor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
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

$vendor_id = $_SESSION['vendor_id'] ?? 0;
$po_id = intval($_POST['po_id'] ?? 0);
$invoice_number = trim($_POST['invoice_number'] ?? '');

if ($po_id <= 0 || empty($invoice_number)) {
    echo json_encode(['success' => false, 'message' => 'Nomor Invoice wajib diisi']);
    exit;
}

// Pastikan PO milik vendor ini dan statusnya 'Goods Received'
$check = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT id, status FROM purchase_orders WHERE id = $po_id AND vendor_id = $vendor_id AND status = 'Goods Received'"
));

if (!$check) {
    echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau barang belum diterima gudang']);
    exit;
}

// Handle file upload invoice
$invoice_path = null;
if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/invoices/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.']);
        exit;
    }

    if ($_FILES['invoice_file']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB']);
        exit;
    }

    $filename = 'invoice_' . $po_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['invoice_file']['tmp_name'], $filepath)) {
        $invoice_path = 'uploads/invoices/' . $filename;
    }
}

// Update PO
$inv_num_safe = mysqli_real_escape_string($conn, $invoice_number);
$inv_file_safe = $invoice_path ? "'" . mysqli_real_escape_string($conn, $invoice_path) . "'" : "NULL";

$sql = "UPDATE purchase_orders SET 
    status = 'Invoice Submitted', 
    invoice_number = '$inv_num_safe',
    invoice_file = $inv_file_safe,
    invoice_submitted_at = NOW()
    WHERE id = $po_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Invoice berhasil disubmit. Menunggu verifikasi Finance.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan invoice: ' . mysqli_error($conn)]);
}
?>
