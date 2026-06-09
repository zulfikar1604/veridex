<div id="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside id="main-sidebar" class="w-[280px] bg-slate-900 text-white flex flex-col shadow-xl h-full shrink-0">
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
    <div class="flex justify-between items-center p-6 border-b border-slate-800">
        <div>
        <h1 class="text-2xl font-black text-red-500">VERIDEX</h1>
        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">ROLE: <?= htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <button onclick="toggleSidebar()" class="sidebar-close-btn md:hidden"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <div class="space-y-1">
            
            <?php if ($_SESSION['role'] === 'Manager Procurement'): ?>
                <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Strategic Monitor</div>
                
                <a href="dashboard_manager.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'dashboard_manager') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-chart-pie w-6"></i> Command Center
                </a>

                <a href="approval_procurement.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'approval') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-user-check w-6"></i> Approval Manager
                </a>
                
                <div class="my-4 border-t border-slate-800"></div>
            <?php endif; ?>

            <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Master Data</div>
            
            <?php if ($_SESSION['role'] === 'Manager Procurement'): ?>
            <a href="master_user.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'master_user') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-users-gear w-6"></i> Master User
            </a>
            <?php endif; ?>

            <a href="master_vendor.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'master_vendor') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-building w-6"></i> Master Vendor
            </a>

            <div class="my-4 border-t border-slate-800"></div>
            <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">AI & Analytics</div>
            
            <a href="ai_item_check.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'ai_analytics') ? 'bg-fuchsia-600 text-white rounded-lg font-bold shadow-lg shadow-fuchsia-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-radar w-6 text-fuchsia-400 group-hover:text-white"></i> AI Analytics & Prediksi
            </a>
            
            <div class="my-4 border-t border-slate-800"></div>

            <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Operational</div>
            
            <a href="dashboard_procurement.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'rop') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
             <i class="fa-solid fa-bullseye w-6"></i> ROP Dashboard
            </a>

            <a href="strategic_procurement.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'strategic') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
             <i class="fa-solid fa-chess-knight w-6"></i> Strategic Procurement
            </a>
            
            <a href="buat_po.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'buat_po') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
             <i class="fa-solid fa-file-signature w-6"></i> Buat PO
            </a>
            
            <a href="diskusi_procurement.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'chat') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:bg-slate-800' ?>">
                <i class="fa-solid fa-comments w-6"></i> Messaging System
            </a>

            <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-4">Archive</div>

            <a href="arsip_rfq.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_rfq') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-rectangle-list w-6"></i> Arsip RFQ
            </a>

            <a href="sph_management.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_sph') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-folder-open w-6"></i> Arsip SPH
            </a>
            
            <a href="arsip_po.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_po') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-file-invoice w-6"></i> Arsip PO
            </a>

            <a href="arsip_invoice.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_invoice') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-file-invoice-dollar w-6"></i> Arsip Invoice
            </a>
            
            <a href="arsip_approval.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_approval') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                 <i class="fa-solid fa-file-circle-check w-6"></i> Arsip Approval
            </a>
        
        </div>
    </nav>

    <div class="p-4 bg-slate-950">
        <a href="logout.php" onclick="confirmLogout(event, 'logout.php')" class="block w-full py-2 bg-gray-700 hover:bg-red-600 rounded-lg text-sm text-center transition font-bold">Keluar</a>
    </div>
</aside>
<?php include 'sweetalert_script.php'; ?>