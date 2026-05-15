<?php
session_start();
require 'database.php';

// 1. Proteksi: Sekarang Manager Procurement DAN Procurement bisa masuk
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y');

// 2. QUERY UNTUK KARTU INDIKATOR (Hitung total barang berdasarkan status)
$kritis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory WHERE status_label='Kritis'"))['total'];
$warning = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory WHERE status_label='Warning'"))['total'];
$aman = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory WHERE status_label='Aman'"))['total'];

// 3. QUERY UNTUK LIST ROP TRIGGER (Hanya ambil barang yang Kritis)
$query_rop = mysqli_query($conn, "SELECT * FROM inventory WHERE status_label='Kritis' ORDER BY stok_saat_ini ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROP Dashboard | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 h-screen flex overflow-hidden">

    <?php 
        $active = 'rop'; 
        include 'sidebar_procurement.php'; 
    ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">ROP DASHBOARD</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">
                        <?= strtoupper(substr($_SESSION['role'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-red-500 flex flex-col justify-center min-h-[140px]">
                    <p class="text-xs font-bold text-red-600 uppercase tracking-widest mb-1">STOK KRITIS (ROP)</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2"><?= str_pad($kritis, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 italic">SKU</span></h3>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-yellow-400 flex flex-col justify-center min-h-[140px]">
                    <p class="text-xs font-bold text-yellow-500 uppercase tracking-widest mb-1">PERINGATAN</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2"><?= str_pad($warning, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 italic">SKU</span></h3>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-green-500 flex flex-col justify-center min-h-[140px]">
                    <p class="text-xs font-bold text-green-500 uppercase tracking-widest mb-1">STOK AMAN</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2"><?= str_pad($aman, 2, '0', STR_PAD_LEFT) ?> <span class="text-sm font-medium text-slate-400 italic">SKU</span></h3>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 mb-6 uppercase tracking-widest">List Pengadaan Otomatis (ROP Trigger)</h3>
                
                <div class="space-y-4">
                    <?php if (mysqli_num_rows($query_rop) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($query_rop)): 
                            $pr_number = "PR-" . date('Y') . "-" . str_pad($item['id'], 4, '0', STR_PAD_LEFT);
                        ?>
                            <div class="bg-red-50/50 p-6 rounded-2xl border-l-4 border-red-500 flex justify-between items-center transition hover:bg-red-50">
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="font-black text-slate-800 text-lg"><?= $pr_number ?></span>
                                        <span class="bg-red-600 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase">KRITIS</span>
                                    </div>
                                    <p class="text-sm text-slate-600 font-medium">Barang: <span class="font-bold uppercase"><?= htmlspecialchars($item['nama_barang']) ?></span> (Sisa: <?= $item['stok_saat_ini'] ?>)</p>
                                </div>
                                
                                <?php if ($_SESSION['role'] === 'Procurement'): ?>
                                    <a href="create_rfq.php?inventory_id=<?= $item['id'] ?>" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-black text-xs shadow-lg hover:bg-red-600 hover:-translate-y-1 transition uppercase tracking-widest">
                                        <i class="fa-solid fa-bullhorn mr-2"></i> BUAT RFQ
                                    </a>
                                <?php else: ?>
                                    <div class="bg-slate-100 text-slate-400 px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest border border-slate-200">
                                        <i class="fa-solid fa-eye mr-2"></i> VIEW ONLY
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-12 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                            <i class="fa-solid fa-check-double text-5xl text-green-300 mb-4 block"></i>
                            <p class="text-slate-500 font-bold text-lg">Semua Stok Aman</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>