<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

$active = 'po';
$vendor_id = $_SESSION['vendor_id'] ?? 0;

// PO menunggu konfirmasi vendor
$query_pending = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.vendor_id = $vendor_id AND p.status = 'Approved'
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

// Riwayat PO (sudah dikonfirmasi ke atas)
$query_history = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.vendor_id = $vendor_id AND p.status NOT IN ('Pending Approval', 'Approved', 'Rejected')
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_vendor.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Manajemen PO</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Konfirmasi & Tracking Purchase Orders</p>
                </div>
                <div class="flex items-center gap-3">
                    <?php if(mysqli_num_rows($query_pending) > 0): ?>
                    <span class="bg-red-600 text-white text-xs font-black px-3 py-1 rounded-full animate-pulse">
                        <?= mysqli_num_rows($query_pending) ?> Menunggu
                    </span>
                    <?php endif; ?>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">V</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            
            <!-- PO Menunggu Konfirmasi -->
            <?php if(mysqli_num_rows($query_pending) > 0): ?>
            <div>
                <h3 class="text-xs font-black text-red-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-bell"></i> PO Menunggu Konfirmasi Anda
                </h3>
                <div class="space-y-3">
                    <?php while($po = mysqli_fetch_assoc($query_pending)): ?>
                    <div class="bg-white p-6 rounded-2xl border-2 border-red-200 shadow-sm hover:shadow-md transition-all" 
                         x-data="{ confirming: false }">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="font-black text-slate-800 text-lg"><?= htmlspecialchars($po['po_number']) ?></span>
                                    <span class="bg-yellow-100 text-yellow-700 text-[9px] px-2 py-0.5 rounded-full font-black uppercase">Menunggu Konfirmasi</span>
                                </div>
                                <p class="text-sm text-slate-500 font-medium">
                                    <i class="fa-solid fa-box mr-1"></i> <?= htmlspecialchars($po['nama_barang']) ?>
                                    <span class="text-slate-300 mx-2">|</span>
                                    <span class="font-bold text-slate-700">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></span>
                                </p>
                                <p class="text-[10px] text-slate-400 mt-1 font-bold">
                                    <i class="fa-regular fa-calendar mr-1"></i> <?= date('d M Y H:i', strtotime($po['created_at'])) ?>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button @click="confirming = true" x-show="!confirming"
                                    class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-green-600 transition-colors shadow-lg">
                                    <i class="fa-solid fa-check mr-2"></i> Terima & Konfirmasi
                                </button>
                                <div x-show="confirming" x-cloak class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500 font-bold">Yakin?</span>
                                    <button @click="
                                        let fd = new FormData();
                                        fd.append('action', 'confirm_po');
                                        fd.append('po_id', <?= $po['id'] ?>);
                                        fetch('process_vendor_po.php', { method: 'POST', body: fd })
                                        .then(r => r.json())
                                        .then(d => { alert(d.message); if(d.success) location.reload(); })
                                    " class="bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-black hover:bg-green-700 transition">
                                        Ya, Konfirmasi
                                    </button>
                                    <button @click="confirming = false" class="bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-xs font-black hover:bg-slate-300 transition">
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat PO -->
            <div>
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat PO
                </h3>
                <?php if(mysqli_num_rows($query_history) > 0): ?>
                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left bg-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                <th class="p-4">No PO</th>
                                <th class="p-4">Barang</th>
                                <th class="p-4 text-center">Nilai</th>
                                <th class="p-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($po = mysqli_fetch_assoc($query_history)): 
                                $status_colors = [
                                    'Confirmed by Vendor' => 'bg-blue-50 text-blue-600 border-blue-200',
                                    'Shipped' => 'bg-purple-50 text-purple-600 border-purple-200',
                                    'Goods Received' => 'bg-green-50 text-green-600 border-green-200',
                                    'Invoice Submitted' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                    'Payment Approved' => 'bg-amber-50 text-amber-600 border-amber-200',
                                    'Payment Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                ];
                                $color = $status_colors[$po['status']] ?? 'bg-slate-100 text-slate-500 border-slate-200';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 font-bold text-slate-700"><?= htmlspecialchars($po['po_number']) ?></td>
                                <td class="p-4 text-slate-600"><?= htmlspecialchars($po['nama_barang']) ?></td>
                                <td class="p-4 text-center font-bold text-slate-700">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></td>
                                <td class="p-4 text-right">
                                    <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase <?= $color ?>"><?= $po['status'] ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-white p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-folder-open text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 font-bold">Belum ada riwayat PO.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>