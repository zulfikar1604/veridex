<?php
// Hitung badge counters untuk sidebar vendor
$_vendor_id = $_SESSION['vendor_id'] ?? 0;
$_badge_rfq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM rfq_vendors rv JOIN rfq_requests r ON rv.rfq_id = r.id WHERE rv.vendor_id = $_vendor_id AND r.status = 'Open' AND rv.has_submitted = 0"))['c'] ?? 0;
$_badge_po = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $_vendor_id AND status = 'Approved'"))['c'] ?? 0;
$_badge_delivery = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $_vendor_id AND status = 'Confirmed by Vendor'"))['c'] ?? 0;
$_badge_invoice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $_vendor_id AND status = 'Goods Received'"))['c'] ?? 0;
?>
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside id="main-sidebar" class="w-[280px] bg-[#111827] text-white flex flex-col h-full shadow-2xl shrink-0">
    <div class="flex justify-between items-center p-6 md:p-6 md:p-8 border-b border-slate-800">
        <div>
        <h1 class="text-3xl font-black text-red-500 tracking-wider">VERIDEX</h1>
        <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-bold">ROLE: VENDOR</p>
            </div>
        <button onclick="toggleSidebar()" class="sidebar-close-btn md:hidden"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <a href="dashboard_vendor.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all <?= ($active == 'bidding') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <span class="flex items-center"><i class="fa-solid fa-gavel w-6 text-center mr-3 text-lg"></i> Bidding Area</span>
            <?php if($_badge_rfq > 0): ?><span class="bg-yellow-400 text-slate-900 text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_rfq ?></span><?php endif; ?>
        </a>
        
        <a href="po_vendor.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all <?= ($active == 'po') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <span class="flex items-center"><i class="fa-solid fa-file-signature w-6 text-center mr-3 text-lg"></i> Konfirmasi PO</span>
            <?php if($_badge_po > 0): ?><span class="bg-red-600 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_po ?></span><?php endif; ?>
        </a>
        
        <a href="delivery_vendor.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all <?= ($active == 'delivery') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <span class="flex items-center"><i class="fa-solid fa-truck-ramp-box w-6 text-center mr-3 text-lg"></i> Delivery</span>
            <?php if($_badge_delivery > 0): ?><span class="bg-blue-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_delivery ?></span><?php endif; ?>
        </a>
        
        <a href="chat_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'chat') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-comment-dots w-6 text-center mr-3 text-lg"></i> 
            <span>Messaging Center</span>
        </a>

        <a href="invoice_vendor.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all <?= ($active == 'invoice') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <span class="flex items-center"><i class="fa-solid fa-file-invoice w-6 text-center mr-3 text-lg"></i> E-Invoicing</span>
            <?php if($_badge_invoice > 0): ?><span class="bg-green-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"><?= $_badge_invoice ?></span><?php endif; ?>
        </a>

        <div class="mt-4 mb-2 px-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Archive</div>

        <a href="arsip_rfq_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'arsip_rfq') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-rectangle-list w-6 text-center mr-3 text-lg"></i> Arsip RFQ
        </a>
        
        <a href="arsip_po_vendor.php" class="flex items-center px-4 py-3.5 rounded-xl transition-all <?= ($active == 'arsip_po') ? 'bg-red-500 text-white shadow-[0_4px_15px_rgba(239,68,68,0.4)] font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800 font-medium' ?>">
            <i class="fa-solid fa-file-invoice w-6 text-center mr-3 text-lg"></i> Arsip PO
        </a>
    </nav>
    
    <div class="p-5">
        <a href="logout.php" onclick="confirmLogout(event, 'logout.php')" class="block w-full py-3.5 px-4 bg-slate-800 hover:bg-slate-700 text-center text-sm font-bold text-slate-300 hover:text-white rounded-xl transition-all">
            Keluar
        </a>
    </div>
</aside>
<?php include 'sweetalert_script.php'; ?>