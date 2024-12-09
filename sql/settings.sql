-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('snmp_version', '2c'),
('community_string', 'public'),
('snmp_timeout', '5'),
('snmp_retries', '3'),
('monitoring_interval', '5'),
('data_retention', '30'),
('auto_discovery', 'false'),
('alert_enabled', 'true'),
('smtp_port', '587'),
('smtp_auth', 'true'),
('smtp_secure', 'true'),
('timezone', 'UTC'),
('date_format', 'Y-m-d'),
('debug_mode', 'false'),
('maintenance_mode', 'false')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value); 