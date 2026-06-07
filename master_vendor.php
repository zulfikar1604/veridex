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
    <style>[x-cloak] { display: none !important; }
    <style>
        #main-sidebar { transition: width 0.3s ease, min-width 0.3s ease, transform 0.3s ease; }
        @media (min-width: 768px) {
            #main-sidebar { position: relative !important; transform: translateX(0) !important; width: 280px; min-width: 280px; overflow: hidden; }
            #main-sidebar.sidebar-collapsed { width: 0 !important; min-width: 0 !important; }
            #sidebar-overlay { display: none !important; }
        }
        @media (max-width: 767px) {
            #main-sidebar { position: fixed !important; top: 0; left: 0; bottom: 0; z-index: 50; width: 280px !important; min-width: 280px !important; transform: translateX(-100%); }
            #main-sidebar.sidebar-open { transform: translateX(0); }
            #sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 40; }
            #sidebar-overlay.overlay-open { display: block; }
        }
        .sidebar-toggle-btn { display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;cursor:pointer;transition:background 0.2s,color 0.2s,transform 0.1s;flex-shrink:0; }
        .sidebar-toggle-btn:hover { background:#ef4444;color:#fff;border-color:#ef4444; }
        .sidebar-toggle-btn:active { transform:scale(0.92); }
        .sidebar-close-btn { display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.08);color:#94a3b8;cursor:pointer;transition:background 0.2s,color 0.2s;flex-shrink:0;border:none; }
        .sidebar-close-btn:hover { background:rgba(239,68,68,0.2);color:#fca5a5; }
    </style>
</head>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Master Data Vendor";
    $page_subtitle = "Evaluasi, Kualifikasi & Kinerja Rekanan";
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
                                        <?php elseif($row['status'] == 'Pending'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-amber-100 text-amber-600 border border-amber-200">
                                                <i class="fa-solid fa-clock mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php elseif($row['status'] == 'Rejected' || $row['status'] == 'Non-Aktif'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-red-100 text-red-600 border border-red-200">
                                                <i class="fa-solid fa-xmark mr-1.5"></i> <?= $row['status'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-widest uppercase bg-slate-200 text-slate-600 border border-slate-300">
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
                                        <span class="text-slate-400 italic font-medium">Belum ada data vendor yang terdaftar di sistem.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
</div>
                </div>
            </div>
            
            <!-- Modal Detail Vendor -->
            <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
                <div @click.away="detailModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Detail Vendor</h3>
                        <button @click="detailModal = false" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>
                    
                    <div class="p-8 overflow-y-auto flex-1 space-y-6">
                        <div class="flex items-center gap-4 border-b border-slate-100 pb-6">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center text-3xl shadow-inner">
                                <i class="fa-solid fa-industry"></i>
                            </div>
                            <div>
                                <h4 class="text-2xl font-black text-slate-800" x-text="vendor.nama_perusahaan"></h4>
                                <span class="bg-blue-100 text-blue-700 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-widest mt-2 inline-block" x-text="vendor.spesialisasi || 'Umum'"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status Kemitraan</p>
                                <span class="font-bold text-sm" 
                                    :class="{'text-green-600': vendor.status === 'Approved' || vendor.status === 'Aktif', 'text-amber-500': vendor.status === 'Pending', 'text-red-500': vendor.status === 'Rejected'}"
                                    x-text="vendor.status"></span>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Email Perusahaan</p>
                                <p class="font-bold text-slate-700 text-sm" x-text="vendor.email || '-'"></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Alamat Lengkap</p>
                                <p class="font-medium text-slate-600 text-sm" x-text="vendor.alamat || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nama PIC</p>
                                <p class="font-bold text-slate-700 text-sm" x-text="vendor.pic_nama || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Kontak PIC</p>
                                <p class="font-bold text-slate-700 text-sm" x-text="vendor.pic_kontak || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">NIB</p>
                                <p class="font-bold text-slate-700 text-sm" x-text="vendor.nib || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">NPWP</p>
                                <p class="font-bold text-slate-700 text-sm" x-text="vendor.npwp || '-'"></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Dokumen Legalitas</p>
                                <a :href="'print_profil_vendor.php?id=' + vendor.id" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-red-50 border border-red-100 hover:bg-red-600 hover:text-white text-red-600 rounded-xl text-xs font-bold transition-all shadow-sm">
                                    <i class="fa-solid fa-file-contract"></i> Cetak Profil Vendor & Legalitas
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-end gap-3" x-show="vendor.status === 'Pending'">
                        <button @click="updateStatus(vendor.id, 'Reject')" class="px-6 py-3 bg-white border border-red-200 text-red-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-red-50 transition">Tolak Vendor</button>
                        <button @click="updateStatus(vendor.id, 'Approve')" class="px-6 py-3 bg-green-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-green-700 shadow-lg shadow-green-500/30 transition">Setujui Kemitraan</button>
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