<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}
$active = 'delivery';
include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_vendor.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">DELIVERY MANAGEMENT</h2>
                <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">V</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 flex items-center justify-center">
            <div class="bg-white p-12 rounded-[2.5rem] border shadow-sm w-full max-w-xl">
                <h4 class="font-black text-slate-800 mb-6 text-xl">Input Bukti Pengiriman</h4>
                <div class="space-y-4">
                    <input type="text" placeholder="No. Resi / No. Surat Jalan" class="w-full border p-4 rounded-xl outline-red-500 bg-slate-50 font-bold">
                    <div class="border-2 border-dashed border-slate-200 p-8 text-center rounded-2xl text-slate-400 text-xs italic font-medium group hover:border-red-400 transition cursor-pointer">
                        <i class="fa-solid fa-camera text-3xl mb-2"></i><br>Unggah Foto Surat Jalan (Tanda Tangan & Cap)
                    </div>
                    <button class="w-full bg-slate-900 text-white py-4 rounded-xl font-black shadow-xl shadow-slate-100 uppercase text-xs italic tracking-[0.2em] hover:bg-red-600 transition">Submit Data Pengiriman</button>
                </div>
            </div>
        </div>
    </main>
</body>