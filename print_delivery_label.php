<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

$po_id = intval($_GET['id'] ?? 0);
$vendor_id = $_SESSION['vendor_id'] ?? 0;

$query = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku, v.nama_perusahaan, v.alamat 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE p.id = $po_id AND p.vendor_id = $vendor_id AND p.status != 'Pending Manager'
");

$po = mysqli_fetch_assoc($query);

if (!$po) {
    die("PO tidak ditemukan atau Anda tidak memiliki akses.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Label Pengiriman - <?= $po['po_number'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 w-[10cm] border border-gray-300 shadow-lg relative">
        <!-- Pengirim -->
        <div class="border-b-2 border-black pb-4 mb-4">
            <h2 class="font-black text-xl uppercase"><?= htmlspecialchars($po['nama_perusahaan']) ?></h2>
            <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($po['alamat'] ?? 'Alamat Vendor') ?></p>
        </div>

        <!-- Penerima -->
        <div class="mb-6">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Penerima:</p>
            <h3 class="font-black text-lg">Gudang Veridex</h3>
            <p class="text-xs text-gray-600">Jl. Industri Raya No. 45, Kawasan Terpadu</p>
        </div>

        <!-- Detail Barang -->
        <div class="bg-gray-50 p-4 border border-gray-200 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase">Item:</span>
                <span class="font-black text-sm"><?= htmlspecialchars($po['sku']) ?></span>
            </div>
            <p class="font-bold text-gray-800 text-lg mb-2"><?= htmlspecialchars($po['nama_barang']) ?></p>
            
            <div class="flex justify-between items-center mt-4 border-t border-gray-200 pt-2">
                <span class="text-xs font-bold text-gray-500 uppercase">Jumlah:</span>
                <span class="font-black text-xl"><?= $po['jumlah_pesan'] ?> Unit</span>
            </div>
        </div>

        <!-- Barcode PO -->
        <div class="text-center">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Scan Barcode untuk Inbound Gudang</p>
            <svg id="barcode"></svg>
        </div>
        
        <button onclick="window.print()" class="no-print absolute -top-12 right-0 bg-blue-600 text-white px-4 py-2 rounded font-bold shadow hover:bg-blue-700"><i class="fa-solid fa-print mr-2"></i>Cetak Label</button>
    </div>

    <script>
        JsBarcode("#barcode", "<?= $po['po_number'] ?>", {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 60,
            displayValue: true,
            fontSize: 14,
            fontWeight: "bold"
        });
    </script>
</body>
</html>
