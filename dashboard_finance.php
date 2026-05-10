<?php
session_start();
// Proteksi Role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Finance') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y'); // Sabtu, 11 April 2026
?>
<?php include 'header_meta.php'; ?>

<body class="bg-slate-50 h-screen flex overflow-hidden">

    <?php 
        $active = '3way'; 
        include 'sidebar_finance.php'; 
    ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">3 WAY</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">F</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 flex flex-col items-center justify-center">
            <div class="bg-white p-12 rounded-[2.5rem] border shadow-sm w-full max-w-5xl">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div class="p-8 border-2 border-green-500 bg-green-50/50 rounded-[2rem] text-center flex flex-col items-center justify-center space-y-4">
                        <i class="fa-solid fa-file-circle-check text-4xl text-green-600"></i>
                        <p class="text-xs font-black text-green-700 tracking-widest uppercase">DATA PO (OK)</p>
                    </div>

                    <div class="p-8 border-2 border-green-500 bg-green-50/50 rounded-[2rem] text-center flex flex-col items-center justify-center space-y-4">
                        <i class="fa-solid fa-truck-ramp-box text-4xl text-green-600"></i>
                        <p class="text-xs font-black text-green-700 tracking-widest uppercase">SCAN GUDANG (OK)</p>
                    </div>

                    <div class="p-8 border-2 border-red-500 bg-red-50 rounded-[2rem] text-center flex flex-col items-center justify-center space-y-4 animate-pulse">
                        <i class="fa-solid fa-file-invoice-dollar text-4xl text-red-600"></i>
                        <p class="text-xs font-black text-red-700 tracking-widest uppercase italic">E-INVOICE (WAITING)</p>
                    </div>
                </div>

                <p class="text-center text-slate-400 italic text-sm">
                    Sistem membandingkan PO, Penerimaan Gudang, dan Faktur secara otomatis.
                </p>

            </div>
        </div>
    </main>
</body>
</html>