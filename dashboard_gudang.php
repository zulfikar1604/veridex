<?php
session_start();
// Proteksi halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

// 1. PANGGIL KONEKSI DATABASE
require 'database.php';

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// Format tanggal hari ini
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$bulan_array = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = $hari_array[date('w')] . ', ' . date('j') . ' ' . $bulan_array[date('n')] . ' ' . date('Y');

// 2. HITUNG DATA UNTUK KARTU INDIKATOR ATAS
$kritis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory WHERE status_label='Kritis'"))['total'];
$warning = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory WHERE status_label='Warning'"))['total'];
$aman = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory WHERE status_label='Aman'"))['total'];

// 3. AMBIL SEMUA DATA INVENTORY
// Diurutkan agar yang kritis muncul duluan di paling atas
$query_inventory = mysqli_query($conn, "SELECT * FROM inventory ORDER BY FIELD(status_label, 'Kritis', 'Warning', 'Aman'), last_update DESC");
?>
<?php
$page_title = "Inventory";
$page_subtitle = "Monitoring Stok • Kelola Inbound & Outbound";
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active = 'inventory'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-5 md:p-6 space-y-6 animate-fade-in-up">
            <!-- GREETING BANNER -->
            <div class="bg-gradient-to-r from-indigo-500/10 to-transparent p-5 rounded-2xl border border-indigo-500/20 flex justify-between items-center flex-wrap gap-4 mb-6">
                <div>
                    <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Pusat Kendali Gudang</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500 font-medium mt-1">Selamat bertugas, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>. Kelola logistik masuk dan keluar sistem.</p>
                </div>
                <div class="bg-white dark:bg-[#1e293b] px-4 py-2 rounded-xl shadow-sm border border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <i class="fa-solid fa-boxes-stacked text-indigo-500 mr-1.5"></i> Gudang Aktif
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-[#1e293b] rounded-xl p-5 shadow-sm border border-slate-100 dark:border-slate-800 border-l-[8px] border-l-red-500 relative overflow-hidden flex flex-col justify-center min-h-[140px]">
                    <i class="fa-solid fa-triangle-exclamation absolute -right-4 -bottom-4 text-6xl text-slate-100 text-red-50 opacity-50 pointer-events-none"></i>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-red-600 uppercase tracking-widest mb-1">STOK KRITIS (< 7)</p>
                        <h3 class="text-3xl font-black text-slate-800 dark:text-white flex items-baseline gap-2">
                            <?= str_pad($kritis, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 dark:text-slate-500 italic normal-case">SKU</span>
                        </h3>
                        <p class="text-xs font-bold text-red-500 mt-2 italic">Segera Buat Pengadaan</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e293b] rounded-xl p-5 shadow-sm border border-slate-100 dark:border-slate-800 border-l-[8px] border-l-yellow-400 relative overflow-hidden flex flex-col justify-center min-h-[140px]">
                    <i class="fa-solid fa-clock-rotate-left absolute -right-2 -bottom-2 text-5xl text-slate-100 text-yellow-50 opacity-80 pointer-events-none"></i>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-yellow-500 uppercase tracking-widest mb-1">PERINGATAN (7 - 10)</p>
                        <h3 class="text-3xl font-black text-slate-800 dark:text-white flex items-baseline gap-2">
                            <?= str_pad($warning, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 dark:text-slate-500 italic normal-case">SKU</span>
                        </h3>
                        <p class="text-xs font-bold text-yellow-600 mt-2 italic">Pantau Pemakaian Rutin</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e293b] rounded-xl p-5 shadow-sm border border-slate-100 dark:border-slate-800 border-l-[8px] border-l-green-500 relative overflow-hidden flex flex-col justify-center min-h-[140px]">
                    <i class="fa-solid fa-circle-check absolute -right-4 -bottom-4 text-6xl text-slate-100 text-green-50 opacity-60 pointer-events-none"></i>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-green-500 uppercase tracking-widest mb-1">STOK AMAN (> 10)</p>
                        <h3 class="text-3xl font-black text-slate-800 dark:text-white flex items-baseline gap-2">
                            <?= str_pad($aman, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 dark:text-slate-500 italic normal-case">SKU</span>
                        </h3>
                        <p class="text-xs font-bold text-green-600 mt-2 italic">Ketersediaan Terjamin</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900">
                    <h3 class="font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest text-sm">Daftar Barang</h3>
                    <a href="tambah_barang.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-md shadow-red-500/30">
                        <i class="fa-solid fa-plus mr-2"></i> Tambah Barang
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <div class="overflow-x-auto w-full">
<table class="w-full text-left border-collapse">
                        <thead class="bg-[#111827] text-white">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">SKU & BARCODE</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest w-[30%]">NAMA BARANG</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">UNIT</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">STOK</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">STATUS LABEL</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            
                            <?php 
                            // LOOPING DATA DARI DATABASE
                            if(mysqli_num_rows($query_inventory) > 0) {
                                while($row = mysqli_fetch_assoc($query_inventory)) { 
                                    
                                    // Set warna styling otomatis berdasarkan status
                                    $row_bg = "border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:bg-slate-900";
                                    $text_stok = "text-slate-800 dark:text-white";
                                    $badge = "";

                                    if($row['status_label'] == 'Kritis') {
                                        $row_bg = "bg-red-50/40 border-b border-red-100 hover:bg-red-50";
                                        $text_stok = "text-red-600";
                                        $badge = "<span class='inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-600 text-white text-xs font-black uppercase tracking-wider shadow-md shadow-red-500/30'><div class='w-1.5 h-1.5 rounded-full bg-white dark:bg-[#1e293b]/80'></div> KRITIS</span>";
                                    } elseif($row['status_label'] == 'Warning') {
                                        $text_stok = "text-yellow-500";
                                        $badge = "<span class='inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-yellow-400 text-white text-xs font-black uppercase tracking-wider shadow-sm'><div class='w-1.5 h-1.5 rounded-full bg-white dark:bg-[#1e293b]/80'></div> WARNING</span>";
                                    } else {
                                        $text_stok = "text-green-600";
                                        $badge = "<span class='inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-black uppercase tracking-wider'><div class='w-1.5 h-1.5 rounded-full bg-green-500'></div> AMAN</span>";
                                    }
                            ?>
                                <tr class="<?= $row_bg ?> transition-colors">
                                    <td class="py-5 px-6">
                                        <div class="bg-white dark:bg-[#1e293b] p-2 border rounded inline-block">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=<?= urlencode($row['sku']) ?>" alt="<?= $row['sku'] ?>" class="h-10 w-10">
                                        </div>
                                        <div class="flex items-center gap-2 mt-2">
                                            <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?= $row['sku'] ?></div>
                                            <a href="print_barcode.php?sku=<?= urlencode($row['sku']) ?>&nama=<?= urlencode($row['nama_barang']) ?>" target="_blank" class="text-slate-400 dark:text-slate-500 hover:text-blue-600 transition" title="Cetak Barcode">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="py-5 px-6 font-bold text-slate-800 dark:text-white"><?= $row['nama_barang'] ?></td>
                                    <td class="py-5 px-6 text-slate-500 dark:text-slate-400 dark:text-slate-500"><?= $row['unit'] ?></td>
                                    <td class="py-5 px-6 font-black <?= $text_stok ?> text-xl">
                                        <?= $row['stok_saat_ini'] ?>
                                        <div class="text-[10px] font-medium text-slate-400 dark:text-slate-500 italic font-normal">Min: <?= $row['stok_kritis'] ?></div>
                                    </td>
                                    <td class="py-5 px-6"><?= $badge ?></td>
                                </tr>
                            <?php 
                                } 
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400 dark:text-slate-500 italic">Belum ada data barang di inventory.</td>
                                </tr>
                            <?php } ?>

                        </tbody>
                    </table>
</div>
                </div>
            </div>
        </main>
    </div>




</body>
</html>
