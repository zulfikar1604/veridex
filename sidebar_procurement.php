<div id="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside id="main-sidebar" class="w-[250px] bg-[#111827] text-white flex flex-col h-full shadow-2xl shrink-0">
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
    <div class="flex justify-between items-center p-5 md:p-6 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-black text-red-500 tracking-wider">VERIDEX</h1>
            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-bold">ROLE: <?= htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <button onclick="toggleSidebar()" class="sidebar-close-btn md:hidden"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <nav class="flex-1 px-4 py-5 space-y-1.5 space-y-2 overflow-y-auto">
        
        <?php if ($_SESSION['role'] === 'Manager Procurement'): ?>
            <div class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Strategic Monitor</div>
            
            <a href="dashboard_manager.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'dashboard_manager') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
                <i class="fa-solid fa-chart-pie w-6 text-center mr-2.5 text-[15px]"></i> Command Center
            </a>

            <a href="approval_procurement.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'approval') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
                <i class="fa-solid fa-user-check w-6 text-center mr-2.5 text-[15px]"></i> Approval Manager
            </a>
            
            <div class="my-4 border-t border-slate-800"></div>
        <?php endif; ?>

        <div class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Master Data</div>
        
        <?php if ($_SESSION['role'] === 'Manager Procurement'): ?>
        <a href="master_user.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'master_user') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-users-gear w-6 text-center mr-2.5 text-[15px]"></i> Master User
        </a>
        <?php endif; ?>

        <a href="master_vendor.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'master_vendor') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-building w-6 text-center mr-2.5 text-[15px]"></i> Master Vendor
        </a>

        <div class="my-4 border-t border-slate-800"></div>
        <div class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">AI & Analytics</div>
        
        <a href="ai_item_check.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'ai_analytics') ? 'bg-fuchsia-600 text-white shadow-[0_4px_15px_rgba(192,38,211,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-radar w-6 text-center mr-2.5 text-[15px] <?= ($active == 'ai_analytics') ? '' : 'text-fuchsia-400' ?>"></i> AI Analytics & Prediksi
        </a>
        
        <div class="my-4 border-t border-slate-800"></div>
        <div class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Operational</div>
        
        <a href="dashboard_procurement.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'rop') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-bullseye w-6 text-center mr-2.5 text-[15px]"></i> ROP Dashboard
        </a>

        <a href="strategic_procurement.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'strategic') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-chess-knight w-6 text-center mr-2.5 text-[15px]"></i> Strategic Procurement
        </a>
        
        <a href="buat_po.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'buat_po') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-file-signature w-6 text-center mr-2.5 text-[15px]"></i> Buat PO
        </a>
        
        <a href="diskusi_procurement.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'chat') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-comments w-6 text-center mr-2.5 text-[15px]"></i> Messaging System
        </a>

        <div class="my-4 border-t border-slate-800"></div>
        <div class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Archive</div>

        <a href="arsip_rfq.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'arsip_rfq') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-rectangle-list w-6 text-center mr-2.5 text-[15px]"></i> Arsip RFQ
        </a>

        <a href="sph_management.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'arsip_sph') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-folder-open w-6 text-center mr-2.5 text-[15px]"></i> Arsip SPH
        </a>
        
        <a href="arsip_po.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'arsip_po') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-file-invoice w-6 text-center mr-2.5 text-[15px]"></i> Arsip PO
        </a>

        <a href="arsip_invoice.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'arsip_invoice') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-file-invoice-dollar w-6 text-center mr-2.5 text-[15px]"></i> Arsip Invoice
        </a>
        
        <a href="arsip_approval.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'arsip_approval') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-file-circle-check w-6 text-center mr-2.5 text-[15px]"></i> Arsip Approval
        </a>
        
    </nav>

    <div class="p-5">
        <a href="logout.php" onclick="confirmLogout(event, 'logout.php')" class="block w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-center text-sm font-bold text-slate-300 hover:text-white rounded-lg transition-all">
            Keluar
        </a>
    </div>
</aside>
<?php include 'sweetalert_script.php'; ?>