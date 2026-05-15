<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

require 'database.php';

date_default_timezone_set('Asia/Jakarta');
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$bulan_array = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = $hari_array[date('w')] . ', ' . date('j') . ' ' . $bulan_array[date('n')] . ' ' . date('Y');

$pesan_sukses = "";
if (isset($_GET['success'])) {
    $pesan_sukses = "Barang <strong>" . htmlspecialchars($_GET['nama'] ?? '') . "</strong> berhasil ditambahkan <strong>+" . intval($_GET['jumlah'] ?? 0) . "</strong> unit. Stok sekarang: <strong>" . intval($_GET['stok_baru'] ?? 0) . "</strong>";
}

// Ambil daftar PO yang sedang shipped (Menunggu Inbound)
$query_pending = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'Shipped'
    ORDER BY p.shipped_at ASC
");

// Bentuk base URL dinamis untuk QR Code
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];

// Jika akses dari komputer server sendiri (localhost), ubah menjadi IP LAN agar bisa diakses device lain (HP)
if ($domainName === 'localhost' || $domainName === '127.0.0.1') {
    $domainName = getHostByName(getHostName());
}

$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url = $protocol . $domainName . $path . '/konfirmasi_inbound.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbound | Veridex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- QR Code Generator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="bg-slate-50 h-screen flex overflow-hidden">
    
    <?php 
        $active_menu = 'inbound'; 
        include 'sidebar_gudang.php'; 
    ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-[0.1em] uppercase">BARANG MASUK (INBOUND)</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-slate-400"><?= $hari_ini ?></span>
                    <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-md">
                        <?= htmlspecialchars(strtoupper(substr($_SESSION['role'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            
            <?php if ($pesan_sukses): ?>
                <div class="bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold mb-6 flex justify-between items-center max-w-4xl mx-auto">
                    <span><i class="fa-solid fa-circle-check mr-2"></i> <?= $pesan_sukses ?></span>
                    <button onclick="window.location='scan_barcode_inbound.php'" class="text-green-800 hover:text-green-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endif; ?>

            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest">
                        <i class="fa-solid fa-truck-fast text-red-500 mr-2"></i> Daftar Pengiriman Tiba
                    </h3>
                    <form action="konfirmasi_inbound.php" method="GET" class="flex gap-2">
                        <input type="text" name="po" placeholder="Input No PO Manual..." class="border border-slate-200 rounded-lg px-3 py-1 text-xs focus:outline-red-500" required>
                        <button type="submit" class="bg-slate-800 text-white px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-slate-700 transition">Cari</button>
                    </form>
                </div>
                
                <?php if(mysqli_num_rows($query_pending) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while($pending = mysqli_fetch_assoc($query_pending)): 
                        $qr_url = $base_url . "?po=" . urlencode($pending['po_number']);
                    ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col items-center text-center relative overflow-hidden group hover:border-red-200 hover:shadow-md transition">
                        
                        <div class="absolute top-0 right-0 bg-red-50 text-red-500 font-black text-[9px] uppercase tracking-widest px-3 py-1 rounded-bl-xl border-b border-l border-red-100">
                            Konfirmasi Inbound
                        </div>

                        <div class="mb-4 mt-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nomor PO</p>
                            <h4 class="font-black text-lg text-slate-800"><?= htmlspecialchars($pending['po_number']) ?></h4>
                        </div>
                        
                        <!-- Tempat Render QR Code -->
                        <div id="qrcode-<?= $pending['id'] ?>" class="bg-white p-2 border border-slate-200 rounded-xl mb-4" data-url="<?= htmlspecialchars($qr_url) ?>"></div>
                        
                        <div class="bg-slate-50 w-full p-4 rounded-2xl border border-slate-100 mt-auto">
                            <p class="font-black text-slate-800 uppercase text-sm mb-1"><?= htmlspecialchars($pending['nama_barang']) ?></p>
                            <div class="flex justify-between items-center text-xs mt-3">
                                <span class="font-bold text-slate-500"><i class="fa-solid fa-building mr-1"></i> <?= htmlspecialchars($pending['nama_perusahaan']) ?></span>
                                <span class="font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-lg"><?= $pending['jumlah_pesan'] ?> Unit</span>
                            </div>
                        </div>

                        <!-- Tombol Manual -->
                        <a href="konfirmasi_inbound.php?po=<?= urlencode($pending['po_number']) ?>" class="mt-4 text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-red-500 transition underline underline-offset-4">Proses di Layar Ini</a>

                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="bg-white p-12 rounded-3xl border border-slate-100 text-center shadow-sm">
                    <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-box-open text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-black text-slate-800 uppercase tracking-tight mb-2">Gudang Kosong</h4>
                    <p class="text-slate-400 font-bold text-sm">Tidak ada pengiriman barang yang sedang menunggu inbound saat ini.</p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script>
        // Generate QR Codes dynamically
        document.querySelectorAll('[id^="qrcode-"]').forEach(function(el) {
            new QRCode(el, {
                text: el.getAttribute('data-url'),
                width: 150,
                height: 150,
                colorDark : "#0f172a", // slate-900
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        });
    </script>
</body>
</html>