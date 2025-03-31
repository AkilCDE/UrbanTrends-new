<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$data = json_decode(file_get_contents('php://input'), true);
$address_id = $data['address_id'] ?? null;
$customer_id = $_SESSION['customer_id'];

if (!$address_id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Address ID is required']));
}

try {
    // Verify address belongs to customer
    $stmt = $pdo->prepare("SELECT customer_id FROM customer_addresses WHERE address_id = ?");
    $stmt->execute([$address_id]);
    $address = $stmt->fetch();
    
    if (!$address || $address['customer_id'] != $customer_id) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Address not found']));
    }
    
    // Delete address
    $stmt = $pdo->prepare("DELETE FROM customer_addresses WHERE address_id = ?");
    $stmt->execute([$address_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>