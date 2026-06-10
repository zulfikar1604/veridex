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
$harga_penawaran = floatval($_POST['harga_penawaran'] ?? 0);
$biaya_pengiriman = floatval($_POST['biaya_pengiriman'] ?? 0);
$diskon_jenis = $_POST['diskon_jenis'] ?? 'Nominal';
$diskon_nilai = floatval($_POST['diskon_nilai'] ?? 0);
$incoterm = trim($_POST['incoterm'] ?? '');
$payment_term = trim($_POST['payment_term'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

if ($rfq_id <= 0 || $harga_penawaran <= 0) {
    echo json_encode(['success' => false, 'message' => 'Field harga penawaran wajib diisi dengan benar']);
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
    echo json_encode(['success' => false, 'message' => 'SPH tidak dapat diubah karena sudah diproses ('.$sph['status'].')']);
    exit;
}

// Cek deadline
if (!empty($sph['deadline']) && strtotime($sph['deadline']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Batas waktu RFQ sudah terlewati. Tidak dapat mengubah penawaran.']);
    exit;
}

$sph_id = $sph['id'];

$stmt_update = mysqli_prepare($conn, "UPDATE sph_submissions SET harga_penawaran = ?, biaya_pengiriman = ?, diskon_jenis = ?, diskon_nilai = ?, incoterm = ?, payment_term = ?, keterangan = ? WHERE id = ? AND vendor_id = ?");
if ($stmt_update) {
    mysqli_stmt_bind_param($stmt_update, "ddsdsssii", $harga_penawaran, $biaya_pengiriman, $diskon_jenis, $diskon_nilai, $incoterm, $payment_term, $keterangan, $sph_id, $vendor_id);
    $success = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Surat Penawaran Harga berhasil diperbarui!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan SPH.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses perbaruan SPH.']);
}
?>
