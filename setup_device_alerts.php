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

    // Update alerts table structure
    echo "<h3>Updating alerts table structure...</h3>";
    
    $alterQueries = [
        "ALTER TABLE alerts ADD COLUMN IF NOT EXISTS message TEXT AFTER last_value",
        "ALTER TABLE alerts ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER message",
        "ALTER TABLE alerts ADD COLUMN IF NOT EXISTS last_triggered TIMESTAMP NULL AFTER created_at",
        "ALTER TABLE alerts ADD COLUMN IF NOT EXISTS last_value DECIMAL(10,2) NULL AFTER threshold"
    ];

    foreach ($alterQueries as $query) {
        $db->exec($query);
        echo "Executed: " . $query . "<br>";
    }

    // Get all devices
    $stmt = $db->query("SELECT id, ip_address FROM devices");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($devices)) {
        throw new Exception("No devices found in the database");
    }

    // Alert templates for each device
    $alertTemplates = [
        [
            'metric' => 'cpu_usage',
            'conditions' => [
                ['threshold' => 90, 'severity' => 'critical'],
                ['threshold' => 80, 'severity' => 'warning'],
                ['threshold' => 70, 'severity' => 'info']
            ]
        ],
        [
            'metric' => 'memory_usage',
            'conditions' => [
                ['threshold' => 95, 'severity' => 'critical'],
                ['threshold' => 85, 'severity' => 'warning'],
                ['threshold' => 75, 'severity' => 'info']
            ]
        ],
        [
            'metric' => 'disk_usage',
            'conditions' => [
                ['threshold' => 95, 'severity' => 'critical'],
                ['threshold' => 85, 'severity' => 'warning'],
                ['threshold' => 75, 'severity' => 'info']
            ]
        ],
        [
            'metric' => 'interface_status',
            'conditions' => [
                ['threshold' => 0, 'severity' => 'critical']
            ]
        ],
        [
            'metric' => 'response_time',
            'conditions' => [
                ['threshold' => 2000, 'severity' => 'critical'],
                ['threshold' => 1000, 'severity' => 'warning'],
                ['threshold' => 500, 'severity' => 'info']
            ]
        ]
    ];

    // Begin transaction
    $db->beginTransaction();

    // Clear existing alerts
    $db->exec("DELETE FROM alerts");
    echo "Cleared existing alerts<br>";

    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO alerts (
            device_id, metric, `condition`, threshold, 
            severity, status, created_at, last_triggered,
            last_value, message
        ) VALUES (
            :device_id, :metric, '>', :threshold,
            :severity, :status, NOW(), :last_triggered,
            :last_value, :message
        )
    ");

    $current_time = date('Y-m-d H:i:s');

    // Create alerts for each device
    foreach ($devices as $device) {
        echo "<h3>Creating alerts for device: {$device['ip_address']}</h3>";

        foreach ($alertTemplates as $template) {
            foreach ($template['conditions'] as $condition) {
                // Generate a random value near the threshold for some triggered alerts
                $shouldTrigger = rand(0, 3) === 0; // 25% chance of being triggered
                $last_value = $shouldTrigger ? 
                    $condition['threshold'] + rand(1, 10) : 
                    $condition['threshold'] - rand(5, 15);

                $status = $shouldTrigger ? 'triggered' : 'active';
                $last_triggered = $shouldTrigger ? $current_time : null;

                // Create message based on metric
                $message = '';
                switch ($template['metric']) {
                    case 'cpu_usage':
                        $message = "CPU Usage is {$last_value}%";
                        break;
                    case 'memory_usage':
                        $message = "Memory Usage is {$last_value}%";
                        break;
                    case 'disk_usage':
                        $message = "Disk Usage is {$last_value}%";
                        break;
                    case 'interface_status':
                        $message = "Interface is " . ($last_value == 0 ? "Down" : "Up");
                        break;
                    case 'response_time':
                        $message = "Response Time is {$last_value}ms";
                        break;
                }

                $params = [
                    ':device_id' => $device['id'],
                    ':metric' => $template['metric'],
                    ':threshold' => $condition['threshold'],
                    ':severity' => $condition['severity'],
                    ':status' => $status,
                    ':last_triggered' => $last_triggered,
                    ':last_value' => $last_value,
                    ':message' => $message
                ];

                $stmt->execute($params);
                echo "Created {$condition['severity']} alert for {$template['metric']} (Threshold: {$condition['threshold']}, Status: {$status})<br>";
            }
        }
    }

    // Commit transaction
    $db->commit();

    echo "<h2>Device alerts created successfully!</h2>";
    echo "<p>You can now <a href='/alerts'>view the alerts page</a> to see all alerts.</p>";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }

    echo "<h2>Error creating device alerts:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Error details have been logged.</p>";
    error_log("Device alerts setup error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
} 