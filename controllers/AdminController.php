<?php
require_once 'models/User.php';

class AdminController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    // Handle user login
    public function login($username, $password) {
        if ($this->user->login($username, $password)) {
            $_SESSION['user_id'] = $this->user->id;
            $_SESSION['username'] = $this->user->username;
            $_SESSION['role'] = $this->user->role;
            return true;
        }
        return false;
    }

    // Handle user logout
    public function logout() {
        session_destroy();
    }

    // Get dashboard statistics
    public function getDashboardStats($range = '24h') {
        $metrics = [
            'total_devices' => 0,
            'active_devices' => 0,
            'inactive_devices' => 0,
            'error_devices' => 0,
            'total_alerts' => 0,
            'critical_issues' => 0,
            'unread_alerts' => 0
        ];

        try {
            // Get device counts
            $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM devices GROUP BY status");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                switch ($row['status']) {
                    case 'active':
                        $metrics['active_devices'] = intval($row['count']);
                        break;
                    case 'inactive':
                        $metrics['inactive_devices'] = intval($row['count']);
                        break;
                    case 'error':
                        $metrics['error_devices'] = intval($row['count']);
                        break;
                }
            }
            $metrics['total_devices'] = $metrics['active_devices'] + $metrics['inactive_devices'] + $metrics['error_devices'];

            // Get alert counts
            $stmt = $this->db->query("SELECT severity, COUNT(*) as count FROM alerts WHERE status = 'active' GROUP BY severity");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['severity'] === 'critical') {
                    $metrics['critical_issues'] = intval($row['count']);
                }
                $metrics['unread_alerts'] += intval($row['count']);
            }

            // Get total alerts
            $stmt = $this->db->query("SELECT COUNT(*) FROM alerts");
            $metrics['total_alerts'] = intval($stmt->fetchColumn());

            // Get chart data
            $chart_data = $this->getChartData($range);

            return [
                'metrics' => $metrics,
                'chart_data' => $chart_data
            ];

        } catch (PDOException $e) {
            error_log("Error getting dashboard stats: " . $e->getMessage());
            return [
                'metrics' => $metrics,
                'chart_data' => [
                    'labels' => [],
                    'cpu' => [],
                    'memory' => []
                ]
            ];
        }
    }

    // Get chart data for dashboard
    private function getChartData($range = '24h') {
        $chart_data = [
            'labels' => [],
            'cpu' => [],
            'memory' => []
        ];

        try {
            // Get time range
            switch ($range) {
                case '1h':
                    $interval = '1 HOUR';
                    $group_by = '%Y-%m-%d %H:%i';
                    $format = 'H:i';
                    break;
                case '7d':
                    $interval = '7 DAY';
                    $group_by = '%Y-%m-%d';
                    $format = 'Y-m-d';
                    break;
                case '24h':
                default:
                    $interval = '24 HOUR';
                    $group_by = '%Y-%m-%d %H:00';
                    $format = 'H:00';
                    break;
            }

            // Get actual metrics data from database
            $query = "
                SELECT 
                    DATE_FORMAT(timestamp, ?) as time_label,
                    AVG(CASE WHEN metric_type = 'cpu_usage' THEN value ELSE NULL END) as cpu_avg,
                    AVG(CASE WHEN metric_type = 'memory_usage' THEN value ELSE NULL END) as memory_avg
                FROM metrics 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL {$interval})
                GROUP BY time_label 
                ORDER BY timestamp ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$group_by]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($results)) {
                foreach ($results as $row) {
                    $chart_data['labels'][] = date($format, strtotime($row['time_label']));
                    $chart_data['cpu'][] = round(floatval($row['cpu_avg']), 2);
                    $chart_data['memory'][] = round(floatval($row['memory_avg']), 2);
                }
            } else {
                // If no data, generate sample data
                $points = $range === '1h' ? 60 : ($range === '7d' ? 7 : 24);
                for ($i = $points; $i >= 0; $i--) {
                    $chart_data['labels'][] = date($format, strtotime("-$i " . ($range === '1h' ? 'minutes' : ($range === '7d' ? 'days' : 'hours'))));
                    $chart_data['cpu'][] = rand(20, 80);
                    $chart_data['memory'][] = rand(30, 90);
                }
            }

            return $chart_data;

        } catch (PDOException $e) {
            error_log("Error getting chart data: " . $e->getMessage());
            return $chart_data;
        }
    }

    // Get recent alerts
    public function getRecentAlerts($limit = 5) {
        try {
            $query = "
                SELECT 
                    a.*,
                    d.ip_address as device_name,
                    CASE 
                        WHEN a.metric = 'cpu_usage' THEN 'CPU Usage'
                        WHEN a.metric = 'memory_usage' THEN 'Memory Usage'
                        WHEN a.metric = 'disk_usage' THEN 'Disk Usage'
                        WHEN a.metric = 'interface_status' THEN 'Interface Status'
                        WHEN a.metric = 'response_time' THEN 'Response Time'
                        ELSE a.metric
                    END as alert_type,
                    CASE 
                        WHEN a.metric = 'cpu_usage' THEN CONCAT('CPU Usage is ', a.last_value, '%')
                        WHEN a.metric = 'memory_usage' THEN CONCAT('Memory Usage is ', a.last_value, '%')
                        WHEN a.metric = 'disk_usage' THEN CONCAT('Disk Usage is ', a.last_value, '%')
                        WHEN a.metric = 'interface_status' THEN 'Interface is Down'
                        WHEN a.metric = 'response_time' THEN CONCAT('Response Time is ', a.last_value, 'ms')
                        ELSE CONCAT(a.metric, ' is ', a.last_value)
                    END as message
                FROM alerts a 
                LEFT JOIN devices d ON a.device_id = d.id 
                WHERE a.status = 'triggered'
                ORDER BY a.last_triggered DESC 
                LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no triggered alerts, get the most recent alerts regardless of status
            if (empty($alerts)) {
                $query = "
                    SELECT 
                        a.*,
                        d.ip_address as device_name,
                        CASE 
                            WHEN a.metric = 'cpu_usage' THEN 'CPU Usage'
                            WHEN a.metric = 'memory_usage' THEN 'Memory Usage'
                            WHEN a.metric = 'disk_usage' THEN 'Disk Usage'
                            WHEN a.metric = 'interface_status' THEN 'Interface Status'
                            WHEN a.metric = 'response_time' THEN 'Response Time'
                            ELSE a.metric
                        END as alert_type,
                        CASE 
                            WHEN a.metric = 'cpu_usage' THEN CONCAT('CPU Usage is ', a.last_value, '%')
                            WHEN a.metric = 'memory_usage' THEN CONCAT('Memory Usage is ', a.last_value, '%')
                            WHEN a.metric = 'disk_usage' THEN CONCAT('Disk Usage is ', a.last_value, '%')
                            WHEN a.metric = 'interface_status' THEN 'Interface is Down'
                            WHEN a.metric = 'response_time' THEN CONCAT('Response Time is ', a.last_value, 'ms')
                            ELSE CONCAT(a.metric, ' is ', a.last_value)
                        END as message
                    FROM alerts a 
                    LEFT JOIN devices d ON a.device_id = d.id 
                    ORDER BY a.created_at DESC 
                    LIMIT :limit";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                
                $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Format the alerts
            foreach ($alerts as &$alert) {
                // Format device name
                $alert['device_name'] = $alert['device_name'] ?? 'Unknown Device';
                
                // Format severity
                if (!isset($alert['severity'])) {
                    $alert['severity'] = 'info';
                }
                
                // Format timestamp
                if (isset($alert['last_triggered'])) {
                    $alert['created_at'] = $alert['last_triggered'];
                }
                
                // Ensure message is not empty
                if (empty($alert['message'])) {
                    $alert['message'] = 'Alert triggered for ' . $alert['alert_type'];
                }
            }
            
            return $alerts;

        } catch (PDOException $e) {
            error_log("Error getting recent alerts: " . $e->getMessage());
            return [];
        }
    }

    // Acknowledge alert
    public function acknowledgeAlert($alert_id) {
        try {
            $query = "UPDATE alerts SET status = 'acknowledged' WHERE id = :alert_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':alert_id', $alert_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error acknowledging alert: " . $e->getMessage());
            return false;
        }
    }

    // Save settings
    public function saveSettings($settings) {
        try {
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO settings (setting_key, setting_value) 
                         VALUES (:key, :value) 
                         ON DUPLICATE KEY UPDATE setting_value = :value";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':key', $key);
                $stmt->bindParam(':value', $value);
                $stmt->execute();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error saving settings: " . $e->getMessage());
            return false;
        }
    }
} 