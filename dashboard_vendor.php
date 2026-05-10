<?php
session_start();
// Proteksi Role Vendor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('l, d F Y'); // Sabtu, 11 April 2026
?>
<?php include 'header_meta.php'; ?>

<body class="bg-slate-50 h-screen flex overflow-hidden">

    <?php 
        $active = 'bidding'; 
        include 'sidebar_vendor.php'; 
    ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">BIDDING</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">V</div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 flex flex-col items-center justify-center">
            <div class="bg-white p-12 rounded-[2.5rem] border shadow-sm w-full max-w-5xl">
                
                <h3 class="text-red-600 font-black text-xl italic underline uppercase tracking-tighter mb-8">
                    BIDDING AREA (PENGADAAN TERBUKA)
                </h3>

                <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h4 class="text-2xl font-black text-slate-800">Pengadaan Alat Tulis Kantor Q4</h4>
                            <p class="text-xs text-slate-400 font-bold italic mt-1 uppercase tracking-widest">
                                Batas Pengajuan: 18:00 WIB Hari Ini
                            </p>
                        </div>
                        <span class="bg-red-600 text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-200">
                            OPEN
                        </span>
                    </div>

                    <button class="w-full bg-[#111827] text-white py-5 rounded-2xl font-black text-sm uppercase tracking-[0.2em] shadow-xl hover:bg-red-600 transition-all duration-300 italic">
                        UNGGAH SURAT PENAWARAN HARGA (SPH)
                    </button>
                </div>

            </div>
        </div>
    </main>
</body>
</html>