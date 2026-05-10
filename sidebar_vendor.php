<aside class="w-[280px] bg-[#111827] text-white flex flex-col h-full shadow-2xl z-20 shrink-0">
    <div class="p-8 border-b border-slate-800">
        <h1 class="text-3xl font-black text-red-500 tracking-wider">VERIDEX</h1>
        <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-bold">ROLE: VENDOR</p>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <a href="dashboard_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'bidding') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-gavel w-6 text-center mr-3 text-lg"></i> 
            <span>Bidding Area</span>
        </a>
        
        <a href="po_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'po') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-file-signature w-6 text-center mr-3 text-lg"></i> 
            <span>Manajemen & Konfirmasi PO</span>
        </a>
        
        <a href="delivery_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'delivery') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-truck-ramp-box w-6 text-center mr-3 text-lg"></i> 
            <span>Delivery Management</span>
        </a>
        
        <a href="chat_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'chat') ? 'bg-red-500 text-white shadow-[0_4_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-comment-dots w-6 text-center mr-3 text-lg"></i> 
            <span>Messaging Center</span>
        </a>

        <a href="invoice_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'invoice') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-file-invoice w-6 text-center mr-3 text-lg"></i> 
            <span>E-Invoicing</span>
        </a>
    </nav>
    
    <div class="p-5">
        <a href="logout.php" class="block w-full py-3.5 px-4 bg-slate-800 hover:bg-slate-700 text-center text-sm font-bold text-slate-300 hover:text-white rounded-xl transition-all">
            Keluar
        </a>
    </div>
</aside>