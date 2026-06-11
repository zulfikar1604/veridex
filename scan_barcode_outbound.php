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
$pesan_error = isset($_SESSION['error']) ? $_SESSION['error'] : "";
unset($_SESSION['error']);
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
<?php
$page_title = "Barcode Outbound";
$page_subtitle = "Pengeluaran Barang Cepat via Barcode Scanner";
include 'header_meta.php';
?>
<!-- HTML5 QR Code Library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<style>
    /* Merapikan tampilan default dari library kamera */
    #reader { border: none !important; border-radius: 1rem; overflow: hidden; width: 100%; }
    #reader button { background: #e2e8f0; color: #1e293b; padding: 12px 24px; border-radius: 8px; font-weight: bold; margin: 10px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.1em; transition: 0.3s; }
    #reader button:hover { background: #cbd5e1; }
    #reader select { padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 15px; width: 80%; max-width: 300px; }
    #reader a { display: none; } /* Sembunyikan link credit library */
</style>

<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <!-- Modal Kamera Scanner -->
    <div id="cameraModal" class="fixed inset-0 z-[100] hidden bg-black/90 flex flex-col items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#1e293b] w-full max-w-2xl rounded-3xl overflow-hidden flex flex-col relative shadow-2xl">
            <div class="p-5 bg-slate-100 dark:bg-slate-800 flex justify-between items-center border-b border-slate-200 dark:border-slate-700">
                <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-lg"><i class="fa-solid fa-camera mr-2 text-rose-500"></i> Kamera Scanner Outbound</h3>
                <button type="button" onclick="closeCameraModal()" class="w-10 h-10 bg-rose-100 text-rose-600 rounded-full hover:bg-rose-200 hover:text-rose-700 flex items-center justify-center font-bold transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="p-6 bg-slate-50 dark:bg-slate-900 flex-1 flex flex-col items-center justify-center min-h-[60vh] max-h-[75vh]">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mb-4 text-center">Arahkan barcode / SKU barang ke area kamera</p>
                <div id="reader" class="w-full mx-auto rounded-2xl overflow-hidden shadow-inner border-2 border-dashed border-slate-300 dark:border-slate-600 bg-black flex-1 flex items-center justify-center"></div>
            </div>
        </div>
    </div>

    <?php
    $page_title = "Barcode Outbound";
    $page_subtitle = "Pengeluaran Barang Cepat via Barcode Scanner";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active = 'outbound'; 
         include 'sidebar_gudang.php'; 
    ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 flex items-center justify-center">
            <div class="bg-white dark:bg-[#1e293b] p-8 md:p-12 rounded-3xl border shadow-sm text-center w-full max-w-2xl">
                
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
                        <p class="text-3xl font-black text-slate-800 dark:text-white mb-4"><?= $barang['nama_barang'] ?></p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-xl border border-rose-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Kode SKU</p>
                                <p class="text-lg font-black text-slate-700 dark:text-slate-200"><?= $barang['sku'] ?></p>
                            </div>
                            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-xl border border-rose-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Stok Saat Ini</p>
                                <p class="text-lg font-black text-slate-700 dark:text-slate-200"><?= $barang['stok_saat_ini'] ?> <span class="text-sm font-normal text-slate-400 dark:text-slate-500"><?= $barang['unit'] ?></span></p>
                            </div>
                            <div class="bg-white dark:bg-[#1e293b] p-4 rounded-xl border border-rose-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Batas ROP</p>
                                <p class="text-lg font-black text-rose-600"><?= $barang['stok_kritis'] ?> <span class="text-sm font-normal text-slate-400 dark:text-slate-500"><?= $barang['unit'] ?></span></p>
                            </div>
                        </div>

                        <form action="proses_outbound.php" method="POST" class="mt-6">
                            <input type="hidden" name="sku_outbound" value="<?= $barang['sku'] ?>">
                            
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Jumlah Barang Dikeluarkan:</label>
                                <input type="number" name="jumlah_keluar" required min="1" max="<?= $barang['stok_saat_ini'] ?>" placeholder="Maksimal: <?= $barang['stok_saat_ini'] ?>" class="w-full border border-slate-300 focus:outline-rose-500 p-4 rounded-xl font-bold text-xl">
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Departemen Peminta:</label>
                                <select name="departemen" id="deptSelect" onchange="toggleDeptLainnya()" required class="w-full border border-slate-300 focus:outline-rose-500 p-4 rounded-xl font-medium bg-white">
                                    <option value="">-- Pilih Departemen --</option>
                                    <option value="Produksi">Divisi Produksi</option>
                                    <option value="Maintenance">Divisi Maintenance (Perbaikan)</option>
                                    <option value="Operasional">Divisi Operasional Umum</option>
                                    <option value="Marketing">Divisi Marketing</option>
                                    <option value="Lainnya">Lainnya (Ketik Sendiri...)</option>
                                </select>
                                <input type="text" name="departemen_lainnya" id="deptLainnyaInput" placeholder="Ketik nama departemen..." class="w-full border border-slate-300 focus:outline-rose-500 p-4 rounded-xl font-medium mt-3 hidden" disabled>
                            </div>

                            <div class="mb-6">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Tujuan Penggunaan (Catatan Khusus):</label>
                                <input type="text" name="keterangan" required placeholder="Contoh: Digunakan untuk mesin A..." class="w-full border border-slate-300 focus:outline-rose-500 p-4 rounded-xl font-medium">
                            </div>

                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-black uppercase tracking-widest px-8 py-4 rounded-xl transition shadow-lg shadow-rose-500/30">
                                Kurangi Stok
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <i class="fa-solid fa-boxes-stacked text-6xl text-slate-200 mb-4"></i>
                    <h3 class="text-2xl font-black mb-2 text-slate-800 dark:text-white uppercase tracking-widest">OUTBOUND BARANG</h3>
                    <p class="text-slate-500 dark:text-slate-400 dark:text-slate-500 mb-8 text-sm">Pilih metode pemilihan barang yang akan dikeluarkan.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch max-w-4xl mx-auto w-full text-left">
                        
                        <!-- Mode Manual -->
                        <div class="border-2 border-slate-200 dark:border-slate-700 rounded-3xl p-8 bg-white dark:bg-[#1e293b] relative flex flex-col justify-center shadow-sm hover:border-red-300 transition">
                            <h4 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest mb-2"><i class="fa-solid fa-keyboard text-red-500 mr-2"></i> Mode Manual</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500 mb-6 font-medium">Ketik atau copy-paste kode SKU jika barcode fisik rusak atau sulit dibaca.</p>
                            
                            <form action="scan_barcode_outbound.php" method="POST" class="flex flex-col gap-4 mt-auto">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase mb-2 block">Kode SKU / Barcode</label>
                                    <input type="text" name="sku_scan" id="scan_input_outbound" autofocus placeholder="Contoh: SKU-001" class="w-full border border-slate-300 p-4 rounded-xl outline-none focus:border-red-500 bg-slate-50 dark:bg-slate-900 font-black text-center text-xl uppercase tracking-widest" required>
                                </div>
                                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-xl font-black uppercase tracking-widest text-sm hover:bg-red-600 transition shadow-lg shadow-slate-900/20 flex justify-center items-center gap-2">
                                    Cari Data Barang
                                </button>
                            </form>
                        </div>

                        <!-- Mode Scanner -->
                        <div class="border-2 border-dashed border-slate-300 rounded-3xl p-8 bg-slate-50 dark:bg-slate-900 relative flex flex-col items-center justify-center text-center">
                            <h4 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest mb-2"><i class="fa-solid fa-camera text-red-500 mr-2"></i> Mode Kamera</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500 mb-6 font-medium">Gunakan kamera perangkat Anda untuk scan barcode secara langsung.</p>
                            
                            <button type="button" onclick="openCameraModal()" class="bg-rose-600 text-white px-6 py-4 w-full rounded-xl font-black uppercase tracking-widest text-sm hover:bg-rose-700 transition shadow-lg shadow-rose-500/30 flex items-center justify-center gap-3">
                                <i class="fa-solid fa-qrcode text-2xl"></i> Buka Kamera Scanner
                            </button>

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
        let html5QrcodeScanner = null;

        function openCameraModal() {
            document.getElementById('cameraModal').classList.remove('hidden');
            
            if (!html5QrcodeScanner) {
                // Determine responsive qrbox size
                let qrboxSize = 250;
                if (window.innerWidth < 600) {
                    qrboxSize = 200; // Smaller box on mobile
                }

                html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader",
                    { 
                        fps: 10, 
                        qrbox: { width: qrboxSize, height: qrboxSize },
                        aspectRatio: 1.0
                    },
                    /* verbose= */ false
                );
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            }
        }

        function closeCameraModal() {
            document.getElementById('cameraModal').classList.add('hidden');
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().then(() => {
                    html5QrcodeScanner = null;
                }).catch(error => {
                    console.log("Failed to clear scanner: ", error);
                });
            }
        }

        // Fungsi ketika kamera berhasil membaca Barcode
        function onScanSuccess(decodedText, decodedResult) {
            // Hentikan kamera agar tidak scan berkali-kali
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().then(() => {
                    html5QrcodeScanner = null;
                    document.getElementById('cameraModal').classList.add('hidden');
                    
                    // Masukkan teks barcode ke form tersembunyi
                    document.getElementById('sku_input').value = decodedText;
                    
                    // Tekan tombol submit secara otomatis
                    document.getElementById('scan_form').submit();
                }).catch(err => {
                    console.error("Failed to clear scanner", err);
                });
            }
        }

        function onScanFailure(error) {
            // Abaikan error saat proses mencari
        }

        function toggleDeptLainnya() {
            const select = document.getElementById('deptSelect');
            const input = document.getElementById('deptLainnyaInput');
            if (select.value === 'Lainnya') {
                input.classList.remove('hidden');
                input.disabled = false;
                input.required = true;
                input.focus();
            } else {
                input.classList.add('hidden');
                input.disabled = true;
                input.required = false;
                input.value = '';
            }
        }

        // SCRIPT UX GUDANG: AUTO-FOCUS & COLOR FEEDBACK
        function flashScreen(isSuccess) {
            const flashDiv = document.createElement('div');
            flashDiv.style.position = 'fixed';
            flashDiv.style.inset = '0';
            flashDiv.style.zIndex = '9999';
            flashDiv.style.pointerEvents = 'none';
            flashDiv.style.transition = 'opacity 0.8s ease-out';
            flashDiv.style.backgroundColor = isSuccess ? 'rgba(34, 197, 94, 0.7)' : 'rgba(239, 68, 68, 0.8)';
            
            document.body.appendChild(flashDiv);
            
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gainNode = ctx.createGain();
                osc.connect(gainNode);
                gainNode.connect(ctx.destination);
                
                if (isSuccess) {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(800, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.1);
                    gainNode.gain.setValueAtTime(0.5, ctx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.3);
                } else {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(300, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(100, ctx.currentTime + 0.2);
                    gainNode.gain.setValueAtTime(0.8, ctx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.4);
                }
            } catch(e) { console.log('Audio error:', e); }
            
            setTimeout(() => {
                flashDiv.style.opacity = '0';
                setTimeout(() => flashDiv.remove(), 800);
            }, 100);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const scanInput = document.getElementById('scan_input_outbound');
            if (scanInput) {
                scanInput.focus();
                document.addEventListener('click', (e) => {
                    const tag = e.target.tagName;
                    if (tag !== 'INPUT' && tag !== 'SELECT' && tag !== 'BUTTON' && tag !== 'A') {
                        scanInput.focus();
                    }
                });
                scanInput.addEventListener('blur', () => {
                    setTimeout(() => {
                        const tag = document.activeElement ? document.activeElement.tagName : '';
                        if (tag !== 'INPUT' && tag !== 'SELECT' && tag !== 'BUTTON' && tag !== 'A') {
                            scanInput.focus();
                        }
                    }, 50);
                });
            }

            // Trigger Flash Feedback
            <?php if ($pesan_sukses): ?>
            flashScreen(true);
            <?php endif; ?>
            
            <?php if ($pesan_error): ?>
            flashScreen(false);
            <?php endif; ?>
        });
    </script>
</body>
</html>
