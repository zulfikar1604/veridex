<?php
session_start();
// Proteksi: Hanya Manager dan Procurement yang bisa akses arsip ini
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

require 'database.php';
$active = 'arsip_approval';

// Query mengambil data PO yang sudah disetujui
$query_arsip = mysqli_query($conn, "
    SELECT 
        p.id, 
        p.po_number, 
        v.nama_perusahaan, 
        p.nilai_kontrak, 
        p.approved_at, 
        u2.username as approved_by_name,
        u.username as created_by
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    JOIN users u ON p.procurement_id = u.id
    LEFT JOIN users u2 ON p.approved_by = u2.id
    WHERE p.status != 'Pending Manager' AND p.approved_at IS NOT NULL
    ORDER BY p.approved_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php
    $page_title = "Arsip Approval";
    $page_subtitle = "Riwayat Keputusan Validasi PO";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            
            <!-- Filter & Search Bar -->
            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400"></i>
                    </div>
                    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari ID Dokumen atau Vendor..." style="padding-left: 2.5rem;" class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors text-slate-800 dark:text-white">
                </div>
            </div>

            <!-- Table Section -->
            <?php if(mysqli_num_rows($query_arsip) > 0): ?>
            <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                        <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            <th class="p-6">ID Dokumen</th>
                            <th class="p-6">Vendor Partner</th>
                            <th class="p-6 text-right">Nilai Kontrak (Rp)</th>
                            <th class="p-6 text-center">Tanggal Approval</th>
                            <th class="p-6 text-center">Validator</th>
                            <th class="p-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="dataTable" class="divide-y divide-slate-50 dark:divide-slate-800">
                        <?php while($row = mysqli_fetch_assoc($query_arsip)): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition group">
                            <td class="p-6">
                                <span class="font-black text-slate-800 dark:text-white text-sm">#<?= htmlspecialchars($row['po_number']) ?></span>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800 dark:text-white uppercase text-sm"><?= htmlspecialchars($row['nama_perusahaan']) ?></div>
                            </td>
                            <td class="p-6 text-right">
                                <div class="font-black text-slate-800 dark:text-white">Rp <?= number_format($row['nilai_kontrak'], 0, ',', '.') ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <div class="text-sm font-bold text-slate-800 dark:text-white">
                                    <?= $row['approved_at'] ? date('d M Y', strtotime($row['approved_at'])) : '-' ?>
                                </div>
                                <div class="text-[10px] text-slate-400 font-bold"><i class="fa-regular fa-clock mr-1"></i> <?= $row['approved_at'] ? date('H:i', strtotime($row['approved_at'])) : '-' ?></div>
                            </td>
                            <td class="p-6 text-center">
                                <div class="inline-flex items-center justify-center text-[10px] font-black text-green-600 bg-green-50 border border-green-200 px-3 py-1 rounded-lg uppercase shadow-sm">
                                    <i class="fa-solid fa-circle-check mr-1.5"></i> <?= htmlspecialchars($row['approved_by_name'] ?? 'Manager') ?>
                                </div>
                            </td>
                            <td class="p-6 text-right">
                                <a href="print_po.php?id=<?= $row['id'] ?>" target="_blank" class="px-3 py-2 bg-white dark:bg-[#1e293b] border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-red-600 hover:border-red-200 rounded-lg text-[10px] font-black uppercase transition shadow-sm whitespace-nowrap inline-flex items-center justify-center gap-1" title="Cetak PO PDF">
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
                <h3 class="font-black text-slate-800 dark:text-white text-xl mb-2">Belum Ada Riwayat Approval</h3>
                <p class="text-slate-400 text-sm">Dokumen yang telah disetujui akan muncul di sini.</p>
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