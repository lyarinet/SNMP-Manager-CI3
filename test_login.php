<?php
require_once __DIR__ . '/bootstrap.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

echo "<h2>Testing Login Functionality</h2>";

// Test database connection
if ($db) {
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    try {
        // Check if users table exists and has admin user
        $query = "SELECT * FROM users WHERE username = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p style='color: green;'>✓ Admin user exists!</p>";
            echo "<p>User details:<br>";
            echo "Username: " . htmlspecialchars($user['username']) . "<br>";
            echo "Email: " . htmlspecialchars($user['email']) . "<br>";
            echo "Role: " . htmlspecialchars($user['role']) . "<br>";
            echo "Password hash length: " . strlen($user['password']) . "</p>";
            
            // Test password verification
            $test_password = 'admin123';
            if (password_verify($test_password, $user['password'])) {
                echo "<p style='color: green;'>✓ Password verification successful!</p>";
            } else {
                echo "<p style='color: red;'>✗ Password verification failed!</p>";
                echo "<p>Debug info:<br>";
                echo "Stored hash: " . $user['password'] . "<br>";
                echo "New hash for 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Admin user not found!</p>";
            
            // Create admin user
            echo "<p>Creating admin user...</p>";
            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, password, email, role) 
                     VALUES ('admin', :password, 'admin@snmpmanager.local', 'admin')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $password_hash);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
                echo "<p>New password hash: " . $password_hash . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to create admin user!</p>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Database connection failed!</p>";
} 