<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y');

// Auto-heal: Sinkronisasi invoice_number dari tabel invoices ke purchase_orders untuk PO lama yang tersangkut
mysqli_query($conn, "
    UPDATE purchase_orders p 
    JOIN invoices i ON p.id = i.po_id 
    SET p.invoice_number = i.invoice_number, 
        p.invoice_submitted_at = i.created_at 
    WHERE (p.invoice_number IS NULL OR p.invoice_number = '')
");

// PO yang siap di-match (Invoice sudah masuk)
$query_match = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status IN ('Goods Received', 'Invoice Submitted')
    ORDER BY p.invoice_submitted_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "3-Way Matching";
    $page_subtitle = "Validasi PO • Penerimaan Gudang • Invoice";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php $active = '3way'; include 'sidebar_finance.php'; ?>

        <main class="flex-1 overflow-y-auto p-5 md:p-6 space-y-6 animate-fade-in-up">
            <!-- GREETING BANNER -->
            <div class="bg-gradient-to-r from-emerald-500/10 to-transparent p-5 rounded-2xl border border-emerald-500/20 flex justify-between items-center flex-wrap gap-4 mb-6">
                <div>
                    <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Validasi Keuangan PO</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500 font-medium mt-1">Selamat datang kembali, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>. Lakukan pencocokan invoice vendor.</p>
                </div>
                <div class="bg-white dark:bg-[#1e293b] px-4 py-2 rounded-xl shadow-sm border border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <i class="fa-solid fa-shield-halved text-emerald-500 mr-1.5"></i> AP Secure
                </div>
            </div>
            <?php if(mysqli_num_rows($query_match) > 0): ?>
                <?php while($po = mysqli_fetch_assoc($query_match)): ?>
                <div class="bg-white dark:bg-[#1e293b] p-6 rounded-2xl border shadow-sm" x-data="{ matching: false }">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="font-black text-slate-800 dark:text-white text-lg uppercase"><?= htmlspecialchars($po['po_number']) ?></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500 mt-1"><?= htmlspecialchars($po['nama_perusahaan']) ?> — <?= htmlspecialchars($po['nama_barang']) ?></p>
                        </div>
                        <span class="bg-indigo-100 text-indigo-700 text-[9px] px-3 py-1 rounded-full font-black uppercase">Siap Matching</span>
                    </div>

                    <!-- 3-Way Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <!-- PO Data -->
                        <div class="p-6 border-2 border-green-500 bg-green-50/50 rounded-2xl">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="fa-solid fa-file-circle-check text-2xl text-green-600"></i>
                                <span class="text-[10px] font-black text-green-700 uppercase tracking-widest">Data PO</span>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400 dark:text-slate-500">No PO</span><span class="font-bold text-slate-800 dark:text-white"><?= $po['po_number'] ?></span></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400 dark:text-slate-500">Barang</span><span class="font-bold text-slate-800 dark:text-white"><?= $po['nama_barang'] ?></span></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400 dark:text-slate-500">Nilai</span><span class="font-bold text-slate-800 dark:text-white">Rp <?= number_format($po['total_harga'],0,',','.') ?></span></div>
                            </div>
                            <div class="mt-3 text-center"><span class="bg-green-600 text-white text-[9px] px-3 py-1 rounded-full font-black uppercase"><i class="fa-solid fa-check mr-1"></i> OK</span></div>
                        </div>

                        <!-- Goods Receipt -->
                        <div class="p-6 border-2 <?= $po['received_at'] ? 'border-green-500 bg-green-50/50' : 'border-red-500 bg-red-50' ?> rounded-2xl">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="fa-solid fa-truck-ramp-box text-2xl <?= $po['received_at'] ? 'text-green-600' : 'text-red-600' ?>"></i>
                                <span class="text-[10px] font-black <?= $po['received_at'] ? 'text-green-700' : 'text-red-700' ?> uppercase tracking-widest">Penerimaan Gudang</span>
                            </div>
                            <?php if($po['received_at']): ?>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400 dark:text-slate-500">Tgl Terima</span><span class="font-bold text-slate-800 dark:text-white"><?= date('d M Y', strtotime($po['received_at'])) ?></span></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400 dark:text-slate-500">Qty Diterima</span><span class="font-bold text-slate-800 dark:text-white"><?= $po['jumlah_diterima'] ?></span></div>
                            </div>
                            <div class="mt-3 text-center"><span class="bg-green-600 text-white text-[9px] px-3 py-1 rounded-full font-black uppercase"><i class="fa-solid fa-check mr-1"></i> OK</span></div>
                            <?php else: ?>
                            <p class="text-red-600 text-xs font-bold italic">Belum diterima gudang</p>
                            <div class="mt-3 text-center"><span class="bg-red-600 text-white text-[9px] px-3 py-1 rounded-full font-black uppercase animate-pulse"><i class="fa-solid fa-xmark mr-1"></i> WAITING</span></div>
                            <?php endif; ?>
                        </div>

                        <!-- Invoice -->
                        <div class="p-6 border-2 <?= $po['invoice_number'] ? 'border-green-500 bg-green-50/50' : 'border-red-500 bg-red-50' ?> rounded-2xl">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="fa-solid fa-file-invoice-dollar text-2xl <?= $po['invoice_number'] ? 'text-green-600' : 'text-red-600' ?>"></i>
                                <span class="text-[10px] font-black <?= $po['invoice_number'] ? 'text-green-700' : 'text-red-700' ?> uppercase tracking-widest">E-Invoice</span>
                            </div>
                            <?php if($po['invoice_number']): ?>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400 dark:text-slate-500">No Invoice</span><span class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($po['invoice_number']) ?></span></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400 dark:text-slate-500">Tgl Submit</span><span class="font-bold text-slate-800 dark:text-white"><?= date('d M Y', strtotime($po['invoice_submitted_at'])) ?></span></div>
                                <?php if($po['invoice_file']): ?>
                                <a href="<?= $po['invoice_file'] ?>" target="_blank" class="text-blue-600 text-xs font-bold underline"><i class="fa-solid fa-download mr-1"></i> Lihat File</a>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3 text-center"><span class="bg-green-600 text-white text-[9px] px-3 py-1 rounded-full font-black uppercase"><i class="fa-solid fa-check mr-1"></i> OK</span></div>
                            <?php else: ?>
                            <p class="text-red-600 text-xs font-bold italic">Belum ada invoice</p>
                            <div class="mt-3 text-center"><span class="bg-red-600 text-white text-[9px] px-3 py-1 rounded-full font-black uppercase animate-pulse"><i class="fa-solid fa-xmark mr-1"></i> WAITING</span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Match Button -->
                    <?php if($po['received_at'] && $po['invoice_number']): ?>
                    <div class="flex justify-center">
                        <button x-show="!matching" @click="
                            matching = true;
                            let fd = new FormData();
                            fd.append('po_id', <?= $po['id'] ?>);
                            fetch('process_3way_matching.php', {method:'POST', body:fd})
                            .then(r=>r.json())
                            .then(d=>{alert(d.message);if(d.success)location.reload();else matching=false;})
                            .catch(()=>{alert('Error');matching=false;})
                        " class="bg-slate-900 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-green-600 transition shadow-xl flex items-center gap-3">
                            <i class="fa-solid fa-check-double"></i> Match & Approve for Payment
                        </button>
                        <div x-show="matching" class="text-slate-400 dark:text-slate-500 font-bold text-sm flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin"></i> Memproses matching...
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-slate-400 dark:text-slate-500 italic text-sm">Menunggu semua dokumen lengkap sebelum bisa di-match.</p>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="bg-white dark:bg-[#1e293b] p-12 rounded-2xl border shadow-sm text-center">
                <i class="fa-solid fa-magnifying-glass-dollar text-6xl text-slate-200 mb-6 block"></i>
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Tidak Ada PO untuk Di-Match</h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm">Semua PO sudah di-match atau belum ada invoice yang masuk dari vendor.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>
