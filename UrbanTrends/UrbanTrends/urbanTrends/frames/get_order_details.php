<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$order_id = $_GET['order_id'] ?? null;
$customer_id = $_SESSION['customer_id'];

if (!$order_id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Order ID is required']));
}

try {
    // Get order info
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_id = ?");
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Order not found']));
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.price 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>