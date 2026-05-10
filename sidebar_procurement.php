<aside class="w-72 bg-slate-900 text-white flex flex-col shadow-xl h-screen">
    <div class="p-6 border-b border-slate-800">
        <h1 class="text-2xl font-black text-red-500">VERIDEX</h1>
        <!-- Menampilkan Role Dinamis sesuai Login[cite: 1] -->
        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">ROLE: <?= htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <div class="space-y-1">
            
            <!-- MENU KHUSUS MANAGER (DASHBOARD UTAMA)[cite: 1, 2] -->
            <?php if ($_SESSION['role'] === 'Manager Procurement'): ?>
                <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Strategic Monitor</div>
                
                <a href="kinerja_vendor.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'kinerja_vendor') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-chart-line mr-3"></i> Dashboard Utama (Kinerja)
                </a>

                <a href="approval_procurement.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'approval') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-user-check mr-3"></i> Approval Manager
                </a>
                
                <div class="my-4 border-t border-slate-800"></div>
            <?php endif; ?>

            <!-- MENU OPERASIONAL (UNTUK SEMUA / STAFF & MANAGER)[cite: 1] -->
            <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Operational</div>
            
            <a href="strategic_procurement.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'strategic') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
             <i class="fa-solid fa-chess-knight mr-3"></i> Strategic Procurement
            </a>
            
            <a href="chat_procurement.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'chat') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:bg-slate-800' ?>">
                <i class="fa-solid fa-comments mr-3"></i> Messaging System
            </a>

            <!-- MENU ARSIP[cite: 1] -->
            <div class="px-3 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-4">Archive</div>

            <a href="sph_management.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_sph') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-folder-open mr-3"></i> Arsip SPH
            </a>

            <a href="arsip_invoice.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_invoice') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                <i class="fa-solid fa-file-invoice-dollar mr-3"></i> Arsip Invoice
            </a>
            
            <a href="arsip_approval.php" class="w-full p-3 text-left flex items-center transition-all <?= ($active == 'arsip_approval') ? 'bg-red-500 text-white rounded-lg font-bold shadow-lg shadow-red-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800' ?>">
                 <i class="fa-solid fa-file-circle-check mr-3"></i> Arsip Approval
            </a>
        
        </div>
    </nav>

    <div class="p-4 bg-slate-950">
        <a href="logout.php" class="block w-full py-2 bg-gray-700 hover:bg-red-600 rounded-lg text-sm text-center transition font-bold">Keluar</a>
    </div>
</aside>
