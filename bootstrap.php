<?php
// Define the application root directory
define('BASE_PATH', __DIR__);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/error.log');

// Start session
session_start();

// Function to autoload classes
spl_autoload_register(function ($class_name) {
    // List of directories to search for classes
    $directories = [
        'models',
        'controllers',
        'config'
    ];

    // Loop through directories
    foreach ($directories as $directory) {
        $file = BASE_PATH . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Function to handle fatal errors
function handleFatalError() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        error_log("Fatal Error: " . print_r($error, true));
        if (!headers_sent()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Internal Server Error'
                ]);
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                echo "<h1>500 Internal Server Error</h1>";
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === 1) {
                    echo "<pre>" . htmlspecialchars($error['message']) . "</pre>";
                }
            }
        }
    }
}

// Register error handlers
register_shutdown_function('handleFatalError');
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    error_log("Error ($errno): $errstr in $errfile on line $errline");
    return true;
});

// Set default timezone
date_default_timezone_set('UTC');

// Load required files
require_once BASE_PATH . '/config/Database.php';

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    die("Database connection failed. Please check the error log for details.");
} 