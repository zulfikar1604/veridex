<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    die("Akses Ditolak.");
}

$inventory_id = intval($_GET['inventory_id'] ?? 0);
if ($inventory_id <= 0) die("ID Barang tidak valid.");

$query = mysqli_query($conn, "SELECT * FROM inventory WHERE id = $inventory_id");
$item = mysqli_fetch_assoc($query);

if (!$item) die("Barang tidak ditemukan.");

// Mockup Data PR
$pr_number = "PR-" . date('Y') . "-" . str_pad($item['id'], 4, '0', STR_PAD_LEFT);
$pr_date = date('d F Y');
$need_date = date('d F Y', strtotime('+14 days')); // Target butuh 2 minggu
$stok_maksimum = isset($item['stok_maksimum']) ? $item['stok_maksimum'] : ($item['stok_kritis'] * 2);
$qty_requested = max(1, $stok_maksimum - $item['stok_saat_ini']); // Permintaan standar (sampai maksimum)

// Penandatangan Gudang
$q_warehouse = mysqli_query($conn, "SELECT username FROM users WHERE role = 'Manager Gudang' LIMIT 1");
$warehouse_manager = ($q_warehouse && mysqli_num_rows($q_warehouse) > 0) ? mysqli_fetch_assoc($q_warehouse)['username'] : 'Manager Gudang';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pr_number ?> - VERIDEX PR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { margin: 1.5cm; size: A4 portrait; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: 'Times New Roman', serif; background-color: white;}
            .no-print { display: none !important; }
        }
        body { background: #525659; font-family: 'Times New Roman', serif; }
        .print-container { background: white; max-width: 21cm; min-height: 29.7cm; margin: 2rem auto; padding: 2cm; box-shadow: 0 0 20px rgba(0,0,0,0.5); position: relative;}
        .table-print th { background-color: #f1f5f9 !important; font-weight: bold; }
        .table-print th, .table-print td { border: 1px solid #cbd5e1; padding: 0.75rem; font-size: 0.875rem; }
        .watermark { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 100px; color: rgba(200, 200, 200, 0.2); font-weight: black; white-space: nowrap; pointer-events: none; }
        .stamp-approved {
            position: absolute;
            color: #22c55e;
            border: 4px double #22c55e;
            padding: 0.25rem 1rem;
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transform: rotate(-15deg);
            opacity: 0.8;
            border-radius: 8px;
            pointer-events: none;
            z-index: 10;
            margin-top: -40px;
            margin-left: -30px;
        }
        .stamp-authorized {
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
            margin-top: -30px;
            margin-left: -20px;
        }
    </style>
</head>
<body class="text-slate-900">

    <div class="no-print text-center py-4 bg-slate-900 text-white fixed top-0 w-full z-50 shadow-xl">
        <p class="font-bold mb-2">Pratinjau Dokumen Purchase Requisition</p>
        <button onclick="window.print()" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition">
            <i class="fa-solid fa-print"></i> Cetak PR
        </button>
        <button onclick="window.close()" class="bg-slate-700 hover:bg-slate-600 px-6 py-2 rounded-lg font-black uppercase tracking-widest transition ml-2">
            Tutup
        </button>
    </div>

    <div class="print-container mt-24 md:mt-8">
        <div class="watermark">SYSTEM GENERATED</div>
        
        <!-- KOP SURAT -->
        <div class="flex justify-between items-center border-b-4 border-slate-900 pb-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-red-600 rounded-lg flex items-center justify-center text-white font-black text-2xl">Vx</div>
                <div>
                    <h1 class="text-3xl font-black tracking-wider uppercase">VERIDEX CORP.</h1>
                    <p class="text-sm font-bold tracking-widest text-slate-500 uppercase mt-1">Smart Warehouse Department</p>
                </div>
            </div>
            <div class="text-right text-xs text-slate-600">
                <p>Jl. Jend. Sudirman Kav. 21, Jakarta</p>
                <p>Phone: (021) 555-0192</p>
                <p>Email: warehouse@veridex.com</p>
            </div>
        </div>

        <div class="text-center mb-10">
            <h2 class="text-2xl font-black uppercase underline mb-1">Purchase Requisition (PR)</h2>
            <p class="text-sm font-bold uppercase tracking-widest text-slate-500">Nomor: <?= $pr_number ?></p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <p class="mb-2"><span class="inline-block w-32 font-bold text-slate-500 uppercase">Tanggal PR</span> : <span class="font-bold"><?= $pr_date ?></span></p>
                <p class="mb-2"><span class="inline-block w-32 font-bold text-slate-500 uppercase">Departemen</span> : Warehouse & Logistik</p>
                <p class="mb-2"><span class="inline-block w-32 font-bold text-slate-500 uppercase">Alasan PR</span> : <span class="text-red-600 font-bold italic">Auto-Trigger ROP (Stok Kritis)</span></p>
            </div>
            <div>
                <p class="mb-2"><span class="inline-block w-32 font-bold text-slate-500 uppercase">Target Dibutuhkan</span> : <span class="font-bold"><?= $need_date ?></span></p>
                <p class="mb-2"><span class="inline-block w-32 font-bold text-slate-500 uppercase">Tipe Barang</span> : Material Gudang</p>
                <p class="mb-2"><span class="inline-block w-32 font-bold text-slate-500 uppercase">Status Sistem</span> : <span class="bg-slate-200 px-2 py-1 font-bold rounded">Submitted to Procurement</span></p>
            </div>
        </div>

        <h3 class="text-sm font-bold uppercase bg-slate-100 p-2 border-l-4 border-slate-900 mb-4">Rincian Permintaan Barang</h3>
        <table class="w-full text-left table-print mb-8">
            <thead>
                <tr>
                    <th class="w-12 text-center">No</th>
                    <th>Nama Barang / Deskripsi</th>
                    <th class="w-24 text-center">SKU</th>
                    <th class="text-center">Sisa Stok</th>
                    <th class="text-center">Batas ROP</th>
                    <th class="text-center bg-red-50 text-red-700">Qty Diminta</th>
                    <th class="text-center">Satuan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center font-bold">1</td>
                    <td class="font-bold uppercase"><?= htmlspecialchars($item['nama_barang']) ?></td>
                    <td class="text-center font-mono text-xs"><?= htmlspecialchars($item['sku']) ?></td>
                    <td class="text-center font-bold text-red-600"><?= $item['stok_saat_ini'] ?></td>
                    <td class="text-center"><?= $item['stok_kritis'] ?></td>
                    <td class="text-center font-black text-red-600 text-lg bg-red-50"><?= $qty_requested ?></td>
                    <td class="text-center font-bold uppercase"><?= $item['unit'] ?></td>
                </tr>
            </tbody>
        </table>

        <div class="border border-slate-300 bg-slate-50 p-4 rounded-lg mb-12 text-sm italic">
            <p class="font-bold mb-1 not-italic">Catatan Sistem:</p>
            <p>Sistem VERIDEX mendeteksi bahwa stok barang ini telah menyentuh atau berada di bawah batas Reorder Point (ROP). Dokumen Permintaan Pembelian (PR) ini diterbitkan secara otomatis dan diteruskan ke Departemen Procurement untuk segera diproses menjadi Request for Quotation (RFQ).</p>
        </div>

        <!-- TANDA TANGAN -->
        <div class="grid grid-cols-3 gap-8 text-center mt-12">
            <div>
                <p class="text-xs font-bold uppercase text-slate-500 mb-20">Sistem Verifikasi</p>
                <div class="relative inline-block">
                    <div class="stamp-approved">VERIFIED</div>
                    <p class="text-sm font-black italic underline uppercase border-b border-black inline-block mb-1 px-4 relative z-0">AUTO-APPROVED</p>
                </div>
                <p class="text-[10px] font-bold uppercase text-slate-500 mt-1">Veridex ROP Engine</p>
            </div>
            <div></div>
            <div>
                <p class="text-xs font-bold uppercase text-slate-500 mb-20">Mengetahui</p>
                <div class="relative inline-block">
                    <div class="stamp-authorized">AUTHORIZED</div>
                    <p class="text-sm font-bold underline uppercase border-b border-black inline-block mb-1 px-4 relative z-0"><?= htmlspecialchars($warehouse_manager) ?></p>
                </div>
                <p class="text-[10px] font-bold uppercase text-slate-500 mt-1">Manager Warehouse</p>
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
