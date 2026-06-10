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
$invoice_number = trim($_POST['invoice_number'] ?? '');

if ($po_id <= 0 || empty($invoice_number)) {
    echo json_encode(['success' => false, 'message' => 'Nomor Invoice wajib diisi']);
    exit;
}

// Pastikan PO milik vendor ini dan statusnya 'Goods Received'
$stmt_check = mysqli_prepare($conn, "SELECT id, status FROM purchase_orders WHERE id = ? AND vendor_id = ? AND status = 'Goods Received'");
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
    echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau barang belum diterima gudang']);
    exit;
}

// Handle file upload invoice
$invoice_path = null;
if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/invoices/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.']);
        exit;
    }

    if ($_FILES['invoice_file']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB']);
        exit;
    }

    $filename = 'invoice_' . $po_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['invoice_file']['tmp_name'], $filepath)) {
        $invoice_path = 'uploads/invoices/' . $filename;
    }
}

// Update PO and sync to Invoices in a transaction
mysqli_begin_transaction($conn);

try {
    $stmt_update_po = mysqli_prepare($conn, "UPDATE purchase_orders SET status = 'Invoice Submitted', invoice_number = ?, invoice_file = ?, invoice_submitted_at = NOW() WHERE id = ?");
    if ($stmt_update_po) {
        mysqli_stmt_bind_param($stmt_update_po, "ssi", $invoice_number, $invoice_path, $po_id);
        $success_po = mysqli_stmt_execute($stmt_update_po);
        mysqli_stmt_close($stmt_update_po);
        
        if (!$success_po) {
            throw new Exception("Gagal mengupdate Purchase Order.");
        }
    } else {
        throw new Exception("Gagal memproses update Purchase Order.");
    }

    // Sync invoice number ke tabel invoices untuk dashboard Finance
    $stmt_sync_inv = mysqli_prepare($conn, "UPDATE invoices SET invoice_number = ? WHERE po_id = ?");
    if ($stmt_sync_inv) {
        mysqli_stmt_bind_param($stmt_sync_inv, "si", $invoice_number, $po_id);
        $success_inv = mysqli_stmt_execute($stmt_sync_inv);
        mysqli_stmt_close($stmt_sync_inv);
        
        if (!$success_inv) {
            throw new Exception("Gagal sinkronisasi data Invoice.");
        }
    } else {
        throw new Exception("Gagal memproses sinkronisasi Invoice.");
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Invoice berhasil disubmit. Menunggu verifikasi Finance.']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    // Hapus file yang sudah diupload jika transaksi database gagal
    if ($invoice_path && file_exists(__DIR__ . '/' . $invoice_path)) {
        @unlink(__DIR__ . '/' . $invoice_path);
    }
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan invoice: ' . $e->getMessage()]);
}
?>
