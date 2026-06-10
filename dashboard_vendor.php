<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y');
$active = 'bidding';
$vendor_id = $_SESSION['vendor_id'] ?? 0;

// RFQ yang masih Open dan vendor ini diundang (belum submit)
$query_open = mysqli_query($conn, "
    SELECT r.*, rv.has_submitted, i.nama_barang, i.sku, i.unit,
           u.username as created_by_name
    FROM rfq_requests r
    JOIN rfq_vendors rv ON r.id = rv.rfq_id
    JOIN inventory i ON r.inventory_id = i.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE rv.vendor_id = $vendor_id AND r.status = 'Open' AND rv.has_submitted = 0
    ORDER BY r.created_at DESC
") or handle_db_error($conn);

// RFQ yang sudah di-submit vendor ini
$query_submitted = mysqli_query($conn, "
    SELECT r.*, rv.has_submitted, i.nama_barang, s.nomor_sph, s.harga_penawaran, s.biaya_pengiriman, s.status as sph_status, s.diskon_jenis, s.diskon_nilai, s.incoterm, s.payment_term, s.keterangan
    FROM rfq_requests r
    JOIN rfq_vendors rv ON r.id = rv.rfq_id
    JOIN inventory i ON r.inventory_id = i.id
    LEFT JOIN sph_submissions s ON s.rfq_id = r.id AND s.vendor_id = $vendor_id
    WHERE rv.vendor_id = $vendor_id AND rv.has_submitted = 1
    ORDER BY r.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Bidding Area";
    $page_subtitle = "Pengadaan Terbuka & Penawaran";
    $navbar_badge = (mysqli_num_rows($query_open) > 0) ? '<span class="bg-red-600 text-white text-xs font-black px-3 py-1 rounded-full animate-pulse">' . mysqli_num_rows($query_open) . ' RFQ Baru</span>' : '';
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_vendor.php'; ?>

        <main class="flex-1 overflow-y-auto p-5 md:p-6 space-y-6 animate-fade-in-up">
            <!-- GREETING BANNER -->
            <div class="bg-gradient-to-r from-blue-500/10 to-transparent p-5 rounded-2xl border border-blue-500/20 flex justify-between items-center flex-wrap gap-4 mb-6">
                <div>
                    <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Portal Rekanan Penjual</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500 font-medium mt-1">Selamat datang di Veridex, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>. Temukan RFQ aktif dan kelola PO.</p>
                </div>
                <div class="bg-white dark:bg-[#1e293b] px-4 py-2 rounded-xl shadow-sm border border-slate-100 dark:border-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <i class="fa-solid fa-handshake text-blue-500 mr-1.5"></i> Vendor Mitra
                </div>
            </div>
            <!-- RFQ Terbuka — Menunggu Penawaran -->
            <?php if(mysqli_num_rows($query_open) > 0): ?>
            <div>
                <h3 class="text-xs font-black text-red-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-fire"></i> RFQ Terbuka — Kirim Penawaran Anda
                </h3>
                <div class="space-y-4">
                    <?php while($rfq = mysqli_fetch_assoc($query_open)):
                        $is_expired = (!empty($rfq['deadline']) && strtotime($rfq['deadline']) < time());
                        $time_left = !empty($rfq['deadline']) ? strtotime($rfq['deadline']) - time() : 0;
                        $days_left = floor($time_left / 86400);
                        $hours_left = floor(($time_left % 86400) / 3600);
                    ?>
                    <div class="bg-white dark:bg-[#1e293b] rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden" x-data="{ open: false, submitting: false }">
                        <!-- Header RFQ -->
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="font-black text-slate-800 dark:text-white text-xl"><?= htmlspecialchars($rfq['nama_barang']) ?></span>
                                        <?php if(!$is_expired): ?>
                                        <span class="bg-red-600 text-white px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg shadow-red-200">OPEN</span>
                                        <?php else: ?>
                                        <span class="bg-slate-400 text-white px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">EXPIRED</span>
                                        <?php endif; ?>
                                        <a href="print_rfq.php?id=<?= $rfq['id'] ?>" target="_blank" class="px-2 py-1 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 dark:text-slate-500 hover:text-blue-600 hover:border-blue-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm ml-2" title="Cetak RFQ">
                                            <i class="fa-solid fa-print"></i> RFQ
                                        </a>
                                    </div>
                                    <div class="flex gap-3 mt-1 flex-wrap">
                                        <span class="text-[10px] bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500 px-2 py-0.5 rounded font-bold uppercase">RFQ: <?= htmlspecialchars($rfq['rfq_number']) ?></span>
                                        <span class="text-[10px] bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500 px-2 py-0.5 rounded font-bold uppercase">SKU: <?= htmlspecialchars($rfq['sku']) ?></span>
                                        <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded font-bold uppercase">Kebutuhan: <?= $rfq['jumlah_kebutuhan'] ?> <?= $rfq['unit'] ?></span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <?php if(!$is_expired && $time_left > 0): ?>
                                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase block">Sisa Waktu</span>
                                    <span class="text-lg font-black <?= $days_left < 2 ? 'text-red-600' : 'text-slate-800 dark:text-white' ?>"><?= $days_left ?>h <?= $hours_left ?>j</span>
                                    <?php else: ?>
                                    <span class="text-xs font-black text-red-500">Waktu Habis</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if(!empty($rfq['catatan'])): ?>
                            <div class="bg-amber-50 border border-amber-100 p-3 rounded-xl text-sm text-amber-700 font-medium mb-4">
                                <i class="fa-solid fa-clipboard mr-1"></i> <strong>Catatan:</strong> <?= htmlspecialchars($rfq['catatan']) ?>
                            </div>
                            <?php endif; ?>

                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold">
                                <i class="fa-regular fa-calendar mr-1"></i> Deadline: <?= date('d M Y, H:i', strtotime($rfq['deadline'])) ?> WIB
                                <span class="mx-2">|</span>
                                <i class="fa-regular fa-clock mr-1"></i> Dibuat: <?= date('d M Y', strtotime($rfq['created_at'])) ?>
                            </p>
                        </div>

                        <?php if(!$is_expired): ?>
                        <!-- Toggle Button -->
                        <div class="px-6 pb-4">
                            <button @click="open = !open" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-red-600 transition-all shadow-xl">
                                <i class="fa-solid fa-file-signature mr-2"></i> Formulir Surat Penawaran Harga (SPH)
                            </button>
                        </div>

                        <!-- Form Submit SPH -->
                        <div x-show="open" x-cloak x-transition class="bg-slate-50 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 p-6">
                            <form id="sph-form-<?= $rfq['id'] ?>" @submit.prevent="
                                submitting = true;
                                let form = document.getElementById('sph-form-<?= $rfq['id'] ?>');
                                let fd = new FormData(form);
                                fd.append('rfq_id', <?= $rfq['id'] ?>);
                                fetch('process_sph_submit.php', {method:'POST', body:fd})
                                .then(r=>r.json())
                                .then(d=>{alert(d.message); if(d.success) location.reload(); else submitting=false;})
                                .catch(e=>{alert('Error: ' + e.message);submitting=false;})
                            " class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-1 block">Harga Penawaran (Per Satuan) *</label>
                                        <input type="number" name="harga_penawaran" required min="1" placeholder="5000000" class="w-full border border-slate-200 dark:border-slate-700 p-3 rounded-xl outline-none focus:border-red-500 bg-white dark:bg-[#1e293b] font-bold text-sm">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-1 block">Biaya Pengiriman</label>
                                        <input type="number" name="biaya_pengiriman" min="0" placeholder="0" class="w-full border border-slate-200 dark:border-slate-700 p-3 rounded-xl outline-none focus:border-red-500 bg-white dark:bg-[#1e293b] font-bold text-sm">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-1 block">Jenis Diskon</label>
                                        <select name="diskon_jenis" class="w-full border border-slate-200 dark:border-slate-700 p-3 rounded-xl outline-none focus:border-red-500 bg-white dark:bg-[#1e293b] font-bold text-sm">
                                            <option value="Nominal">Nominal (Rp)</option>
                                            <option value="Persentase">Persentase (%)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-1 block">Nilai Diskon</label>
                                        <input type="number" name="diskon_nilai" min="0" step="0.01" placeholder="0" class="w-full border border-slate-200 dark:border-slate-700 p-3 rounded-xl outline-none focus:border-red-500 bg-white dark:bg-[#1e293b] font-bold text-sm">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-1 block">Incoterm</label>
                                        <input type="text" name="incoterm" placeholder="ex: FOB, CIF, EXW" class="w-full border border-slate-200 dark:border-slate-700 p-3 rounded-xl outline-none focus:border-red-500 bg-white dark:bg-[#1e293b] font-bold text-sm">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-1 block">Payment Term</label>
                                        <input type="text" name="payment_term" placeholder="ex: Net 30, CBD" class="w-full border border-slate-200 dark:border-slate-700 p-3 rounded-xl outline-none focus:border-red-500 bg-white dark:bg-[#1e293b] font-bold text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-1 block">Keterangan / Catatan Tambahan</label>
                                    <textarea name="keterangan" rows="3" placeholder="Syarat dan ketentuan lainnya..." class="w-full border border-slate-200 dark:border-slate-700 p-3 rounded-xl outline-none focus:border-red-500 bg-white dark:bg-[#1e293b] font-bold text-sm"></textarea>
                                </div>
                                <button type="submit" :disabled="submitting" class="w-full bg-red-600 text-white py-4 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-red-700 transition disabled:opacity-50 shadow-lg shadow-red-600/20">
                                    <span x-show="!submitting"><i class="fa-solid fa-paper-plane mr-2"></i> Kirim Penawaran otomatis</span>
                                    <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Mengirim...</span>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat Penawaran -->
            <div>
                <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penawaran Anda
                </h3>
                <?php if(mysqli_num_rows($query_submitted) > 0): ?>
                <div class="bg-white dark:bg-[#1e293b] rounded-2xl border shadow-sm overflow-hidden">
                    <div class="overflow-x-auto w-full">
<table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-900 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                <th class="p-4">RFQ</th>
                                <th class="p-4">Barang</th>
                                <th class="p-4">No. SPH</th>
                                <th class="p-4 text-center">Harga</th>
                                <th class="p-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($row = mysqli_fetch_assoc($query_submitted)):
                                $sc = [
                                    'Pending Review'=>'bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500',
                                    'TBE Done'=>'bg-blue-50 text-blue-600',
                                    'CBE Done'=>'bg-purple-50 text-purple-600',
                                    'Evaluated'=>'bg-indigo-50 text-indigo-600',
                                    'Selected'=>'bg-green-50 text-green-600',
                                    'Rejected'=>'bg-red-50 text-red-600'
                                ];
                                $c = $sc[$row['sph_status'] ?? ''] ?? 'bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500';
                            ?>
                            <tr class="hover:bg-slate-50 dark:bg-slate-900 transition">
                                <td class="p-4 font-bold text-slate-600 dark:text-slate-300 text-xs"><?= htmlspecialchars($row['rfq_number']) ?></td>
                                <td class="p-4 font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td class="p-4 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['nomor_sph'] ?? '-') ?></td>
                                <td class="p-4 text-center font-bold text-slate-700 dark:text-slate-200">Rp <?= number_format($row['harga_penawaran'] ?? 0, 0, ',', '.') ?> <span class="text-[9px] text-slate-400 dark:text-slate-500 font-normal">/unit</span></td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php 
                                            $is_expired_sub = (!empty($row['deadline']) && strtotime($row['deadline']) < time());
                                            if (!$is_expired_sub && $row['sph_status'] === 'Pending Review'): 
                                        ?>
                                        <button @click="editData = <?= htmlspecialchars(json_encode([
                                            'rfq_id' => $row['id'],
                                            'harga_penawaran' => $row['harga_penawaran'],
                                            'biaya_pengiriman' => $row['biaya_pengiriman'],
                                            'diskon_jenis' => $row['diskon_jenis'],
                                            'diskon_nilai' => $row['diskon_nilai'],
                                            'incoterm' => $row['incoterm'],
                                            'payment_term' => $row['payment_term'],
                                            'keterangan' => $row['keterangan']
                                        ])) ?>; editModalOpen = true" class="px-3 py-1 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-amber-500 hover:text-amber-600 hover:border-amber-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Edit SPH"><i class="fa-solid fa-pen"></i> Edit</button>
                                        
                                        <button onclick="if(confirm('Yakin ingin membatalkan dan menghapus penawaran ini?')) { fetch('process_sph_delete.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'rfq_id=<?= $row['id'] ?>'}).then(r=>r.json()).then(d=>{alert(d.message); if(d.success) location.reload();}).catch(e=>alert(e)) }" class="px-3 py-1 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-red-500 hover:text-red-600 hover:border-red-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Hapus SPH"><i class="fa-solid fa-trash"></i> Hapus</button>
                                        <?php endif; ?>

                                        <a href="print_sph.php?rfq=<?= $row['id'] ?>&vendor=<?= $vendor_id ?>" target="_blank" class="px-3 py-1 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 dark:text-slate-500 hover:text-blue-600 hover:border-blue-200 rounded-lg text-[9px] font-black uppercase transition shadow-sm" title="Cetak SPH"><i class="fa-solid fa-print"></i> Cetak</a>
                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase <?= $c ?>"><?= $row['sph_status'] ?? 'Submitted' ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
