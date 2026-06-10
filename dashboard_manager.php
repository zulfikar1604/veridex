<?php
session_start();

// Proteksi Halaman: Hanya Manager yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager Procurement') {
    header("Location: login.php");
    exit;
}

require 'database.php';
$active = 'dashboard_manager';

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y');

// --- QUERIES UNTUK SUMMARY CARDS ---
// 1. Total Pengeluaran Selesai
$q_spend = mysqli_query($conn, "SELECT SUM(total_harga) as total_spend FROM purchase_orders WHERE status = 'Payment Completed'");
$total_spend = mysqli_fetch_assoc($q_spend)['total_spend'] ?? 0;

// 2. Outstanding Payables (Hutang Berjalan)
$q_hutang = mysqli_query($conn, "SELECT SUM(total_harga) as total_hutang FROM purchase_orders WHERE status IN ('Invoice Submitted', 'Payment Approved')");
$total_hutang = mysqli_fetch_assoc($q_hutang)['total_hutang'] ?? 0;

// 3. PO Menunggu Approval
$q_pending_po = mysqli_query($conn, "SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'Pending Manager'");
$total_pending_po = mysqli_fetch_assoc($q_pending_po)['total'] ?? 0;

// 4. Total RFQ Terbuka
$q_open_rfq = mysqli_query($conn, "SELECT COUNT(*) as total FROM rfq_requests WHERE status = 'Open'");
$total_open_rfq = mysqli_fetch_assoc($q_open_rfq)['total'] ?? 0;

// 5. Total Vendor Aktif
$q_vendors = mysqli_query($conn, "SELECT COUNT(*) as total FROM vendors WHERE status = 'Approved'");
$total_vendors = mysqli_fetch_assoc($q_vendors)['total'] ?? 0;


// --- QUERIES UNTUK GRAFIK (Chart.js) ---

// A. Distribusi Status PO
$q_po_status = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM purchase_orders GROUP BY status");
$po_status_labels = [];
$po_status_data = [];
while ($row = mysqli_fetch_assoc($q_po_status)) {
    $po_status_labels[] = $row['status'];
    $po_status_data[] = $row['count'];
}

