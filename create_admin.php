<?php
// create_admin.php - Run this once to create the admin user
require_once 'db.php';

$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123';
$firstName = 'Admin';
$lastName = 'User';

try {
    // Delete existing admin user if exists
    $pdo->prepare("DELETE FROM users WHERE username = ? OR email = ?")->execute([$username, $email]);
    
    // Hash the password properly
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, firstName, lastName, isAdmin) VALUES (?, ?, ?, ?, ?, TRUE)");
    $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName]);
    
    echo "Admin user created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "You can now delete this file for security.";
    
} catch (PDOException $e) {
    echo "Error creating admin user: " . $e->getMessage();
}
?>