<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Vendor | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-pattern {
            background-color: #f8fafc;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-pattern font-sans min-h-screen flex flex-col">

    <header class="bg-slate-900 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/30">
                    <i class="fa-solid fa-cube text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tighter leading-none">VERIDEX</h1>
                    <p class="text-[10px] text-red-500 font-bold tracking-widest uppercase">Vendor Portal</p>
                </div>
            </div>
            <a href="login.php" class="text-white text-sm font-bold border border-slate-700 hover:border-red-500 hover:bg-red-600 px-5 py-2.5 rounded-xl transition-all duration-300 flex items-center">
                <i class="fa-solid fa-arrow-left mr-2"></i> KEMBALI KE LOGIN
            </a>
        </div>
    </header>
    
    <main class="flex-1 w-full max-w-7xl mx-auto px-6 py-12 flex items-center">
        <div class="grid lg:grid-cols-2 gap-16 items-center w-full">
            
            <div class="space-y-8 animate-[fade-in-right_1s_ease-out]">
                <div>
                    <span class="inline-block py-1 px-3 rounded-full bg-red-100 text-red-600 text-xs font-bold tracking-widest mb-4">GABUNG BERSAMA KAMI</span>
                    <h2 class="text-5xl font-black text-slate-900 mb-6 leading-tight">
                        Jadilah Bagian Dari <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-red-400">Ekosistem Pengadaan</span>
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-lg mb-8">
                        Veridex adalah platform ekosistem digital untuk menjamin transparansi pengadaan barang operasional. Kami memprioritaskan kualitas, integritas, dan kecepatan transaksi.
                    </p>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                    <h3 class="font-bold text-slate-800 mb-4 uppercase tracking-widest text-xs border-b pb-3 flex items-center">
                        <i class="fa-solid fa-fire text-red-500 mr-2"></i> Kebutuhan Rutin Kami:
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-slate-50 text-slate-700 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-paperclip mr-2 text-slate-400"></i>Alat Tulis Kantor</span>
                        <span class="bg-slate-50 text-slate-700 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-computer mr-2 text-slate-400"></i>Maintenance IT</span>
                        <span class="bg-slate-50 text-slate-700 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-helmet-safety mr-2 text-slate-400"></i>Konstruksi Ringan</span>
                        <span class="bg-slate-50 text-slate-700 px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-boxes-stacked mr-2 text-slate-400"></i>Bahan Baku Kemasan</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-white">
                
                <h3 class="text-2xl font-black mb-2 text-slate-800">Form Registrasi Vendor</h3>
                <p class="text-slate-500 text-sm mb-8">Isi data perusahaan Anda. Tim verifikasi kami akan mengecek dalam 1x24 jam.</p>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-xl text-sm font-bold">
                        <i class="fa-solid fa-circle-check mr-2"></i> <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-xl text-sm font-bold">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action="process_register.php" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ fileName: '' }">
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nama Perusahaan (PT/CV)</label>
                        <input type="text" name="nama_perusahaan" required 
                               class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl outline-none focus:border-red-500 focus:bg-white transition-all font-medium text-slate-700" 
                               placeholder="Contoh: PT. Solusi Maju Bersama">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Spesialisasi Pengadaan</label>
                        <input type="text" name="spesialisasi" required 
                               class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl outline-none focus:border-red-500 focus:bg-white transition-all font-medium text-slate-700" 
                               placeholder="Contoh: Alat Tulis, Perangkat IT, Jasa Cleaning">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Dokumen Legalitas (NIB/NPWP)</label>
                        <div class="relative relative border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center hover:border-red-500 hover:bg-red-50 transition-colors group">
                            <input type="file" name="dokumen_legal" required accept=".pdf,.jpg,.jpeg,.png"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                   @change="fileName = $event.target.files[0].name">
                            
                            <div class="space-y-2" x-show="!fileName">
                                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto group-hover:bg-red-100 transition-colors">
                                    <i class="fa-solid fa-cloud-arrow-up text-slate-400 group-hover:text-red-500 text-xl"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-600">Klik atau seret file ke sini</p>
                                <p class="text-xs text-slate-400 font-medium">Format: PDF, JPG, PNG (Maks 2MB)</p>
                            </div>

                            <div x-cloak x-show="fileName" class="flex flex-col items-center">
                                <i class="fa-solid fa-file-circle-check text-4xl text-green-500 mb-2"></i>
                                <p class="text-sm font-bold text-slate-700" x-text="fileName"></p>
                                <p class="text-xs text-red-500 mt-1 font-bold cursor-pointer">Klik untuk mengganti file</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 mt-4 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-widest hover:bg-red-600 hover:-translate-y-1 shadow-xl hover:shadow-red-500/30 transition-all duration-300">
                        Daftarkan Vendor Saya
                    </button>

                </form>
            </div>
        </div>
    </main>

    <footer class="py-6 text-center text-slate-500 text-xs font-medium tracking-widest opacity-60">
        &copy; 2026 VERIDEX SUPPLY CHAIN MANAGEMENT
    </footer>

</body>
</html>