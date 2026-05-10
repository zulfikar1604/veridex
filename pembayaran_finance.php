<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('Asia/Jakarta');
$active = 'pembayaran';
include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_finance.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">MANAGEMENT PEMBAYARAN</h2>
                <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">F</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4">
            <div class="flex items-center justify-between p-6 bg-white border rounded-2xl group hover:border-red-500 transition-all shadow-sm">
                <div>
                    <p class="font-black text-slate-800 text-lg">PELUNASAN PO-9901</p>
                    <p class="text-sm text-slate-400 font-medium">PT. Solusi Digital | <span class="text-slate-700 font-bold">Rp 45.000.000</span></p>
                </div>
                <button class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-red-600 transition-colors">
                    BAYAR DP 30%
                </button>
            </div>

            <div class="flex items-center justify-between p-6 bg-white border rounded-2xl group hover:border-red-500 transition-all shadow-sm">
                <div>
                    <p class="font-black text-slate-800 text-lg">PELUNASAN FINAL PO-8812</p>
                    <p class="text-sm text-slate-400 font-medium">CV. Global Tech | <span class="text-slate-700 font-bold">Rp 12.000.000</span></p>
                </div>
                <button class="bg-green-600 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-green-700 transition-colors">
                    LUNASI SEKARANG
                </button>
            </div>
        </div>
    </main>
</body>
</html>