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
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_finance.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Management Pembayaran</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Proses Pelunasan PO</p>
                </div>
                <div class="flex items-center gap-3">
                    <?php if(mysqli_num_rows($query_ready) > 0): ?>
                    <span class="bg-amber-500 text-white text-xs font-black px-3 py-1 rounded-full animate-pulse"><?= mysqli_num_rows($query_ready) ?> Siap Bayar</span>
                    <?php endif; ?>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">F</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- PO Siap Bayar -->
            <?php if(mysqli_num_rows($query_ready) > 0): ?>
            <div>
                <h3 class="text-xs font-black text-amber-600 uppercase tracking-widest mb-4"><i class="fa-solid fa-money-check-dollar mr-1"></i> Menunggu Pembayaran</h3>
                <div class="space-y-3">
                    <?php while($po = mysqli_fetch_assoc($query_ready)): ?>
                    <div class="flex items-center justify-between p-6 bg-white border rounded-2xl group hover:border-red-500 transition-all shadow-sm">
                        <div>
                            <p class="font-black text-slate-800 text-lg"><?= htmlspecialchars($po['po_number']) ?></p>
                            <p class="text-sm text-slate-400 font-medium">
                                <?= htmlspecialchars($po['nama_perusahaan']) ?> — <?= htmlspecialchars($po['nama_barang']) ?>
                                <span class="text-slate-300 mx-2">|</span>
                                <span class="text-slate-700 font-bold">Rp <?= number_format($po['total_harga'],0,',','.') ?></span>
                            </p>
                            <p class="text-[10px] text-slate-400 font-bold mt-1">
                                <i class="fa-solid fa-file-invoice mr-1"></i> Invoice: <?= htmlspecialchars($po['invoice_number'] ?? '-') ?>
                                <span class="mx-2">|</span>
                                <i class="fa-solid fa-check-double mr-1 text-green-500"></i> Matched: <?= date('d M Y', strtotime($po['payment_matched_at'])) ?>
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
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Riwayat Pembayaran</h3>
                <?php if(mysqli_num_rows($query_done) > 0): ?>
                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead><tr class="bg-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-widest"><th class="p-4">No PO</th><th class="p-4">Vendor & Barang</th><th class="p-4 text-center">Nilai</th><th class="p-4 text-center">Tgl Bayar</th><th class="p-4 text-right">Status</th></tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($po = mysqli_fetch_assoc($query_done)): ?>
                            <tr class="hover:bg-green-50/30 transition">
                                <td class="p-4 font-bold text-slate-700"><?= htmlspecialchars($po['po_number']) ?></td>
                                <td class="p-4"><div class="text-slate-800 font-bold"><?= htmlspecialchars($po['nama_perusahaan']) ?></div><div class="text-[10px] text-slate-400"><?= htmlspecialchars($po['nama_barang']) ?></div></td>
                                <td class="p-4 text-center font-bold">Rp <?= number_format($po['total_harga'],0,',','.') ?></td>
                                <td class="p-4 text-center text-xs text-slate-400 font-bold"><?= date('d M Y', strtotime($po['payment_completed_at'])) ?></td>
                                <td class="p-4 text-right"><span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase bg-emerald-50 text-emerald-700"><i class="fa-solid fa-check mr-1"></i> Lunas</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-white p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-receipt text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 font-bold">Belum ada riwayat pembayaran.</p>
                </div>
                <?php endif; ?>
            </div>

            <?php if(mysqli_num_rows($query_ready) == 0 && mysqli_num_rows($query_done) == 0): ?>
            <div class="bg-white p-16 rounded-[2.5rem] border shadow-sm text-center">
                <i class="fa-solid fa-money-check-dollar text-7xl text-slate-100 mb-6 block"></i>
                <h3 class="font-black text-slate-800 text-xl mb-2">Tidak Ada Pembayaran Pending</h3>
                <p class="text-slate-400 text-sm">Lakukan 3-Way Matching terlebih dahulu di halaman sebelumnya.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>