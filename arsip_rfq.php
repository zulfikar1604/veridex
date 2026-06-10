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

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <?php if(mysqli_num_rows($query_rfq) > 0): ?>
            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-900">
                        <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            <th class="p-6">RFQ Number</th>
                            <th class="p-6">Item</th>
                            <th class="p-6 text-center">Kebutuhan</th>
                            <th class="p-6 text-center">Vendor</th>
                            <th class="p-6 text-center">SPH Masuk</th>
                            <th class="p-6 text-center">Deadline</th>
                            <th class="p-6 text-center">Status</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($rfq = mysqli_fetch_assoc($query_rfq)): 
                            $status_colors = [
                                'Open' => 'bg-green-50 text-green-600 border-green-200',
                                'Closed' => 'bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700',
                                'Cancelled' => 'bg-red-50 text-red-500 border-red-200'
                            ];
                            $sc = $status_colors[$rfq['status']] ?? 'bg-yellow-50 text-yellow-600 border-yellow-200';
                            $is_expired = (!empty($rfq['deadline']) && strtotime($rfq['deadline']) < time() && $rfq['status'] === 'Open');
                        ?>
                        <tr class="hover:bg-slate-50 dark:bg-slate-900/50 transition-colors">
                            <td class="p-6">
                                <div class="font-black text-slate-800 dark:text-white text-sm"><?= htmlspecialchars($rfq['rfq_number']) ?></div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold"><?= date('d M Y', strtotime($rfq['created_at'])) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($rfq['nama_barang']) ?></div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold">SKU: <?= htmlspecialchars($rfq['sku']) ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="font-black text-slate-800 dark:text-white"><?= $rfq['jumlah_kebutuhan'] ?></span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-[10px] font-black"><?= $rfq['total_vendor'] ?> Vendor</span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-[10px] font-black"><?= $rfq['total_sph'] ?> SPH</span>
                            </td>
                            <td class="p-6 text-center">
                                <?php if(!empty($rfq['deadline'])): ?>
                                <div class="text-xs font-bold <?= $is_expired ? 'text-red-500' : 'text-slate-600 dark:text-slate-300' ?>">
                                    <?= date('d M Y', strtotime($rfq['deadline'])) ?>
                                </div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500"><?= date('H:i', strtotime($rfq['deadline'])) ?></div>
                                <?php else: ?>
                                <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($is_expired): ?>
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase bg-amber-50 text-amber-600 border-amber-200">Expired</span>
                                <?php else: ?>
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase <?= $sc ?>"><?= htmlspecialchars($rfq['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-right">
                                <a href="print_rfq.php?id=<?= $rfq['id'] ?>" target="_blank" class="px-3 py-1.5 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 dark:text-slate-500 hover:text-blue-600 hover:border-blue-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak RFQ">
                                    <i class="fa-solid fa-print mr-1"></i> Print
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
                <i class="fa-solid fa-folder-open text-7xl text-slate-100 mb-6 block"></i>
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Data RFQ</h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm">RFQ yang sudah pernah dibuat akan muncul di sini.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
