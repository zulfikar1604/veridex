<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    die("Akses Ditolak.");
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$where_inbound = "DATE(tanggal_masuk) >= '$start_date' AND DATE(tanggal_masuk) <= '$end_date'";
$where_outbound = "DATE(tanggal_keluar) >= '$start_date' AND DATE(tanggal_keluar) <= '$end_date'";

$q_inbound = mysqli_query($conn, "SELECT SUM(jumlah_masuk) as total FROM inbound_history WHERE $where_inbound");
$total_inbound = mysqli_fetch_assoc($q_inbound)['total'] ?? 0;

$q_outbound = mysqli_query($conn, "SELECT SUM(jumlah_keluar) as total FROM outbound_history WHERE $where_outbound");
$total_outbound = mysqli_fetch_assoc($q_outbound)['total'] ?? 0;

$q_kritis = mysqli_query($conn, "SELECT COUNT(id) as total FROM inventory WHERE status_label = 'Kritis'");
$total_kritis = mysqli_fetch_assoc($q_kritis)['total'] ?? 0;

// Detail Inbound Outbound (Sample for table)
$q_detail_inbound = mysqli_query($conn, "SELECT * FROM inbound_history WHERE $where_inbound ORDER BY tanggal_masuk DESC LIMIT 100");
$q_detail_outbound = mysqli_query($conn, "SELECT * FROM outbound_history WHERE $where_outbound ORDER BY tanggal_keluar DESC LIMIT 100");

