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
            'desc'  => "$count_po dokumen Purchase Order baru menunggu approval.",
            'icon'  => 'fa-file-signature',
            'color' => 'text-red-500 bg-red-100',
            'link'  => 'approval_procurement.php'
        ];
        $total_notifs += $count_po;
    }
    
    // 2. Vendor Pending Approval
    $q_vendor = mysqli_query($conn, "SELECT COUNT(*) as c FROM vendors WHERE status = 'Pending'");
    $count_vendor = mysqli_fetch_assoc($q_vendor)['c'] ?? 0;
    if ($count_vendor > 0) {
        $notifs[] = [
            'title' => 'Pendaftaran Vendor Baru',
            'desc'  => "$count_vendor vendor menunggu verifikasi Anda.",
            'icon'  => 'fa-building-circle-exclamation',
            'color' => 'text-amber-500 bg-amber-100',
            'link'  => 'master_vendor.php'
        ];
        $total_notifs += $count_vendor;
    }

    // 3. Stok Kritis
    $q_kritis = mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory WHERE status_label='Kritis'");
    $count_kritis = mysqli_fetch_assoc($q_kritis)['c'] ?? 0;
    if ($count_kritis > 0) {
        $notifs[] = [
            'title' => 'Stok Kritis',
            'desc'  => "$count_kritis item berada di bawah batas ROP.",
            'icon'  => 'fa-triangle-exclamation',
            'color' => 'text-orange-600 bg-orange-100',
            'link'  => 'dashboard_manager.php'
        ];
        $total_notifs += $count_kritis;
    }

    // 4. RFQ aktif
    $q_rfq = mysqli_query($conn, "SELECT COUNT(*) as c FROM rfq_requests WHERE status = 'Open'");
    $count_rfq = mysqli_fetch_assoc($q_rfq)['c'] ?? 0;
    if ($count_rfq > 0) {
        $notifs[] = [
            'title' => 'RFQ Aktif',
            'desc'  => "$count_rfq RFQ sedang menunggu penawaran vendor.",
            'icon'  => 'fa-paper-plane',
            'color' => 'text-blue-500 bg-blue-100',
            'link'  => 'arsip_rfq.php'
        ];
    }
} 
elseif ($role === 'Procurement') {
    // 1. Stok Kritis (ROP) belum dibuatkan RFQ
    $q_rop = mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory WHERE status_label='Kritis' AND id NOT IN (SELECT inventory_id FROM rfq_requests WHERE status = 'Open')");
    $count_rop = mysqli_fetch_assoc($q_rop)['c'] ?? 0;
    if ($count_rop > 0) {
        $notifs[] = [
            'title' => 'Stok Kritis — Butuh RFQ',
            'desc'  => "$count_rop SKU kritis belum dibuatkan RFQ.",
            'icon'  => 'fa-triangle-exclamation',
            'color' => 'text-red-600 bg-red-100',
            'link'  => 'dashboard_procurement.php'
        ];
        $total_notifs += $count_rop;
    }

    // 2. SPH masuk siap evaluasi
    $q_sph = mysqli_query($conn, "SELECT COUNT(*) as c FROM sph_submissions WHERE status = 'Submitted'");
    $count_sph = mysqli_fetch_assoc($q_sph)['c'] ?? 0;
    if ($count_sph > 0) {
        $notifs[] = [
            'title' => 'SPH Masuk',
            'desc'  => "$count_sph penawaran harga baru dari vendor.",
            'icon'  => 'fa-envelope-open-text',
            'color' => 'text-blue-500 bg-blue-100',
            'link'  => 'strategic_procurement.php'
        ];
        $total_notifs += $count_sph;
    }

    // 3. PO Siap digenerate (Selected tapi belum ada PO)
    $q_ready = mysqli_query($conn, "SELECT COUNT(*) as c FROM sph_submissions s LEFT JOIN purchase_orders po ON po.rfq_id = s.rfq_id AND po.vendor_id = s.vendor_id WHERE s.status = 'Selected' AND po.id IS NULL");
    $count_ready = mysqli_fetch_assoc($q_ready)['c'] ?? 0;
    if ($count_ready > 0) {
        $notifs[] = [
            'title' => 'PO Siap Digenerate',
            'desc'  => "$count_ready vendor terpilih menunggu pembuatan PO.",
            'icon'  => 'fa-file-circle-plus',
            'color' => 'text-green-600 bg-green-100',
            'link'  => 'buat_po.php'
        ];
        $total_notifs += $count_ready;
    }

    // 4. Stok Warning
    $q_warn = mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory WHERE status_label='Warning'");
    $count_warn = mysqli_fetch_assoc($q_warn)['c'] ?? 0;
    if ($count_warn > 0) {
        $notifs[] = [
            'title' => 'Stok Warning',
            'desc'  => "$count_warn SKU mendekati batas minimum.",
            'icon'  => 'fa-clock-rotate-left',
            'color' => 'text-yellow-600 bg-yellow-100',
            'link'  => 'dashboard_procurement.php'
        ];
    }
}
elseif ($role === 'Vendor') {
    $vendor_id = $_SESSION['vendor_id'] ?? 0;
    
    // 1. RFQ Terbuka — belum submit penawaran
    $q_rfq = mysqli_query($conn, "SELECT COUNT(*) as c FROM rfq_vendors rv JOIN rfq_requests r ON rv.rfq_id = r.id WHERE rv.vendor_id = $vendor_id AND r.status = 'Open' AND rv.has_submitted = 0");
    $count_rfq = mysqli_fetch_assoc($q_rfq)['c'] ?? 0;
    if ($count_rfq > 0) {
        $notifs[] = [
            'title' => 'RFQ Terbuka',
            'desc'  => "$count_rfq undangan pengadaan menunggu penawaran Anda.",
            'icon'  => 'fa-gavel',
            'color' => 'text-red-500 bg-red-100',
            'link'  => 'dashboard_vendor.php'
        ];
        $total_notifs += $count_rfq;
    }

    // 2. PO Baru — perlu dikonfirmasi vendor
    $q_po = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $vendor_id AND status = 'Approved'");
    $count_po = mysqli_fetch_assoc($q_po)['c'] ?? 0;
    if ($count_po > 0) {
        $notifs[] = [
            'title' => 'Pesanan Baru (PO)',
            'desc'  => "$count_po PO baru yang perlu dikonfirmasi.",
            'icon'  => 'fa-cart-flatbed',
            'color' => 'text-blue-500 bg-blue-100',
            'link'  => 'po_vendor.php'
        ];
        $total_notifs += $count_po;
    }

    // 3. Perlu pengiriman (Confirmed by Vendor)
    $q_delivery = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $vendor_id AND status = 'Confirmed by Vendor'");
    $count_delivery = mysqli_fetch_assoc($q_delivery)['c'] ?? 0;
    if ($count_delivery > 0) {
        $notifs[] = [
            'title' => 'Menunggu Pengiriman',
            'desc'  => "$count_delivery pesanan siap untuk dikirim.",
            'icon'  => 'fa-truck-ramp-box',
            'color' => 'text-indigo-500 bg-indigo-100',
            'link'  => 'delivery_vendor.php'
        ];
        $total_notifs += $count_delivery;
    }

    // 4. Butuh Upload Invoice (Goods Received)
    $q_inv = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $vendor_id AND status = 'Goods Received'");
    $count_inv = mysqli_fetch_assoc($q_inv)['c'] ?? 0;
    if ($count_inv > 0) {
        $notifs[] = [
            'title' => 'Upload Invoice',
            'desc'  => "$count_inv pengiriman diterima gudang, upload invoice sekarang.",
            'icon'  => 'fa-file-invoice-dollar',
            'color' => 'text-amber-500 bg-amber-100',
            'link'  => 'invoice_vendor.php'
        ];
        $total_notifs += $count_inv;
    }

    // 5. Pembayaran Selesai
    $q_paid = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE vendor_id = $vendor_id AND status = 'Paid'");
    $count_paid = mysqli_fetch_assoc($q_paid)['c'] ?? 0;
    if ($count_paid > 0) {
        $notifs[] = [
            'title' => 'Pembayaran Diterima',
            'desc'  => "$count_paid pembayaran telah ditransfer.",
            'icon'  => 'fa-circle-check',
            'color' => 'text-green-600 bg-green-100',
            'link'  => 'arsip_po_vendor.php'
        ];
    }
}
elseif ($role === 'Gudang') {
    // 1. Barang Dalam Perjalanan (Shipped)
    $q_shipped = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Shipped'");
    $count_shipped = mysqli_fetch_assoc($q_shipped)['c'] ?? 0;
    if ($count_shipped > 0) {
        $notifs[] = [
            'title' => 'Barang Dalam Perjalanan',
            'desc'  => "$count_shipped pengiriman vendor menuju gudang.",
            'icon'  => 'fa-truck-fast',
            'color' => 'text-indigo-500 bg-indigo-100',
            'link'  => 'scan_barcode_inbound.php'
        ];
        $total_notifs += $count_shipped;
    }

    // 2. Stok Kritis
    $q_kritis = mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory WHERE status_label='Kritis'");
    $count_kritis = mysqli_fetch_assoc($q_kritis)['c'] ?? 0;
    if ($count_kritis > 0) {
        $notifs[] = [
            'title' => 'Stok Kritis',
            'desc'  => "$count_kritis item berada di bawah batas minimum.",
            'icon'  => 'fa-triangle-exclamation',
            'color' => 'text-red-600 bg-red-100',
            'link'  => 'notifikasi_rop.php'
        ];
        $total_notifs += $count_kritis;
    }

    // 3. Stok Warning
    $q_warn = mysqli_query($conn, "SELECT COUNT(*) as c FROM inventory WHERE status_label='Warning'");
    $count_warn = mysqli_fetch_assoc($q_warn)['c'] ?? 0;
    if ($count_warn > 0) {
        $notifs[] = [
            'title' => 'Stok Warning',
            'desc'  => "$count_warn item mendekati batas minimum.",
            'icon'  => 'fa-clock-rotate-left',
            'color' => 'text-yellow-600 bg-yellow-100',
            'link'  => 'dashboard_gudang.php'
        ];
        $total_notifs += $count_warn;
    }
}
elseif ($role === 'Finance') {
    // 1. Butuh 3-Way Matching (Invoice Submitted)
    $q_3way = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'Invoice Submitted'");
    $count_3way = mysqli_fetch_assoc($q_3way)['c'] ?? 0;
    if ($count_3way > 0) {
        $notifs[] = [
            'title' => 'Menunggu 3-Way Matching',
            'desc'  => "$count_3way invoice vendor butuh verifikasi.",
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
            'desc'  => "$count_pay PO siap ditransfer ke vendor.",
            'icon'  => 'fa-money-check-dollar',
            'color' => 'text-green-600 bg-green-100',
            'link'  => 'pembayaran_finance.php'
        ];
        $total_notifs += $count_pay;
    }

    // 3. Hutang Dagang Belum Lunas
    $q_hutang = mysqli_query($conn, "SELECT COUNT(*) as c FROM purchase_orders WHERE status IN ('Payment Approved', 'Invoice Submitted')");
    $count_hutang = mysqli_fetch_assoc($q_hutang)['c'] ?? 0;
    if ($count_hutang > 0) {
        $notifs[] = [
            'title' => 'Outstanding AP',
            'desc'  => "$count_hutang tagihan belum lunas.",
            'icon'  => 'fa-file-invoice-dollar',
            'color' => 'text-amber-500 bg-amber-100',
            'link'  => 'hutang_dagang.php'
        ];
    }
}

