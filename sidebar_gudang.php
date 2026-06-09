<div id="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside id="main-sidebar" class="w-[280px] bg-[#111827] text-white flex flex-col h-full shadow-2xl shrink-0">
    <script>
        // Apply collapsed state immediately on load to prevent layout flash
        (function() {
            if (window.innerWidth >= 768) {
                const savedState = localStorage.getItem('sidebar-collapsed');
                const isCollapsed = savedState === 'true' || (savedState === null && window.innerWidth < 1024);
                if (isCollapsed) {
                    document.getElementById('main-sidebar').classList.add('sidebar-collapsed');
                }
            }
        })();
    </script>
    <div class="flex justify-between items-center p-6 md:p-6 md:p-8 border-b border-slate-800">
        <div>
        <h1 class="text-3xl font-black text-red-500 tracking-wider">VERIDEX</h1>
        <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-bold">ROLE: GUDANG</p>
            </div>
        <button onclick="toggleSidebar()" class="sidebar-close-btn md:hidden"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <a href="scan_barcode_inbound.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active_menu == 'inbound') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-download w-6 text-center mr-3 text-lg"></i> 
            <span>Scan Barcode Inbound</span>
        </a>
        <a href="scan_barcode_outbound.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active_menu == 'outbound') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
    <i class="fa-solid fa-upload w-6 text-center mr-3 text-lg"></i> 
    <span>Scan Barcode Outbound</span>
</a>
        <a href="riwayat_inbound.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active_menu == 'riwayat_inbound') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-clock-rotate-left w-6 text-center mr-3 text-lg"></i> 
            <span>Riwayat Inbound</span>
        </a>

        <a href="riwayat_outbound.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active_menu == 'riwayat_outbound') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-clock-rotate-left w-6 text-center mr-3 text-lg"></i> 
            <span>Riwayat Outbound</span>
        </a>
        
        <a href="dashboard_gudang.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active_menu == 'inventory') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-table-list w-6 text-center mr-3 text-lg"></i> 
            <span>Monitoring Stok RT</span>
        </a>
        
        <a href="notifikasi_rop.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active_menu == 'rop') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-triangle-exclamation w-6 text-center mr-3 text-lg <?= ($active_menu == 'rop') ? '' : 'text-red-400' ?>"></i> 
            <span>Notifikasi ROP</span>
        </a>
    </nav>
    
    <div class="p-5">
        <a href="logout.php" onclick="confirmLogout(event, 'logout.php')" class="block w-full py-3.5 px-4 bg-slate-800 hover:bg-slate-700 text-center text-sm font-bold text-slate-300 hover:text-white rounded-xl transition-all">
            Keluar
        </a>
    </div>
</aside>
<?php include 'sweetalert_script.php'; ?>