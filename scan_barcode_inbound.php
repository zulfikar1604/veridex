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
    <style> body { font-family: 'Inter', sans-serif; } 
    <style>
        #main-sidebar { transition: width 0.3s ease, min-width 0.3s ease, transform 0.3s ease; }
        @media (min-width: 768px) {
            #main-sidebar { position: relative !important; transform: translateX(0) !important; width: 280px; min-width: 280px; overflow: hidden; }
            #main-sidebar.sidebar-collapsed { width: 0 !important; min-width: 0 !important; }
            #sidebar-overlay { display: none !important; }
        }
        @media (max-width: 767px) {
            #main-sidebar { position: fixed !important; top: 0; left: 0; bottom: 0; z-index: 50; width: 280px !important; min-width: 280px !important; transform: translateX(-100%); }
            #main-sidebar.sidebar-open { transform: translateX(0); }
            #sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 40; }
            #sidebar-overlay.overlay-open { display: block; }
        }
        .sidebar-toggle-btn { display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;cursor:pointer;transition:background 0.2s,color 0.2s,transform 0.1s;flex-shrink:0; }
        .sidebar-toggle-btn:hover { background:#ef4444;color:#fff;border-color:#ef4444; }
        .sidebar-toggle-btn:active { transform:scale(0.92); }
        .sidebar-close-btn { display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.08);color:#94a3b8;cursor:pointer;transition:background 0.2s,color 0.2s;flex-shrink:0;border:none; }
        .sidebar-close-btn:hover { background:rgba(239,68,68,0.2);color:#fca5a5; }
    </style>
</head>

<body class="bg-slate-50 flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Barcode Inbound";
    $page_subtitle = "Penerimaan Barang Cepat via Barcode Scanner";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active_menu = 'inbound'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8">
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 md:grid-cols-3 gap-6">
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
        </main>
    </div>

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
