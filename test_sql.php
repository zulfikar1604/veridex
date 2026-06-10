<?php
require 'database.php';
$query = "
    SELECT 
        i.id as inventory_id, 
        i.nama_barang, 
        i.sku, 
        i.kategori,
        'Closed (Telah Diproses ke RFQ)' as status,
        r.created_at as pr_date
    FROM rfq_requests r
    JOIN inventory i ON r.inventory_id = i.id
    UNION
    SELECT 
        id as inventory_id, 
        nama_barang, 
        sku, 
        kategori,
        'Open (Menunggu Proses Procurement)' as status,
        CURRENT_TIMESTAMP as pr_date
    FROM inventory 
    WHERE status_label = 'Kritis' AND id NOT IN (SELECT inventory_id FROM rfq_requests WHERE status = 'Open')
    ORDER BY pr_date DESC
";
$res = mysqli_query($conn, $query);
if (!$res) {
    echo mysqli_error($conn);
} else {
    echo "Success";
}
