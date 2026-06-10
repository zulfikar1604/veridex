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
$stmt_check = mysqli_prepare($conn, "
    SELECT s.id, s.status, r.deadline 
    FROM sph_submissions s
    JOIN rfq_requests r ON s.rfq_id = r.id
    WHERE s.rfq_id = ? AND s.vendor_id = ?
");
$sph = null;
if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "ii", $rfq_id, $vendor_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $sph = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pemeriksaan SPH.']);
    exit;
}

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
    $stmt_delete = mysqli_prepare($conn, "DELETE FROM sph_submissions WHERE id = ? AND vendor_id = ?");
    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "ii", $sph_id, $vendor_id);
        $success_delete = mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        
        if (!$success_delete) {
            throw new Exception("Gagal menghapus SPH.");
        }
    } else {
        throw new Exception("Gagal mempersiapkan penghapusan SPH.");
    }

    // Update vendor has_submitted back to 0
    $stmt_update = mysqli_prepare($conn, "UPDATE rfq_vendors SET has_submitted = 0 WHERE rfq_id = ? AND vendor_id = ?");
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ii", $rfq_id, $vendor_id);
        $success_update = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        if (!$success_update) {
            throw new Exception("Gagal mengupdate status pendaftaran vendor.");
        }
    } else {
        throw new Exception("Gagal mempersiapkan update status pendaftaran vendor.");
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Penawaran SPH berhasil dibatalkan dan dihapus.']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus SPH: ' . $e->getMessage()]);
}
?>
