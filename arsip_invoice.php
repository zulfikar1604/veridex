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
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            
            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400"></i>
                    </div>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari Invoice, Vendor, atau PO..." style="padding-left: 2.5rem;" class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors text-slate-800 dark:text-white">
                </div>
            </div>

            <?php if(mysqli_num_rows($query_invoice) > 0): ?>
            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                        <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            <th class="p-6">Invoice & Date</th>
                            <th class="p-6">Vendor / Supplier</th>
                            <th class="p-6 text-center">Ref. PO</th>
                            <th class="p-6 text-right">Total Amount (Rp)</th>
                            <th class="p-6 text-center">Payment Status</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="dataTable" class="divide-y divide-slate-50 dark:divide-slate-800">
                        <?php while($row = mysqli_fetch_assoc($query_invoice)): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors group">
                            <td class="p-6">
                                <div class="font-black text-slate-800 dark:text-white uppercase text-sm">#<?= htmlspecialchars($row['invoice_number']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold"><i class="fa-regular fa-calendar mr-1"></i> <?= date('d M Y', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($row['nama_perusahaan']) ?></div>
                                <span class="text-[9px] bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded shadow-sm uppercase font-black">Verified Vendor</span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="text-xs font-black text-slate-500 dark:text-slate-400 italic">#<?= htmlspecialchars($row['po_number']) ?></span>
                            </td>
                            <td class="p-6 text-right">
                                <div class="font-black text-slate-800 dark:text-white">Rp <?= number_format($row['total_tagihan'], 0, ',', '.') ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($row['status'] == 'Paid'): ?>
                                    <span class="px-3 py-1 bg-green-50 text-green-600 border border-green-200 rounded-lg shadow-sm text-[9px] font-black uppercase inline-block whitespace-nowrap">Settled / Paid</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-amber-50 text-amber-600 border border-amber-200 rounded-lg shadow-sm text-[9px] font-black uppercase inline-block whitespace-nowrap">Pending Payment</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-right">
                                <a href="print_invoice.php?id=<?= $row['id'] ?>" target="_blank" class="px-3 py-2 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-red-600 hover:border-red-200 rounded-lg text-[10px] font-black uppercase transition shadow-sm whitespace-nowrap inline-flex items-center justify-center gap-1" title="Cetak Invoice PDF">
                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
</div>
            </div>
            <?php else: ?>
            <div class="bg-white dark:bg-[#1e293b] p-16 rounded-[2.5rem] border shadow-sm text-center">
                <i class="fa-solid fa-folder-open text-7xl text-slate-200 dark:text-slate-700 mb-6 block"></i>
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Riwayat Tagihan</h3>
                <p class="text-slate-400 text-sm">Invoice yang telah diterbitkan oleh vendor akan muncul di sini.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#dataTable tr");
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }
    </script>
</body>
</html>