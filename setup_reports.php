<?php
require_once __DIR__ . '/bootstrap.php';

try {
    // Read and execute the reports SQL file
    $sql = file_get_contents(__DIR__ . '/sql/reports.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    echo "Reports tables created successfully!\n";
} catch (Exception $e) {
    die("Error setting up reports tables: " . $e->getMessage() . "\n");
} 