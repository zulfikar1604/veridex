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
