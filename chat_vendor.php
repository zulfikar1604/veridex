<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}
$active = 'chat';
include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_vendor.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">MESSAGING CENTER</h2>
                <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">V</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-12 flex flex-col items-center justify-center text-center">
            <div class="max-w-md">
                <i class="fa-solid fa-headset text-8xl text-slate-100 mb-6"></i>
                <h3 class="font-black text-slate-800 text-2xl mb-2">Pusat Pesan Vendor</h3>
                <p class="text-slate-400 text-sm mb-8 italic">Fitur komunikasi khusus dengan Tim Procurement mengenai spesifikasi produk atau negosiasi kontrak.</p>
                <button class="px-10 py-4 bg-slate-100 border border-slate-200 rounded-full text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition">Buka Pesan Terakhir</button>
            </div>
        </div>
    </main>
</body>