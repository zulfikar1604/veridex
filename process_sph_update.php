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
    echo json_encode(['success' => false, 'message' => 'SPH tidak dapat diubah karena sudah diproses ('.$sph['status'].')']);
    exit;
}

// Cek deadline
if (!empty($sph['deadline']) && strtotime($sph['deadline']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Batas waktu RFQ sudah terlewati. Tidak dapat mengubah penawaran.']);
    exit;
}

$incoterm_safe = mysqli_real_escape_string($conn, $incoterm);
$payment_term_safe = mysqli_real_escape_string($conn, $payment_term);
$keterangan_safe = mysqli_real_escape_string($conn, $keterangan);
$diskon_jenis_safe = mysqli_real_escape_string($conn, $diskon_jenis);
$sph_id = $sph['id'];

$sql = "UPDATE sph_submissions SET 
            harga_penawaran = $harga_penawaran,
            biaya_pengiriman = $biaya_pengiriman,
            diskon_jenis = '$diskon_jenis_safe',
            diskon_nilai = $diskon_nilai,
            incoterm = '$incoterm_safe',
            payment_term = '$payment_term_safe',
            keterangan = '$keterangan_safe'
        WHERE id = $sph_id AND vendor_id = $vendor_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Surat Penawaran Harga berhasil diperbarui!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui SPH: ' . mysqli_error($conn)]);
}
?>
