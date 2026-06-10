<?php
session_start();
require 'database.php';

// Proteksi: Hanya Finance
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
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

$po_id = intval($_POST['po_id'] ?? 0);
$payment_notes = trim($_POST['payment_notes'] ?? '');

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'PO ID tidak valid']);
    exit;
}

// Pastikan PO berstatus 'Payment Approved'
$stmt_check = mysqli_prepare($conn, "SELECT id, status FROM purchase_orders WHERE id = ? AND status = 'Payment Approved'");
$check = null;
if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "i", $po_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $check = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses data Purchase Order.']);
    exit;
}

if (!$check) {
    echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan atau belum di-approve untuk pembayaran']);
    exit;
}

// Handle file upload bukti bayar
$bukti_path = null;
if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/payments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.']);
        exit;
    }

    if ($_FILES['bukti_bayar']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB']);
        exit;
    }

    $filename = 'payment_' . $po_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $filepath)) {
        $bukti_path = 'uploads/payments/' . $filename;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File bukti pembayaran wajib diunggah']);
    exit;
}

// Update PO and Invoice in a transaction
mysqli_begin_transaction($conn);

try {
    $stmt_update_po = mysqli_prepare($conn, "UPDATE purchase_orders SET status = 'Payment Completed', bukti_bayar_file = ?, payment_notes = ?, payment_completed_at = NOW() WHERE id = ?");
    if ($stmt_update_po) {
        mysqli_stmt_bind_param($stmt_update_po, "ssi", $bukti_path, $payment_notes, $po_id);
        $success_po = mysqli_stmt_execute($stmt_update_po);
        mysqli_stmt_close($stmt_update_po);
        
        if (!$success_po) {
            throw new Exception("Gagal mengupdate Purchase Order.");
        }
    } else {
        throw new Exception("Gagal memproses update Purchase Order.");
    }

    // Sinkronisasi status invoice ke 'Paid' agar vendor melihat update yang sama
    $user_id = $_SESSION['user_id'] ?? 0;
    $stmt_update_inv = mysqli_prepare($conn, "UPDATE invoices SET status = 'Paid', paid_at = NOW(), paid_by = ? WHERE po_id = ?");
    if ($stmt_update_inv) {
        mysqli_stmt_bind_param($stmt_update_inv, "ii", $user_id, $po_id);
        $success_inv = mysqli_stmt_execute($stmt_update_inv);
        mysqli_stmt_close($stmt_update_inv);
        
        if (!$success_inv) {
            throw new Exception("Gagal mengupdate Invoice.");
        }
    } else {
        throw new Exception("Gagal memproses update Invoice.");
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil diproses! Siklus P2P selesai.']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    // Hapus file yang sudah diupload jika transaksi database gagal
    if ($bukti_path && file_exists(__DIR__ . '/' . $bukti_path)) {
        @unlink(__DIR__ . '/' . $bukti_path);
    }
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pembayaran: ' . $e->getMessage()]);
}
?>
