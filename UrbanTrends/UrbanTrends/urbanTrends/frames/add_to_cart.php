<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? 1;
$customer_id = $_SESSION['customer_id'];

if (!$product_id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Product ID is required']));
}

try {
    // Check if product exists
    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Product not found']));
    }
    
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT cart_id, quantity FROM cart WHERE customer_id = ? AND product_id = ?");
    $stmt->execute([$customer_id, $product_id]);
    $cartItem = $stmt->fetch();
    
    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
        $stmt->execute([$newQuantity, $cartItem['cart_id']]);
    } else {
        // Add new item to cart
        $stmt = $pdo->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$customer_id, $product_id, $quantity]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>