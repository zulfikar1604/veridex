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
$query_rop = mysqli_query($conn, "SELECT * FROM inventory WHERE status_label='Kritis' AND id NOT IN (SELECT inventory_id FROM rfq_requests WHERE status = 'Open') ORDER BY stok_saat_ini ASC");
?>
<?php
$page_title = "ROP Dashboard";
$page_subtitle = "Reorder Point • Reorder Quantity • Safety Stock";
include 'header_meta.php';
?>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active = 'rop'; 
         include 'sidebar_procurement.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
            <!-- GREETING BANNER -->
            <div class="bg-gradient-to-r from-amber-500/10 to-transparent p-6 rounded-3xl border border-amber-500/20 flex justify-between items-center flex-wrap gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Ruang Operasional Procurement</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Selamat datang kembali, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>. Berikut status ROP dan safety stock Anda.</p>
                </div>
                <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100 text-xs font-bold text-slate-600">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-1.5 animate-bounce"></i> ROP Active
                </div>
            </div>
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
        </main>
    </div>



</body>
</html>
