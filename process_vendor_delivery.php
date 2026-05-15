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
$no_resi = trim($_POST['no_resi'] ?? '');

if ($po_id <= 0 || empty($no_resi)) {
    echo json_encode(['success' => false, 'message' => 'No. Resi/Surat Jalan wajib diisi']);
    exit;
}

// Pastikan PO milik vendor ini dan statusnya 'Confirmed by Vendor'
$check = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT id, status FROM purchase_orders WHERE id = $po_id AND vendor_id = $vendor_id AND status = 'Confirmed by Vendor'"
));

if (!$check) {
    echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau belum dikonfirmasi']);
    exit;
}

// Handle file upload foto surat jalan
$foto_path = null;
if (isset($_FILES['foto_surat_jalan']) && $_FILES['foto_surat_jalan']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/delivery/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['foto_surat_jalan']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.']);
        exit;
    }

    if ($_FILES['foto_surat_jalan']['size'] > 5 * 1024 * 1024) { // Max 5MB
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB']);
        exit;
    }

    $filename = 'delivery_' . $po_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['foto_surat_jalan']['tmp_name'], $filepath)) {
        $foto_path = 'uploads/delivery/' . $filename;
    }
}

// Update PO
$no_resi_safe = mysqli_real_escape_string($conn, $no_resi);
$foto_safe = $foto_path ? "'" . mysqli_real_escape_string($conn, $foto_path) . "'" : "NULL";

$sql = "UPDATE purchase_orders SET 
    status = 'Shipped', 
    shipped_at = NOW(), 
    no_resi = '$no_resi_safe',
    foto_surat_jalan = $foto_safe
    WHERE id = $po_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Data pengiriman berhasil disimpan. Barang sedang dalam perjalanan.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengiriman: ' . mysqli_error($conn)]);
}
?>
