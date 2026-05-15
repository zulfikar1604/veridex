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

$action = $_POST['action'] ?? '';
$vendor_id = $_SESSION['vendor_id'] ?? 0;

switch ($action) {
    case 'confirm_po':
        $po_id = intval($_POST['po_id'] ?? 0);

        if ($po_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'PO ID tidak valid']);
            exit;
        }

        // Pastikan PO milik vendor ini dan statusnya 'Approved'
        $check = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT id, status FROM purchase_orders WHERE id = $po_id AND vendor_id = $vendor_id AND status = 'Approved'"
        ));

        if (!$check) {
            echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau sudah dikonfirmasi']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "UPDATE purchase_orders SET status = 'Confirmed by Vendor', confirmed_by_vendor_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $po_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'PO berhasil dikonfirmasi. Silakan lanjutkan ke proses pengiriman.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengkonfirmasi PO']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
        break;
}
?>
