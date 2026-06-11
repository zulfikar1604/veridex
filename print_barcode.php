<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Gudang') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['sku'])) {
    die("SKU tidak ditemukan.");
}

$sku = htmlspecialchars($_GET['sku']);
$nama = isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : '';

// Render halaman print-friendly
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Print Barcode - <?= $sku ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f1f5f9;
        }
        .print-container {
            background: white;
            padding: 30px;
            border: 2px dashed #cbd5e1;
            text-align: center;
            width: 300px;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        .sku {
            font-weight: bold;
            font-size: 24px;
            margin-top: 10px;
            letter-spacing: 2px;
            color: #1e293b;
        }
        .nama {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .btn-print {
            margin-top: 20px;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        @media print {
            body {
                background-color: white;
                height: auto;
                display: block;
                padding: 0;
                margin: 0;
            }
            .print-container {
                border: none;
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: 100vw;
            }
            img {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 0 auto;
                page-break-inside: avoid;
            }
            .btn-print {
                display: none;
            }
            @page {
                size: auto;
                margin: 5mm; /* Beri sedikit margin agar tidak kepotong di printer kasir/thermal */
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="print-container">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($sku) ?>" alt="QR Code <?= $sku ?>">
        <div class="sku"><?= $sku ?></div>
        <div class="nama"><?= $nama ?></div>
        <button class="btn-print" onclick="window.print()">Cetak Ulang</button>
    </div>
</body>
</html>
