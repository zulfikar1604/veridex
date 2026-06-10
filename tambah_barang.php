<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}
$page_title = "Tambah Barang";
$page_subtitle = "Daftarkan inventaris/barang baru ke gudang";
$active = 'inventory'; 
include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_gudang.php'; ?>

        <main class="flex-1 overflow-y-auto p-10 flex items-center justify-center">
            <div class="bg-white dark:bg-[#1e293b] p-8 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 w-full max-w-lg">
                <div class="flex items-center gap-4 mb-6">
                    <button onclick="toggleSidebar()" class="sidebar-toggle-btn" title="Toggle Sidebar"><i class="fa-solid fa-bars text-sm"></i></button>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase">Tambah Barang</h2>
                </div>
                
                <form action="proses_tambah.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-widest mb-1">Nama Barang</label>
                        <input type="text" name="nama_barang" required class="w-full border border-slate-350 dark:border-slate-700 p-3 rounded-lg focus:outline-red-500 bg-white dark:bg-[#0f172a] text-slate-800 dark:text-white transition-colors">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-widest mb-1">Unit / Satuan</label>
                        <select name="unit" class="w-full border border-slate-350 dark:border-slate-700 p-3 rounded-lg focus:outline-red-500 bg-white dark:bg-[#0f172a] text-slate-800 dark:text-white transition-colors">
                            <option value="Pcs">Pcs</option>
                            <option value="Rim">Rim</option>
                            <option value="Botol">Botol</option>
                            <option value="Box">Box</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-widest mb-1">Batas Stok Kritis (ROP)</label>
                        <input type="number" name="stok_kritis" required class="w-full border border-slate-350 dark:border-slate-700 p-3 rounded-lg focus:outline-red-500 bg-white dark:bg-[#0f172a] text-slate-800 dark:text-white transition-colors">
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 italic">*Sistem akan memberi peringatan jika stok di bawah angka ini.</p>
                    </div>

                    <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition uppercase tracking-widest mt-4 shadow-md shadow-red-500/20">
                        Simpan & Generate Barcode
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>