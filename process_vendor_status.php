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

// Mulai transaksi untuk menjaga konsistensi database
mysqli_begin_transaction($conn);

try {
    // 1. Update status vendor
    $stmt_update_vendor = mysqli_prepare($conn, "UPDATE vendors SET status = ? WHERE id = ?");
    if ($stmt_update_vendor) {
        mysqli_stmt_bind_param($stmt_update_vendor, "si", $status, $vendor_id);
        $success = mysqli_stmt_execute($stmt_update_vendor);
        mysqli_stmt_close($stmt_update_vendor);
        
        if (!$success) {
            throw new Exception('Gagal mengubah status vendor.');
        }
    } else {
        throw new Exception('Gagal memproses perbaruan status vendor.');
    }
    
    // 2. Jika disetujui (Approve), buatkan akun user otomatis di tabel users
    $message_suffix = "";
    if ($action === 'Approve') {
        // Ambil nama perusahaan, email, dan password untuk generate akun login
        $stmt_vendor_info = mysqli_prepare($conn, "SELECT nama_perusahaan, email, password FROM vendors WHERE id = ?");
        $vendor_data = null;
        if ($stmt_vendor_info) {
            mysqli_stmt_bind_param($stmt_vendor_info, "i", $vendor_id);
            mysqli_stmt_execute($stmt_vendor_info);
            $result = mysqli_stmt_get_result($stmt_vendor_info);
            $vendor_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt_vendor_info);
        } else {
            throw new Exception('Gagal mengambil data vendor.');
        }

        if ($vendor_data) {
            $company_name = $vendor_data['nama_perusahaan'];
            $email = trim($vendor_data['email'] ?? '');
            
            // Password sudah di-hash dari form register. Jika kosong, buat fallback ber-hash.
            if (!empty($vendor_data['password'])) {
                $password = $vendor_data['password'];
                $plain_pass_for_email = "Dibuat saat registrasi";
            } else {
                $plain_pass_for_email = "123";
                $password = password_hash($plain_pass_for_email, PASSWORD_DEFAULT);
            }
            
            // Set username (gunakan email jika ada, fallback ke vendor_name jika kosong)
            if (!empty($email)) {
                $username = $email;
            } else {
                $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($company_name));
                if (strpos($clean_name, 'pt') === 0) $clean_name = substr($clean_name, 2);
                if (strpos($clean_name, 'cv') === 0) $clean_name = substr($clean_name, 2);
                if (strpos($clean_name, 'pd') === 0) $clean_name = substr($clean_name, 2);
                $username = "vendor_" . $clean_name;
            }
            
            // Hindari duplikasi username dengan menambahkan suffix ID jika sudah ada
            $stmt_check_username = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            $has_username = false;
            if ($stmt_check_username) {
                mysqli_stmt_bind_param($stmt_check_username, "s", $username);
                mysqli_stmt_execute($stmt_check_username);
                $res_check = mysqli_stmt_get_result($stmt_check_username);
                $has_username = mysqli_num_rows($res_check) > 0;
                mysqli_stmt_close($stmt_check_username);
            } else {
                throw new Exception('Gagal memeriksa ketersediaan username.');
            }

            if ($has_username) {
                $username .= "_" . $vendor_id;
            }
            
            // Cek apakah vendor ini sudah memiliki akun
            $stmt_check_user = mysqli_prepare($conn, "SELECT id FROM users WHERE vendor_id = ?");
            $has_user = false;
            if ($stmt_check_user) {
                mysqli_stmt_bind_param($stmt_check_user, "i", $vendor_id);
                mysqli_stmt_execute($stmt_check_user);
                $res_check_user = mysqli_stmt_get_result($stmt_check_user);
                $has_user = mysqli_num_rows($res_check_user) > 0;
                mysqli_stmt_close($stmt_check_user);
            } else {
                throw new Exception('Gagal memeriksa akun user vendor.');
            }

            if (!$has_user) {
                // Masukkan data user baru
                $stmt_insert_user = mysqli_prepare($conn, "INSERT INTO users (username, password, role, vendor_id) VALUES (?, ?, 'Vendor', ?)");
                if ($stmt_insert_user) {
                    mysqli_stmt_bind_param($stmt_insert_user, "ssi", $username, $password, $vendor_id);
                    $success_insert = mysqli_stmt_execute($stmt_insert_user);
                    mysqli_stmt_close($stmt_insert_user);
                    
                    if (!$success_insert) {
                        throw new Exception('Gagal membuat akun user vendor.');
                    }
                    $message_suffix = ". Akun Login Dibuat -> Username: " . $username . " | Password: " . $plain_pass_for_email;
                } else {
                    throw new Exception('Gagal mempersiapkan pembuatan akun user vendor.');
                }
            } else {
                $message_suffix = ". Akun user sudah terdaftar di sistem.";
            }
        } else {
            throw new Exception('Data vendor tidak ditemukan.');
        }
    }
    
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => "Vendor berhasil di-" . strtolower($action) . $message_suffix]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Kesalahan: ' . $e->getMessage()]);
}
?>
