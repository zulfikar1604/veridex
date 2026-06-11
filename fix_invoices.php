<?php
require 'database.php';

echo "<h2>Veridex Auto-Fix Database</h2>";

// 1. Cek berapa banyak PO yang Payment Completed tapi Invoice masih Unpaid
$cek = mysqli_query($conn, "
    SELECT i.id as invoice_id, po.po_number, po.status as po_status, i.status as inv_status 
    FROM invoices i 
    JOIN purchase_orders po ON i.po_id = po.id 
    WHERE po.status = 'Payment Completed' AND i.status != 'Paid'
");

if (!$cek) {
    die("Gagal membaca database: " . mysqli_error($conn));
}

$jumlah_bermasalah = mysqli_num_rows($cek);
echo "<p>Ditemukan <b>$jumlah_bermasalah</b> invoice yang tidak sinkron.</p>";

if ($jumlah_bermasalah > 0) {
    echo "<ul>";
    while ($row = mysqli_fetch_assoc($cek)) {
        echo "<li>PO: {$row['po_number']} | PO Status: {$row['po_status']} | Inv Status: {$row['inv_status']}</li>";
        
        // Fix satu per satu
        $inv_id = $row['invoice_id'];
        mysqli_query($conn, "UPDATE invoices SET status = 'Paid', paid_at = NOW() WHERE id = $inv_id");
    }
    echo "</ul>";
    echo "<h3 style='color: green;'>Semua data yang bermasalah di atas telah diperbaiki secara paksa menjadi 'Paid'.</h3>";
} else {
    echo "<h3 style='color: blue;'>Database sudah sinkron. Tidak ada masalah.</h3>";
}

echo "<p><a href='invoice_vendor.php'>Kembali ke Halaman Vendor</a></p>";
?>
