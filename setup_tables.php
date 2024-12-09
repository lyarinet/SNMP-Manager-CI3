<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', __DIR__);

// Load database configuration
require_once BASE_PATH . '/config/Database.php';

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Read and execute the reports SQL file
    $sql = file_get_contents(BASE_PATH . '/sql/reports.sql');
    
    // Remove the first character 'a' if it exists (from the file content)
    if (substr($sql, 0, 1) === 'a') {
        $sql = substr($sql, 1);
    }
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each statement
    foreach($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...<br>";
        }
    }
    
    echo "<h2>Reports tables created successfully!</h2>";
    echo "<p>You can now <a href='/reports'>view the reports page</a>.</p>";

} catch (Exception $e) {
    echo "<h2>Error setting up reports tables:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
} 