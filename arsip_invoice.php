<?php
session_start();
require 'database.php';

// Proteksi Internal: Procurement & Finance/Manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'invoice';

// Query mengambil data invoice yang sudah masuk
$query_invoice = mysqli_query($conn, "
    SELECT i.*, v.nama_perusahaan, p.po_number 
    FROM invoices i
    JOIN vendors v ON i.vendor_id = v.id
    JOIN purchase_orders p ON i.po_id = p.id
    ORDER BY i.created_at DESC
") or handle_db_error($conn);
?>
<?php
$page_title = "Arsip Invoice";
$page_subtitle = "Riwayat Tagihan & Pembayaran";
include 'header_meta.php';
?>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-900 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                        <tr>
                            <th class="p-6">Invoice & Date</th>
                            <th class="p-6">Vendor / Supplier</th>
                            <th class="p-6 text-center">Ref. PO</th>
                            <th class="p-6 text-right">Total Amount</th>
                            <th class="p-6 text-center">Payment Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = mysqli_fetch_assoc($query_invoice)): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-6">
                                <div class="font-black text-slate-800 uppercase text-sm">#<?= $row['invoice_number'] ?></div>
                                <div class="text-[10px] text-slate-400 font-bold"><?= date('d/m/Y', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-700 text-sm"><?= $row['nama_perusahaan'] ?></div>
                                <span class="text-[9px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase font-black">Verified Vendor</span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="text-xs font-black text-slate-500 italic"><?= $row['po_number'] ?></span>
                            </td>
                            <td class="p-6 text-right">
                                <div class="font-black text-slate-800">Rp <?= number_format($row['total_tagihan'], 0, ',', '.') ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($row['status'] == 'Paid'): ?>
                                    <span class="px-3 py-1 bg-green-50 text-green-600 border border-green-100 rounded-full text-[9px] font-black uppercase">Settled</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-orange-50 text-orange-600 border border-orange-100 rounded-full text-[9px] font-black uppercase">Awaiting Payment</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
</div>
            </div>
        </main>
    </div>


    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                sidebar.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('sidebar-open');
                if (overlay) overlay.classList.toggle('overlay-open');
            }
        }
    </script>

</body>
</html>