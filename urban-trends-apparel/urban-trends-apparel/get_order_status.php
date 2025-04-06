<?php
require_once 'db_config.php'; // Your database configuration file

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get current status
$stmt = $db->prepare("
    SELECT status FROM orders 
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$response = ['status' => $order['status']];

// Check if there's a new status update
$stmt = $db->prepare("
    SELECT status, notes FROM order_status_history 
    WHERE order_id = ? 
    ORDER BY changed_at DESC 
    LIMIT 1
");
$stmt->execute([$order_id]);
$latestUpdate = $stmt->fetch(PDO::FETCH_ASSOC);

if ($latestUpdate && $latestUpdate['status'] !== $order['status']) {
    $response['newStatus'] = $latestUpdate;
}

echo json_encode($response);
?>