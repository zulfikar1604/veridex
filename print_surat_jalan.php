<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$po_id = intval($_GET['id'] ?? 0);

$query = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, i.unit,
           v.nama_perusahaan, v.alamat, v.email, v.pic_kontak, v.pic_nama
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.id = $po_id AND p.no_resi IS NOT NULL
") or die("ERROR DATABASE: " . mysqli_error($conn));

$sj = mysqli_fetch_assoc($query);

if (!$sj) {
    die("Surat Jalan tidak ditemukan atau belum di-generate.");
}

// Vendor hanya bisa cetak miliknya sendiri
if ($_SESSION['role'] === 'Vendor' && $_SESSION['vendor_id'] != $sj['vendor_id']) {
    die("Akses ditolak.");
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - <?= htmlspecialchars($sj['no_resi']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <i class="fa-solid fa-print mr-2"></i> Print PDF
        </button>
        <button onclick="window.close()" class="bg-slate-200 text-slate-700 px-6 py-2 rounded-lg font-bold shadow hover:bg-slate-300">Tutup</button>
    </div>

    <div class="print-area">
        <!-- Header (Vendor sebagai Pengirim) -->
        <div class="flex justify-between items-start border-b-4 border-slate-900 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase"><?= htmlspecialchars($sj['nama_perusahaan']) ?></h1>
                <p class="text-sm text-slate-600 font-medium mt-2 max-w-sm"><?= nl2br(htmlspecialchars($sj['alamat'] ?? '-')) ?></p>
                <div class="mt-2 text-sm text-slate-600">
                    <p>PIC: <?= htmlspecialchars($sj['pic_nama'] ?? '-') ?> | Telp: <?= htmlspecialchars($sj['pic_kontak'] ?? '-') ?></p>
                    <p>Email: <?= htmlspecialchars($sj['email'] ?? '-') ?></p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-4xl font-black text-red-600 uppercase mb-2 tracking-widest">Surat Jalan</h2>
                <div class="inline-block bg-slate-50 p-3 rounded-lg text-left border border-slate-200">
                    <table class="text-sm">
                        <tr><td class="font-bold text-slate-500 pr-4">No. Dokumen</td><td class="font-black text-slate-800"><?= htmlspecialchars($sj['no_resi']) ?></td></tr>
                        <tr><td class="font-bold text-slate-500 pr-4">Tanggal</td><td class="font-bold"><?= date('d F Y', strtotime($sj['shipped_at'])) ?></td></tr>
                        <tr><td class="font-bold text-slate-500 pr-4">No. Referensi PO</td><td class="font-bold text-blue-600"><?= htmlspecialchars($sj['po_number']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Tujuan & Ekspedisi -->
        <div class="grid grid-cols-2 gap-12 mb-8">
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-200 pb-1">Tujuan Pengiriman (Ship To):</h3>
                <h4 class="text-lg font-black text-slate-800 uppercase">PT Veridex</h4>
                <p class="text-sm mt-1 text-slate-600 font-bold">Gudang Utama Veridex</p>
                <p class="text-sm text-slate-600">Jl. Industri Raya Blok B2<br>Kawasan Industri Cikarang, Jawa Barat 17530</p>
                <p class="text-sm mt-2 text-slate-600 font-medium">PIC: Warehouse Manager</p>
            </div>
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-200 pb-1">Detail Ekspedisi / Kendaraan:</h3>
                <table class="text-sm w-full">
                    <tr><td class="py-1 font-bold text-slate-500 w-1/3">Nama Pengemudi</td><td class="py-1 font-black text-slate-800">: <?= htmlspecialchars($sj['sj_pengemudi'] ?? '-') ?></td></tr>
                    <tr><td class="py-1 font-bold text-slate-500 w-1/3">No. Polisi (Nopol)</td><td class="py-1 font-black text-slate-800 uppercase">: <?= htmlspecialchars($sj['sj_nopol'] ?? '-') ?></td></tr>
                    <tr><td class="py-1 font-bold text-slate-500 w-1/3">Kontak Pengemudi</td><td class="py-1 font-bold text-slate-800">: <?= htmlspecialchars($sj['sj_kontak'] ?? '-') ?></td></tr>
                </table>
            </div>
        </div>

        <p class="text-sm text-slate-600 italic mb-4">Bersama surat ini, kami kirimkan barang dengan rincian sebagai berikut:</p>

        <!-- Items Table -->
        <table class="w-full mb-8 border border-slate-300">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest w-16">No</th>
                    <th class="py-3 px-4 text-left text-xs font-black uppercase tracking-widest">Deskripsi Barang</th>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest">SKU</th>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest">Qty Dikirim</th>
                    <th class="py-3 px-4 text-center text-xs font-black uppercase tracking-widest border-l border-slate-700">Qty Diterima<br><span class="text-[9px] font-normal italic">(Diisi Gudang)</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-sm">
                <tr>
                    <td class="py-4 px-4 text-center font-bold">1</td>
                    <td class="py-4 px-4 font-bold text-slate-800"><?= htmlspecialchars($sj['nama_barang']) ?></td>
                    <td class="py-4 px-4 text-center text-slate-500 font-mono"><?= htmlspecialchars($sj['sku']) ?></td>
                    <td class="py-4 px-4 text-center font-black text-lg"><?= $sj['jumlah_pesan'] ?> <span class="text-xs text-slate-500 font-normal"><?= htmlspecialchars($sj['unit']) ?></span></td>
                    <td class="py-4 px-4 text-center border-l border-slate-200"></td>
                </tr>
                <!-- Spacer for layout -->
                <tr class="h-24"><td colspan="3"></td><td class="border-l border-slate-200"></td><td class="border-l border-slate-200"></td></tr>
            </tbody>
        </table>

        <!-- Catatan Tambahan -->
        <?php if(!empty($sj['sj_catatan'])): ?>
        <div class="mb-12 border border-slate-300 p-4 rounded-xl bg-slate-50 text-sm">
            <span class="font-bold text-slate-700 block mb-1">Catatan Pengiriman:</span>
            <span class="text-slate-600"><?= nl2br(htmlspecialchars($sj['sj_catatan'])) ?></span>
        </div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-8 mt-16 text-center">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase mb-16">Hormat Kami,<br>Pengirim (Vendor)</p>
                <div class="border-b border-slate-400 pb-1 font-bold text-sm mx-8"></div>
                <p class="text-[10px] mt-1 text-slate-500">Tanda Tangan & Cap</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase mb-16">Dibawa Oleh,<br>Pengemudi/Kurir</p>
                <div class="border-b border-slate-400 pb-1 font-bold text-sm mx-8"><?= htmlspecialchars($sj['sj_pengemudi']) ?></div>
                <p class="text-[10px] mt-1 text-slate-500">Tanda Tangan</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase mb-16">Diterima Oleh,<br>Gudang Veridex</p>
                <div class="border-b border-slate-400 pb-1 font-bold text-sm mx-8"></div>
                <p class="text-[10px] mt-1 text-slate-500">Tanda Tangan & Nama Terang</p>
            </div>
        </div>

    </div>

</body>
</html>
