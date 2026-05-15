<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

$active = 'delivery';
$vendor_id = $_SESSION['vendor_id'] ?? 0;

$query_ready = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.vendor_id = $vendor_id AND p.status = 'Confirmed by Vendor'
    ORDER BY p.confirmed_by_vendor_at DESC
") or handle_db_error($conn);

$query_shipped = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.vendor_id = $vendor_id AND p.status IN ('Shipped','Goods Received','Invoice Submitted','Payment Approved','Payment Completed')
    ORDER BY p.shipped_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_vendor.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Delivery Management</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Input Bukti Pengiriman</p>
                </div>
                <div class="flex items-center gap-3">
                    <?php if(mysqli_num_rows($query_ready) > 0): ?>
                    <span class="bg-blue-600 text-white text-xs font-black px-3 py-1 rounded-full animate-pulse"><?= mysqli_num_rows($query_ready) ?> Siap Kirim</span>
                    <?php endif; ?>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">V</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <?php if(mysqli_num_rows($query_ready) > 0): ?>
            <div>
                <h3 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-4"><i class="fa-solid fa-truck mr-1"></i> PO Siap Dikirim</h3>
                <div class="space-y-4">
                    <?php while($po = mysqli_fetch_assoc($query_ready)): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ open: false, submitting: false }">
                        <div class="p-6 flex items-center justify-between">
                            <div>
                                <span class="font-black text-slate-800 text-lg"><?= htmlspecialchars($po['po_number']) ?></span>
                                <span class="bg-blue-100 text-blue-700 text-[9px] px-2 py-0.5 rounded-full font-black uppercase ml-2">Siap Kirim</span>
                                <p class="text-sm text-slate-500 mt-1"><i class="fa-solid fa-box mr-1"></i> <?= htmlspecialchars($po['nama_barang']) ?> | <span class="font-bold text-slate-700">Rp <?= number_format($po['total_harga'],0,',','.') ?></span></p>
                            </div>
                            <button @click="open = !open" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition">
                                <i class="fa-solid fa-truck-ramp-box mr-2"></i> Input Pengiriman
                            </button>
                        </div>
                        <div x-show="open" x-cloak x-transition class="bg-slate-50 border-t border-slate-100 p-6">
                            <form @submit.prevent="submitting=true; let fd=new FormData($event.target); fd.append('po_id',<?=$po['id']?>); fetch('process_vendor_delivery.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{alert(d.message);if(d.success)location.reload();else submitting=false;}).catch(()=>{alert('Error');submitting=false;})" enctype="multipart/form-data" class="space-y-4 max-w-lg">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">No. Resi / No. Surat Jalan *</label>
                                    <input type="text" name="no_resi" required placeholder="Contoh: SJ-2026-0001" class="w-full border border-slate-200 p-4 rounded-xl outline-none focus:border-red-500 bg-white font-bold">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Foto Surat Jalan (Opsional)</label>
                                    <div class="border-2 border-dashed border-slate-200 p-6 text-center rounded-2xl text-slate-400 text-xs font-medium hover:border-red-400 transition cursor-pointer relative">
                                        <i class="fa-solid fa-camera text-2xl mb-2 block"></i>Unggah JPG/PNG/PDF (Maks 5MB)
                                        <input type="file" name="foto_surat_jalan" accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 opacity-0 cursor-pointer">
                                    </div>
                                </div>
                                <button type="submit" :disabled="submitting" class="w-full bg-slate-900 text-white py-4 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-blue-600 transition disabled:opacity-50">
                                    <span x-show="!submitting"><i class="fa-solid fa-paper-plane mr-2"></i> Submit Pengiriman</span>
                                    <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Memproses...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div>
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Riwayat Pengiriman</h3>
                <?php if(mysqli_num_rows($query_shipped) > 0): ?>
                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead><tr class="bg-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-widest"><th class="p-4">No PO</th><th class="p-4">Barang</th><th class="p-4">No. Resi</th><th class="p-4 text-right">Status</th></tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($po = mysqli_fetch_assoc($query_shipped)):
                                $sc = ['Shipped'=>'bg-purple-50 text-purple-600','Goods Received'=>'bg-green-50 text-green-600','Invoice Submitted'=>'bg-indigo-50 text-indigo-600','Payment Approved'=>'bg-amber-50 text-amber-600','Payment Completed'=>'bg-emerald-50 text-emerald-700'];
                                $c = $sc[$po['status']] ?? 'bg-slate-100 text-slate-500';
                            ?>
                            <tr class="hover:bg-slate-50 transition"><td class="p-4 font-bold text-slate-700"><?= htmlspecialchars($po['po_number']) ?></td><td class="p-4 text-slate-600"><?= htmlspecialchars($po['nama_barang']) ?></td><td class="p-4 text-slate-600"><?= htmlspecialchars($po['no_resi'] ?? '-') ?></td><td class="p-4 text-right"><span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase <?= $c ?>"><?= $po['status'] ?></span></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-white p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-truck text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 font-bold">Belum ada riwayat pengiriman.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>