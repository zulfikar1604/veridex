<?php
session_start();
require 'database.php';

// 1. Proteksi Internal: Memastikan hanya Manager Procurement yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager Procurement') {
    header("Location: login.php");
    die();
}

$active = 'master_user';

// 2. Query Data Users
$query_users = mysqli_query($conn, "
    SELECT * FROM users 
    ORDER BY id ASC
") or die(mysqli_error($conn));

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Data User | Veridex</title>
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
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Master Data User</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Pengelolaan User Sistem & Akuntabilitas</p>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 pt-4">
            <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'sukses'): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                  <p class="font-bold">Sukses!</p>
                  <p>Data user berhasil ditambahkan.</p>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Daftar Pengguna Sistem</h3>
                    <div x-data="{ open: false }">
                        <button @click="open = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-md shadow-red-500/30">
                            <i class="fa-solid fa-plus mr-2"></i> Tambah User Baru
                        </button>
                        
                        <!-- Modal Tambah User -->
                        <div x-show="open" x-cloak class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50">
                            <div @click.away="open = false" class="bg-white p-8 rounded-2xl w-full max-w-md shadow-2xl">
                                <h3 class="text-xl font-black mb-4">Tambah Pengguna Baru</h3>
                                <form action="proses_tambah_user.php" method="POST" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Username</label>
                                        <input type="text" name="username" required class="w-full border border-slate-300 p-3 rounded-lg focus:outline-red-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Password</label>
                                        <input type="password" name="password" required class="w-full border border-slate-300 p-3 rounded-lg focus:outline-red-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Role</label>
                                        <select name="role" class="w-full border border-slate-300 p-3 rounded-lg focus:outline-red-500">
                                            <option value="Manager Procurement">Manager Procurement</option>
                                            <option value="Procurement">Procurement</option>
                                            <option value="Gudang">Gudang</option>
                                            <option value="Finance">Finance</option>
                                            <option value="Vendor">Vendor</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-4 mt-6">
                                        <button type="button" @click="open = false" class="flex-1 bg-slate-200 text-slate-800 font-bold py-3 rounded-lg hover:bg-slate-300 transition">Batal</button>
                                        <button type="submit" class="flex-1 bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition">Simpan</button>
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
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">ID</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">USERNAME</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">ROLE</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">VENDOR ID (JIKA VENDOR)</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php while($row = mysqli_fetch_assoc($query_users)): ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="py-4 px-6 font-bold text-slate-800"><?= $row['id'] ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-800"><?= htmlspecialchars($row['username']) ?></td>
                                    <td class="py-4 px-6 text-slate-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                          <?= htmlspecialchars($row['role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-500"><?= $row['vendor_id'] ? $row['vendor_id'] : '-' ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
