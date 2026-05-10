<?php

require 'functions.php';

function testCalculateStatusLabel() {
    $testCases = [
        // [stok_awal, stok_kritis, expected]
        [5, 10, 'Kritis'],   // Below critical
        [9, 10, 'Kritis'],   // Just below critical
        [10, 10, 'Warning'], // Exactly critical
        [11, 10, 'Warning'], // Within critical + 3
        [13, 10, 'Warning'], // Exactly critical + 3
        [14, 10, 'Aman'],    // Above critical + 3
        [20, 10, 'Aman'],    // Well above critical
        
        // Edge cases
        [0, 0, 'Warning'],   // Critical is 0, stock is 0 -> should be Warning based on ($stok_awal <= $stok_kritis + 3)
        [1, 0, 'Warning'],
        [3, 0, 'Warning'],
        [4, 0, 'Aman'],
        [-1, 0, 'Kritis'],
    ];

    $passed = 0;
    $failed = 0;

    foreach ($testCases as $index => $case) {
        [$stok_awal, $stok_kritis, $expected] = $case;
        $actual = calculateStatusLabel($stok_awal, $stok_kritis);
        
        if ($actual === $expected) {
            echo "Test Case #$index: PASS (stok_awal=$stok_awal, stok_kritis=$stok_kritis, expected=$expected)\n";
            $passed++;
        } else {
            echo "Test Case #$index: FAIL (stok_awal=$stok_awal, stok_kritis=$stok_kritis, expected=$expected, actual=$actual)\n";
            $failed++;
        }
    }

    echo "\nSummary: $passed passed, $failed failed.\n";
    
    if ($failed > 0) {
        exit(1);
    }
}

testCalculateStatusLabel();
