<?php
session_start();
require 'database.php';

// 1. Proteksi Internal: Memastikan integritas data hanya bisa diakses Procurement/Manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'strategic';

// 2. Query Data Inventory (Hanya barang yang butuh pengadaan)
$query_items = mysqli_query($conn, "
    SELECT * FROM inventory 
    WHERE stok_kritis > stok_saat_ini 
    ORDER BY stok_saat_ini ASC
") or handle_db_error($conn);

// 3. Ambil List Vendor untuk Assignment
$vendors = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_perusahaan FROM vendors"), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Strategic Procurement Dashboard | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header: Memberikan impresi IDSS (Intelligent Decision Support System)[cite: 2] -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Strategic Procurement</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1 italic">Internal Decision Control Center</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-white px-6 py-2 rounded-2xl shadow-sm border border-slate-100 text-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase block">Pending Review</span>
                        <span class="text-xl font-black text-red-600"><?= mysqli_num_rows($query_items) ?> Items</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content dengan Konsep Master-Detail[cite: 1] -->
        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="max-w-6xl">
                <!-- Table Header -->
                <div class="grid grid-cols-12 gap-4 px-6 py-3 bg-slate-900 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] rounded-t-2xl shadow-lg">
                    <div class="col-span-6">Procurement Subject & SKU</div>
                    <div class="col-span-3 text-center">Inventory Status</div>
                    <div class="col-span-3 text-right">Decision Tool</div>
                </div>

                <div class="space-y-3 mt-3">
                    <?php if(mysqli_num_rows($query_items) > 0): ?>
                        <?php while($item = mysqli_fetch_assoc($query_items)): ?>
                        <div x-data="{ open: false }" class="bg-white border border-slate-200 rounded-3xl shadow-sm hover:shadow-md transition-all overflow-hidden">
                            
                            <!-- Master Row: Menampilkan data stok kritis secara ringkas[cite: 1] -->
                            <div @click="open = !open" class="grid grid-cols-12 gap-4 p-6 items-center cursor-pointer hover:bg-slate-50/50 transition">
                                <div class="col-span-6">
                                    <h4 class="font-black text-slate-800 text-lg uppercase"><?= $item['nama_barang'] ?></h4>
                                    <div class="flex gap-3 mt-1">
                                        <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold uppercase">SKU: <?= $item['sku'] ?></span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase italic"><?= $item['unit'] ?> Basis</span>
                                    </div>
                                </div>
                                <div class="col-span-3 text-center">
                                    <div class="text-xl font-black text-red-600"><?= $item['stok_saat_ini'] ?> / <span class="text-slate-300 font-medium"><?= $item['stok_kritis'] ?></span></div>
                                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Critical Level Alert</div>
                                </div>
                                <div class="col-span-3 text-right">
                                    <button class="bg-slate-900 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 transition-all flex items-center gap-2 ml-auto shadow-lg shadow-slate-900/10">
                                        <span x-text="open ? 'Minimize' : 'Analyze Now'"></span>
                                        <i class="fa-solid fa-brain transition-transform" :class="open ? 'rotate-180' : ''"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Detail Section: Penggabungan Scoring (Kiri) & Ranking (Kanan) -->
                            <div x-show="open" x-cloak x-transition.origin.top class="p-10 bg-slate-50 border-t border-slate-100 grid grid-cols-1 lg:grid-cols-2 gap-12">
                                
                                <!-- Sisi Kiri: Auto-Scoring & AI Insights -->
                                <div class="space-y-6">
                                    <h5 class="text-xs font-black text-red-500 uppercase tracking-[0.2em] flex items-center gap-2">
                                        <i class="fa-solid fa-chart-line"></i> Evaluation Analysis
                                    </h5>
                                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                                        <table class="w-full text-left text-[11px]">
                                            <thead class="bg-slate-100 border-b border-slate-200 text-slate-500">
                                                <tr>
                                                    <th class="p-3">Vendor</th>
                                                    <th class="p-3 text-center">TBE</th>
                                                    <th class="p-3 text-center">CBE</th>
                                                    <th class="p-3 text-right font-black text-slate-800">Final</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <tr class="hover:bg-slate-50">
                                                    <td class="p-3 font-bold text-slate-700">PT. Solusi Digital</td>
                                                    <td class="p-3 text-center">88.5</td>
                                                    <td class="p-3 text-center">92.0</td>
                                                    <td class="p-3 text-right font-black text-red-600">90.60</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="p-3 font-bold text-slate-700">CV. Global Tech</td>
                                                    <td class="p-3 text-center">91.0</td>
                                                    <td class="p-3 text-center">84.5</td>
                                                    <td class="p-3 text-right font-black text-slate-800">87.10</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- AI Recomendation Box -->
                                    <div class="bg-red-600 rounded-2xl p-5 text-white shadow-xl shadow-red-600/20 relative">
                                        <i class="fa-solid fa-robot absolute top-4 right-4 text-white/10 text-4xl"></i>
                                        <h6 class="text-[10px] font-black uppercase tracking-widest mb-2 flex items-center gap-2">
                                            <i class="fa-solid fa-microchip"></i> Veridex AI Logic
                                        </h6>
                                        <p class="text-xs italic leading-relaxed font-medium">
                                            "Berdasarkan komparasi TBE/CBE, **PT. Solusi Digital** sangat unggul di efisiensi biaya. Disarankan alokasi **70%** sebagai Main Vendor untuk menjaga profitabilitas pengadaan ini."
                                        </p>
                                    </div>
                                </div>

                                <!-- Sisi Kanan: Quota Assignment (Eksekusi Ranking)[cite: 1] -->
                                <div class="space-y-6">
                                    <h5 class="text-xs font-black text-red-500 uppercase tracking-[0.2em] flex items-center gap-2">
                                        <i class="fa-solid fa-pen-to-square"></i> Quota Execution
                                    </h5>
                                    <form action="process_strategic.php" method="POST" class="space-y-4">
                                        <input type="hidden" name="inventory_id" value="<?= $item['id'] ?>">
                                        
                                        <!-- Rank 1 Assignment[cite: 1] -->
                                        <div class="flex items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                                            <span class="w-10 h-10 bg-yellow-400 text-slate-900 rounded-xl flex items-center justify-center font-black text-xs">1</span>
                                            <select name="vendor_1" class="flex-1 bg-slate-50 border-none text-xs font-bold text-slate-700 focus:ring-0 outline-none">
                                                <option value="">Select Main Vendor...</option>
                                                <?php foreach($vendors as $v): ?>
                                                    <option value="<?= $v['id'] ?>"><?= $v['nama_perusahaan'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="w-20 relative">
                                                <input type="number" name="quota_1" value="70" class="w-full bg-slate-900 text-white p-2 rounded-lg text-center text-xs font-black outline-none border-none">
                                                <span class="absolute -top-2 -right-1 text-[8px] font-black text-red-600 bg-white px-1 border border-slate-100 rounded">QUOTA %</span>
                                            </div>
                                        </div>

                                        <!-- Rank 2 Assignment[cite: 1] -->
                                        <div class="flex items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                                            <span class="w-10 h-10 bg-slate-200 text-slate-500 rounded-xl flex items-center justify-center font-black text-xs">2</span>
                                            <select name="vendor_2" class="flex-1 bg-slate-50 border-none text-xs font-bold text-slate-700 outline-none">
                                                <option value="">Select Secondary Vendor...</option>
                                                <?php foreach($vendors as $v): ?>
                                                    <option value="<?= $v['id'] ?>"><?= $v['nama_perusahaan'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="w-20 relative">
                                                <input type="number" name="quota_2" value="30" class="w-full bg-slate-100 text-slate-400 p-2 rounded-lg text-center text-xs font-black outline-none border-none">
                                            </div>
                                        </div>

                                        <button type="submit" class="w-full py-4 bg-slate-900 hover:bg-red-600 text-white rounded-2xl font-black uppercase tracking-[0.2em] shadow-xl transition-all flex items-center justify-center gap-3 mt-6">
                                            <i class="fa-solid fa-file-contract"></i> Confirm Procurement Decision
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Status jika tidak ada barang kritis[cite: 1] -->
                        <div class="bg-white p-20 rounded-3xl text-center border-2 border-dashed border-slate-200">
                            <i class="fa-solid fa-check-double text-5xl text-green-500/20 mb-4"></i>
                            <p class="text-slate-400 font-bold uppercase tracking-widest">Inventory Levels are Stable</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>