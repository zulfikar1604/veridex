<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

require 'database.php';
date_default_timezone_set('Asia/Jakarta');
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$bulan_array = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = $hari_array[date('w')] . ', ' . date('j') . ' ' . $bulan_array[date('n')] . ' ' . date('Y');
?>
<?php
$page_title = "Stok ROP";
$page_subtitle = "Peringatan Safety Stock & Reorder Point";
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Stok ROP";
    $page_subtitle = "Peringatan Safety Stock & Reorder Point";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active = 'rop'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <?php
                $query_kritis = mysqli_query($conn, "SELECT * FROM inventory WHERE status_label = 'Kritis' ORDER BY stok_saat_ini ASC");
                $total_kritis = mysqli_num_rows($query_kritis);
            ?>

            <div class="space-y-4 max-w-4xl mx-auto">

                <!-- Header ringkasan -->
                <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Total Barang Butuh Pengadaan</span>
                        <p class="text-3xl font-black text-red-600 mt-1">
                            <?= str_pad($total_kritis, 2, '0', STR_PAD_LEFT) ?>
                            <span class="text-sm font-medium text-slate-400 dark:text-slate-500">SKU</span>
                        </p>
                    </div>
                    <i class="fa-solid fa-triangle-exclamation text-5xl text-red-100"></i>
                </div>

                <!-- List barang kritis -->
                <?php if ($total_kritis > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($query_kritis)): ?>
                        <div class="bg-red-600 p-6 rounded-2xl text-white shadow-xl flex items-center justify-between border-4 border-red-400 animate-pulse">
                            <div>
                                <span class="text-xs font-bold bg-red-800/40 px-2 py-1 rounded-full uppercase tracking-widest"><?= htmlspecialchars($row['sku']) ?></span>
                                <h4 class="text-lg font-black uppercase tracking-tight mt-2"><?= htmlspecialchars($row['nama_barang']) ?></h4>
                                <p class="text-red-100 text-sm mt-1">
                                    Sisa stok: <span class="font-black text-white"><?= $row['stok_saat_ini'] ?> <?= htmlspecialchars($row['unit']) ?></span>
                                    &nbsp;·&nbsp; ROP: <span class="font-bold"><?= $row['stok_kritis'] ?> <?= htmlspecialchars($row['unit']) ?></span>
                                </p>
                            </div>
                            <div class="text-center shrink-0 ml-6">
                                <i class="fa-solid fa-bell text-4xl opacity-80 mb-2 block"></i>
                                <span class="text-xs font-bold bg-red-800/40 px-3 py-1 rounded-full">KRITIS</span>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <!-- Semua aman -->
                    <div class="bg-white dark:bg-[#1e293b] p-10 rounded-2xl border shadow-sm text-center">
                        <i class="fa-solid fa-check-double text-5xl text-green-200 mb-4 block"></i>
                        <p class="text-slate-500 dark:text-slate-400 dark:text-slate-500 font-bold">Semua stok dalam kondisi aman.</p>
                        <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Tidak ada barang yang perlu pengadaan saat ini.</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>




</body>
</html>
