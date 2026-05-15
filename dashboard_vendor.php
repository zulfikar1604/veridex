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
    SELECT r.*, rv.has_submitted, i.nama_barang, s.nomor_sph, s.harga_penawaran, s.status as sph_status
    FROM rfq_requests r
    JOIN rfq_vendors rv ON r.id = rv.rfq_id
    JOIN inventory i ON r.inventory_id = i.id
    LEFT JOIN sph_submissions s ON s.rfq_id = r.id AND s.vendor_id = $vendor_id
    WHERE rv.vendor_id = $vendor_id AND rv.has_submitted = 1
    ORDER BY r.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_vendor.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Bidding Area</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Pengadaan Terbuka & Penawaran</p>
                </div>
                <div class="flex items-center gap-4">
                    <?php if(mysqli_num_rows($query_open) > 0): ?>
                    <span class="bg-red-600 text-white text-xs font-black px-3 py-1 rounded-full animate-pulse">
                        <?= mysqli_num_rows($query_open) ?> RFQ Baru
                    </span>
                    <?php endif; ?>
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">V</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">

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
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ open: false, submitting: false }">
                        <!-- Header RFQ -->
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="font-black text-slate-800 text-xl"><?= htmlspecialchars($rfq['nama_barang']) ?></span>
                                        <?php if(!$is_expired): ?>
                                        <span class="bg-red-600 text-white px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg shadow-red-200">OPEN</span>
                                        <?php else: ?>
                                        <span class="bg-slate-400 text-white px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">EXPIRED</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex gap-3 mt-1 flex-wrap">
                                        <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold uppercase">RFQ: <?= htmlspecialchars($rfq['rfq_number']) ?></span>
                                        <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold uppercase">SKU: <?= htmlspecialchars($rfq['sku']) ?></span>
                                        <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded font-bold uppercase">Kebutuhan: <?= $rfq['jumlah_kebutuhan'] ?> <?= $rfq['unit'] ?></span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <?php if(!$is_expired && $time_left > 0): ?>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block">Sisa Waktu</span>
                                    <span class="text-lg font-black <?= $days_left < 2 ? 'text-red-600' : 'text-slate-800' ?>"><?= $days_left ?>h <?= $hours_left ?>j</span>
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

                            <p class="text-[10px] text-slate-400 font-bold">
                                <i class="fa-regular fa-calendar mr-1"></i> Deadline: <?= date('d M Y, H:i', strtotime($rfq['deadline'])) ?> WIB
                                <span class="mx-2">|</span>
                                <i class="fa-regular fa-clock mr-1"></i> Dibuat: <?= date('d M Y', strtotime($rfq['created_at'])) ?>
                            </p>
                        </div>

                        <?php if(!$is_expired): ?>
                        <!-- Toggle Button -->
                        <div class="px-6 pb-4">
                            <button @click="open = !open" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-red-600 transition-all shadow-xl">
                                <i class="fa-solid fa-file-arrow-up mr-2"></i> Unggah Surat Penawaran Harga (SPH)
                            </button>
                        </div>

                        <!-- Form Submit SPH -->
                        <div x-show="open" x-cloak x-transition class="bg-slate-50 border-t border-slate-100 p-6">
                            <form id="sph-form-<?= $rfq['id'] ?>" @submit.prevent="
                                submitting = true;
                                let form = document.getElementById('sph-form-<?= $rfq['id'] ?>');
                                let fd = new FormData(form);
                                fd.append('rfq_id', <?= $rfq['id'] ?>);
                                fetch('process_sph_submit.php', {method:'POST', body:fd})
                                .then(r=>r.json())
                                .then(d=>{alert(d.message); if(d.success) location.reload(); else submitting=false;})
                                .catch(e=>{alert('Error: ' + e.message);submitting=false;})
                            " enctype="multipart/form-data" class="space-y-4 max-w-xl">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Nomor SPH *</label>
                                        <input type="text" name="nomor_sph" required placeholder="SPH/2026/001" class="w-full border border-slate-200 p-3 rounded-xl outline-none focus:border-red-500 bg-white font-bold text-sm">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Harga Penawaran (Rp) *</label>
                                        <input type="number" name="harga_penawaran" required min="1" placeholder="5000000" class="w-full border border-slate-200 p-3 rounded-xl outline-none focus:border-red-500 bg-white font-bold text-sm">
                                    </div>
                                </div>
                                <div x-data="{ fileName: '' }">
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Dokumen SPH (PDF/JPG, opsional)</label>
                                    <div class="border-2 border-dashed rounded-xl text-center transition cursor-pointer relative"
                                         :class="fileName ? 'border-green-400 bg-green-50 p-3' : 'border-slate-200 p-4 hover:border-red-400'">
                                        <template x-if="!fileName">
                                            <div>
                                                <i class="fa-solid fa-file-pdf text-xl mb-1 block text-slate-400"></i>
                                                <span class="text-slate-400 text-xs font-medium">Klik untuk pilih file (Maks 5MB)</span>
                                            </div>
                                        </template>
                                        <template x-if="fileName">
                                            <div class="flex items-center gap-2 justify-center">
                                                <i class="fa-solid fa-file-circle-check text-green-500 text-lg"></i>
                                                <span class="text-green-700 text-xs font-bold truncate" x-text="fileName"></span>
                                                <button type="button" @click.stop="fileName=''; $refs.fileInput.value=''" class="text-red-400 hover:text-red-600 text-xs"><i class="fa-solid fa-xmark"></i></button>
                                            </div>
                                        </template>
                                        <input type="file" name="dokumen_sph" x-ref="fileInput" accept=".jpg,.jpeg,.png,.pdf" 
                                               @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    </div>
                                </div>
                                <button type="submit" :disabled="submitting" class="w-full bg-red-600 text-white py-4 rounded-xl font-black uppercase text-xs tracking-widest hover:bg-red-700 transition disabled:opacity-50 shadow-lg shadow-red-600/20">
                                    <span x-show="!submitting"><i class="fa-solid fa-paper-plane mr-2"></i> Kirim Penawaran</span>
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
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penawaran Anda
                </h3>
                <?php if(mysqli_num_rows($query_submitted) > 0): ?>
                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-widest">
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
                                    'Pending Review'=>'bg-slate-100 text-slate-500',
                                    'TBE Done'=>'bg-blue-50 text-blue-600',
                                    'CBE Done'=>'bg-purple-50 text-purple-600',
                                    'Evaluated'=>'bg-indigo-50 text-indigo-600',
                                    'Selected'=>'bg-green-50 text-green-600',
                                    'Rejected'=>'bg-red-50 text-red-600'
                                ];
                                $c = $sc[$row['sph_status'] ?? ''] ?? 'bg-slate-100 text-slate-500';
                            ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 font-bold text-slate-600 text-xs"><?= htmlspecialchars($row['rfq_number']) ?></td>
                                <td class="p-4 font-bold text-slate-800"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td class="p-4 text-slate-600"><?= htmlspecialchars($row['nomor_sph'] ?? '-') ?></td>
                                <td class="p-4 text-center font-bold text-slate-700">Rp <?= number_format($row['harga_penawaran'] ?? 0, 0, ',', '.') ?></td>
                                <td class="p-4 text-right">
                                    <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase <?= $c ?>"><?= $row['sph_status'] ?? 'Submitted' ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-white p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-folder-open text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 font-bold">Belum ada riwayat penawaran.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Empty state total -->
            <?php if(mysqli_num_rows($query_open) == 0 && mysqli_num_rows($query_submitted) == 0): ?>
            <div class="bg-white p-16 rounded-[2.5rem] border shadow-sm text-center">
                <i class="fa-solid fa-gavel text-7xl text-slate-100 mb-6 block"></i>
                <h3 class="font-black text-slate-800 text-xl mb-2">Belum Ada Pengadaan Terbuka</h3>
                <p class="text-slate-400 text-sm">Saat ini tidak ada RFQ yang ditujukan ke perusahaan Anda. Silakan cek kembali nanti.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>