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
    // Verify address belongs to customer
    $stmt = $pdo->prepare("SELECT customer_id FROM customer_addresses WHERE address_id = ?");
    $stmt->execute([$data['address_id']]);
    $address = $stmt->fetch();
    
    if (!$address || $address['customer_id'] != $customer_id) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Address not found']));
    }
    
    // If setting as default, first unset any existing default
    if ($data['is_default']) {
        $stmt = $pdo->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
    }
    
    // Update address
    $stmt = $pdo->prepare("
        UPDATE customer_addresses SET
        address_name = ?,
        street_address = ?,
        city = ?,
        state = ?,
        zip_code = ?,
        country = ?,
        phone = ?,
        is_default = ?
        WHERE address_id = ?
    ");
    $stmt->execute([
        $data['address_name'],
        $data['street_address'],
        $data['city'],
        $data['state'],
        $data['zip_code'],
        $data['country'],
        $data['phone'],
        $data['is_default'] ? 1 : 0,
        $data['address_id']
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>