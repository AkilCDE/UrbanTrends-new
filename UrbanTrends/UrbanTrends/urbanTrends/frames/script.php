<?php
require 'db.php';

$email = 'admin@trendswear.com';
$password = 'admin1234';
$firstName = 'Admin';
$lastName = 'User';

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT email FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        die("Admin user already exists");
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $pdo->prepare("
        INSERT INTO customers (first_name, last_name, email, password, role, is_admin)
        VALUES (?, ?, ?, ?, 'admin', TRUE)
    ");
    $stmt->execute([$firstName, $lastName, $email, $hashedPassword]);
    
    echo "Admin user created successfully!";
} catch (PDOException $e) {
    die("Error creating admin user: " . $e->getMessage());
}
?>