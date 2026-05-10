<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

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
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">
                        <?= htmlspecialchars(strtoupper(substr($_SESSION['role'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div class="space-y-4 max-w-4xl mx-auto">
                
                <div class="bg-red-600 p-6 rounded-2xl text-white shadow-xl flex items-center justify-between border-4 border-red-200 animate-pulse">
                    <div>
                        <h4 class="text-xl font-bold uppercase tracking-tighter">PERINGATAN STOK MINIMAL!</h4>
                        <p class="text-red-100 mt-1">Barang: <span class="font-bold">Toner Printer HP 101</span> mencapai titik pengadaan ulang (Sisa 2 Pcs).</p>
                    </div>
                    <i class="fa-solid fa-bell text-5xl opacity-80"></i>
                </div>
                
                <div class="bg-white p-6 rounded-2xl border flex items-center justify-between shadow-sm">
                    <span class="font-bold text-slate-700 uppercase tracking-widest text-sm">Log Sistem Notifikasi ROP</span>
                    <button class="text-xs bg-slate-100 hover:bg-slate-200 transition-colors px-4 py-2 font-bold text-slate-600 rounded-lg">Bersihkan Log</button>
                </div>

                <div class="bg-white p-8 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-check-double text-4xl text-slate-200 mb-3"></i>
                    <p class="text-slate-400 text-sm font-medium">Tidak ada log notifikasi ROP yang tertunda hari ini.</p>
                </div>

            </div>
        </div>
    </main>

</body>
</html>