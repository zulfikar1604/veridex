<?php
require 'database.php';
$q = mysqli_query($conn, "SELECT id, status, inventory_id, vendor_id, tbe_score, cbe_score FROM sph_submissions");
$count = 0;
while($row = mysqli_fetch_assoc($q)) {
    print_r($row);
    $count++;
}
echo "Total SPH: $count\n";

$q2 = mysqli_query($conn, "SELECT id, po_number, status FROM purchase_orders");
$count2 = 0;
while($row = mysqli_fetch_assoc($q2)) {
    print_r($row);
    $count2++;
}
echo "Total PO: $count2\n";

echo "Done.\n";
