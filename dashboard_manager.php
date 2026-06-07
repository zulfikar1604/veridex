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
// 1. Total Pengeluaran (PO yang Payment Completed)
$q_spend = mysqli_query($conn, "SELECT SUM(total_harga) as total_spend FROM purchase_orders WHERE status = 'Payment Completed'");
$total_spend = mysqli_fetch_assoc($q_spend)['total_spend'] ?? 0;

// 2. PO Menunggu Approval
$q_pending_po = mysqli_query($conn, "SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'Pending Manager'");
$total_pending_po = mysqli_fetch_assoc($q_pending_po)['total'] ?? 0;

// 3. Total RFQ Terbuka
$q_open_rfq = mysqli_query($conn, "SELECT COUNT(*) as total FROM rfq_requests WHERE status = 'Open'");
$total_open_rfq = mysqli_fetch_assoc($q_open_rfq)['total'] ?? 0;

// 4. Total Vendor Aktif
$q_vendors = mysqli_query($conn, "SELECT COUNT(*) as total FROM vendors WHERE status = 'Approved'");
$total_vendors = mysqli_fetch_assoc($q_vendors)['total'] ?? 0;


// --- QUERIES UNTUK GRAFIK (Chart.js) ---
// Distribusi Status PO
$q_po_status = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM purchase_orders GROUP BY status");
$po_status_labels = [];
$po_status_data = [];
while ($row = mysqli_fetch_assoc($q_po_status)) {
    $po_status_labels[] = $row['status'];
    $po_status_data[] = $row['count'];
}

// Top 5 Vendor by Nilai PO
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
    // Persingkat nama vendor agar muat di chart
    $name = (strlen($row['nama_perusahaan']) > 15) ? substr($row['nama_perusahaan'],0,15).'...' : $row['nama_perusahaan'];
    $top_vendor_labels[] = $name;
    $top_vendor_data[] = $row['total_value'];
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

