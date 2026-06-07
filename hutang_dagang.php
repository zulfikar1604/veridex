<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y');

// Ambil data invoice (Account Payable)
$query_ap = mysqli_query($conn, "
    SELECT inv.*, p.po_number, i.nama_barang, v.nama_perusahaan 
    FROM invoices inv
    JOIN purchase_orders p ON inv.po_id = p.id
    JOIN inventory i ON inv.inventory_id = i.id
    JOIN vendors v ON inv.vendor_id = v.id
    ORDER BY inv.status DESC, inv.created_at DESC
") or handle_db_error($conn);

// Hitung total hutang yang Unpaid
$total_hutang = 0;
$count_unpaid = 0;
$ap_list = [];
while($row = mysqli_fetch_assoc($query_ap)) {
    $ap_list[] = $row;
    if($row['status'] === 'Unpaid') {
        $total_hutang += $row['total_tagihan'];
        $count_unpaid++;
    }
}

include 'header_meta.php';
?>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Accounts Payable";
    $page_subtitle = "Verifikasi & Pemantauan Hutang Dagang";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_finance.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-red-500 relative overflow-hidden flex flex-col justify-center min-h-[140px]">
                    <i class="fa-solid fa-file-invoice-dollar absolute -right-4 -bottom-4 text-9xl text-red-50 opacity-50 pointer-events-none"></i>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-red-600 uppercase tracking-widest mb-1">Total Hutang Berjalan</p>
                        <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2">
                            Rp <?= number_format($total_hutang, 0, ',', '.') ?>
                        </h3>
                        <p class="text-xs font-bold text-red-500 mt-2 italic"><?= $count_unpaid ?> Tagihan belum dibayar</p>
                    </div>
                </div>
            </div>

            <!-- AP List -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Daftar Invoice Masuk</h3>
                </div>

                <div class="overflow-x-auto">
                    <div class="overflow-x-auto w-full">
<table class="w-full text-left border-collapse">
                        <thead class="bg-[#111827] text-white">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">No Invoice / PO</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest w-[30%]">Vendor & Barang</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-center">Jatuh Tempo</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-right">Nilai Tagihan</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-center">Status</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if(!empty($ap_list)): ?>
                                <?php foreach($ap_list as $ap): 
                                    $is_overdue = ($ap['status'] === 'Unpaid' && strtotime($ap['tanggal_jatuh_tempo']) < time());
                                    $row_bg = $ap['status'] === 'Paid' ? 'bg-green-50/20' : ($is_overdue ? 'bg-red-50/30' : 'hover:bg-slate-50');
                                ?>
                                <tr class="border-b border-slate-100 <?= $row_bg ?> transition-colors">
                                    <td class="py-5 px-6">
                                        <div class="font-black text-slate-800"><?= htmlspecialchars($ap['invoice_number']) ?></div>
                                        <div class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest">PO: <?= htmlspecialchars($ap['po_number']) ?></div>
                                    </td>
                                    <td class="py-5 px-6">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($ap['nama_perusahaan']) ?></div>
                                        <div class="text-slate-500 text-xs mt-1"><?= htmlspecialchars($ap['nama_barang']) ?> (<?= $ap['jumlah_barang'] ?> unit)</div>
                                    </td>
                                    <td class="py-5 px-6 text-center">
                                        <div class="font-bold <?= $is_overdue ? 'text-red-600' : 'text-slate-700' ?>">
                                            <?= date('d M Y', strtotime($ap['tanggal_jatuh_tempo'])) ?>
                                        </div>
                                        <?php if($is_overdue): ?>
                                            <div class="text-[9px] font-black text-red-500 uppercase mt-1">Overdue</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-5 px-6 font-black text-slate-800 text-right text-lg">
                                        Rp <?= number_format($ap['total_tagihan'], 0, ',', '.') ?>
                                    </td>
                                    <td class="py-5 px-6 text-center">
                                        <?php if($ap['status'] === 'Paid'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-wider">
                                                <i class="fa-solid fa-check-circle"></i> Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-[10px] font-black uppercase tracking-wider">
                                                <i class="fa-solid fa-clock"></i> Unpaid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-5 px-6 text-right">
                                        <a href="print_invoice.php?id=<?= $ap['id'] ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 hover:bg-blue-600 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                            <i class="fa-solid fa-print"></i> Cetak
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400 italic">Belum ada tagihan dari vendor.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
</div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
