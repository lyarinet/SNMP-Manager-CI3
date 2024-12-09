<?php
require_once __DIR__ . '/bootstrap.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create a new password hash
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Resetting Admin Password</h2>";

try {
    // Update admin user with new password hash
    $query = "UPDATE users SET password = :password WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hash);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Password reset successful!</p>";
        echo "<p>New login credentials:<br>";
        echo "Username: admin<br>";
        echo "Password: admin123</p>";
        
        // Verify the new password
        $query = "SELECT password FROM users WHERE username = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>✓ Password verification successful!</p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification failed!</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to reset password!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
} 