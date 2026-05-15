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

$barang = null;
$pesan_error = "";
$pesan_sukses = "";
$po_list = [];

// Pesan sukses dari proses_inbound.php
if (isset($_GET['success'])) {
    $pesan_sukses = "Barang <strong>" . htmlspecialchars($_GET['nama'] ?? '') . "</strong> berhasil ditambahkan <strong>+" . intval($_GET['jumlah'] ?? 0) . "</strong> unit. Stok sekarang: <strong>" . intval($_GET['stok_baru'] ?? 0) . "</strong>";
}

if (isset($_GET['error'])) {
    $err_map = ['invalid_input'=>'Input tidak valid','not_found'=>'Barang tidak ditemukan','db_error'=>'Terjadi kesalahan database'];
    $pesan_error = $err_map[$_GET['error']] ?? 'Terjadi kesalahan';
}

// Jika kamera berhasil mendeteksi barcode atau dikirim dari list
if (isset($_POST['sku_scan'])) {
    $scan = trim($_POST['sku_scan']);
    $scan = strtoupper($scan);
    $scan_safe = mysqli_real_escape_string($conn, $scan);
    
    $po_found = null;

    if (strpos($scan, 'PO-') === 0) {
        $query_po = mysqli_query($conn, "
            SELECT p.*, i.nama_barang, i.sku, i.stok_saat_ini, i.unit, i.stok_kritis, v.nama_perusahaan 
            FROM purchase_orders p
            JOIN inventory i ON p.inventory_id = i.id
            JOIN vendors v ON p.vendor_id = v.id
            WHERE p.po_number = '$scan_safe' AND p.status = 'Shipped'
        ");
        if (mysqli_num_rows($query_po) > 0) {
            $po_found = mysqli_fetch_assoc($query_po);
            $barang = [
               'id' => $po_found['inventory_id'],
               'sku' => $po_found['sku'],
               'nama_barang' => $po_found['nama_barang'],
               'stok_saat_ini' => $po_found['stok_saat_ini'],
               'unit' => $po_found['unit'],
               'status_label' => '', // akan dihitung di proses_inbound
            ];
        } else {
            $pesan_error = "PO '$scan' tidak ditemukan atau belum berstatus Shipped!";
        }
    } else {
        $query = mysqli_query($conn, "SELECT * FROM inventory WHERE sku = '$scan_safe'");
        if (mysqli_num_rows($query) > 0) {
            $barang = mysqli_fetch_assoc($query);
            $query_po = mysqli_query($conn, "
                SELECT p.*, v.nama_perusahaan 
                FROM purchase_orders p
                JOIN vendors v ON p.vendor_id = v.id
                WHERE p.inventory_id = {$barang['id']} AND p.status = 'Shipped'
                ORDER BY p.shipped_at ASC LIMIT 1
            ");
            if (mysqli_num_rows($query_po) > 0) {
                $po_found = mysqli_fetch_assoc($query_po);
            } else {
                $pesan_error = "Barang '{$barang['nama_barang']}' ditemukan, tapi tidak ada pengiriman (PO) berstatus Shipped menuju gudang saat ini.";
                $barang = null;
            }
        } else {
            $pesan_error = "Scanner membaca kode: <strong>'$scan'</strong>, tapi tidak dikenali sebagai PO atau SKU!";
        }
    }
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
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        #reader { border: none !important; border-radius: 1rem; overflow: hidden; }
        #reader button { background: #e2e8f0; padding: 8px 16px; border-radius: 8px; font-weight: bold; margin: 5px; cursor: pointer; }
        #reader a { display: none; }
    </style>
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

        <div class="flex-1 overflow-y-auto p-6 flex flex-col items-center">
            
            <div class="bg-white p-8 md:p-12 rounded-3xl border shadow-sm text-center w-full max-w-2xl">
                
                <?php if ($pesan_sukses): ?>
                    <div class="bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 font-bold mb-6 flex justify-between items-center">
                        <span><i class="fa-solid fa-circle-check mr-2"></i> <?= $pesan_sukses ?></span>
                        <a href="scan_barcode_inbound.php" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">Scan Lagi</a>
                    </div>
                <?php endif; ?>

                <?php if ($pesan_error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl border border-red-200 font-bold mb-6 flex justify-between items-center">
                        <span><i class="fa-solid fa-circle-xmark mr-2"></i> <?= $pesan_error ?></span>
                        <a href="scan_barcode_inbound.php" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Scan Ulang</a>
                    </div>
                <?php endif; ?>

                <?php if ($barang && $po_found): ?>
                    <div class="bg-green-50 border-2 border-green-500 p-8 rounded-2xl text-left relative overflow-hidden">
                        <i class="fa-solid fa-truck-ramp-box text-9xl absolute -right-6 -bottom-6 text-green-200 opacity-50"></i>
                        <h3 class="text-xs font-black text-green-600 uppercase tracking-widest mb-1">DATA PENGIRIMAN DITEMUKAN</h3>
                        <p class="text-3xl font-black text-slate-800 mb-2"><?= htmlspecialchars($barang['nama_barang']) ?></p>
                        <p class="text-sm font-bold text-slate-500 mb-6">Vendor: <?= htmlspecialchars($po_found['nama_perusahaan']) ?> | PO: <?= htmlspecialchars($po_found['po_number']) ?></p>
                        
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-white p-4 rounded-xl border border-green-100 shadow-sm text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jumlah Dikirim</p>
                                <p class="text-3xl font-black text-blue-600"><?= $po_found['jumlah_pesan'] ?> <span class="text-sm font-normal text-slate-400"><?= $barang['unit'] ?></span></p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-green-100 shadow-sm text-center">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Stok Saat Ini</p>
                                <p class="text-3xl font-black text-slate-700"><?= $barang['stok_saat_ini'] ?> <span class="text-sm font-normal text-slate-400"><?= $barang['unit'] ?></span></p>
                            </div>
                        </div>

                        <form action="proses_inbound.php" method="POST">
                            <input type="hidden" name="sku_inbound" value="<?= $barang['sku'] ?>">
                            <input type="hidden" name="po_id" value="<?= $po_found['id'] ?>">
                            <input type="hidden" name="jumlah_masuk" value="<?= $po_found['jumlah_pesan'] ?>">
                            
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-black uppercase tracking-widest py-4 rounded-xl transition shadow-lg shadow-green-500/30 flex justify-center items-center gap-2 text-lg">
                                <i class="fa-solid fa-check-circle"></i> Konfirmasi Barang Masuk
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <?php if(!$pesan_sukses): ?>
                    <i class="fa-solid fa-qrcode text-6xl text-slate-200 mb-4"></i>
                    <h3 class="text-2xl font-black mb-2 text-slate-800 uppercase tracking-widest">SCAN BARCODE INBOUND</h3>
                    <p class="text-slate-500 mb-6 text-sm">Gunakan kamera untuk memindai Barcode PO (pada Surat Jalan) atau SKU Barang.</p>
                    
                    <div id="reader" class="mx-auto w-full max-w-sm bg-slate-100 rounded-2xl min-h-[250px] flex items-center justify-center border-2 border-dashed border-slate-300"></div>

                    <form id="scan_form" method="POST" class="hidden">
                        <input type="text" name="sku_scan" id="sku_input">
                    </form>
                    <?php endif; ?>
                <?php endif; ?>

            </div>

            <!-- Daftar Menunggu Inbound -->
            <?php if (!$barang): ?>
            <div class="w-full max-w-2xl mt-8">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4"><i class="fa-solid fa-truck-fast text-red-500 mr-2"></i> Daftar Menunggu Inbound</h3>
                
                <?php if(mysqli_num_rows($query_pending) > 0): ?>
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="p-4">PO / Barang</th>
                                <th class="p-4 text-center">Jumlah</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($pending = mysqli_fetch_assoc($query_pending)): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($pending['nama_barang']) ?></div>
                                    <div class="text-[10px] text-slate-500 font-bold uppercase"><?= htmlspecialchars($pending['po_number']) ?></div>
                                </td>
                                <td class="p-4 text-center font-black text-blue-600">
                                    <?= $pending['jumlah_pesan'] ?>
                                </td>
                                <td class="p-4 text-right">
                                    <form method="POST">
                                        <input type="hidden" name="sku_scan" value="<?= $pending['po_number'] ?>">
                                        <button type="submit" class="bg-slate-100 hover:bg-red-500 hover:text-white text-slate-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase transition">Proses</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-100 text-center shadow-sm">
                    <p class="text-slate-400 font-bold text-sm">Tidak ada barang yang sedang dalam perjalanan.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            </div>
        </div>
    </main>

    <script>
        if (document.getElementById('reader')) {
            function onScanSuccess(decodedText, decodedResult) {
                let audio = new Audio('https://www.soundjay.com/buttons/sounds/beep-07a.mp3');
                audio.play().catch(e => console.log("Audio play blocked by browser"));
                html5QrcodeScanner.clear();
                document.getElementById('sku_input').value = decodedText;
                document.getElementById('scan_form').submit();
            }

            function onScanFailure(error) {}

            let html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: {width: 250, height: 100} },
                false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    </script>
</body>
</html>