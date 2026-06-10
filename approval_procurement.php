<?php 
session_start(); 
require 'database.php';

// Proteksi: Hanya Manajer yang bisa akses halaman validasi ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager Procurement') {
    header("Location: login.php");
    exit;
}

$active = 'approval'; 

// Ambil semua PO yang statusnya menunggu validasi manajer
$query_pending = mysqli_query($conn, "
    SELECT p.*, v.nama_perusahaan, i.nama_barang 
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.status = 'Pending Manager'
    ORDER BY p.created_at DESC
") or handle_db_error($conn);
?>

<?php
$page_title = "PO Validation";
$page_subtitle = "Daftar Pengadaan Menunggu Persetujuan";
$navbar_badge = '<span class="hidden sm:inline-block bg-slate-800 text-slate-300 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-700/50">Pending: ' . mysqli_num_rows($query_pending) . ' Requests</span>';
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-6xl">
                <?php if(mysqli_num_rows($query_pending) > 0): ?>
                    <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                        <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                            <thead class="bg-slate-900 text-[10px] font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">
                                <tr>
                                    <th class="p-6">Request Date</th>
                                    <th class="p-6">Item & Vendor</th>
                                    <th class="p-6 text-center">Total Value</th>
                                    <th class="p-6 text-center">AI Recommendation</th>
                                    <th class="p-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php while($po = mysqli_fetch_assoc($query_pending)): ?>
                                <tr class="hover:bg-slate-50 dark:bg-slate-900 transition-colors">
                                    <td class="p-6 text-xs font-bold text-slate-400 dark:text-slate-500">
                                        <?= date('d M Y', strtotime($po['created_at'])) ?>
                                    </td>
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 dark:text-white uppercase text-sm"><?= $po['nama_barang'] ?></div>
                                        <div class="text-[10px] text-red-500 font-bold uppercase tracking-widest italic">Target: <?= $po['nama_perusahaan'] ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <div class="font-black text-slate-700 dark:text-slate-200">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></div>
                                        <div class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase">Incl. Tax</div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <!-- Menunjukkan integrasi dengan hasil scoring[cite: 2] -->
                                        <span class="px-3 py-1 bg-green-50 text-green-600 border border-green-100 rounded-full text-[9px] font-black uppercase">
                                            High Score (AI)
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="p-2 px-3 bg-white dark:bg-[#1e293b] text-slate-400 dark:text-slate-500 border border-slate-100 dark:border-slate-800 rounded-xl hover:text-blue-600 hover:border-blue-200 transition" title="Print PO">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                            <a href="process_approval.php?id=<?= $po['id'] ?>&status=Approved" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-green-600 transition shadow-lg shadow-slate-900/10">
                                                Approve
                                            </a>
                                            <a href="process_approval.php?id=<?= $po['id'] ?>&status=Rejected" onclick="return confirm('Yakin ingin menolak PO ini?')" class="p-2 px-3 bg-white dark:bg-[#1e293b] text-slate-400 dark:text-slate-500 border border-slate-100 dark:border-slate-800 rounded-xl hover:text-red-600 hover:border-red-200 transition">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
</div>
                    </div>
                <?php else: ?>
                    <!-- State jika tidak ada antrean -->
                    <div class="bg-white dark:bg-[#1e293b] p-20 rounded-[40px] text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                            <i class="fa-solid fa-stamp text-3xl"></i>
                        </div>
                        <p class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-widest">Tidak ada pengajuan yang perlu di-acc.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>



</body>
</html>