<?php
/**
 * VERIDEX AUTO-MIGRATION
 * Jalankan file ini sekali via browser: http://localhost/veridex/run_migration.php
 * File ini akan membuat semua tabel dan kolom baru yang dibutuhkan.
 */

require 'database.php';

$results = [];
$errors = [];

// Helper function
function run_sql($conn, $sql, $desc, &$results, &$errors) {
    if (mysqli_query($conn, $sql)) {
        $results[] = "✅ $desc";
    } else {
        $err = mysqli_error($conn);
        // Ignore "already exists" or "duplicate column" errors
        if (strpos($err, 'Duplicate') !== false || strpos($err, 'already exists') !== false) {
            $results[] = "⏩ $desc (sudah ada, di-skip)";
        } else {
            $errors[] = "❌ $desc — Error: $err";
        }
    }
}

// ============================================================
// 1. Tabel RFQ Requests
// ============================================================
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS rfq_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rfq_number VARCHAR(50) NOT NULL,
        inventory_id INT NOT NULL,
        jumlah_kebutuhan INT NOT NULL DEFAULT 0,
        catatan TEXT NULL,
        deadline DATETIME NULL,
        status ENUM('Open', 'Closed', 'Cancelled') DEFAULT 'Open',
        created_by INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        closed_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "Tabel rfq_requests", $results, $errors);

// ============================================================
// 2. Tabel RFQ Vendors (Undangan)
// ============================================================
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS rfq_vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rfq_id INT NOT NULL,
        vendor_id INT NOT NULL,
        invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        has_submitted TINYINT(1) DEFAULT 0,
        UNIQUE KEY unique_rfq_vendor (rfq_id, vendor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "Tabel rfq_vendors", $results, $errors);

// ============================================================
// 3. Tambah kolom baru ke sph_submissions
// ============================================================
run_sql($conn, "ALTER TABLE sph_submissions ADD COLUMN rfq_id INT NULL AFTER id", "Kolom sph_submissions.rfq_id", $results, $errors);
run_sql($conn, "ALTER TABLE sph_submissions ADD COLUMN dokumen_sph VARCHAR(255) NULL AFTER harga_penawaran", "Kolom sph_submissions.dokumen_sph", $results, $errors);

// ============================================================
// 4. Tambah kolom P2P tracking ke purchase_orders
// ============================================================
$po_columns = [
    "confirmed_by_vendor_at DATETIME NULL",
    "shipped_at DATETIME NULL",
    "no_resi VARCHAR(100) NULL",
    "foto_surat_jalan VARCHAR(255) NULL",
    "received_at DATETIME NULL",
    "received_by INT NULL",
    "jumlah_diterima INT DEFAULT 0",
    "invoice_number VARCHAR(100) NULL",
    "invoice_file VARCHAR(255) NULL",
    "invoice_submitted_at DATETIME NULL",
    "payment_matched_at DATETIME NULL",
    "payment_completed_at DATETIME NULL",
    "bukti_bayar_file VARCHAR(255) NULL",
    "payment_notes TEXT NULL",
    "approved_by INT NULL",
    "approved_at DATETIME NULL",
];

foreach ($po_columns as $col_def) {
    $col_name = explode(' ', trim($col_def))[0];
    run_sql($conn, "ALTER TABLE purchase_orders ADD COLUMN $col_def", "Kolom purchase_orders.$col_name", $results, $errors);
}

// ============================================================
// 5. Fix status mismatch
// ============================================================
$fix = mysqli_query($conn, "UPDATE purchase_orders SET status = 'Pending Approval' WHERE status = 'Waiting Approval'");
$affected = mysqli_affected_rows($conn);
$results[] = "✅ Fix status mismatch: $affected baris diperbarui";

// ============================================================
// 6. Pastikan tabel inbound_history punya kolom po_id
// ============================================================
run_sql($conn, "ALTER TABLE inbound_history ADD COLUMN po_id INT NULL", "Kolom inbound_history.po_id", $results, $errors);

// ============================================================
// 7. Tabel Invoices (Hutang Dagang)
// ============================================================
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        invoice_number VARCHAR(100) NOT NULL,
        vendor_id INT NOT NULL,
        inventory_id INT NOT NULL,
        jumlah_barang INT NOT NULL DEFAULT 0,
        total_tagihan DECIMAL(15,2) NOT NULL DEFAULT 0,
        tanggal_terima_gudang DATETIME NOT NULL,
        tanggal_jatuh_tempo DATE NOT NULL,
        status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid',
        paid_at DATETIME NULL,
        paid_by INT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
        FOREIGN KEY (vendor_id) REFERENCES vendors(id),
        FOREIGN KEY (inventory_id) REFERENCES inventory(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "Tabel invoices", $results, $errors);

// ============================================================
// 8. Fix status mismatch (Pending Manager)
// ============================================================
$fix2 = mysqli_query($conn, "UPDATE purchase_orders SET status = 'Pending Manager' WHERE status = 'Pending Approval'");
$affected2 = mysqli_affected_rows($conn);
$results[] = "✅ Rename status 'Pending Approval' to 'Pending Manager': $affected2 baris diperbarui";

// ============================================================
// Output Hasil
// ============================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Migration | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-3xl shadow-lg border w-full max-w-2xl p-10">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-14 h-14 bg-red-600 rounded-2xl flex items-center justify-center shadow-lg shadow-red-500/30">
                <i class="fa-solid fa-database text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Veridex Migration</h1>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Procure-to-Pay Database Setup</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 p-4 rounded-xl mb-6">
            <h3 class="font-black text-red-600 text-sm mb-2"><i class="fa-solid fa-circle-xmark mr-1"></i> Errors</h3>
            <?php foreach($errors as $e): ?>
            <p class="text-sm text-red-500 font-medium"><?= $e ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="space-y-2 mb-8">
            <?php foreach($results as $r): ?>
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl text-sm font-medium text-slate-700">
                <?= $r ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($errors)): ?>
        <div class="bg-green-50 border-2 border-green-500 p-6 rounded-2xl text-center">
            <i class="fa-solid fa-circle-check text-5xl text-green-500 mb-3 block"></i>
            <h3 class="font-black text-green-700 text-lg">Migration Selesai!</h3>
            <p class="text-green-600 text-sm mt-1">Semua tabel dan kolom berhasil dibuat. Sistem siap digunakan.</p>
        </div>
        <?php endif; ?>

        <div class="mt-8 text-center">
            <a href="login.php" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-red-600 transition inline-block shadow-lg">
                <i class="fa-solid fa-arrow-right mr-2"></i> Ke Halaman Login
            </a>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                sidebar.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('sidebar-open');
                if (overlay) overlay.classList.toggle('overlay-open');
            }
        }
    </script>

</body>
</html>
