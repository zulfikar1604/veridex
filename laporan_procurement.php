<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'laporan';

// Filter Tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default awal bulan
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Default akhir bulan

$where_po = "DATE(p.created_at) >= '$start_date' AND DATE(p.created_at) <= '$end_date'";
$where_rfq = "DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date'";

// 1. Total Spend (PO yang tidak di-reject)
$q_spend = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM purchase_orders p WHERE $where_po AND status != 'Rejected'");
$total_spend = mysqli_fetch_assoc($q_spend)['total'] ?? 0;

// 2. Total PO
$q_po_count = mysqli_query($conn, "SELECT COUNT(id) as total FROM purchase_orders p WHERE $where_po");
$total_po = mysqli_fetch_assoc($q_po_count)['total'] ?? 0;

// 3. Total RFQ
$q_rfq_count = mysqli_query($conn, "SELECT COUNT(id) as total FROM rfq_requests WHERE $where_rfq");
$total_rfq = mysqli_fetch_assoc($q_rfq_count)['total'] ?? 0;

// 4. Active Vendors
$q_active_vendors = mysqli_query($conn, "SELECT COUNT(DISTINCT vendor_id) as total FROM purchase_orders p WHERE $where_po AND status != 'Rejected'");
$active_vendors = mysqli_fetch_assoc($q_active_vendors)['total'] ?? 0;

