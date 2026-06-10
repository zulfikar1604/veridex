<?php
session_start();
require 'database.php';

// 1. Proteksi Internal: Memastikan hanya Manager Procurement yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager Procurement') {
    header("Location: login.php");
    die();
}

$active = 'master_user';

// 2. Query Data Users dengan Hierarki Jabatan
// Menggunakan FIELD() agar Manager selalu muncul di atas Staf
$query_users = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE role IN ('Manager Procurement', 'Procurement')
    ORDER BY FIELD(role, 'Manager Procurement', 'Procurement') ASC, id ASC
") or die(mysqli_error($conn));

?>

<?php
$page_title = "Master Data User";
$page_subtitle = "Pengelolaan User Sistem & Akuntabilitas";
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Master Data User";
    $page_subtitle = "Pengelolaan User Sistem & Akuntabilitas";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'sukses'): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-xl shadow-sm" role="alert">
                  <p class="font-bold"><i class="fa-solid fa-check-circle mr-2"></i> Sukses!</p>
                  <p>Data user berhasil ditambahkan ke dalam tim.</p>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900">
                    <h3 class="font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest text-sm">Daftar Hierarki Tim Pengadaan</h3>
                    <div x-data="{ open: false }">
                        <button @click="open = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-md shadow-red-500/30">
                            <i class="fa-solid fa-user-plus mr-2"></i> Tambah Anggota
                        </button>
                        
                        <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex justify-end">
                            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="open = false"></div>
                            
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-300 transform"
                                 x-transition:enter-start="translate-x-full"
                                 x-transition:enter-end="translate-x-0"
                                 x-transition:leave="transition ease-in duration-200 transform"
                                 x-transition:leave-start="translate-x-0"
                                 x-transition:leave-end="translate-x-full"
                                 class="relative w-full max-w-md h-full bg-white dark:bg-[#1e293b] shadow-2xl flex flex-col z-50 border-l border-slate-100 dark:border-slate-800">
                                 
                                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 dark:bg-slate-900/50 shrink-0">
                                    <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Tambah Anggota Baru</h3>
                                    <button type="button" @click="open = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-red-500 hover:border-red-200 dark:hover:border-red-900 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                
                                <div class="flex-1 overflow-y-auto p-6">
                                    <form action="proses_tambah_user.php" method="POST" class="space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Username</label>
                                            <input type="text" name="username" required class="w-full">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Password</label>
                                            <input type="password" name="password" required class="w-full">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Role Jabatan</label>
                                            <select name="role" class="w-full">
                                                <option value="Manager Procurement">Manager Procurement (KADIV)</option>
                                                <option value="Procurement">Staf Procurement</option>
                                            </select>
                                        </div>
                                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                                            <button type="submit" class="w-full bg-red-600 text-white font-black py-3.5 rounded-xl uppercase tracking-widest text-xs hover:bg-red-700 transition shadow-lg shadow-red-500/30">Daftarkan Anggota</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div class="overflow-x-auto w-full">
<table class="w-full text-left border-collapse">
                        <thead class="bg-[#111827] text-white">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest w-24">No.</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Username / Nama Akun</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-center">Tingkat Jabatan</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($query_users)): 
                            ?>
                                <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:bg-slate-900 transition-colors">
                                    <td class="py-4 px-6 font-bold text-slate-400 dark:text-slate-500">#<?= $no++ ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-800 dark:text-white">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full <?= $row['role'] == 'Manager Procurement' ? 'bg-red-500 text-white' : 'bg-slate-200 text-slate-500 dark:text-slate-400 dark:text-slate-500' ?> flex items-center justify-center font-black shadow-sm text-sm">
                                                <?= strtoupper(substr($row['username'], 0, 1)) ?>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-slate-800 dark:text-white"><?= htmlspecialchars($row['username']) ?></span>
                                                <span class="text-[9px] text-slate-400 dark:text-slate-500 uppercase tracking-widest">ID User: <?= $row['id'] ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if($row['role'] == 'Manager Procurement'): ?>
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black tracking-widest uppercase bg-red-600 text-white shadow-md shadow-red-200">
                                                <i class="fa-solid fa-shield-halved mr-2 text-[12px]"></i> <?= htmlspecialchars($row['role']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black tracking-widest uppercase bg-slate-800 text-white shadow-md shadow-slate-200">
                                                <i class="fa-solid fa-user-gear mr-2 text-[12px]"></i> <?= htmlspecialchars($row['role']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if(mysqli_num_rows($query_users) == 0): ?>
                                <tr>
                                    <td colspan="3" class="py-16 text-center">
                                        <i class="fa-solid fa-users-viewfinder text-5xl text-slate-200 mb-4 block"></i>
                                        <span class="text-slate-400 dark:text-slate-500 italic font-medium">Data tim belum tersedia. Silakan tambah anggota baru.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
</div>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex gap-4 items-center">
                <div class="w-12 h-12 bg-white dark:bg-[#1e293b] rounded-xl flex items-center justify-center text-blue-600 shadow-sm">
                    <i class="fa-solid fa-circle-info text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-blue-800 uppercase tracking-widest">Informasi Hierarki</p>
                    <p class="text-[11px] text-blue-600 leading-relaxed font-medium mt-0.5">Daftar ini diurutkan berdasarkan tingkat otoritas jabatan (Manager ke Staf). Hanya personil yang terdaftar di sini yang memiliki akses ke modul strategis Veridex.</p>
                </div>
            </div>
        </main>
    </div>



</body>
</html>