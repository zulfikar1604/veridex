<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'arsip_pr';

// Query Logika UNION untuk Simulasi PR Archive
$query_pr = mysqli_query($conn, "
    SELECT 
        i.id as inventory_id, 
        i.nama_barang, 
        i.sku, 
        'Material' as kategori,
        'Closed (Telah Diproses ke RFQ)' as status,
        r.created_at as pr_date
    FROM rfq_requests r
    JOIN inventory i ON r.inventory_id = i.id
    UNION
    SELECT 
        id as inventory_id, 
        nama_barang, 
        sku, 
        'Material' as kategori,
        'Open (Menunggu Proses Procurement)' as status,
        CURRENT_TIMESTAMP as pr_date
    FROM inventory 
    WHERE status_label = 'Kritis' AND id NOT IN (SELECT inventory_id FROM rfq_requests WHERE status = 'Open')
    ORDER BY pr_date DESC
");

$page_title = "Arsip Purchase Requisition";
$page_subtitle = "Riwayat Permintaan Pembelian dari Gudang (ROP Trigger)";
$navbar_badge = '<span class="bg-indigo-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total: ' . mysqli_num_rows($query_pr) . ' PR</span>';
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            
            <div class="bg-indigo-50 dark:bg-indigo-900/30 border-l-4 border-indigo-600 p-4 rounded-r-xl">
                <p class="text-sm font-bold text-indigo-800 dark:text-indigo-300">
                    <i class="fa-solid fa-circle-info mr-2"></i> Dokumen PR pada sistem VERIDEX diterbitkan secara otomatis (*Auto-Generated*) ketika stok barang di Gudang menyentuh batas kritis (ROP).
                </p>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400"></i>
                    </div>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari Nomor PR, Nama Barang, atau SKU..." style="padding-left: 2.5rem;" class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors text-slate-800 dark:text-white">
                </div>
            </div>

            <?php if(mysqli_num_rows($query_pr) > 0): ?>
            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                            <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                                <th class="p-6">Nomor PR</th>
                                <th class="p-6">Tanggal ROP Trigger</th>
                                <th class="p-6">Barang</th>
                                <th class="p-6">Kategori</th>
                                <th class="p-6 text-center">Status</th>
                                <th class="p-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="dataTable" class="divide-y divide-slate-50 dark:divide-slate-800">
                            <?php while($pr = mysqli_fetch_assoc($query_pr)): 
                                $pr_number = "PR-" . date('Y', strtotime($pr['pr_date'])) . "-" . str_pad($pr['inventory_id'], 4, '0', STR_PAD_LEFT);
                                $is_open = strpos($pr['status'], 'Open') !== false;
                            ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors group">
                                <td class="p-6">
                                    <div class="font-black text-slate-800 dark:text-white text-sm"><?= $pr_number ?></div>
                                    <div class="text-[10px] text-slate-400 font-bold"><i class="fa-solid fa-robot mr-1 text-indigo-400"></i> Auto-Generated</div>
                                </td>
                                <td class="p-6 font-bold text-sm">
                                    <?= date('d M Y, H:i', strtotime($pr['pr_date'])) ?>
                                </td>
                                <td class="p-6">
                                    <div class="font-bold text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($pr['nama_barang']) ?></div>
                                    <div class="text-[10px] text-slate-400 font-bold">SKU: <?= htmlspecialchars($pr['sku']) ?></div>
                                </td>
                                <td class="p-6">
                                    <span class="text-xs font-bold bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded"><?= htmlspecialchars($pr['kategori']) ?></span>
                                </td>
                                <td class="p-6 text-center">
                                    <?php if($is_open): ?>
                                        <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase bg-red-50 text-red-600 border-red-200 whitespace-nowrap inline-block shadow-sm">Open (Butuh RFQ)</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase bg-green-50 text-green-600 border-green-200 whitespace-nowrap inline-block shadow-sm">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-6 text-right">
                                    <a href="print_pr.php?inventory_id=<?= $pr['inventory_id'] ?>" target="_blank" class="px-3 py-2 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 rounded-lg text-[10px] font-black uppercase transition shadow-sm whitespace-nowrap inline-flex items-center justify-center gap-1" title="Cetak Dokumen PR">
                                        <i class="fa-solid fa-print"></i> Cetak Dokumen
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="p-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl bg-white dark:bg-[#1e293b]">
                    <i class="fa-solid fa-folder-open text-5xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
                    <p class="text-slate-500 dark:text-slate-400 font-bold text-lg">Belum ada riwayat Purchase Requisition (PR).</p>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
    function searchTable() {
        let input = document.getElementById("searchInput").value.toLowerCase();
        let rows = document.getElementById("dataTable").getElementsByTagName("tr");
        for (let row of rows) {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? "" : "none";
        }
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
