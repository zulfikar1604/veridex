<?php
session_start();
// Proteksi: Hanya Manager dan Procurement yang bisa akses arsip ini
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

require 'database.php'; // Menggunakan koneksi mysqli $conn
$active = 'arsip_approval';

// Query mengambil data PO yang sudah disetujui (contoh status 'Approved')
// Query yang sudah disesuaikan dengan nama kolom di database kamu
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
    WHERE p.status = 'Approved'
    ORDER BY p.approved_at DESC
") or handle_db_error($conn);
?>
<?php
$page_title = "Arsip Approval";
$page_subtitle = "Riwayat Keputusan Validasi PO";
include 'header_meta.php';
?>
<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_procurement.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex gap-4">
                <input type="text" placeholder="Cari Nomor PO atau Vendor..." class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-red-500">
                <button class="bg-slate-800 text-white px-6 py-2 rounded-xl text-sm font-bold">Filter Tanggal</button>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto w-full">
<table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            <th class="p-6">ID Dokumen</th>
                            <th class="p-6">Vendor Partner</th>
                            <th class="p-6">Nilai Kontrak</th>
                            <th class="p-6">Tanggal Approval</th>
                            <th class="p-6">Validator</th>
                            <th class="p-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = mysqli_fetch_assoc($query_arsip)): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-6">
                                <span class="font-black text-slate-700">#<?= htmlspecialchars($row['po_number']) ?></span>
                            </td>
                            <td class="p-6">
                                <div class="font-bold text-slate-800"><?= $row['nama_perusahaan'] ?></div>
                            </td>
                            <td class="p-6">
                                <div class="text-sm font-bold text-slate-600">Rp <?= number_format($row['nilai_kontrak'], 0, ',', '.') ?></div>
                            </td>
                            <td class="p-6 text-sm text-slate-500">
                                <?= $row['approved_at'] ? date('d M Y', strtotime($row['approved_at'])) : '-' ?>
                            </td>
                            <td class="p-6">
                                <div class="flex items-center text-xs font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full w-fit">
                                    <i class="fa-solid fa-circle-check mr-2"></i> <?= htmlspecialchars($row['approved_by_name'] ?? 'Manager') ?>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <a href="print_po.php?id=<?= $row['id'] ?>" target="_blank" class="text-slate-400 hover:text-red-500 transition shadow-sm" title="Print / PDF PO">
                                    <i class="fa-solid fa-file-pdf text-xl"></i>
                                </a>
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