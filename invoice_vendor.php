<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}
$active = 'invoice';
include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_vendor.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">E-INVOICING</h2>
                <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">V</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-12 flex flex-col items-center justify-center text-center">
            <div class="max-w-lg">
                <i class="fa-solid fa-file-invoice text-8xl text-slate-100 mb-6"></i>
                <h3 class="font-black text-slate-800 text-2xl mb-2">Penagihan Digital</h3>
                <p class="text-slate-400 text-sm mb-10 italic">Unggah faktur tagihan digital Anda di sini. Pastikan barang sudah terkonfirmasi sampai oleh tim Gudang agar proses matching finance berjalan lancar.</p>
                <button class="px-12 py-5 bg-green-600 text-white rounded-2xl font-black text-xs shadow-2xl shadow-green-100 uppercase tracking-[0.2em] italic hover:bg-green-700 transition">Unggah Digital Invoice</button>
            </div>
        </div>
    </main>
</body>