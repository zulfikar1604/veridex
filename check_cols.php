<?php
require 'database.php';
$q = mysqli_query($conn, "DESCRIBE purchase_orders");
while($row = mysqli_fetch_assoc($q)) {
    print_r($row);
}
echo "Done.\n";
