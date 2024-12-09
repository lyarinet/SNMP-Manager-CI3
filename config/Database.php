<?php
class Database {
    private $host = "localhost";
    private $db_name = "snmp_manager";
    private $username = "snmp_manager";
    private $password = "snmp_manager";
    private $conn;
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->getConnection();
    }

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create devices table if it doesn't exist
            $this->createDevicesTable();
            
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function createDevicesTable() {
        $query = "CREATE TABLE IF NOT EXISTS devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            community_string VARCHAR(255) NOT NULL,
            snmp_version VARCHAR(5) NOT NULL,
            description TEXT,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_ip (ip_address)
        )";

        try {
            $this->conn->exec($query);
        } catch(PDOException $e) {
            error_log("Error creating devices table: " . $e->getMessage());
            throw new Exception("Error creating devices table: " . $e->getMessage());
        }
    }
} 