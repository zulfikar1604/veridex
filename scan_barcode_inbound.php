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

// Jika kamera berhasil mendeteksi barcode dan mengirim data SKU
if (isset($_POST['sku_scan'])) {
    $sku_mentah = trim($_POST['sku_scan']);
    $sku_mentah = strtoupper($sku_mentah);
    $sku_input = mysqli_real_escape_string($conn, $sku_mentah);
    
    $query = mysqli_query($conn, "SELECT * FROM inventory WHERE sku = '$sku_input'");
    
    if (mysqli_num_rows($query) > 0) {
        $barang = mysqli_fetch_assoc($query);
        
        // Cari PO yang statusnya 'Shipped' untuk barang ini
        $query_po = mysqli_query($conn, "
            SELECT p.*, v.nama_perusahaan 
            FROM purchase_orders p
            JOIN vendors v ON p.vendor_id = v.id
            WHERE p.inventory_id = {$barang['id']} AND p.status = 'Shipped'
            ORDER BY p.shipped_at DESC
        ");
        while ($po = mysqli_fetch_assoc($query_po)) {
            $po_list[] = $po;
        }
    } else {
        $pesan_error = "Scanner membaca kode: <strong>'$sku_input'</strong>, tapi tidak ada di database!";
    }
}
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

        <div class="flex-1 overflow-y-auto p-6 flex items-center justify-center">
            
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

                <?php if ($barang): ?>
                    <div class="bg-green-50 border-2 border-green-500 p-8 rounded-2xl text-left relative overflow-hidden">
                        <i class="fa-solid fa-circle-check text-9xl absolute -right-6 -bottom-6 text-green-200 opacity-50"></i>
                        <h3 class="text-xs font-black text-green-600 uppercase tracking-widest mb-1">BARANG DITEMUKAN</h3>
                        <p class="text-3xl font-black text-slate-800 mb-4"><?= $barang['nama_barang'] ?></p>
                        
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-white p-4 rounded-xl border border-green-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kode SKU</p>
                                <p class="text-lg font-black text-slate-700"><?= $barang['sku'] ?></p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-green-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Stok Saat Ini</p>
                                <p class="text-lg font-black text-slate-700"><?= $barang['stok_saat_ini'] ?> <span class="text-sm font-normal text-slate-400"><?= $barang['unit'] ?></span></p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-green-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</p>
                                <p class="text-lg font-black text-slate-700"><?= $barang['status_label'] ?></p>
                            </div>
                        </div>

                        <!-- PO Terkait -->
                        <?php if (!empty($po_list)): ?>
                        <div class="mt-6 bg-blue-50 border border-blue-200 p-4 rounded-xl">
                            <h4 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-3"><i class="fa-solid fa-truck mr-1"></i> PO Dalam Pengiriman</h4>
                            <?php foreach($po_list as $po): ?>
                            <div class="bg-white p-3 rounded-lg border border-blue-100 mb-2 flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($po['po_number']) ?></span>
                                    <span class="text-[10px] text-slate-400 ml-2"><?= htmlspecialchars($po['nama_perusahaan']) ?></span>
                                    <?php if($po['no_resi']): ?>
                                    <span class="text-[10px] text-blue-500 ml-2">Resi: <?= htmlspecialchars($po['no_resi']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs font-bold text-purple-600">Shipped</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <form action="proses_inbound.php" method="POST" class="mt-8">
                            <input type="hidden" name="sku_inbound" value="<?= $barang['sku'] ?>">
                            
                            <?php if (!empty($po_list)): ?>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Pilih PO Terkait (Opsional):</label>
                            <select name="po_id" class="w-full border border-slate-300 p-3 rounded-xl font-bold mb-4 outline-none focus:border-green-500">
                                <option value="0">-- Tanpa PO (Inbound Manual) --</option>
                                <?php foreach($po_list as $po): ?>
                                <option value="<?= $po['id'] ?>"><?= htmlspecialchars($po['po_number']) ?> — <?= htmlspecialchars($po['nama_perusahaan']) ?> (Rp <?= number_format($po['total_harga'],0,',','.') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            <input type="hidden" name="po_id" value="0">
                            <?php endif; ?>

                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Masukkan Jumlah Barang Masuk:</label>
                            <div class="flex gap-4">
                                <input type="number" name="jumlah_masuk" required min="1" placeholder="Misal: 50" class="flex-1 border border-slate-300 focus:outline-green-500 p-4 rounded-xl font-bold text-xl">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-black uppercase tracking-widest px-8 py-4 rounded-xl transition shadow-lg shadow-green-500/30">
                                    Simpan Stok
                                </button>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <?php if(!$pesan_sukses): ?>
                    <i class="fa-solid fa-qrcode text-6xl text-slate-200 mb-4"></i>
                    <h3 class="text-2xl font-black mb-2 text-slate-800 uppercase tracking-widest">SCAN BARCODE INBOUND</h3>
                    <p class="text-slate-500 mb-6 text-sm">Gunakan kamera perangkat ini untuk memindai SKU barang.</p>
                    
                    <div id="reader" class="mx-auto w-full max-w-sm bg-slate-100 rounded-2xl min-h-[250px] flex items-center justify-center border-2 border-dashed border-slate-300"></div>

                    <form id="scan_form" method="POST" class="hidden">
                        <input type="text" name="sku_scan" id="sku_input">
                    </form>
                    <?php endif; ?>
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