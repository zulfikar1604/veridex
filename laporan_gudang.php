<?php
session_start();
require 'database.php';

// Proteksi role: Gudang dan Manager Gudang (atau Procurement jika diperlukan, tapi fokus Gudang)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Gudang', 'Staf Gudang', 'Gudang'])) {
    header("Location: login.php");
    exit;
}

$active = 'laporan_gudang';

// Filter Tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$where_inbound = "DATE(tanggal_masuk) >= '$start_date' AND DATE(tanggal_masuk) <= '$end_date'";
$where_outbound = "DATE(tanggal_keluar) >= '$start_date' AND DATE(tanggal_keluar) <= '$end_date'";

// Data Summary
$q_inbound = mysqli_query($conn, "SELECT SUM(jumlah_masuk) as total FROM inbound_history WHERE $where_inbound");
$total_inbound = mysqli_fetch_assoc($q_inbound)['total'] ?? 0;

$q_outbound = mysqli_query($conn, "SELECT SUM(jumlah_keluar) as total FROM outbound_history WHERE $where_outbound");
$total_outbound = mysqli_fetch_assoc($q_outbound)['total'] ?? 0;

$q_kritis = mysqli_query($conn, "SELECT COUNT(id) as total FROM inventory WHERE status_label = 'Kritis'");
$total_kritis = mysqli_fetch_assoc($q_kritis)['total'] ?? 0;

// Chart Data (6 Bulan Terakhir)
$q_chart_inbound = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal_masuk, '%b %Y') as month_label, SUM(jumlah_masuk) as total 
    FROM inbound_history 
    WHERE tanggal_masuk >= DATE_SUB('$end_date', INTERVAL 5 MONTH) AND tanggal_masuk <= '$end_date 23:59:59'
    GROUP BY DATE_FORMAT(tanggal_masuk, '%Y-%m')
    ORDER BY tanggal_masuk ASC
");
$q_chart_outbound = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal_keluar, '%b %Y') as month_label, SUM(jumlah_keluar) as total 
    FROM outbound_history 
    WHERE tanggal_keluar >= DATE_SUB('$end_date', INTERVAL 5 MONTH) AND tanggal_keluar <= '$end_date 23:59:59'
    GROUP BY DATE_FORMAT(tanggal_keluar, '%Y-%m')
    ORDER BY tanggal_keluar ASC
");

// Gabungkan data bulan (bisa disempurnakan logikanya agar sinkron)
$chart_months = [];
$chart_inbound_data = [];
$chart_outbound_data = [];

while($row = mysqli_fetch_assoc($q_chart_inbound)) {
    if(!in_array($row['month_label'], $chart_months)) $chart_months[] = $row['month_label'];
    $chart_inbound_data[$row['month_label']] = $row['total'];
}
while($row = mysqli_fetch_assoc($q_chart_outbound)) {
    if(!in_array($row['month_label'], $chart_months)) $chart_months[] = $row['month_label'];
    $chart_outbound_data[$row['month_label']] = $row['total'];
}

$final_inbound = [];
$final_outbound = [];
foreach($chart_months as $m) {
    $final_inbound[] = $chart_inbound_data[$m] ?? 0;
    $final_outbound[] = $chart_outbound_data[$m] ?? 0;
}
?>
<?php
$page_title = "Warehouse Analytics";
$page_subtitle = "Laporan Inbound & Outbound";
include 'header_meta.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_gudang.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Header & Controls -->
                <div class="bg-slate-900 p-6 rounded-3xl flex flex-col md:flex-row justify-between items-center gap-6 text-white shadow-xl">
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <button onclick="toggleSidebar()" class="sidebar-toggle-btn text-white" title="Toggle Sidebar"><i class="fa-solid fa-bars text-sm"></i></button>
                        <h3 class="text-xl font-black uppercase tracking-wider"><i class="fa-solid fa-warehouse mr-2 text-indigo-500"></i> Laporan Gudang</h3>
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
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded-xl transition-colors shrink-0">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <a href="print_laporan_gudang.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="bg-white text-slate-900 hover:bg-slate-200 p-2 rounded-xl transition-colors font-black shrink-0 ml-auto md:ml-2 flex items-center gap-2 px-4 text-xs uppercase tracking-widest">
                            <i class="fa-solid fa-print"></i> Print
                        </a>
                    </form>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-boxes-packing"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Total Inbound (Masuk)</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_inbound) ?> <span class="text-sm font-bold text-slate-400">Unit</span></h3>
                            <p class="text-xs font-bold text-green-500 mt-2 bg-green-50 dark:bg-green-900/30 inline-block px-2 py-1 rounded-lg">Barang Diterima</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Total Outbound (Keluar)</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_outbound) ?> <span class="text-sm font-bold text-slate-400">Unit</span></h3>
                            <p class="text-xs font-bold text-orange-500 mt-2 bg-orange-50 dark:bg-orange-900/30 inline-block px-2 py-1 rounded-lg">Barang Didistribusikan</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group border-b-4 border-b-red-500">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Barang Kritis (ROP)</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_kritis) ?> <span class="text-sm font-bold text-slate-400">SKU</span></h3>
                            <p class="text-xs font-bold text-red-500 mt-2 bg-red-50 dark:bg-red-900/30 inline-block px-2 py-1 rounded-lg">Butuh PR Baru</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm">
                    <h4 class="font-black text-lg mb-6 uppercase border-b border-slate-100 dark:border-slate-800 pb-4">Tren Volume Inbound vs Outbound</h4>
                    <div class="h-[400px] w-full">
                        <canvas id="flowChart"></canvas>
                    </div>
                </div>

            </div>
            <div class="h-10"></div>
        </main>
    </div>

    <script>
        const isDarkMode = document.documentElement.classList.contains('dark');
        Chart.defaults.color = isDarkMode ? '#94a3b8' : '#64748b';
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.weight = 'bold';

        const ctx = document.getElementById('flowChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_months) ?>,
                    datasets: [
                        {
                            label: 'Inbound (Masuk)',
                            data: <?= json_encode($final_inbound) ?>,
                            backgroundColor: 'rgba(16, 185, 129, 0.8)', // emerald-500
                            borderRadius: 6
                        },
                        {
                            label: 'Outbound (Keluar)',
                            data: <?= json_encode($final_outbound) ?>,
                            backgroundColor: 'rgba(249, 115, 22, 0.8)', // orange-500
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: isDarkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
                                drawBorder: false,
                            }
                        },
                        x: {
                            grid: { display: false, drawBorder: false }
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
