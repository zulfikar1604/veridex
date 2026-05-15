<?php
session_start();
require 'database.php';

// Proteksi: Hanya Procurement & Manager yang bisa akses
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
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

switch ($action) {

    // ============================================================
    // ACTION 1: Simpan Score TBE untuk satu SPH
    // ============================================================
    case 'score_tbe':
        $sph_id = intval($_POST['sph_id'] ?? 0);
        $tbe_score = floatval($_POST['tbe_score'] ?? 0);

        if ($sph_id <= 0 || $tbe_score < 0 || $tbe_score > 100) {
            echo json_encode(['success' => false, 'message' => 'Score TBE harus antara 0-100']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "UPDATE sph_submissions SET tbe_score = ?, status = 'TBE Done', tbe_scored_by = ?, tbe_scored_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "dii", $tbe_score, $_SESSION['user_id'], $sph_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'TBE Score berhasil disimpan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan TBE Score']);
        }
        break;

    // ============================================================
    // ACTION 2: Simpan Score CBE untuk satu SPH
    // ============================================================
    case 'score_cbe':
        $sph_id = intval($_POST['sph_id'] ?? 0);
        $cbe_score = floatval($_POST['cbe_score'] ?? 0);

        if ($sph_id <= 0 || $cbe_score < 0 || $cbe_score > 100) {
            echo json_encode(['success' => false, 'message' => 'Score CBE harus antara 0-100']);
            exit;
        }

        // Pastikan TBE sudah di-score terlebih dahulu
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tbe_score FROM sph_submissions WHERE id = $sph_id"));
        if (!$check || $check['tbe_score'] === null) {
            echo json_encode(['success' => false, 'message' => 'TBE harus di-score terlebih dahulu']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "UPDATE sph_submissions SET cbe_score = ?, status = 'Evaluated', cbe_scored_by = ?, cbe_scored_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "dii", $cbe_score, $_SESSION['user_id'], $sph_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'CBE Score berhasil disimpan. Evaluasi lengkap.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan CBE Score']);
        }
        break;

    // ============================================================
    // ACTION 3: Select Vendor & Assign Quota → Generate PO
    // ============================================================
    case 'select_vendor':
        $inventory_id = intval($_POST['inventory_id'] ?? 0);
        $vendor_1 = intval($_POST['vendor_1'] ?? 0);
        $quota_1 = intval($_POST['quota_1'] ?? 0);
        $vendor_2 = intval($_POST['vendor_2'] ?? 0);
        $quota_2 = intval($_POST['quota_2'] ?? 0);

        if ($inventory_id <= 0 || $vendor_1 <= 0 || $quota_1 <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vendor utama dan kuota wajib diisi']);
            exit;
        }

        // Validasi: Vendor harus sudah "Evaluated" untuk item ini
        $check_eval = mysqli_query($conn, "SELECT id FROM sph_submissions WHERE inventory_id = $inventory_id AND vendor_id = $vendor_1 AND status = 'Evaluated'");
        if (mysqli_num_rows($check_eval) === 0) {
            echo json_encode(['success' => false, 'message' => 'Vendor utama belum menyelesaikan evaluasi TBE/CBE']);
            exit;
        }

        // Validasi total kuota
        $total_quota = $quota_1 + $quota_2;
        if ($total_quota !== 100) {
            echo json_encode(['success' => false, 'message' => 'Total kuota harus 100%. Saat ini: ' . $total_quota . '%']);
            exit;
        }

        // Jika vendor_2 dipilih, validasi juga
        if ($vendor_2 > 0) {
            $check_eval2 = mysqli_query($conn, "SELECT id FROM sph_submissions WHERE inventory_id = $inventory_id AND vendor_id = $vendor_2 AND status = 'Evaluated'");
            if (mysqli_num_rows($check_eval2) === 0) {
                echo json_encode(['success' => false, 'message' => 'Vendor pendamping belum menyelesaikan evaluasi TBE/CBE']);
                exit;
            }
        }

        mysqli_begin_transaction($conn);

        try {
            // Update SPH status vendor terpilih menjadi 'Selected'
            $stmt1 = mysqli_prepare($conn, "UPDATE sph_submissions SET status = 'Selected' WHERE inventory_id = ? AND vendor_id = ?");
            mysqli_stmt_bind_param($stmt1, "ii", $inventory_id, $vendor_1);
            mysqli_stmt_execute($stmt1);

            // Generate PO untuk Vendor 1
            $po_number_1 = 'PO-' . date('Ymd') . '-' . str_pad($inventory_id, 3, '0', STR_PAD_LEFT) . '-A';
            $jumlah_pesan_1 = 0; // Akan di-set nanti berdasarkan kebutuhan
            
            // Ambil harga penawaran vendor 1
            $harga1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga_penawaran FROM sph_submissions WHERE inventory_id = $inventory_id AND vendor_id = $vendor_1 LIMIT 1"));
            $total_harga_1 = $harga1['harga_penawaran'] ?? 0;

            $stmt_po1 = mysqli_prepare($conn, "INSERT INTO purchase_orders (po_number, procurement_id, vendor_id, inventory_id, total_harga, nilai_kontrak, jumlah_pesan, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Manager', NOW())");
            $procurement_id = $_SESSION['user_id'];
            mysqli_stmt_bind_param($stmt_po1, "siiddii", $po_number_1, $procurement_id, $vendor_1, $inventory_id, $total_harga_1, $total_harga_1, $jumlah_pesan_1);
            mysqli_stmt_execute($stmt_po1);

            // Simpan quota ke vendor_quotas
            $po_id_1 = mysqli_insert_id($conn);
            $stmt_q1 = mysqli_prepare($conn, "INSERT INTO vendor_quotas (inventory_id, vendor_id, quota_percentage, rank_position) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE quota_percentage = VALUES(quota_percentage), rank_position = VALUES(rank_position)");
            mysqli_stmt_bind_param($stmt_q1, "iii", $inventory_id, $vendor_1, $quota_1);
            mysqli_stmt_execute($stmt_q1);

            // Jika ada vendor 2
            if ($vendor_2 > 0 && $quota_2 > 0) {
                $stmt2 = mysqli_prepare($conn, "UPDATE sph_submissions SET status = 'Selected' WHERE inventory_id = ? AND vendor_id = ?");
                mysqli_stmt_bind_param($stmt2, "ii", $inventory_id, $vendor_2);
                mysqli_stmt_execute($stmt2);

                $po_number_2 = 'PO-' . date('Ymd') . '-' . str_pad($inventory_id, 3, '0', STR_PAD_LEFT) . '-B';
                $harga2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga_penawaran FROM sph_submissions WHERE inventory_id = $inventory_id AND vendor_id = $vendor_2 LIMIT 1"));
                $total_harga_2 = $harga2['harga_penawaran'] ?? 0;

                $stmt_po2 = mysqli_prepare($conn, "INSERT INTO purchase_orders (po_number, procurement_id, vendor_id, inventory_id, total_harga, nilai_kontrak, jumlah_pesan, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Manager', NOW())");
                $jumlah_pesan_2 = 0;
                mysqli_stmt_bind_param($stmt_po2, "siiddii", $po_number_2, $procurement_id, $vendor_2, $inventory_id, $total_harga_2, $total_harga_2, $jumlah_pesan_2);
                mysqli_stmt_execute($stmt_po2);

                $stmt_q2 = mysqli_prepare($conn, "INSERT INTO vendor_quotas (inventory_id, vendor_id, quota_percentage, rank_position) VALUES (?, ?, ?, 2) ON DUPLICATE KEY UPDATE quota_percentage = VALUES(quota_percentage), rank_position = VALUES(rank_position)");
                mysqli_stmt_bind_param($stmt_q2, "iii", $inventory_id, $vendor_2, $quota_2);
                mysqli_stmt_execute($stmt_q2);
            }

            // Reject vendor lain yang tidak terpilih
            $reject_ids = [$vendor_1];
            if ($vendor_2 > 0) $reject_ids[] = $vendor_2;
            $reject_list = implode(',', $reject_ids);
            mysqli_query($conn, "UPDATE sph_submissions SET status = 'Rejected' WHERE inventory_id = $inventory_id AND vendor_id NOT IN ($reject_list) AND status = 'Evaluated'");

            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Vendor berhasil dipilih dan PO telah di-generate. Menunggu approval Manager.']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
        break;
}
?>
