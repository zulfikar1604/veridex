<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

require 'database.php';
date_default_timezone_set('Asia/Jakarta');
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$bulan_array = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = $hari_array[date('w')] . ', ' . date('j') . ' ' . $bulan_array[date('n')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi ROP | Veridex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 h-screen flex overflow-hidden">

    <?php 
        $active_menu = 'rop'; 
        include 'sidebar_gudang.php'; 
    ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-[0.1em] uppercase">NOTIFIKASI ROP (REORDER POINT)</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <?php include 'navbar_notifications.php'; ?>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">
                        <?= htmlspecialchars(strtoupper(substr($_SESSION['role'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <?php
                $query_kritis = mysqli_query($conn, "SELECT * FROM inventory WHERE status_label = 'Kritis' ORDER BY stok_saat_ini ASC");
                $total_kritis = mysqli_num_rows($query_kritis);
            ?>

            <div class="space-y-4 max-w-4xl mx-auto">

                <!-- Header ringkasan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Barang Butuh Pengadaan</span>
                        <p class="text-3xl font-black text-red-600 mt-1">
                            <?= str_pad($total_kritis, 2, '0', STR_PAD_LEFT) ?>
                            <span class="text-sm font-medium text-slate-400">SKU</span>
                        </p>
                    </div>
                    <i class="fa-solid fa-triangle-exclamation text-5xl text-red-100"></i>
                </div>

                <!-- List barang kritis -->
                <?php if ($total_kritis > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($query_kritis)): ?>
                        <div class="bg-red-600 p-6 rounded-2xl text-white shadow-xl flex items-center justify-between border-4 border-red-400 animate-pulse">
                            <div>
                                <span class="text-xs font-bold bg-red-800/40 px-2 py-1 rounded-full uppercase tracking-widest"><?= htmlspecialchars($row['sku']) ?></span>
                                <h4 class="text-lg font-black uppercase tracking-tight mt-2"><?= htmlspecialchars($row['nama_barang']) ?></h4>
                                <p class="text-red-100 text-sm mt-1">
                                    Sisa stok: <span class="font-black text-white"><?= $row['stok_saat_ini'] ?> <?= htmlspecialchars($row['unit']) ?></span>
                                    &nbsp;·&nbsp; ROP: <span class="font-bold"><?= $row['stok_kritis'] ?> <?= htmlspecialchars($row['unit']) ?></span>
                                </p>
                            </div>
                            <div class="text-center shrink-0 ml-6">
                                <i class="fa-solid fa-bell text-4xl opacity-80 mb-2 block"></i>
                                <span class="text-xs font-bold bg-red-800/40 px-3 py-1 rounded-full">KRITIS</span>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <!-- Semua aman -->
                    <div class="bg-white p-10 rounded-2xl border shadow-sm text-center">
                        <i class="fa-solid fa-check-double text-5xl text-green-200 mb-4 block"></i>
                        <p class="text-slate-500 font-bold">Semua stok dalam kondisi aman.</p>
                        <p class="text-slate-400 text-sm mt-1">Tidak ada barang yang perlu pengadaan saat ini.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>