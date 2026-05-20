<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$po_id = intval($_GET['id'] ?? 0);

$query_po = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, i.unit, v.nama_perusahaan, v.alamat, v.email, v.pic_kontak,
           u.username as creator_name
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN users u ON p.procurement_id = u.id
    WHERE p.id = $po_id
") or handle_db_error($conn);

$po = mysqli_fetch_assoc($query_po);

if (!$po) {
    die("PO tidak ditemukan.");
}

// Vendor hanya boleh melihat PO-nya sendiri
if ($_SESSION['role'] === 'Vendor' && $po['vendor_id'] != $_SESSION['vendor_id']) {
    die("Akses ditolak.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print PO - <?= htmlspecialchars($po['po_number']) ?></title>
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
                <h2 class="text-3xl font-black text-slate-800 uppercase mb-2">Purchase Order</h2>
                <div class="inline-block bg-slate-100 p-3 rounded-lg text-left border border-slate-200">
                    <table class="text-sm">
                        <tr><td class="font-bold text-slate-500 pr-4">PO Number</td><td class="font-black"><?= htmlspecialchars($po['po_number']) ?></td></tr>
                        <tr><td class="font-bold text-slate-500 pr-4">Date</td><td class="font-bold"><?= date('d F Y', strtotime($po['created_at'])) ?></td></tr>
                        <tr><td class="font-bold text-slate-500 pr-4">Status</td><td class="font-bold text-red-600 uppercase"><?= $po['status'] ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Vendor & Ship To -->
        <div class="grid grid-cols-2 gap-12 mb-8">
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-200 pb-1">Vendor To:</h3>
                <h4 class="text-lg font-black text-slate-800 uppercase"><?= htmlspecialchars($po['nama_perusahaan']) ?></h4>
                <p class="text-sm mt-1 text-slate-600"><?= nl2br(htmlspecialchars($po['alamat'] ?? '')) ?></p>
                <p class="text-sm mt-2 text-slate-600 font-medium">T: <?= htmlspecialchars($po['pic_kontak'] ?? '-') ?></p>
                <p class="text-sm text-slate-600 font-medium">E: <?= htmlspecialchars($po['email'] ?? '-') ?></p>
            </div>
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-200 pb-1">Ship To:</h3>
                <h4 class="text-lg font-black text-slate-800 uppercase">Gudang Utama Veridex</h4>
                <p class="text-sm mt-1 text-slate-600">Jl. Industri Raya Blok B2<br>Kawasan Industri Cikarang, Jawa Barat 17530</p>
                <p class="text-sm mt-2 text-slate-600 font-medium">PIC: Warehouse Manager</p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full mb-8 border border-slate-300">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-black uppercase tracking-widest">No</th>
                    <th class="py-3 px-4 text-left text-xs font-black uppercase tracking-widest">Description</th>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest">SKU</th>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest">Qty</th>
                    <th class="py-3 px-4 text-right text-xs font-black uppercase tracking-widest">Unit Price</th>
                    <th class="py-3 px-4 text-right text-xs font-black uppercase tracking-widest">Total Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm">
                <tr>
                    <td class="py-4 px-4 text-center font-bold">1</td>
                    <td class="py-4 px-4 font-bold text-slate-800"><?= htmlspecialchars($po['nama_barang']) ?></td>
                    <td class="py-4 px-4 text-center text-slate-500 font-mono"><?= htmlspecialchars($po['sku']) ?></td>
                    <td class="py-4 px-4 text-center font-bold"><?= $po['jumlah_pesan'] ?> <span class="text-xs text-slate-400"><?= htmlspecialchars($po['unit']) ?></span></td>
                    <?php $unit_price = $po['jumlah_pesan'] > 0 ? $po['total_harga'] / $po['jumlah_pesan'] : 0; ?>
                    <td class="py-4 px-4 text-right font-bold">Rp <?= number_format($unit_price, 0, ',', '.') ?></td>
                    <td class="py-4 px-4 text-right font-black">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></td>
                </tr>
                <!-- Spacer for layout -->
                <tr class="h-32"><td colspan="6"></td></tr>
            </tbody>
            <tfoot class="bg-slate-50 border-t-2 border-slate-900">
                <tr>
                    <td colspan="5" class="py-4 px-4 text-right font-black uppercase tracking-widest text-slate-600">Grand Total</td>
                    <td class="py-4 px-4 text-right font-black text-lg text-red-600">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Terms and Signatures -->
        <div class="grid grid-cols-2 gap-8 mt-12">
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Terms & Conditions:</h3>
                <ul class="text-xs text-slate-600 list-disc pl-4 space-y-1">
                    <li>Semua barang harus dikirimkan sesuai dengan spesifikasi yang disepakati.</li>
                    <li>Invoice harus mencantumkan nomor PO ini.</li>
                    <li>Pembayaran dilakukan 30 hari setelah invoice dan barang diterima dengan baik.</li>
                </ul>
            </div>
            
            <div class="flex justify-end gap-12">
                <div class="text-center w-32">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Procurement</p>
                    <div class="border-b border-slate-400 pb-1 font-bold text-sm"><?= htmlspecialchars($po['creator_name'] ?? 'Procurement Staff') ?></div>
                </div>
                <div class="text-center w-32">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Manager Approval</p>
                    <?php if($po['approved_by']): ?>
                    <div class="border-b border-slate-400 pb-1 font-bold text-sm">Approved</div>
                    <p class="text-[9px] mt-1"><?= date('d/m/Y', strtotime($po['approved_at'])) ?></p>
                    <?php else: ?>
                    <div class="border-b border-slate-400 pb-1 font-bold text-sm text-white select-none">.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
