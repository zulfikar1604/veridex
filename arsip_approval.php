<?php
session_start();
// Proteksi: Hanya Manager dan Procurement yang bisa akses arsip ini
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

require 'database.php'; // Menggunakan koneksi mysqli $conn
$active = 'arsip_approval';

// Query mengambil data PO yang sudah disetujui (contoh status 'Approved')
// Query yang sudah disesuaikan dengan nama kolom di database kamu
$query_arsip = mysqli_query($conn, "
    SELECT 
        p.id, 
        p.po_number, 
        v.nama_perusahaan, 
        p.nilai_kontrak, 
        p.created_at, 
        u.username as created_by
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    JOIN users u ON p.procurement_id = u.id
    WHERE p.status = 'Approved'
    ORDER BY p.created_at DESC
") or handle_db_error($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Approval | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header Section -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Approval Archives</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">History of Validated Documents</p>
                </div>
                <div class="bg-white px-4 py-2 rounded-2xl shadow-sm border border-slate-100">
                    <span class="text-[10px] font-black text-slate-400 uppercase block">Total Arsip</span>
                    <span class="text-xl font-black text-red-600"><?= mysqli_num_rows($query_arsip) ?></span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Mockup -->
        <div class="px-8 mb-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex gap-4">
                <input type="text" placeholder="Cari Nomor PO atau Vendor..." class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-red-500">
                <button class="bg-slate-800 text-white px-6 py-2 rounded-xl text-sm font-bold">Filter Tanggal</button>
            </div>
        </div>

        <!-- Table Section -->
        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            <th class="p-6">ID Dokumen</th>
                            <th class="p-6">Vendor Partner</th>
                            <th class="p-6">Nilai Kontrak</th>
                            <th class="p-6">Tanggal Approval</th>
                            <th class="p-6">Validator</th>
                            <th class="p-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = mysqli_fetch_assoc($query_arsip)): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-6">
                                <span class="font-black text-slate-700">#<?= $row['nomor_po'] ?></span>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800"><?= $row['nama_perusahaan'] ?></div>
                            </td>
                            <td class="p-6">
                                <div class="text-sm font-bold text-slate-600">Rp <?= number_format($row['nilai_kontrak'], 0, ',', '.') ?></div>
                            </td>
                            <td class="p-6 text-sm text-slate-500">
                                <?= date('d M Y', strtotime($row['tanggal_approval'])) ?>
                            </td>
                            <td class="p-6">
                                <div class="flex items-center text-xs font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full w-fit">
                                    <i class="fa-solid fa-circle-check mr-2"></i> <?= $row['approved_by'] ?>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <button class="text-slate-400 hover:text-red-500 transition">
                                    <i class="fa-solid fa-file-pdf text-xl"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
