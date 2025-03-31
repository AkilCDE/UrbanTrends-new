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
    $stmt = $pdo->prepare("UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE customer_id = ?");
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $customer_id
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>