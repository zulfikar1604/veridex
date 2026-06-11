<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    die("Unauthorized");
}

$po_id = intval($_GET['po_id'] ?? 0);

$query = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan, v.alamat, v.email, v.pic_kontak
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.id = $po_id
");

$po = mysqli_fetch_assoc($query);

if (!$po || !$po['invoice_number']) {
    die("Invoice belum tersedia.");
}

$tanggal_invoice = date('d F Y', strtotime($po['invoice_submitted_at']));

// Menghitung tagihan (karena invoice ini auto-generated untuk simulasi)
// Unit price didapat dari total_harga PO dibagi jumlah_pesan, dikali jumlah_diterima gudang
$unit_price = $po['jumlah_pesan'] > 0 ? $po['total_harga'] / $po['jumlah_pesan'] : 0;
$total_tagihan = $unit_price * $po['jumlah_diterima'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>E-Invoice - <?= htmlspecialchars($po['invoice_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: white; margin: 0; padding: 20px; font-family: sans-serif; color: #334155; }
        .invoice-box { max-width: 800px; margin: auto; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-weight: bold; text-transform: uppercase; font-size: 12px; color: #64748b; }
        .text-right { text-align: right; }
        .total-row td { font-weight: 900; font-size: 18px; border-top: 2px solid #cbd5e1; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="flex justify-between items-start mb-8 border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-3xl font-black text-indigo-600 uppercase tracking-widest">INVOICE</h1>
                <p class="text-sm text-slate-500 font-bold mt-1"># <?= htmlspecialchars($po['invoice_number']) ?></p>
            </div>
            <div class="text-right">
                <h2 class="font-black text-xl text-slate-800 uppercase"><?= htmlspecialchars($po['nama_perusahaan']) ?></h2>
                <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($po['alamat']) ?></p>
                <p class="text-sm text-slate-500"><?= htmlspecialchars($po['email']) ?> | <?= htmlspecialchars($po['pic_kontak']) ?></p>
            </div>
        </div>

        <div class="flex justify-between mb-8">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ditagihkan Kepada:</p>
                <h3 class="font-bold text-slate-800">PT Veridex</h3>
                <p class="text-sm text-slate-600">Departemen Finance / Procurement</p>
                <p class="text-sm text-slate-600">Kawasan Terpadu Industri Raya No. 45</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal Invoice:</p>
                <p class="font-bold text-slate-800 text-sm mb-4"><?= $tanggal_invoice ?></p>
                
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Referensi PO:</p>
                <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($po['po_number']) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Deskripsi Barang</th>
                    <th class="text-right">Qty Diterima</th>
                    <th class="text-right">Harga Satuan (Rp)</th>
                    <th class="text-right">Subtotal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="font-bold text-slate-800"><?= htmlspecialchars($po['nama_barang']) ?></div>
                        <div class="text-xs text-slate-500">SKU: <?= htmlspecialchars($po['sku']) ?></div>
                    </td>
                    <td class="text-right font-bold"><?= $po['jumlah_diterima'] ?> Unit</td>
                    <td class="text-right"><?= number_format($unit_price, 0, ',', '.') ?></td>
                    <td class="text-right font-bold text-slate-800"><?= number_format($total_tagihan, 0, ',', '.') ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" class="text-right uppercase text-slate-500">Grand Total Tagihan</td>
                    <td class="text-right text-indigo-600">Rp <?= number_format($total_tagihan, 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-12 text-sm text-slate-500 text-center border-t border-slate-100 pt-8">
            <p class="font-bold italic">Dokumen ini adalah e-invoice sah yang digenerate otomatis oleh sistem.</p>
            <p class="text-xs mt-2">Mohon segera lakukan proses 3-Way Matching dan Pembayaran sesuai termin yang disepakati.</p>
        </div>
    </div>
</body>
</html>
