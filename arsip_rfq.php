<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'arsip_rfq';

// Query semua RFQ beserta jumlah vendor diundang dan jumlah SPH masuk
$query_rfq = mysqli_query($conn, "
    SELECT r.*, i.nama_barang, i.sku, u.username as creator_name,
           (SELECT COUNT(*) FROM rfq_vendors rv WHERE rv.rfq_id = r.id) as total_vendor,
           (SELECT COUNT(*) FROM sph_submissions s WHERE s.rfq_id = r.id) as total_sph
    FROM rfq_requests r
    JOIN inventory i ON r.inventory_id = i.id
    LEFT JOIN users u ON r.created_by = u.id
    ORDER BY r.created_at DESC
") or handle_db_error($conn);

// Query daftar vendor yang diundang per RFQ (untuk referensi login testing)
$vendor_per_rfq = [];
$query_rv = mysqli_query($conn, "
    SELECT rv.rfq_id, v.id as vendor_id, v.nama_perusahaan, u.username as vendor_username
    FROM rfq_vendors rv
    JOIN vendors v ON rv.vendor_id = v.id
    LEFT JOIN users u ON u.vendor_id = v.id
    ORDER BY rv.rfq_id, v.nama_perusahaan
") or handle_db_error($conn);
while($rv = mysqli_fetch_assoc($query_rv)) {
    $vendor_per_rfq[$rv['rfq_id']][] = $rv;
}

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php
    $page_title = "Arsip RFQ";
    $page_subtitle = "Riwayat Seluruh Request for Quotation";
    $navbar_badge = '<span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total: ' . mysqli_num_rows($query_rfq) . ' RFQ</span>';
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">

            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400"></i>
                    </div>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari Nomor RFQ, Kebutuhan, atau Pemohon..." style="padding-left: 2.5rem;" class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors text-slate-800 dark:text-white">
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
                            <th class="p-6">Vendor Diundang</th>
                            <th class="p-6 text-center">SPH Masuk</th>
                            <th class="p-6 text-center">Deadline</th>
                            <th class="p-6 text-center">Status</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="dataTable" class="divide-y divide-slate-50 dark:divide-slate-800">
                        <?php while($rfq = mysqli_fetch_assoc($query_rfq)): 
                            $status_colors = [
                                'Open' => 'bg-green-50 text-green-600 border-green-200',
                                'Closed' => 'bg-slate-100 text-slate-500 border-slate-200',
                                'Cancelled' => 'bg-red-50 text-red-500 border-red-200'
                            ];
                            $sc = $status_colors[$rfq['status']] ?? 'bg-yellow-50 text-yellow-600 border-yellow-200';
                            $is_expired = (!empty($rfq['deadline']) && strtotime($rfq['deadline']) < time() && $rfq['status'] === 'Open');
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
                            </td>
                            <td class="p-6">
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    <?php 
                                    $vendors_list = $vendor_per_rfq[$rfq['id']] ?? [];
                                    if(!empty($vendors_list)):
                                        foreach($vendors_list as $vr): 
                                    ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800 whitespace-nowrap" title="Login: <?= htmlspecialchars($vr['vendor_username'] ?? '-') ?>">
                                        <i class="fa-solid fa-building text-[8px] opacity-60"></i>
                                        <?= htmlspecialchars($vr['nama_perusahaan']) ?>
                                        <?php if(!empty($vr['vendor_username'])): ?>
                                        <span class="text-[9px] text-blue-400 dark:text-blue-500 font-mono">@<?= htmlspecialchars($vr['vendor_username']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                    <span class="text-slate-400 dark:text-slate-500 italic text-[10px]">Belum ada</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="bg-indigo-50 text-indigo-600 border border-indigo-200 px-3 py-1 rounded-full text-[10px] font-black whitespace-nowrap inline-block shadow-sm"><i class="fa-solid fa-file-invoice mr-1"></i> <?= $rfq['total_sph'] ?></span>
                            </td>
                            <td class="p-6 text-center">
                                <?php if(!empty($rfq['deadline'])): ?>
                                <div class="text-xs font-bold <?= $is_expired ? 'text-red-500' : 'text-slate-600 dark:text-slate-300' ?>">
                                    <?= date('d M Y', strtotime($rfq['deadline'])) ?>
                                </div>
                                <div class="text-[10px] text-slate-400 font-bold"><i class="fa-regular fa-clock mr-1"></i> <?= date('H:i', strtotime($rfq['deadline'])) ?></div>
                                <?php else: ?>
                                <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($is_expired): ?>
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase bg-amber-50 text-amber-600 border-amber-200 whitespace-nowrap inline-block shadow-sm">Expired</span>
                                <?php else: ?>
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase <?= $sc ?> whitespace-nowrap inline-block shadow-sm"><?= htmlspecialchars($rfq['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-right">
                                <a href="print_rfq.php?id=<?= $rfq['id'] ?>" target="_blank" class="px-3 py-2 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-red-600 hover:border-red-200 rounded-lg text-[10px] font-black uppercase transition shadow-sm whitespace-nowrap inline-flex items-center justify-center gap-1" title="Cetak RFQ PDF">
                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                </a>
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
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Data RFQ</h3>
                <p class="text-slate-400 text-sm">RFQ yang sudah pernah dibuat akan muncul di sini.</p>
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
