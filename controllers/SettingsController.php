<?php

class SettingsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $settings = $this->getSettings();
        $content = 'settings';
        require_once BASE_PATH . '/views/settings.php';
    }

    public function getSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM settings");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (PDOException $e) {
            error_log("Error getting settings: " . $e->getMessage());
            return [];
        }
    }

    public function save() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        try {
            // Get the raw POST data
            $rawData = file_get_contents('php://input');
            error_log("Received settings data: " . $rawData);

            // Decode JSON data
            $data = json_decode($rawData, true);
            
            if ($data === null) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }

            // Begin transaction
            $this->db->beginTransaction();

            // Prepare the statement once
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE setting_value = :value
            ");

            // Process each setting
            foreach ($data as $key => $value) {
                // Convert boolean values to strings
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                
                // Convert arrays or objects to JSON strings
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }

                // Convert null to empty string
                if ($value === null) {
                    $value = '';
                }

                // Bind parameters and execute
                $stmt->bindValue(':key', $key);
                $stmt->bindValue(':value', (string)$value);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save setting: $key");
                }
            }

            // Commit transaction
            $this->db->commit();
            
            // Get updated settings
            $updatedSettings = $this->getSettings();
            
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Settings saved successfully',
                'settings' => $updatedSettings
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            error_log("Error saving settings: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error saving settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reset() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        try {
            // Default settings
            $defaults = [
                'snmp_version' => '2c',
                'community_string' => 'public',
                'snmp_timeout' => '5',
                'snmp_retries' => '3',
                'monitoring_interval' => '5',
                'data_retention' => '30',
                'auto_discovery' => 'false',
                'alert_enabled' => 'true',
                'smtp_port' => '587',
                'smtp_auth' => 'true',
                'smtp_secure' => 'true',
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
                'debug_mode' => 'false',
                'maintenance_mode' => 'false'
            ];

            // Begin transaction
            $this->db->beginTransaction();

            // Clear existing settings
            $this->db->exec("DELETE FROM settings");

            // Prepare statement once
            $stmt = $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");

            // Insert default settings
            foreach ($defaults as $key => $value) {
                $stmt->bindValue(':key', $key);
                $stmt->bindValue(':value', $value);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to reset setting: $key");
                }
            }

            // Commit transaction
            $this->db->commit();

            $this->jsonResponse([
                'success' => true, 
                'message' => 'Settings reset to default values',
                'settings' => $this->getSettings()
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            error_log("Error resetting settings: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error resetting settings: ' . $e->getMessage()
            ], 500);
        }
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 