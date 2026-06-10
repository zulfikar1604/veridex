<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Finance', 'Finance'])) {
    die("Akses Ditolak.");
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$where_clause = "DATE(po.created_at) >= '$start_date' AND DATE(po.created_at) <= '$end_date'";

// Data Summary (Hutang AP)
$q_hutang = mysqli_query($conn, "SELECT SUM(po.total_harga) as total, COUNT(*) as qty FROM purchase_orders po WHERE po.status IN ('Invoice Submitted', 'Payment Approved') AND $where_clause");
$hutang = mysqli_fetch_assoc($q_hutang);
$total_hutang_rp = $hutang['total'] ?? 0;

// Data Summary (Paid)
$q_paid = mysqli_query($conn, "SELECT SUM(po.total_harga) as total, COUNT(*) as qty FROM purchase_orders po WHERE po.status = 'Payment Completed' AND $where_clause");
$paid = mysqli_fetch_assoc($q_paid);
$total_paid_rp = $paid['total'] ?? 0;

// Query Hutang
$list_hutang = mysqli_query($conn, "
    SELECT po.po_number, v.nama_perusahaan as vendor_name, po.total_harga, po.status 
    FROM purchase_orders po 
    JOIN vendors v ON po.vendor_id = v.id 
    WHERE po.status IN ('Invoice Submitted', 'Payment Approved') AND $where_clause 
    ORDER BY po.created_at DESC LIMIT 10
");

// Query Paid
$list_paid = mysqli_query($conn, "
    SELECT po.po_number, v.nama_perusahaan as vendor_name, po.total_harga, po.status 
    FROM purchase_orders po 
    JOIN vendors v ON po.vendor_id = v.id 
    WHERE po.status = 'Payment Completed' AND $where_clause 
    ORDER BY po.created_at DESC LIMIT 10
");

// Penandatangan Finance
$q_manager = mysqli_query($conn, "SELECT username FROM users WHERE role = 'Manager Finance' LIMIT 1");
$manager_name = ($q_manager && mysqli_num_rows($q_manager) > 0) ? mysqli_fetch_assoc($q_manager)['username'] : 'Manager Finance';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Analytics Report - VERIDEX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { margin: 1.5cm; size: A4 portrait; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: 'Times New Roman', serif; background-color: white;}
            .no-print { display: none !important; }
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
        <p class="font-bold mb-2">Pratinjau Laporan Keuangan</p>
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition">
            <i class="fa-solid fa-print"></i> Cetak Dokumen
        </button>
        <button onclick="window.close()" class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition ml-2">
            Tutup
        </button>
    </div>

    <div class="print-container mt-24 md:mt-8">
        <!-- KOP SURAT -->
        <div class="flex justify-between items-center border-b-4 border-slate-900 pb-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-black text-2xl">Vx</div>
                <div>
                    <h1 class="text-3xl font-black tracking-wider uppercase">VERIDEX CORP.</h1>
                    <p class="text-sm font-bold tracking-widest text-slate-500 uppercase mt-1">Finance & Accounting Department</p>
                </div>
            </div>
            <div class="text-right text-xs text-slate-600">
                <p>Jl. Jend. Sudirman Kav. 21, Jakarta</p>
                <p>Phone: (021) 555-0192</p>
                <p>Email: finance@veridex.com</p>
            </div>
        </div>

        <div class="text-center mb-10">
            <h2 class="text-xl font-black uppercase underline mb-1">Laporan Arus Kas Pembelian</h2>
            <p class="text-sm font-bold uppercase tracking-widest text-slate-500">Periode: <?= date('d F Y', strtotime($start_date)) ?> s/d <?= date('d F Y', strtotime($end_date)) ?></p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div class="bg-orange-50 border border-orange-200 p-4 rounded-lg">
                <p class="text-[10px] font-black uppercase tracking-widest text-orange-600 mb-1">Total Hutang Dagang (AP)</p>
                <p class="text-2xl font-black text-slate-800">Rp <?= number_format($total_hutang_rp, 0, ',', '.') ?></p>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-lg">
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-1">Total Pembayaran Lunas</p>
                <p class="text-2xl font-black text-slate-800">Rp <?= number_format($total_paid_rp, 0, ',', '.') ?></p>
            </div>
        </div>

        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4 mt-8">Rekapitulasi Hutang Belum Dibayar (Top 10)</h3>
        <table class="w-full text-left table-print mb-8">
            <thead>
                <tr>
                    <th class="w-12 text-center">No</th>
                    <th>Nomor PO</th>
                    <th>Nama Vendor</th>
                    <th>Status Berjalan</th>
                    <th class="text-right">Total Tagihan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($list_hutang) > 0): $no=1; while($row = mysqli_fetch_assoc($list_hutang)): ?>
                <tr>
                    <td class="text-center font-bold"><?= $no++ ?></td>
                    <td class="font-bold uppercase"><?= $row['po_number'] ?></td>
                    <td class="uppercase"><?= $row['vendor_name'] ?></td>
                    <td><?= $row['status'] ?></td>
                    <td class="text-right font-bold text-orange-600">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="text-center py-4 font-bold text-slate-400">Tidak ada hutang pada periode ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4 mt-8">Rekapitulasi Pembayaran Sukses (Top 10)</h3>
        <table class="w-full text-left table-print mb-8">
            <thead>
                <tr>
                    <th class="w-12 text-center">No</th>
                    <th>Nomor PO</th>
                    <th>Nama Vendor</th>
                    <th>Status Berjalan</th>
                    <th class="text-right">Total Tagihan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($list_paid) > 0): $no=1; while($row = mysqli_fetch_assoc($list_paid)): ?>
                <tr>
                    <td class="text-center font-bold"><?= $no++ ?></td>
                    <td class="font-bold uppercase"><?= $row['po_number'] ?></td>
                    <td class="uppercase"><?= $row['vendor_name'] ?></td>
                    <td><?= $row['status'] ?></td>
                    <td class="text-right font-bold text-emerald-600">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="text-center py-4 font-bold text-slate-400">Belum ada pembayaran lunas pada periode ini.</td></tr>
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
                <p class="text-xs font-bold uppercase text-slate-600">Manager Finance</p>
            </div>
        </div>

    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>
