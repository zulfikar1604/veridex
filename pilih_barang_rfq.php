<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'buat_rfq';

// Fetch inventory yang hanya mencapai Reorder Point (stok kritis)
$query = mysqli_query($conn, "SELECT * FROM inventory WHERE stok_saat_ini <= stok_kritis ORDER BY nama_barang ASC");
?>
<?php
$page_title = "Buat RFQ Baru";
$page_subtitle = "Pilih Barang dari Master Inventory";
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">
    <?php include 'navbar.php'; ?>
    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Header -->
                <div class="bg-slate-900 p-6 rounded-3xl flex justify-between items-center flex-wrap gap-4 text-white">
                    <div class="flex items-center gap-3">
                        <button onclick="toggleSidebar()" class="sidebar-toggle-btn" title="Toggle Sidebar"><i class="fa-solid fa-bars text-sm"></i></button>
                        <h3 class="text-xl font-black uppercase tracking-wider"><i class="fa-solid fa-box-open mr-2"></i> Pilih Barang Kebutuhan</h3>
                    </div>
                </div>

                <!-- Grid Data -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php while($item = mysqli_fetch_assoc($query)): ?>
                    <div class="bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 rounded-2xl p-5 hover:border-red-500 transition-colors shadow-sm flex flex-col">
                        <div class="flex-1">
                            <span class="text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-2 py-1 rounded uppercase tracking-widest mb-3 inline-block">SKU: <?= htmlspecialchars($item['sku']) ?></span>
                            <h4 class="font-black text-lg mb-2 line-clamp-2"><?= htmlspecialchars($item['nama_barang']) ?></h4>
                            <div class="flex items-end justify-between mt-4">
                                <div>
                                    <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Stok / Kritis</span>
                                    <span class="font-black text-xl <?= $item['stok_saat_ini'] <= $item['stok_kritis'] ? 'text-red-500' : 'text-slate-700 dark:text-slate-200' ?>"><?= $item['stok_saat_ini'] ?> <span class="text-sm text-slate-400">/ <?= $item['stok_kritis'] ?></span></span>
                                </div>
                                <span class="text-xs font-bold text-slate-400"><?= $item['unit'] ?></span>
                            </div>
                        </div>
                        <a href="create_rfq.php?inventory_id=<?= $item['id'] ?>" class="mt-6 block text-center bg-slate-900 hover:bg-red-600 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-colors">
                            Buat RFQ <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($query) == 0): ?>
                    <div class="col-span-full bg-slate-50 dark:bg-slate-900 border-2 border-dashed border-slate-200 dark:border-slate-700 p-12 rounded-3xl text-center">
                        <i class="fa-solid fa-box-open text-5xl text-slate-300 dark:text-slate-600 mb-4"></i>
                        <h4 class="text-xl font-black text-slate-800 dark:text-white uppercase mb-2">Semua Stok Aman</h4>
                        <p class="text-slate-500">Tidak ada barang yang menyentuh batas Reorder Point (ROP) saat ini.</p>
                    </div>
                    <?php endif; ?>
                </div>

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
