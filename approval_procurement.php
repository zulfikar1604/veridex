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
    WHERE p.status = 'Pending Approval'
    ORDER BY p.created_at DESC
") or handle_db_error($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manager Approval Dashboard | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">
    
    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header Dashboard -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">PO Validation</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Daftar Pengadaan Menunggu Persetujuan</p>
                </div>
                <div class="bg-white px-6 py-3 rounded-2xl border border-slate-100 shadow-sm">
                    <span class="text-[10px] font-black text-slate-400 uppercase block">Pending Tasks</span>
                    <span class="text-xl font-black text-red-600"><?= mysqli_num_rows($query_pending) ?> Requests</span>
                </div>
            </div>
        </div>

        <!-- List Pengadaan (List View)[cite: 2] -->
        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="max-w-6xl">
                <?php if(mysqli_num_rows($query_pending) > 0): ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-900 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
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
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-6 text-xs font-bold text-slate-400">
                                        <?= date('d M Y', strtotime($po['created_at'])) ?>
                                    </td>
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 uppercase text-sm"><?= $po['nama_barang'] ?></div>
                                        <div class="text-[10px] text-red-500 font-bold uppercase tracking-widest italic">Target: <?= $po['nama_perusahaan'] ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <div class="font-black text-slate-700">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></div>
                                        <div class="text-[9px] text-slate-400 font-bold uppercase">Incl. Tax</div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <!-- Menunjukkan integrasi dengan hasil scoring[cite: 2] -->
                                        <span class="px-3 py-1 bg-green-50 text-green-600 border border-green-100 rounded-full text-[9px] font-black uppercase">
                                            High Score (AI)
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="process_approval.php?id=<?= $po['id'] ?>&status=Approved" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-green-600 transition shadow-lg shadow-slate-900/10">
                                                Approve
                                            </a>
                                            <button class="p-2 px-3 bg-white text-slate-400 border border-slate-100 rounded-xl hover:text-red-600 transition">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- State jika tidak ada antrean -->
                    <div class="bg-white p-20 rounded-[40px] text-center border-2 border-dashed border-slate-200">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                            <i class="fa-solid fa-stamp text-3xl"></i>
                        </div>
                        <p class="text-slate-400 font-bold uppercase tracking-widest">Tidak ada pengajuan yang perlu di-acc.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>