<?php
session_start();
require 'database.php';

// Proteksi: Hanya Manager Procurement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager Procurement') {
    header("Location: login.php");
    exit;
}

$po_id = intval($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

// Validasi input
if ($po_id <= 0 || !in_array($status, ['Approved', 'Rejected'])) {
    header("Location: approval_procurement.php?error=invalid");
    exit;
}

// Pastikan PO masih berstatus 'Pending Approval'
$check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, status FROM purchase_orders WHERE id = $po_id"));
if (!$check || $check['status'] !== 'Pending Approval') {
    header("Location: approval_procurement.php?error=already_processed");
    exit;
}

// Update status PO
$new_status = $status; // 'Approved' atau 'Rejected'
$approved_by = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "UPDATE purchase_orders SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, "sii", $new_status, $approved_by, $po_id);

if (mysqli_stmt_execute($stmt)) {
    $msg = ($status === 'Approved') ? 'PO berhasil di-approve.' : 'PO telah ditolak.';
    header("Location: approval_procurement.php?success=" . urlencode($msg));
} else {
    header("Location: approval_procurement.php?error=db_error");
}
exit;
?>
