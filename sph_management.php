<?php
session_start();
require 'database.php';

// 1. Proteksi Internal
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'sph';

// 2. Query mengambil rekap penawaran masuk
// Kita join dengan tabel vendors dan inventory agar informasinya lengkap
$query_sph = mysqli_query($conn, "
    SELECT s.*, v.nama_perusahaan, i.nama_barang 
    FROM sph_submissions s
    JOIN vendors v ON s.vendor_id = v.id
    JOIN inventory i ON s.inventory_id = i.id
    ORDER BY s.created_at DESC
") or handle_db_error($conn);
?>
<?php
$page_title = "Surat Penawaran Harga";
$page_subtitle = "Evaluasi & Arsip Penawaran Masuk";
$navbar_badge = '<span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total SPH: ' . mysqli_num_rows($query_sph) . '</span>';
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                        <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            <th class="p-6">Date Received</th>
                            <th class="p-6">Vendor Name</th>
                            <th class="p-6">Item Subject</th>
                            <th class="p-6 text-center">TBE (Tech)</th>
                            <th class="p-6 text-center">CBE (Comm)</th>
                            <th class="p-6 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = mysqli_fetch_assoc($query_sph)): ?>
                        <tr class="hover:bg-slate-50 dark:bg-slate-900/50 transition-colors">
                            <td class="p-6 text-xs font-bold text-slate-400 dark:text-slate-500">
                                <?= date('d M Y', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="p-6">
                                <div class="font-black text-slate-800 dark:text-white uppercase text-sm"><?= $row['nama_perusahaan'] ?></div>
                                <div class="text-[9px] text-slate-400 dark:text-slate-500 font-bold">DOC ID: <?= $row['nomor_sph'] ?></div>
                            </td>
                            <td class="p-6 text-xs font-medium text-slate-600 dark:text-slate-300">
                                <?= $row['nama_barang'] ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if ($row['tbe_score'] !== null): ?>
                                    <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-[10px] font-black"><?= $row['tbe_score'] ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if ($row['cbe_score'] !== null): ?>
                                    <span class="bg-green-50 text-green-600 px-3 py-1 rounded-full text-[10px] font-black"><?= $row['cbe_score'] ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-right">
                                <?php
                                $status_colors = [
                                    'Pending Review' => 'bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700',
                                    'TBE Done' => 'bg-blue-50 text-blue-600 border-blue-200',
                                    'CBE Done' => 'bg-purple-50 text-purple-600 border-purple-200',
                                    'Evaluated' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                    'Selected' => 'bg-green-50 text-green-600 border-green-200',
                                    'Rejected' => 'bg-red-50 text-red-600 border-red-200'
                                ];
                                $color = $status_colors[$row['status']] ?? 'bg-yellow-50 text-yellow-600 border-yellow-100';
                                ?>
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase <?= $color ?> mr-2">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                                <a href="print_sph.php?rfq=<?= $row['rfq_id'] ?>&vendor=<?= $row['vendor_id'] ?>" target="_blank" class="px-2 py-1 bg-white dark:bg-[#1e293b] text-slate-500 dark:text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700 rounded hover:text-blue-600 hover:border-blue-200 transition" title="Print SPH">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
</div>
            </div>

            <!-- Dashboard Info -->
            <div class="mt-6 p-6 bg-slate-900 rounded-3xl text-white flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center text-xl shadow-lg shadow-red-600/40">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Ready for Analysis</p>
                        <h4 class="text-sm font-bold uppercase">Data dari SPH ini akan otomatis diproses di halaman Strategic Procurement</h4>
                    </div>
                </div>
                <a href="strategic_procurement.php" class="bg-white dark:bg-[#1e293b]/10 hover:bg-white dark:bg-[#1e293b]/20 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition border border-white/10">
                    Go to Strategic Analysis <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
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