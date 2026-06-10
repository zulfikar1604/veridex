<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

$active = 'arsip_rfq';
$vendor_id = $_SESSION['vendor_id'] ?? 0;

// Query semua RFQ di mana vendor ini diundang, beserta status SPH-nya
$query_rfq = mysqli_query($conn, "
    SELECT r.*, i.nama_barang, i.sku, i.unit,
           rv.has_submitted,
           s.nomor_sph, s.harga_penawaran, s.status as sph_status, s.created_at as sph_date
    FROM rfq_requests r
    JOIN rfq_vendors rv ON r.id = rv.rfq_id
    JOIN inventory i ON r.inventory_id = i.id
    LEFT JOIN sph_submissions s ON s.rfq_id = r.id AND s.vendor_id = $vendor_id
    WHERE rv.vendor_id = $vendor_id
    ORDER BY r.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php
    $page_title = "Arsip RFQ";
    $page_subtitle = "Daftar Permintaan Penawaran Terbuka";
    $navbar_badge = '<span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total: ' . mysqli_num_rows($query_rfq) . ' RFQ</span>';
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_vendor.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">

            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400"></i>
                    </div>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari Nomor RFQ, SPH, atau Barang..." style="padding-left: 2.5rem;" class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors text-slate-800 dark:text-white">
                </div>
            </div>

            <?php if(mysqli_num_rows($query_rfq) > 0): ?>
            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                        <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            <th class="p-6">RFQ Number</th>
                            <th class="p-6">Item Kebutuhan</th>
                            <th class="p-6 text-center">Qty</th>
                            <th class="p-6 text-center">Deadline</th>
                            <th class="p-6 text-center">SPH Anda</th>
                            <th class="p-6 text-center">Status SPH</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="dataTable" class="divide-y divide-slate-50 dark:divide-slate-800">
                        <?php while($rfq = mysqli_fetch_assoc($query_rfq)):
                            $is_expired = (!empty($rfq['deadline']) && strtotime($rfq['deadline']) < time());
                            
                            $sph_status_colors = [
                                'Pending Review' => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                                'TBE Done' => 'bg-blue-50 text-blue-600 border-blue-200',
                                'CBE Done' => 'bg-purple-50 text-purple-600 border-purple-200',
                                'Evaluated' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                'Selected' => 'bg-green-50 text-green-600 border-green-200',
                                'Rejected' => 'bg-red-50 text-red-500 border-red-200'
                            ];
                            $sph_sc = $sph_status_colors[$rfq['sph_status'] ?? ''] ?? 'bg-slate-100 text-slate-500 border-slate-200';
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors group">
                            <td class="p-6">
                                <div class="font-black text-slate-800 dark:text-white text-sm">#<?= htmlspecialchars($rfq['rfq_number']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold"><i class="fa-regular fa-calendar mr-1"></i> <?= date('d M Y', strtotime($rfq['created_at'])) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($rfq['nama_barang']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold">SKU: <?= htmlspecialchars($rfq['sku']) ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="font-black text-slate-800 dark:text-white"><?= number_format($rfq['jumlah_kebutuhan']) ?></span>
                                <span class="text-[10px] text-slate-400 font-bold ml-1"><?= htmlspecialchars($rfq['unit']) ?></span>
                            </td>
                            <td class="p-6 text-center">
                                <?php if(!empty($rfq['deadline'])): ?>
                                <div class="text-xs font-bold <?= ($is_expired && $rfq['status'] === 'Open') ? 'text-red-500' : 'text-slate-600 dark:text-slate-300' ?>">
                                    <?= date('d M Y', strtotime($rfq['deadline'])) ?>
                                </div>
                                <div class="text-[10px] text-slate-400 font-bold"><i class="fa-regular fa-clock mr-1"></i> <?= date('H:i', strtotime($rfq['deadline'])) ?></div>
                                <?php else: ?>
                                <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($rfq['has_submitted'] && $rfq['nomor_sph']): ?>
                                <div class="text-[10px] text-slate-500 font-bold">#<?= htmlspecialchars($rfq['nomor_sph']) ?></div>
                                <div class="text-xs font-black text-slate-800 dark:text-white">Rp <?= number_format($rfq['harga_penawaran'] ?? 0, 0, ',', '.') ?></div>
                                <?php else: ?>
                                <span class="text-[10px] font-bold text-slate-400 italic">Belum Submit</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($rfq['sph_status']): ?>
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase shadow-sm <?= $sph_sc ?>"><?= htmlspecialchars($rfq['sph_status']) ?></span>
                                <?php else: ?>
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase shadow-sm bg-slate-50 text-slate-400 border-slate-200 dark:bg-slate-800 dark:border-slate-700">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="print_rfq.php?id=<?= $rfq['id'] ?>" target="_blank" class="px-3 py-1 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-blue-600 hover:border-blue-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak RFQ PDF">
                                        <i class="fa-solid fa-print"></i> RFQ
                                    </a>
                                    <?php if($rfq['has_submitted'] && $rfq['nomor_sph']): ?>
                                    <a href="print_sph.php?rfq=<?= $rfq['id'] ?>&vendor=<?= $vendor_id ?>" target="_blank" class="px-3 py-1 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-green-600 hover:border-green-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak SPH PDF">
                                        <i class="fa-solid fa-print"></i> SPH
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
</div>
            </div>
            <?php else: ?>
            <div class="bg-white dark:bg-[#1e293b] p-16 rounded-[2.5rem] border shadow-sm text-center">
                <i class="fa-solid fa-folder-open text-7xl text-slate-200 dark:text-slate-700 mb-6 block"></i>
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Riwayat RFQ</h3>
                <p class="text-slate-400 text-sm">Anda belum pernah diundang dalam proses pengadaan oleh Veridex.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#dataTable tr");
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }
    </script>
</body>
</html>
