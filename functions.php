<?php

/**
 * Calculates the status label based on current stock and critical stock level.
 * 
 * @param int $stok_awal Current stock
 * @param int $stok_kritis Critical stock level
 * @return string 'Kritis', 'Warning', or 'Aman'
 */
function calculateStatusLabel($stok_awal, $stok_kritis) {
    if ($stok_awal < $stok_kritis) {
        return 'Kritis';
    } elseif ($stok_awal <= ($stok_kritis + 3)) { // Jarak 3 angka dari kritis masuk Warning
        return 'Warning';
    } else {
        return 'Aman';
    }
}

/**
 * Generates a CSRF token and stores it in session.
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies if the provided CSRF token matches the session CSRF token.
 * 
 * @param string $token The token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

