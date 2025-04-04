<?php
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: shop.php");
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $db->prepare("SELECT firstname FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order details
$stmt = $db->prepare("
    SELECT o.*, p.status as payment_status, p.payment_method 
    FROM orders o 
    LEFT JOIN payments p ON o.id = p.order_id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: shop.php");
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Same header as shop.php -->
    <title>Urban Trends Apparel - Order Confirmation</title>
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--primary-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 4rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        
        .confirmation-message {
            margin-bottom: 2rem;
        }
        
        .order-details {
            text-align: left;
            margin: 2rem 0;
            padding: 1.5rem;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
        }
        
        .order-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #444;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }
        
        .order-summary {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #444;
        }
        
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .order-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-top: 1rem;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .continue-shopping {
            background-color: var(--accent-color);
            color: white;
        }
        
        .continue-shopping:hover {
            background-color: #ff5252;
        }
        
        .view-orders {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
        }
        
        .view-orders:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <!-- Same header as shop.php -->
    
    <main class="container">
        <div class="confirmation-container">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1>Order Confirmed!</h1>
            
            <div class="confirmation-message">
                <p>Thank you for your order, <?php echo htmlspecialchars($user['firstname']); ?>!</p>
                <p>Your order #<?php echo $order_id; ?> has been received and is being processed.</p>
            </div>
            
            <div class="order-details">
                <h3>Order Summary</h3>
                
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <img src="assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" class="order-item-image">
                        <div style="flex: 1;">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-summary">
                    <div class="order-summary-item">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($order['total_amount'] - 50, 2); ?></span>
                    </div>
                    <div class="order-summary-item">
                        <span>Shipping:</span>
                        <span>₱50.00</span>
                    </div>
                    <div class="order-summary-item order-total">
                        <span>Total:</span>
                        <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <h4>Payment Method</h4>
                    <p>
                        <?php 
                        switch($order['payment_method']) {
                            case 'cod': echo 'Cash on Delivery'; break;
                            case 'gcash': echo 'GCash'; break;
                            case 'paypal': echo 'PayPal'; break;
                            case 'credit_card': echo 'Credit Card'; break;
                            default: echo $order['payment_method'];
                        }
                        ?>
                    </p>
                </div>
                
                <div style="margin-top: 1rem;">
                    <h4>Shipping Address</h4>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="shop.php" class="action-btn continue-shopping">Continue Shopping</a>
                <a href="orders.php" class="action-btn view-orders">View My Orders</a>
            </div>
        </div>
    </main>
    
    <!-- Same footer as shop.php -->
</body>
</html>