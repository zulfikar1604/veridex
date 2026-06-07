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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi ROP | Veridex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } 
    <style>
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
    $page_title = "Stok ROP";
    $page_subtitle = "Peringatan Safety Stock & Reorder Point";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active_menu = 'rop'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <?php
                $query_kritis = mysqli_query($conn, "SELECT * FROM inventory WHERE status_label = 'Kritis' ORDER BY stok_saat_ini ASC");
                $total_kritis = mysqli_num_rows($query_kritis);
            ?>

            <div class="space-y-4 max-w-4xl mx-auto">

                <!-- Header ringkasan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Barang Butuh Pengadaan</span>
                        <p class="text-3xl font-black text-red-600 mt-1">
                            <?= str_pad($total_kritis, 2, '0', STR_PAD_LEFT) ?>
                            <span class="text-sm font-medium text-slate-400">SKU</span>
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
                    <div class="bg-white p-10 rounded-2xl border shadow-sm text-center">
                        <i class="fa-solid fa-check-double text-5xl text-green-200 mb-4 block"></i>
                        <p class="text-slate-500 font-bold">Semua stok dalam kondisi aman.</p>
                        <p class="text-slate-400 text-sm mt-1">Tidak ada barang yang perlu pengadaan saat ini.</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>


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
