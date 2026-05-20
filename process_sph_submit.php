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

// Generate Nomor SPH otomatis: SPH/V{VendorID}/{Tahun}/{Bulan}/{Random4Digit}
$nomor_sph = "SPH/V" . $vendor_id . "/" . date('Y/m') . "/" . rand(1000, 9999);


// Cek RFQ masih open dan vendor diundang
$rfq = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT r.*, rv.id as invitation_id, rv.has_submitted, i.id as inv_id
    FROM rfq_requests r
    JOIN rfq_vendors rv ON r.id = rv.rfq_id
    JOIN inventory i ON r.inventory_id = i.id
    WHERE r.id = $rfq_id AND rv.vendor_id = $vendor_id AND r.status = 'Open'
"));

if (!$rfq) {
    echo json_encode(['success' => false, 'message' => 'RFQ tidak ditemukan atau Anda tidak diundang']);
    exit;
}

if ($rfq['has_submitted']) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah mengirim penawaran untuk RFQ ini']);
    exit;
}

// Cek deadline
if (!empty($rfq['deadline']) && strtotime($rfq['deadline']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Batas waktu penawaran sudah lewat']);
    exit;
}

// Handle file upload dokumen SPH dihapus (auto-generate SPH)
$dokumen_path = null;

mysqli_begin_transaction($conn);

try {
    // Insert ke sph_submissions
    $nomor_safe = mysqli_real_escape_string($conn, $nomor_sph);
    $incoterm_safe = mysqli_real_escape_string($conn, $incoterm);
    $payment_term_safe = mysqli_real_escape_string($conn, $payment_term);
    $keterangan_safe = mysqli_real_escape_string($conn, $keterangan);
    $diskon_jenis_safe = mysqli_real_escape_string($conn, $diskon_jenis);
    
    $dok_safe = $dokumen_path ? "'" . mysqli_real_escape_string($conn, $dokumen_path) . "'" : "NULL";
    $inventory_id = $rfq['inv_id'];

    $sql = "INSERT INTO sph_submissions (rfq_id, vendor_id, inventory_id, nomor_sph, harga_penawaran, biaya_pengiriman, diskon_jenis, diskon_nilai, incoterm, payment_term, keterangan, dokumen_sph, status, created_at) 
            VALUES ($rfq_id, $vendor_id, $inventory_id, '$nomor_safe', $harga_penawaran, $biaya_pengiriman, '$diskon_jenis_safe', $diskon_nilai, '$incoterm_safe', '$payment_term_safe', '$keterangan_safe', $dok_safe, 'Pending Review', NOW())";
    
    if (!mysqli_query($conn, $sql)) {
        throw new Exception(mysqli_error($conn));
    }

    // Mark vendor sebagai sudah submit
    mysqli_query($conn, "UPDATE rfq_vendors SET has_submitted = 1 WHERE rfq_id = $rfq_id AND vendor_id = $vendor_id");

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Surat Penawaran Harga berhasil dikirim! Menunggu evaluasi tim Procurement.']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan SPH: ' . $e->getMessage()]);
}
?>
