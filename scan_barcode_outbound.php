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
    <title>Outbound | Veridex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="bg-slate-50 h-screen flex overflow-hidden">

    <?php 
        $active_menu = 'outbound'; 
        include 'sidebar_gudang.php'; 
    ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-[0.1em] uppercase">BARANG KELUAR (OUTBOUND)</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">
                        <?= strtoupper(substr($_SESSION['role'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 flex items-center justify-center">
            <div class="bg-white p-12 rounded-3xl border shadow-sm text-center w-full max-w-2xl">
                <i class="fa-solid fa-barcode text-8xl text-slate-200 mb-6"></i>
                <h3 class="text-2xl font-bold mb-4 italic text-slate-600 underline">SCAN BARCODE OUTBOUND</h3>
                <p class="text-gray-500 mb-8 max-w-sm mx-auto">Gunakan kamera HP kantor untuk memindai barang keluar guna mengurangi stok otomatis.</p>
                <button class="px-8 py-4 bg-slate-800 text-white rounded-full font-bold shadow-xl hover:scale-105 transition">
                    <i class="fa-solid fa-camera mr-2"></i> AKTIFKAN KAMERA
                </button>
            </div>
        </div>
    </main>

</body>
</html>