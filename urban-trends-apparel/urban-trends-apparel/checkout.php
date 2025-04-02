<?php
// Database configuration and session start
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
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'firstname' => $_SESSION['user_firstname'],
                'lastname' => $_SESSION['user_lastname'],
                'address' => $_SESSION['user_address']
            ];
        }
        return null;
    }
}

$auth = new Auth($db);

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header("Location: login.php");
    exit;
}

// Get cart items
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("
    SELECT c.*, p.name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 50; // Flat rate shipping
$total = $subtotal + $shipping;

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Create order
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, total_amount, shipping_address, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $user_id,
            $total,
            $_POST['shipping_address']
        ]);
        $order_id = $db->lastInsertId();
        
        // Add order items
        foreach ($cart_items as $item) {
            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
            
            // Update product stock
            $stmt = $db->prepare("
                UPDATE products SET stock = stock - ? WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Create payment record
        $stmt = $db->prepare("
            INSERT INTO payments (order_id, amount, payment_method, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $order_id,
            $total,
            $_POST['payment_method']
        ]);
        
        // Clear cart
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $db->commit();
        
        // Redirect to order confirmation
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Checkout failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Trends Apparel - Checkout</title>
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

        /* Checkout specific styles */
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .checkout-section {
            background-color: var(--primary-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .checkout-section h2 {
            margin-bottom: 1.5rem;
            color: var(--accent-color);
            border-bottom: 1px solid #444;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: var(--border-radius);
            color: var(--text-color);
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .payment-method:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .payment-method.selected {
            background-color: rgba(255, 107, 107, 0.2);
            border: 1px solid var(--accent-color);
        }
        
        .payment-method input {
            display: none;
        }
        
        .payment-method i {
            font-size: 1.5rem;
        }
        
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #444;
        }
        
        .order-total {
            font-size: 1.2rem;
            font-weight: 700;
            margin-top: 1rem;
            color: var(--accent-color);
        }
        
        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }
        
        .checkout-btn:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
        }
        
        .cart-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #444;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }
        
        .cart-item-details {
            flex: 1;
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

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
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
                    <a href="wishlist.php" title="Wishlist"><i class="fas fa-heart"></i></a>
                    <a href="cart.php" class="cart-count" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-counter"><?php echo count($cart_items); ?></span>
                    </a>
                    <a href="?logout=1" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" title="Login"><i class="fas fa-sign-in-alt"></i></a>
                    <a href="register.php" title="Register"><i class="fas fa-user-plus"></i></a>
                    <a href="cart.php" class="cart-count" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-counter">0</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="container">
        <h1 style="margin: 2rem 0 1rem;">Checkout</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="checkout-container">
            <div class="checkout-section">
                <h2>Shipping Information</h2>
                
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" required 
                           value="<?php echo htmlspecialchars($auth->getCurrentUser()['firstname'] . ' ' . $auth->getCurrentUser()['lastname']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($auth->getCurrentUser()['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="shipping_address">Shipping Address</label>
                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="4" required><?php 
                        echo htmlspecialchars($auth->getCurrentUser()['address']); 
                    ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" required>
                </div>
                
                <h2>Payment Method</h2>
                
                <div class="payment-methods">
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="cod" required checked>
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Cash on Delivery</span>
                    </label>
                    
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="gcash">
                        <i class="fas fa-mobile-alt"></i>
                        <span>GCash</span>
                    </label>
                    
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="paypal">
                        <i class="fab fa-paypal"></i>
                        <span>PayPal</span>
                    </label>
                    
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="credit_card">
                        <i class="far fa-credit-card"></i>
                        <span>Credit Card</span>
                    </label>
                </div>
                
                <!-- Payment details (shown based on selection) -->
                <div id="payment-details"></div>
            </div>
            
            <div class="checkout-section">
                <h2>Order Summary</h2>
                
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                        <div class="cart-item-details">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>₱<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?></p>
                        </div>
                        <div>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-summary-item">
                    <span>Subtotal</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <div class="order-summary-item">
                    <span>Shipping</span>
                    <span>₱<?php echo number_format($shipping, 2); ?></span>
                </div>
                
                <div class="order-summary-item order-total">
                    <span>Total</span>
                    <span>₱<?php echo number_format($total, 2); ?></span>
                </div>
                
                <button type="submit" class="checkout-btn">Complete Order</button>
            </div>
        </form>
    </main>
    
    <footer>
        <div class="container">
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
        </div>
    </footer>

    <script>
        // Show payment details based on selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const paymentDetails = document.getElementById('payment-details');
                let html = '';
                
                switch(this.value) {
                    case 'gcash':
                        html = `
                            <div class="form-group">
                                <label for="gcash_number">GCash Number</label>
                                <input type="text" id="gcash_number" name="gcash_number" class="form-control" placeholder="09XXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label for="gcash_name">Account Name</label>
                                <input type="text" id="gcash_name" name="gcash_name" class="form-control">
                            </div>
                        `;
                        break;
                        
                    case 'paypal':
                        html = `
                            <div class="alert alert-info">
                                You will be redirected to PayPal to complete your payment.
                            </div>
                        `;
                        break;
                        
                    case 'credit_card':
                        html = `
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="form-group">
                                <label for="card_name">Name on Card</label>
                                <input type="text" id="card_name" name="card_name" class="form-control">
                            </div>
                            <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="card_expiry">Expiry Date</label>
                                    <input type="text" id="card_expiry" name="card_expiry" class="form-control" placeholder="MM/YY">
                                </div>
                                <div>
                                    <label for="card_cvv">CVV</label>
                                    <input type="text" id="card_cvv" name="card_cvv" class="form-control" placeholder="123">
                                </div>
                            </div>
                        `;
                        break;
                        
                    default:
                        html = '';
                }
                
                paymentDetails.innerHTML = html;
            });
        });
    </script>
</body>
</html>