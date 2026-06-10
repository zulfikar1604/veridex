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

<?php
$page_title = "Master Data Vendor";
$page_subtitle = "Evaluasi, Kualifikasi & Kinerja Rekanan";
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8" x-data="{ 
            detailModal: false, 
            vendor: {},
            openDetail(v) {
                this.vendor = v;
                this.detailModal = true;
            },
            updateStatus(id, action) {
                if(!confirm('Anda yakin ingin ' + action + ' vendor ini?')) return;
                let fd = new FormData();
                fd.append('vendor_id', id);
                fd.append('action', action);
                fetch('process_vendor_status.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => { alert(d.message); if(d.success) location.reload(); });
            }
        }">
            <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'sukses'): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-xl shadow-sm" role="alert">
                  <p class="font-bold"><i class="fa-solid fa-check-circle mr-2"></i> Sukses!</p>
                  <p>Data vendor berhasil ditambahkan ke dalam direktori.</p>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900">
                    <h3 class="font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest text-sm">Daftar Vendor Terdaftar</h3>
                    
                    <div x-data="{ open: false }">
                        <button @click="open = true" class="bg-slate-800 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-md">
                            <i class="fa-solid fa-building-circle-check mr-2"></i> Input Manual Vendor
                        </button>
                        
                        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex justify-end">
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
                                    <div>
                                        <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Registrasi Vendor</h3>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-widest mt-1">Input Manual</p>
                                    </div>
                                    <button type="button" @click="open = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-red-500 hover:border-red-200 dark:hover:border-red-900 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                
                                <div class="flex-1 overflow-y-auto p-6">
                                    <form action="proses_tambah_vendor.php" method="POST" class="space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Nama Perusahaan (PT/CV)</label>
                                            <input type="text" name="nama_perusahaan" required placeholder="Contoh: PT. Logistik Indonesia" class="w-full">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Kategori Spesialisasi</label>
                                            <input type="text" name="spesialisasi" required placeholder="Contoh: IT, Sparepart, Alat Tulis" class="w-full">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Status Kemitraan</label>
                                            <select name="status" class="w-full">
                                                <option value="Aktif">Aktif (Approved)</option>
                                                <option value="Non-Aktif">Non-Aktif</option>
                                            </select>
                                        </div>
                                        
                                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                                            <button type="submit" class="w-full bg-red-600 text-white font-black py-3.5 rounded-xl uppercase text-xs tracking-widest hover:bg-red-700 transition shadow-lg shadow-red-500/30">Simpan Vendor</button>
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
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">ID / Kode</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Nama Perusahaan</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Spesialisasi</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest">Status</th>
                                <th class="py-4 px-6 text-xs font-bold uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php while($row = mysqli_fetch_assoc($query_vendors)): ?>
                                <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:bg-slate-900 transition-colors">
                                    <td class="py-4 px-6 font-bold text-slate-400 dark:text-slate-500">VND-<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-800 dark:text-white">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-slate-200 flex items-center justify-center text-slate-500 dark:text-slate-400 dark:text-slate-500 font-black text-xs">
                                                <i class="fa-solid fa-industry"></i>
                                            </div>
                                            <?= htmlspecialchars($row['nama_perusahaan']) ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600 dark:text-slate-300 font-medium">
                                        <?= htmlspecialchars($row['spesialisasi'] ?? 'Umum') ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php if($row['status'] == 'Aktif' || $row['status'] == 'Approved'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-green-100 text-green-700 border border-green-200">
                                                <i class="fa-solid fa-check mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php elseif($row['status'] == 'Pending'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-amber-100 text-amber-600 border border-amber-200">
                                                <i class="fa-solid fa-clock mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php elseif($row['status'] == 'Rejected' || $row['status'] == 'Non-Aktif'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-red-100 text-red-600 border border-red-200">
                                                <i class="fa-solid fa-xmark mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-slate-200 text-slate-600 dark:text-slate-300 border border-slate-300">
                                                <i class="fa-solid fa-ban mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <button @click="openDetail(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-500 hover:text-blue-700 font-bold text-xs uppercase tracking-widest">Detail <i class="fa-solid fa-chevron-right ml-1"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if(mysqli_num_rows($query_vendors) == 0): ?>
                                <tr>
                                    <td colspan="5" class="py-16 text-center">
                                        <i class="fa-solid fa-building-circle-xmark text-5xl text-slate-200 mb-4 block"></i>
                                        <span class="text-slate-400 dark:text-slate-500 italic font-medium">Belum ada data vendor yang terdaftar di sistem.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
</div>
                </div>
            </div>
            
            <!-- Slide-Over Detail Vendor -->
            <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 flex justify-end">
                <div x-show="detailModal" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="detailModal = false"></div>
                
                <div x-show="detailModal" 
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="relative w-full max-w-xl h-full bg-white dark:bg-[#1e293b] shadow-2xl flex flex-col z-50 border-l border-slate-100 dark:border-slate-800">
                     
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 dark:bg-slate-900/50 shrink-0">
                        <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Detail Vendor</h3>
                        <button @click="detailModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-red-500 hover:border-red-200 dark:hover:border-red-900 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <div class="flex items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-6">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 dark:text-slate-500 flex items-center justify-center text-3xl shadow-inner border border-slate-200 dark:border-slate-700">
                                <i class="fa-solid fa-industry"></i>
                            </div>
                            <div>
                                <h4 class="text-2xl font-black text-slate-800 dark:text-white" x-text="vendor.nama_perusahaan"></h4>
                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-widest mt-2 inline-block" x-text="vendor.spesialisasi || 'Umum'"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Status Kemitraan</p>
                                <span class="font-bold text-sm" 
                                    :class="{'text-green-600 dark:text-green-400': vendor.status === 'Approved' || vendor.status === 'Aktif', 'text-amber-500 dark:text-amber-400': vendor.status === 'Pending', 'text-red-500 dark:text-red-400': vendor.status === 'Rejected'}"
                                    x-text="vendor.status"></span>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Email Perusahaan</p>
                                <p class="font-bold text-slate-700 dark:text-slate-300 text-sm" x-text="vendor.email || '-'"></p>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Alamat Lengkap</p>
                                <p class="font-medium text-slate-600 dark:text-slate-400 dark:text-slate-500 text-sm" x-text="vendor.alamat || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Nama PIC</p>
                                <p class="font-bold text-slate-700 dark:text-slate-300 text-sm" x-text="vendor.pic_nama || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Kontak PIC</p>
                                <p class="font-bold text-slate-700 dark:text-slate-300 text-sm" x-text="vendor.pic_kontak || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">NIB</p>
                                <p class="font-bold text-slate-700 dark:text-slate-300 text-sm" x-text="vendor.nib || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">NPWP</p>
                                <p class="font-bold text-slate-700 dark:text-slate-300 text-sm" x-text="vendor.npwp || '-'"></p>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Dokumen Legalitas</p>
                                <a :href="'print_profil_vendor.php?id=' + vendor.id" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 hover:bg-red-600 hover:text-white text-red-600 dark:text-red-400 rounded-xl text-xs font-bold transition-all shadow-sm">
                                    <i class="fa-solid fa-file-contract"></i> Cetak Profil Vendor & Legalitas
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 dark:bg-slate-900/50 flex justify-end gap-3 shrink-0" x-show="vendor.status === 'Pending'">
                        <button @click="updateStatus(vendor.id, 'Reject')" class="px-6 py-3.5 bg-white dark:bg-slate-800 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-red-50 dark:hover:bg-red-900/20 transition flex-1 text-center">Tolak Vendor</button>
                        <button @click="updateStatus(vendor.id, 'Approve')" class="px-6 py-3.5 bg-green-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-green-700 shadow-lg shadow-green-500/30 transition flex-1 text-center">Setujui Kemitraan</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                sidebar.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('sidebar-open');
                if (overlay) overlay.classList.toggle('overlay-open');
            }
        }
    </script>

</body>
</html>