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

    // Get the first device ID
    $stmt = $db->query("SELECT id FROM devices LIMIT 1");
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        throw new Exception("No devices found in the database");
    }

    $device_id = $device['id'];
    $current_time = date('Y-m-d H:i:s');
    $hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $two_hours_ago = date('Y-m-d H:i:s', strtotime('-2 hours'));

    // Demo alerts data
    $alerts = [
        [
            'device_id' => $device_id,
            'metric' => 'cpu_usage',
            'condition' => '>',
            'threshold' => 80,
            'last_value' => 85.5,
            'severity' => 'critical',
            'status' => 'triggered',
            'last_triggered' => $current_time,
            'message' => 'CPU Usage exceeded threshold'
        ],
        [
            'device_id' => $device_id,
            'metric' => 'memory_usage',
            'condition' => '>',
            'threshold' => 90,
            'last_value' => 92.3,
            'severity' => 'critical',
            'status' => 'triggered',
            'last_triggered' => $hour_ago,
            'message' => 'Memory Usage exceeded threshold'
        ],
        [
            'device_id' => $device_id,
            'metric' => 'disk_usage',
            'condition' => '>',
            'threshold' => 85,
            'last_value' => 87.8,
            'severity' => 'warning',
            'status' => 'triggered',
            'last_triggered' => $two_hours_ago,
            'message' => 'Disk Usage exceeded threshold'
        ],
        [
            'device_id' => $device_id,
            'metric' => 'interface_status',
            'condition' => '=',
            'threshold' => 0,
            'last_value' => 0,
            'severity' => 'critical',
            'status' => 'triggered',
            'last_triggered' => $current_time,
            'message' => 'Interface is down'
        ],
        [
            'device_id' => $device_id,
            'metric' => 'response_time',
            'condition' => '>',
            'threshold' => 1000,
            'last_value' => 1500,
            'severity' => 'warning',
            'status' => 'triggered',
            'last_triggered' => $hour_ago,
            'message' => 'High response time detected'
        ]
    ];

    // Clear existing alerts
    $db->exec("DELETE FROM alerts");

    // Insert demo alerts
    $stmt = $db->prepare("
        INSERT INTO alerts (
            device_id, metric, `condition`, threshold, last_value, 
            severity, status, last_triggered, message, created_at
        ) VALUES (
            :device_id, :metric, :condition, :threshold, :last_value,
            :severity, :status, :last_triggered, :message, :created_at
        )
    ");

    foreach ($alerts as $alert) {
        $stmt->execute([
            ':device_id' => $alert['device_id'],
            ':metric' => $alert['metric'],
            ':condition' => $alert['condition'],
            ':threshold' => $alert['threshold'],
            ':last_value' => $alert['last_value'],
            ':severity' => $alert['severity'],
            ':status' => $alert['status'],
            ':last_triggered' => $alert['last_triggered'],
            ':message' => $alert['message'],
            ':created_at' => $alert['last_triggered']
        ]);
        echo "Inserted alert: {$alert['metric']} - {$alert['message']}<br>";
    }

    echo "<h2>Demo alerts created successfully!</h2>";
    echo "<p>You can now <a href='/dashboard'>view the dashboard</a> to see the alerts.</p>";

} catch (Exception $e) {
    echo "<h2>Error creating demo alerts:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Error details have been logged.</p>";
    error_log("Demo alerts setup error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
} 