<?php
session_start();
require 'database.php';

// Cek apakah diakses oleh Manager Procurement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager Procurement') {
    header("Location: login.php");
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $vendor_id = 'NULL';

    // Insert into database with hashed password
    $query = "INSERT INTO users (username, password, role, vendor_id) VALUES ('$username', '$hashed_password', '$role', $vendor_id)";

    if (mysqli_query($conn, $query)) {
        header("Location: master_user.php?pesan=sukses");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    die();
} else {
    header("Location: master_user.php");
    die();
}
