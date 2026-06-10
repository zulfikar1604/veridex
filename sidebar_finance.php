<?php
// Hitung badge counters untuk sidebar finance
$_badge_3way = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Invoice Submitted'"))['c'] ?? 0;
$_badge_payment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Payment Approved'"))['c'] ?? 0;
?>
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
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-widest font-bold">ROLE: FINANCE</p>
        </div>
        <button onclick="toggleSidebar()" class="sidebar-close-btn md:hidden"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <nav class="flex-1 px-4 py-5 space-y-1.5 space-y-2 overflow-y-auto">
        <div class="px-4 py-2 text-[10px] font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Operations</div>

        <a href="dashboard_finance.php" class="flex items-center justify-between px-4 py-2.5 rounded-lg transition-all <?= ($active == '3way') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <span class="flex items-center"><i class="fa-solid fa-magnifying-glass-dollar w-6 text-center mr-2.5 text-[15px]"></i> 3-Way Matching</span>
            <?php if($_badge_3way > 0): ?><span class="bg-indigo-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_3way ?></span><?php endif; ?>
        </a>
        
        <a href="pembayaran_finance.php" class="flex items-center justify-between px-4 py-2.5 rounded-lg transition-all <?= ($active == 'pembayaran') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <span class="flex items-center"><i class="fa-solid fa-money-check-dollar w-6 text-center mr-2.5 text-[15px]"></i> Pembayaran</span>
            <?php if($_badge_payment > 0): ?><span class="bg-amber-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_payment ?></span><?php endif; ?>
        </a>
        
        <a href="hutang_dagang.php" class="flex items-center justify-between px-4 py-2.5 rounded-lg transition-all <?= ($active == 'hutang_dagang') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <span class="flex items-center"><i class="fa-solid fa-file-invoice-dollar w-6 text-center mr-2.5 text-[15px]"></i> Hutang Dagang (AP)</span>
        </a>
        
        <a href="upload_finance.php" class="flex items-center px-4 py-2.5 rounded-lg transition-all <?= ($active == 'upload') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold text-[13px]' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium text-[13px]' ?>">
            <i class="fa-solid fa-cloud-arrow-up w-6 text-center mr-2.5 text-[15px]"></i> 
            <span>Upload Bukti Bayar</span>
        </a>
    </nav>
    
    <div class="p-5">
        <a href="logout.php" onclick="confirmLogout(event, 'logout.php')" class="block w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-center text-sm font-bold text-slate-300 hover:text-white rounded-lg transition-all">
            Keluar
        </a>
    </div>
</aside>
<?php include 'sweetalert_script.php'; ?>