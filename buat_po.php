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
    SELECT s.inventory_id, s.vendor_id, s.harga_penawaran, 
           i.nama_barang, v.nama_perusahaan,
           rq.jumlah_kebutuhan, vq.quota_percentage
    FROM sph_submissions s
    JOIN inventory i ON s.inventory_id = i.id
    JOIN vendors v ON s.vendor_id = v.id
    LEFT JOIN rfq_requests rq ON s.rfq_id = rq.id
    LEFT JOIN vendor_quotas vq ON s.inventory_id = vq.inventory_id AND s.vendor_id = vq.vendor_id
    LEFT JOIN purchase_orders po ON po.inventory_id = s.inventory_id AND po.vendor_id = s.vendor_id
    WHERE s.status = 'Selected' AND po.id IS NULL
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Purchase Orders</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Daftar PO (Pending Approval Manager)</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-black text-slate-400 uppercase block">Pending PO</span>
                    <span class="text-2xl font-black text-red-600"><?= mysqli_num_rows($query_po) ?></span>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 pb-8" x-data="{ 
            showModal: false, 
            form: { inventory_id: 0, vendor_id: 0, vendor_name: '', item_name: '', jumlah_pesan: 0, total_harga: 0 },
            openModal(item) {
                this.form.inventory_id = item.inventory_id;
                this.form.vendor_id = item.vendor_id;
                this.form.vendor_name = item.nama_perusahaan;
                this.form.item_name = item.nama_barang;
                
                let kebutuhan = parseInt(item.jumlah_kebutuhan) || 0;
                let quota = parseInt(item.quota_percentage) || 100;
                this.form.jumlah_pesan = Math.round(kebutuhan * (quota / 100));
                
                // Asumsi harga_penawaran dari vendor adalah total, kita prorata dengan kuota
                let harga = parseFloat(item.harga_penawaran) || 0;
                this.form.total_harga = Math.round(harga * (quota / 100));
                
                this.showModal = true;
            },
            submitPO() {
                let fd = new FormData();
                fd.append('inventory_id', this.form.inventory_id);
                fd.append('vendor_id', this.form.vendor_id);
                fd.append('jumlah_pesan', this.form.jumlah_pesan);
                fd.append('total_harga', this.form.total_harga);

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
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4"><i class="fa-solid fa-clipboard-list text-red-500 mr-2"></i> Siap Dibuat PO</h3>
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
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
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 uppercase text-sm"><?= htmlspecialchars($ready['nama_barang']) ?></div>
                                        <div class="text-[10px] text-slate-500 font-bold uppercase">Kebutuhan: <?= $ready['jumlah_kebutuhan'] ?? 'N/A' ?></div>
                                    </td>
                                    <td class="p-6">
                                        <div class="font-bold text-slate-700"><?= htmlspecialchars($ready['nama_perusahaan']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase">Penawaran: Rp <?= number_format($ready['harga_penawaran'], 0, ',', '.') ?></div>
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
                <?php endif; ?>

                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4 mt-8"><i class="fa-solid fa-clock-rotate-left text-red-500 mr-2"></i> PO Pending Manager</h3>

                <?php if(mysqli_num_rows($query_po) > 0): ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-900 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
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
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-6 font-black text-slate-800"><?= htmlspecialchars($po['po_number']) ?></td>
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 uppercase text-sm"><?= htmlspecialchars($po['nama_barang']) ?></div>
                                        <div class="text-[10px] text-red-500 font-bold uppercase tracking-widest italic">Vendor: <?= htmlspecialchars($po['nama_perusahaan']) ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <div class="font-black text-slate-700">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="px-3 py-1 bg-yellow-50 text-yellow-600 border border-yellow-100 rounded-full text-[9px] font-black uppercase tracking-widest">
                                            <?= htmlspecialchars($po['status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 hover:bg-red-500 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                            <i class="fa-solid fa-print"></i> Print PO
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200 shadow-sm">
                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-file-signature text-5xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight mb-2">No Pending PO</h3>
                        <p class="text-slate-400 font-medium">Belum ada PO yang digenerate atau menunggu persetujuan manager.</p>
                        <a href="strategic_procurement.php" class="mt-6 inline-block bg-red-600 text-white px-6 py-3 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-red-700 transition">Ke Evaluasi Vendor</a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Modal Buat PO -->
            <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
                <div @click.away="showModal = false" class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full border border-slate-100 transform transition-all">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Form Generate PO</h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Item</label>
                            <input type="text" x-model="form.item_name" disabled class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Vendor</label>
                            <input type="text" x-model="form.vendor_name" disabled class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Jumlah Pesan</label>
                            <input type="number" x-model="form.jumlah_pesan" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-800 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Harga (Rp)</label>
                            <input type="number" x-model="form.total_harga" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-800 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        </div>
                    </div>

                    <div class="mt-8">
                        <button @click="submitPO" class="w-full bg-red-600 text-white rounded-xl py-4 font-black uppercase tracking-widest text-xs hover:bg-red-700 transition shadow-lg shadow-red-600/20">
                            Generate & Kirim ke Manager
                        </button>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
