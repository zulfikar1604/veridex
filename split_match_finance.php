<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}

$po_id = intval($_GET['po_id'] ?? 0);

// Get PO Data
$query = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.id = $po_id
");

if (mysqli_num_rows($query) == 0) {
    die("PO Tidak Ditemukan.");
}

$po = mysqli_fetch_assoc($query);

$page_title = "Split-View 3-Way Matching";
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] h-screen overflow-hidden flex flex-col">

    <!-- Slim Header -->
    <header class="bg-white dark:bg-[#1e293b] border-b border-slate-200 dark:border-slate-800 h-16 flex items-center justify-between px-6 shrink-0 z-10">
        <div class="flex items-center gap-4">
            <a href="dashboard_finance.php" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-sm">3-Way Matching Viewer</h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">PO: <?= htmlspecialchars($po['po_number']) ?></p>
            </div>
        </div>
        <div class="bg-indigo-50 text-indigo-700 px-4 py-1.5 rounded-lg text-xs font-black border border-indigo-100 flex items-center gap-2">
            <i class="fa-solid fa-columns"></i> Split-View Mode Active
        </div>
    </header>

    <!-- Split Content -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Kiri: Document Viewer (60%) -->
        <div class="w-3/5 border-r border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 p-4 flex flex-col">
            <div class="bg-white dark:bg-[#1e293b] rounded-t-xl border-t border-x border-slate-200 dark:border-slate-700 px-4 py-3 flex justify-between items-center shrink-0">
                <h3 class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-widest"><i class="fa-solid fa-file-pdf text-red-500 mr-2"></i> Document Preview</h3>
                <div class="flex gap-2">
                    <button class="px-3 py-1 bg-slate-100 text-slate-700 text-[10px] font-bold rounded hover:bg-slate-200 transition">Invoice</button>
                    <!-- Tombol Surat Jalan dihapus sementara jika tidak ada di DB, atau bisa ditambahkan jika ada -->
                </div>
            </div>
            <div class="flex-1 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 rounded-b-xl overflow-hidden shadow-inner relative">
                <?php 
                $file_to_show = $po['invoice_file'];
                if (empty($file_to_show) && !empty($po['invoice_number'])) {
                    $file_to_show = 'print_auto_invoice.php?po_id=' . $po['id'];
                }
                ?>
                <?php if(!empty($file_to_show)): ?>
                    <iframe src="<?= htmlspecialchars($file_to_show) ?>" class="w-full h-full border-0"></iframe>
                <?php else: ?>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-solid fa-file-circle-xmark text-6xl mb-4 text-slate-200"></i>
                        <p class="font-bold">File Invoice Tidak Tersedia di Server</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kanan: Validasi Sistem (40%) -->
        <div class="w-2/5 bg-slate-50 dark:bg-slate-800 p-6 overflow-y-auto" x-data="{ matching: false }">
            <h3 class="font-black text-slate-800 dark:text-white text-lg uppercase tracking-widest mb-6 border-b pb-4"><i class="fa-solid fa-clipboard-check text-emerald-500 mr-2"></i> Pengecekan Sistem</h3>
            
            <div class="space-y-4 mb-8">
                <!-- Data PO -->
                <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border shadow-sm">
                    <div class="flex items-center gap-2 mb-4 border-b pb-3">
                        <i class="fa-solid fa-file-contract text-blue-500"></i>
                        <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">Data PO Asli</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Nomor PO</span><span class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($po['po_number']) ?></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Vendor</span><span class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($po['nama_perusahaan']) ?></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Nilai PO</span><span class="font-bold text-blue-600">Rp <?= number_format($po['total_harga'],0,',','.') ?></span></div>
                    </div>
                </div>

                <!-- Penerimaan Gudang -->
                <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border shadow-sm <?= $po['received_at'] ? 'border-l-4 border-l-emerald-500' : 'border-l-4 border-l-red-500' ?>">
                    <div class="flex items-center gap-2 mb-4 border-b pb-3">
                        <i class="fa-solid fa-truck-ramp-box <?= $po['received_at'] ? 'text-emerald-500' : 'text-red-500' ?>"></i>
                        <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">Penerimaan Gudang</span>
                    </div>
                    <?php if($po['received_at']): ?>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Barang Diterima</span><span class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($po['nama_barang']) ?></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Tgl Terima</span><span class="font-bold text-slate-800 dark:text-white"><?= date('d M Y', strtotime($po['received_at'])) ?></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Qty Diterima</span><span class="font-bold text-emerald-600"><?= $po['jumlah_diterima'] ?> Unit (Lengkap)</span></div>
                        </div>
                    <?php else: ?>
                        <p class="text-red-500 font-bold text-xs"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Barang belum terkonfirmasi diterima oleh Gudang.</p>
                    <?php endif; ?>
                </div>

                <!-- Invoice Details -->
                <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border shadow-sm <?= $po['invoice_number'] ? 'border-l-4 border-l-emerald-500' : 'border-l-4 border-l-red-500' ?>">
                    <div class="flex items-center gap-2 mb-4 border-b pb-3">
                        <i class="fa-solid fa-file-invoice-dollar <?= $po['invoice_number'] ? 'text-emerald-500' : 'text-red-500' ?>"></i>
                        <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">Data E-Invoice</span>
                    </div>
                    <?php if($po['invoice_number']): ?>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">No Invoice</span><span class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($po['invoice_number']) ?></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Tgl Submit</span><span class="font-bold text-slate-800 dark:text-white"><?= date('d M Y', strtotime($po['invoice_submitted_at'])) ?></span></div>
                        </div>
                    <?php else: ?>
                        <p class="text-red-500 font-bold text-xs"><i class="fa-solid fa-triangle-exclamation mr-1"></i> E-Invoice belum di-submit oleh Vendor.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Area -->
            <div class="bg-white dark:bg-[#1e293b] p-6 rounded-2xl shadow-lg border-2 border-emerald-100 dark:border-slate-700">
                <p class="text-xs text-center text-slate-500 mb-4 font-bold uppercase tracking-widest">Keputusan Final</p>
                
                <?php if($po['received_at'] && $po['invoice_number'] && $po['status'] != 'Payment Approved' && $po['status'] != 'Payment Completed'): ?>
                <button x-show="!matching" @click="
                    matching = true;
                    let fd = new FormData();
                    fd.append('po_id', <?= $po['id'] ?>);
                    fetch('process_3way_matching.php', {method:'POST', body:fd})
                    .then(r=>r.json())
                    .then(d=>{
                        alert(d.message);
                        if(d.success) window.location.href='dashboard_finance.php';
                        else matching=false;
                    })
                    .catch(()=>{alert('Terjadi kesalahan jaringan');matching=false;})
                " class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-widest py-4 rounded-xl shadow-lg shadow-emerald-500/30 transition-all flex justify-center items-center gap-3">
                    <i class="fa-solid fa-fingerprint text-xl"></i> Approve 3-Way Matching
                </button>
                <div x-show="matching" class="w-full bg-slate-100 text-slate-500 font-black uppercase tracking-widest py-4 rounded-xl flex justify-center items-center gap-3">
                    <i class="fa-solid fa-spinner fa-spin text-xl"></i> Memproses...
                </div>
                <?php elseif($po['status'] == 'Payment Approved' || $po['status'] == 'Payment Completed'): ?>
                <div class="w-full bg-emerald-50 text-emerald-700 font-black uppercase tracking-widest py-4 rounded-xl border border-emerald-200 text-center flex justify-center items-center gap-2">
                    <i class="fa-solid fa-check-double text-xl"></i> Sudah di-Match
                </div>
                <?php else: ?>
                <button disabled class="w-full bg-slate-200 text-slate-400 font-black uppercase tracking-widest py-4 rounded-xl cursor-not-allowed flex justify-center items-center gap-3">
                    <i class="fa-solid fa-lock text-xl"></i> Belum Memenuhi Syarat
                </button>
                <p class="text-[10px] text-center text-red-500 mt-3 font-bold">Pastikan Gudang sudah menerima barang dan Vendor sudah upload Invoice.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
