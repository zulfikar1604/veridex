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
$stmt_check = mysqli_prepare($conn, "SELECT id, status FROM purchase_orders WHERE id = ? AND vendor_id = ? AND status = 'Confirmed by Vendor'");
$check = null;
if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "ii", $po_id, $vendor_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $check = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pemeriksaan Purchase Order.']);
    exit;
}

if (!$check) {
    echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau belum dikonfirmasi']);
    exit;
}

// Generate Nomor Surat Jalan otomatis
// Format: SJ-[VendorID]-[YYYYMMDD]-[PO_ID]
$nomor_sj = 'SJ-' . str_pad($vendor_id, 3, '0', STR_PAD_LEFT) . '-' . date('Ymd') . '-' . str_pad($po_id, 4, '0', STR_PAD_LEFT);

// Update PO
$stmt_update = mysqli_prepare($conn, "UPDATE purchase_orders SET status = 'Shipped', shipped_at = NOW(), no_resi = ?, sj_pengemudi = ?, sj_nopol = ?, sj_kontak = ?, sj_catatan = ? WHERE id = ?");
if ($stmt_update) {
    mysqli_stmt_bind_param($stmt_update, "sssssi", $nomor_sj, $sj_pengemudi, $sj_nopol, $sj_kontak, $sj_catatan, $po_id);
    $success = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Surat Jalan berhasil di-generate. Barang sedang dalam perjalanan.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan Surat Jalan.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses Surat Jalan.']);
}
?>
