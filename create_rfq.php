<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'rop';

$inventory_id = intval($_GET['inventory_id'] ?? 0);
$item = null;

if ($inventory_id > 0) {
    $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM inventory WHERE id = $inventory_id"));
}

if (!$item) {
    header("Location: dashboard_procurement.php?error=item_not_found");
    exit;
}

// Auto-heal: Tutup RFQ yang statusnya masih 'Open' tapi proses Strategic Procurement sudah selesai (sudah ada vendor yang 'Selected')
mysqli_query($conn, "
    UPDATE rfq_requests r 
    JOIN sph_submissions s ON r.inventory_id = s.inventory_id
    SET r.status = 'Closed', r.closed_at = NOW()
    WHERE r.status = 'Open' AND s.status = 'Selected'
");

// Cek apakah sudah ada RFQ Open untuk item ini
$existing_rfq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, rfq_number FROM rfq_requests WHERE inventory_id = $inventory_id AND status = 'Open'"));

// Ambil daftar vendor aktif
$query_vendors = mysqli_query($conn, "SELECT id, nama_perusahaan, spesialisasi FROM vendors WHERE status IN ('Aktif', 'Approved') ORDER BY nama_perusahaan ASC");
$vendors = [];
while ($v = mysqli_fetch_assoc($query_vendors)) {
    $vendors[] = $v;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat RFQ | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">
    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Create RFQ</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Request for Quotation — Undang Vendor</p>
                </div>
                <a href="dashboard_procurement.php" class="text-slate-400 hover:text-slate-800 font-bold text-xs uppercase transition flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="max-w-4xl mx-auto">

                <?php if ($existing_rfq): ?>
                <!-- Sudah ada RFQ Open -->
                <div class="bg-yellow-50 border-2 border-yellow-300 p-8 rounded-3xl text-center">
                    <i class="fa-solid fa-triangle-exclamation text-5xl text-yellow-400 mb-4 block"></i>
                    <h3 class="text-xl font-black text-slate-800 mb-2">RFQ Sudah Ada</h3>
                    <p class="text-slate-500 mb-4">Barang <strong><?= htmlspecialchars($item['nama_barang']) ?></strong> sudah memiliki RFQ terbuka: <strong><?= $existing_rfq['rfq_number'] ?></strong></p>
                    <p class="text-sm text-slate-400 mb-6">Tunggu vendor mengirimkan penawaran, lalu lanjut ke evaluasi di Strategic Procurement.</p>
                    <div class="flex justify-center gap-4">
                        <a href="print_rfq.php?id=<?= $existing_rfq['id'] ?>" target="_blank" class="bg-white text-slate-800 border border-slate-200 px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest hover:border-slate-400 transition">
                            <i class="fa-solid fa-print mr-2"></i> Print RFQ
                        </a>
                        <a href="strategic_procurement.php" class="bg-slate-900 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-red-600 transition">
                            <i class="fa-solid fa-chess-knight mr-2"></i> Ke Strategic Procurement
                        </a>
                        <a href="dashboard_procurement.php" class="bg-slate-200 text-slate-600 px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-300 transition">
                            Kembali
                        </a>
                    </div>
                </div>
                <?php else: ?>

                <!-- Info Barang -->
                <div class="bg-slate-900 p-6 rounded-t-3xl flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-white uppercase"><?= htmlspecialchars($item['nama_barang']) ?></h3>
                        <div class="flex gap-3 mt-1">
                            <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded font-bold uppercase">SKU: <?= $item['sku'] ?></span>
                            <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded font-bold uppercase">Unit: <?= $item['unit'] ?></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Stok Saat Ini</span>
                        <span class="text-xl font-black text-red-500"><?= $item['stok_saat_ini'] ?> <span class="text-sm text-slate-500">/ <?= $item['stok_kritis'] ?></span></span>
                    </div>
                </div>

                <!-- Form RFQ -->
                <div class="bg-white p-8 rounded-b-3xl border border-t-0 border-slate-200 shadow-sm" 
                     x-data="{
                        selectedVendors: [],
                        submitting: false,
                        toggleVendor(id) {
                            if (this.selectedVendors.includes(id)) {
                                this.selectedVendors = this.selectedVendors.filter(v => v !== id);
                            } else {
                                this.selectedVendors.push(id);
                            }
                        },
                        selectAll() {
                            this.selectedVendors = [<?= implode(',', array_column($vendors, 'id')) ?>];
                        },
                        async submitRFQ() {
                            if (this.selectedVendors.length === 0) return alert('Pilih minimal 1 vendor!');
                            this.submitting = true;
                            
                            let fd = new FormData(document.getElementById('rfq_form'));
                            fd.append('vendor_ids', JSON.stringify(this.selectedVendors));
                            
                            let res = await fetch('process_rfq.php', { method: 'POST', body: fd });
                            let data = await res.json();
                            alert(data.message);
                            if (data.success) {
                                window.location.href = 'dashboard_procurement.php';
                            } else {
                                this.submitting = false;
                            }
                        }
                     }">

                    <form id="rfq_form" @submit.prevent="submitRFQ()" class="space-y-6">
                        <input type="hidden" name="inventory_id" value="<?= $item['id'] ?>">

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Jumlah Kebutuhan *</label>
                                <div class="relative">
                                    <input type="number" name="jumlah_kebutuhan" required min="1" value="<?= max(1, $item['stok_kritis'] - $item['stok_saat_ini'] + 10) ?>" 
                                        class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl font-black text-center focus:border-red-500 outline-none">
                                    <span class="absolute right-4 top-4 text-slate-400 font-bold text-sm"><?= $item['unit'] ?></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Batas Waktu Penawaran *</label>
                                <input type="datetime-local" name="deadline" required 
                                    value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>"
                                    class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl font-bold focus:border-red-500 outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Catatan / Spesifikasi Tambahan</label>
                            <textarea name="catatan" rows="3" placeholder="Contoh: Wajib memiliki sertifikat ISO, warna biru, ukuran A4..." 
                                class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl font-medium text-sm focus:border-red-500 outline-none"></textarea>
                        </div>

                        <!-- Vendor Selection -->
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pilih Vendor yang Diundang *</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="selectAll()" class="text-[10px] text-blue-600 font-bold uppercase hover:underline">Pilih Semua</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="selectedVendors = []" class="text-[10px] text-red-500 font-bold uppercase hover:underline">Reset</button>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 text-blue-600 p-3 rounded-xl text-xs font-medium border border-blue-100 flex items-center gap-2 mb-3">
                                <i class="fa-solid fa-circle-info"></i>
                                Terpilih: <strong x-text="selectedVendors.length"></strong> vendor
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-2">
                                <?php foreach ($vendors as $v): ?>
                                <div @click="toggleVendor(<?= $v['id'] ?>)" 
                                     :class="selectedVendors.includes(<?= $v['id'] ?>) ? 'border-red-500 bg-red-50 shadow-md' : 'border-slate-200 bg-white hover:border-slate-300'"
                                     class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-center gap-3">
                                    <div :class="selectedVendors.includes(<?= $v['id'] ?>) ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-400'"
                                         class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors shrink-0">
                                        <i :class="selectedVendors.includes(<?= $v['id'] ?>) ? 'fa-solid fa-check' : 'fa-solid fa-building'" class="text-sm"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800 text-sm truncate"><?= htmlspecialchars($v['nama_perusahaan']) ?></p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest truncate"><?= htmlspecialchars($v['spesialisasi'] ?? 'Umum') ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (empty($vendors)): ?>
                            <div class="p-8 text-center border-2 border-dashed border-slate-200 rounded-xl">
                                <p class="text-slate-400 font-bold">Belum ada vendor terdaftar. Tambahkan di Master Vendor terlebih dahulu.</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Submit -->
                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" :disabled="submitting || selectedVendors.length === 0"
                                class="bg-red-600 hover:bg-red-700 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-red-600/20 transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!submitting"><i class="fa-solid fa-paper-plane"></i> Kirim RFQ ke Vendor</span>
                                <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin"></i> Mengirim...</span>
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
