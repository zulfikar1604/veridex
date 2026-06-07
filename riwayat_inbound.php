<?php
session_start();
// Proteksi role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

require 'database.php';

date_default_timezone_set('Asia/Jakarta');
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$bulan_array = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = $hari_array[date('w')] . ', ' . date('j') . ' ' . $bulan_array[date('n')] . ' ' . date('Y');

// Query data riwayat inbound
// Jika tabel inbound_history belum ada datanya, tidak akan error, hanya tampil kosong.
$query_riwayat = mysqli_query($conn, "SELECT * FROM inbound_history ORDER BY tanggal_masuk DESC");
$total_riwayat = mysqli_num_rows($query_riwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Inbound | Veridex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
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
    $page_title = "Riwayat Inbound";
    $page_subtitle = "Log Penerimaan Barang Masuk";
    $navbar_badge = '<span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total: ' . $total_riwayat . ' Transaksi</span>';
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active_menu = 'riwayat_inbound'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-green-500 relative overflow-hidden flex justify-between items-center flex-wrap gap-4">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-widest mb-1">Total Aktivitas Inbound</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2">
                        <?= str_pad($total_riwayat, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 italic normal-case">Transaksi</span>
                    </h3>
                    <p class="text-xs font-bold text-slate-400 mt-2 italic">Data riwayat penerimaan barang masuk dari vendor/pengadaan.</p>
                </div>
                <i class="fa-solid fa-boxes-packing text-7xl text-green-50 opacity-80 pointer-events-none"></i>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Log Penerimaan Barang</h3>
                    
                    <div class="flex gap-2">
                        <input type="text" placeholder="Cari SKU / Nama Barang..." class="border border-slate-200 rounded-lg px-4 py-2 text-xs focus:outline-red-500">
                        <button class="bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest"><i class="fa-solid fa-filter"></i> Filter</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div class="overflow-x-auto w-full">
<table class="w-full text-left border-collapse">
                        <thead class="bg-[#111827] text-white">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Tanggal & Waktu</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Detail Barang</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-center">Jumlah Masuk</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            
                            <?php 
                            if($total_riwayat > 0) {
                                while($row = mysqli_fetch_assoc($query_riwayat)) { 
                            ?>
                                <tr class="border-b border-slate-100 hover:bg-green-50/30 transition-colors">
                                    <td class="py-5 px-6">
                                        <div class="font-bold text-slate-800"><?= date('d M Y', strtotime($row['tanggal_masuk'])) ?></div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                                            <i class="fa-regular fa-clock mr-1"></i> <?= date('H:i:s', strtotime($row['tanggal_masuk'])) ?> WIB
                                        </div>
                                    </td>
                                    <td class="py-5 px-6">
                                        <div class="font-black text-slate-800 uppercase"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                        <div class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold uppercase inline-block mt-1">
                                            SKU: <?= htmlspecialchars($row['sku']) ?>
                                        </div>
                                    </td>
                                    <td class="py-5 px-6 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-black uppercase tracking-wider">
                                            <i class="fa-solid fa-arrow-turn-down fa-rotate-90 text-[10px]"></i> +<?= $row['jumlah_masuk'] ?>
                                        </span>
                                    </td>
                                    <td class="py-5 px-6 font-bold text-slate-600">
                                        <i class="fa-solid fa-user-check text-slate-400 mr-2"></i> <?= htmlspecialchars($row['user_gudang']) ?>
                                    </td>
                                </tr>
                            <?php 
                                } 
                            } else {
                            ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <i class="fa-solid fa-folder-open text-5xl text-slate-200 mb-3 block"></i>
                                        <span class="text-slate-400 italic font-medium">Belum ada riwayat barang masuk.</span>
                                    </td>
                                </tr>
                            <?php } ?>

                        </tbody>
                    </table>
</div>
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
