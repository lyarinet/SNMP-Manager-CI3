-- Create alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    metric VARCHAR(50) NOT NULL,
    `condition` ENUM('>', '<', '=', '>=', '<=', '!=') NOT NULL,
    threshold DECIMAL(10,2) NOT NULL,
    last_value DECIMAL(10,2) NULL,
    message TEXT,
    severity ENUM('critical', 'warning', 'info') NOT NULL,
    status ENUM('active', 'triggered', 'resolved', 'disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_triggered TIMESTAMP NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create index for faster lookups
CREATE INDEX idx_alerts_device ON alerts(device_id);
CREATE INDEX idx_alerts_status ON alerts(status);
CREATE INDEX idx_alerts_severity ON alerts(severity); 