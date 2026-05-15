<?php
session_start();
require 'database.php';

// Proteksi Internal
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'buat_po';

// Ambil item yang sudah selesai evaluasi CBE (semua SPH untuk item tersebut statusnya minimal 'Evaluated')
// Tapi ini tricky. Cukup cari item yang SPH-nya ada yang status 'Selected' (sudah digenerate) 
// atau yang siap di-generate (Semua SPH Evaluated).
// Agar gampang, kita join purchase_orders untuk mengecek. 
// Karena di strategic_procurement.php ketika kita klik "Finalize & Generate PO", PO sudah terbuat.
// Halaman ini mungkin untuk melihat PO yang baru dibuat atau yang Pending Manager.

// Query: PO yang dibuat oleh procurement ini dan masih Pending Manager
$proc_id = $_SESSION['user_id'];
$query_po = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'Pending Manager'
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Purchase Orders</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Daftar PO (Pending Approval Manager)</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-black text-slate-400 uppercase block">Pending PO</span>
                    <span class="text-2xl font-black text-red-600"><?= mysqli_num_rows($query_po) ?></span>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <?php if(mysqli_num_rows($query_po) > 0): ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-900 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                                <tr>
                                    <th class="p-6">PO Number</th>
                                    <th class="p-6">Vendor & Item</th>
                                    <th class="p-6 text-center">Total Value</th>
                                    <th class="p-6 text-center">Status</th>
                                    <th class="p-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php while($po = mysqli_fetch_assoc($query_po)): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-6 font-black text-slate-800"><?= htmlspecialchars($po['po_number']) ?></td>
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 uppercase text-sm"><?= htmlspecialchars($po['nama_barang']) ?></div>
                                        <div class="text-[10px] text-red-500 font-bold uppercase tracking-widest italic">Vendor: <?= htmlspecialchars($po['nama_perusahaan']) ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <div class="font-black text-slate-700">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="px-3 py-1 bg-yellow-50 text-yellow-600 border border-yellow-100 rounded-full text-[9px] font-black uppercase tracking-widest">
                                            <?= htmlspecialchars($po['status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 hover:bg-red-500 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                            <i class="fa-solid fa-print"></i> Print PO
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200 shadow-sm">
                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-file-signature text-5xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight mb-2">No Pending PO</h3>
                        <p class="text-slate-400 font-medium">Belum ada PO yang digenerate atau menunggu persetujuan manager.</p>
                        <a href="strategic_procurement.php" class="mt-6 inline-block bg-red-600 text-white px-6 py-3 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-red-700 transition">Ke Evaluasi Vendor</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>
