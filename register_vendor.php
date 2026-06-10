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
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center flex-wrap gap-4">
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
        <div class="grid lg:grid-cols-1 md:grid-cols-2 gap-16 items-center w-full">
            
            <div class="space-y-8 animate-[fade-in-right_1s_ease-out]">
                <div>
                    <span class="inline-block py-1 px-3 rounded-full bg-red-100 text-red-600 text-xs font-bold tracking-widest mb-4">GABUNG BERSAMA KAMI</span>
                    <h2 class="text-5xl font-black text-slate-900 mb-6 leading-tight">
                        Jadilah Bagian Dari <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-red-400">Ekosistem Pengadaan</span>
                    </h2>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-lg mb-8">
                        Veridex adalah platform ekosistem digital untuk menjamin transparansi pengadaan barang operasional. Kami memprioritaskan kualitas, integritas, dan kecepatan transaksi.
                    </p>
                </div>

                <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-800 dark:text-white mb-4 uppercase tracking-widest text-xs border-b pb-3 flex items-center">
                        <i class="fa-solid fa-fire text-red-500 mr-2"></i> Kebutuhan Rutin Kami:
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-paperclip mr-2 text-slate-400 dark:text-slate-500"></i>Alat Tulis Kantor</span>
                        <span class="bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-computer mr-2 text-slate-400 dark:text-slate-500"></i>Maintenance IT</span>
                        <span class="bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-helmet-safety mr-2 text-slate-400 dark:text-slate-500"></i>Konstruksi Ringan</span>
                        <span class="bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors cursor-default"><i class="fa-solid fa-boxes-stacked mr-2 text-slate-400 dark:text-slate-500"></i>Bahan Baku Kemasan</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#1e293b] p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-white">
                
                <h3 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">Form Registrasi Vendor</h3>
                <p class="text-slate-500 dark:text-slate-400 dark:text-slate-500 text-sm mb-8">Isi data perusahaan Anda. Tim verifikasi kami akan mengecek dalam 1x24 jam.</p>

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
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Nama Perusahaan (PT/CV)</label>
                        <input type="text" name="nama_perusahaan" required 
                               class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                               placeholder="Contoh: PT. Solusi Maju Bersama">
                    </div>

                    <div class="relative">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Spesialisasi Pengadaan</label>
                        <select name="spesialisasi" required 
                               class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200 appearance-none">
                            <option value="" disabled selected>-- Pilih Kategori Spesialisasi --</option>
                            <option value="Alat Tulis Kantor (ATK)">Alat Tulis Kantor (ATK)</option>
                            <option value="Perangkat IT & Komputer">Perangkat IT & Komputer</option>
                            <option value="Bahan Baku Pabrik">Bahan Baku Pabrik</option>
                            <option value="Konstruksi & Material Bangunan">Konstruksi & Material Bangunan</option>
                            <option value="Jasa Kebersihan (Cleaning Service)">Jasa Kebersihan (Cleaning Service)</option>
                            <option value="Jasa Keamanan (Security)">Jasa Keamanan (Security)</option>
                            <option value="Perabotan & Mebel Kantor">Perabotan & Mebel Kantor</option>
                            <option value="Alat Keselamatan Kerja (K3)">Alat Keselamatan Kerja (K3)</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 pt-8 text-slate-500 dark:text-slate-400 dark:text-slate-500">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Alamat Lengkap</label>
                        <textarea name="alamat" required rows="3"
                               class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                               placeholder="Contoh: Jl. Sudirman No. 123, Jakarta"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Email Perusahaan (Username Login)</label>
                            <input type="email" name="email" required 
                                   class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                                   placeholder="Contoh: info@perusahaan.com">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Password Akun Baru</label>
                            <input type="password" name="password" required 
                                   class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                                   placeholder="Masukkan password...">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Nama PIC</label>
                            <input type="text" name="pic_nama" required 
                                   class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                                   placeholder="Contoh: Budi Santoso">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Nomor Kontak PIC (WA/Telp)</label>
                            <input type="text" name="pic_kontak" required 
                                   class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                                   placeholder="Contoh: 081234567890">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 dark:border-slate-800 pt-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Nomor Induk Berusaha (NIB)</label>
                            <input type="text" name="nib" required 
                                   class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                                   placeholder="Contoh: 1234567890123">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">NPWP Perusahaan</label>
                            <input type="text" name="npwp" required 
                                   class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition-all font-medium text-slate-700 dark:text-slate-200" 
                                   placeholder="Contoh: 12.345.678.9-012.000">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 mt-4 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-widest hover:bg-red-600 hover:-translate-y-1 shadow-xl hover:shadow-red-500/30 transition-all duration-300">
                        Daftarkan Vendor Saya
                    </button>

                </form>
            </div>
        </div>
    </main>

    <footer class="py-6 text-center text-slate-500 dark:text-slate-400 dark:text-slate-500 text-xs font-medium tracking-widest opacity-60">
        &copy; 2026 VERIDEX SUPPLY CHAIN MANAGEMENT
    </footer>

</body>
</html>