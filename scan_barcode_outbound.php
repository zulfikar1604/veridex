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
$pesan_sukses = isset($_SESSION['sukses']) ? $_SESSION['sukses'] : "";
unset($_SESSION['sukses']); // Hapus session sukses setelah ditampilkan

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
        // Cek jika stok kosong
        if($barang['stok_saat_ini'] <= 0) {
             $pesan_error = "Stok barang <strong>'".$barang['nama_barang']."'</strong> saat ini KOSONG (0)! Tidak bisa melakukan Outbound.";
             $barang = null; // Batalkan proses outbound
        }
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
    <title>Outbound | Veridex</title>
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
    $page_title = "Barcode Outbound";
    $page_subtitle = "Pengeluaran Barang Cepat via Barcode Scanner";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active_menu = 'outbound'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 flex items-center justify-center">
            <div class="bg-white p-8 md:p-12 rounded-3xl border shadow-sm text-center w-full max-w-2xl">
                
                <?php if ($pesan_sukses): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-xl border border-green-200 font-bold mb-6 flex justify-between items-center flex-wrap gap-4">
                        <span><i class="fa-solid fa-check-circle mr-2"></i> <?= $pesan_sukses ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($pesan_error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl border border-red-200 font-bold mb-6 flex justify-between items-center flex-wrap gap-4">
                        <span><i class="fa-solid fa-circle-xmark mr-2"></i> <?= $pesan_error ?></span>
                        <a href="scan_barcode_outbound.php" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Scan Ulang</a>
                    </div>
                <?php endif; ?>

                <?php if ($barang): ?>
                    <div class="bg-rose-50 border-2 border-rose-500 p-8 rounded-2xl text-left relative overflow-hidden">
                        <i class="fa-solid fa-box-open text-9xl absolute -right-6 -bottom-6 text-rose-200 opacity-50"></i>
                        <h3 class="text-xs font-black text-rose-600 uppercase tracking-widest mb-1">PROSES OUTBOUND</h3>
                        <p class="text-3xl font-black text-slate-800 mb-4"><?= $barang['nama_barang'] ?></p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white p-4 rounded-xl border border-rose-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kode SKU</p>
                                <p class="text-lg font-black text-slate-700"><?= $barang['sku'] ?></p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-rose-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Stok Saat Ini</p>
                                <p class="text-lg font-black text-slate-700"><?= $barang['stok_saat_ini'] ?> <span class="text-sm font-normal text-slate-400"><?= $barang['unit'] ?></span></p>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-rose-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Batas ROP</p>
                                <p class="text-lg font-black text-rose-600"><?= $barang['stok_kritis'] ?> <span class="text-sm font-normal text-slate-400"><?= $barang['unit'] ?></span></p>
                            </div>
                        </div>

                        <form action="proses_outbound.php" method="POST" class="mt-6">
                            <input type="hidden" name="sku_outbound" value="<?= $barang['sku'] ?>">
                            
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Jumlah Barang Dikeluarkan:</label>
                                <input type="number" name="jumlah_keluar" required min="1" max="<?= $barang['stok_saat_ini'] ?>" placeholder="Maksimal: <?= $barang['stok_saat_ini'] ?>" class="w-full border border-slate-300 focus:outline-rose-500 p-4 rounded-xl font-bold text-xl">
                            </div>

                            <div class="mb-6">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Keterangan / Tujuan:</label>
                                <input type="text" name="keterangan" required placeholder="Contoh: Dipakai divisi Produksi" class="w-full border border-slate-300 focus:outline-rose-500 p-4 rounded-xl font-medium">
                            </div>

                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-black uppercase tracking-widest px-8 py-4 rounded-xl transition shadow-lg shadow-rose-500/30">
                                Kurangi Stok
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <i class="fa-solid fa-boxes-stacked text-6xl text-slate-200 mb-4"></i>
                    <h3 class="text-2xl font-black mb-2 text-slate-800 uppercase tracking-widest">OUTBOUND BARANG</h3>
                    <p class="text-slate-500 mb-8 text-sm">Pilih metode pemilihan barang yang akan dikeluarkan.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch max-w-4xl mx-auto w-full text-left">
                        
                        <!-- Mode Manual -->
                        <div class="border-2 border-slate-200 rounded-3xl p-8 bg-white relative flex flex-col justify-center shadow-sm hover:border-red-300 transition">
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-2"><i class="fa-solid fa-keyboard text-red-500 mr-2"></i> Mode Manual</h4>
                            <p class="text-xs text-slate-500 mb-6 font-medium">Ketik atau copy-paste kode SKU jika barcode fisik rusak atau sulit dibaca.</p>
                            
                            <form action="scan_barcode_outbound.php" method="POST" class="flex flex-col gap-4 mt-auto">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block">Kode SKU / Barcode</label>
                                    <input type="text" name="sku_scan" placeholder="Contoh: SKU-001" class="w-full border border-slate-300 p-4 rounded-xl outline-none focus:border-red-500 bg-slate-50 font-black text-center text-xl uppercase tracking-widest" required>
                                </div>
                                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-xl font-black uppercase tracking-widest text-sm hover:bg-red-600 transition shadow-lg shadow-slate-900/20 flex justify-center items-center gap-2">
                                    Cari Data Barang
                                </button>
                            </form>
                        </div>

                        <!-- Mode Scanner -->
                        <div class="border-2 border-dashed border-slate-300 rounded-3xl p-8 bg-slate-50 relative flex flex-col items-center justify-center text-center">
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-2"><i class="fa-solid fa-camera text-red-500 mr-2"></i> Mode Kamera</h4>
                            <p class="text-xs text-slate-500 mb-6 font-medium">Arahkan barcode fisik barang ke kamera perangkat Anda.</p>
                            
                            <div id="reader" class="mx-auto w-full max-w-[280px] bg-white rounded-2xl min-h-[250px] flex items-center justify-center overflow-hidden border border-slate-200 shadow-inner"></div>

                            <form id="scan_form" method="POST" class="hidden">
                                <input type="text" name="sku_scan" id="sku_input">
                            </form>
                        </div>

                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

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
                // Abaikan error saat proses mencari
            }

            // Setting Kamera
            let html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: {width: 250, height: 100} },
                /* verbose= */ false
            );
            
            // Mulai nyalakan kamera
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
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
