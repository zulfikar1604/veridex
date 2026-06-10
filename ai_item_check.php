<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}
require 'database.php';

$active = 'ai_analytics';
$page_title = "AI Analytics & Prediksi";
$page_subtitle = "Global Radar & Deep Dive Item Intelligence";

include 'header_meta.php';

// === 1. LOGIK UNTUK GLOBAL RADAR (Tabel Semua Barang) ===
$sku_query = mysqli_query($conn, "SELECT id, sku, nama_barang, stok_saat_ini, unit FROM inventory ORDER BY nama_barang ASC");
$inventory_data = [];
$items_dropdown = []; // Untuk dropdown
while ($row = mysqli_fetch_assoc($sku_query)) {
    $inventory_data[$row['sku']] = $row;
    $items_dropdown[] = $row;
}

// Ambil data pengeluaran 90 Hari Terakhir untuk semua SKU
$q_hist = mysqli_query($conn, "SELECT sku, SUM(jumlah_keluar) as total_keluar FROM outbound_history WHERE tanggal_keluar >= DATE_SUB(NOW(), INTERVAL 90 DAY) GROUP BY sku");
$outbound_90 = [];
while ($row = mysqli_fetch_assoc($q_hist)) {
    $outbound_90[$row['sku']] = (int)$row['total_keluar'];
}

$analysis_table = [];
foreach ($inventory_data as $sku => $inv) {
    $total_keluar_90 = $outbound_90[$sku] ?? 0;
    $daily_demand = $total_keluar_90 / 90;
    $stok_saat_ini = (int)$inv['stok_saat_ini'];
    
    if ($daily_demand > 0) {
        $days_remaining = floor($stok_saat_ini / $daily_demand);
    } else {
        $days_remaining = -1;
    }
    
    if ($days_remaining === -1) {
        $status = 'Stagnan (Belum Ada Data Keluar)';
        $color = 'slate';
        $days_text = '∞';
    } elseif ($days_remaining <= 7) {
        $status = 'Kritis (Akan Segera Habis)';
        $color = 'red';
        $days_text = $days_remaining . ' Hari Lagi';
    } elseif ($days_remaining <= 21) {
        $status = 'Siaga (Mulai Restock)';
        $color = 'yellow';
        $days_text = $days_remaining . ' Hari Lagi';
    } else {
        $status = 'Aman';
        $color = 'emerald';
        $days_text = $days_remaining . ' Hari Lagi';
    }
    
    $analysis_table[] = [
        'id' => $inv['id'],
        'sku' => $sku,
        'nama_barang' => $inv['nama_barang'],
        'stok' => $stok_saat_ini,
        'daily_demand' => number_format($daily_demand, 2),
        'days_remaining' => $days_remaining,
        'days_text' => $days_text,
        'status' => $status,
        'color' => $color
    ];
}

// Urutkan Global Radar
usort($analysis_table, function($a, $b) {
    if ($a['days_remaining'] === -1) return 1;
    if ($b['days_remaining'] === -1) return -1;
    return $a['days_remaining'] <=> $b['days_remaining'];
});


// === 2. LOGIK UNTUK DEEP DIVE (Satu Barang Spesifik) ===
$selected_id = isset($_GET['inventory_id']) ? (int)$_GET['inventory_id'] : 0;
$item_detail = null;
$monthly_demand = 0;
$days_remaining_deep = -1;
$vendor_history = [];

