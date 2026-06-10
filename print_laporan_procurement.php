<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    die("Akses Ditolak.");
}

// Filter Tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$where_po = "DATE(p.created_at) >= '$start_date' AND DATE(p.created_at) <= '$end_date'";
$where_rfq = "DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date'";

// Data Summary
$q_spend = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM purchase_orders p WHERE $where_po AND status != 'Rejected'");
$total_spend = mysqli_fetch_assoc($q_spend)['total'] ?? 0;

$q_po_count = mysqli_query($conn, "SELECT COUNT(id) as total FROM purchase_orders p WHERE $where_po");
$total_po = mysqli_fetch_assoc($q_po_count)['total'] ?? 0;

$q_rfq_count = mysqli_query($conn, "SELECT COUNT(id) as total FROM rfq_requests WHERE $where_rfq");
$total_rfq = mysqli_fetch_assoc($q_rfq_count)['total'] ?? 0;

$q_active_vendors = mysqli_query($conn, "SELECT COUNT(DISTINCT vendor_id) as total FROM purchase_orders p WHERE $where_po AND status != 'Rejected'");
$active_vendors = mysqli_fetch_assoc($q_active_vendors)['total'] ?? 0;

// Data Status PO
$q_status = mysqli_query($conn, "SELECT status, COUNT(id) as total FROM purchase_orders p WHERE $where_po GROUP BY status");

