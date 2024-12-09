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

    // Drop existing settings table if exists
    $db->exec("DROP TABLE IF EXISTS settings");

    // Create settings table
    $createTable = "
    CREATE TABLE settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($createTable);
    echo "Settings table created successfully.<br>";

    // Insert default settings
    $defaultSettings = [
        ['snmp_version', '2c'],
        ['community_string', 'public'],
        ['snmp_timeout', '5'],
        ['snmp_retries', '3'],
        ['monitoring_interval', '5'],
        ['data_retention', '30'],
        ['auto_discovery', 'false'],
        ['alert_enabled', 'true'],
        ['smtp_port', '587'],
        ['smtp_auth', 'true'],
        ['smtp_secure', 'true'],
        ['timezone', 'UTC'],
        ['date_format', 'Y-m-d'],
        ['debug_mode', 'false'],
        ['maintenance_mode', 'false']
    ];

    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
        echo "Inserted setting: {$setting[0]} = {$setting[1]}<br>";
    }

    echo "<h2>Settings setup completed successfully!</h2>";
    echo "<p>You can now <a href='/settings'>view the settings page</a>.</p>";

} catch (Exception $e) {
    echo "<h2>Error setting up settings:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Error details have been logged.</p>";
    error_log("Settings setup error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
} 