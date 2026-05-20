<?php
session_start();
require 'database.php';

// Proteksi Internal
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID Vendor tidak valid.");
}

$query = mysqli_query($conn, "SELECT * FROM vendors WHERE id = $id");
if (mysqli_num_rows($query) == 0) {
    die("Data vendor tidak ditemukan.");
}

$vendor = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Vendor - <?= htmlspecialchars($vendor['nama_perusahaan']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        @media print {
            body { background-color: #ffffff; }
            .no-print { display: none !important; }
            .print-border { border-color: #000 !important; }
        }
    </style>
</head>
<body class="p-8">

    <div class="max-w-4xl mx-auto bg-white p-12 shadow-2xl rounded-2xl print:shadow-none print:rounded-none">
        
        <!-- Header / Kop -->
        <div class="flex justify-between items-center border-b-4 border-slate-900 pb-6 mb-8 print-border">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tighter">Profil Mitra Pengadaan</h1>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mt-1">Sistem Registrasi Veridex</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">ID Vendor</p>
                <p class="text-2xl font-black text-slate-900">VND-<?= str_pad($vendor['id'], 4, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <!-- Info Utama -->
        <div class="mb-10">
            <h2 class="text-4xl font-black text-slate-900 uppercase mb-2"><?= htmlspecialchars($vendor['nama_perusahaan']) ?></h2>
            <div class="inline-block bg-slate-900 text-white px-4 py-1 font-bold text-sm uppercase tracking-widest">
                Spesialisasi: <?= htmlspecialchars($vendor['spesialisasi']) ?>
            </div>
        </div>

        <!-- Detail Grid -->
        <div class="grid grid-cols-2 gap-y-8 gap-x-12 mb-10">
            
            <div class="col-span-2">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Alamat Lengkap Perusahaan</p>
                <p class="font-medium text-slate-800 text-base leading-relaxed border-b border-slate-200 pb-2"><?= nl2br(htmlspecialchars($vendor['alamat'])) ?></p>
            </div>

            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nama Penanggung Jawab (PIC)</p>
                <p class="font-bold text-slate-800 text-lg border-b border-slate-200 pb-2"><?= htmlspecialchars($vendor['pic_nama']) ?></p>
            </div>

            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nomor Kontak (WhatsApp / Telp)</p>
                <p class="font-bold text-slate-800 text-lg border-b border-slate-200 pb-2"><?= htmlspecialchars($vendor['pic_kontak']) ?></p>
            </div>

            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Email Resmi</p>
                <p class="font-bold text-slate-800 text-lg border-b border-slate-200 pb-2"><?= htmlspecialchars($vendor['email']) ?></p>
            </div>

            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status Kemitraan Saat Ini</p>
                <p class="font-black text-slate-800 text-lg border-b border-slate-200 pb-2 uppercase"><?= htmlspecialchars($vendor['status']) ?></p>
            </div>

        </div>

        <!-- Legalitas -->
        <div class="bg-slate-50 border border-slate-200 p-8 rounded-xl print-border mb-12">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-6 border-b border-slate-200 pb-2">Informasi Legalitas & Perizinan</h3>
            
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Nomor Induk Berusaha (NIB)</p>
                    <p class="font-black text-xl text-slate-900 tracking-wider"><?= htmlspecialchars($vendor['nib'] ?? 'Tidak ada data') ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">NPWP Perusahaan</p>
                    <p class="font-black text-xl text-slate-900 tracking-wider"><?= htmlspecialchars($vendor['npwp'] ?? 'Tidak ada data') ?></p>
                </div>
            </div>
        </div>

        <!-- Tanda Tangan -->
        <div class="grid grid-cols-2 gap-8 text-center pt-8">
            <div>
                <p class="text-sm font-bold text-slate-500 mb-20">Pihak Vendor</p>
                <p class="font-black text-slate-900 underline underline-offset-4 decoration-2"><?= htmlspecialchars($vendor['pic_nama']) ?></p>
                <p class="text-xs font-bold text-slate-500 mt-1 uppercase"><?= htmlspecialchars($vendor['nama_perusahaan']) ?></p>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500 mb-20">Diverifikasi Oleh</p>
                <p class="font-black text-slate-900 underline underline-offset-4 decoration-2">Tim Procurement</p>
                <p class="text-xs font-bold text-slate-500 mt-1 uppercase">Veridex SCM</p>
            </div>
        </div>

        <div class="mt-16 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest border-t border-slate-200 pt-4 print-border">
            Dokumen ini di-generate secara otomatis oleh sistem Veridex pada <?= date('d M Y H:i:s') ?>
        </div>
    </div>

    <!-- Tombol Cetak (Sembunyi saat diprint) -->
    <div class="fixed bottom-8 right-8 no-print flex gap-4">
        <button onclick="window.close()" class="bg-slate-800 text-white px-6 py-3 rounded-xl font-black uppercase tracking-widest shadow-xl hover:bg-slate-700 hover:-translate-y-1 transition-all">Tutup</button>
        <button onclick="window.print()" class="bg-red-600 text-white px-6 py-3 rounded-xl font-black uppercase tracking-widest shadow-xl hover:bg-red-700 hover:-translate-y-1 transition-all">
            <i class="fa-solid fa-print mr-2"></i> Cetak Profil
        </button>
    </div>

</body>
</html>
