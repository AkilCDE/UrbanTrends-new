<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$customer_id = $_SESSION['customer_id'];

if (!$order_id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Order ID is required']));
}

try {
    // Verify order belongs to customer and is cancellable
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ? AND customer_id = ?");
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Order not found']));
    }
    
    if (!in_array($order['status'], ['Pending', 'Processing'])) {
        header('HTTP/1.1 400 Bad Request');
        exit(json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']));
    }
    
    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ?");
    $stmt->execute([$order_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>