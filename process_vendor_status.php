<?php
session_start();
require 'database.php';

// Proteksi Internal
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$vendor_id = intval($_POST['vendor_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($vendor_id <= 0 || !in_array($action, ['Approve', 'Reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$status = ($action === 'Approve') ? 'Approved' : 'Rejected';

$query = "UPDATE vendors SET status = '$status' WHERE id = $vendor_id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => "Vendor berhasil di-" . strtolower($action)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status vendor']);
}
?>
