<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$active = 'pembayaran';

// PO siap bayar (sudah di-match)
$query_ready = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'Payment Approved'
    ORDER BY p.payment_matched_at DESC
") or handle_db_error($conn);

// Riwayat pembayaran
$query_done = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'Payment Completed'
    ORDER BY p.payment_completed_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Payment Release";
    $page_subtitle = "Pelepasan Pembayaran Invoice Vendor";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_finance.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6" x-data="{ selectedPOs: [], submitting: false }">
            <!-- PO Siap Bayar -->
            <?php if(mysqli_num_rows($query_ready) > 0): ?>
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-black text-amber-600 uppercase tracking-widest"><i class="fa-solid fa-money-check-dollar mr-1"></i> Menunggu Pembayaran</h3>
                    <button @click="
                        <?php 
                            mysqli_data_seek($query_ready, 0);
                            $all_ids = [];
                            while($r = mysqli_fetch_assoc($query_ready)) $all_ids[] = $r['id'];
                            $all_ids_js = json_encode($all_ids);
                            mysqli_data_seek($query_ready, 0);
                        ?>
                        selectedPOs = (selectedPOs.length === <?= count($all_ids) ?>) ? [] : <?= $all_ids_js ?>;
                    " class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition bg-slate-200 dark:bg-slate-700 px-3 py-1.5 rounded-lg shadow-sm">
                        <i class="fa-solid fa-check-double mr-1"></i> Pilih Semua
                    </button>
                </div>
                <div class="space-y-3">
                    <?php while($po = mysqli_fetch_assoc($query_ready)): ?>
                    <div class="flex items-center justify-between p-6 bg-white dark:bg-[#1e293b] border rounded-2xl group hover:border-red-500 transition-all shadow-sm"
                         :class="selectedPOs.includes(<?= $po['id'] ?>) ? 'border-red-500 bg-red-50/10 dark:bg-red-900/10' : 'border-slate-200 dark:border-slate-700'">
                        <div class="flex items-center gap-4">
                            <input type="checkbox" value="<?= $po['id'] ?>" x-model="selectedPOs" class="w-5 h-5 rounded border-slate-300 text-red-600 focus:ring-red-500 cursor-pointer">
                            <div>
                            <p class="font-black text-slate-800 dark:text-white text-lg"><?= htmlspecialchars($po['po_number']) ?></p>
                            <p class="text-sm text-slate-400 dark:text-slate-500 font-medium">
                                <?= htmlspecialchars($po['nama_perusahaan']) ?> — <?= htmlspecialchars($po['nama_barang']) ?>
                                <span class="text-slate-300 mx-2">|</span>
                                <span class="text-slate-700 dark:text-slate-200 font-bold">Rp <?= number_format($po['total_harga'],0,',','.') ?></span>
                            </p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold mt-1">
                                <i class="fa-solid fa-file-invoice mr-1"></i> Invoice: <?= htmlspecialchars($po['invoice_number'] ?? '-') ?>
                                <span class="mx-2">|</span>
                                <i class="fa-solid fa-check-double mr-1 text-green-500"></i> Matched: <?= date('d M Y', strtotime($po['payment_matched_at'])) ?>
                                <?php if(!empty($po['payment_term'])): ?>
                                <span class="mx-2">|</span>
                                <i class="fa-solid fa-clock mr-1 text-amber-500"></i> Term: <span class="text-amber-600 font-black"><?= htmlspecialchars($po['payment_term']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <a href="upload_finance.php?po_id=<?= $po['id'] ?>" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-red-600 transition-colors shadow-lg">
                            <i class="fa-solid fa-credit-card mr-2"></i> Proses Bayar
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat Pembayaran -->
            <div>
                <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Riwayat Pembayaran</h3>
                <?php if(mysqli_num_rows($query_done) > 0): ?>
                <div class="bg-white dark:bg-[#1e293b] rounded-2xl border shadow-sm overflow-hidden">
                    <div class="overflow-x-auto w-full">
<table class="w-full text-sm">
                        <thead><tr class="bg-slate-900 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest"><th class="p-4">No PO</th><th class="p-4">Vendor & Barang</th><th class="p-4 text-center">Nilai</th><th class="p-4 text-center">Tgl Bayar</th><th class="p-4 text-right">Status</th></tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($po = mysqli_fetch_assoc($query_done)): ?>
                            <tr class="hover:bg-green-50/30 transition">
                                <td class="p-4 font-bold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($po['po_number']) ?></td>
                                <td class="p-4"><div class="text-slate-800 dark:text-white font-bold"><?= htmlspecialchars($po['nama_perusahaan']) ?></div><div class="text-[10px] text-slate-400 dark:text-slate-500"><?= htmlspecialchars($po['nama_barang']) ?></div></td>
                                <td class="p-4 text-center font-bold">Rp <?= number_format($po['total_harga'],0,',','.') ?></td>
                                <td class="p-4 text-center text-xs text-slate-400 dark:text-slate-500 font-bold"><?= date('d M Y', strtotime($po['payment_completed_at'])) ?></td>
                                <td class="p-4 text-right"><span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase bg-emerald-50 text-emerald-700"><i class="fa-solid fa-check mr-1"></i> Lunas</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
</div>
                </div>
                <?php else: ?>
                <div class="bg-white dark:bg-[#1e293b] p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-receipt text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 dark:text-slate-500 font-bold">Belum ada riwayat pembayaran.</p>
                </div>
                <?php endif; ?>
            </div>

            <?php if(mysqli_num_rows($query_ready) == 0 && mysqli_num_rows($query_done) == 0): ?>
            <div class="bg-white dark:bg-[#1e293b] p-16 rounded-[2.5rem] border shadow-sm text-center">
                <i class="fa-solid fa-money-check-dollar text-7xl text-slate-100 mb-6 block"></i>
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Tidak Ada Pembayaran Pending</h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm">Lakukan 3-Way Matching terlebih dahulu di halaman sebelumnya.</p>
            </div>
            <?php endif; ?>

            <!-- Floating Action Button for Bulk Payment -->
            <div x-show="selectedPOs.length > 0" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-10"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-10"
                 class="fixed bottom-8 left-1/2 -translate-x-1/2 md:ml-32 z-50">
                <div class="bg-slate-900 text-white rounded-[2rem] p-2 pr-6 shadow-2xl flex items-center gap-4 border border-slate-700">
                    <div class="bg-red-500 text-white w-12 h-12 rounded-full flex items-center justify-center font-black text-lg shadow-inner">
                        <span x-text="selectedPOs.length"></span>
                    </div>
                    <div>
                        <p class="font-bold text-sm">PO Terpilih</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest">Siap dirilis</p>
                    </div>
                    <button :disabled="submitting" @click="
                        if(confirm('Rilis pembayaran untuk ' + selectedPOs.length + ' PO terpilih?')) {
                            submitting = true;
                            let fd = new FormData();
                            fd.append('po_ids', JSON.stringify(selectedPOs));
                            fetch('process_bulk_payment.php', {method:'POST', body:fd})
                            .then(r=>r.json())
                            .then(d=>{
                                alert(d.message);
                                if(d.success) location.reload();
                                else submitting=false;
                            })
                            .catch(()=>{alert('Error koneksi');submitting=false;});
                        }
                    " class="ml-4 bg-white text-slate-900 hover:bg-emerald-400 hover:text-slate-900 px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-sm flex items-center gap-2">
                        <span x-show="!submitting"><i class="fa-solid fa-paper-plane"></i> Bayar Massal</span>
                        <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin"></i> Proses...</span>
                    </button>
                </div>
            </div>

        </main>
    </div>
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>