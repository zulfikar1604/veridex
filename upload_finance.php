<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$active = 'upload';

$po_id = intval($_GET['po_id'] ?? 0);
$po = null;

if ($po_id > 0) {
    $po = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan 
        FROM purchase_orders p
        JOIN inventory i ON p.inventory_id = i.id
        JOIN vendors v ON p.vendor_id = v.id
        WHERE p.id = $po_id AND p.status = 'Payment Approved'
    "));
}

$page_title = "Upload Bukti Bayar";
$page_subtitle = "Unggah Bukti Pelunasan PO";
if ($po) {
    $page_subtitle = "Unggah Bukti Pelunasan " . htmlspecialchars($po['po_number']);
}
include 'header_meta.php';
?>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden text-slate-800">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_finance.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 flex items-center justify-center">
            <?php if($po): ?>
            <div class="bg-white p-10 rounded-[3rem] border shadow-sm w-full max-w-2xl" x-data="{ submitting: false }">
                <!-- Detail PO -->
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-8">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Detail Pembayaran</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-500">No PO</span><p class="font-black text-slate-800"><?= $po['po_number'] ?></p></div>
                        <div><span class="text-slate-500">Vendor</span><p class="font-black text-slate-800"><?= htmlspecialchars($po['nama_perusahaan']) ?></p></div>
                        <div><span class="text-slate-500">Barang</span><p class="font-black text-slate-800"><?= htmlspecialchars($po['nama_barang']) ?></p></div>
                        <div><span class="text-slate-500">Invoice</span><p class="font-black text-slate-800"><?= htmlspecialchars($po['invoice_number'] ?? '-') ?></p></div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-200 flex justify-between items-center flex-wrap gap-4">
                        <span class="text-slate-500 text-sm">Total Pembayaran</span>
                        <span class="text-2xl font-black text-red-600">Rp <?= number_format($po['total_harga'],0,',','.') ?></span>
                    </div>
                </div>

                <!-- Upload Form -->
                <form @submit.prevent="
                    submitting = true;
                    let fd = new FormData($event.target);
                    fd.append('po_id', <?= $po['id'] ?>);
                    fetch('process_payment.php', {method:'POST', body:fd})
                    .then(r=>r.json())
                    .then(d=>{
                        alert(d.message);
                        if(d.success) window.location.href='pembayaran_finance.php';
                        else submitting=false;
                    })
                    .catch(()=>{alert('Error');submitting=false;})
                " enctype="multipart/form-data" class="space-y-6">
                    <label class="cursor-pointer group block">
                        <div class="p-10 border-4 border-dashed border-slate-100 rounded-[2rem] group-hover:border-red-400 group-hover:bg-red-50/30 transition-all text-center">
                            <i class="fa-solid fa-cloud-arrow-up text-6xl text-slate-200 group-hover:text-red-500 transition-all mb-4 block"></i>
                            <h4 class="font-black text-slate-700 text-lg mb-1">Pilih File Bukti Transfer</h4>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Format: PDF/JPG (Maks 5MB)</p>
                            <input type="file" name="bukti_bayar" accept=".jpg,.jpeg,.png,.pdf" required class="hidden">
                        </div>
                    </label>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Catatan Pembayaran (Opsional)</label>
                        <textarea name="payment_notes" rows="3" placeholder="Contoh: Transfer via BCA ke rekening vendor..." class="w-full border border-slate-200 p-4 rounded-xl outline-none focus:border-red-500 bg-slate-50 font-medium text-sm"></textarea>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-500 font-medium bg-blue-50 p-3 rounded-xl border border-blue-100">
                        <i class="fa-solid fa-circle-info text-blue-500"></i>
                        Bukti yang diunggah akan muncul secara transparan di dashboard vendor.
                    </div>

                    <button type="submit" :disabled="submitting" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-red-600 transition-all disabled:opacity-50">
                        <span x-show="!submitting"><i class="fa-solid fa-check-double mr-2"></i> Konfirmasi Pembayaran</span>
                        <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Memproses...</span>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="bg-white p-16 rounded-[3rem] border shadow-sm text-center max-w-lg">
                <i class="fa-solid fa-cloud-arrow-up text-7xl text-slate-100 mb-6 block"></i>
                <h3 class="font-black text-slate-800 text-xl mb-2">Pilih PO Terlebih Dahulu</h3>
                <p class="text-slate-400 text-sm mb-6">Buka halaman Management Pembayaran untuk memilih PO yang akan dibayar.</p>
                <a href="pembayaran_finance.php" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-red-600 transition inline-block">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Ke Halaman Pembayaran
                </a>
            </div>
            <?php endif; ?>
    </main>
</div>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>