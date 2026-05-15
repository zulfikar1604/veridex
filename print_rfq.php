<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$rfq_id = intval($_GET['id'] ?? 0);

$query_rfq = mysqli_query($conn, "
    SELECT r.*, i.nama_barang, i.sku, i.unit, u.username as creator_name
    FROM rfq_requests r
    JOIN inventory i ON r.inventory_id = i.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE r.id = $rfq_id
") or handle_db_error($conn);

$rfq = mysqli_fetch_assoc($query_rfq);

if (!$rfq) {
    die("RFQ tidak ditemukan.");
}

// Ambil list vendor yang diundang
$query_vendors = mysqli_query($conn, "
    SELECT v.nama_perusahaan 
    FROM rfq_vendors rv
    JOIN vendors v ON rv.vendor_id = v.id
    WHERE rv.rfq_id = $rfq_id
");
$invited_vendors = [];
while($v = mysqli_fetch_assoc($query_vendors)) {
    $invited_vendors[] = $v['nama_perusahaan'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print RFQ - <?= htmlspecialchars($rfq['rfq_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .print-area { background: white; max-w: 21cm; min-h: 29.7cm; margin: 2rem auto; padding: 2cm; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
        
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .print-area { margin: 0; padding: 1cm; box-shadow: none; max-w: 100%; min-h: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="text-slate-800">

    <div class="fixed top-4 right-4 no-print flex gap-2">
        <button onclick="window.print()" class="bg-red-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-red-700">
            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print PDF
        </button>
        <button onclick="window.close()" class="bg-slate-200 text-slate-700 px-6 py-2 rounded-lg font-bold shadow hover:bg-slate-300">Tutup</button>
    </div>

    <div class="print-area">
        <!-- Header -->
        <div class="flex justify-between items-start border-b-4 border-slate-900 pb-6 mb-8">
            <div>
                <h1 class="text-4xl font-black text-red-600 tracking-tighter uppercase">VERIDEX</h1>
                <p class="text-xs font-bold text-slate-500 tracking-widest mt-1">PROCUREMENT SYSTEM</p>
                <div class="mt-4 text-sm text-slate-600">
                    <p>Jl. Jend. Sudirman No. 123, Jakarta 12190</p>
                    <p>Tel: (021) 555-0123 | Email: procurement@veridex.co.id</p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-3xl font-black text-slate-800 uppercase mb-2">Request For Quotation</h2>
                <div class="inline-block bg-slate-100 p-3 rounded-lg text-left border border-slate-200">
                    <table class="text-sm">
                        <tr><td class="font-bold text-slate-500 pr-4">RFQ Number</td><td class="font-black"><?= htmlspecialchars($rfq['rfq_number']) ?></td></tr>
                        <tr><td class="font-bold text-slate-500 pr-4">Date Issued</td><td class="font-bold"><?= date('d F Y', strtotime($rfq['created_at'])) ?></td></tr>
                        <tr><td class="font-bold text-slate-500 pr-4">Submission Deadline</td><td class="font-black text-red-600"><?= date('d F Y H:i', strtotime($rfq['deadline'])) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="font-black text-lg mb-2 uppercase">Kepada Yth. Vendor Terpilih,</h3>
            <p class="text-sm text-slate-600 leading-relaxed">Kami mengundang perusahaan Anda untuk berpartisipasi dalam proses pengadaan (Bidding) untuk kebutuhan material/barang di Veridex. Silakan submit Surat Penawaran Harga (SPH) beserta dokumen pendukung sebelum batas waktu yang ditentukan.</p>
        </div>

        <!-- Items Table -->
        <h3 class="font-black text-md mb-3 uppercase tracking-widest text-slate-500">Spesifikasi Kebutuhan:</h3>
        <table class="w-full mb-8 border border-slate-300">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-black uppercase tracking-widest">No</th>
                    <th class="py-3 px-4 text-left text-xs font-black uppercase tracking-widest">Description</th>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest">SKU</th>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest">Target Qty</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm">
                <tr>
                    <td class="py-4 px-4 text-center font-bold">1</td>
                    <td class="py-4 px-4">
                        <div class="font-bold text-slate-800"><?= htmlspecialchars($rfq['nama_barang']) ?></div>
                        <?php if($rfq['catatan']): ?>
                        <div class="text-xs text-slate-500 mt-1 italic">Catatan: <?= htmlspecialchars($rfq['catatan']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 px-4 text-center text-slate-500 font-mono"><?= htmlspecialchars($rfq['sku']) ?></td>
                    <td class="py-4 px-4 text-center font-black text-lg"><?= htmlspecialchars($rfq['jumlah_kebutuhan']) ?> <span class="text-xs text-slate-400 font-normal"><?= htmlspecialchars($rfq['unit']) ?></span></td>
                </tr>
                <tr class="h-32"><td colspan="4"></td></tr>
            </tbody>
        </table>

        <!-- Terms and Signatures -->
        <div class="grid grid-cols-2 gap-8 mt-12">
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Instruksi Bidding:</h3>
                <ul class="text-xs text-slate-600 list-decimal pl-4 space-y-2">
                    <li>Submit SPH melalui sistem e-Procurement Veridex di tab <b>Bidding Area</b>.</li>
                    <li>Harga penawaran harus sudah termasuk pajak (jika ada) dan biaya pengiriman sampai ke gudang.</li>
                    <li>Lampirkan dokumen spesifikasi teknis (TBE) bersama dengan dokumen SPH.</li>
                    <li>Penawaran yang melewati batas waktu <b><?= date('d M Y H:i', strtotime($rfq['deadline'])) ?></b> tidak akan diproses.</li>
                </ul>
            </div>
            
            <div class="flex justify-end gap-12">
                <div class="text-center w-40">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Diterbitkan Oleh</p>
                    <div class="border-b border-slate-400 pb-1 font-bold text-sm"><?= htmlspecialchars($rfq['creator_name'] ?? 'Procurement') ?></div>
                    <p class="text-[10px] mt-1 text-slate-500">Veridex Procurement</p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
