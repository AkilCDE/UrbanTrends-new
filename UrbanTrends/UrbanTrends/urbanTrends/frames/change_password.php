<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$data = json_decode(file_get_contents('php://input'), true);
$customer_id = $_SESSION['customer_id'];

try {
    // First verify current password
    $stmt = $pdo->prepare("SELECT password FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    
    if (!password_verify($data['current_password'], $customer['password'])) {
        header('HTTP/1.1 400 Bad Request');
        exit(json_encode(['success' => false, 'message' => 'Current password is incorrect']));
    }
    
    // Update password
    $newPasswordHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE customers SET password = ? WHERE customer_id = ?");
    $stmt->execute([$newPasswordHash, $customer_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>