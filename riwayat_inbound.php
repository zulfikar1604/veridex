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
$query_riwayat = mysqli_query($conn, "SELECT * FROM inbound_history ORDER BY tanggal_masuk DESC");
$total_riwayat = mysqli_num_rows($query_riwayat);
?>
<?php
$page_title = "Riwayat Inbound";
$page_subtitle = "Log Penerimaan Barang Masuk";
$navbar_badge = '<span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total: ' . $total_riwayat . ' Transaksi</span>';
include 'header_meta.php';
?>

<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active = 'riwayat_inbound'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-800 border-l-[8px] border-l-green-500 relative overflow-hidden flex justify-between items-center flex-wrap gap-4">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-widest mb-1">Total Aktivitas Inbound</p>
                    <h3 class="text-4xl font-black text-slate-800 dark:text-white flex items-baseline gap-2">
                        <?= str_pad($total_riwayat, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 italic normal-case">Transaksi</span>
                    </h3>
                    <p class="text-xs font-bold text-slate-400 mt-2 italic">Data riwayat penerimaan barang masuk dari vendor/pengadaan.</p>
                </div>
                <i class="fa-solid fa-boxes-packing text-7xl text-green-50 dark:text-green-900/20 opacity-80 pointer-events-none"></i>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400"></i>
                    </div>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari SKU, Nama Barang, atau Petugas..." style="padding-left: 2.5rem;" class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors text-slate-800 dark:text-white">
                </div>
            </div>

            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                        <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            <th class="p-6">Tanggal & Waktu</th>
                            <th class="p-6">Detail Barang</th>
                            <th class="p-6 text-center">Jumlah Masuk</th>
                            <th class="p-6 text-center">Petugas Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody id="dataTable" class="divide-y divide-slate-50 dark:divide-slate-800">
                        
                        <?php 
                        if($total_riwayat > 0) {
                            while($row = mysqli_fetch_assoc($query_riwayat)) { 
                        ?>
                            <tr class="hover:bg-green-50/30 dark:hover:bg-slate-900/50 transition-colors group">
                                <td class="p-6">
                                    <div class="font-black text-slate-800 dark:text-white text-sm"><?= date('d M Y', strtotime($row['tanggal_masuk'])) ?></div>
                                    <div class="text-[10px] text-slate-400 font-bold mt-1">
                                        <i class="fa-regular fa-clock mr-1"></i> <?= date('H:i', strtotime($row['tanggal_masuk'])) ?> WIB
                                    </div>
                                </td>
                                <td class="p-6">
                                    <div class="font-bold text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                    <div class="text-[10px] text-slate-400 font-bold mt-1">
                                        SKU: <?= htmlspecialchars($row['sku']) ?>
                                    </div>
                                </td>
                                <td class="p-6 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 border border-green-200 rounded-lg bg-green-50 text-green-700 text-[10px] font-black uppercase tracking-wider shadow-sm">
                                        <i class="fa-solid fa-arrow-turn-down fa-rotate-90"></i> +<?= number_format($row['jumlah_masuk']) ?>
                                    </span>
                                </td>
                                <td class="p-6 text-center">
                                    <div class="inline-flex items-center text-xs font-bold text-slate-600 dark:text-slate-300">
                                        <i class="fa-solid fa-user-check text-green-500 mr-2"></i> <?= htmlspecialchars($row['user_gudang']) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            } 
                        } else {
                        ?>
                            <tr>
                                <td colspan="4" class="p-16 text-center">
                                    <i class="fa-solid fa-folder-open text-7xl text-slate-200 dark:text-slate-700 mb-6 block"></i>
                                    <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Riwayat Inbound</h3>
                                    <p class="text-slate-400 text-sm">Barang yang berhasil discan masuk akan tercatat di sini.</p>
                                </td>
                            </tr>
                        <?php } ?>

                    </tbody>
                </table>
</div>
            </div>
        </main>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#dataTable tr");
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }
    </script>
</body>
</html>
