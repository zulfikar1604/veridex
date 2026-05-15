<?php
require 'database.php';

// Ubah kolom status menjadi VARCHAR agar bisa menampung nilai baru
$sql = "ALTER TABLE purchase_orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'Pending Manager'";
if (mysqli_query($conn, $sql)) {
    echo "Berhasil mengubah kolom status menjadi VARCHAR.\n";
} else {
    echo "Gagal mengubah: " . mysqli_error($conn) . "\n";
}

// Kembalikan SPH ke Evaluated agar bisa dicoba lagi
mysqli_query($conn, "UPDATE sph_submissions SET status = 'Evaluated' WHERE status IN ('Selected', 'Rejected')");
echo "Berhasil mengembalikan SPH ke Evaluated.\n";
