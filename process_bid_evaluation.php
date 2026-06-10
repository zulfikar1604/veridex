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
        $stmt_check = mysqli_prepare($conn, "SELECT tbe_score FROM sph_submissions WHERE id = ?");
        $check = null;
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "i", $sph_id);
            mysqli_stmt_execute($stmt_check);
            $result = mysqli_stmt_get_result($stmt_check);
            $check = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt_check);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memproses pemeriksaan TBE.']);
            exit;
        }
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
        $rfq_id = intval($_POST['rfq_id'] ?? 0);

        if ($inventory_id <= 0 || $vendor_1 <= 0 || $quota_1 <= 0 || $rfq_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Input tidak valid atau RFQ tidak ditemukan']);
            exit;
        }

        // Validasi: Vendor harus sudah "Evaluated" atau "Selected" untuk item ini di RFQ terpilih
        $stmt_eval = mysqli_prepare($conn, "SELECT id FROM sph_submissions WHERE rfq_id = ? AND vendor_id = ? AND status IN ('Evaluated', 'Selected', 'Rejected')");
        $has_eval = false;
        if ($stmt_eval) {
            mysqli_stmt_bind_param($stmt_eval, "ii", $rfq_id, $vendor_1);
            mysqli_stmt_execute($stmt_eval);
            $res_eval = mysqli_stmt_get_result($stmt_eval);
            $has_eval = mysqli_num_rows($res_eval) > 0;
            mysqli_stmt_close($stmt_eval);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memproses pemeriksaan evaluasi vendor utama.']);
            exit;
        }
        if (!$has_eval) {
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
            $stmt_eval2 = mysqli_prepare($conn, "SELECT id FROM sph_submissions WHERE rfq_id = ? AND vendor_id = ? AND status IN ('Evaluated', 'Selected', 'Rejected')");
            $has_eval2 = false;
            if ($stmt_eval2) {
                mysqli_stmt_bind_param($stmt_eval2, "ii", $rfq_id, $vendor_2);
                mysqli_stmt_execute($stmt_eval2);
                $res_eval2 = mysqli_stmt_get_result($stmt_eval2);
                $has_eval2 = mysqli_num_rows($res_eval2) > 0;
                mysqli_stmt_close($stmt_eval2);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal memproses pemeriksaan evaluasi vendor pendamping.']);
                exit;
            }
            if (!$has_eval2) {
                echo json_encode(['success' => false, 'message' => 'Vendor pendamping belum menyelesaikan evaluasi TBE/CBE']);
                exit;
            }
        }

        mysqli_begin_transaction($conn);

        try {
            // Update SPH status vendor terpilih menjadi 'Selected'
            $stmt1 = mysqli_prepare($conn, "UPDATE sph_submissions SET status = 'Selected' WHERE rfq_id = ? AND vendor_id = ?");
            mysqli_stmt_bind_param($stmt1, "ii", $rfq_id, $vendor_1);
            mysqli_stmt_execute($stmt1);

            // Simpan quota ke vendor_quotas
            $stmt_q1 = mysqli_prepare($conn, "INSERT INTO vendor_quotas (inventory_id, vendor_id, quota_percentage, rank_position) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE quota_percentage = VALUES(quota_percentage), rank_position = VALUES(rank_position)");
            mysqli_stmt_bind_param($stmt_q1, "iii", $inventory_id, $vendor_1, $quota_1);
            mysqli_stmt_execute($stmt_q1);

            // Jika ada vendor 2
            if ($vendor_2 > 0 && $quota_2 > 0) {
                $stmt2 = mysqli_prepare($conn, "UPDATE sph_submissions SET status = 'Selected' WHERE rfq_id = ? AND vendor_id = ?");
                mysqli_stmt_bind_param($stmt2, "ii", $rfq_id, $vendor_2);
                mysqli_stmt_execute($stmt2);

                $stmt_q2 = mysqli_prepare($conn, "INSERT INTO vendor_quotas (inventory_id, vendor_id, quota_percentage, rank_position) VALUES (?, ?, ?, 2) ON DUPLICATE KEY UPDATE quota_percentage = VALUES(quota_percentage), rank_position = VALUES(rank_position)");
                mysqli_stmt_bind_param($stmt_q2, "iii", $inventory_id, $vendor_2, $quota_2);
                mysqli_stmt_execute($stmt_q2);
            }

            // Reject vendor lain yang tidak terpilih
            if ($vendor_2 > 0) {
                $stmt_reject = mysqli_prepare($conn, "UPDATE sph_submissions SET status = 'Rejected' WHERE rfq_id = ? AND vendor_id != ? AND vendor_id != ? AND status IN ('Evaluated', 'Selected', 'Rejected')");
                if ($stmt_reject) {
                    mysqli_stmt_bind_param($stmt_reject, "iii", $rfq_id, $vendor_1, $vendor_2);
                    mysqli_stmt_execute($stmt_reject);
                    mysqli_stmt_close($stmt_reject);
                } else {
                    throw new Exception('Gagal mempersiapkan penolakan vendor lain.');
                }
            } else {
                $stmt_reject = mysqli_prepare($conn, "UPDATE sph_submissions SET status = 'Rejected' WHERE rfq_id = ? AND vendor_id != ? AND status IN ('Evaluated', 'Selected', 'Rejected')");
                if ($stmt_reject) {
                    mysqli_stmt_bind_param($stmt_reject, "ii", $rfq_id, $vendor_1);
                    mysqli_stmt_execute($stmt_reject);
                    mysqli_stmt_close($stmt_reject);
                } else {
                    throw new Exception('Gagal mempersiapkan penolakan vendor lain.');
                }
            }

            // Tutup RFQ yang sedang berjalan agar item ini bisa dipesan ulang di masa depan
            $stmt_close_rfq = mysqli_prepare($conn, "UPDATE rfq_requests SET status = 'Closed', closed_at = NOW() WHERE id = ?");
            if ($stmt_close_rfq) {
                mysqli_stmt_bind_param($stmt_close_rfq, "i", $rfq_id);
                mysqli_stmt_execute($stmt_close_rfq);
                mysqli_stmt_close($stmt_close_rfq);
            } else {
                throw new Exception('Gagal mempersiapkan penutupan RFQ.');
            }

            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Vendor berhasil dipilih. Silakan lanjut ke halaman Buat PO untuk men-generate Purchase Order.']);
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
