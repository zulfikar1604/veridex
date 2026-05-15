<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$rfq_id = intval($_GET['rfq'] ?? 0);
$vendor_id = intval($_GET['vendor'] ?? 0);

if ($rfq_id <= 0 || $vendor_id <= 0) {
    die("Data SPH tidak valid.");
}

// Cek hak akses
if ($_SESSION['role'] === 'Vendor' && $_SESSION['vendor_id'] != $vendor_id) {
    die("Anda tidak memiliki akses ke dokumen SPH ini.");
}

$query = mysqli_query($conn, "
    SELECT s.*, r.rfq_number, r.jumlah_kebutuhan, r.unit, 
           i.nama_barang, i.sku,
           v.nama_perusahaan, v.alamat, v.email, v.pic_nama, v.pic_kontak
    FROM sph_submissions s
    JOIN rfq_requests r ON s.rfq_id = r.id
    JOIN inventory i ON s.inventory_id = i.id
    JOIN vendors v ON s.vendor_id = v.id
    WHERE s.rfq_id = $rfq_id AND s.vendor_id = $vendor_id
");

$sph = mysqli_fetch_assoc($query);

if (!$sph) {
    die("Dokumen Surat Penawaran Harga belum di-submit atau tidak ditemukan.");
}

$total_harga = $sph['harga_penawaran'] * $sph['jumlah_kebutuhan'];

date_default_timezone_set('Asia/Jakarta');
$tanggal_cetak = date('d F Y', strtotime($sph['created_at']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Penawaran Harga - <?= htmlspecialchars($sph['nomor_sph']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-8">

    <div class="bg-white p-12 w-[21cm] min-h-[29.7cm] border border-gray-300 shadow-xl relative">
        <button onclick="window.print()" class="no-print absolute top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded font-bold shadow hover:bg-blue-700">Cetak PDF</button>
        
        <!-- Header Vendor -->
        <div class="border-b-4 border-slate-800 pb-6 mb-8 text-center">
            <h1 class="text-3xl font-black text-slate-800 uppercase tracking-widest"><?= htmlspecialchars($sph['nama_perusahaan']) ?></h1>
            <p class="text-sm text-slate-600 mt-2"><?= htmlspecialchars($sph['alamat']) ?></p>
            <p class="text-sm text-slate-600">Email: <?= htmlspecialchars($sph['email']) ?> | Telepon: <?= htmlspecialchars($sph['pic_kontak']) ?></p>
        </div>

        <!-- Judul Surat -->
        <div class="text-center mb-10">
            <h2 class="text-2xl font-black text-slate-800 uppercase underline underline-offset-4">SURAT PENAWARAN HARGA (SPH)</h2>
            <p class="text-md font-bold text-slate-600 mt-2">Nomor: <?= htmlspecialchars($sph['nomor_sph']) ?></p>
        </div>

        <div class="flex justify-between items-start mb-8 text-sm">
            <div>
                <p>Kepada Yth,</p>
                <p class="font-black text-slate-800 mt-1">Tim Procurement - PT Veridex</p>
                <p>Kawasan Terpadu Industri Raya No. 45</p>
            </div>
            <div class="text-right">
                <p>Tanggal: <?= $tanggal_cetak ?></p>
                <p>Referensi RFQ: <span class="font-bold"><?= htmlspecialchars($sph['rfq_number']) ?></span></p>
            </div>
        </div>

        <div class="mb-6 text-sm leading-relaxed">
            <p>Dengan hormat,</p>
            <p class="mt-2">Memenuhi permintaan penawaran harga (RFQ) dari PT Veridex, bersama surat ini kami bermaksud mengajukan penawaran harga untuk pengadaan barang dengan rincian sebagai berikut:</p>
        </div>

        <!-- Tabel Barang -->
        <table class="w-full border-collapse border border-slate-400 mb-8 text-sm">
            <thead>
                <tr class="bg-slate-100">
                    <th class="border border-slate-400 p-3 text-left">No</th>
                    <th class="border border-slate-400 p-3 text-left">Nama Barang / SKU</th>
                    <th class="border border-slate-400 p-3 text-center">Kuantitas</th>
                    <th class="border border-slate-400 p-3 text-right">Harga Satuan (Rp)</th>
                    <th class="border border-slate-400 p-3 text-right">Total Harga (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-slate-400 p-3 text-center">1</td>
                    <td class="border border-slate-400 p-3">
                        <strong><?= htmlspecialchars($sph['nama_barang']) ?></strong><br>
                        <span class="text-xs text-slate-500"><?= htmlspecialchars($sph['sku']) ?></span>
                    </td>
                    <td class="border border-slate-400 p-3 text-center">
                        <?= $sph['jumlah_kebutuhan'] ?> <?= htmlspecialchars($sph['unit']) ?>
                    </td>
                    <td class="border border-slate-400 p-3 text-right">
                        <?= number_format($sph['harga_penawaran'], 0, ',', '.') ?>
                    </td>
                    <td class="border border-slate-400 p-3 text-right font-bold text-slate-800">
                        <?= number_format($total_harga, 0, ',', '.') ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="bg-slate-50">
                    <td colspan="4" class="border border-slate-400 p-3 text-right font-black uppercase">Grand Total Penawaran</td>
                    <td class="border border-slate-400 p-3 text-right font-black text-lg text-slate-800">
                        Rp <?= number_format($total_harga, 0, ',', '.') ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="mb-12 text-sm leading-relaxed">
            <p><strong>Syarat & Ketentuan:</strong></p>
            <ul class="list-disc list-inside mt-2 text-slate-600">
                <li>Harga di atas belum termasuk pajak yang berlaku (jika ada).</li>
                <li>Penawaran ini tunduk pada syarat pembayaran standar yang telah disepakati bersama.</li>
                <li>Dokumen ini dicetak otomatis oleh sistem dan sah sebagai dokumen penawaran resmi.</li>
            </ul>
        </div>

        <div class="flex justify-end text-sm text-center">
            <div>
                <p>Hormat Kami,</p>
                <div class="h-20"></div> <!-- Ruang tanda tangan -->
                <p class="font-black text-slate-800 underline"><?= htmlspecialchars($sph['pic_nama']) ?></p>
                <p class="text-slate-500">Perwakilan Vendor</p>
            </div>
        </div>

    </div>

</body>
</html>
