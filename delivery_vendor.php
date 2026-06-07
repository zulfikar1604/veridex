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
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Surat Jalan";
    $page_subtitle = "Pemberitahuan Pengiriman Barang";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_vendor.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            <?php if(mysqli_num_rows($query_ready) > 0): ?>
            <div>
                <h3 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-4"><i class="fa-solid fa-truck mr-1"></i> PO Siap Dikirim</h3>
                <div class="space-y-4">
                    <?php while($po = mysqli_fetch_assoc($query_ready)): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ open: false, submitting: false }">
                        <div class="p-6 flex items-center justify-between flex-wrap gap-4">
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
                            <form @submit.prevent="submitting=true; let fd=new FormData($event.target); fd.append('po_id',<?=$po['id']?>); fetch('process_vendor_delivery.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{alert(d.message);if(d.success)location.reload();else submitting=false;}).catch(()=>{alert('Error');submitting=false;})" class="space-y-4 max-w-xl">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Nama Pengemudi *</label>
                                        <input type="text" name="sj_pengemudi" required placeholder="Contoh: Budi Santoso" class="w-full border border-slate-200 p-3 rounded-xl outline-none focus:border-red-500 bg-white font-bold text-sm">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">No. Polisi Kendaraan *</label>
                                        <input type="text" name="sj_nopol" required placeholder="Contoh: B 1234 CD" class="w-full border border-slate-200 p-3 rounded-xl outline-none focus:border-red-500 bg-white font-bold text-sm uppercase">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Kontak Pengemudi *</label>
                                    <input type="text" name="sj_kontak" required placeholder="Contoh: 08123456789" class="w-full border border-slate-200 p-3 rounded-xl outline-none focus:border-red-500 bg-white font-bold text-sm">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Catatan Pengiriman (Opsional)</label>
                                    <textarea name="sj_catatan" rows="2" placeholder="Catatan khusus terkait pengiriman barang..." class="w-full border border-slate-200 p-3 rounded-xl outline-none focus:border-red-500 bg-white text-sm"></textarea>
                                </div>
                                
                                <button type="submit" :disabled="submitting" class="w-full bg-slate-900 text-white py-4 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-blue-600 transition shadow-lg disabled:opacity-50 mt-4">
                                    <span x-show="!submitting"><i class="fa-solid fa-file-signature mr-2"></i> Generate Surat Jalan & Kirim</span>
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
                    <div class="overflow-x-auto w-full">
<table class="w-full text-sm">
                        <thead><tr class="bg-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-widest"><th class="p-4">No PO</th><th class="p-4">Barang</th><th class="p-4">No. Resi</th><th class="p-4 text-right">Status</th></tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($po = mysqli_fetch_assoc($query_shipped)):
                                $sc = ['Shipped'=>'bg-purple-50 text-purple-600','Goods Received'=>'bg-green-50 text-green-600','Invoice Submitted'=>'bg-indigo-50 text-indigo-600','Payment Approved'=>'bg-amber-50 text-amber-600','Payment Completed'=>'bg-emerald-50 text-emerald-700'];
                                $c = $sc[$po['status']] ?? 'bg-slate-100 text-slate-500';
                            ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 font-bold text-slate-700"><?= htmlspecialchars($po['po_number']) ?></td>
                                <td class="p-4 text-slate-600"><?= htmlspecialchars($po['nama_barang']) ?></td>
                                <td class="p-4 text-slate-600"><?= htmlspecialchars($po['no_resi'] ?? '-') ?></td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="print_surat_jalan.php?id=<?= $po['id'] ?>" target="_blank" class="px-3 py-1 bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak Surat Jalan"><i class="fa-solid fa-file-invoice"></i> SJ</a>
                                        <a href="print_delivery_label.php?id=<?= $po['id'] ?>" target="_blank" class="px-3 py-1 bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak Label Barcode"><i class="fa-solid fa-barcode"></i> Label</a>
                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase <?= $c ?>"><?= $po['status'] ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
</div>
                </div>
                <?php else: ?>
                <div class="bg-white p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-truck text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 font-bold">Belum ada riwayat pengiriman.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>