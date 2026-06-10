<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$inv_id = intval($_GET['id'] ?? 0);

$query = mysqli_query($conn, "
    SELECT inv.*, p.po_number, p.total_harga as po_total, p.jumlah_pesan, p.payment_term,
           i.nama_barang, i.sku, i.unit,
           v.nama_perusahaan, v.alamat, v.pic_kontak, v.email, v.pic_nama
    FROM invoices inv
    JOIN purchase_orders p ON inv.po_id = p.id
    JOIN inventory i ON inv.inventory_id = i.id
    JOIN vendors v ON inv.vendor_id = v.id
    WHERE inv.id = $inv_id
") or die("ERROR DATABASE: " . mysqli_error($conn));

$invoice = mysqli_fetch_assoc($query);

if (!$invoice) {
    die("Invoice tidak ditemukan.");
}

// Vendor hanya bisa cetak invoice-nya sendiri
if ($_SESSION['role'] === 'Vendor' && $_SESSION['vendor_id'] != $invoice['vendor_id']) {
    die("Akses ditolak.");
}

$unit_price = $invoice['jumlah_pesan'] > 0 ? $invoice['po_total'] / $invoice['jumlah_pesan'] : 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .no-print { display: none; }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-8 text-slate-800">

    <div class="bg-white p-12 w-[21cm] min-h-[29.7cm] border border-gray-300 shadow-xl relative mt-12">
        <button onclick="window.print()" class="no-print absolute top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded font-bold shadow hover:bg-blue-700">Cetak PDF</button>
        <button onclick="window.close()" class="no-print absolute top-4 right-32 bg-slate-200 text-slate-700 px-4 py-2 rounded font-bold shadow hover:bg-slate-300">Tutup</button>
        
        <div class="flex justify-between items-start border-b-4 border-slate-800 pb-6 mb-8 mt-10">
            <div>
                <h1 class="text-4xl font-black text-slate-800 uppercase tracking-widest"><?= htmlspecialchars($invoice['nama_perusahaan']) ?></h1>
                <p class="text-sm text-slate-600 mt-2 max-w-sm"><?= htmlspecialchars($invoice['alamat'] ?? '-') ?></p>
                <p class="text-sm text-slate-600">PIC: <?= htmlspecialchars($invoice['pic_nama'] ?? '-') ?> | Tel: <?= htmlspecialchars($invoice['pic_kontak'] ?? '-') ?> | Email: <?= htmlspecialchars($invoice['email'] ?? '-') ?></p>
            </div>
            <div class="text-right">
                <h2 class="text-4xl font-black text-blue-600 uppercase">INVOICE</h2>
                <p class="text-lg font-bold text-slate-700 mt-1"><?= htmlspecialchars($invoice['invoice_number']) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Bill To:</p>
                <h3 class="text-lg font-black text-slate-800">PT Veridex</h3>
                <p class="text-sm text-slate-600">Jl. Jend. Sudirman No. 123, Jakarta 12190</p>
                <p class="text-sm text-slate-600">Departemen Finance</p>
            </div>
            <div class="text-right text-sm">
                <p><span class="font-bold text-slate-500 mr-2">Tanggal Terbit:</span> <?= date('d F Y', strtotime($invoice['created_at'])) ?></p>
                <p><span class="font-bold text-slate-500 mr-2">Jatuh Tempo:</span> <span class="text-red-600 font-bold"><?= date('d F Y', strtotime($invoice['tanggal_jatuh_tempo'])) ?></span></p>
                <p><span class="font-bold text-slate-500 mr-2">Referensi PO:</span> <?= htmlspecialchars($invoice['po_number']) ?></p>
            </div>
        </div>

        <!-- Tabel Barang -->
        <div class="overflow-x-auto w-full">
<table class="w-full border-collapse border border-slate-400 mb-8 text-sm">
            <thead>
                <tr class="bg-slate-800 text-white">
                    <th class="border border-slate-400 p-3 text-left">No</th>
                    <th class="border border-slate-400 p-3 text-left">Deskripsi / Item</th>
                    <th class="border border-slate-400 p-3 text-center">Qty Diterima</th>
                    <th class="border border-slate-400 p-3 text-right">Harga Satuan (Rp)</th>
                    <th class="border border-slate-400 p-3 text-right">Total (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-slate-400 p-3 text-center">1</td>
                    <td class="border border-slate-400 p-3">
                        <strong><?= htmlspecialchars($invoice['nama_barang']) ?></strong><br>
                        <span class="text-xs text-slate-500">SKU: <?= htmlspecialchars($invoice['sku']) ?></span>
                    </td>
                    <td class="border border-slate-400 p-3 text-center font-bold">
                        <?= $invoice['jumlah_barang'] ?> <?= htmlspecialchars($invoice['unit']) ?>
                    </td>
                    <td class="border border-slate-400 p-3 text-right">
                        <?= number_format($unit_price, 0, ',', '.') ?>
                    </td>
                    <td class="border border-slate-400 p-3 text-right font-bold text-slate-800">
                        <?= number_format($invoice['total_tagihan'], 0, ',', '.') ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="border border-slate-400 p-3 text-right font-bold uppercase text-slate-600">Subtotal</td>
                    <td class="border border-slate-400 p-3 text-right font-bold">
                        Rp <?= number_format($invoice['total_tagihan'], 0, ',', '.') ?>
                    </td>
                </tr>
                <tr class="bg-slate-100">
                    <td colspan="4" class="border border-slate-400 p-3 text-right font-black uppercase text-slate-800 text-lg">Total Tagihan</td>
                    <td class="border border-slate-400 p-3 text-right font-black text-xl text-blue-600">
                        Rp <?= number_format($invoice['total_tagihan'], 0, ',', '.') ?>
                    </td>
                </tr>
            </tfoot>
        </table>
</div>

        <!-- Info Pembayaran -->
        <div class="mb-12 border border-slate-300 p-5 rounded-lg bg-slate-50">
            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Instruksi Pembayaran</h3>
            <p class="text-sm font-bold text-slate-800">Bank Transfer</p>
            <p class="text-sm text-slate-600 mt-1">Sesuai dengan kontrak yang terdaftar di sistem Veridex.</p>
            <p class="text-sm text-slate-600 mt-1 italic">Mohon cantumkan Nomor Invoice saat melakukan pembayaran.</p>
        </div>

        <div class="flex justify-end text-sm text-center">
            <div>
                <p>Hormat Kami,</p>
                <div class="h-24 flex items-center justify-center">
                    <div class="border-2 border-blue-200 text-blue-400 text-[10px] uppercase font-black px-2 py-1 rotate-12 bg-white inline-block">Sistem Generated</div>
                </div>
                <p class="font-black text-slate-800 underline uppercase"><?= htmlspecialchars($invoice['nama_perusahaan']) ?></p>
            </div>
        </div>

    </div>

</body>
</html>