if ($selected_id > 0) {
    $query_detail = mysqli_query($conn, "SELECT * FROM inventory WHERE id = $selected_id");
    $item_detail = mysqli_fetch_assoc($query_detail);
    
    if ($item_detail) {
        $sku = $item_detail['sku'];
        $total_90_deep = $outbound_90[$sku] ?? 0;
        
        $daily_demand_deep = $total_90_deep / 90;
        $monthly_demand = $total_90_deep / 3;
        
        if ($daily_demand_deep > 0) {
            $days_remaining_deep = floor($item_detail['stok_saat_ini'] / $daily_demand_deep);
        }
        
        $q_vendor = mysqli_query($conn, "
            SELECT v.nama_perusahaan, COUNT(po.id) as freq, SUM(po.jumlah_pesan) as total_vol 
            FROM purchase_orders po 
            JOIN vendors v ON po.vendor_id = v.id 
            WHERE po.inventory_id = $selected_id 
            GROUP BY v.id 
            ORDER BY freq DESC, total_vol DESC
        ");
        
        while ($v_row = mysqli_fetch_assoc($q_vendor)) {
            $vendor_history[] = $v_row;
        }
    }
}
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex h-screen overflow-hidden text-slate-800 dark:text-white">
    <?php include 'sidebar_procurement.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include 'navbar.php'; ?>
        
        <div class="flex-1 overflow-y-auto p-6 md:p-8 scroll-smooth" id="main-scroll">
            <div class="max-w-7xl mx-auto space-y-8">

                <!-- HEADER UTAMA -->
                <div class="bg-gradient-to-r from-violet-900 to-slate-900 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden flex items-center justify-between">
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white dark:bg-[#1e293b] rounded-full blur-3xl opacity-10"></div>
                    <div class="relative z-10">
                        <h2 class="text-2xl font-black uppercase tracking-tight">AI Analytics & Command Center</h2>
                        <p class="text-sm text-violet-200 mt-2 font-medium max-w-xl">
                            Pantau pergerakan stok seluruh gudang melalui Global Radar, atau selami profil satu barang secara mendalam di panel Deep Dive.
                        </p>
                    </div>
                    <div class="hidden md:flex w-16 h-16 bg-white dark:bg-[#1e293b]/5 rounded-2xl items-center justify-center backdrop-blur-md border border-white/10">
                        <i class="fa-solid fa-radar text-3xl text-fuchsia-400"></i>
                    </div>
                </div>

                <!-- LAPIS 1: GLOBAL RADAR (TABEL SELURUH BARANG) -->
                <div class="bg-white dark:bg-[#1e293b] rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-800">
                    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight"><i class="fa-solid fa-earth-asia text-blue-500 mr-2"></i> Global Radar (Burnout Rate)</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 font-bold mt-1">Daftar diurutkan otomatis dari barang yang estimasi habisnya paling cepat.</p>
                        </div>
                        
                        <!-- Search Form (Deep Dive) -->
                        <form method="GET" action="ai_item_check.php#deep-dive" class="w-full md:w-auto flex bg-slate-50 dark:bg-slate-900 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-700">
                            <select name="inventory_id" onchange="this.form.submit()" class="w-full md:w-64 bg-transparent text-slate-700 dark:text-slate-200 focus:outline-none px-4 py-2 text-sm font-bold cursor-pointer">
                                <option value="" disabled <?= (!$selected_id) ? 'selected' : '' ?>>Pilih Barang Spesifik...</option>
                                <?php foreach ($items_dropdown as $itm): ?>
                                    <option value="<?= $itm['id'] ?>" <?= ($selected_id == $itm['id']) ? 'selected' : '' ?>>
                                        <?= $itm['sku'] ?> - <?= htmlspecialchars($itm['nama_barang'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-2 border-slate-100 dark:border-slate-800 text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-black">
                                    <th class="pb-3 px-4">SKU / Nama Barang</th>
                                    <th class="pb-3 px-4 text-center">Stok</th>
                                    <th class="pb-3 px-4 text-center">Turnover Harian</th>
                                    <th class="pb-3 px-4 text-center">Estimasi Habis</th>
                                    <th class="pb-3 px-4">Status AI</th>
                                    <th class="pb-3 px-4 text-right">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php foreach ($analysis_table as $row): ?>
                                <tr class="border-b border-slate-50 hover:bg-slate-50 dark:bg-slate-900 transition-colors <?= ($selected_id == $row['id']) ? 'bg-fuchsia-50/50' : '' ?>">
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-black tracking-widest mt-0.5"><?= $row['sku'] ?></div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-block px-3 py-1 bg-slate-100 text-slate-600 dark:text-slate-300 font-black rounded-lg text-xs">
                                            <?= $row['stok'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center font-bold text-slate-600 dark:text-slate-300">
                                        <?= $row['daily_demand'] ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <?php if ($row['days_remaining'] === -1): ?>
                                            <span class="text-slate-400 dark:text-slate-500 font-bold text-lg">∞</span>
                                        <?php else: ?>
                                            <span class="inline-block px-4 py-2 <?= ($row['days_remaining'] <= 7) ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700' ?> font-black rounded-xl text-sm border shadow-sm">
                                                <?= $row['days_text'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-<?= $row['color'] ?>-50 text-<?= $row['color'] ?>-700 border border-<?= $row['color'] ?>-200 font-black rounded-lg text-[10px] uppercase tracking-wider shadow-sm">
                                            <span class="w-1.5 h-1.5 bg-<?= $row['color'] ?>-500 rounded-full <?= ($row['days_remaining'] !== -1 && $row['days_remaining'] <= 21) ? 'animate-pulse' : '' ?>"></span>
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right space-x-1">
                                        <!-- Tombol Buat RFQ -->
                                        <?php if ($_SESSION['role'] === 'Procurement'): ?>
                                            <?php if ($row['days_remaining'] !== -1 && $row['days_remaining'] <= 21): ?>
                                                <a href="create_rfq.php?inventory_id=<?= $row['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition" title="Buat RFQ">
                                                    <i class="fa-solid fa-bullhorn text-xs"></i>
                                                </a>
                                            <?php else: ?>
                                                <button disabled class="inline-flex items-center justify-center w-8 h-8 bg-slate-50 dark:bg-slate-900 text-slate-300 rounded-lg cursor-not-allowed">
                                                    <i class="fa-solid fa-check text-xs"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Tombol Deep Dive -->
                                        <a href="ai_item_check.php?inventory_id=<?= $row['id'] ?>#deep-dive" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-900 text-white hover:bg-fuchsia-600 rounded-lg text-[10px] font-black uppercase tracking-widest transition shadow-md shadow-slate-900/10">
                                            <i class="fa-solid fa-magnifying-glass"></i> Deep Dive
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pembatas Visual -->
                <div id="deep-dive" class="pt-4 pb-2">
                    <div class="flex items-center justify-center gap-4">
                        <div class="h-px bg-slate-200 flex-1"></div>
                        <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">Deep Dive Area</div>
                        <div class="h-px bg-slate-200 flex-1"></div>
                    </div>
                </div>

                <!-- LAPIS 2: DEEP DIVE ANALYTICS -->
                <?php if ($item_detail): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Kartu Identitas Barang -->
                    <div class="bg-white dark:bg-[#1e293b] rounded-3xl p-8 shadow-sm border border-fuchsia-100 flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 bg-fuchsia-50 text-fuchsia-500 rounded-2xl flex items-center justify-center text-xl mb-4 border border-fuchsia-100">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1"><?= $item_detail['sku'] ?></p>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white leading-tight"><?= htmlspecialchars($item_detail['nama_barang'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500 mt-2">Satuan: <span class="text-slate-800 dark:text-white bg-slate-100 px-2 py-0.5 rounded-md uppercase text-[10px]"><?= $item_detail['unit'] ?></span></p>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex items-end justify-between">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Stok Saat Ini</p>
                                <div class="text-4xl font-black text-slate-800 dark:text-white"><?= $item_detail['stok_saat_ini'] ?></div>
                            </div>
                            <?php 
                            if ($days_remaining_deep === -1) {
                                echo '<div class="text-xs font-bold text-slate-400 dark:text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><i class="fa-solid fa-infinity mr-1"></i> Stagnan</div>';
                            } elseif ($days_remaining_deep <= 7) {
                                echo '<div class="text-xs font-black uppercase tracking-widest text-red-600 bg-red-50 px-3 py-1 rounded-full animate-pulse">Kritis</div>';
                            } elseif ($days_remaining_deep <= 21) {
                                echo '<div class="text-xs font-black uppercase tracking-widest text-yellow-600 bg-yellow-50 px-3 py-1 rounded-full">Siaga</div>';
                            } else {
                                echo '<div class="text-xs font-black uppercase tracking-widest text-green-600 bg-green-50 px-3 py-1 rounded-full">Aman</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Kartu Prediksi Kebutuhan (AI) -->
                    <div class="md:col-span-2 bg-slate-900 rounded-3xl p-8 shadow-xl text-white relative overflow-hidden flex flex-col justify-center">
                        <i class="fa-solid fa-robot absolute -right-4 -bottom-4 text-9xl text-slate-800 dark:text-white opacity-50 pointer-events-none"></i>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-3 h-3 bg-fuchsia-500 rounded-full animate-ping"></span>
                                <h3 class="text-sm font-black text-fuchsia-400 uppercase tracking-widest">Analisis Kebutuhan AI</h3>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-2 uppercase tracking-widest">Estimasi Per Bulan</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-5xl font-black text-white"><?= number_format($monthly_demand, 1) ?></span>
                                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400 dark:text-slate-500"><?= $item_detail['unit'] ?></span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 dark:text-slate-500 mt-2 font-medium">Volume yg diprediksi keluar 30 hari ke depan.</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-2 uppercase tracking-widest">Days of Supply (Sisa)</p>
                                    <div class="flex items-baseline gap-2">
                                        <?php if ($days_remaining_deep === -1): ?>
                                            <span class="text-5xl font-black text-slate-600 dark:text-slate-300">∞</span>
                                        <?php else: ?>
                                            <span class="text-5xl font-black text-white"><?= $days_remaining_deep ?></span>
                                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400 dark:text-slate-500">Hari</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 dark:text-slate-500 mt-2 font-medium">Batas hari sebelum stok benar-benar habis.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Tabel Peringkat Vendor -->
                <div class="bg-white dark:bg-[#1e293b] rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-800">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Peringkat Vendor Langganan</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 font-bold mt-1">Daftar *supplier* yang paling sering dan paling banyak memasok barang ini.</p>
                        </div>
                        <div class="w-10 h-10 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center text-slate-400 dark:text-slate-500">
                            <i class="fa-solid fa-medal"></i>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-2 border-slate-100 dark:border-slate-800 text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-widest font-black">
                                    <th class="pb-3 px-4 w-16 text-center">Rank</th>
                                    <th class="pb-3 px-4">Nama Vendor</th>
                                    <th class="pb-3 px-4 text-center">Frekuensi PO</th>
                                    <th class="pb-3 px-4 text-right">Total Volume Suplai</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php if (!empty($vendor_history)): ?>
                                    <?php $rank = 1; foreach ($vendor_history as $vh): ?>
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 dark:bg-slate-900 transition-colors">
                                        <td class="py-4 px-4 text-center">
                                            <?php if ($rank === 1): ?>
                                                <div class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded-full mx-auto flex items-center justify-center font-black text-xs"><i class="fa-solid fa-crown"></i></div>
                                            <?php elseif ($rank === 2): ?>
                                                <div class="w-6 h-6 bg-slate-200 text-slate-500 dark:text-slate-400 dark:text-slate-500 rounded-full mx-auto flex items-center justify-center font-black text-xs">2</div>
                                            <?php elseif ($rank === 3): ?>
                                                <div class="w-6 h-6 bg-orange-100 text-orange-700 rounded-full mx-auto flex items-center justify-center font-black text-xs">3</div>
                                            <?php else: ?>
                                                <div class="w-6 h-6 text-slate-400 dark:text-slate-500 mx-auto flex items-center justify-center font-black text-xs"><?= $rank ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($vh['nama_perusahaan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="py-4 px-4 text-center">
                                            <span class="inline-block px-3 py-1 bg-slate-100 text-slate-600 dark:text-slate-300 font-black rounded-lg text-xs">
                                                <?= $vh['freq'] ?>x Order
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <span class="font-black text-slate-700 dark:text-slate-200"><?= $vh['total_vol'] ?></span> <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?= $item_detail['unit'] ?></span>
                                        </td>
                                    </tr>
                                    <?php $rank++; endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="py-12 text-center">
                                            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300 text-2xl">
                                                <i class="fa-solid fa-building-circle-xmark"></i>
                                            </div>
                                            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500">Belum Ada Riwayat Vendor</p>
                                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-widest">Barang ini belum pernah dibeli via Purchase Order.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php else: ?>
                <!-- No Selection / Data Not Found -->
                <div class="bg-slate-100 rounded-3xl p-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
                    <i class="fa-solid fa-magnifying-glass text-5xl text-slate-300 mb-4 block"></i>
                    <h3 class="text-lg font-black text-slate-700 dark:text-slate-200 uppercase tracking-tight">Belum Ada Barang Dipilih</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500 font-medium mt-1">Gunakan tombol <strong>Deep Dive</strong> pada tabel di atas untuk membedah data salah satu barang.</p>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </main>
</body>
</html>
