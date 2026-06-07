<?php
session_start();
require 'database.php';

// Bisa diakses oleh siapa saja yang punya link (jika ingin tanpa login), 
// tapi sebaiknya diproteksi agar hanya role Gudang yang bisa konfirmasi.
// Namun untuk fleksibilitas scan HP, kita asumsikan yang scan sudah login di HP.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    // Jika belum login, simpan url ini agar setelah login bisa di-redirect kembali
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$po_number = mysqli_real_escape_string($conn, $_GET['po'] ?? '');

if (empty($po_number)) {
    die("Invalid QR Code.");
}

$query_po = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, i.stok_saat_ini, i.unit, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.po_number = '$po_number' AND p.status = 'Shipped'
");

$po = mysqli_fetch_assoc($query_po);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Konfirmasi Inbound</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-6 md:p-8 rounded-3xl shadow-xl border border-slate-200 w-full max-w-md">
        
        <?php if ($po): ?>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-box-open text-2xl"></i>
                </div>
                <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight">Konfirmasi Inbound</h1>
                <p class="text-sm text-slate-500 font-medium mt-1">PO: <?= htmlspecialchars($po['po_number']) ?></p>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 mb-6 space-y-4">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nama Barang</p>
                    <p class="text-lg font-black text-slate-800"><?= htmlspecialchars($po['nama_barang']) ?></p>
                    <p class="text-xs text-slate-500">SKU: <?= htmlspecialchars($po['sku']) ?></p>
                </div>
                <hr class="border-slate-200">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Vendor Pengirim</p>
                    <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($po['nama_perusahaan']) ?></p>
                </div>
                <hr class="border-slate-200">
                <div class="flex justify-between items-center flex-wrap gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jumlah Diterima</p>
                        <p class="text-3xl font-black text-blue-600"><?= $po['jumlah_pesan'] ?> <span class="text-sm font-normal text-slate-500"><?= htmlspecialchars($po['unit']) ?></span></p>
                    </div>
                </div>
                <hr class="border-slate-200">
                <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-2"><i class="fa-solid fa-truck mr-1"></i> Detail Ekspedisi</p>
                    <div class="overflow-x-auto w-full">
<table class="text-xs w-full text-slate-600 font-medium">
                        <tr><td class="pb-1 w-1/3">Surat Jalan</td><td class="pb-1 font-bold text-slate-800">: <?= htmlspecialchars($po['no_resi']) ?></td></tr>
                        <tr><td class="pb-1 w-1/3">Pengemudi</td><td class="pb-1 font-bold text-slate-800">: <?= htmlspecialchars($po['sj_pengemudi']) ?></td></tr>
                        <tr><td class="pb-1 w-1/3">No. Polisi</td><td class="pb-1 font-bold text-slate-800 uppercase">: <?= htmlspecialchars($po['sj_nopol']) ?></td></tr>
                    </table>
</div>
                    <div class="mt-3">
                        <a href="print_surat_jalan.php?id=<?= $po['id'] ?>" target="_blank" class="block w-full text-center bg-white border border-blue-200 text-blue-600 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors shadow-sm">
                            <i class="fa-solid fa-print mr-1"></i> Cetak Surat Jalan Masuk
                        </a>
                    </div>
                </div>
            </div>

            <form action="proses_inbound.php" method="POST" id="inboundForm">
                <input type="hidden" name="sku_inbound" value="<?= $po['sku'] ?>">
                <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
                <input type="hidden" name="jumlah_masuk" value="<?= $po['jumlah_pesan'] ?>">
                
                <button type="submit" id="btnKonfirmasi" class="w-full bg-green-600 hover:bg-green-700 text-white font-black uppercase tracking-widest py-4 rounded-xl transition shadow-lg shadow-green-500/30 flex justify-center items-center gap-2 text-lg">
                    <i class="fa-solid fa-check"></i> Konfirmasi Stok
                </button>
            </form>

            <script>
            document.getElementById('inboundForm').addEventListener('submit', function(e) {
                const btn = document.getElementById('btnKonfirmasi');
                if (btn.disabled) {
                    e.preventDefault();
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
            });
            </script>
            
            <a href="scan_barcode_inbound.php" class="block text-center mt-4 text-xs font-bold text-slate-400 hover:text-slate-600">Batal & Kembali ke Dashboard</a>

        <?php else: ?>
            <div class="text-center py-8">
                <i class="fa-solid fa-circle-xmark text-5xl text-red-500 mb-4"></i>
                <h2 class="text-xl font-black text-slate-800 uppercase mb-2">Tidak Ditemukan</h2>
                <p class="text-slate-500 text-sm">Pesanan ini tidak ada atau sudah dikonfirmasi sebelumnya.</p>
                <a href="scan_barcode_inbound.php" class="mt-6 inline-block bg-slate-900 text-white px-6 py-3 rounded-xl font-bold text-sm">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>

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