// B. Top 5 Vendor by Nilai PO
$q_top_vendors = mysqli_query($conn, "
    SELECT v.nama_perusahaan, SUM(p.total_harga) as total_value
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status NOT IN ('Rejected')
    GROUP BY v.id
    ORDER BY total_value DESC
    LIMIT 5
");
$top_vendor_labels = [];
$top_vendor_data = [];
while ($row = mysqli_fetch_assoc($q_top_vendors)) {
    $name = (strlen($row['nama_perusahaan']) > 15) ? substr($row['nama_perusahaan'],0,15).'...' : $row['nama_perusahaan'];
    $top_vendor_labels[] = $name;
    $top_vendor_data[] = $row['total_value'];
}

// C. Tren Pengeluaran per Bulan (Line Chart)
$q_trend = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_name, SUM(total_harga) as total_value 
    FROM purchase_orders 
    WHERE status = 'Payment Completed' 
    GROUP BY YEAR(created_at), MONTH(created_at) 
    ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC 
    LIMIT 6
");
$trend_labels = [];
$trend_data = [];
// Reverse agar ascending (bulan lama ke baru)
$temp_trend = [];
while ($row = mysqli_fetch_assoc($q_trend)) {
    $temp_trend[] = $row;
}
$temp_trend = array_reverse($temp_trend);
foreach($temp_trend as $r) {
    $trend_labels[] = $r['month_name'];
    $trend_data[] = $r['total_value'];
}

// D. Top 5 Barang (Doughnut/Bar)
$q_top_items = mysqli_query($conn, "
    SELECT i.nama_barang, SUM(p.total_harga) as total_value 
    FROM purchase_orders p 
    JOIN inventory i ON p.inventory_id = i.id 
    WHERE p.status NOT IN ('Rejected') 
    GROUP BY i.id 
    ORDER BY total_value DESC 
    LIMIT 5
");
$top_item_labels = [];
$top_item_data = [];
while ($row = mysqli_fetch_assoc($q_top_items)) {
    $name = (strlen($row['nama_barang']) > 15) ? substr($row['nama_barang'],0,15).'...' : $row['nama_barang'];
    $top_item_labels[] = $name;
    $top_item_data[] = $row['total_value'];
}


// --- QUERIES UNTUK TABEL DATA ---
// A. PO Pending untuk Quick Action
$query_pending_action = mysqli_query($conn, "
    SELECT p.id, p.po_number, p.total_harga, i.nama_barang, v.nama_perusahaan, p.created_at
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'Pending Manager'
    ORDER BY p.created_at ASC
    LIMIT 5
");

// B. Fulfillment Rate Vendor
$query_kinerja = mysqli_query($conn, "
    SELECT 
        v.id, 
        v.nama_perusahaan, 
        v.spesialisasi, 
        COUNT(p.id) as total_po,
        SUM(p.jumlah_pesan) as total_pesan,
        SUM(p.jumlah_diterima) as total_diterima
    FROM vendors v
    JOIN purchase_orders p ON v.id = p.vendor_id
    GROUP BY v.id
");

// C. Recent Activities Log
$query_recent = mysqli_query($conn, "
    SELECT p.po_number, p.status, v.nama_perusahaan, p.created_at
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    ORDER BY p.id DESC
    LIMIT 6
");

?>
<?php
$page_title = "Command Center";
$page_subtitle = "Strategic Procurement Overview";
include 'header_meta.php';
?>
<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-5 md:p-6 space-y-6 animate-fade-in-up pb-20">
            <!-- GREETING BANNER -->
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-6 rounded-2xl shadow-lg flex justify-between items-center flex-wrap gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-tight">Selamat Datang, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>!</h3>
                    <p class="text-sm text-slate-300 font-medium mt-1">Command Center memberikan pandangan 360° atas arus kas dan performa rantai pasok Veridex.</p>
                </div>
                <div class="bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700 text-xs font-bold text-white backdrop-blur">
                    <i class="fa-solid fa-satellite-dish text-green-400 mr-1.5 animate-pulse"></i> Live Analytics
                </div>
            </div>

            <!-- SUMMARY CARDS (5 Columns) -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Card 1: Total Spend -->
                <div class="bg-white dark:bg-[#1e293b] rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-lg"><i class="fa-solid fa-wallet"></i></div>
                    </div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Pengeluaran Selesai</p>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white">Rp <?= number_format($total_spend / 1000000, 1, ',', '.') ?> <span class="text-xs text-slate-500">Jt</span></h3>
                </div>

                <!-- Card 2: Outstanding Payables -->
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 shadow-lg shadow-orange-500/20 text-white relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[9px] font-black uppercase tracking-widest text-orange-100 mb-1">Hutang Berjalan</p>
                        <h3 class="text-xl font-black mb-1">Rp <?= number_format($total_hutang / 1000000, 1, ',', '.') ?> <span class="text-xs text-orange-200">Jt</span></h3>
                        <p class="text-[9px] text-orange-100 font-medium">Invoice tertagih</p>
                    </div>
                    <i class="fa-solid fa-file-invoice-dollar absolute -bottom-4 -right-2 text-6xl text-white opacity-10 group-hover:scale-110 transition-transform duration-500"></i>
                </div>

                <!-- Card 3: Pending POs -->
                <div class="bg-white dark:bg-[#1e293b] rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-lg"><i class="fa-solid fa-file-signature"></i></div>
                        <?php if($total_pending_po > 0): ?>
                            <span class="flex h-3 w-3 relative">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">PO Menunggu Approval</p>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white"><?= $total_pending_po ?> <span class="text-xs font-bold text-slate-400">Dokumen</span></h3>
                </div>

                <!-- Card 4: Open RFQ -->
                <div class="bg-white dark:bg-[#1e293b] rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg"><i class="fa-solid fa-bullhorn"></i></div>
                    </div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">RFQ Aktif</p>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white"><?= $total_open_rfq ?> <span class="text-xs font-bold text-slate-400">Terbuka</span></h3>
                </div>

                <!-- Card 5: Vendors -->
                <div class="bg-white dark:bg-[#1e293b] rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-lg"><i class="fa-solid fa-building"></i></div>
                    </div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Vendor Mitra</p>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white"><?= $total_vendors ?> <span class="text-xs font-bold text-slate-400">Partner</span></h3>
                </div>
            </div>

            <!-- CHARTS ROW 1: Trend & Top Items -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- LINE CHART: Monthly Trend -->
                <div class="lg:col-span-2 bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 flex flex-col">
                    <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-slate-800 pb-2"><i class="fa-solid fa-chart-area mr-1"></i> Tren Pengeluaran Terselesaikan (6 Bulan)</h3>
                    <div class="flex-1 relative w-full h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- BAR CHART: Top Items -->
                <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 flex flex-col">
                    <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-slate-800 pb-2"><i class="fa-solid fa-box-open mr-1"></i> Top 5 Barang (Total Rp)</h3>
                    <div class="flex-1 relative w-full h-64 flex justify-center">
                        <canvas id="topItemChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- CHARTS ROW 2: Status & Top Vendors -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- PIE CHART: PO STATUS -->
                <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 flex flex-col">
                    <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-slate-800 pb-2"><i class="fa-solid fa-chart-pie mr-1"></i> Sebaran Status Pesanan</h3>
                    <div class="flex-1 relative w-full max-h-64 flex justify-center">
                        <canvas id="poStatusChart"></canvas>
                    </div>
                </div>

                <!-- BAR CHART: TOP 5 VENDORS -->
                <div class="lg:col-span-2 bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 flex flex-col">
                    <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-slate-800 pb-2"><i class="fa-solid fa-trophy text-yellow-500 mr-1"></i> Top 5 Mitra Pengadaan (Total Rp)</h3>
                    <div class="flex-1 relative w-full h-64">
                        <canvas id="topVendorChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- TABLES & ACTION LOG ROW -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- COLUMN 1: QUICK ACTION -->
                <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 flex flex-col">
                    <div class="flex justify-between items-center mb-4 border-b border-slate-100 dark:border-slate-800 pb-2">
                        <h3 class="text-xs font-black text-red-600 uppercase tracking-widest"><i class="fa-solid fa-clock mr-1"></i> Butuh Persetujuan</h3>
                        <a href="approval_procurement.php" class="text-[9px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 px-2 py-1 rounded font-bold transition">Lihat Semua</a>
                    </div>
                    <div class="space-y-3 flex-1">
                        <?php if (mysqli_num_rows($query_pending_action) > 0): ?>
                            <?php while ($po_action = mysqli_fetch_assoc($query_pending_action)): ?>
                            <div class="p-3 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/50 rounded-xl group hover:border-red-300 transition">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-black text-sm text-slate-800 dark:text-white"><?= $po_action['po_number'] ?></span>
                                    <span class="text-[9px] font-bold text-red-500 bg-red-100 dark:bg-red-900/50 px-2 py-0.5 rounded-full">Baru</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-300 font-medium truncate mb-1"><?= htmlspecialchars($po_action['nama_perusahaan']) ?></p>
                                <div class="flex justify-between items-center flex-wrap gap-4 mt-2">
                                    <span class="text-xs font-black text-slate-800 dark:text-white">Rp <?= number_format($po_action['total_harga'],0,',','.') ?></span>
                                    <a href="approval_procurement.php" class="text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 px-3 py-1 rounded-full shadow-sm">Review</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-10 opacity-50 flex flex-col items-center justify-center h-full">
                                <i class="fa-solid fa-check-circle text-4xl text-green-500 mb-2"></i>
                                <p class="text-xs font-bold uppercase text-slate-500">Semua Clear</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- COLUMN 2: RECENT ACTIVITIES -->
                <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 flex flex-col">
                    <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-slate-800 pb-2"><i class="fa-solid fa-history mr-1"></i> Riwayat Transaksi PO Baru</h3>
                    <div class="relative border-l border-slate-200 dark:border-slate-700 ml-3 space-y-4 pb-4 flex-1">
                        <?php while ($log = mysqli_fetch_assoc($query_recent)): 
                            $status_color = 'bg-slate-500';
                            if (strpos($log['status'], 'Pending') !== false) $status_color = 'bg-red-500';
                            if (strpos($log['status'], 'Approved') !== false || strpos($log['status'], 'Confirmed') !== false) $status_color = 'bg-blue-500';
                            if (strpos($log['status'], 'Completed') !== false || strpos($log['status'], 'Received') !== false) $status_color = 'bg-green-500';
                        ?>
                        <div class="relative pl-6 group">
                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full <?= $status_color ?> ring-4 ring-white dark:ring-[#1e293b] group-hover:scale-150 transition-transform"></span>
                            <p class="text-[10px] text-slate-400 font-bold"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></p>
                            <p class="text-xs font-black text-slate-800 dark:text-white mt-0.5"><?= $log['po_number'] ?></p>
                            <p class="text-[10px] text-slate-600 dark:text-slate-300 truncate w-full">Mitra: <?= htmlspecialchars($log['nama_perusahaan']) ?></p>
                            <p class="text-[9px] font-bold text-slate-500 uppercase mt-1"><?= $log['status'] ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- COLUMN 3: FULFILLMENT RATE -->
                <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
                        <h3 class="font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest text-xs"><i class="fa-solid fa-chart-line mr-1"></i> Peringkat Pemenuhan Vendor</h3>
                    </div>
                    
                    <div class="overflow-y-auto flex-1 max-h-[350px]">
<table class="w-full text-left whitespace-nowrap">
                            <thead class="sticky top-0 bg-white dark:bg-[#1e293b] shadow-sm">
                                <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">
                                    <th class="py-3 px-4">Vendor</th>
                                    <th class="py-3 px-4 text-center">Fulfillment</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php while($row = mysqli_fetch_assoc($query_kinerja)): 
                                    $pesan = $row['total_pesan'] ?? 0;
                                    $terima = $row['total_diterima'] ?? 0;
                                    $persen = ($pesan > 0) ? round(($terima / $pesan) * 100) : 0;
                                    $color = ($persen >= 90) ? 'bg-green-500' : (($persen >= 70) ? 'bg-yellow-500' : 'bg-red-500');
                                ?>
                                <tr class="border-b border-slate-50 hover:bg-slate-50 dark:bg-slate-900 transition">
                                    <td class="py-3 px-4">
                                        <div class="font-black text-xs text-slate-800 dark:text-white truncate w-32"><?= htmlspecialchars($row['nama_perusahaan']) ?></div>
                                        <div class="text-[9px] text-slate-400 font-bold uppercase truncate w-32"><?= htmlspecialchars($row['spesialisasi']) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex justify-between items-end mb-1">
                                            <span class="text-xs font-black text-slate-700 dark:text-slate-200"><?= $persen ?>%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div class="<?= $color ?> h-full rounded-full transition-all duration-1000" style="width: <?= $persen ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- SCRIPTS UNTUK CHART.JS -->
    <script>
        // Set default font for all charts
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#64748b'; // slate-500

        // 1. Line Chart: Tren Pengeluaran
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?= json_encode($trend_labels) ?>,
                datasets: [{
                    label: 'Pengeluaran (Rp)',
                    data: <?= json_encode($trend_data) ?>,
                    borderColor: '#10b981', // green-500
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4, // smooth curve
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4], color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: {
                            font: { size: 10 },
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });

        // 2. Bar Chart: Top Items
        const ctxItem = document.getElementById('topItemChart').getContext('2d');
        new Chart(ctxItem, {
            type: 'bar',
            data: {
                labels: <?= json_encode($top_item_labels) ?>,
                datasets: [{
                    label: 'Total Value',
                    data: <?= json_encode($top_item_data) ?>,
                    backgroundColor: '#3b82f6', // blue-500
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y', // horizontal bar
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.x);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4], color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: {
                            font: { size: 9 },
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Jt';
                                return value;
                            }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });

        // 3. Pie Chart: Status PO
        const ctxPie = document.getElementById('poStatusChart').getContext('2d');
        const poStatusLabels = <?= json_encode($po_status_labels) ?>;
        const colorMap = {
            'Pending Manager': '#ef4444',
            'Approved': '#eab308',
            'Confirmed by Vendor': '#3b82f6',
            'Shipped': '#a855f7',
            'Goods Received': '#14b8a6',
            'Invoice Submitted': '#6366f1',
            'Payment Approved': '#f59e0b',
            'Payment Completed': '#10b981',
            'Rejected': '#64748b'
        };
        const poColors = poStatusLabels.map(label => colorMap[label] || '#cbd5e1');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: poStatusLabels,
                datasets: [{
                    data: <?= json_encode($po_status_data) ?>,
                    backgroundColor: poColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { usePointStyle: true, boxWidth: 8, font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });

        // 4. Bar Chart: Top 5 Vendors
        const ctxVendor = document.getElementById('topVendorChart').getContext('2d');
        new Chart(ctxVendor, {
            type: 'bar',
            data: {
                labels: <?= json_encode($top_vendor_labels) ?>,
                datasets: [{
                    label: 'Total Pembelian',
                    data: <?= json_encode($top_vendor_data) ?>,
                    backgroundColor: '#0f172a', // slate-900
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4], color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: {
                            font: { size: 10 },
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });
    </script>

</body>
</html>
