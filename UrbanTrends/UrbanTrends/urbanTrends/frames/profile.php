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

// Get customer addresses
$addresses = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ?");
$addresses->execute([$customer_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendsWear - My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        /* Container */
        .profile-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .profile-header h1 {
            font-size: 24px;
            color: #333;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .tab.active {
            border-bottom-color: #3498db;
            color: #3498db;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            font-size: 18px;
            color: #333;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #f9f9f9;
        }
        
        /* Status Badges */
        .status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status.pending {
            background-color: #f39c12;
            color: white;
        }
        
        .status.completed {
            background-color: #2ecc71;
            color: white;
        }
        
        .status.shipped {
            background-color: #3498db;
            color: white;
        }
        
        .status.cancelled {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Grid Layouts */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .wishlist-item, .address-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        
        .wishlist-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 3px;
        }
        
        .wishlist-item-title {
            font-weight: bold;
            margin: 10px 0 5px;
        }
        
        .wishlist-item-price {
            color: #3498db;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }
        
        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 20px;
            border-radius: 5px;
            width: 90%;
            max-width: 600px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .close {
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-avatar {
                margin-top: 15px;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex: 1 0 auto;
                text-align: center;
            }
            
            .wishlist-grid, .address-grid {
                grid-template-columns: 1fr;
            }
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
                
                <?php if ($addresses->rowCount() > 0): ?>
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
        
        <!-- Edit Profile Modal -->
        <div id="edit-profile-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Profile</h2>
                    <span class="close" onclick="closeModal('edit-profile-modal')">&times;</span>
                </div>
                <form id="edit-profile-form" onsubmit="updateProfile(event)">
                    <div class="form-group">
                        <label for="edit-first-name">First Name</label>
                        <input type="text" id="edit-first-name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-last-name">Last Name</label>
                        <input type="text" id="edit-last-name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-email">Email</label>
                        <input type="email" id="edit-email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-phone">Phone</label>
                        <input type="tel" id="edit-phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
        
        <!-- Order Details Modal -->
        <div id="order-details-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Order Details</h2>
                    <span class="close" onclick="closeModal('order-details-modal')">&times;</span>
                </div>
                <div id="order-details-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
        
        <!-- Add Address Modal -->
        <div id="add-address-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Address</h2>
                    <span class="close" onclick="closeModal('add-address-modal')">&times;</span>
                </div>
                <form id="add-address-form" onsubmit="saveAddress(event)">
                    <div class="form-group">
                        <label for="address-name">Address Name (e.g., Home, Work)</label>
                        <input type="text" id="address-name" required>
                    </div>
                    <div class="form-group">
                        <label for="street-address">Street Address</label>
                        <input type="text" id="street-address" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State/Province</label>
                        <input type="text" id="state" required>
                    </div>
                    <div class="form-group">
                        <label for="zip-code">ZIP/Postal Code</label>
                        <input type="text" id="zip-code" required>
                    </div>
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" required>
                    </div>
                    <div class="form-group">
                        <label for="address-phone">Phone</label>
                        <input type="tel" id="address-phone">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="default-address"> Set as default address
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </form>
            </div>
        </div>
        
        <!-- Edit Address Modal -->
        <div id="edit-address-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Address</h2>
                    <span class="close" onclick="closeModal('edit-address-modal')">&times;</span>
                </div>
                <form id="edit-address-form" onsubmit="updateAddress(event)">
                    <input type="hidden" id="edit-address-id">
                    <div class="form-group">
                        <label for="edit-address-name">Address Name</label>
                        <input type="text" id="edit-address-name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-street-address">Street Address</label>
                        <input type="text" id="edit-street-address" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-city">City</label>
                        <input type="text" id="edit-city" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-state">State/Province</label>
                        <input type="text" id="edit-state" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-zip-code">ZIP/Postal Code</label>
                        <input type="text" id="edit-zip-code" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-country">Country</label>
                        <input type="text" id="edit-country" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-address-phone">Phone</label>
                        <input type="tel" id="edit-address-phone">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit-default-address"> Set as default address
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Address</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize tabs on page load
        document.addEventListener('DOMContentLoaded', function() {
            switchTab('profile');
        });
        
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });
            document.getElementById(tab + '-tab').classList.add('active');
            
            document.querySelectorAll('.tab').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`.tab[onclick="switchTab('${tab}')"]`).classList.add('active');
        }
        
        // Profile functions
        function showEditProfileModal() {
            showModal('edit-profile-modal');
        }
        
        function updateProfile(e) {
            e.preventDefault();
            
            const formData = {
                first_name: document.getElementById('edit-first-name').value,
                last_name: document.getElementById('edit-last-name').value,
                email: document.getElementById('edit-email').value,
                phone: document.getElementById('edit-phone').value
            };
            
            fetch('update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    closeModal('edit-profile-modal');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update profile'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating profile');
            });
        }
        
        // Order functions
        function viewOrderDetails(orderId) {
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <h3>Order #${data.order.order_id}</h3>
                            <p><strong>Date:</strong> ${new Date(data.order.order_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> <span class="status ${data.order.status.toLowerCase()}">${data.order.status}</span></p>
                            <p><strong>Total:</strong> $${data.order.total_amount.toFixed(2)}</p>
                            
                            <h4>Items</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        
                        data.items.forEach(item => {
                            html += `
                                <tr>
                                    <td>${item.name}</td>
                                    <td>$${item.price.toFixed(2)}</td>
                                    <td>${item.quantity}</td>
                                    <td>$${(item.price * item.quantity).toFixed(2)}</td>
                                </tr>`;
                        });
                        
                        html += `</tbody></table>`;
                        
                        document.getElementById('order-details-content').innerHTML = html;
                        showModal('order-details-modal');
                    } else {
                        alert('Error: ' + (data.message || 'Failed to load order details'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading order details');
                });
        }
        
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to cancel order'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling order');
                });
            }
        }
        
        // Wishlist functions
        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to add to cart'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart');
            });
        }
        
        function removeFromWishlist(productId) {
            if (confirm('Remove this item from your wishlist?')) {
                fetch('remove_from_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item removed from wishlist!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to remove from wishlist'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing from wishlist');
                });
            }
        }
        
        // Address functions
        function showAddAddressModal() {
            document.getElementById('add-address-form').reset();
            showModal('add-address-modal');
        }
        
        function saveAddress(e) {
            e.preventDefault();
            
            const formData = {
                address_name: document.getElementById('address-name').value,
                street_address: document.getElementById('street-address').value,
                city: document.getElementById('city').value,
                state: document.getElementById('state').value,
                zip_code: document.getElementById('zip-code').value,
                country: document.getElementById('country').value,
                phone: document.getElementById('address-phone').value,
                is_default: document.getElementById('default-address').checked
            };
            
            fetch('add_address.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Address saved successfully!');
                    closeModal('add-address-modal');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save address'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving address');
            });
        }
        
        function editAddress(addressId) {
            fetch('get_address.php?address_id=' + addressId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit-address-id').value = data.address.address_id;
                        document.getElementById('edit-address-name').value = data.address.address_name;
                        document.getElementById('edit-street-address').value = data.address.street_address;
                        document.getElementById('edit-city').value = data.address.city;
                        document.getElementById('edit-state').value = data.address.state;
                        document.getElementById('edit-zip-code').value = data.address.zip_code;
                        document.getElementById('edit-country').value = data.address.country;
                        document.getElementById('edit-address-phone').value = data.address.phone || '';
                        document.getElementById('edit-default-address').checked = data.address.is_default;
                        
                        showModal('edit-address-modal');
                    } else {
                        alert('Error: ' + (data.message || 'Failed to load address'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading address');
                });
        }
        
        function updateAddress(e) {
            e.preventDefault();
            
            const formData = {
                address_id: document.getElementById('edit-address-id').value,
                address_name: document.getElementById('edit-address-name').value,
                street_address: document.getElementById('edit-street-address').value,
                city: document.getElementById('edit-city').value,
                state: document.getElementById('edit-state').value,
                zip_code: document.getElementById('edit-zip-code').value,
                country: document.getElementById('edit-country').value,
                phone: document.getElementById('edit-address-phone').value,
                is_default: document.getElementById('edit-default-address').checked
            };
            
            fetch('update_address.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Address updated successfully!');
                    closeModal('edit-address-modal');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update address'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating address');
            });
        }
        
        function deleteAddress(addressId) {
            if (confirm('Delete this address?')) {
                fetch('delete_address.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ address_id: addressId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Address deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete address'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting address');
                });
            }
        }
        
        // Password change
        document.getElementById('change-password-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }
            
            fetch('change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password changed successfully!');
                    this.reset();
                } else {
                    alert('Error: ' + (data.message || 'Failed to change password'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while changing password');
            });
        });
    </script>
</body>
</html>