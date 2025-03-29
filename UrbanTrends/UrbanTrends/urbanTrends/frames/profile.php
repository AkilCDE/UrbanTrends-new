<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

// Get customer data
$customer_id = $_SESSION['customer_id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

// Get orders
$orders = $pdo->prepare("
    SELECT o.*, COUNT(oi.item_id) as item_count 
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.customer_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");
$orders->execute([$customer_id]);

// Get wishlist items
$wishlist = $pdo->prepare("
    SELECT p.* FROM products p
    JOIN wishlist w ON p.product_id = w.product_id
    WHERE w.customer_id = ?
");
$wishlist->execute([$customer_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendsWear - My Profile</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
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
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .profile-header h1 {
            font-size: 28px;
            color: var(--primary-color);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: bold;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
        }
        
        .tab.active {
            border-bottom-color: var(--accent-color);
            color: var(--accent-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            font-size: 18px;
            color: var(--primary-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status.completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status.shipped {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .wishlist-item {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .wishlist-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .wishlist-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .wishlist-item-details {
            padding: 15px;
        }
        
        .wishlist-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .wishlist-item-price {
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>My Account</h1>
            <div class="user-avatar">
                <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="switchTab('profile')">Profile</div>
            <div class="tab" onclick="switchTab('orders')">Orders</div>
            <div class="tab" onclick="switchTab('wishlist')">Wishlist</div>
            <div class="tab" onclick="switchTab('addresses')">Addresses</div>
        </div>
        
        <!-- Profile Tab -->
        <div id="profile-tab" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h2>Personal Information</h2>
                    <button class="btn btn-primary" onclick="showEditProfileModal()">Edit Profile</button>
                </div>
                
                <div class="profile-info">
                    <div class="form-group">
                        <label>Full Name</label>
                        <p><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($customer['email']); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Member Since</label>
                        <p><?php echo date('F j, Y', strtotime($customer['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                </div>
                
                <form id="change-password-form">
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
        
        <!-- Orders Tab -->
        <div id="orders-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Order History</h2>
                    <div class="search-filter">
                        <input type="text" id="order-search" placeholder="Search orders...">
                        <select id="order-status-filter">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Processing">Processing</option>
                            <option value="Shipped">Shipped</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                        <button class="btn btn-primary" onclick="filterOrders()">Filter</button>
                    </div>
                </div>
                
                <?php if ($orders->rowCount() > 0): ?>
                    <table>
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
                            <?php while ($order = $orders->fetch()): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo $order['item_count']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">View</button>
                                        <?php if ($order['status'] == 'Pending' || $order['status'] == 'Processing'): ?>
                                            <button class="btn btn-danger" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">Cancel</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i>📦</i>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders with us yet.</p>
                        <button class="btn btn-primary" onclick="location.href='products.php'">Start Shopping</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Wishlist Tab -->
        <div id="wishlist-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>My Wishlist</h2>
                    <div class="search-filter">
                        <input type="text" id="wishlist-search" placeholder="Search wishlist...">
                        <button class="btn btn-primary" onclick="filterWishlist()">Search</button>
                    </div>
                </div>
                
                <?php if ($wishlist->rowCount() > 0): ?>
                    <div class="wishlist-grid">
                        <?php while ($item = $wishlist->fetch()): ?>
                            <div class="wishlist-item">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="wishlist-item-details">
                                    <div class="wishlist-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="wishlist-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="wishlist-item-actions">
                                        <button class="btn btn-primary" onclick="addToCart(<?php echo $item['product_id']; ?>)">Add to Cart</button>
                                        <button class="btn btn-danger" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)">Remove</button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i>❤️</i>
                        <h3>Your Wishlist is Empty</h3>
                        <p>Save items you love for easy access later.</p>
                        <button class="btn btn-primary" onclick="location.href='products.php'">Browse Products</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Addresses Tab -->
        <div id="addresses-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Saved Addresses</h2>
                    <button class="btn btn-primary" onclick="showAddAddressModal()">Add New Address</button>
                </div>
                
                <?php
                // Get customer addresses
                $addresses = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ?");
                $addresses->execute([$customer_id]);
                
                if ($addresses->rowCount() > 0): ?>
                    <div class="address-grid">
                        <?php while ($address = $addresses->fetch()): ?>
                            <div class="address-card">
                                <h4><?php echo htmlspecialchars($address['address_name']); ?></h4>
                                <p>
                                    <?php echo htmlspecialchars($address['street_address']); ?><br>
                                    <?php echo htmlspecialchars($address['city']); ?>, 
                                    <?php echo htmlspecialchars($address['state']); ?> 
                                    <?php echo htmlspecialchars($address['zip_code']); ?><br>
                                    <?php echo htmlspecialchars($address['country']); ?>
                                </p>
                                <p>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?>
                                </p>
                                <div class="address-actions">
                                    <button class="btn btn-primary" onclick="editAddress(<?php echo $address['address_id']; ?>)">Edit</button>
                                    <button class="btn btn-danger" onclick="deleteAddress(<?php echo $address['address_id']; ?>)">Delete</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i>🏠</i>
                        <h3>No Saved Addresses</h3>
                        <p>Add your addresses for faster checkout.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal (would be shown with JavaScript) -->
    <div id="edit-profile-modal" class="modal" style="display:none;">
        <!-- Modal content would go here -->
    </div>
    
    <!-- Order Details Modal (would be shown with JavaScript) -->
    <div id="order-details-modal" class="modal" style="display:none;">
        <!-- Modal content would go here -->
    </div>
    
    <!-- Add Address Modal (would be shown with JavaScript) -->
    <div id="add-address-modal" class="modal" style="display:none;">
        <!-- Modal content would go here -->
    </div>
    
    <script>
        // Tab switching
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tab + '-tab').classList.add('active');
            
            // Update active tab button
            document.querySelectorAll('.tab').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`.tab[onclick="switchTab('${tab}')"]`).classList.add('active');
        }
        
        // Order functions
        function viewOrderDetails(orderId) {
            // In a real app, this would show a modal with order details
            alert('Viewing order #' + orderId);
            // You would fetch order details via AJAX and display them
        }
        
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // In a real app, this would make an AJAX call to cancel the order
                alert('Cancelling order #' + orderId);
                // Then refresh the order list
                location.reload();
            }
        }
        
        // Wishlist functions
        function addToCart(productId) {
            // In a real app, this would add the item to cart via AJAX
            alert('Adding product #' + productId + ' to cart');
        }
        
        function removeFromWishlist(productId) {
            if (confirm('Remove this item from your wishlist?')) {
                // In a real app, this would make an AJAX call to remove the item
                alert('Removing product #' + productId + ' from wishlist');
                // Then refresh the wishlist
                location.reload();
            }
        }
        
        function filterWishlist() {
            const searchTerm = document.getElementById('wishlist-search').value.toLowerCase();
            // In a real app, this would filter the wishlist via AJAX
            alert('Filtering wishlist with search: ' + searchTerm);
        }
        
        // Address functions
        function showAddAddressModal() {
            // In a real app, this would show the add address modal
            alert('Showing add address modal');
        }
        
        function editAddress(addressId) {
            // In a real app, this would show the edit address modal
            alert('Editing address #' + addressId);
        }
        
        function deleteAddress(addressId) {
            if (confirm('Delete this address?')) {
                // In a real app, this would make an AJAX call to delete the address
                alert('Deleting address #' + addressId);
                // Then refresh the address list
                location.reload();
            }
        }
        
        // Profile functions
        function showEditProfileModal() {
            // In a real app, this would show the edit profile modal
            alert('Showing edit profile modal');
        }
        
        // Form submission
        document.getElementById('change-password-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }
            
            // In a real app, this would make an AJAX call to change the password
            alert('Password change request submitted');
            this.reset();
        });
    </script>
</body>
</html>