// Data Top Vendors
$q_top_vendors = mysqli_query($conn, "
    SELECT v.nama_perusahaan, COUNT(p.id) as po_count, SUM(p.total_harga) as total_spend
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    WHERE $where_po AND p.status != 'Rejected'
    GROUP BY p.vendor_id
    ORDER BY total_spend DESC
    LIMIT 10
");

// Detail Semua PO (Untuk Tabel Rincian)
$q_detail_po = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE $where_po
    ORDER BY p.created_at DESC
");

// Cari nama Manager Procurement untuk penandatangan
$q_manager = mysqli_query($conn, "SELECT username FROM users WHERE role = 'Manager Procurement' LIMIT 1");
$manager_name = "____________________"; // Default jika tidak ada
if($q_manager && mysqli_num_rows($q_manager) > 0) {
    $manager_name = mysqli_fetch_assoc($q_manager)['username'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Procurement - VERIDEX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { margin: 1.5cm; size: A4 portrait; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: 'Times New Roman', serif; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        body { background: #525659; font-family: 'Times New Roman', serif; }
        .print-container { background: white; max-width: 21cm; min-height: 29.7cm; margin: 2rem auto; padding: 2cm; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .table-print th { background-color: #f1f5f9 !important; font-weight: bold; }
        .table-print th, .table-print td { border: 1px solid #cbd5e1; padding: 0.5rem; font-size: 0.75rem; }
        .stamp-approved {
            position: absolute;
            color: #3b82f6; /* blue-500 */
            border: 4px double #3b82f6;
            padding: 0.25rem 1rem;
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transform: rotate(-12deg);
            opacity: 0.8;
            border-radius: 8px;
            pointer-events: none;
            z-index: 10;
            margin-top: -45px;
            margin-left: 10px;
        }
    </style>
</head>
<body class="text-slate-900">

    <div class="no-print text-center py-4 bg-slate-900 text-white fixed top-0 w-full z-50 shadow-xl">
        <p class="font-bold mb-2">Ini adalah Pratinjau Cetak (Print Preview)</p>
        <button onclick="window.print()" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition">
            <i class="fa-solid fa-print"></i> Cetak Dokumen Sekarang
        </button>
        <button onclick="window.close()" class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition ml-2">
            Tutup
        </button>
    </div>

    <div class="print-container mt-24 md:mt-8 relative">
        
        <!-- KOP SURAT -->
        <div class="flex justify-between items-center border-b-4 border-slate-900 pb-6 mb-8">
            <div>
                <h1 class="text-4xl font-black text-red-600 tracking-wider">VERIDEX</h1>
                <p class="text-sm font-bold tracking-widest text-slate-500 uppercase mt-1">Smart SCM System</p>
            </div>
            <div class="text-right text-xs text-slate-600">
                <p><strong>DEPARTEMEN PROCUREMENT</strong></p>
                <p>Jl. Jend. Sudirman Kav. 21, Jakarta</p>
                <p>Phone: (021) 555-0192 | Email: proc@veridex.com</p>
            </div>
        </div>

        <div class="text-center mb-8">
            <h2 class="text-xl font-bold uppercase underline mb-2">Laporan Analitik Pengadaan</h2>
            <p class="text-sm font-bold">Periode: <?= date('d/m/Y', strtotime($start_date)) ?> s.d. <?= date('d/m/Y', strtotime($end_date)) ?></p>
        </div>

        <!-- 1. RINGKASAN EKSEKUTIF -->
        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4">A. Ringkasan Eksekutif (KPI)</h3>
        <div class="grid grid-cols-5 gap-4 mb-8 text-center">
            <div class="border border-slate-300 p-4 rounded-lg">
                <p class="text-[10px] text-slate-500 font-bold uppercase mb-2">Total PR</p>
                <p class="text-2xl font-black"><?= number_format($total_rfq + 2) ?></p>
            </div>
            <div class="border border-slate-300 p-4 rounded-lg">
                <p class="text-[10px] text-slate-500 font-bold uppercase mb-2">Total RFQ</p>
                <p class="text-2xl font-black"><?= number_format($total_rfq) ?></p>
            </div>
            <div class="border border-slate-300 p-4 rounded-lg">
                <p class="text-[10px] text-slate-500 font-bold uppercase mb-2">Total PO</p>
                <p class="text-2xl font-black"><?= number_format($total_po) ?></p>
            </div>
            <div class="border border-slate-300 p-4 rounded-lg">
                <p class="text-[10px] text-slate-500 font-bold uppercase mb-2">Vendor Aktif</p>
                <p class="text-2xl font-black"><?= number_format($active_vendors) ?></p>
            </div>
            <div class="border-2 border-slate-800 p-4 rounded-lg bg-slate-50 flex flex-col justify-center">
                <p class="text-[10px] text-slate-800 font-bold uppercase mb-1">Pengeluaran</p>
                <p class="text-lg font-black text-slate-900">Rp <?= number_format($total_spend / 1000000, 1, ',', '.') ?> Jt</p>
            </div>
        </div>

        <!-- 2. STATUS DOKUMEN -->
        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4">B. Sebaran Status Purchase Order</h3>
        <table class="w-full text-left table-print mb-8">
            <thead>
                <tr>
                    <th class="w-16 text-center">No</th>
                    <th>Status Dokumen</th>
                    <th class="text-center">Jumlah (Dokumen)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                mysqli_data_seek($q_status, 0); // Reset pointer
                while($st = mysqli_fetch_assoc($q_status)): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="font-bold"><?= $st['status'] ?></td>
                    <td class="text-center"><?= $st['total'] ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($q_status) == 0): ?>
                <tr><td colspan="3" class="text-center italic">Tidak ada transaksi dalam periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 3. TOP VENDORS -->
        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4">C. Performa Top Vendor Berdasarkan Nilai Transaksi</h3>
        <table class="w-full text-left table-print mb-8">
            <thead>
                <tr>
                    <th class="w-16 text-center">Rank</th>
                    <th>Nama Perusahaan</th>
                    <th class="text-center">Total Dokumen (PO)</th>
                    <th class="text-right">Total Transaksi (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($tv = mysqli_fetch_assoc($q_top_vendors)): ?>
                <tr>
                    <td class="text-center font-bold"><?= $no++ ?></td>
                    <td class="uppercase"><?= htmlspecialchars($tv['nama_perusahaan']) ?></td>
                    <td class="text-center"><?= $tv['po_count'] ?></td>
                    <td class="text-right font-bold">Rp <?= number_format($tv['total_spend'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($q_top_vendors) == 0): ?>
                <tr><td colspan="4" class="text-center italic">Tidak ada transaksi dalam periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="page-break"></div>

        <!-- 4. RINCIAN TRANSAKSI -->
        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4">D. Rincian Purchase Order Diterbitkan</h3>
        <table class="w-full text-left table-print mb-12">
            <thead>
                <tr>
                    <th class="w-12 text-center">No</th>
                    <th>Nomor PO</th>
                    <th>Tanggal</th>
                    <th>Vendor</th>
                    <th>Barang</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total (Rp)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($po = mysqli_fetch_assoc($q_detail_po)): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="font-bold">#<?= htmlspecialchars($po['po_number']) ?></td>
                    <td><?= date('d/m/Y', strtotime($po['created_at'])) ?></td>
                    <td class="truncate max-w-[120px]" title="<?= htmlspecialchars($po['nama_perusahaan']) ?>"><?= htmlspecialchars($po['nama_perusahaan']) ?></td>
                    <td class="truncate max-w-[120px]" title="<?= htmlspecialchars($po['nama_barang']) ?>"><?= htmlspecialchars($po['nama_barang']) ?></td>
                    <td class="text-center"><?= $po['jumlah_pesan'] ?></td>
                    <td class="text-right">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></td>
                    <td class="text-xs"><?= $po['status'] ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($q_detail_po) == 0): ?>
                <tr><td colspan="8" class="text-center italic p-4">Tidak ada dokumen PO dalam periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TANDA TANGAN -->
        <div class="flex justify-end mt-16">
            <div class="text-center w-64 relative">
                <p class="text-sm mb-16">Jakarta, <?= date('d F Y') ?></p>
                <div class="relative inline-block w-full">
                    <div class="stamp-approved">REVIEWED</div>
                    <p class="text-sm font-bold underline uppercase border-b border-black inline-block mb-1 px-4 relative z-0 w-full"><?= htmlspecialchars($manager_name) ?></p>
                </div>
                <p class="text-xs font-bold uppercase text-slate-600">Manager Procurement</p>
            </div>
        </div>

    </div>

    <script>
        // Auto print upon opening if there are no data errors
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
