<?php
require_once 'config/Database.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Read and execute the backup_log.sql file
    $sql = file_get_contents('sql/backup_log.sql');
    $db->exec($sql);

    // Create backups directory if it doesn't exist
    $backupsDir = __DIR__ . '/backups';
    if (!file_exists($backupsDir)) {
        mkdir($backupsDir, 0755, true);
    }

    // Create .htaccess file to protect backups directory
    $htaccess = $backupsDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order deny,allow\nDeny from all");
    }

    echo "Backup system setup completed successfully!\n";
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
} 