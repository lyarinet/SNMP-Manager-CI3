<?php
require_once 'config/Database.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // SQL to create alerts table
    $sql = "CREATE TABLE IF NOT EXISTS alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        metric VARCHAR(50) NOT NULL,
        `condition` VARCHAR(10) NOT NULL,
        threshold FLOAT NOT NULL,
        severity VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        last_triggered DATETIME NULL,
        last_value FLOAT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // Execute the SQL
    $db->exec($sql);
    echo "Alerts table created successfully\n";

    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_alerts_device ON alerts(device_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_alerts_status ON alerts(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_alerts_severity ON alerts(severity)");
    echo "Indexes created successfully\n";

    echo "Setup completed successfully!";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 