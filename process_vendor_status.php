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
    $query = "UPDATE vendors SET status = '$status' WHERE id = $vendor_id";
    if (!mysqli_query($conn, $query)) {
        throw new Exception('Gagal mengubah status vendor');
    }
    
    // 2. Jika disetujui (Approve), buatkan akun user otomatis di tabel users
    $message_suffix = "";
    if ($action === 'Approve') {
        // Ambil nama perusahaan, email, dan password untuk generate akun login
        $v_res = mysqli_query($conn, "SELECT nama_perusahaan, email, password FROM vendors WHERE id = $vendor_id");
        if ($v_res && mysqli_num_rows($v_res) > 0) {
            $vendor_data = mysqli_fetch_assoc($v_res);
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
            $check_u = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
            if (mysqli_num_rows($check_u) > 0) {
                $username .= "_" . $vendor_id;
            }
            
            // Cek apakah vendor ini sudah memiliki akun
            $check_v = mysqli_query($conn, "SELECT id FROM users WHERE vendor_id = $vendor_id");
            if (mysqli_num_rows($check_v) === 0) {
                // Masukkan data user baru
                $insert_user = "INSERT INTO users (username, password, role, vendor_id) 
                                VALUES ('$username', '$password', 'Vendor', $vendor_id)";
                if (!mysqli_query($conn, $insert_user)) {
                    throw new Exception('Gagal membuat akun user vendor: ' . mysqli_error($conn));
                }
                $message_suffix = ". Akun Login Dibuat -> Username: " . $username . " | Password: " . $plain_pass_for_email;
            } else {
                $message_suffix = ". Akun user sudah terdaftar di sistem.";
            }
        } else {
            throw new Exception('Data vendor tidak ditemukan');
        }
    }
    
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => "Vendor berhasil di-" . strtolower($action) . $message_suffix]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Kesalahan: ' . $e->getMessage()]);
}
?>
