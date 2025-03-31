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
    // If setting as default, first unset any existing default
    if ($data['is_default']) {
        $stmt = $pdo->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
    }
    
    // Insert new address
    $stmt = $pdo->prepare("
        INSERT INTO customer_addresses 
        (customer_id, address_name, street_address, city, state, zip_code, country, phone, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $customer_id,
        $data['address_name'],
        $data['street_address'],
        $data['city'],
        $data['state'],
        $data['zip_code'],
        $data['country'],
        $data['phone'],
        $data['is_default'] ? 1 : 0
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>