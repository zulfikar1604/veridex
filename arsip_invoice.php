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
    ORDER BY i.tanggal_invoice DESC
") or handle_db_error($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice Archive | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">Invoice Archive</h2>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-1 italic">Financial Procurement Settlement</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
                    <i class="fa-solid fa-file-invoice-dollar text-red-600 text-2xl"></i>
                    <div class="text-right">
                        <span class="text-[10px] font-black text-slate-400 uppercase block">Total Records</span>
                        <span class="text-xl font-black text-slate-800"><?= mysqli_num_rows($query_invoice) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Arsip -->
        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
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
                                <div class="font-black text-slate-800 uppercase text-sm">#<?= $row['nomor_invoice'] ?></div>
                                <div class="text-[10px] text-slate-400 font-bold"><?= date('d/m/Y', strtotime($row['tanggal_invoice'])) ?></div>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-700 text-sm"><?= $row['nama_perusahaan'] ?></div>
                                <span class="text-[9px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase font-black">Verified Vendor</span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="text-xs font-black text-slate-500 italic"><?= $row['nomor_po'] ?></span>
                            </td>
                            <td class="p-6 text-right">
                                <div class="font-black text-slate-800">Rp <?= number_format($row['total_tagihan'], 0, ',', '.') ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <?php if($row['status_pembayaran'] == 'Paid'): ?>
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

</body>
</html>
