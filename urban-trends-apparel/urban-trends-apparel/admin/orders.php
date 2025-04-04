<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'urban_trends');

// Create database connection
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

/**
 * Order & Delivery Management Class
 */
class OrderDelivery {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Update order status and notify customer
     */
    public function updateOrderStatus($order_id, $status) {
        try {
            // Update order status
            $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            
            // Record status change in history
            $this->recordStatusHistory($order_id, $status);
            
            // Update shipping status if needed
            if ($status == 'shipped' || $status == 'delivered') {
                $shipping_status = $status == 'shipped' ? 'shipped' : 'delivered';
                $this->updateShippingStatus($order_id, $shipping_status);
            }
            
            // Get order details for notification
            $stmt = $this->db->prepare("SELECT u.email, u.firstname, o.order_number 
                                      FROM orders o 
                                      JOIN users u ON o.user_id = u.id 
                                      WHERE o.id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                $this->sendStatusNotification($order['email'], $order['firstname'], $order['order_number'], $status);
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Error updating order status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update shipping status
     */
    private function updateShippingStatus($order_id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE shipping SET status = ?, updated_at = NOW() WHERE order_id = ?");
            $stmt->execute([$status, $order_id]);
        } catch(PDOException $e) {
            error_log("Error updating shipping status: " . $e->getMessage());
        }
    }
    
    /**
     * Record status change in history
     */
    private function recordStatusHistory($order_id, $status) {
        try {
            $stmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status) VALUES (?, ?)");
            $stmt->execute([$order_id, $status]);
        } catch(PDOException $e) {
            error_log("Error recording status history: " . $e->getMessage());
        }
    }
    
    /**
     * Send email notification for order status update
     */
    private function sendStatusNotification($email, $name, $order_number, $status) {
        $subject = "Order #$order_number Status Update";
        $status_text = ucwords(str_replace('_', ' ', $status));
        
        $message = "
        <html>
        <head>
            <title>Order Status Update</title>
        </head>
        <body>
            <h2>Hello $name,</h2>
            <p>Your order #$order_number status has been updated to: <strong>$status_text</strong></p>
            
            <p>Here's what to expect next:</p>";
            
        switch($status) {
            case 'processing':
                $message .= "<p>Our team is preparing your order for shipment. You'll receive another notification when it's on its way.</p>";
                break;
            case 'shipped':
                $message .= "<p>Your order has been shipped! It should arrive within the estimated delivery time.</p>";
                break;
            case 'delivered':
                $message .= "<p>Your order has been delivered! We hope you're happy with your purchase.</p>";
                break;
            default:
                $message .= "<p>Thank you for shopping with us!</p>";
        }
        
        $message .= "
            <p>If you have any questions, please contact our support team.</p>
            <p>Best regards,<br>Urban Trends Team</p>
        </body>
        </html>";
        
        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Urban Trends <no-reply@urbantrends.com>" . "\r\n";
        
        // Send email
        mail($email, $subject, $message, $headers);
    }
    
    /**
     * Schedule delivery for an order
     */
    public function scheduleDelivery($order_id, $delivery_data) {
        try {
            // Check if shipping record exists
            $stmt = $this->db->prepare("SELECT id FROM shipping WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $shipping_id = $stmt->fetchColumn();
            
            if ($shipping_id) {
                // Update existing shipping record
                $stmt = $this->db->prepare("UPDATE shipping SET 
                                           pickup_location = ?,
                                           estimated_delivery = ?,
                                           carrier = ?,
                                           tracking_number = ?,
                                           shipping_method = ?,
                                           status = 'processing',
                                           updated_at = NOW()
                                           WHERE order_id = ?");
                $stmt->execute([
                    $delivery_data['pickup_location'],
                    $delivery_data['delivery_date'],
                    $delivery_data['carrier'],
                    $delivery_data['tracking_number'],
                    $delivery_data['is_pickup'] ? 'Pickup' : 'Delivery',
                    $order_id
                ]);
            } else {
                // Create new shipping record
                $stmt = $this->db->prepare("INSERT INTO shipping 
                                           (order_id, pickup_location, estimated_delivery, carrier, tracking_number, shipping_method, status)
                                           VALUES (?, ?, ?, ?, ?, ?, 'processing')");
                $stmt->execute([
                    $order_id,
                    $delivery_data['pickup_location'],
                    $delivery_data['delivery_date'],
                    $delivery_data['carrier'],
                    $delivery_data['tracking_number'],
                    $delivery_data['is_pickup'] ? 'Pickup' : 'Delivery'
                ]);
            }
            
            // If pickup, send pickup instructions
            if ($delivery_data['is_pickup']) {
                $this->sendPickupInstructions($order_id);
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Error scheduling delivery: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send pickup instructions to customer
     */
    private function sendPickupInstructions($order_id) {
        $stmt = $this->db->prepare("SELECT u.email, u.firstname, o.order_number, s.pickup_location
                                  FROM orders o 
                                  JOIN users u ON o.user_id = u.id
                                  JOIN shipping s ON o.id = s.order_id
                                  WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $subject = "Order #{$order['order_number']} Ready for Pickup";
            
            $message = "
            <html>
            <head>
                <title>Pickup Instructions</title>
            </head>
            <body>
                <h2>Hello {$order['firstname']},</h2>
                <p>Your order #{$order['order_number']} is ready for pickup at our store!</p>
                
                <h3>Pickup Information:</h3>
                <p><strong>Location:</strong> {$order['pickup_location']}</p>
                <p><strong>Pickup Hours:</strong> Monday-Friday, 9AM-6PM</p>
                
                <p>Please bring your order confirmation email and a valid ID when picking up your order.</p>
                
                <p>If you have any questions, please contact our support team.</p>
                <p>Best regards,<br>Urban Trends Team</p>
            </body>
            </html>";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Urban Trends <no-reply@urbantrends.com>" . "\r\n";
            
            mail($order['email'], $subject, $message, $headers);
        }
    }
    
    /**
     * Get order status history
     */
    public function getOrderStatusHistory($order_id) {
        try {
            $stmt = $this->db->prepare("SELECT status, changed_at as updated_at, notes
                                      FROM order_status_history 
                                      WHERE order_id = ? 
                                      ORDER BY changed_at DESC");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error getting order status history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all orders with filters
     */
    public function getAllOrders($status = null, $date_from = null, $date_to = null) {
        try {
            $query = "SELECT o.*, u.email, u.firstname, u.lastname, 
                     s.pickup_location, s.estimated_delivery, s.carrier, s.tracking_number
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id
                     LEFT JOIN shipping s ON o.id = s.order_id";
            
            $conditions = [];
            $params = [];
            
            if ($status) {
                $conditions[] = "o.status = ?";
                $params[] = $status;
            }
            
            if ($date_from) {
                $conditions[] = "o.order_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $conditions[] = "o.order_date <= ?";
                $params[] = $date_to . ' 23:59:59';
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY o.order_date DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error getting orders: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get order details with items
     */
    public function getOrderDetails($order_id) {
        try {
            // Get order info
            $stmt = $this->db->prepare("SELECT o.*, u.email, u.firstname, u.lastname, u.address, u.phone,
                                      s.tracking_number, s.carrier, s.shipping_method, s.estimated_delivery, 
                                      s.actual_delivery, s.pickup_location, s.status as shipping_status
                                      FROM orders o 
                                      JOIN users u ON o.user_id = u.id
                                      LEFT JOIN shipping s ON o.id = s.order_id
                                      WHERE o.id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return null;
            }
            
            // Set default values
            $order['is_pickup'] = !empty($order['pickup_location']) ? 1 : 0;
            $order['delivery_date'] = $order['estimated_delivery'] ?? null;
            $order['delivery_time'] = null;
            
            // Get order items
            $stmt = $this->db->prepare("SELECT oi.*, p.name, p.image, p.price as unit_price
                                      FROM order_items oi
                                      JOIN products p ON oi.product_id = p.id
                                      WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get status history
            $order['status_history'] = $this->getOrderStatusHistory($order_id);
            
            return $order;
        } catch(PDOException $e) {
            error_log("Error getting order details: " . $e->getMessage());
            return null;
        }
    }
}

// Initialize OrderDelivery
$orderDelivery = new OrderDelivery($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update order status
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        if ($orderDelivery->updateOrderStatus($order_id, $status)) {
            $_SESSION['success_message'] = "Order status updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update order status.";
        }
        
        header("Location: orders.php" . (isset($_GET['id']) ? '?id='.$_GET['id'] : ''));
        exit;
    }
    
    // Schedule delivery
    if (isset($_POST['schedule_delivery'])) {
        $order_id = $_POST['order_id'];
        $is_pickup = isset($_POST['is_pickup']) ? 1 : 0;
        
        $delivery_data = [
            'is_pickup' => $is_pickup,
            'pickup_location' => $is_pickup ? ($_POST['pickup_location'] ?? '') : null,
            'delivery_date' => !$is_pickup ? ($_POST['delivery_date'] ?? null) : null,
            'carrier' => !$is_pickup ? ($_POST['carrier'] ?? null) : null,
            'tracking_number' => !$is_pickup ? ($_POST['tracking_number'] ?? null) : null
        ];
        
        if ($orderDelivery->scheduleDelivery($order_id, $delivery_data)) {
            $_SESSION['success_message'] = "Delivery scheduled successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to schedule delivery.";
        }
        
        header("Location: orders.php?id=$order_id");
        exit;
    }
}

// Get order details if viewing single order
$order = null;
if (isset($_GET['id'])) {
    $order = $orderDelivery->getOrderDetails($_GET['id']);
    if (!$order) {
        $_SESSION['error_message'] = "Order not found.";
        header("Location: orders.php");
        exit;
    }
}

// Get all orders with filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

$orders = $orderDelivery->getAllOrders($status_filter, $date_from, $date_to);

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Trends Apparel - Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --success-color: #4cc9f0;
            --dark-color: #2b2d42;
            --light-color: #f8f9fa;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            z-index: 100;
        }
        
        .admin-sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .admin-sidebar-header h2 {
            display: flex;
            align-items: center;
            font-size: 1.2rem;
        }
        
        .admin-sidebar-header h2 i {
            margin-right: 10px;
            color: var(--accent-color);
        }
        
        .admin-sidebar ul {
            list-style: none;
        }
        
        .admin-sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .admin-sidebar ul li a:hover, 
        .admin-sidebar ul li a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--accent-color);
        }
        
        .admin-sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .admin-header h2 {
            color: var(--dark-color);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .admin-header h2 i {
            margin-right: 10px;
        }
        
        .admin-actions a {
            color: var(--dark-color);
            text-decoration: none;
            margin-left: 15px;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }
        
        .admin-actions a:hover {
            color: var(--danger-color);
        }
        
        .admin-actions a i {
            margin-right: 5px;
        }
        
        /* Tables */
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            color: #555;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status.pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status.processing {
            background-color: #CCE5FF;
            color: #004085;
        }
        
        .status.shipped {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status.delivered {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status.cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        /* Buttons */
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d63384;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #3aa8d1;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        /* Order Details */
        .order-details {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-header h3 {
            color: var(--dark-color);
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .order-meta-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
        }
        
        .order-meta-item h4 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .order-meta-item p {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .order-items {
            margin-top: 20px;
        }
        
        .order-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 4px;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .order-item-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-item-price {
            font-weight: 600;
            color: var(--dark-color);
            text-align: right;
            min-width: 100px;
        }
        
        .order-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .order-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .order-summary-row.total {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark-color);
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        /* Status History */
        .status-history {
            margin-top: 30px;
        }
        
        .status-history h4 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .status-timeline {
            position: relative;
            padding-left: 20px;
            border-left: 2px solid #eee;
        }
        
        .status-event {
            position: relative;
            padding-bottom: 20px;
            padding-left: 20px;
        }
        
        .status-event:last-child {
            padding-bottom: 0;
        }
        
        .status-event::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .status-event-date {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .status-event-status {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        /* Filters */
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Delivery Scheduling */
        .delivery-schedule {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-top: 20px;
        }
        
        .delivery-schedule h4 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .admin-sidebar-header h2 span,
            .admin-sidebar ul li a span {
                display: none;
            }
            
            .admin-sidebar ul li a {
                justify-content: center;
                padding: 12px 0;
            }
            
            .admin-sidebar ul li a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .admin-main {
                margin-left: 70px;
            }
            
            .order-meta {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: static;
                display: flex;
                flex-direction: column;
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-sidebar-header {
                display: none;
            }
            
            .admin-sidebar ul {
                display: flex;
                overflow-x: auto;
            }
            
            .admin-sidebar ul li {
                flex: 0 0 auto;
            }
            
            .admin-sidebar ul li a {
                padding: 10px 15px;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .admin-sidebar ul li a:hover, 
            .admin-sidebar ul li a.active {
                border-left: none;
                border-bottom: 3px solid var(--accent-color);
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2><i class="fas fa-crown"></i> <span>Admin Panel</span></h2>
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="products.php"><i class="fas fa-tshirt"></i> <span>Products</span></a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-header">
            <h2><i class="fas fa-shopping-bag"></i> <?php echo isset($order) ? "Order #" . $order['id'] : "Order Management"; ?></h2>
            <div class="admin-actions">
                <a href="../index.php"><i class="fas fa-home"></i> View Site</a>
                <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($order)): ?>
            <!-- Single Order View -->
            <div class="order-details">
                <div class="order-header">
                    <h3>Order Details</h3>
                    <div>
                        <span class="status <?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-meta">
                    <div class="order-meta-item">
                        <h4>Customer</h4>
                        <p><?php echo $order['firstname'] . ' ' . $order['lastname']; ?></p>
                        <p><?php echo $order['email']; ?></p>
                        <p><?php echo $order['phone']; ?></p>
                    </div>
                    
                    <div class="order-meta-item">
                        <h4>Shipping Address</h4>
                        <p><?php echo nl2br($order['address']); ?></p>
                    </div>
                    
                    <div class="order-meta-item">
                        <h4>Order Information</h4>
                        <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                        <p><strong>Number:</strong> <?php echo $order['id']; ?></p>
                        <p><strong>Payment:</strong> Cash on Delivery</p>
                    </div>
                    
                    <?php if (!empty($order['pickup_location']) || !empty($order['estimated_delivery'])): ?>
                        <div class="order-meta-item">
                            <h4>Delivery Information</h4>
                            <?php if (!empty($order['pickup_location'])): ?>
                                <p><strong>Type:</strong> In-Store Pickup</p>
                                <p><strong>Pickup Location:</strong> <?php echo $order['pickup_location']; ?></p>
                            <?php else: ?>
                                <p><strong>Type:</strong> Delivery</p>
                                <?php if (!empty($order['estimated_delivery'])): ?>
                                    <p><strong>Estimated Delivery:</strong> <?php echo date('M d, Y', strtotime($order['estimated_delivery'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order['carrier'])): ?>
                                    <p><strong>Carrier:</strong> <?php echo $order['carrier']; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order['tracking_number'])): ?>
                                    <p><strong>Tracking Number:</strong> <?php echo $order['tracking_number']; ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="order-items">
                    <h4>Order Items</h4>
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item">
                            <img src="../assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="order-item-image">
                            <div class="order-item-details">
                                <div class="order-item-name"><?php echo $item['name']; ?></div>
                                <div class="order-item-meta">
                                    Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['unit_price'], 2); ?>
                                </div>
                            </div>
                            <div class="order-item-price">
                                $<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-summary">
                    <div class="order-summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span>Shipping:</span>
                        <span>$0.00</span>
                    </div>
                    <div class="order-summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
                
                <!-- Status Update Form -->
                <div class="status-update" style="margin-top: 30px;">
                    <h4>Update Order Status</h4>
                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="status" class="form-control" style="flex: 1; max-width: 200px;">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>
                </div>
                
                <!-- Delivery Scheduling -->
                <?php if ($order['status'] != 'cancelled' && $order['status'] != 'delivered'): ?>
                    <div class="delivery-schedule">
                        <h4>Schedule Delivery</h4>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_pickup" id="is_pickup" <?php echo !empty($order['pickup_location']) ? 'checked' : ''; ?>>
                                    In-Store Pickup
                                </label>
                            </div>
                            
                            <div class="form-group" id="pickup_location_group" style="<?php echo empty($order['pickup_location']) ? 'display: none;' : ''; ?>">
                                <label for="pickup_location">Pickup Location</label>
                                <input type="text" id="pickup_location" name="pickup_location" 
                                       value="<?php echo $order['pickup_location'] ?? ''; ?>" 
                                       class="form-control">
                            </div>
                            
                            <div class="form-group" id="delivery_info_group" style="<?php echo !empty($order['pickup_location']) ? 'display: none;' : ''; ?>">
                                <label for="delivery_date">Estimated Delivery Date</label>
                                <input type="date" id="delivery_date" name="delivery_date" 
                                       value="<?php echo isset($order['estimated_delivery']) ? date('Y-m-d', strtotime($order['estimated_delivery'])) : ''; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" class="form-control">
                                
                                <label for="carrier" style="margin-top: 10px;">Carrier</label>
                                <select id="carrier" name="carrier" class="form-control">
                                    <option value="">Select Carrier</option>
                                    <option value="LBC" <?php echo isset($order['carrier']) && $order['carrier'] == 'LBC' ? 'selected' : ''; ?>>LBC</option>
                                    <option value="J&T Express" <?php echo isset($order['carrier']) && $order['carrier'] == 'J&T Express' ? 'selected' : ''; ?>>J&T Express</option>
                                    <option value="Ninja Van" <?php echo isset($order['carrier']) && $order['carrier'] == 'Ninja Van' ? 'selected' : ''; ?>>Ninja Van</option>
                                    <option value="DHL" <?php echo isset($order['carrier']) && $order['carrier'] == 'DHL' ? 'selected' : ''; ?>>DHL</option>
                                </select>
                                
                                <label for="tracking_number" style="margin-top: 10px;">Tracking Number</label>
                                <input type="text" id="tracking_number" name="tracking_number" 
                                       value="<?php echo $order['tracking_number'] ?? ''; ?>" 
                                       class="form-control">
                            </div>
                            
                            <button type="submit" name="schedule_delivery" class="btn btn-success">
                                <i class="fas fa-calendar-check"></i> Schedule
                            </button>
                        </form>
                    </div>
                    
                    <script>
                        document.getElementById('is_pickup').addEventListener('change', function() {
                            const isPickup = this.checked;
                            document.getElementById('pickup_location_group').style.display = isPickup ? 'block' : 'none';
                            document.getElementById('delivery_info_group').style.display = isPickup ? 'none' : 'block';
                        });
                    </script>
                <?php endif; ?>
                
                <!-- Status History -->
                <div class="status-history">
                    <h4>Status History</h4>
                    <div class="status-timeline">
                        <?php foreach ($order['status_history'] as $history): ?>
                            <div class="status-event">
                                <div class="status-event-date">
                                    <?php echo date('M d, Y H:i', strtotime($history['updated_at'])); ?>
                                </div>
                                <div class="status-event-status">
                                    <?php echo ucfirst($history['status']); ?>
                                    <?php if (!empty($history['notes'])): ?>
                                        <p><small><?php echo $history['notes']; ?></small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="orders.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Order List View -->
            <div class="filters">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control" onchange="applyFilters()">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" class="form-control" onchange="applyFilters()">
                </div>
                
                <div class="form-group">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" class="form-control" onchange="applyFilters()">
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()" style="height: 38px;">
                        <i class="fas fa-sync-alt"></i> Reset
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['email']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($order['pickup_location'])): ?>
                                        Pickup
                                    <?php elseif (!empty($order['estimated_delivery'])): ?>
                                        Delivery
                                    <?php else: ?>
                                        Not scheduled
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="orders.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Apply filters
        function applyFilters() {
            const status = document.getElementById('status').value;
            const date_from = document.getElementById('date_from').value;
            const date_to = document.getElementById('date_to').value;
            
            let url = 'orders.php?';
            
            if (status) url += `status=${status}&`;
            if (date_from) url += `date_from=${date_from}&`;
            if (date_to) url += `date_to=${date_to}&`;
            
            window.location.href = url.slice(0, -1); // Remove last & or ?
        }
        
        // Reset filters
        function resetFilters() {
            window.location.href = 'orders.php';
        }
        
        // Set max date for "to" filter
        document.addEventListener('DOMContentLoaded', function() {
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            
            if (dateFrom) {
                dateFrom.addEventListener('change', function() {
                    if (dateTo) {
                        dateTo.min = this.value;
                        if (dateTo.value && dateTo.value < this.value) {
                            dateTo.value = this.value;
                        }
                    }
                });
            }
            
            // Initialize min date for "to" if "from" has value
            if (dateFrom && dateFrom.value && dateTo) {
                dateTo.min = dateFrom.value;
            }
        });
    </script>
</body>
</html>