<?php
session_start();
require 'database.php';

// Proteksi Internal
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'strategic';

// Ambil item yang sedang ada di SPH dan butuh evaluasi
// Kita kumpulkan data SPH berdasarkan inventory_id
$query_items = mysqli_query($conn, "
    SELECT DISTINCT i.* 
    FROM inventory i
    JOIN sph_submissions s ON i.id = s.inventory_id
    WHERE s.status IN ('Pending Review', 'TBE Done', 'CBE Done', 'Evaluated')
    ORDER BY i.stok_saat_ini ASC
") or handle_db_error($conn);

// Fungsi pembantu untuk mengambil SPH per item
function get_sph_for_item($conn, $inventory_id) {
    $q = "SELECT s.*, v.nama_perusahaan 
          FROM sph_submissions s 
          JOIN vendors v ON s.vendor_id = v.id 
          WHERE s.inventory_id = $inventory_id
          ORDER BY s.id ASC";
    return mysqli_query($conn, $q);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bid Evaluation & Selection | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .step-active { @apply border-red-500 text-red-600 bg-red-50; }
        .step-done { @apply border-green-500 text-green-600 bg-green-50; }
        .step-pending { @apply border-slate-200 text-slate-400 bg-slate-50; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Bid Evaluation</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1">Technical & Commercial Assessment</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-black text-slate-400 uppercase block">Items to Evaluate</span>
                    <span class="text-2xl font-black text-red-600"><?= mysqli_num_rows($query_items) ?></span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <?php if(mysqli_num_rows($query_items) > 0): ?>
                    <?php while($item = mysqli_fetch_assoc($query_items)): 
                        $sph_list = get_sph_for_item($conn, $item['id']);
                        $sph_data = [];
                        $all_evaluated = true;
                        $any_cbe_done = false;
                        
                        while($sph = mysqli_fetch_assoc($sph_list)) {
                            $sph_data[] = $sph;
                            if ($sph['status'] !== 'Evaluated') {
                                $all_evaluated = false;
                            }
                            if (in_array($sph['status'], ['CBE Done', 'Evaluated'])) {
                                $any_cbe_done = true;
                            }
                            
                            // Calculate final score if TBE and CBE are present
                            if ($sph['tbe_score'] !== null && $sph['cbe_score'] !== null) {
                                // Default weight: 40% TBE, 60% CBE
                                $final_score = ($sph['tbe_score'] * 0.4) + ($sph['cbe_score'] * 0.6);
                                $sph_data[count($sph_data)-1]['final_score'] = number_format($final_score, 2);
                            } else {
                                $sph_data[count($sph_data)-1]['final_score'] = null;
                            }
                        }
                        
                        // Sort by final score for ranking if all evaluated
                        if ($all_evaluated) {
                            usort($sph_data, function($a, $b) {
                                return $b['final_score'] <=> $a['final_score'];
                            });
                        }
                    ?>
                    
                    <div x-data="{ 
                            step: <?= $all_evaluated ? 3 : 1 ?>,
                            scores: {},
                            
                            getTbe(id) { return this.scores['tbe_'+id] || ''; },
                            setTbe(id, v) { this.scores['tbe_'+id] = v; },
                            getCbe(id) { return this.scores['cbe_'+id] || ''; },
                            setCbe(id, v) { this.scores['cbe_'+id] = v; },
                            
                            async submitTbe(sphId) {
                                let score = this.getTbe(sphId);
                                if(!score || score < 0 || score > 100) return alert('Score harus 0-100');
                                let fd = new FormData();
                                fd.append('action', 'score_tbe');
                                fd.append('sph_id', sphId);
                                fd.append('tbe_score', score);
                                
                                let res = await fetch('process_bid_evaluation.php', { method: 'POST', body: fd });
                                let data = await res.json();
                                if(data.success) {
                                    window.location.reload();
                                } else {
                                    alert(data.message);
                                }
                            },
                            
                            async submitCbe(sphId) {
                                let score = this.getCbe(sphId);
                                if(!score || score < 0 || score > 100) return alert('Score harus 0-100');
                                let fd = new FormData();
                                fd.append('action', 'score_cbe');
                                fd.append('sph_id', sphId);
                                fd.append('cbe_score', score);
                                
                                let res = await fetch('process_bid_evaluation.php', { method: 'POST', body: fd });
                                let data = await res.json();
                                if(data.success) {
                                    window.location.reload();
                                } else {
                                    alert(data.message);
                                }
                            },
                            
                            async finalizeSelection(e) {
                                e.preventDefault();
                                let form = e.target;
                                let fd = new FormData(form);
                                fd.append('action', 'select_vendor');
                                
                                let res = await fetch('process_bid_evaluation.php', { method: 'POST', body: fd });
                                let data = await res.json();
                                if(data.success) {
                                    alert(data.message);
                                    window.location.href = 'approval_procurement.php';
                                } else {
                                    alert(data.message);
                                }
                            }
                         }" 
                         class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                         
                        <!-- Header Master -->
                        <div class="bg-slate-900 p-6 flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-black text-white uppercase"><?= htmlspecialchars($item['nama_barang']) ?></h3>
                                <div class="flex gap-3 mt-1">
                                    <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded font-bold uppercase">SKU: <?= $item['sku'] ?></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Stok Saat Ini</span>
                                <span class="text-xl font-black text-red-500"><?= $item['stok_saat_ini'] ?> <span class="text-sm text-slate-500">/ <?= $item['stok_kritis'] ?></span></span>
                            </div>
                        </div>

                        <div class="p-8">
                            <!-- Stepper Nav -->
                            <div class="flex items-center justify-between mb-8 relative">
                                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-100 z-0 rounded-full"></div>
                                
                                <!-- Step 1 -->
                                <button @click="step = 1" class="relative z-10 flex flex-col items-center gap-2 group cursor-pointer transition-all">
                                    <div class="w-10 h-10 rounded-full border-4 flex items-center justify-center font-black text-sm transition-colors bg-white"
                                         :class="step === 1 ? 'border-red-600 text-red-600' : (<?= $any_cbe_done ? 'true' : 'false' ?> ? 'border-green-500 text-green-500' : 'border-slate-200 text-slate-400')">
                                        1
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-widest" :class="step === 1 ? 'text-slate-800' : 'text-slate-400'">TBE & CBE Scoring</span>
                                </button>

                                <!-- Step 2 -->
                                <button @click="step = 2" class="relative z-10 flex flex-col items-center gap-2 group cursor-pointer transition-all">
                                    <div class="w-10 h-10 rounded-full border-4 flex items-center justify-center font-black text-sm transition-colors bg-white"
                                         :class="step === 2 ? 'border-red-600 text-red-600' : (<?= $all_evaluated ? 'true' : 'false' ?> ? 'border-green-500 text-green-500' : 'border-slate-200 text-slate-400')">
                                        2
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-widest" :class="step === 2 ? 'text-slate-800' : 'text-slate-400'">Final Ranking</span>
                                </button>

                                <!-- Step 3 -->
                                <button @click="<?= $all_evaluated ? 'step = 3' : 'alert(\'Selesaikan evaluasi semua vendor terlebih dahulu\')' ?>" class="relative z-10 flex flex-col items-center gap-2 group cursor-pointer transition-all">
                                    <div class="w-10 h-10 rounded-full border-4 flex items-center justify-center font-black text-sm transition-colors bg-white"
                                         :class="step === 3 ? 'border-red-600 text-red-600' : 'border-slate-200 text-slate-400'">
                                        3
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-widest" :class="step === 3 ? 'text-slate-800' : 'text-slate-400'">Vendor Selection</span>
                                </button>
                            </div>

                            <!-- STEP 1: Evaluation Forms -->
                            <div x-show="step === 1" x-transition.opacity class="space-y-4">
                                <div class="bg-blue-50 text-blue-600 p-4 rounded-xl text-sm font-medium border border-blue-100 flex items-center gap-3">
                                    <i class="fa-solid fa-circle-info"></i> Lakukan penilaian Teknis (TBE) lalu Komersial (CBE) untuk setiap vendor.
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach($sph_data as $s): ?>
                                    <div class="border border-slate-200 rounded-2xl p-5 hover:border-slate-300 transition-colors">
                                        <div class="flex justify-between items-start mb-4">
                                            <div>
                                                <h4 class="font-black text-slate-800 uppercase"><?= htmlspecialchars($s['nama_perusahaan']) ?></h4>
                                                <p class="text-[10px] text-slate-400 font-bold tracking-widest">SPH: <?= htmlspecialchars($s['nomor_sph'] ?? '-') ?></p>
                                            </div>
                                            <span class="px-2 py-1 rounded text-[9px] font-black uppercase tracking-widest bg-slate-100 text-slate-500">
                                                <?= $s['status'] ?>
                                            </span>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- TBE -->
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-center justify-between">
                                                <div class="font-bold text-xs text-slate-600">Technical (TBE)</div>
                                                <?php if($s['tbe_score'] != null && $s['tbe_score'] !== ''): ?>
                                                    <span class="font-black text-lg text-slate-800"><?= $s['tbe_score'] ?></span>
                                                <?php else: ?>
                                                    <div class="flex gap-2 w-32">
                                                        <input type="number" :value="getTbe(<?= $s['id'] ?>)" @input="setTbe(<?= $s['id'] ?>, $event.target.value)" class="w-full text-xs p-1 border rounded text-center outline-none focus:border-red-500" placeholder="0-100" min="0" max="100">
                                                        <button @click="submitTbe(<?= $s['id'] ?>)" class="bg-slate-900 text-white px-2 rounded text-xs hover:bg-red-600 transition"><i class="fa-solid fa-check"></i></button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- CBE -->
                                            <?php 
                                                $tbe_done = ($s['tbe_score'] != null && $s['tbe_score'] !== '');
                                            ?>
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-center justify-between <?= !$tbe_done ? 'opacity-50 pointer-events-none' : '' ?>">
                                                <div class="font-bold text-xs text-slate-600">Commercial (CBE)</div>
                                                <?php if($s['cbe_score'] != null && $s['cbe_score'] !== ''): ?>
                                                    <span class="font-black text-lg text-slate-800"><?= $s['cbe_score'] ?></span>
                                                <?php elseif($tbe_done): ?>
                                                    <div class="flex gap-2 w-32">
                                                        <input type="number" :value="getCbe(<?= $s['id'] ?>)" @input="setCbe(<?= $s['id'] ?>, $event.target.value)" class="w-full text-xs p-1 border rounded text-center outline-none focus:border-red-500" placeholder="0-100" min="0" max="100">
                                                        <button @click="submitCbe(<?= $s['id'] ?>)" class="bg-slate-900 text-white px-2 rounded text-xs hover:bg-red-600 transition"><i class="fa-solid fa-check"></i></button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400 italic">Isi TBE dulu</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-6 flex justify-end">
                                    <button @click="step = 2" class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-colors flex items-center gap-2">
                                        View Ranking <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- STEP 2: Final Ranking -->
                            <div x-show="step === 2" x-transition.opacity class="space-y-6">
                                <?php if(!$all_evaluated): ?>
                                    <div class="bg-yellow-50 text-yellow-600 p-4 rounded-xl text-sm font-medium border border-yellow-100 flex items-center gap-3">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Selesaikan evaluasi TBE & CBE untuk semua vendor untuk melihat Final Ranking.
                                    </div>
                                <?php else: ?>
                                    <table class="w-full text-left text-sm border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                                        <thead class="bg-slate-100 text-slate-500 text-[10px] uppercase font-black tracking-widest">
                                            <tr>
                                                <th class="p-4">Rank</th>
                                                <th class="p-4">Vendor</th>
                                                <th class="p-4 text-center">TBE (40%)</th>
                                                <th class="p-4 text-center">CBE (60%)</th>
                                                <th class="p-4 text-right">Final Score</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php foreach($sph_data as $idx => $s): ?>
                                            <tr class="<?= $idx === 0 ? 'bg-red-50' : '' ?>">
                                                <td class="p-4">
                                                    <?php if($idx === 0): ?>
                                                        <span class="w-8 h-8 rounded bg-red-600 text-white flex items-center justify-center font-black shadow-lg shadow-red-600/30">1</span>
                                                    <?php else: ?>
                                                        <span class="w-8 h-8 rounded bg-slate-200 text-slate-500 flex items-center justify-center font-black"><?= $idx + 1 ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-4 font-bold text-slate-800"><?= htmlspecialchars($s['nama_perusahaan']) ?></td>
                                                <td class="p-4 text-center font-medium"><?= $s['tbe_score'] ?></td>
                                                <td class="p-4 text-center font-medium"><?= $s['cbe_score'] ?></td>
                                                <td class="p-4 text-right font-black text-lg <?= $idx === 0 ? 'text-red-600' : 'text-slate-800' ?>">
                                                    <?= $s['final_score'] ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    
                                    <div class="mt-6 flex justify-between items-center">
                                        <button @click="step = 1" class="text-slate-400 hover:text-slate-800 font-bold text-xs uppercase transition-colors"><i class="fa-solid fa-arrow-left mr-2"></i> Back to Scoring</button>
                                        <button @click="step = 3" class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-colors flex items-center gap-2">
                                            Assign Quota <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- STEP 3: Vendor Selection & Quota -->
                            <div x-show="step === 3" x-transition.opacity class="space-y-6">
                                <?php if(!$all_evaluated): ?>
                                    <div class="bg-yellow-50 text-yellow-600 p-4 rounded-xl text-sm font-medium border border-yellow-100 flex items-center gap-3">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Selesaikan evaluasi TBE & CBE untuk semua vendor terlebih dahulu.
                                    </div>
                                <?php else: ?>
                                    <form @submit="finalizeSelection" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <input type="hidden" name="inventory_id" value="<?= $item['id'] ?>">
                                        
                                        <!-- Rank 1 Assignment -->
                                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                                            <div class="flex justify-between items-center mb-4">
                                                <span class="w-8 h-8 bg-yellow-400 text-slate-900 rounded-lg flex items-center justify-center font-black">1</span>
                                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Utama</span>
                                            </div>
                                            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Pilih Vendor</label>
                                            <select name="vendor_1" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm mb-4 outline-none focus:border-red-500 font-bold" required>
                                                <option value="">-- Pilih Vendor --</option>
                                                <?php foreach($sph_data as $s): ?>
                                                    <option value="<?= $s['vendor_id'] ?>" <?= $sph_data[0]['vendor_id'] == $s['vendor_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nama_perusahaan']) ?> (Skor: <?= $s['final_score'] ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Kuota (%)</label>
                                            <div class="relative">
                                                <input type="number" name="quota_1" value="70" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl font-black text-center focus:border-red-500 outline-none" required>
                                                <span class="absolute right-4 top-3.5 text-slate-400 font-black">%</span>
                                            </div>
                                        </div>

                                        <!-- Rank 2 Assignment -->
                                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                                            <div class="flex justify-between items-center mb-4">
                                                <span class="w-8 h-8 bg-slate-200 text-slate-600 rounded-lg flex items-center justify-center font-black">2</span>
                                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Pendamping</span>
                                            </div>
                                            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Pilih Vendor</label>
                                            <select name="vendor_2" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-sm mb-4 outline-none font-bold">
                                                <option value="">-- Tanpa Pendamping --</option>
                                                <?php foreach($sph_data as $s): ?>
                                                    <option value="<?= $s['vendor_id'] ?>"><?= htmlspecialchars($s['nama_perusahaan']) ?> (Skor: <?= $s['final_score'] ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Kuota (%)</label>
                                            <div class="relative">
                                                <input type="number" name="quota_2" value="30" class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl font-black text-center outline-none">
                                                <span class="absolute right-4 top-3.5 text-slate-400 font-black">%</span>
                                            </div>
                                        </div>

                                        <!-- Konfirmasi -->
                                        <div class="col-span-1 md:col-span-2 pt-4 flex items-center justify-between border-t border-slate-100">
                                            <button type="button" @click="step = 2" class="text-slate-400 hover:text-slate-800 font-bold text-xs uppercase transition-colors"><i class="fa-solid fa-arrow-left mr-2"></i> Back to Ranking</button>
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-red-600/20 transition-all flex items-center gap-3">
                                                <i class="fa-solid fa-file-signature"></i> Finalize & Generate PO
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200 shadow-sm">
                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-clipboard-check text-5xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight mb-2">No Items to Evaluate</h3>
                        <p class="text-slate-400 font-medium">Tidak ada SPH yang memerlukan evaluasi saat ini.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>