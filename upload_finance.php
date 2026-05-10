<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('Asia/Jakarta');
$active = 'upload';
include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_finance.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">UPLOAD BUKTI BAYAR</h2>
                <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">F</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 flex items-center justify-center">
            <div class="bg-white p-12 rounded-[3rem] border shadow-sm text-center w-full max-w-2xl">
                <label class="cursor-pointer group">
                    <div class="max-w-md mx-auto p-12 border-4 border-dashed border-slate-100 rounded-[2.5rem] group-hover:border-red-400 group-hover:bg-red-50/30 transition-all">
                        <i class="fa-solid fa-cloud-arrow-up text-7xl text-slate-200 group-hover:text-red-500 transition-all mb-6"></i>
                        <h4 class="font-black text-slate-700 text-xl mb-2">Pilih File Bukti Transfer</h4>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest italic">Format: PDF/JPG (Maks 2MB)</p>
                        <input type="file" class="hidden">
                    </div>
                </label>
                
                <div class="mt-8 space-y-4">
                    <p class="text-xs text-slate-500 font-medium">
                        <i class="fa-solid fa-circle-info mr-1 text-blue-500"></i> Bukti yang diunggah akan muncul secara transparan di dashboard vendor.
                    </p>
                    <button class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-red-600 transition-all">
                        PROSES UNGGAH DATA
                    </button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>