</div>
                </div>                <!-- Slide-Over Edit Modal -->
                <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex justify-end">
                    <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="editModalOpen = false"></div>
                    
                    <div x-show="editModalOpen" 
                         x-transition:enter="transition ease-out duration-300 transform"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transition ease-in duration-200 transform"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full"
                         class="relative w-full max-w-xl h-full bg-white dark:bg-[#1e293b] shadow-2xl flex flex-col z-50 border-l border-slate-100 dark:border-slate-800">
                         
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 dark:bg-slate-900/50 shrink-0">
                            <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">Edit Surat Penawaran Harga</h3>
                            <button @click="editModalOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-red-500 hover:border-red-200 dark:hover:border-red-900 transition-colors"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto p-6">
                            <form id="edit-sph-form" @submit.prevent="
                                submittingEdit = true;
                                let fd = new FormData(document.getElementById('edit-sph-form'));
                                fd.append('rfq_id', editData.rfq_id);
                                fetch('process_sph_update.php', {method:'POST', body:fd})
                                .then(r=>r.json())
                                .then(d=>{alert(d.message); if(d.success) location.reload(); else submittingEdit=false;})
                                .catch(e=>{alert('Error: ' + e.message);submittingEdit=false;})
                            " class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Harga Penawaran (Per Satuan) *</label>
                                        <input type="number" name="harga_penawaran" required min="1" x-model="editData.harga_penawaran" class="w-full">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Biaya Pengiriman</label>
                                        <input type="number" name="biaya_pengiriman" min="0" x-model="editData.biaya_pengiriman" class="w-full">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Jenis Diskon</label>
                                        <select name="diskon_jenis" x-model="editData.diskon_jenis" class="w-full">
                                            <option value="Nominal">Nominal (Rp)</option>
                                            <option value="Persentase">Persentase (%)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Nilai Diskon</label>
                                        <input type="number" name="diskon_nilai" min="0" step="0.01" x-model="editData.diskon_nilai" class="w-full">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Incoterm</label>
                                        <input type="text" name="incoterm" x-model="editData.incoterm" class="w-full">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Payment Term</label>
                                        <input type="text" name="payment_term" x-model="editData.payment_term" class="w-full">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Keterangan / Catatan Tambahan</label>
                                    <textarea name="keterangan" rows="3" x-model="editData.keterangan" class="w-full"></textarea>
                                </div>
                                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                                    <button type="submit" :disabled="submittingEdit" class="w-full bg-blue-600 text-white py-3.5 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-600/20 disabled:opacity-50">
                                        <span x-show="!submittingEdit"><i class="fa-solid fa-save mr-2"></i> Simpan Perubahan</span>
                                        <span x-show="submittingEdit"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Menyimpan...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white dark:bg-[#1e293b] p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-folder-open text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 dark:text-slate-500 font-bold">Belum ada riwayat penawaran.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Empty state total -->
            <?php if(mysqli_num_rows($query_open) == 0 && mysqli_num_rows($query_submitted) == 0): ?>
            <div class="bg-white dark:bg-[#1e293b] p-12 rounded-2xl border shadow-sm text-center">
                <i class="fa-solid fa-gavel text-6xl text-slate-100 mb-6 block"></i>
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Pengadaan Terbuka</h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm">Saat ini tidak ada RFQ yang ditujukan ke perusahaan Anda. Silakan cek kembali nanti.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>