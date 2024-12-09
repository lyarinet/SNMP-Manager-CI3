<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'models/User.php';

echo "<h2>Testing Database Connection and Authentication</h2>";

// Test database connection
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test user table
    try {
        $query = "SELECT COUNT(*) as count FROM users";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✓ Users table exists! Total users: " . $row['count'] . "</p>";
        
        // Test admin user
        $query = "SELECT * FROM users WHERE username = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p style='color: green;'>✓ Admin user exists!</p>";
            
            // Test password verification
            if (password_verify('admin123', $user['password'])) {
                echo "<p style='color: green;'>✓ Password verification working!</p>";
            } else {
                echo "<p style='color: red;'>✗ Password verification failed!</p>";
                echo "<p>Debug info:<br>";
                echo "Stored hash: " . $user['password'] . "<br>";
                echo "Test hash: " . password_hash('admin123', PASSWORD_DEFAULT) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Admin user not found!</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Error testing users table: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Database connection failed!</p>";
} 