// B. Fulfillment Rate Vendor (Dari kinerja_vendor lama)
$query_kinerja = mysqli_query($conn, "
    SELECT 
        v.id, 
        v.nama_perusahaan, 
        v.spesialisasi, 
        v.status as status_vendor,
        COUNT(p.id) as total_po,
        SUM(p.jumlah_pesan) as total_pesan,
        SUM(p.jumlah_diterima) as total_diterima
    FROM vendors v
    LEFT JOIN purchase_orders p ON v.id = p.vendor_id
    GROUP BY v.id
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
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
    $page_title = "Command Center";
    $page_subtitle = "Strategic Procurement Overview";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            <!-- GREETING BANNER -->
            <div class="bg-gradient-to-r from-red-500/10 to-transparent p-6 rounded-3xl border border-red-500/20 flex justify-between items-center flex-wrap gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Selamat Datang Kembali, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>!</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Sistem siap memantau performa pengadaan strategis dan status persetujuan hari ini.</p>
                </div>
                <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100 text-xs font-bold text-slate-600">
                    <i class="fa-solid fa-clock text-red-500 mr-1.5 animate-pulse"></i> Monitoring Real-time
                </div>
            </div>
            <!-- SUMMARY CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-6 shadow-lg shadow-slate-900/20 text-white relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Pengeluaran Selesai</p>
                        <h3 class="text-2xl font-black mb-1">Rp <span class="text-3xl"><?= number_format($total_spend, 0, ',', '.') ?></span></h3>
                        <p class="text-xs text-slate-300 font-medium">Nilai PO yang sudah dibayar</p>
                    </div>
                    <i class="fa-solid fa-rupiah-sign absolute -bottom-4 -right-2 text-7xl text-white opacity-5 group-hover:scale-110 transition-transform duration-500"></i>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-lg"><i class="fa-solid fa-file-signature"></i></div>
                        <?php if($total_pending_po > 0): ?>
                            <span class="flex h-3 w-3 relative">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">PO Butuh Persetujuan</p>
                    <h3 class="text-4xl font-black text-slate-800"><?= $total_pending_po ?> <span class="text-sm font-bold text-slate-400">Dokumen</span></h3>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg"><i class="fa-solid fa-bullhorn"></i></div>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">RFQ Aktif (Terbuka)</p>
                    <h3 class="text-4xl font-black text-slate-800"><?= $total_open_rfq ?> <span class="text-sm font-bold text-slate-400">Permintaan</span></h3>
                </div>
                <!-- Card 4 -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-lg"><i class="fa-solid fa-building"></i></div>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Vendor Mitra</p>
                    <h3 class="text-4xl font-black text-slate-800"><?= $total_vendors ?> <span class="text-sm font-bold text-slate-400">Perusahaan</span></h3>
                </div>
            </div>

            <!-- CHARTS & ACTION TABLES SECTION -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- PIE CHART: PO STATUS -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Status Pesanan (PO)</h3>
                    <div class="flex-1 relative w-full max-h-64 flex justify-center">
                        <canvas id="poStatusChart"></canvas>
                    </div>
                </div>

                <!-- BAR CHART: TOP 5 VENDORS -->
                <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Top 5 Mitra Pengadaan (Nilai Pembelian Rp)</h3>
                    <div class="flex-1 relative w-full h-64">
                        <canvas id="topVendorChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- QUICK ACTION: PENDING PO -->
                <div class="lg:col-span-1 bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                    <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-2">
                        <h3 class="text-xs font-black text-red-600 uppercase tracking-widest"><i class="fa-solid fa-clock mr-1"></i> Butuh Persetujuan Segera</h3>
                        <a href="approval_procurement.php" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-1 rounded font-bold transition">Lihat Semua</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (mysqli_num_rows($query_pending_action) > 0): ?>
                            <?php while ($po_action = mysqli_fetch_assoc($query_pending_action)): ?>
                            <div class="p-3 bg-red-50 border border-red-100 rounded-xl group hover:border-red-300 transition">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-black text-sm text-slate-800"><?= $po_action['po_number'] ?></span>
                                    <span class="text-[9px] font-bold text-red-500 bg-red-100 px-2 py-0.5 rounded-full">Baru</span>
                                </div>
                                <p class="text-xs text-slate-600 font-medium truncate mb-1"><?= htmlspecialchars($po_action['nama_perusahaan']) ?></p>
                                <div class="flex justify-between items-center flex-wrap gap-4">
                                    <span class="text-xs font-black text-slate-800">Rp <?= number_format($po_action['total_harga'],0,',','.') ?></span>
                                    <a href="approval_procurement.php" class="text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 px-2 py-1 rounded">Approve &rarr;</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-10 opacity-50">
                                <i class="fa-solid fa-check-circle text-4xl text-green-500 mb-2"></i>
                                <p class="text-xs font-bold uppercase text-slate-500">Semua Clear</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TABEL KINERJA VENDOR (Fulfillment Rate) -->
                <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center flex-wrap gap-4">
                        <h3 class="font-black text-slate-700 uppercase tracking-widest text-xs"><i class="fa-solid fa-chart-line mr-1"></i> Peringkat Pemenuhan (Fulfillment Rate)</h3>
                    </div>
                    
                    <div class="overflow-x-auto flex-1">
                        <div class="overflow-x-auto w-full">
<table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">
                                    <th class="py-3 px-6">Vendor</th>
                                    <th class="py-3 px-6 text-center">Total PO</th>
                                    <th class="py-3 px-6 w-1/2">Tingkat Pemenuhan</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php while($row = mysqli_fetch_assoc($query_kinerja)): 
                                    $pesan = $row['total_pesan'] ?? 0;
                                    $terima = $row['total_diterima'] ?? 0;
                                    $persen = ($pesan > 0) ? round(($terima / $pesan) * 100) : 0;
                                    $color = ($persen >= 90) ? 'bg-green-500' : (($persen >= 70) ? 'bg-yellow-500' : 'bg-red-500');
                                ?>
                                <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                    <td class="py-4 px-6">
                                        <div class="font-black text-slate-800"><?= htmlspecialchars($row['nama_perusahaan']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase"><?= htmlspecialchars($row['spesialisasi']) ?></div>
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold text-slate-600">
                                        <?= $row['total_po'] ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex justify-between mb-1 items-end">
                                            <span class="text-xs font-black text-slate-700"><?= $persen ?>%</span>
                                            <span class="text-[9px] font-bold text-slate-400"><?= number_format($terima) ?>/<?= number_format($pesan) ?> Unit</span>
                                        </div>
                                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
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
            </div>
        </main>
    </div>

    <!-- SCRIPTS UNTUK CHART.JS -->
    <script>
        // Pie Chart: Status PO
        const ctxPie = document.getElementById('poStatusChart').getContext('2d');
        const poStatusLabels = <?= json_encode($po_status_labels) ?>;
        const poStatusData = <?= json_encode($po_status_data) ?>;
        
        // Warna dinamis sesuai status
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
                    data: poStatusData,
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
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: { family: 'Inter', size: 10, weight: 'bold' }
                        }
                    }
                }
            }
        });

        // Bar Chart: Top 5 Vendors
        const ctxBar = document.getElementById('topVendorChart').getContext('2d');
        const topVendorLabels = <?= json_encode($top_vendor_labels) ?>;
        const topVendorData = <?= json_encode($top_vendor_data) ?>;

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: topVendorLabels,
                datasets: [{
                    label: 'Total Pembelian (Rp)',
                    data: topVendorData,
                    backgroundColor: '#0f172a', // slate-900
                    borderRadius: 6,
                    borderSkipped: false
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
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4] },
                        ticks: {
                            font: { family: 'Inter', size: 10 },
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                                if (value >= 1000) return 'Rp ' + (value / 1000) + ' Rb';
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 10, weight: 'bold' } }
                    }
                }
            }
        });
    </script>

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
