<?php
require 'database.php';
mysqli_query($conn, "UPDATE sph_submissions SET status = 'Evaluated' WHERE status IN ('Selected', 'Rejected')");
echo "Done resetting SPH status.\n";
