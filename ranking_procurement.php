<?php
session_start();
require 'database.php'; // Koneksi $conn

// 1. Proteksi Halaman: Hanya Procurement dan Manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'ranking';

// 2. Query Data: Menggunakan nama kolom sesuai image_fe2e5f.png
$query_items = mysqli_query($conn, "
    SELECT * FROM inventory 
    WHERE stok_kritis > stok_saat_ini 
    ORDER BY stok_saat_ini ASC
") or handle_db_error($conn);

// 3. Ambil data vendor untuk dropdown
$query_vendors = mysqli_query($conn, "SELECT id, nama_perusahaan FROM vendors ORDER BY nama_perusahaan ASC");
$vendors = [];
while($v = mysqli_fetch_assoc($query_vendors)) {
    $vendors[] = $v;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ranking & Multi-Vendor | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>[x-cloak] { display: none !important; }
    <style>
        #main-sidebar { transition: width 0.3s ease, min-width 0.3s ease, transform 0.3s ease; }
        @media (min-width: 768px) {
            #main-sidebar { position: relative !important; transform: translateX(0) !important; width: 280px; min-width: 280px; overflow: hidden; }
            #main-sidebar.sidebar-collapsed { width: 0 !important; min-width: 0 !important; }
            #sidebar-overlay { display: none !important; }
        }
        @media (max-width: 767px) {
            #main-sidebar { position: fixed !important; top: 0; left: 0; bottom: 0; z-index: 50; width: 280px !important; min-width: 280px !important; transform: translateX(-100%); }
            #main-sidebar.sidebar-open { transform: translateX(0); }
            #sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 40; }
            #sidebar-overlay.overlay-open { display: block; }
        }
        .sidebar-toggle-btn { display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;cursor:pointer;transition:background 0.2s,color 0.2s,transform 0.1s;flex-shrink:0; }
        .sidebar-toggle-btn:hover { background:#ef4444;color:#fff;border-color:#ef4444; }
        .sidebar-toggle-btn:active { transform:scale(0.92); }
        .sidebar-close-btn { display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.08);color:#94a3b8;cursor:pointer;transition:background 0.2s,color 0.2s;flex-shrink:0;border:none; }
        .sidebar-close-btn:hover { background:rgba(239,68,68,0.2);color:#fca5a5; }
    </style>
</head>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Ranking Procurement";
    $page_subtitle = "Evaluasi Rekomendasi Vendor Terbaik";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-6xl">
                <!-- Label Tabel -->
                <div class="grid grid-cols-12 gap-4 px-6 py-3 bg-slate-900 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] rounded-t-2xl">
                    <div class="col-span-6">Informasi Barang & SKU</div>
                    <div class="col-span-2 text-center">Stok / Kritis</div>
                    <div class="col-span-2 text-center">Status</div>
                    <div class="col-span-2 text-right">Konfigurasi</div>
                </div>

                <div class="space-y-2 mt-2">
                    <?php if(mysqli_num_rows($query_items) > 0): ?>
                        <?php while($item = mysqli_fetch_assoc($query_items)): ?>
                        <!-- Alpine.js Accordion Logic[cite: 1] -->
                        <div x-data="{ open: false }" class="bg-white border border-slate-200 rounded-2xl shadow-sm hover:border-red-200 transition-all overflow-hidden">
                            
                            <!-- Header Baris (Master)[cite: 1] -->
                            <div class="grid grid-cols-12 gap-4 p-5 items-center">
                                <div class="col-span-6">
                                    <h4 class="font-black text-slate-800 text-lg"><?= $item['nama_barang'] ?></h4>
                                    <div class="flex gap-3 mt-1">
                                        <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold uppercase">SKU: <?= $item['sku'] ?></span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase"><i class="fa-solid fa-box mr-1"></i> Satuan: <?= $item['unit'] ?></span>
                                    </div>
                                </div>
                                <div class="col-span-2 text-center">
                                    <div class="text-xl font-black text-slate-700"><?= $item['stok_saat_ini'] ?> / <span class="text-red-500"><?= $item['stok_kritis'] ?></span></div>
                                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Inventory Gap</div>
                                </div>
                                <div class="col-span-2 text-center">
                                    <span class="px-3 py-1 bg-red-50 text-red-600 border border-red-100 rounded-full text-[9px] font-black uppercase tracking-widest">
                                        <?= $item['status_label'] ?>
                                    </span>
                                </div>
                                <div class="col-span-2 text-right">
                                    <button @click="open = !open" class="p-2 px-4 rounded-xl border border-slate-200 hover:bg-slate-900 hover:text-white transition-all text-xs font-black uppercase tracking-widest flex items-center gap-2 ml-auto">
                                        <span x-text="open ? 'Close' : 'Set Rank'"></span>
                                        <i class="fa-solid fa-chevron-down transition-transform" :class="open ? 'rotate-180' : ''"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Detail Form (Muncul saat Klik)[cite: 1] -->
                            <div x-show="open" x-cloak x-transition class="bg-slate-50 border-t border-slate-100 p-8">
                                <form action="process_ranking.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <input type="hidden" name="inventory_id" value="<?= $item['id'] ?>">
                                    
                                    <!-- Rank 1: Main Vendor -->
                                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                                        <div class="flex justify-between items-center mb-4">
                                            <span class="w-8 h-8 bg-yellow-400 text-slate-900 rounded-lg flex items-center justify-center font-black">1</span>
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Utama</span>
                                        </div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Pilih Vendor</label>
                                        <select name="vendor_1" class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg text-sm mb-4 outline-none focus:border-red-500" required>
                                            <option value="">-- Pilih Vendor --</option>
                                            <?php foreach($vendors as $v): ?>
                                                <option value="<?= $v['id'] ?>"><?= $v['nama_perusahaan'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Kuota (%)</label>
                                        <div class="relative">
                                            <input type="number" name="quota_1" value="70" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl font-black text-center focus:border-red-500 outline-none" required>
                                            <span class="absolute right-4 top-3.5 text-slate-400 font-black">%</span>
                                        </div>
                                    </div>

                                    <!-- Rank 2: Secondary -->
                                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                                        <div class="flex justify-between items-center mb-4">
                                            <span class="w-8 h-8 bg-slate-200 text-slate-600 rounded-lg flex items-center justify-center font-black">2</span>
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Pendamping</span>
                                        </div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Pilih Vendor</label>
                                        <select name="vendor_2" class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg text-sm mb-4 outline-none">
                                            <option value="">-- Pilih Vendor --</option>
                                            <?php foreach($vendors as $v): ?>
                                                <option value="<?= $v['id'] ?>"><?= $v['nama_perusahaan'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Kuota (%)</label>
                                        <div class="relative">
                                            <input type="number" name="quota_2" value="30" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl font-black text-center outline-none">
                                            <span class="absolute right-4 top-3.5 text-slate-400 font-black">%</span>
                                        </div>
                                    </div>

                                    <!-- Konfirmasi Tombol -->
                                    <div class="flex flex-col justify-center items-center p-5">
                                        <p class="text-center text-[10px] text-slate-400 font-bold uppercase mb-4 px-4">Pastikan total kuota mencapai <span class="text-red-500">100%</span> sebelum melakukan penyimpanan data.</p>
                                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-red-600/20 transition-all flex items-center justify-center gap-3">
                                            <i class="fa-solid fa-check-double"></i> Simpan Ranking
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bg-white p-20 rounded-3xl text-center border-2 border-dashed border-slate-200">
                            <i class="fa-solid fa-boxes-stacked text-5xl text-slate-200 mb-4"></i>
                            <p class="text-slate-400 font-bold">Semua stok barang masih aman (di atas stok kritis).</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                sidebar.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('sidebar-open');
                if (overlay) overlay.classList.toggle('overlay-open');
            }
        }
    </script>

</body>
</html>