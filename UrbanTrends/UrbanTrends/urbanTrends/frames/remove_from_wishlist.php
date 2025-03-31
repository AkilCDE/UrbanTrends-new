<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$customer_id = $_SESSION['customer_id'];

if (!$product_id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Product ID is required']));
}

try {
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE customer_id = ? AND product_id = ?");
    $stmt->execute([$customer_id, $product_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Item not found in wishlist']);
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>