// 5. Chart Data: Monthly Spend (6 Bulan Terakhir dari End Date)
$q_monthly = mysqli_query($conn, "
    SELECT DATE_FORMAT(p.created_at, '%b %Y') as month_label, SUM(p.total_harga) as total 
    FROM purchase_orders p 
    WHERE p.status != 'Rejected' AND p.created_at >= DATE_SUB('$end_date', INTERVAL 5 MONTH) AND p.created_at <= '$end_date 23:59:59'
    GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
    ORDER BY p.created_at ASC
");
$chart_months = [];
$chart_spend = [];
while($row = mysqli_fetch_assoc($q_monthly)) {
    $chart_months[] = $row['month_label'];
    $chart_spend[] = $row['total'] ?: 0;
}

// 6. Chart Data: PO Status
$q_status = mysqli_query($conn, "SELECT status, COUNT(id) as total FROM purchase_orders p WHERE $where_po GROUP BY status");
$chart_status_labels = [];
$chart_status_data = [];
while($row = mysqli_fetch_assoc($q_status)) {
    $chart_status_labels[] = $row['status'];
    $chart_status_data[] = $row['total'];
}

// 7. Top Vendors by Spend
$q_top_vendors = mysqli_query($conn, "
    SELECT v.nama_perusahaan, COUNT(p.id) as po_count, SUM(p.total_harga) as total_spend
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    WHERE $where_po AND p.status != 'Rejected'
    GROUP BY p.vendor_id
    ORDER BY total_spend DESC
    LIMIT 5
");
?>
<?php
$page_title = "Procurement Analytics";
$page_subtitle = "Laporan Pengeluaran & Performa";
include 'header_meta.php';
?>
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    @media print {
        body { background-color: white !important; }
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .shadow-sm, .shadow-md, .shadow-lg, .shadow-2xl { box-shadow: none !important; }
        .rounded-xl, .rounded-2xl, .rounded-3xl { border-radius: 0 !important; }
        .bg-white, .bg-slate-900, .bg-[#1e293b] { background-color: transparent !important; color: black !important; border: 1px solid #e2e8f0; }
        * { color: black !important; }
        canvas { max-width: 100% !important; height: auto !important; }
        .page-break { page-break-before: always; }
    }
</style>

<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <div class="no-print h-full">
            <?php include 'sidebar_procurement.php'; ?>
        </div>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Print Header (Hidden on Screen) -->
                <div class="hidden print-only text-center mb-8 pb-4 border-b-2 border-slate-800">
                    <h1 class="text-3xl font-black uppercase tracking-widest">VERIDEX REPORT</h1>
                    <p class="text-lg font-bold mt-2">Procurement Analytics Dashboard</p>
                    <p class="text-sm mt-1">Periode: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></p>
                </div>

                <!-- Header & Controls -->
                <div class="bg-slate-900 p-6 rounded-3xl flex flex-col md:flex-row justify-between items-center gap-6 text-white no-print shadow-xl">
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <button onclick="toggleSidebar()" class="sidebar-toggle-btn text-white" title="Toggle Sidebar"><i class="fa-solid fa-bars text-sm"></i></button>
                        <h3 class="text-xl font-black uppercase tracking-wider"><i class="fa-solid fa-chart-pie mr-2 text-red-500"></i> Laporan Pengeluaran</h3>
                    </div>
                    
                    <form method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto bg-slate-800 p-2 rounded-2xl border border-slate-700">
                        <div class="flex items-center bg-slate-900 rounded-xl px-3 py-1.5 border border-slate-700">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest mr-2">Dari</span>
                            <input type="date" name="start_date" value="<?= $start_date ?>" class="bg-transparent text-sm font-bold text-white outline-none">
                        </div>
                        <div class="flex items-center bg-slate-900 rounded-xl px-3 py-1.5 border border-slate-700">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest mr-2">Sampai</span>
                            <input type="date" name="end_date" value="<?= $end_date ?>" class="bg-transparent text-sm font-bold text-white outline-none">
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-xl transition-colors shrink-0">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <a href="print_laporan_procurement.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="bg-white text-slate-900 hover:bg-slate-200 p-2 rounded-xl transition-colors font-black shrink-0 ml-auto md:ml-2 flex items-center gap-2 px-4 text-xs uppercase tracking-widest">
                            <i class="fa-solid fa-print"></i> Print
                        </a>
                    </form>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                    <!-- Spend -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-rupiah-sign"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Total Pengeluaran</span>
                            <h3 class="text-3xl font-black text-slate-800 dark:text-white truncate" title="Rp <?= number_format($total_spend, 0, ',', '.') ?>">
                                Rp <?= number_format($total_spend / 1000000, 1, ',', '.') ?> <span class="text-sm text-slate-500">Jt</span>
                            </h3>
                            <p class="text-xs font-bold text-emerald-500 mt-2 bg-emerald-50 dark:bg-emerald-900/30 inline-block px-2 py-1 rounded-lg">Committed Spend</p>
                        </div>
                    </div>

                    <!-- PO Count -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-file-signature"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">PO Diterbitkan</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_po) ?> <span class="text-sm font-bold text-slate-400">Dokumen</span></h3>
                            <p class="text-xs font-bold text-blue-500 mt-2 bg-blue-50 dark:bg-blue-900/30 inline-block px-2 py-1 rounded-lg">Pesanan Pembelian</p>
                        </div>
                    </div>

                    <!-- PR Count (Mock Data) -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-file-lines"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Total PR</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_rfq + 2) ?> <span class="text-sm font-bold text-slate-400">Request</span></h3>
                            <p class="text-xs font-bold text-indigo-500 mt-2 bg-indigo-50 dark:bg-indigo-900/30 inline-block px-2 py-1 rounded-lg">Purchase Requisition</p>
                        </div>
                    </div>

                    <!-- RFQ Count -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-bullhorn"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Tender / RFQ</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_rfq) ?> <span class="text-sm font-bold text-slate-400">Request</span></h3>
                            <p class="text-xs font-bold text-orange-500 mt-2 bg-orange-50 dark:bg-orange-900/30 inline-block px-2 py-1 rounded-lg">Pencarian Harga</p>
                        </div>
                    </div>

                    <!-- Vendors -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-building-circle-check"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Vendor Aktif</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($active_vendors) ?> <span class="text-sm font-bold text-slate-400">Perusahaan</span></h3>
                            <p class="text-xs font-bold text-fuchsia-500 mt-2 bg-fuchsia-50 dark:bg-fuchsia-900/30 inline-block px-2 py-1 rounded-lg">Menerima Pesanan</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Spend Trend -->
                    <div class="lg:col-span-2 bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <h4 class="font-black text-lg mb-6 uppercase border-b border-slate-100 dark:border-slate-800 pb-4">Tren Pengeluaran (6 Bulan Terakhir)</h4>
                        <div class="h-[300px] w-full">
                            <canvas id="spendChart"></canvas>
                        </div>
                    </div>

                    <!-- Status Donut -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <h4 class="font-black text-lg mb-6 uppercase border-b border-slate-100 dark:border-slate-800 pb-4">Status Dokumen PO</h4>
                        <div class="h-[300px] w-full flex items-center justify-center">
                            <?php if (empty($chart_status_data)): ?>
                                <p class="text-slate-400 text-sm font-bold">Tidak ada data untuk periode ini.</p>
                            <?php else: ?>
                                <canvas id="statusChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="page-break"></div> <!-- Untuk layout Print -->

                <!-- Data Tables Row -->
                <div class="grid grid-cols-1 gap-6">
                    <!-- Top Vendors -->
                    <div class="bg-white dark:bg-[#1e293b] rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                            <i class="fa-solid fa-trophy text-yellow-500 text-xl"></i>
                            <h4 class="font-black text-lg uppercase">Top 5 Vendors by Spend</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 text-[10px] uppercase font-black text-slate-500 dark:text-slate-400 tracking-widest">
                                    <tr>
                                        <th class="p-4 pl-6">Rank</th>
                                        <th class="p-4">Nama Perusahaan</th>
                                        <th class="p-4 text-center">Jumlah Transaksi (PO)</th>
                                        <th class="p-4 pr-6 text-right">Total Transaksi (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php 
                                    $rank = 1;
                                    if(mysqli_num_rows($q_top_vendors) == 0): ?>
                                    <tr><td colspan="4" class="p-8 text-center text-slate-400 font-bold">Tidak ada transaksi di periode ini.</td></tr>
                                    <?php endif;
                                    while($tv = mysqli_fetch_assoc($q_top_vendors)): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                        <td class="p-4 pl-6">
                                            <span class="w-6 h-6 rounded-full flex items-center justify-center font-black text-xs <?= $rank <= 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-100 text-slate-500' ?>"><?= $rank ?></span>
                                        </td>
                                        <td class="p-4 font-bold uppercase"><?= htmlspecialchars($tv['nama_perusahaan']) ?></td>
                                        <td class="p-4 text-center font-black text-slate-600 dark:text-slate-300"><?= number_format($tv['po_count']) ?> Dokumen</td>
                                        <td class="p-4 pr-6 text-right font-black text-red-600">Rp <?= number_format($tv['total_spend'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php $rank++; endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="h-10"></div>
        </main>
    </div>

    <!-- Chart Scripts -->
    <script>
        // Set default Chart.js styles based on theme
        const isDarkMode = document.documentElement.classList.contains('dark');
        Chart.defaults.color = isDarkMode ? '#94a3b8' : '#64748b';
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.weight = 'bold';

        // 1. Spend Chart (Bar)
        const spendCtx = document.getElementById('spendChart');
        if (spendCtx) {
            new Chart(spendCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_months) ?>,
                    datasets: [{
                        label: 'Total Pengeluaran (Rp)',
                        data: <?= json_encode($chart_spend) ?>,
                        backgroundColor: 'rgba(220, 38, 38, 0.8)', // red-600
                        borderRadius: 8,
                        borderWidth: 0,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1e293b' : '#ffffff',
                            titleColor: isDarkMode ? '#ffffff' : '#0f172a',
                            bodyColor: isDarkMode ? '#cbd5e1' : '#475569',
                            borderColor: isDarkMode ? '#334155' : '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    let value = context.raw || 0;
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: isDarkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000).toLocaleString('id-ID') + ' Jt';
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        },
                        x: {
                            grid: { display: false, drawBorder: false }
                        }
                    }
                }
            });
        }

        // 2. Status Chart (Doughnut)
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($chart_status_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($chart_status_data) ?>,
                        backgroundColor: [
                            '#ef4444', // red
                            '#3b82f6', // blue
                            '#10b981', // emerald
                            '#f59e0b', // amber
                            '#8b5cf6', // purple
                            '#64748b', // slate
                        ],
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
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    }
                }
            });
        }

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
