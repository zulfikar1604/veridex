<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$notifs = [];
$total_notifs = 0;
$role = $_SESSION['role'] ?? '';

// --- ROLE-BASED NOTIFICATION QUERIES ---

if ($role === 'Manager Procurement') {
    // 1. PO Pending Manager
    $q_po = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Pending Manager'");
    $count_po = mysqli_fetch_assoc($q_po)['c'] ?? 0;
    if ($count_po > 0) {
        $notifs[] = [
            'title' => 'PO Butuh Persetujuan',
            'desc'  => "$count_po dokumen Purchase Order baru.",
            'icon'  => 'fa-file-signature',
            'color' => 'text-red-500 bg-red-100',
            'link'  => 'approval_procurement.php'
        ];
        $total_notifs += $count_po;
    }
    
    // 2. Vendor Pending
    $q_vendor = mysqli_query($conn, "SELECT COUNT(*) as c FROM vendors WHERE status = 'Pending'");
    $count_vendor = mysqli_fetch_assoc($q_vendor)['c'] ?? 0;
    if ($count_vendor > 0) {
        $notifs[] = [
            'title' => 'Pendaftaran Vendor Baru',
            'desc'  => "$count_vendor vendor menunggu verifikasi.",
            'icon'  => 'fa-building-circle-exclamation',
            'color' => 'text-amber-500 bg-amber-100',
            'link'  => 'master_vendor.php'
        ];
        $total_notifs += $count_vendor;
    }
} 
elseif ($role === 'Procurement') {
    // 1. Stok Kritis (ROP)
    $q_rop = mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory WHERE status_label='Kritis' AND id NOT IN (SELECT inventory_id FROM rfq_requests WHERE status = 'Open')");
    $count_rop = mysqli_fetch_assoc($q_rop)['c'] ?? 0;
    if ($count_rop > 0) {
        $notifs[] = [
            'title' => 'Peringatan Stok Kritis (ROP)',
            'desc'  => "$count_rop SKU membutuhkan pembuatan RFQ.",
            'icon'  => 'fa-triangle-exclamation',
            'color' => 'text-red-600 bg-red-100 border-red-200 border',
            'link'  => 'dashboard_procurement.php'
        ];
        $total_notifs += $count_rop;
    }
}
elseif ($role === 'Vendor') {
    $vendor_id = $_SESSION['vendor_id'] ?? 0;
    
    // 1. PO Baru Diterima (Status Approved, belum dikirim)
    $q_po = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $vendor_id AND status = 'Approved'");
    $count_po = mysqli_fetch_assoc($q_po)['c'] ?? 0;
    if ($count_po > 0) {
        $notifs[] = [
            'title' => 'Pesanan Baru (PO)',
            'desc'  => "Anda menerima $count_po PO baru untuk diproses.",
            'icon'  => 'fa-cart-flatbed',
            'color' => 'text-blue-500 bg-blue-100',
            'link'  => 'terima_po_vendor.php'
        ];
        $total_notifs += $count_po;
    }

    // 2. Butuh Upload Invoice (Goods Received)
    $q_inv = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $vendor_id AND status = 'Goods Received'");
    $count_inv = mysqli_fetch_assoc($q_inv)['c'] ?? 0;
    if ($count_inv > 0) {
        $notifs[] = [
            'title' => 'Tagihan Pending',
            'desc'  => "$count_inv pengiriman sudah tiba, segera upload invoice.",
            'icon'  => 'fa-file-invoice-dollar',
            'color' => 'text-amber-500 bg-amber-100',
            'link'  => 'invoice_vendor.php'
        ];
        $total_notifs += $count_inv;
    }
}
elseif ($role === 'Gudang') {
    // 1. Barang Sedang Dikirim Vendor (Shipped)
    $q_shipped = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Shipped'");
    $count_shipped = mysqli_fetch_assoc($q_shipped)['c'] ?? 0;
    if ($count_shipped > 0) {
        $notifs[] = [
            'title' => 'Pengiriman Dalam Perjalanan',
            'desc'  => "$count_shipped armada vendor sedang menuju gudang.",
            'icon'  => 'fa-truck-fast',
            'color' => 'text-indigo-500 bg-indigo-100',
            'link'  => 'scan_barcode_inbound.php'
        ];
        $total_notifs += $count_shipped;
    }
}
elseif ($role === 'Finance') {
    // 1. Butuh 3-Way Matching (Invoice Submitted)
    $q_3way = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Invoice Submitted'");
    $count_3way = mysqli_fetch_assoc($q_3way)['c'] ?? 0;
    if ($count_3way > 0) {
        $notifs[] = [
            'title' => 'Menunggu 3-Way Matching',
            'desc'  => "$count_3way invoice vendor butuh dicocokkan.",
            'icon'  => 'fa-magnifying-glass-dollar',
            'color' => 'text-indigo-500 bg-indigo-100',
            'link'  => 'dashboard_finance.php'
        ];
        $total_notifs += $count_3way;
    }

    // 2. Siap Dibayar (Payment Approved)
    $q_pay = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Payment Approved'");
    $count_pay = mysqli_fetch_assoc($q_pay)['c'] ?? 0;
    if ($count_pay > 0) {
        $notifs[] = [
            'title' => 'Tagihan Siap Dibayar',
            'desc'  => "$count_pay PO harus segera ditransfer ke vendor.",
            'icon'  => 'fa-money-check-dollar',
            'color' => 'text-green-600 bg-green-100',
            'link'  => 'pembayaran_finance.php'
        ];
        $total_notifs += $count_pay;
    }
}

// Ensure AlpineJS is loaded if not already in the parent document (it is in Veridex usually, but good practice to have it available in headers)
// Usually the parent has `<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>`
?>

<!-- Notification Component (Alpine.js) -->
<div x-data="{ openNotif: false }" class="relative">
    <button @click="openNotif = !openNotif" @click.away="openNotif = false" class="relative w-12 h-12 flex items-center justify-center bg-white rounded-2xl border border-slate-100 text-slate-500 hover:text-slate-800 hover:shadow-md transition">
        <i class="fa-regular fa-bell text-xl"></i>
        <?php if($total_notifs > 0): ?>
            <span class="absolute top-2 right-2 w-3.5 h-3.5 bg-red-500 border-2 border-white rounded-full flex items-center justify-center animate-pulse"></span>
        <?php endif; ?>
    </button>

    <!-- Dropdown -->
    <div x-show="openNotif" x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-3 w-80 bg-white rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-slate-100 z-50 overflow-hidden">
        
        <div class="p-4 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
            <h4 class="font-black text-slate-800 uppercase tracking-widest text-xs">Notifikasi</h4>
            <?php if($total_notifs > 0): ?>
                <span class="bg-red-100 text-red-600 text-[9px] font-black px-2 py-1 rounded-lg"><?= $total_notifs ?> Baru</span>
            <?php endif; ?>
        </div>

        <div class="max-h-80 overflow-y-auto">
            <?php if(count($notifs) > 0): ?>
                <?php foreach($notifs as $n): ?>
                    <a href="<?= $n['link'] ?>" class="flex items-start gap-4 p-4 border-b border-slate-50 hover:bg-slate-50 transition block">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $n['color'] ?>">
                            <i class="fa-solid <?= $n['icon'] ?>"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800 mb-0.5"><?= $n['title'] ?></p>
                            <p class="text-xs font-medium text-slate-500 leading-snug"><?= $n['desc'] ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-slate-400">
                    <i class="fa-regular fa-bell-slash text-4xl mb-3 opacity-50 block"></i>
                    <p class="text-xs font-bold uppercase tracking-widest">Belum ada notifikasi</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
