<?php
session_start();
require 'database.php';

// Proteksi Internal
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'buat_po';

// Ambil item yang sudah selesai evaluasi CBE (semua SPH untuk item tersebut statusnya minimal 'Evaluated')
// Tapi ini tricky. Cukup cari item yang SPH-nya ada yang status 'Selected' (sudah digenerate) 
// atau yang siap di-generate (Semua SPH Evaluated).
// Agar gampang, kita join purchase_orders untuk mengecek. 
// Karena di strategic_procurement.php ketika kita klik "Finalize & Generate PO", PO sudah terbuat.
// Halaman ini mungkin untuk melihat PO yang baru dibuat atau yang Pending Manager.

// Query: PO yang dibuat oleh procurement ini dan masih Pending Manager
$proc_id = $_SESSION['user_id'];
$query_po = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'Pending Manager'
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

// Query: SPH yang sudah Selected tapi belum ada PO-nya
$query_ready = mysqli_query($conn, "
    SELECT s.inventory_id, s.vendor_id, s.rfq_id, s.harga_penawaran, 
           s.diskon_jenis, s.diskon_nilai, s.biaya_pengiriman,
           i.nama_barang, v.nama_perusahaan,
           rq.jumlah_kebutuhan, vq.quota_percentage
    FROM sph_submissions s
    JOIN inventory i ON s.inventory_id = i.id
    JOIN vendors v ON s.vendor_id = v.id
    LEFT JOIN rfq_requests rq ON s.rfq_id = rq.id
    LEFT JOIN vendor_quotas vq ON s.inventory_id = vq.inventory_id AND s.vendor_id = vq.vendor_id
    LEFT JOIN purchase_orders po ON po.rfq_id = s.rfq_id AND po.vendor_id = s.vendor_id
    WHERE s.status = 'Selected' AND po.id IS NULL
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php
    $page_title = "Purchase Orders";
    $page_subtitle = "Daftar PO (Pending Approval Manager)";
    $navbar_badge = '<span class="hidden sm:inline-flex items-center gap-2 bg-red-500/10 text-red-500 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border border-red-500/20">Pending PO: <span class="bg-red-500 text-white px-2 py-0.5 rounded-lg">' . mysqli_num_rows($query_po) . '</span></span>';
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8" x-data="{ 
            showModal: false, 
            form: { inventory_id: 0, vendor_id: 0, rfq_id: 0, vendor_name: '', item_name: '', jumlah_pesan: 0, harga_satuan: 0, diskon_jenis: 'Nominal', diskon_nilai: 0, biaya_pengiriman: 0 },
            get totalHarga() { 
                let subtotal = this.form.jumlah_pesan * this.form.harga_satuan;
                let diskon = this.form.diskon_jenis === 'Persentase' ? (this.form.diskon_nilai / 100) * subtotal : parseFloat(this.form.diskon_nilai) || 0;
                return (subtotal - diskon) + (parseFloat(this.form.biaya_pengiriman) || 0);
            },
            openModal(item) {
                this.form.inventory_id = item.inventory_id;
                this.form.vendor_id = item.vendor_id;
                this.form.rfq_id = item.rfq_id;
                this.form.vendor_name = item.nama_perusahaan;
                this.form.item_name = item.nama_barang;
                
                let kebutuhan = parseInt(item.jumlah_kebutuhan) || 0;
                let quota = parseInt(item.quota_percentage) || 100;
                this.form.jumlah_pesan = Math.round(kebutuhan * (quota / 100));
                
                // Harga penawaran adalah Harga Satuan
                this.form.harga_satuan = parseFloat(item.harga_penawaran) || 0;
                this.form.diskon_jenis = item.diskon_jenis || 'Nominal';
                this.form.diskon_nilai = parseFloat(item.diskon_nilai) || 0;
                this.form.biaya_pengiriman = parseFloat(item.biaya_pengiriman) || 0;
                
                this.showModal = true;
            },
            submitPO() {
                let fd = new FormData();
                fd.append('inventory_id', this.form.inventory_id);
                fd.append('vendor_id', this.form.vendor_id);
                fd.append('rfq_id', this.form.rfq_id);
                fd.append('jumlah_pesan', this.form.jumlah_pesan);
                fd.append('harga_satuan', this.form.harga_satuan);

                fetch('process_buat_po.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    if (d.success) location.reload();
                });
            }
        }">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Siap Dibuat PO -->
                <?php if(mysqli_num_rows($query_ready) > 0): ?>
                <div>
                    <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4"><i class="fa-solid fa-clipboard-list text-red-500 mr-2"></i> Siap Dibuat PO</h3>
                    <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                        <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                            <thead class="bg-red-50 text-[10px] font-black text-red-600 uppercase tracking-[0.2em]">
                                <tr>
                                    <th class="p-6">Item & Kebutuhan</th>
                                    <th class="p-6">Vendor Terpilih</th>
                                    <th class="p-6 text-center">Quota</th>
                                    <th class="p-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php while($ready = mysqli_fetch_assoc($query_ready)): ?>
                                <tr class="hover:bg-slate-50 dark:bg-slate-900 transition-colors">
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($ready['nama_barang']) ?></div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 dark:text-slate-500 font-bold uppercase">Kebutuhan: <?= $ready['jumlah_kebutuhan'] ?? 'N/A' ?></div>
                                    </td>
                                    <td class="p-6">
                                        <div class="font-bold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($ready['nama_perusahaan']) ?></div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase">Harga Satuan: Rp <span x-text="new Intl.NumberFormat('id-ID').format(<?= $ready['harga_penawaran'] ?>)"></span></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="px-3 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded-full text-[10px] font-black uppercase">
                                            <?= $ready['quota_percentage'] ?? 100 ?>%
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <button @click="openModal(<?= htmlspecialchars(json_encode($ready)) ?>)" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 transition shadow-lg shadow-slate-900/10">
                                            <i class="fa-solid fa-plus mr-1"></i> Buat PO
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
</div>
                    </div>
                </div>
                <?php endif; ?>

                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest mb-4 mt-8"><i class="fa-solid fa-clock-rotate-left text-red-500 mr-2"></i> PO Pending Manager</h3>

                <?php if(mysqli_num_rows($query_po) > 0): ?>
                    <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                        <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                            <thead class="bg-slate-900 text-[10px] font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">
                                <tr>
                                    <th class="p-6">PO Number</th>
                                    <th class="p-6">Vendor & Item</th>
                                    <th class="p-6 text-center">Total Value</th>
                                    <th class="p-6 text-center">Status</th>
                                    <th class="p-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php while($po = mysqli_fetch_assoc($query_po)): ?>
                                <tr class="hover:bg-slate-50 dark:bg-slate-900 transition-colors">
                                    <td class="p-6 font-black text-slate-800 dark:text-white"><?= htmlspecialchars($po['po_number']) ?></td>
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($po['nama_barang']) ?></div>
                                        <div class="text-[10px] text-red-500 font-bold uppercase tracking-widest italic">Vendor: <?= htmlspecialchars($po['nama_perusahaan']) ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <div class="font-black text-slate-700 dark:text-slate-200">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="px-3 py-1 bg-yellow-50 text-yellow-600 border border-yellow-100 rounded-full text-[9px] font-black uppercase tracking-widest">
                                            <?= htmlspecialchars($po['status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 dark:text-slate-300 hover:bg-red-500 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                            <i class="fa-solid fa-print"></i> Print PO
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
</div>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-[#1e293b] p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="w-24 h-24 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-file-signature text-5xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight mb-2">No Pending PO</h3>
                        <p class="text-slate-400 dark:text-slate-500 font-medium">Belum ada PO yang digenerate atau menunggu persetujuan manager.</p>
                        <a href="strategic_procurement.php" class="mt-6 inline-block bg-red-600 text-white px-6 py-3 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-red-700 transition">Ke Evaluasi Vendor</a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Slide-Over Panel Buat PO -->
            <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex justify-end">
                <!-- Backdrop -->
                <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
                
                <!-- Panel -->
                <div x-show="showModal" 
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="relative w-full max-w-md h-full bg-white dark:bg-[#1e293b] shadow-2xl flex flex-col z-50 border-l border-slate-100 dark:border-slate-800">
                    
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 dark:bg-slate-900/50 shrink-0">
                        <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Form Generate PO</h3>
                        <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-red-500 hover:border-red-200 dark:hover:border-red-900 transition-colors">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    
                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Item</label>
                            <input type="text" x-model="form.item_name" disabled class="w-full">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Vendor</label>
                            <input type="text" x-model="form.vendor_name" disabled class="w-full">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Jumlah Pesan</label>
                            <input type="number" x-model="form.jumlah_pesan" class="w-full">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Harga Satuan (Rp)</label>
                            <input type="number" x-model="form.harga_satuan" disabled class="w-full">
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 p-5 rounded-2xl border border-red-100 dark:border-red-900/30 mt-6">
                            <label class="block text-[10px] font-black text-red-400 dark:text-red-500 uppercase tracking-widest mb-1">Total Harga PO</label>
                            <div class="text-2xl font-black text-red-600 dark:text-red-400">Rp <span x-text="new Intl.NumberFormat('id-ID').format(totalHarga)"></span></div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 shrink-0 flex gap-4">
                        <button @click="showModal = false" class="w-1/3 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-xl py-3.5 font-black uppercase tracking-widest text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Batal
                        </button>
                        <button @click="submitPO" class="w-2/3 bg-red-600 text-white rounded-xl py-3.5 font-black uppercase tracking-widest text-xs hover:bg-red-700 transition shadow-lg shadow-red-600/20">
                            Generate & Kirim
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