// Load Alpine.js (once)
?>
<?php if (!isset($GLOBALS['alpine_loaded'])): $GLOBALS['alpine_loaded'] = true; ?>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php endif; ?>

<style>
    /* Notification dropdown responsive */
    .notif-dropdown {
        position: fixed;
        top: auto;
        right: 12px;
        width: calc(100vw - 24px);
        max-width: 380px;
        max-height: 70vh;
        z-index: 999;
        border-radius: 1.25rem;
        background: #fff;
        box-shadow: 0 10px 50px rgba(0,0,0,0.15);
        border: 1px solid #f1f5f9;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    @media (min-width: 768px) {
        .notif-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.75rem;
            width: 380px;
            max-height: 420px;
        }
    }
</style>

<!-- Notification Component (Alpine.js) -->
<div x-data="{ openNotif: false }" class="relative">
    <button @click="openNotif = !openNotif" class="relative w-9 h-9 flex items-center justify-center rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition group shrink-0" title="Notifikasi">
        <i class="fa-regular fa-bell text-[14px]"></i>
        <?php if($total_notifs > 0): ?>
            <span class="absolute -top-1 -right-1 min-w-[16px] h-[16px] px-1 bg-brand-500 bg-red-500 text-white text-[9px] font-black rounded-full flex items-center justify-center shadow-sm border-2 border-slate-900"><?= $total_notifs ?></span>
        <?php endif; ?>
    </button>

    <!-- Overlay for mobile -->
    <div x-show="openNotif" x-cloak @click="openNotif = false" class="fixed inset-0 bg-slate-900/30 z-[998] md:hidden"></div>

    <!-- Dropdown -->
    <div x-show="openNotif" x-cloak 
         @click.away="openNotif = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="notif-dropdown">
        
        <!-- Header -->
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80 flex justify-between items-center shrink-0">
            <h4 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-[11px]">
                <i class="fa-solid fa-bell text-red-500 mr-1.5"></i> Notifikasi
            </h4>
            <div class="flex items-center gap-2">
                <?php if($total_notifs > 0): ?>
                    <span class="bg-red-500 text-white text-[9px] font-black px-2.5 py-1 rounded-lg"><?= $total_notifs ?> Baru</span>
                <?php endif; ?>
                <button @click="openNotif = false" class="w-7 h-7 rounded-lg bg-slate-200/60 text-slate-400 dark:text-slate-500 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Items -->
        <div class="flex-1 overflow-y-auto">
            <?php if(count($notifs) > 0): ?>
                <?php foreach($notifs as $n): ?>
                    <a href="<?= $n['link'] ?>" class="flex items-start gap-3 px-4 py-3.5 border-b border-slate-50 hover:bg-red-50/40 transition group">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $n['color'] ?> group-hover:scale-110 transition-transform">
                            <i class="fa-solid <?= $n['icon'] ?> text-sm"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-bold text-slate-800 dark:text-white mb-0.5 truncate"><?= $n['title'] ?></p>
                            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 dark:text-slate-500 leading-snug"><?= $n['desc'] ?></p>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 mt-1.5 shrink-0"></i>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-10 text-center text-slate-400 dark:text-slate-500">
                    <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3">
                        <i class="fa-regular fa-bell-slash text-2xl opacity-50"></i>
                    </div>
                    <p class="text-[11px] font-bold uppercase tracking-widest">Semua aman!</p>
                    <p class="text-[10px] mt-1 text-slate-400 dark:text-slate-500">Tidak ada notifikasi saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
