<?php
// Hitung badge counters untuk sidebar finance
$_badge_3way = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Invoice Submitted'"))['c'] ?? 0;
$_badge_payment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Payment Approved'"))['c'] ?? 0;
?>
<aside class="w-[280px] bg-[#111827] text-white flex flex-col h-full shadow-2xl z-20 shrink-0">
    <div class="p-8 border-b border-slate-800">
        <h1 class="text-3xl font-black text-red-500 tracking-wider">VERIDEX</h1>
        <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-bold">ROLE: FINANCE</p>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <a href="dashboard_finance.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all <?= ($active == '3way') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <span class="flex items-center"><i class="fa-solid fa-magnifying-glass-dollar w-6 text-center mr-3 text-lg"></i> 3-Way Matching</span>
            <?php if($_badge_3way > 0): ?><span class="bg-indigo-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_3way ?></span><?php endif; ?>
        </a>
        
        <a href="pembayaran_finance.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all <?= ($active == 'pembayaran') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <span class="flex items-center"><i class="fa-solid fa-money-check-dollar w-6 text-center mr-3 text-lg"></i> Pembayaran</span>
            <?php if($_badge_payment > 0): ?><span class="bg-amber-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_payment ?></span><?php endif; ?>
        </a>
        
        <a href="hutang_dagang.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all <?= ($active == 'hutang_dagang') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <span class="flex items-center"><i class="fa-solid fa-file-invoice-dollar w-6 text-center mr-3 text-lg"></i> Hutang Dagang (AP)</span>
        </a>
        
        <a href="upload_finance.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'upload') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-cloud-arrow-up w-6 text-center mr-3 text-lg"></i> 
            <span>Upload Bukti Bayar</span>
        </a>
    </nav>
    
    <div class="p-5">
        <a href="logout.php" class="block w-full py-3.5 px-4 bg-slate-800 hover:bg-slate-700 text-center text-sm font-bold text-slate-300 hover:text-white rounded-xl transition-all">
            Keluar
        </a>
    </div>
</aside>