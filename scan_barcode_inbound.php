<?php
session_start();
// Proteksi role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

require 'database.php'; // Panggil database

date_default_timezone_set('Asia/Jakarta');
$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$bulan_array = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = $hari_array[date('w')] . ', ' . date('j') . ' ' . $bulan_array[date('n')] . ' ' . date('Y');

// Variabel penampung
$barang = null;
$pesan_error = "";

// Jika kamera berhasil mendeteksi barcode dan mengirim data SKU
// Variabel penampung
$barang = null;
$pesan_error = "";

// Jika kamera berhasil mendeteksi barcode dan mengirim data SKU
if (isset($_POST['sku_scan'])) {
    
    // PERBAIKAN JOS JIS: 
    // 1. trim() untuk membuang spasi nyempil di awal/akhir
    // 2. strtoupper() untuk memastikan huruf besar semua (SKU-001)
    $sku_mentah = trim($_POST['sku_scan']);
    $sku_mentah = strtoupper($sku_mentah);
    
    // Amankan dari SQL Injection
    $sku_input = mysqli_real_escape_string($conn, $sku_mentah);
    
    // Cari barang di database
    $query = mysqli_query($conn, "SELECT * FROM inventory WHERE sku = '$sku_input'");
    
    if (mysqli_num_rows($query) > 0) {
        $barang = mysqli_fetch_assoc($query);
    } else {
        // Ubah pesan error agar kita tahu persis apa yang dibaca kamera
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
        /* Merapikan tampilan default dari library kamera */
        #reader { border: none !important; border-radius: 1rem; overflow: hidden; }
        #reader button { background: #e2e8f0; padding: 8px 16px; border-radius: 8px; font-weight: bold; margin: 5px; cursor: pointer; }
        #reader a { display: none; } /* Sembunyikan link credit library */
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
                        <?= strtoupper(substr($_SESSION['role'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 flex items-center justify-center">
            
            <div class="bg-white p-8 md:p-12 rounded-3xl border shadow-sm text-center w-full max-w-2xl">
                
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
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status Gudang</p>
                                <p class="text-lg font-black text-slate-700"><?= $barang['status_label'] ?></p>
                            </div>
                        </div>

                        <form action="proses_inbound.php" method="POST" class="mt-8">
                            <input type="hidden" name="sku_inbound" value="<?= $barang['sku'] ?>">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Masukkan Jumlah Barang Masuk:</label>
                            <div class="flex gap-4">
                                <input type="number" name="jumlah_masuk" required min="1" placeholder="Misal: 50" class="flex-1 border border-slate-300 focus:outline-red-500 p-4 rounded-xl font-bold text-xl">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-black uppercase tracking-widest px-8 py-4 rounded-xl transition shadow-lg shadow-green-500/30">
                                    Simpan Stok
                                </button>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <i class="fa-solid fa-qrcode text-6xl text-slate-200 mb-4"></i>
                    <h3 class="text-2xl font-black mb-2 text-slate-800 uppercase tracking-widest">SCAN BARCODE INBOUND</h3>
                    <p class="text-slate-500 mb-6 text-sm">Gunakan kamera perangkat ini untuk memindai SKU barang.</p>
                    
                    <div id="reader" class="mx-auto w-full max-w-sm bg-slate-100 rounded-2xl min-h-[250px] flex items-center justify-center border-2 border-dashed border-slate-300"></div>

                    <form id="scan_form" method="POST" class="hidden">
                        <input type="text" name="sku_scan" id="sku_input">
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <script>
        // Hanya jalankan kamera jika form pencarian barang (scanner box) sedang tampil
        if (document.getElementById('reader')) {
            
            // Fungsi ketika kamera berhasil membaca Barcode
            function onScanSuccess(decodedText, decodedResult) {
                // 1. Bunyikan suara 'beep' (opsional)
                let audio = new Audio('https://www.soundjay.com/buttons/sounds/beep-07a.mp3');
                audio.play().catch(e => console.log("Audio play blocked by browser"));

                // 2. Hentikan kamera agar tidak scan berkali-kali
                html5QrcodeScanner.clear();
                
                // 3. Masukkan teks barcode ke form tersembunyi
                document.getElementById('sku_input').value = decodedText;
                
                // 4. Tekan tombol submit secara otomatis
                document.getElementById('scan_form').submit();
            }

            function onScanFailure(error) {
                // Abaikan error saat proses mencari (wajar karena kamera terus mencari)
            }

            // Setting Kamera
            let html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: {width: 250, height: 100} }, // Kotak scan dibuat persegi panjang khas barcode
                /* verbose= */ false
            );
            
            // Mulai nyalakan kamera
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    </script>
</body>
</html>