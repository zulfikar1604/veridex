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
    <style> body { font-family: 'Inter', sans-serif; } </style>
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
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jumlah Diterima</p>
                        <p class="text-3xl font-black text-blue-600"><?= $po['jumlah_pesan'] ?> <span class="text-sm font-normal text-slate-500"><?= htmlspecialchars($po['unit']) ?></span></p>
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

</body>
</html>
