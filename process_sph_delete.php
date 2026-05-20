<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$vendor_id = $_SESSION['vendor_id'] ?? 0;
$rfq_id = intval($_POST['rfq_id'] ?? 0);

if ($rfq_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

// Cek RFQ dan SPH existing
$sph = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT s.id, s.status, r.deadline 
    FROM sph_submissions s
    JOIN rfq_requests r ON s.rfq_id = r.id
    WHERE s.rfq_id = $rfq_id AND s.vendor_id = $vendor_id
"));

if (!$sph) {
    echo json_encode(['success' => false, 'message' => 'Data penawaran tidak ditemukan']);
    exit;
}

if ($sph['status'] !== 'Pending Review') {
    echo json_encode(['success' => false, 'message' => 'SPH tidak dapat dibatalkan karena sudah diproses ('.$sph['status'].')']);
    exit;
}

// Cek deadline
if (!empty($sph['deadline']) && strtotime($sph['deadline']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Batas waktu RFQ sudah terlewati. Tidak dapat membatalkan penawaran.']);
    exit;
}

$sph_id = $sph['id'];

mysqli_begin_transaction($conn);
try {
    // Delete SPH
    if (!mysqli_query($conn, "DELETE FROM sph_submissions WHERE id = $sph_id AND vendor_id = $vendor_id")) {
        throw new Exception(mysqli_error($conn));
    }

    // Update vendor has_submitted back to 0
    if (!mysqli_query($conn, "UPDATE rfq_vendors SET has_submitted = 0 WHERE rfq_id = $rfq_id AND vendor_id = $vendor_id")) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Penawaran SPH berhasil dibatalkan dan dihapus.']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus SPH: ' . $e->getMessage()]);
}
?>
