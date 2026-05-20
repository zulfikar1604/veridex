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
$sj_pengemudi = trim($_POST['sj_pengemudi'] ?? '');
$sj_nopol = trim($_POST['sj_nopol'] ?? '');
$sj_kontak = trim($_POST['sj_kontak'] ?? '');
$sj_catatan = trim($_POST['sj_catatan'] ?? '');

if ($po_id <= 0 || empty($sj_pengemudi) || empty($sj_nopol) || empty($sj_kontak)) {
    echo json_encode(['success' => false, 'message' => 'Detail pengemudi dan kendaraan wajib diisi lengkap.']);
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

// Generate Nomor Surat Jalan otomatis
// Format: SJ-[VendorID]-[YYYYMMDD]-[PO_ID]
$nomor_sj = 'SJ-' . str_pad($vendor_id, 3, '0', STR_PAD_LEFT) . '-' . date('Ymd') . '-' . str_pad($po_id, 4, '0', STR_PAD_LEFT);

// Update PO
$pengemudi_safe = mysqli_real_escape_string($conn, $sj_pengemudi);
$nopol_safe = mysqli_real_escape_string($conn, $sj_nopol);
$kontak_safe = mysqli_real_escape_string($conn, $sj_kontak);
$catatan_safe = mysqli_real_escape_string($conn, $sj_catatan);

$sql = "UPDATE purchase_orders SET 
    status = 'Shipped', 
    shipped_at = NOW(), 
    no_resi = '$nomor_sj',
    sj_pengemudi = '$pengemudi_safe',
    sj_nopol = '$nopol_safe',
    sj_kontak = '$kontak_safe',
    sj_catatan = '$catatan_safe'
    WHERE id = $po_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Surat Jalan berhasil di-generate. Barang sedang dalam perjalanan.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal membuat Surat Jalan: ' . mysqli_error($conn)]);
}
?>
