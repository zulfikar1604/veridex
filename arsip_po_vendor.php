<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

$active = 'arsip_po';
$vendor_id = $_SESSION['vendor_id'] ?? 0;

// Query semua PO yang ditujukan untuk vendor ini
$query_po = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.vendor_id = $vendor_id
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php
    $page_title = "Arsip PO";
    $page_subtitle = "Riwayat Purchase Order Anda";
    $navbar_badge = '<span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">Total: ' . mysqli_num_rows($query_po) . ' PO</span>';
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_vendor.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <?php if(mysqli_num_rows($query_po) > 0): ?>
            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-900">
                        <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            <th class="p-6">PO Number</th>
                            <th class="p-6">Item</th>
                            <th class="p-6 text-center">Jumlah Pesan</th>
                            <th class="p-6 text-right">Total Nilai (Rp)</th>
                            <th class="p-6 text-center">Status</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($po = mysqli_fetch_assoc($query_po)): 
                            $status_colors = [
                                'Pending Manager' => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                                'Approved' => 'bg-blue-50 text-blue-600 border-blue-200',
                                'Confirmed by Vendor' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                'Shipped' => 'bg-purple-50 text-purple-600 border-purple-200',
                                'Goods Received' => 'bg-teal-50 text-teal-600 border-teal-200',
                                'Invoice Submitted' => 'bg-cyan-50 text-cyan-600 border-cyan-200',
                                'Payment Approved' => 'bg-orange-50 text-orange-600 border-orange-200',
                                'Payment Completed' => 'bg-green-50 text-green-600 border-green-200',
                                'Rejected' => 'bg-red-50 text-red-500 border-red-200'
                            ];
                            $sc = $status_colors[$po['status']] ?? 'bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700';
                        ?>
                        <tr class="hover:bg-slate-50 dark:bg-slate-900/50 transition-colors">
                            <td class="p-6">
                                <div class="font-black text-slate-800 dark:text-white text-sm"><?= htmlspecialchars($po['po_number']) ?></div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold"><?= date('d M Y', strtotime($po['created_at'])) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($po['nama_barang']) ?></div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold">SKU: <?= htmlspecialchars($po['sku']) ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="font-black text-slate-800 dark:text-white"><?= $po['jumlah_pesan'] ?></span>
                            </td>
                            <td class="p-6 text-right">
                                <span class="font-black text-slate-800 dark:text-white">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase <?= $sc ?>"><?= htmlspecialchars($po['status']) ?></span>
                            </td>
                            <td class="p-6 text-right">
                                <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="px-3 py-1.5 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 dark:text-slate-500 hover:text-blue-600 hover:border-blue-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak PO">
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
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Data PO</h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm">Purchase Order yang Anda terima akan muncul di sini.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
