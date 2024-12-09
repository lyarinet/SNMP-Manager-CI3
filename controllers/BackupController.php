<?php

class BackupController {
    private $db;
    private $backupDir;

    public function __construct($db) {
        $this->db = $db;
        $this->backupDir = dirname(__DIR__) . '/backups';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Get list of backups
        $backups = $this->getBackupsList();
        
        // Set template variables
        $content = 'backups';
        
        // Include the layout
        require_once dirname(__DIR__) . '/views/layout.php';
    }

    public function createDatabaseBackup() {
        try {
            // Get database configuration
            $config = require BASE_PATH . '/config/database.php';
            
            // Create backup filename
            $filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            
            // Build mysqldump command
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s',
                escapeshellarg($config['host']),
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );
            
            // Execute backup command
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                throw new Exception('Database backup failed');
            }

            // Log backup creation
            $this->logBackup('database', $filename);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Database backup created successfully',
                'filename' => $filename
            ]);
        } catch (Exception $e) {
            error_log("Backup error: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error creating database backup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createFilesBackup() {
        try {
            // Create backup filename
            $filename = 'files_backup_' . date('Y-m-d_H-i-s') . '.zip';
            $filepath = $this->backupDir . '/' . $filename;
            
            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Cannot create ZIP file");
            }

            // Add files to ZIP
            $this->addDirToZip(BASE_PATH, $zip, BASE_PATH);
            
            // Close ZIP file
            $zip->close();

            // Log backup creation
            $this->logBackup('files', $filename);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Files backup created successfully',
                'filename' => $filename
            ]);
        } catch (Exception $e) {
            error_log("Backup error: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error creating files backup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($filename) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $filepath = $this->backupDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            header('HTTP/1.0 404 Not Found');
            echo 'Backup file not found';
            exit;
        }

        // Get file extension
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        
        // Set appropriate headers
        header('Content-Type: application/' . ($ext === 'sql' ? 'sql' : 'zip'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        
        // Output file
        readfile($filepath);
        exit;
    }

    public function delete($filename) {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        try {
            $filepath = $this->backupDir . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception('Backup file not found');
            }

            if (!unlink($filepath)) {
                throw new Exception('Failed to delete backup file');
            }

            // Delete from backup log
            $stmt = $this->db->prepare("DELETE FROM backup_log WHERE filename = ?");
            $stmt->execute([$filename]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
        } catch (Exception $e) {
            error_log("Delete backup error: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error deleting backup: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getBackupsList() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM backup_log 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting backups list: " . $e->getMessage());
            return [];
        }
    }

    private function logBackup($type, $filename) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO backup_log (type, filename, created_by)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$type, $filename, $_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Error logging backup: " . $e->getMessage());
        }
    }

    private function addDirToZip($baseDir, $zip, $exclusiveLength) {
        $handle = opendir($baseDir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry == "." || $entry == ".." || $entry == "backups") {
                continue;
            }
            
            $filePath = $baseDir . "/" . $entry;
            $localPath = substr($filePath, $exclusiveLength);
            
            if (is_file($filePath)) {
                $zip->addFile($filePath, $localPath);
            } elseif (is_dir($filePath)) {
                $zip->addEmptyDir($localPath);
                $this->addDirToZip($filePath, $zip, $exclusiveLength);
            }
        }
        closedir($handle);
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 