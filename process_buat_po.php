<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$inventory_id = intval($_POST['inventory_id'] ?? 0);
$vendor_id = intval($_POST['vendor_id'] ?? 0);
$jumlah_pesan = intval($_POST['jumlah_pesan'] ?? 0);
$harga_satuan = floatval($_POST['harga_satuan'] ?? 0);

if ($inventory_id <= 0 || $vendor_id <= 0 || $jumlah_pesan <= 0 || $harga_satuan <= 0) {
    echo json_encode(['success' => false, 'message' => 'Input tidak valid. Pastikan jumlah pesan dan harga satuan lebih dari 0.']);
    exit;
}

$total_harga = $jumlah_pesan * $harga_satuan;

// Ambil payment_term dan biaya_pengiriman dari SPH yang sudah Selected
$sph_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_term, biaya_pengiriman FROM sph_submissions WHERE inventory_id = $inventory_id AND vendor_id = $vendor_id AND status = 'Selected' LIMIT 1"));
$payment_term = $sph_data['payment_term'] ?? 'Net 30';
$biaya_pengiriman = floatval($sph_data['biaya_pengiriman'] ?? 0);
$total_harga += $biaya_pengiriman;

// Cek apakah sudah ada PO untuk inventory dan vendor ini
$check = mysqli_query($conn, "SELECT id FROM purchase_orders WHERE inventory_id = $inventory_id AND vendor_id = $vendor_id");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'message' => 'Purchase Order untuk vendor ini sudah dibuat sebelumnya.']);
    exit;
}

// Generate PO number
$vq_q = mysqli_query($conn, "SELECT rank_position FROM vendor_quotas WHERE inventory_id = $inventory_id AND vendor_id = $vendor_id");
$vq = mysqli_fetch_assoc($vq_q);
$rank = $vq['rank_position'] ?? 1;
$rank_letter = ($rank == 1) ? 'A' : (($rank == 2) ? 'B' : 'C');
$po_number = 'PO-' . date('Ymd') . '-' . str_pad($inventory_id, 3, '0', STR_PAD_LEFT) . '-' . $rank_letter;

$procurement_id = $_SESSION['user_id'];

$pt_safe = mysqli_real_escape_string($conn, $payment_term);
$stmt_po = mysqli_prepare($conn, "INSERT INTO purchase_orders (po_number, procurement_id, vendor_id, inventory_id, total_harga, nilai_kontrak, jumlah_pesan, payment_term, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, '$pt_safe', 'Pending Manager', NOW())");
mysqli_stmt_bind_param($stmt_po, "siiddii", $po_number, $procurement_id, $vendor_id, $inventory_id, $total_harga, $total_harga, $jumlah_pesan);

if (mysqli_stmt_execute($stmt_po)) {
    echo json_encode(['success' => true, 'message' => 'Purchase Order berhasil dibuat dan menunggu approval Manager.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal membuat Purchase Order: ' . mysqli_error($conn)]);
}
?>
