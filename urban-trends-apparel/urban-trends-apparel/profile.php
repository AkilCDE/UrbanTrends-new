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

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    public function logout() {
        session_unset();
        session_destroy();
    }
}

$auth = new Auth($db);

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = $auth->getCurrentUser();
$page_title = 'Profile';
$message = '';

if (isset($_GET['logout'])) {
    $auth->logout();
    header("Location: login.php");
    exit;
}

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function display_success($msg) {
    return '<div class="message message-success"><i class="fas fa-check-circle"></i> '.$msg.'</div>';
}

function display_error($msg) {
    return '<div class="message message-error"><i class="fas fa-exclamation-circle"></i> '.$msg.'</div>';
}

function getWishlistItems($db, $user_id) {
    $stmt = $db->prepare("SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCartItems($db, $user_id) {
    $stmt = $db->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrderItems($db, $order_id) {
    $stmt = $db->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isInWishlist($db, $user_id, $product_id) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    return $stmt->fetchColumn() > 0;
}

function canReturnOrder($order) {
    // Only delivered orders can be returned
    if ($order['status'] !== 'delivered') {
        return false;
    }
    
    // Check if order was delivered within the last 30 days
    $delivered_date = strtotime($order['order_date']);
    $thirty_days_ago = strtotime('-30 days');
    
    return $delivered_date >= $thirty_days_ago;
}

function processReturn($db, $order_id, $user_id) {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // 1. Check if the order exists and belongs to the user
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception("Order not found or doesn't belong to you.");
        }
        
        // 2. Check if order is eligible for return (must be delivered)
        if ($order['status'] !== 'delivered') {
            throw new Exception("Only delivered orders can be returned.");
        }
        
        // 3. Update order status to 'returned'
        $stmt = $db->prepare("UPDATE orders SET status = 'returned' WHERE id = ?");
        $stmt->execute([$order_id]);
        
        // 4. Add to order status history
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, 'returned', 'Customer initiated return']);
        
        // 5. Update shipping status if shipping record exists
        $stmt = $db->prepare("UPDATE shipping SET status = 'returned' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // 6. Restock products if needed
        $order_items = getOrderItems($db, $order_id);
        foreach ($order_items as $item) {
            $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Commit transaction
        $db->commit();
        
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return $e->getMessage();
    }
}

// Handle Add to Cart and Buy Now actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        
        try {
            // Check if product already in cart
            $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $existing_item = $stmt->fetch();
            
            if ($existing_item) {
                // Update quantity if already in cart
                $stmt = $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
                $stmt->execute([$quantity, $existing_item['id']]);
            } else {
                // Add new item to cart
                $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            }
            
            $_SESSION['success_message'] = "Product added to cart successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error adding product to cart: " . $e->getMessage();
        }
        
        header("Location: profile.php#cart");
        exit;
    }
    
    if (isset($_POST['buy_now'])) {
        $product_id = $_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        
        try {
            // Clear current cart (optional, depends on your business logic)
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Add the selected product to cart
            $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            
            header("Location: checkout.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error processing your order: " . $e->getMessage();
            header("Location: profile.php#cart");
            exit;
        }
    }
    
    // Handle profile update
    if (isset($_POST['firstname'])) {
        $firstname = sanitize($_POST['firstname']);
        $lastname = sanitize($_POST['lastname']);
        $address = sanitize($_POST['address']);
        
        // Update profile
        $stmt = $db->prepare("UPDATE users SET firstname = ?, lastname = ?, address = ? WHERE id = ?");
        if ($stmt->execute([$firstname, $lastname, $address, $user['id']])) {
            // Update session
            $_SESSION['user_firstname'] = $firstname;
            $_SESSION['user_lastname'] = $lastname;
            $_SESSION['user_address'] = $address;
            
            $message = display_success('Profile updated successfully!');
        } else {
            $message = display_error('Error updating profile.');
        }
        
        // Handle password change if provided
        if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user['id']])) {
                    $message .= display_success('Password changed successfully!');
                } else {
                    $message .= display_error('Error changing password.');
                }
            } else {
                $message .= display_error('Passwords do not match.');
            }
        }
    }
    
    // Handle wishlist actions
    if (isset($_POST['wishlist_action'])) {
        $product_id = sanitize($_POST['product_id']);
        
        if ($_POST['wishlist_action'] === 'add') {
            $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            if ($stmt->execute([$user['id'], $product_id])) {
                $message = display_success('Item added to wishlist!');
            } else {
                $message = display_error('Error adding item to wishlist.');
            }
        } elseif ($_POST['wishlist_action'] === 'remove') {
            $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            if ($stmt->execute([$user['id'], $product_id])) {
                $message = display_success('Item removed from wishlist!');
            } else {
                $message = display_error('Error removing item from wishlist.');
            }
        }
    }
    
    // Handle cart actions
    if (isset($_POST['cart_action'])) {
        $product_id = sanitize($_POST['product_id']);
        
        if ($_POST['cart_action'] === 'remove') {
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            if ($stmt->execute([$user['id'], $product_id])) {
                $message = display_success('Item removed from cart!');
            } else {
                $message = display_error('Error removing item from cart.');
            }
        } elseif ($_POST['cart_action'] === 'update') {
            $quantity = (int)$_POST['quantity'];
            $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            if ($stmt->execute([$quantity, $user['id'], $product_id])) {
                $message = display_success('Cart updated!');
            } else {
                $message = display_error('Error updating cart.');
            }
        }
    }
    
    // Handle order actions (returns/cancellations)
    if (isset($_POST['order_action'])) {
        $order_id = sanitize($_POST['order_id']);
        $action = sanitize($_POST['order_action']);
        
        if ($action === 'cancel') {
            $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$order_id, $user['id']])) {
                // Add to status history
                $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)");
                $stmt->execute([$order_id, 'cancelled', 'Customer cancelled order']);
                
                $message = display_success('Order cancelled successfully!');
            } else {
                $message = display_error('Error cancelling order.');
            }
        } elseif ($action === 'return') {
            $result = processReturn($db, $order_id, $user['id']);
            if ($result === true) {
                $message = display_success('Return processed successfully! Your items will be picked up soon.');
            } else {
                $message = display_error($result);
            }
        }
    }
}

