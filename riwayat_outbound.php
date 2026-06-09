<?php
session_start();
// Proteksi role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit();
}

require 'database.php';

date_default_timezone_set('Asia/Jakarta');
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$bulan_array = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = $hari_array[date('w')] . ', ' . date('j') . ' ' . $bulan_array[date('n')] . ' ' . date('Y');

// Query data riwayat outbound
$query_riwayat = mysqli_query($conn, "SELECT * FROM outbound_history");
$total_riwayat = mysqli_num_rows($query_riwayat);
?>
<?php
$page_title = "Riwayat Outbound";
$page_subtitle = "Log Pengeluaran Barang Keluar";
include 'header_meta.php';
?>

<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Riwayat Outbound";
    $page_subtitle = "Log Pengeluaran Barang Keluar";
    $navbar_badge = '<span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total: ' . $total_riwayat . ' Transaksi</span>';
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php
        $active_menu = 'riwayat_outbound';
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-blue-500 relative overflow-hidden flex justify-between items-center flex-wrap gap-4">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-1">Total Aktivitas Outbound</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2">
                        <?= str_pad($total_riwayat, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 italic normal-case">Transaksi</span>
                    </h3>
                    <p class="text-xs font-bold text-slate-400 mt-2 italic">Data riwayat pengeluaran barang dari gudang.</p>
                </div>
                <i class="fa-solid fa-truck-ramp-box text-7xl text-blue-50 opacity-80 pointer-events-none"></i>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Log Pengeluaran Barang</h3>

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
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Detail Barang</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-center">Jumlah Keluar</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Keterangan</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">

                            <?php
                            if($total_riwayat > 0) {
                                while($row = mysqli_fetch_assoc($query_riwayat)) {
                            ?>
                                <tr class="border-b border-slate-100 hover:bg-blue-50/30 transition-colors">
                                    <td class="py-5 px-6">
                                        <div class="font-black text-slate-800 uppercase"><?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold uppercase inline-block mt-1">
                                            SKU: <?= htmlspecialchars($row['sku'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td class="py-5 px-6 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm font-black uppercase tracking-wider">
                                            <i class="fa-solid fa-arrow-turn-up fa-rotate-90 text-[10px]"></i> -<?= $row['jumlah_keluar'] ?>
                                        </span>
                                    </td>
                                    <td class="py-5 px-6 font-medium text-slate-600">
                                        <?= htmlspecialchars($row['keterangan'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-5 px-6 font-bold text-slate-600">
                                        <i class="fa-solid fa-user-check text-slate-400 mr-2"></i> <?= htmlspecialchars($row['user_gudang'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <i class="fa-solid fa-folder-open text-5xl text-slate-200 mb-3 block"></i>
                                        <span class="text-slate-400 italic font-medium">Belum ada riwayat barang keluar.</span>
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




</body>
</html>
