<?php
require 'database.php';
$q = mysqli_query($conn, "SELECT username, role, password FROM users");
while($row = mysqli_fetch_assoc($q)) {
    print_r($row);
}
echo "Done.\n";
