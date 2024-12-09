<?php
class Device {
    private $conn;
    private $table_name = "devices";

    // Device properties
    public $id;
    public $ip_address;
    public $community_string;
    public $snmp_version;
    public $description;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (ip_address, community_string, snmp_version, description, status)
                    VALUES
                    (:ip_address, :community_string, :snmp_version, :description, :status)";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
            }

            // Sanitize and bind values
            $this->ip_address = htmlspecialchars(strip_tags($this->ip_address));
            $this->community_string = htmlspecialchars(strip_tags($this->community_string));
            $this->snmp_version = htmlspecialchars(strip_tags($this->snmp_version));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->status = htmlspecialchars(strip_tags($this->status));

            // Bind parameters
            $stmt->bindParam(":ip_address", $this->ip_address);
            $stmt->bindParam(":community_string", $this->community_string);
            $stmt->bindParam(":snmp_version", $this->snmp_version);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":status", $this->status);

            // Execute query
            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return true;
        } catch (PDOException $e) {
            error_log("Database error in Device::create: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error in Device::create: " . $e->getMessage());
            throw $e;
        }
    }

    public function read() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
            }

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in Device::read: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function read_single($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
            }

            $stmt->bindParam(":id", $id);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in Device::read_single: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET ip_address = :ip_address,
                        community_string = :community_string,
                        snmp_version = :snmp_version,
                        description = :description,
                        status = :status
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
            }

            // Sanitize and bind values
            $this->ip_address = htmlspecialchars(strip_tags($this->ip_address));
            $this->community_string = htmlspecialchars(strip_tags($this->community_string));
            $this->snmp_version = htmlspecialchars(strip_tags($this->snmp_version));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->id = htmlspecialchars(strip_tags($this->id));

            // Bind parameters
            $stmt->bindParam(":ip_address", $this->ip_address);
            $stmt->bindParam(":community_string", $this->community_string);
            $stmt->bindParam(":snmp_version", $this->snmp_version);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":id", $this->id);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return true;
        } catch (PDOException $e) {
            error_log("Database error in Device::update: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
            }

            $stmt->bindParam(":id", $id);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return true;
        } catch (PDOException $e) {
            error_log("Database error in Device::delete: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function update_status($id, $status) {
        try {
            $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
            }

            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $id);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return true;
        } catch (PDOException $e) {
            error_log("Database error in Device::update_status: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function check_ip_exists($ip, $exclude_id = null) {
        try {
            if ($exclude_id) {
                $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE ip_address = :ip AND id != :id";
                $stmt = $this->conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
                }

                $stmt->bindParam(":ip", $ip);
                $stmt->bindParam(":id", $exclude_id);
            } else {
                $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE ip_address = :ip";
                $stmt = $this->conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Error preparing statement: " . print_r($this->conn->errorInfo(), true));
                }

                $stmt->bindParam(":ip", $ip);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Database error in Device::check_ip_exists: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
} 