-- Get the first device ID
SET @device_id = (SELECT id FROM devices LIMIT 1);

-- CPU Usage Alerts
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) VALUES
(@device_id, 'cpu_usage', '>', 80, 'critical', 'active'),
(@device_id, 'cpu_usage', '>', 60, 'warning', 'active'),
(@device_id, 'cpu_usage', '>', 40, 'info', 'active');

-- Memory Usage Alerts
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) VALUES
(@device_id, 'memory_usage', '>', 90, 'critical', 'active'),
(@device_id, 'memory_usage', '>', 75, 'warning', 'active'),
(@device_id, 'memory_usage', '>', 50, 'info', 'active');

-- Disk Usage Alerts
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) VALUES
(@device_id, 'disk_usage', '>', 95, 'critical', 'active'),
(@device_id, 'disk_usage', '>', 85, 'warning', 'active'),
(@device_id, 'disk_usage', '>', 70, 'info', 'active');

-- Interface Status Alerts
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) VALUES
(@device_id, 'interface_status', '>', 0, 'critical', 'active');

-- Response Time Alerts
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) VALUES
(@device_id, 'response_time', '>', 1000, 'critical', 'active'),
(@device_id, 'response_time', '>', 500, 'warning', 'active'),
(@device_id, 'response_time', '>', 200, 'info', 'active');

-- Some triggered alerts for demonstration
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status, last_triggered, last_value) VALUES
(@device_id, 'cpu_usage', '>', 70, 'warning', 'triggered', NOW(), 75.5),
(@device_id, 'memory_usage', '>', 80, 'critical', 'triggered', NOW(), 92.3),
(@device_id, 'disk_usage', '>', 90, 'critical', 'triggered', DATE_SUB(NOW(), INTERVAL 1 HOUR), 96.8),
(@device_id, 'response_time', '>', 300, 'warning', 'triggered', DATE_SUB(NOW(), INTERVAL 30 MINUTE), 450);

-- Some resolved alerts
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status, last_triggered, last_value) VALUES
(@device_id, 'cpu_usage', '>', 50, 'info', 'resolved', DATE_SUB(NOW(), INTERVAL 2 HOUR), 35.2),
(@device_id, 'memory_usage', '>', 60, 'info', 'resolved', DATE_SUB(NOW(), INTERVAL 3 HOUR), 45.7),
(@device_id, 'disk_usage', '>', 75, 'warning', 'resolved', DATE_SUB(NOW(), INTERVAL 4 HOUR), 72.1);

-- Some disabled alerts
INSERT INTO alerts (device_id, metric, `condition`, threshold, severity, status) VALUES
(@device_id, 'cpu_usage', '>', 95, 'critical', 'disabled'),
(@device_id, 'memory_usage', '>', 95, 'critical', 'disabled'),
(@device_id, 'disk_usage', '>', 99, 'critical', 'disabled'); 