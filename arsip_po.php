<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'arsip_po';

// Query semua PO beserta informasi barang dan vendor
$query_po = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">
    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Arsip PO</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Riwayat Seluruh Purchase Order</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">
                        Total: <?= mysqli_num_rows($query_po) ?> PO
                    </span>
                    <?php include 'navbar_notifications.php'; ?>
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow-lg shadow-slate-900/20 shrink-0">
                        <i class="fa-solid fa-user-tie text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <?php if(mysqli_num_rows($query_po) > 0): ?>
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-900">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            <th class="p-6">PO Number</th>
                            <th class="p-6">Vendor</th>
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
                            $sc = $status_colors[$po['status']] ?? 'bg-slate-100 text-slate-500 border-slate-200';
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-6">
                                <div class="font-black text-slate-800 text-sm"><?= htmlspecialchars($po['po_number']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold"><?= date('d M Y', strtotime($po['created_at'])) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800 uppercase text-sm"><?= htmlspecialchars($po['nama_perusahaan']) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800 uppercase text-sm"><?= htmlspecialchars($po['nama_barang']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold">SKU: <?= htmlspecialchars($po['sku']) ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="font-black text-slate-800"><?= $po['jumlah_pesan'] ?></span>
                            </td>
                            <td class="p-6 text-right">
                                <span class="font-black text-slate-800">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase <?= $sc ?>"><?= htmlspecialchars($po['status']) ?></span>
                            </td>
                            <td class="p-6 text-right">
                                <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak PO">
                                    <i class="fa-solid fa-print mr-1"></i> Print
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-white p-16 rounded-[2.5rem] border shadow-sm text-center">
                <i class="fa-solid fa-folder-open text-7xl text-slate-100 mb-6 block"></i>
                <h3 class="font-black text-slate-800 text-xl mb-2">Belum Ada Data PO</h3>
                <p class="text-slate-400 text-sm">Purchase Order yang sudah dibuat akan muncul di sini.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