$q_manager = mysqli_query($conn, "SELECT username FROM users WHERE role = 'Manager Gudang' LIMIT 1");
$manager_name = ($q_manager && mysqli_num_rows($q_manager) > 0) ? mysqli_fetch_assoc($q_manager)['username'] : '____________________';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Gudang - VERIDEX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { margin: 1.5cm; size: A4 portrait; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: 'Times New Roman', serif; background-color: white;}
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        body { background: #525659; font-family: 'Times New Roman', serif; }
        .print-container { background: white; max-width: 21cm; min-height: 29.7cm; margin: 2rem auto; padding: 2cm; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .table-print th { background-color: #f1f5f9 !important; font-weight: bold; }
        .table-print th, .table-print td { border: 1px solid #cbd5e1; padding: 0.5rem; font-size: 0.75rem; }
        .stamp-approved {
            position: absolute;
            color: #ef4444;
            border: 4px double #ef4444;
            padding: 0.25rem 1rem;
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transform: rotate(-10deg);
            opacity: 0.8;
            border-radius: 8px;
            pointer-events: none;
            z-index: 10;
            margin-top: -50px;
            margin-left: 20px;
        }
    </style>
</head>
<body class="text-slate-900">
    
    <div class="no-print text-center py-4 bg-slate-900 text-white fixed top-0 w-full z-50 shadow-xl">
        <p class="font-bold mb-2">Pratinjau Cetak Laporan Gudang</p>
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition">
            <i class="fa-solid fa-print"></i> Cetak Laporan
        </button>
        <button onclick="window.close()" class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition ml-2">
            Tutup
        </button>
    </div>

    <div class="print-container mt-24 md:mt-8">
        <!-- KOP SURAT -->
        <div class="flex justify-between items-center border-b-4 border-slate-900 pb-6 mb-8">
            <div>
                <h1 class="text-4xl font-black text-indigo-600 tracking-wider">VERIDEX</h1>
                <p class="text-sm font-bold tracking-widest text-slate-500 uppercase mt-1">Smart SCM System</p>
            </div>
            <div class="text-right text-xs text-slate-600">
                <p><strong>DEPARTEMEN WAREHOUSE</strong></p>
                <p>Jl. Jend. Sudirman Kav. 21, Jakarta</p>
                <p>Phone: (021) 555-0192 | Email: warehouse@veridex.com</p>
            </div>
        </div>

        <div class="text-center mb-8">
            <h2 class="text-xl font-bold uppercase underline mb-2">Laporan Lalu Lintas Barang (Inbound & Outbound)</h2>
            <p class="text-sm font-bold">Periode: <?= date('d/m/Y', strtotime($start_date)) ?> s.d. <?= date('d/m/Y', strtotime($end_date)) ?></p>
        </div>

        <!-- KPI -->
        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4">A. Ringkasan Volume Barang</h3>
        <div class="grid grid-cols-3 gap-4 mb-8 text-center">
            <div class="border border-slate-300 p-4 rounded-lg bg-green-50">
                <p class="text-[10px] text-green-700 font-bold uppercase mb-2">Total Inbound (Masuk)</p>
                <p class="text-3xl font-black text-green-700"><?= number_format($total_inbound) ?> <span class="text-sm">Unit</span></p>
            </div>
            <div class="border border-slate-300 p-4 rounded-lg bg-orange-50">
                <p class="text-[10px] text-orange-700 font-bold uppercase mb-2">Total Outbound (Keluar)</p>
                <p class="text-3xl font-black text-orange-700"><?= number_format($total_outbound) ?> <span class="text-sm">Unit</span></p>
            </div>
            <div class="border-2 border-red-500 p-4 rounded-lg bg-red-50">
                <p class="text-[10px] text-red-700 font-bold uppercase mb-2">Barang Kritis Saat Ini (ROP)</p>
                <p class="text-3xl font-black text-red-700"><?= number_format($total_kritis) ?> <span class="text-sm">SKU</span></p>
            </div>
        </div>

        <!-- TABEL INBOUND -->
        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4 mt-8">B. Sampel Transaksi Inbound Terakhir</h3>
        <table class="w-full text-left table-print mb-8">
            <thead>
                <tr>
                    <th class="w-12 text-center">No</th>
                    <th>Waktu Masuk</th>
                    <th>Nama Barang</th>
                    <th>Vendor/Keterangan</th>
                    <th class="text-center">Jumlah (Qty)</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($in = mysqli_fetch_assoc($q_detail_inbound)): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($in['tanggal_masuk'])) ?></td>
                    <td class="uppercase"><?= htmlspecialchars($in['nama_barang']) ?></td>
                    <td class="text-xs"><?= htmlspecialchars($in['keterangan'] ?? '-') ?></td>
                    <td class="text-center font-bold"><?= $in['jumlah_masuk'] ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($q_detail_inbound) == 0): ?>
                <tr><td colspan="5" class="text-center italic">Tidak ada Inbound.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TABEL OUTBOUND -->
        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4 mt-8">C. Sampel Transaksi Outbound Terakhir</h3>
        <table class="w-full text-left table-print mb-12">
            <thead>
                <tr>
                    <th class="w-12 text-center">No</th>
                    <th>Waktu Keluar</th>
                    <th>Nama Barang</th>
                    <th>Penerima/Keterangan</th>
                    <th class="text-center">Jumlah (Qty)</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($out = mysqli_fetch_assoc($q_detail_outbound)): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($out['tanggal_keluar'])) ?></td>
                    <td class="uppercase"><?= htmlspecialchars($out['nama_barang']) ?></td>
                    <td class="text-xs"><?= htmlspecialchars($out['keterangan'] ?? '-') ?></td>
                    <td class="text-center font-bold text-orange-600">-<?= $out['jumlah_keluar'] ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($q_detail_outbound) == 0): ?>
                <tr><td colspan="5" class="text-center italic">Tidak ada Outbound.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TANDA TANGAN -->
        <div class="flex justify-end mt-16">
            <div class="text-center w-64 relative">
                <p class="text-sm mb-16">Jakarta, <?= date('d F Y') ?></p>
                <div class="relative inline-block w-full">
                    <div class="stamp-approved">AUTHORIZED</div>
                    <p class="text-sm font-bold underline uppercase border-b border-black inline-block mb-1 px-4 relative z-0 w-full"><?= htmlspecialchars($manager_name) ?></p>
                </div>
                <p class="text-xs font-bold uppercase text-slate-600">Manager Warehouse</p>
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