// Get user's orders
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get wishlist items
$wishlist = getWishlistItems($db, $user['id']);

// Get cart items
$cart = getCartItems($db, $user['id']);

// Get cart count
$cart_count = 0;
if ($auth->isLoggedIn()) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Urban Trends</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a1a1a;
            --secondary-color: #121212;
            --accent-color: #ff6b6b;
            --light-color: #f8f9fa;
            --dark-color: #0d0d0d;
            --text-color: #e0e0e0;
            --text-muted: #b0b0b0;
            --success-color: #4bb543;
            --error-color: #ff3333;
            --warning-color: #ffcc00;
            --border-radius: 8px;
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem 2rem;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo a {
            color: white;
            text-decoration: none;
        }

        .logo i {
            color: var(--accent-color);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background-color: var(--accent-color);
            transition: var(--transition);
        }

        nav a:hover::after {
            width: 70%;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-actions a {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .user-actions a:hover {
            color: var(--accent-color);
            transform: translateY(-2px);
        }

        .cart-count {
            position: relative;
        }

        .cart-count span {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Profile Content */
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }

        .profile-sidebar {
            background: var(--primary-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            height: fit-content;
        }

        .profile-sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-sidebar ul {
            list-style: none;
        }

        .profile-sidebar li {
            margin-bottom: 0.5rem;
        }

        .profile-sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.8rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .profile-sidebar a:hover {
            background-color: rgba(255, 107, 107, 0.1);
            color: var(--accent-color);
            transform: translateX(5px);
        }

        .profile-sidebar a.active {
            background-color: rgba(255, 107, 107, 0.2);
            color: var(--accent-color);
            font-weight: 500;
        }

        .profile-content {
            background: var(--primary-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
        }

        .profile-section {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            color: var(--text-color);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
            background-color: rgba(255, 255, 255, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
        }

        .btn-outline:hover {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--error-color);
        }

        .btn-danger:hover {
            background-color: #e60000;
        }

        /* Orders Table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #444;
        }

        .orders-table th {
            background-color: rgba(255, 107, 107, 0.1);
            color: var(--accent-color);
            font-weight: 600;
        }

        .orders-table tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: rgba(255, 204, 0, 0.2);
            color: #ffcc00;
        }

        .status-processing {
            background-color: rgba(0, 123, 255, 0.2);
            color: #4dabf7;
        }

        .status-shipped {
            background-color: rgba(23, 162, 184, 0.2);
            color: #15aabf;
        }

        .status-delivered {
            background-color: rgba(40, 167, 69, 0.2);
            color: #40c057;
        }

        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.2);
            color: #fa5252;
        }

        .status-return_requested {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-returned {
            background-color: rgba(13, 110, 253, 0.2);
            color: #0d6efd;
        }

        .status-refunded {
            background-color: rgba(25, 135, 84, 0.2);
            color: #198754;
        }

        /* Wishlist Items */
        .wishlist-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .wishlist-item {
            background-color: var(--secondary-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
            position: relative;
        }

        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .wishlist-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #444;
        }

        .wishlist-item-info {
            padding: 1.2rem;
        }

        .wishlist-item-info h4 {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .wishlist-item-info p {
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .wishlist-actions {
            display: flex;
            gap: 0.8rem;
        }

        /* Product Card in Wishlist */
        .product-card {
            background-color: var(--primary-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: var(--accent-color);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 2;
        }

        .product-image-container {
            height: 200px;
            position: relative;
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            gap: 0.8rem;
        }

        .action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.8rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .buy-now {
            background-color: var(--accent-color);
            color: white;
        }

        .buy-now:hover {
            background-color: #ff5252;
        }

        .add-to-cart {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
        }

        .add-to-cart:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .wishlist-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
            font-size: 1.1rem;
        }

        .wishlist-btn:hover, .wishlist-btn.active {
            color: var(--accent-color);
            background-color: rgba(255, 107, 107, 0.1);
        }

        /* Messages */
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-success {
            background-color: rgba(75, 181, 67, 0.2);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .message-error {
            background-color: rgba(255, 51, 51, 0.2);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        /* Return History Styles */
        .history-details {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 1rem;
        }

        .history-details ul {
            list-style-type: none;
            padding-left: 0;
        }

        .history-details li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .history-details li:last-child {
            border-bottom: none;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-top: 3rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-column h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            color: var(--accent-color);
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--accent-color);
        }

        .footer-column p {
            margin-bottom: 1rem;
            color: var(--text-muted);
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 0.8rem;
        }

        .footer-column a {
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-column a:hover {
            color: white;
            transform: translateX(5px);
        }

        .footer-column a i {
            width: 20px;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: var(--transition);
        }

        .social-links a:hover {
            background-color: var(--accent-color);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                margin-bottom: 2rem;
            }

            nav ul {
                gap: 1rem;
            }

            .user-actions {
                gap: 1rem;
            }
        }

        @media (max-width: 576px) {
            header {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }

            .user-actions {
                margin-top: 1rem;
            }

            .wishlist-items {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><i class="fas fa-tshirt"></i> Urban Trends</a>
        </div>
        
        <nav>
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="shop.php"><i class="fas fa-store"></i> Shop</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
            </ul>
        </nav>
        
        <div class="user-actions">
            <?php if ($auth->isLoggedIn()): ?>
                <a href="profile.php" title="Profile"><i class="fas fa-user"></i></a>
                <?php if ($auth->isAdmin()): ?>
                    <a href="admin/dashboard.php" title="Admin"><i class="fas fa-cog"></i></a>
                <?php endif; ?>
                <a href="?logout=1" title="Logout"><i class="fas fa-sign-out-alt"></i> logout</a>
            <?php else: ?>
                <a href="login.php" title="Login"><i class="fas fa-sign-in-alt"></i></a>
                <a href="register.php" title="Register"><i class="fas fa-user-plus"></i></a>
            <?php endif; ?>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-sidebar">
            <h2><i class="fas fa-user-circle"></i> Profile</h2>
            <ul>
                <li><a href="#edit-profile" class="active"><i class="fas fa-user-edit"></i> Edit profile</a></li>
                <li><a href="#change-password"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="#cart"><i class="fas fa-shopping-cart"></i> My Cart (<?php echo $cart_count; ?>)</a></li>
                <li><a href="#orders"><i class="fas fa-clipboard-list"></i> My Orders</a></li>
                <li><a href="#wishlist"><i class="fas fa-heart"></i> Wishlist (<?php echo count($wishlist); ?>)</a></li>
                <li><a href="#returns"><i class="fas fa-exchange-alt"></i> Returns</a></li>
            </ul>
        </div>
        
        <div class="profile-content">
            <?php 
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'success') !== false ? 'message-success' : 'message-error') . '">
                    <i class="fas ' . (strpos($message, 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle') . '"></i>
                    ' . $message . '
                </div>';
            }
            
            if (isset($_SESSION['success_message'])) {
                echo '<div class="message message-success">
                    <i class="fas fa-check-circle"></i> ' . $_SESSION['success_message'] . '
                </div>';
                unset($_SESSION['success_message']);
            }
            
            if (isset($_SESSION['error_message'])) {
                echo '<div class="message message-error">
                    <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error_message'] . '
                </div>';
                unset($_SESSION['error_message']);
            }
            ?>
            
            <!-- Edit Profile Section -->
            <div id="edit-profile" class="profile-section">
                <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="firstname">Firstname</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastname">Lastname</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
            
            <!-- Change Password Section -->
            <div id="change-password" class="profile-section" style="display: none;">
                <h3><i class="fas fa-key"></i> Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-key"></i> Change Password</button>
                </form>
            </div>
            
            <!-- Cart Section -->
            <div id="cart" class="profile-section" style="display: none;">
                <h3><i class="fas fa-shopping-cart"></i> My Cart</h3>
                <?php if (empty($cart)): ?>
                    <p>Your cart is empty. <a href="shop.php">Continue shopping</a></p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $cart_total = 0;
                            foreach ($cart as $item): 
                                $product_total = $item['price'] * $item['quantity'];
                                $cart_total += $product_total;
                            ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                    </td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <form method="POST" style="display: flex; align-items: center; gap: 5px;">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="cart_action" value="update">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px; padding: 5px;">
                                            <button type="submit" class="btn" style="padding: 5px 10px;"><i class="fas fa-sync-alt"></i></button>
                                        </form>
                                    </td>
                                    <td>₱<?php echo number_format($product_total, 2); ?></td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="cart_action" value="remove">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px;"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                                <td style="font-weight: bold;">₱<?php echo number_format($cart_total, 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 20px; text-align: right;">
                        <a href="shop.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                        <a href="checkout.php" class="btn"><i class="fas fa-credit-card"></i> Proceed to Checkout</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Orders Section -->
            <div id="orders" class="profile-section" style="display: none;">
                <h3><i class="fas fa-clipboard-list"></i> Order History</h3>
                <?php if (empty($orders)): ?>
                    <p>You haven't placed any orders yet. <a href="shop.php">Start shopping</a></p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): 
                                $order_items = getOrderItems($db, $order['id']);
                            ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php foreach ($order_items as $item): ?>
                                            <div style="margin-bottom: 5px;">
                                                <?php echo htmlspecialchars($item['name']); ?> 
                                                (x<?php echo $item['quantity']; ?>)
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn"><i class="fas fa-eye"></i> View</a>
                                        <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                            <form method="POST" style="display: inline-block; margin-left: 5px;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="order_action" value="cancel">
                                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px;"><i class="fas fa-times"></i> Cancel</button>
                                            </form>
                                        <?php elseif (canReturnOrder($order)): ?>
                                            <form method="POST" style="display: inline-block; margin-left: 5px;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="order_action" value="return">
                                                <button type="submit" class="btn btn-outline" style="padding: 5px 10px;"><i class="fas fa-exchange-alt"></i> Return</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Wishlist Section -->
            <div id="wishlist" class="profile-section" style="display: none;">
                <h3><i class="fas fa-heart"></i> Wishlist</h3>
                <?php if (empty($wishlist)): ?>
                    <p>Your wishlist is empty. <a href="shop.php">Browse our products</a></p>
                <?php else: ?>
                    <div class="wishlist-items">
                        <?php foreach ($wishlist as $product): ?>
                            <div class="product-card">
                                <?php if($product['stock'] < 10): ?>
                                    <span class="product-badge">Only <?php echo $product['stock']; ?> left</span>
                                <?php endif; ?>
                                <div class="product-image-container">
                                    <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                    <div class="product-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="buy_now" class="action-btn buy-now">
                                                <i class="fas fa-bolt"></i> Buy Now
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="add_to_cart" class="action-btn add-to-cart">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="wishlist_action" value="remove">
                                            <button type="submit" class="wishlist-btn active">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Returns Section -->
            <div id="returns" class="profile-section" style="display: none;">
                <h3><i class="fas fa-exchange-alt"></i> Returns & Refunds</h3>
                <?php 
                $return_orders = array_filter($orders, function($order) {
                    return in_array($order['status'], ['return_requested', 'returned', 'refunded']);
                });
                
                if (empty($return_orders)): ?>
                    <p>You don't have any return requests.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($return_orders as $order): 
                                $order_items = getOrderItems($db, $order['id']);
                                $status_history = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY changed_at DESC");
                                $status_history->execute([$order['id']]);
                                $history = $status_history->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php foreach ($order_items as $item): ?>
                                            <div style="margin-bottom: 5px;">
                                                <?php echo htmlspecialchars($item['name']); ?> 
                                                (x<?php echo $item['quantity']; ?>)
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php 
                                            $status_map = [
                                                'return_requested' => 'Return Requested',
                                                'returned' => 'Returned - Pending Refund',
                                                'refunded' => 'Refund Processed'
                                            ];
                                            echo $status_map[$order['status']] ?? ucfirst($order['status']); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn" onclick="document.getElementById('history-<?php echo $order['id']; ?>').style.display='block'">
                                            <i class="fas fa-history"></i> View History
                                        </button>
                                    </td>
                                </tr>
                                <tr id="history-<?php echo $order['id']; ?>" style="display: none;">
                                    <td colspan="6">
                                        <div class="history-details">
                                            <h4>Status History:</h4>
                                            <ul>
                                                <?php foreach ($history as $entry): ?>
                                                    <li>
                                                        <strong><?php echo ucfirst($entry['status']); ?></strong> - 
                                                        <?php echo date('M d, Y h:i A', strtotime($entry['changed_at'])); ?>
                                                        <?php if ($entry['notes']): ?>
                                                            <br><em><?php echo htmlspecialchars($entry['notes']); ?></em>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <button class="btn btn-outline" onclick="document.getElementById('history-<?php echo $order['id']; ?>').style.display='none'">
                                                <i class="fas fa-times"></i> Close
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <div style="margin-top: 30px;">
                    <h4><i class="fas fa-info-circle"></i> Return Policy</h4>
                    <p>Our return policy allows you to return items within 30 days of delivery for a full refund. Items must be in their original condition with all tags attached. Please contact our support team if you have any questions about returns.</p>
                    
                    <h5>How to Return an Item:</h5>
                    <ol>
                        <li>Click the "Return" button on your delivered order</li>
                        <li>Wait for our team to approve your return request</li>
                        <li>You'll receive a return shipping label via email</li>
                        <li>Pack the items securely and attach the label</li>
                        <li>Drop off the package at any courier location</li>
                        <li>Once received, we'll process your refund within 3-5 business days</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>About Urban Trends</h3>
                <p>Your premier destination for the latest in urban fashion trends. We offer high-quality apparel and accessories for the modern urban lifestyle.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="shop.php"><i class="fas fa-chevron-right"></i> Shop</a></li>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                    <li><a href="faq.php"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="profile.php"><i class="fas fa-chevron-right"></i> My Account</a></li>
                    <li><a href="orders.php"><i class="fas fa-chevron-right"></i> Order Tracking</a></li>
                    <li><a href="returns.php"><i class="fas fa-chevron-right"></i> Returns & Refunds</a></li>
                    <li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                    <li><a href="terms.php"><i class="fas fa-chevron-right"></i> Terms & Conditions</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contact Info</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Urban Street, Fashion District, City</li>
                    <li><i class="fas fa-phone"></i> +1 (123) 456-7890</li>
                    <li><i class="fas fa-envelope"></i> info@urbantrends.com</li>
                    <li><i class="fas fa-clock"></i> Mon-Fri: 9AM - 6PM</li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Urban Trends Apparel. All rights reserved.
        </div>
    </footer>

    <script>
        // Profile section navigation
        document.querySelectorAll('.profile-sidebar a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('.profile-sidebar a').forEach(l => {
                    l.classList.remove('active');
                });
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Hide all sections
                document.querySelectorAll('.profile-section').forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show selected section with animation
                const sectionId = this.getAttribute('href');
                const section = document.querySelector(sectionId);
                section.style.display = 'block';
                
                // Trigger animation
                section.style.animation = 'none';
                setTimeout(() => {
                    section.style.animation = 'fadeIn 0.5s ease';
                }, 10);
            });
        });
        
        // Update cart counter
        function updateCartCounter() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-counter').textContent = data.count;
                })
                .catch(error => {
                    console.error('Error fetching cart count:', error);
                });
        }

        // Initialize cart counter
        updateCartCounter();

        // Handle wishlist button clicks
        document.querySelectorAll('.wishlist-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(() => {
                    window.location.reload();
                });
            });
        });

        // Handle add to cart and buy now buttons in wishlist
        document.querySelectorAll('.add-to-cart, .buy-now').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const formData = new FormData(form);
                
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(() => {
                    updateCartCounter();
                    if (form.querySelector('[name="buy_now"]')) {
                        window.location.href = 'checkout.php';
                    } else {
                        window.location.href = 'profile.php#cart';
                    }
                });
            });
        });
    </script>
</body>
</html>