<?php
require_once 'config/Database.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get first device ID
    $stmt = $db->query("SELECT id FROM devices LIMIT 1");
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        die("No devices found in the database. Please add a device first.");
    }

    $device_id = $device['id'];

    // Define demo alerts
    $alerts = [
        // CPU Usage Alerts
        ['cpu_usage', '>', 80, 'critical', 'active'],
        ['cpu_usage', '>', 60, 'warning', 'active'],
        ['cpu_usage', '>', 40, 'info', 'active'],

        // Memory Usage Alerts
        ['memory_usage', '>', 90, 'critical', 'active'],
        ['memory_usage', '>', 75, 'warning', 'active'],
        ['memory_usage', '>', 50, 'info', 'active'],

        // Disk Usage Alerts
        ['disk_usage', '>', 95, 'critical', 'active'],
        ['disk_usage', '>', 85, 'warning', 'active'],
        ['disk_usage', '>', 70, 'info', 'active'],

        // Interface Status Alert
        ['interface_status', '>', 0, 'critical', 'active'],

        // Response Time Alerts
        ['response_time', '>', 1000, 'critical', 'active'],
        ['response_time', '>', 500, 'warning', 'active'],
        ['response_time', '>', 200, 'info', 'active']
    ];

    // Insert active alerts
    $stmt = $db->prepare("INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) 
                         VALUES (:device_id, :metric, :condition, :threshold, :severity, :status)");

    foreach ($alerts as $alert) {
        $stmt->execute([
            ':device_id' => $device_id,
            ':metric' => $alert[0],
            ':condition' => $alert[1],
            ':threshold' => $alert[2],
            ':severity' => $alert[3],
            ':status' => $alert[4]
        ]);
    }

    // Insert triggered alerts
    $triggered_alerts = [
        ['cpu_usage', '>', 70, 'warning', 75.5],
        ['memory_usage', '>', 80, 'critical', 92.3],
        ['disk_usage', '>', 90, 'critical', 96.8],
        ['response_time', '>', 300, 'warning', 450]
    ];

    $stmt = $db->prepare("INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status, last_triggered, last_value) 
                         VALUES (:device_id, :metric, :condition, :threshold, :severity, 'triggered', NOW(), :last_value)");

    foreach ($triggered_alerts as $alert) {
        $stmt->execute([
            ':device_id' => $device_id,
            ':metric' => $alert[0],
            ':condition' => $alert[1],
            ':threshold' => $alert[2],
            ':severity' => $alert[3],
            ':last_value' => $alert[4]
        ]);
    }

    // Insert resolved alerts
    $resolved_alerts = [
        ['cpu_usage', '>', 50, 'info', 35.2],
        ['memory_usage', '>', 60, 'info', 45.7],
        ['disk_usage', '>', 75, 'warning', 72.1]
    ];

    $stmt = $db->prepare("INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status, last_triggered, last_value) 
                         VALUES (:device_id, :metric, :condition, :threshold, :severity, 'resolved', DATE_SUB(NOW(), INTERVAL 2 HOUR), :last_value)");

    foreach ($resolved_alerts as $alert) {
        $stmt->execute([
            ':device_id' => $device_id,
            ':metric' => $alert[0],
            ':condition' => $alert[1],
            ':threshold' => $alert[2],
            ':severity' => $alert[3],
            ':last_value' => $alert[4]
        ]);
    }

    // Insert disabled alerts
    $disabled_alerts = [
        ['cpu_usage', '>', 95, 'critical'],
        ['memory_usage', '>', 95, 'critical'],
        ['disk_usage', '>', 99, 'critical']
    ];

    $stmt = $db->prepare("INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) 
                         VALUES (:device_id, :metric, :condition, :threshold, :severity, 'disabled')");

    foreach ($disabled_alerts as $alert) {
        $stmt->execute([
            ':device_id' => $device_id,
            ':metric' => $alert[0],
            ':condition' => $alert[1],
            ':threshold' => $alert[2],
            ':severity' => $alert[3]
        ]);
    }

    echo "Demo alerts inserted successfully!\n";
    echo "Added:\n";
    echo "- " . count($alerts) . " active alerts\n";
    echo "- " . count($triggered_alerts) . " triggered alerts\n";
    echo "- " . count($resolved_alerts) . " resolved alerts\n";
    echo "- " . count($disabled_alerts) . " disabled alerts\n";
    echo "Total: " . (count($alerts) + count($triggered_alerts) + count($resolved_alerts) + count($disabled_alerts)) . " alerts\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 