<?php
require 'database.php';

// Migrasi tabel users
$res_users = mysqli_query($conn, "SELECT id, password FROM users");
while ($row = mysqli_fetch_assoc($res_users)) {
    $id = $row['id'];
    $pwd = $row['password'];
    
    // Cek apakah belum di-hash (Bcrypt hash length is 60 and starts with $2y$)
    if (strlen($pwd) != 60 || substr($pwd, 0, 4) !== '$2y$') {
        $hashed = password_hash($pwd, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $id");
        echo "User ID $id password hashed.\n";
    }
}

// Migrasi tabel vendors
$res_vendors = mysqli_query($conn, "SELECT id, password FROM vendors WHERE password IS NOT NULL AND password != ''");
if ($res_vendors) {
    while ($row = mysqli_fetch_assoc($res_vendors)) {
        $id = $row['id'];
        $pwd = $row['password'];
        
        if (strlen($pwd) != 60 || substr($pwd, 0, 4) !== '$2y$') {
            $hashed = password_hash($pwd, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE vendors SET password = '$hashed' WHERE id = $id");
            echo "Vendor ID $id password hashed.\n";
        }
    }
}

echo "Migrasi Hash selesai.\n";
?>
