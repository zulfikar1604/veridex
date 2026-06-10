<?php
session_start();
require 'database.php';

// Proteksi role: Manager Finance & Finance
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Finance', 'Finance'])) {
    header("Location: login.php");
    exit;
}

$active = 'laporan_finance';

// Filter Tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$where_clause = "DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date'";

// Data Summary (Hutang AP vs Lunas Paid)
$q_hutang = mysqli_query($conn, "SELECT SUM(total_harga) as total, COUNT(*) as qty FROM purchase_orders WHERE status IN ('Invoice Submitted', 'Payment Approved') AND $where_clause");
$hutang = mysqli_fetch_assoc($q_hutang);
$total_hutang_rp = $hutang['total'] ?? 0;
$total_hutang_qty = $hutang['qty'] ?? 0;

$q_paid = mysqli_query($conn, "SELECT SUM(total_harga) as total, COUNT(*) as qty FROM purchase_orders WHERE status = 'Payment Completed' AND $where_clause");
$paid = mysqli_fetch_assoc($q_paid);
$total_paid_rp = $paid['total'] ?? 0;
$total_paid_qty = $paid['qty'] ?? 0;

$q_3way = mysqli_query($conn, "SELECT COUNT(*) as qty FROM purchase_orders WHERE status = 'Invoice Submitted'");
$total_3way_qty = mysqli_fetch_assoc($q_3way)['qty'] ?? 0;

// Chart Data (6 Bulan Terakhir berdasarkan created_at PO)
$q_chart_hutang = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_label, SUM(total_harga) as total 
    FROM purchase_orders 
    WHERE status IN ('Invoice Submitted', 'Payment Approved') 
      AND created_at >= DATE_SUB('$end_date', INTERVAL 5 MONTH) 
      AND created_at <= '$end_date 23:59:59'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$q_chart_paid = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_label, SUM(total_harga) as total 
    FROM purchase_orders 
    WHERE status = 'Payment Completed' 
      AND created_at >= DATE_SUB('$end_date', INTERVAL 5 MONTH) 
      AND created_at <= '$end_date 23:59:59'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");

$chart_months = [];
$chart_hutang_data = [];
$chart_paid_data = [];

while($row = mysqli_fetch_assoc($q_chart_hutang)) {
    if(!in_array($row['month_label'], $chart_months)) $chart_months[] = $row['month_label'];
    $chart_hutang_data[$row['month_label']] = $row['total'];
}
while($row = mysqli_fetch_assoc($q_chart_paid)) {
    if(!in_array($row['month_label'], $chart_months)) $chart_months[] = $row['month_label'];
    $chart_paid_data[$row['month_label']] = $row['total'];
}

$final_hutang = [];
$final_paid = [];
foreach($chart_months as $m) {
    $final_hutang[] = $chart_hutang_data[$m] ?? 0;
    $final_paid[] = $chart_paid_data[$m] ?? 0;
}
?>
<?php
$page_title = "Finance Analytics";
$page_subtitle = "Laporan Keuangan & Arus Kas";
include 'header_meta.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_finance.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Header & Controls -->
                <div class="bg-indigo-900 p-6 rounded-3xl flex flex-col md:flex-row justify-between items-center gap-6 text-white shadow-xl">
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <button onclick="toggleSidebar()" class="sidebar-toggle-btn text-white" title="Toggle Sidebar"><i class="fa-solid fa-bars text-sm"></i></button>
                        <h3 class="text-xl font-black uppercase tracking-wider"><i class="fa-solid fa-coins mr-2 text-yellow-400"></i> Laporan Finance</h3>
                    </div>
                    
                    <form method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto bg-indigo-950 p-2 rounded-2xl border border-indigo-800">
                        <div class="flex items-center bg-indigo-900 rounded-xl px-3 py-1.5 border border-indigo-700">
                            <span class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mr-2">Dari</span>
                            <input type="date" name="start_date" value="<?= $start_date ?>" class="bg-transparent text-sm font-bold text-white outline-none">
                        </div>
                        <div class="flex items-center bg-indigo-900 rounded-xl px-3 py-1.5 border border-indigo-700">
                            <span class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mr-2">Sampai</span>
                            <input type="date" name="end_date" value="<?= $end_date ?>" class="bg-transparent text-sm font-bold text-white outline-none">
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white p-2 rounded-xl transition-colors shrink-0">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <a href="print_laporan_finance.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="bg-white text-indigo-900 hover:bg-indigo-100 p-2 rounded-xl transition-colors font-black shrink-0 ml-auto md:ml-2 flex items-center gap-2 px-4 text-xs uppercase tracking-widest">
                            <i class="fa-solid fa-print"></i> Print Laporan
                        </a>
                    </form>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Hutang Dagang -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group border-b-4 border-b-orange-500">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Hutang Dagang (AP)</span>
                            <h3 class="text-3xl font-black text-slate-800 dark:text-white">Rp <?= number_format($total_hutang_rp, 0, ',', '.') ?></h3>
                            <p class="text-xs font-bold text-orange-600 mt-2 bg-orange-50 dark:bg-orange-900/30 inline-block px-2 py-1 rounded-lg">Dari <?= $total_hutang_qty ?> Invoice</p>
                        </div>
                    </div>

                    <!-- Pembayaran Sukses -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group border-b-4 border-b-green-500">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-money-check-dollar"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Total Terbayar Lunas</span>
                            <h3 class="text-3xl font-black text-slate-800 dark:text-white">Rp <?= number_format($total_paid_rp, 0, ',', '.') ?></h3>
                            <p class="text-xs font-bold text-green-600 mt-2 bg-green-50 dark:bg-green-900/30 inline-block px-2 py-1 rounded-lg">Dari <?= $total_paid_qty ?> Transaksi</p>
                        </div>
                    </div>

                    <!-- 3-Way Matching Pending -->
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-100 dark:text-slate-800 text-8xl group-hover:scale-110 transition-transform opacity-50"><i class="fa-solid fa-magnifying-glass-dollar"></i></div>
                        <div class="relative z-10">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2">Menunggu 3-Way Matching</span>
                            <h3 class="text-4xl font-black text-slate-800 dark:text-white"><?= number_format($total_3way_qty) ?> <span class="text-sm font-bold text-slate-400">Dokumen</span></h3>
                            <p class="text-xs font-bold text-indigo-500 mt-2 bg-indigo-50 dark:bg-indigo-900/30 inline-block px-2 py-1 rounded-lg">Membutuhkan Verifikasi</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm">
                    <h4 class="font-black text-lg mb-6 uppercase border-b border-slate-100 dark:border-slate-800 pb-4">Tren Arus Kas Keuangan</h4>
                    <div class="h-[400px] w-full">
                        <canvas id="financeChart"></canvas>
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

        const ctx = document.getElementById('financeChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_months) ?>,
                    datasets: [
                        {
                            label: 'Pembayaran Lunas (Rp)',
                            data: <?= json_encode($final_paid) ?>,
                            borderColor: 'rgba(16, 185, 129, 1)', // emerald-500
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Hutang Dagang / AP (Rp)',
                            data: <?= json_encode($final_hutang) ?>,
                            borderColor: 'rgba(249, 115, 22, 1)', // orange-500
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                    }
                                    return label;
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
                                callback: function(value, index, values) {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                                    return 'Rp ' + value;
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
