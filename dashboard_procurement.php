<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Procurement') {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y'); // Sesuai format di gambar: Sabtu, 11 April 2026
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement Dashboard | Veridex</title>
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
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">ROP</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">P</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-red-500 flex flex-col justify-center min-h-[140px]">
                    <p class="text-xs font-bold text-red-600 uppercase tracking-widest mb-1">STOK KRITIS (< 7)</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2">01 <span class="text-sm font-medium text-slate-400 italic">SKU</span></h3>
                    <p class="text-xs font-bold text-red-500 mt-2 italic">Segera Buat Pengadaan</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-yellow-400 flex flex-col justify-center min-h-[140px]">
                    <p class="text-xs font-bold text-yellow-500 uppercase tracking-widest mb-1">PERINGATAN (7 - 10)</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2">04 <span class="text-sm font-medium text-slate-400 italic">SKU</span></h3>
                    <p class="text-xs font-bold text-yellow-600 mt-2 italic">Pantau Pemakaian Rutin</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 border-l-[8px] border-l-green-500 flex flex-col justify-center min-h-[140px]">
                    <p class="text-xs font-bold text-green-500 uppercase tracking-widest mb-1">STOK AMAN (> 10)</p>
                    <h3 class="text-4xl font-black text-slate-800 flex items-baseline gap-2">12 <span class="text-sm font-medium text-slate-400 italic">SKU</span></h3>
                    <p class="text-xs font-bold text-green-600 mt-2 italic">Ketersediaan Terjamin</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 mb-8">List Pengadaan Otomatis (ROP Trigger)</h3>
                
                <div class="bg-red-50/50 p-6 rounded-2xl border-l-4 border-red-500 flex justify-between items-center transition hover:bg-red-50">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="font-black text-slate-800 text-lg">PR-2023-0091</span>
                            <span class="bg-red-600 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase">KRITIS</span>
                        </div>
                        <p class="text-sm text-slate-600 font-medium">Barang: <span class="font-bold">Toner Printer</span> | Status: <span class="italic">Menunggu Quotation</span></p>
                    </div>
                    <a href="open_bidding.php?pr=PR-2023-0091" class="bg-red-600 text-white px-8 py-3 rounded-xl font-black text-xs shadow-lg shadow-red-200 hover:-translate-y-1 transition uppercase tracking-widest">
                        OPEN BIDDING
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>