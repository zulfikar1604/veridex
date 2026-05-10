<?php
session_start();
require 'database.php';

// 1. Proteksi Internal: Memastikan hanya divisi Procurement yang bisa akses (Manager & Staff)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    die();
}

$active = 'master_vendor';

// 2. Query Data Vendor
$query_vendors = mysqli_query($conn, "
    SELECT * FROM vendors 
    ORDER BY nama_perusahaan ASC
") or die(mysqli_error($conn));

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Data Vendor | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Master Data Vendor</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Direktori & Manajemen Mitra Pemasok</p>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 pt-4">
            <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'sukses'): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-xl shadow-sm" role="alert">
                  <p class="font-bold"><i class="fa-solid fa-check-circle mr-2"></i> Sukses!</p>
                  <p>Data vendor berhasil ditambahkan ke dalam direktori.</p>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Daftar Vendor Terdaftar</h3>
                    
                    <div x-data="{ open: false }">
                        <button @click="open = true" class="bg-slate-800 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-md">
                            <i class="fa-solid fa-building-circle-check mr-2"></i> Input Manual Vendor
                        </button>
                        
                        <div x-show="open" x-cloak class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50">
                            <div @click.away="open = false" class="bg-white p-8 rounded-2xl w-full max-w-lg shadow-2xl">
                                <h3 class="text-xl font-black mb-1 text-slate-800 uppercase tracking-tight">Registrasi Vendor Manual</h3>
                                <p class="text-xs text-slate-400 mb-4 font-bold">Gunakan form ini jika vendor tidak mendaftar melalui sistem.</p>
                                
                                <form action="proses_tambah_vendor.php" method="POST" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Nama Perusahaan (PT/CV)</label>
                                        <input type="text" name="nama_perusahaan" required placeholder="Contoh: PT. Logistik Indonesia" class="w-full border border-slate-300 p-3 rounded-lg focus:outline-red-500 font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Kategori Spesialisasi</label>
                                        <input type="text" name="spesialisasi" required placeholder="Contoh: IT, Sparepart, Alat Tulis" class="w-full border border-slate-300 p-3 rounded-lg focus:outline-red-500 font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Status Kemitraan</label>
                                        <select name="status" class="w-full border border-slate-300 p-3 rounded-lg focus:outline-red-500 font-medium text-slate-700">
                                            <option value="Aktif">Aktif (Approved)</option>
                                            <option value="Non-Aktif">Non-Aktif</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-4 mt-6">
                                        <button type="button" @click="open = false" class="flex-1 bg-slate-200 text-slate-800 font-bold py-3 rounded-lg hover:bg-slate-300 transition">Batal</button>
                                        <button type="submit" class="flex-1 bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition shadow-lg shadow-red-500/30">Simpan Vendor</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#111827] text-white">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">ID / Kode</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Nama Perusahaan</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Spesialisasi</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Status</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php while($row = mysqli_fetch_assoc($query_vendors)): ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="py-4 px-6 font-bold text-slate-400">VND-<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-800">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-slate-200 flex items-center justify-center text-slate-500 font-black text-xs">
                                                <i class="fa-solid fa-industry"></i>
                                            </div>
                                            <?= htmlspecialchars($row['nama_perusahaan']) ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600 font-medium">
                                        <?= htmlspecialchars($row['spesialisasi'] ?? 'Umum') ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php if($row['status'] == 'Aktif' || $row['status'] == 'Approved'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-green-100 text-green-700 border border-green-200">
                                                <i class="fa-solid fa-check mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-slate-200 text-slate-600 border border-slate-300">
                                                <i class="fa-solid fa-ban mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <button class="text-blue-500 hover:text-blue-700 font-bold text-xs uppercase tracking-widest">Detail <i class="fa-solid fa-chevron-right ml-1"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if(mysqli_num_rows($query_vendors) == 0): ?>
                                <tr>
                                    <td colspan="5" class="py-16 text-center">
                                        <i class="fa-solid fa-building-circle-xmark text-5xl text-slate-200 mb-4 block"></i>
                                        <span class="text-slate-400 italic font-medium">Belum ada data vendor yang terdaftar di sistem.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </main>
</body>
</html>