<?php
session_start();

// 1. Proteksi Halaman: Hanya Manager yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager Procurement') {
    header("Location: login.php");
    exit;
}

require 'database.php'; // Pastikan koneksi mysqli $conn sudah benar
$active = 'kinerja_vendor';

// 2. Query Dashboard: Hitung Fulfillment Rate Vendor secara Otomatis[cite: 1]
$query_kinerja = mysqli_query($conn, "
    SELECT 
        v.id, 
        v.nama_perusahaan, 
        v.spesialisasi, 
        v.status as status_vendor,
        COUNT(p.id) as total_po,
        SUM(p.jumlah_pesan) as total_pesan,
        SUM(p.jumlah_diterima) as total_diterima
    FROM vendors v
    LEFT JOIN purchase_orders p ON v.id = p.vendor_id
    GROUP BY v.id
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kinerja Vendor | Veridex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <!-- Panggil Sidebar yang sudah kita batasi rolenya tadi[cite: 1] -->
    <?php include 'sidebar_procurement.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="p-8">
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase">Vendor Performance</h2>
                    <p class="text-sm text-slate-400 font-bold tracking-widest uppercase mt-1">Managerial Strategic Dashboard</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs font-black text-slate-400 uppercase">Logged in as:</p>
                        <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-red-500 text-white flex items-center justify-center shadow-lg shadow-red-500/20">
                        <i class="fa-solid fa-user-tie text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-black text-slate-700 uppercase tracking-widest">Peringkat Pemenuhan (Fulfillment Rate)</h3>
                </div>
                
                <div class="p-6">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">
                                <th class="pb-4 px-4">Vendor Nama</th>
                                <th class="pb-4 px-4 text-center">Total PO</th>
                                <th class="pb-4 px-4 w-1/3">Tingkat Pemenuhan</th>
                                <th class="pb-4 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php while($row = mysqli_fetch_assoc($query_kinerja)): 
                                $pesan = $row['total_pesan'] ?? 0;
                                $terima = $row['total_diterima'] ?? 0;
                                $persen = ($pesan > 0) ? round(($terima / $pesan) * 100) : 0;
                                
                                // Penentuan warna progress bar[cite: 1]
                                $color = ($persen >= 90) ? 'bg-green-500' : (($persen >= 70) ? 'bg-yellow-500' : 'bg-red-500');
                            ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                <td class="py-5 px-4">
                                    <div class="font-black text-slate-800"><?= $row['nama_perusahaan'] ?></div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase"><?= $row['spesialisasi'] ?></div>
                                </td>
                                <td class="py-5 px-4 text-center font-bold text-slate-600">
                                    <?= $row['total_po'] ?> <span class="text-[10px] text-slate-400">PO</span>
                                </td>
                                <td class="py-5 px-4">
                                    <div class="flex justify-between mb-1 items-end">
                                        <span class="text-xs font-black text-slate-700"><?= $persen ?>%</span>
                                        <span class="text-[9px] font-bold text-slate-400"><?= number_format($terima) ?> / <?= number_format($pesan) ?> Unit</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="<?= $color ?> h-full rounded-full transition-all duration-1000" style="width: <?= $persen ?>%"></div>
                                    </div>
                                </td>
                                <td class="py-5 px-4 text-center">
                                    <span class="px-3 py-1 rounded-lg border text-[10px] font-black uppercase <?= $row['status_vendor'] == 'Aktif' ? 'text-green-600 border-green-200 bg-green-50' : 'text-slate-400 border-slate-200 bg-slate-50' ?>">
                                        <?= $row['status_vendor'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
