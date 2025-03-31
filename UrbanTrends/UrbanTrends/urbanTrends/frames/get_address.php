<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$address_id = $_GET['address_id'] ?? null;
$customer_id = $_SESSION['customer_id'];

if (!$address_id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Address ID is required']));
}

try {
    $stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE address_id = ? AND customer_id = ?");
    $stmt->execute([$address_id, $customer_id]);
    $address = $stmt->fetch();
    
    if (!$address) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Address not found']));
    }
    
    echo json_encode(['success' => true, 'address' => $address]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>