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
<?php
$page_title = "Barcode Inbound";
$page_subtitle = "Penerimaan Barang Cepat via Barcode Scanner";
include 'header_meta.php';
?>
<!-- QR Code Generator & Scanner -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
                <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-lg"><i class="fa-solid fa-camera mr-2 text-rose-500"></i> Kamera Scanner Inbound</h3>
                <button type="button" onclick="closeCameraModal()" class="w-10 h-10 bg-rose-100 text-rose-600 rounded-full hover:bg-rose-200 hover:text-rose-700 flex items-center justify-center font-bold transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="p-6 bg-slate-50 dark:bg-slate-900 flex-1 flex flex-col items-center justify-center min-h-[60vh] max-h-[75vh]">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mb-4 text-center">Arahkan QR Code PO atau Barcode ke area kamera</p>
                <div id="reader" class="w-full mx-auto rounded-2xl overflow-hidden shadow-inner border-2 border-dashed border-slate-300 dark:border-slate-600 bg-black flex-1 flex items-center justify-center"></div>
            </div>
        </div>
    </div>

    <?php
    $page_title = "Barcode Inbound";
    $page_subtitle = "Penerimaan Barang Cepat via Barcode Scanner";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php 
        $active = 'inbound'; 
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
                <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-widest">
                        <i class="fa-solid fa-truck-fast text-red-500 mr-2"></i> Daftar Pengiriman Tiba
                    </h3>
                    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                        <button type="button" onclick="openCameraModal()" class="bg-rose-600 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-rose-700 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-camera text-sm"></i> Scan Barcode
                        </button>
                        <form action="konfirmasi_inbound.php" method="GET" class="flex gap-2" id="inbound_form">
                            <input type="text" name="po" id="scan_input_inbound" autofocus placeholder="Input No PO Manual..." class="border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1 text-xs focus:outline-red-500" required>
                            <button type="submit" class="bg-slate-800 text-white px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-slate-700 transition">Cari</button>
                        </form>
                    </div>
                </div>
                
                <?php if(mysqli_num_rows($query_pending) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while($pending = mysqli_fetch_assoc($query_pending)): 
                        $qr_url = $base_url . "?po=" . urlencode($pending['po_number']);
                    ?>
                    <div class="bg-white dark:bg-[#1e293b] rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 flex flex-col items-center text-center relative overflow-hidden group hover:border-red-200 hover:shadow-md transition">
                        
                        <div class="absolute top-0 right-0 bg-red-50 text-red-500 font-black text-[9px] uppercase tracking-widest px-3 py-1 rounded-bl-xl border-b border-l border-red-100">
                            Konfirmasi Inbound
                        </div>

                        <div class="mb-4 mt-2">
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Nomor PO</p>
                            <h4 class="font-black text-lg text-slate-800 dark:text-white"><?= htmlspecialchars($pending['po_number']) ?></h4>
                        </div>
                        
                        <!-- Tempat Render QR Code -->
                        <div id="qrcode-<?= $pending['id'] ?>" class="bg-white dark:bg-[#1e293b] p-2 border border-slate-200 dark:border-slate-700 rounded-xl mb-4" data-url="<?= htmlspecialchars($qr_url) ?>"></div>
                        
                        <div class="bg-slate-50 dark:bg-slate-900 w-full p-4 rounded-2xl border border-slate-100 dark:border-slate-800 mt-auto">
                            <p class="font-black text-slate-800 dark:text-white uppercase text-sm mb-1"><?= htmlspecialchars($pending['nama_barang']) ?></p>
                            <div class="flex justify-between items-center text-xs mt-3">
                                <span class="font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500"><i class="fa-solid fa-building mr-1"></i> <?= htmlspecialchars($pending['nama_perusahaan']) ?></span>
                                <span class="font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-lg"><?= $pending['jumlah_pesan'] ?> Unit</span>
                            </div>
                        </div>

                        <!-- Tombol Manual -->
                        <a href="konfirmasi_inbound.php?po=<?= urlencode($pending['po_number']) ?>" class="mt-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest hover:text-red-500 transition underline underline-offset-4">Proses di Layar Ini</a>

                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="bg-white dark:bg-[#1e293b] p-12 rounded-3xl border border-slate-100 dark:border-slate-800 text-center shadow-sm">
                    <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-box-open text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight mb-2">Gudang Kosong</h4>
                    <p class="text-slate-400 dark:text-slate-500 font-bold text-sm">Tidak ada pengiriman barang yang sedang menunggu inbound saat ini.</p>
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

        // Script untuk Scanner Kamera Pop Up
        let html5QrcodeScanner = null;

        function openCameraModal() {
            document.getElementById('cameraModal').classList.remove('hidden');
            
            if (!html5QrcodeScanner) {
                let qrboxSize = 250;
                if (window.innerWidth < 600) {
                    qrboxSize = 200;
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

        function onScanSuccess(decodedText, decodedResult) {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().then(() => {
                    html5QrcodeScanner = null;
                    document.getElementById('cameraModal').classList.add('hidden');
                    
                    // Cek apakah hasil scan berupa URL (mengandung http atau https)
                    if (decodedText.startsWith('http://') || decodedText.startsWith('https://')) {
                        window.location.href = decodedText;
                    } else {
                        // Jika bukan URL, asumsikan itu adalah nomor PO atau SKU
                        document.getElementById('scan_input_inbound').value = decodedText;
                        document.getElementById('inbound_form').submit();
                    }
                }).catch(err => {
                    console.error("Failed to clear scanner", err);
                });
            }
        }

        function onScanFailure(error) {
            // Abaikan error saat proses mencari
        }
    </script>

    <!-- SCRIPT UX GUDANG: AUTO-FOCUS & COLOR FEEDBACK -->
    <script>
        // 1. Audio Beep & Color Flash Function
        function flashScreen(isSuccess) {
            const flashDiv = document.createElement('div');
            flashDiv.style.position = 'fixed';
            flashDiv.style.inset = '0';
            flashDiv.style.zIndex = '9999';
            flashDiv.style.pointerEvents = 'none';
            flashDiv.style.transition = 'opacity 0.8s ease-out';
            flashDiv.style.backgroundColor = isSuccess ? 'rgba(34, 197, 94, 0.7)' : 'rgba(239, 68, 68, 0.8)';
            
            document.body.appendChild(flashDiv);
            
            // Audio context
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

        // 2. Auto-Focus Enforcer untuk Scanner Gun
        document.addEventListener('DOMContentLoaded', () => {
            const scanInput = document.getElementById('scan_input_inbound');
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

            // 3. Trigger Flash jika ada pesan sukses
            <?php if ($pesan_sukses): ?>
            flashScreen(true);
            <?php endif; ?>
        });
    </script>
</body>
</html>
