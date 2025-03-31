<?php
// Database connection
include 'db.php';

$error = '';
$success = '';

// Check if user is logged in
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: try.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Product
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $description = $_POST['description'];
        $image_path = 'images/' . basename($_FILES['image']['name']);
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock_quantity, description, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $category, $price, $stock, $description, $image_path])) {
                $success = "Product added successfully!";
            } else {
                $error = "Error adding product!";
            }
        } else {
            $error = "Error uploading image!";
        }
    }
    
    // Edit Product
    if (isset($_POST['edit_product'])) {
        $product_id = $_POST['product_id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $description = $_POST['description'];
        
        if (!empty($_FILES['image']['name'])) {
            $image_path = 'images/' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, stock_quantity=?, description=?, image_path=? WHERE product_id=?");
                $stmt->execute([$name, $category, $price, $stock, $description, $image_path, $product_id]);
            } else {
                $error = "Error uploading image!";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, stock_quantity=?, description=? WHERE product_id=?");
            $stmt->execute([$name, $category, $price, $stock, $description, $product_id]);
        }
        
        if ($stmt->rowCount() > 0) {
            $success = "Product updated successfully!";
        } else {
            $error = "Error updating product or no changes made!";
        }
    }
    
    // Delete Product
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        if ($stmt->execute([$product_id])) {
            $success = "Product deleted successfully!";
        } else {
            $error = "Error deleting product!";
        }
    }
    
    // Update Order Status
    if (isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $success = "Order status updated successfully!";
        } else {
            $error = "Error updating order status!";
        }
    }
    
    // Adjust Inventory
    if (isset($_POST['adjust_inventory'])) {
        $product_id = $_POST['product_id'];
        $adjustment = $_POST['adjustment'];
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
        }
        
        if ($stmt->execute([$adjustment, $product_id])) {
            $success = "Inventory adjusted successfully!";
        } else {
            $error = "Error adjusting inventory!";
        }
    }
    
    // Edit Customer
    if (isset($_POST['edit_customer'])) {
        $customer_id = $_POST['customer_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        $stmt = $pdo->prepare("UPDATE customers SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE customer_id=?");
        if ($stmt->execute([$first_name, $last_name, $email, $phone, $address, $customer_id])) {
            $success = "Customer updated successfully!";
        } else {
            $error = "Error updating customer!";
        }
    }
}

// Get product data for editing
$edit_product = null;
if (isset($_GET['edit_product'])) {
    $product_id = $_GET['edit_product'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $edit_product = $stmt->fetch();
}

// Get order data for status update
$edit_order = null;
if (isset($_GET['edit_order'])) {
    $order_id = $_GET['edit_order'];
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $edit_order = $stmt->fetch();
}

// Get inventory data for adjustment
$adjust_inventory = null;
if (isset($_GET['adjust_inventory'])) {
    $product_id = $_GET['adjust_inventory'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $adjust_inventory = $stmt->fetch();
}

// Get customer data for editing
$edit_customer = null;
if (isset($_GET['edit_customer'])) {
    $customer_id = $_GET['edit_customer'];
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $edit_customer = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendsWear - Admin Dashboard</title>
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
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-menu {
            margin-top: 20px;
        }
        
        .menu-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: var(--secondary-color);
        }
        
        .menu-item i {
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .top-bar h1 {
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card .change {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .change.positive {
            color: var(--success-color);
        }
        
        .change.negative {
            color: var(--danger-color);
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
        
        .chart-container {
            height: 300px;
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom-color: var(--accent-color);
            color: var(--accent-color);
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-filter input, .search-filter select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .pagination a:hover {
            background-color: #f8f9fa;
        }
        
        .pagination .active {
            background-color: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
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
        
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        .error {
            color: var(--danger-color);
            margin-bottom: 15px;
        }
        
        .success {
            color: var(--success-color);
            margin-bottom: 15px;
        }
    </style>
    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>
                    <span>TrendsWear</span>
                </h2>
                <p>Admin Dashboard</p>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-item active" onclick="showSection('dashboard')">
                    <i>📊</i>
                    <span>Dashboard</span>
                </div>
                <div class="menu-item" onclick="showSection('orders')">
                    <i>📦</i>
                    <span>Orders</span>
                </div>
                <div class="menu-item" onclick="showSection('products')">
                    <i>👕</i>
                    <span>Products</span>
                </div>
                <div class="menu-item" onclick="showSection('customers')">
                    <i>👥</i>
                    <span>Customers</span>
                </div>
                <div class="menu-item" onclick="showSection('inventory')">
                    <i>📦</i>
                    <span>Inventory</span>
                </div>
                <div class="menu-item" onclick="showSection('reports')">
                    <i>📈</i>
                    <span>Reports</span>
                </div>
                <div class="menu-item" onclick="location.href='admin_logout.php'">
                    <i>🚪</i>
                    <span>Logout</span>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1 id="section-title">Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?></div>
                    <span><?php echo $_SESSION['admin_username']; ?></span>
                </div>
            </div>
            
            <!-- Display success/error messages -->
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="section-content">
                <div class="stats-container">
                    <?php
                    // Get stats data
                    $total_sales = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'Completed'")->fetch()['total'];
                    $total_orders = $pdo->query("SELECT COUNT(*) as total FROM orders")->fetch()['total'];
                    $total_customers = $pdo->query("SELECT COUNT(*) as total FROM customers")->fetch()['total'];
                    $low_stock = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity < 10")->fetch()['total'];
                    ?>
                    <div class="stat-card">
                        <h3>Total Sales</h3>
                        <div class="value">$<?php echo number_format($total_sales, 2); ?></div>
                        <div class="change positive">↑ 12% from last month</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <div class="value"><?php echo $total_orders; ?></div>
                        <div class="change positive">↑ 8% from last month</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Customers</h3>
                        <div class="value"><?php echo $total_customers; ?></div>
                        <div class="change positive">↑ 5% from last month</div>
                    </div>
                    <div class="stat-card">
                        <h3>Low Stock Items</h3>
                        <div class="value"><?php echo $low_stock; ?></div>
                        <div class="change negative">↓ Need restock</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="#" onclick="showSection('orders')" class="btn btn-primary">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_orders = $pdo->query("
                                SELECT o.order_id, o.order_date, o.total_amount, o.status, 
                                       c.first_name, c.last_name 
                                FROM orders o
                                JOIN customers c ON o.customer_id = c.customer_id
                                ORDER BY o.order_date DESC
                                LIMIT 5
                            ");
                            
                            while ($order = $recent_orders->fetch()) {
                                echo "<tr>
                                    <td>#{$order['order_id']}</td>
                                    <td>{$order['first_name']} {$order['last_name']}</td>
                                    <td>" . date('M d, Y', strtotime($order['order_date'])) . "</td>
                                    <td>$" . number_format($order['total_amount'], 2) . "</td>
                                    <td><span class='status {$order['status']}'>" . ucfirst($order['status']) . "</span></td>
                                    <td><a href='#' onclick=\"updateOrderStatus({$order['order_id']})\" class='btn btn-primary'>Update</a></td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Sales Overview</h2>
                        <div>
                            <select id="sales-period" onchange="updateSalesChart()">
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Orders Section -->
            <div id="orders-section" class="section-content" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Order Management</h2>
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
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-body">
                            <?php
                            $orders = $pdo->query("
                                SELECT o.order_id, o.order_date, o.total_amount, o.status, 
                                       c.first_name, c.last_name,
                                       COUNT(oi.item_id) as item_count
                                FROM orders o
                                JOIN customers c ON o.customer_id = c.customer_id
                                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                                GROUP BY o.order_id
                                ORDER BY o.order_date DESC
                                LIMIT 10
                            ");
                            
                            while ($order = $orders->fetch()) {
                                echo "<tr>
                                    <td>#{$order['order_id']}</td>
                                    <td>{$order['first_name']} {$order['last_name']}</td>
                                    <td>" . date('M d, Y', strtotime($order['order_date'])) . "</td>
                                    <td>{$order['item_count']}</td>
                                    <td>$" . number_format($order['total_amount'], 2) . "</td>
                                    <td><span class='status {$order['status']}'>" . ucfirst($order['status']) . "</span></td>
                                    <td>
                                        <a href='#' onclick=\"updateOrderStatus({$order['order_id']})\" class='btn btn-primary'>Update</a>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <a href="#">&laquo;</a>
                        <a href="#" class="active">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <a href="#">&raquo;</a>
                    </div>
                </div>
            </div>
            
            <!-- Products Section -->
            <div id="products-section" class="section-content" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Product Management</h2>
                        <div>
                            <button class="btn btn-primary" onclick="showAddProductModal()">Add Product</button>
                        </div>
                    </div>
                    <div class="search-filter">
                        <input type="text" id="product-search" placeholder="Search products...">
                        <select id="product-category-filter">
                            <option value="">All Categories</option>
                            <option value="Men">Men</option>
                            <option value="Women">Women</option>
                            <option value="Shoes">Shoes</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                        <button class="btn btn-primary" onclick="filterProducts()">Filter</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="products-table-body">
                            <?php
                            $products = $pdo->query("
                                SELECT * FROM products
                                ORDER BY created_at DESC
                                LIMIT 10
                            ");
                            
                            while ($product = $products->fetch()) {
                                echo "<tr>
                                    <td>{$product['product_id']}</td>
                                    <td><img src='{$product['image_path']}' alt='{$product['name']}' style='width:50px;height:50px;object-fit:cover;'></td>
                                    <td>{$product['name']}</td>
                                    <td>{$product['category']}</td>
                                    <td>$" . number_format($product['price'], 2) . "</td>
                                    <td>{$product['stock_quantity']}</td>
                                    <td>
                                        <a href='#' onclick=\"editProduct({$product['product_id']})\" class='btn btn-primary'>Edit</a>
                                        <a href='#' onclick=\"deleteProduct({$product['product_id']})\" class='btn btn-danger'>Delete</a>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <a href="#">&laquo;</a>
                        <a href="#" class="active">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <a href="#">&raquo;</a>
                    </div>
                </div>
            </div>
            
            <!-- Customers Section -->
            <div id="customers-section" class="section-content" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Customer Management</h2>
                        <div class="search-filter">
                            <input type="text" id="customer-search" placeholder="Search customers...">
                            <button class="btn btn-primary" onclick="filterCustomers()">Search</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="customers-table-body">
                            <?php
                            $customers = $pdo->query("
                                SELECT c.*, 
                                       COUNT(o.order_id) as order_count,
                                       SUM(o.total_amount) as total_spent
                                FROM customers c
                                LEFT JOIN orders o ON c.customer_id = o.customer_id
                                GROUP BY c.customer_id
                                ORDER BY c.created_at DESC
                                LIMIT 10
                            ");
                            
                            while ($customer = $customers->fetch()) {
                                $total_spent = $customer['total_spent'] ? number_format($customer['total_spent'], 2) : '0.00';
                                echo "<tr>
                                    <td>{$customer['customer_id']}</td>
                                    <td>{$customer['first_name']} {$customer['last_name']}</td>
                                    <td>{$customer['email']}</td>
                                    <td>{$customer['phone']}</td>
                                    <td>{$customer['order_count']}</td>
                                    <td>$" . $total_spent . "</td>
                                    <td>
                                        <a href='#' onclick=\"editCustomer({$customer['customer_id']})\" class='btn btn-primary'>Edit</a>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <a href="#">&laquo;</a>
                        <a href="#" class="active">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <a href="#">&raquo;</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Customer Insights</h2>
                    </div>
                    <div class="tabs">
                        <div class="tab active" onclick="switchCustomerTab('location')">Location</div>
                        <div class="tab" onclick="switchCustomerTab('activity')">Activity</div>
                        <div class="tab" onclick="switchCustomerTab('loyalty')">Loyalty</div>
                    </div>
                    
                    <div class="tab-content active" id="location-tab">
                        <div class="chart-container">
                            <canvas id="customerLocationChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="activity-tab">
                        <div class="chart-container">
                            <canvas id="customerActivityChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="loyalty-tab">
                        <div class="chart-container">
                            <canvas id="customerLoyaltyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Inventory Section -->
            <div id="inventory-section" class="section-content" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Inventory Management</h2>
                        <div>
                            <button class="btn btn-primary" onclick="showInventoryModal()">Add Stock</button>
                        </div>
                    </div>
                    <div class="search-filter">
                        <input type="text" id="inventory-search" placeholder="Search inventory...">
                        <select id="inventory-status-filter">
                            <option value="">All Items</option>
                            <option value="low">Low Stock (<10)</option>
                            <option value="out">Out of Stock</option>
                        </select>
                        <button class="btn btn-primary" onclick="filterInventory()">Filter</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Last Updated</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-table-body">
                            <?php
                            $inventory = $pdo->query("
                                SELECT p.product_id, p.name, p.category, p.stock_quantity, p.updated_at
                                FROM products p
                                ORDER BY p.stock_quantity ASC, p.updated_at DESC
                                LIMIT 10
                            ");
                            
                            while ($item = $inventory->fetch()) {
                                $status = '';
                                if ($item['stock_quantity'] == 0) {
                                    $status = '<span class="status cancelled">Out of Stock</span>';
                                } elseif ($item['stock_quantity'] < 10) {
                                    $status = '<span class="status pending">Low Stock</span>';
                                } else {
                                    $status = '<span class="status completed">In Stock</span>';
                                }
                                
                                echo "<tr>
                                    <td>{$item['product_id']}</td>
                                    <td>{$item['name']}</td>
                                    <td>{$item['category']}</td>
                                    <td>{$item['stock_quantity']}</td>
                                    <td>" . date('M d, Y', strtotime($item['updated_at'])) . "</td>
                                    <td>{$status}</td>
                                    <td>
                                        <a href='#' onclick=\"adjustInventory({$item['product_id']})\" class='btn btn-primary'>Adjust</a>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <a href="#">&laquo;</a>
                        <a href="#" class="active">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <a href="#">&raquo;</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Inventory Reports</h2>
                    </div>
                    <div class="tabs">
                        <div class="tab active" onclick="switchInventoryTab('stock-levels')">Stock Levels</div>
                        <div class="tab" onclick="switchInventoryTab('movement')">Movement</div>
                        <div class="tab" onclick="switchInventoryTab('valuation')">Valuation</div>
                    </div>
                    
                    <div class="tab-content active" id="stock-levels-tab">
                        <div class="chart-container">
                            <canvas id="inventoryLevelsChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="movement-tab">
                        <div class="chart-container">
                            <canvas id="inventoryMovementChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="valuation-tab">
                        <div class="chart-container">
                            <canvas id="inventoryValuationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reports Section -->
            <div id="reports-section" class="section-content" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h2>Sales Reports</h2>
                        <div>
                            <button class="btn btn-primary" onclick="exportSalesReport()">Export Report</button>
                        </div>
                    </div>
                    <div class="search-filter">
                        <input type="date" id="report-start-date">
                        <input type="date" id="report-end-date">
                        <select id="report-type">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly" selected>Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                        <button class="btn btn-primary" onclick="generateReport()">Generate</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesReportChart"></canvas>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Avg. Order Value</th>
                                <th>New Customers</th>
                            </tr>
                        </thead>
                        <tbody id="sales-report-data">
                            <!-- Report data will be inserted here by JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Order Volume Reports</h2>
                    </div>
                    <div class="tabs">
                        <div class="tab active" onclick="switchOrderTab('volume')">Volume</div>
                        <div class="tab" onclick="switchOrderTab('status')">Status</div>
                        <div class="tab" onclick="switchOrderTab('time')">Time Analysis</div>
                    </div>
                    
                    <div class="tab-content active" id="volume-tab">
                        <div class="chart-container">
                            <canvas id="orderVolumeChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="status-tab">
                        <div class="chart-container">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="time-tab">
                        <div class="chart-container">
                            <canvas id="orderTimeChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Product Performance</h2>
                    </div>
                    <div class="search-filter">
                        <select id="product-report-category">
                            <option value="">All Categories</option>
                            <option value="Men">Men</option>
                            <option value="Women">Women</option>
                            <option value="Shoes">Shoes</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                        <button class="btn btn-primary" onclick="generateProductReport()">Generate</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="productPerformanceChart"></canvas>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody id="product-report-data">
                            <!-- Product report data will be inserted here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addProductModal')">&times;</span>
            <h2>Add New Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_product" value="1">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="Men">Men</option>
                        <option value="Women">Women</option>
                        <option value="Shoes">Shoes</option>
                        <option value="Accessories">Accessories</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stock Quantity</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('addProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editProductModal')">&times;</span>
            <h2>Edit Product</h2>
            <?php if ($edit_product): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_product" value="1">
                <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                <div class="form-group">
                    <label for="edit_name">Product Name</label>
                    <input type="text" id="edit_name" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="category" required>
                        <option value="Men" <?php echo $edit_product['category'] == 'Men' ? 'selected' : ''; ?>>Men</option>
                        <option value="Women" <?php echo $edit_product['category'] == 'Women' ? 'selected' : ''; ?>>Women</option>
                        <option value="Shoes" <?php echo $edit_product['category'] == 'Shoes' ? 'selected' : ''; ?>>Shoes</option>
                        <option value="Accessories" <?php echo $edit_product['category'] == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_price">Price</label>
                    <input type="number" id="edit_price" name="price" step="0.01" value="<?php echo $edit_product['price']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_stock">Stock Quantity</label>
                    <input type="number" id="edit_stock" name="stock" value="<?php echo $edit_product['stock_quantity']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_image">Product Image (Leave blank to keep current)</label>
                    <input type="file" id="edit_image" name="image">
                    <p>Current image: <?php echo basename($edit_product['image_path']); ?></p>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('editProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Product Modal -->
    <div id="deleteProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteProductModal')">&times;</span>
            <h2>Delete Product</h2>
            <p>Are you sure you want to delete this product?</p>
            <form method="POST">
                <input type="hidden" name="delete_product" value="1">
                <input type="hidden" name="product_id" id="delete_product_id" value="">
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="closeModal('deleteProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Order Status Modal -->
    <div id="updateOrderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateOrderModal')">&times;</span>
            <h2>Update Order Status</h2>
            <?php if ($edit_order): ?>
            <form method="POST">
                <input type="hidden" name="update_order_status" value="1">
                <input type="hidden" name="order_id" value="<?php echo $edit_order['order_id']; ?>">
                <div class="form-group">
                    <label>Order ID: #<?php echo $edit_order['order_id']; ?></label>
                </div>
                <div class="form-group">
                    <label>Current Status: <?php echo ucfirst($edit_order['status']); ?></label>
                </div>
                <div class="form-group">
                    <label for="status">New Status</label>
                    <select id="status" name="status" required>
                        <option value="Pending" <?php echo $edit_order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $edit_order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Shipped" <?php echo $edit_order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="Completed" <?php echo $edit_order['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $edit_order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('updateOrderModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Adjust Inventory Modal -->
    <div id="adjustInventoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('adjustInventoryModal')">&times;</span>
            <h2>Adjust Inventory</h2>
            <?php if ($adjust_inventory): ?>
            <form method="POST">
                <input type="hidden" name="adjust_inventory" value="1">
                <input type="hidden" name="product_id" value="<?php echo $adjust_inventory['product_id']; ?>">
                <div class="form-group">
                    <label>Product: <?php echo htmlspecialchars($adjust_inventory['name']); ?></label>
                </div>
                <div class="form-group">
                    <label>Current Stock: <?php echo $adjust_inventory['stock_quantity']; ?></label>
                </div>
                <div class="form-group">
                    <label for="action">Action</label>
                    <select id="action" name="action" required>
                        <option value="add">Add Stock</option>
                        <option value="subtract">Subtract Stock</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="adjustment">Quantity</label>
                    <input type="number" id="adjustment" name="adjustment" min="1" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('adjustInventoryModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Inventory</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Customer Modal -->
    <div id="editCustomerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editCustomerModal')">&times;</span>
            <h2>Edit Customer</h2>
            <?php if ($edit_customer): ?>
            <form method="POST">
                <input type="hidden" name="edit_customer" value="1">
                <input type="hidden" name="customer_id" value="<?php echo $edit_customer['customer_id']; ?>">
                <div class="form-group">
                    <label for="edit_first_name">First Name</label>
                    <input type="text" id="edit_first_name" name="first_name" value="<?php echo htmlspecialchars($edit_customer['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_last_name">Last Name</label>
                    <input type="text" id="edit_last_name" name="last_name" value="<?php echo htmlspecialchars($edit_customer['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" value="<?php echo htmlspecialchars($edit_customer['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" id="edit_phone" name="phone" value="<?php echo htmlspecialchars($edit_customer['phone']); ?>">
                </div>
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <textarea id="edit_address" name="address" rows="3"><?php echo htmlspecialchars($edit_customer['address']); ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('editCustomerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Function to switch between sections
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.section-content').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            
            // Update title
            const titles = {
                'dashboard': 'Dashboard',
                'orders': 'Order Management',
                'products': 'Product Management',
                'customers': 'Customer Management',
                'inventory': 'Inventory Management',
                'reports': 'Reports'
            };
            document.getElementById('section-title').textContent = titles[section];
            
            // Update active menu item
            document.querySelectorAll('.menu-item').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`.menu-item[onclick="showSection('${section}')"]`).classList.add('active');
            
            // Initialize charts if needed
            if (section === 'dashboard') {
                initSalesChart();
            } else if (section === 'customers') {
                initCustomerCharts();
            } else if (section === 'inventory') {
                initInventoryCharts();
            } else if (section === 'reports') {
                initReportCharts();
            }
        }
        
        // Initialize sales chart
        function initSalesChart() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            window.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Sales',
                        data: [12000, 19000, 15000, 18000, 21000, 19000, 23000],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Update sales chart based on selected period
        function updateSalesChart() {
            const period = document.getElementById('sales-period').value;
            // In a real app, you would fetch data based on the period
            // This is just a simulation
            let labels, data;
            
            if (period === '7') {
                labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                data = [1200, 1900, 1500, 1800, 2100, 2500, 2300];
            } else if (period === '30') {
                labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                data = [5200, 6900, 7500, 8800];
            } else if (period === '90') {
                labels = ['Month 1', 'Month 2', 'Month 3'];
                data = [22000, 25000, 28000];
            } else {
                labels = ['Q1', 'Q2', 'Q3', 'Q4'];
                data = [65000, 72000, 68000, 81000];
            }
            
            window.salesChart.data.labels = labels;
            window.salesChart.data.datasets[0].data = data;
            window.salesChart.update();
        }
        
        // Initialize customer charts
        function initCustomerCharts() {
            // Customer Location Chart
            const locationCtx = document.getElementById('customerLocationChart').getContext('2d');
            window.customerLocationChart = new Chart(locationCtx, {
                type: 'bar',
                data: {
                    labels: ['USA', 'UK', 'Canada', 'Australia', 'Germany', 'France'],
                    datasets: [{
                        label: 'Customers by Country',
                        data: [120, 85, 45, 30, 55, 40],
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(155, 89, 182, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(241, 196, 15, 0.7)',
                            'rgba(230, 126, 34, 0.7)',
                            'rgba(231, 76, 60, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Customer Activity Chart
            const activityCtx = document.getElementById('customerActivityChart').getContext('2d');
            window.customerActivityChart = new Chart(activityCtx, {
                type: 'doughnut',
                data: {
                    labels: ['New (1 order)', 'Returning (2-5 orders)', 'Frequent (5+ orders)'],
                    datasets: [{
                        data: [120, 85, 45],
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(155, 89, 182, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Customer Loyalty Chart
            const loyaltyCtx = document.getElementById('customerLoyaltyChart').getContext('2d');
            window.customerLoyaltyChart = new Chart(loyaltyCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Repeat Customers',
                        data: [15, 22, 18, 25, 30, 28, 35],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true
                    }, {
                        label: 'New Customers',
                        data: [25, 30, 22, 28, 35, 40, 45],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        // Initialize inventory charts
        function initInventoryCharts() {
            // Inventory Levels Chart
            const levelsCtx = document.getElementById('inventoryLevelsChart').getContext('2d');
            window.inventoryLevelsChart = new Chart(levelsCtx, {
                type: 'bar',
                data: {
                    labels: ['Men', 'Women', 'Shoes', 'Accessories'],
                    datasets: [{
                        label: 'In Stock',
                        data: [120, 85, 45, 30],
                        backgroundColor: 'rgba(46, 204, 113, 0.7)'
                    }, {
                        label: 'Low Stock',
                        data: [15, 10, 5, 8],
                        backgroundColor: 'rgba(241, 196, 15, 0.7)'
                    }, {
                        label: 'Out of Stock',
                        data: [5, 3, 2, 4],
                        backgroundColor: 'rgba(231, 76, 60, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true
                        }
                    }
                }
            });
            
            // Inventory Movement Chart
            const movementCtx = document.getElementById('inventoryMovementChart').getContext('2d');
            window.inventoryMovementChart = new Chart(movementCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Stock In',
                        data: [120, 190, 150, 180, 210, 190, 230],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true
                    }, {
                        label: 'Stock Out',
                        data: [100, 170, 130, 160, 190, 170, 210],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Inventory Valuation Chart
            const valuationCtx = document.getElementById('inventoryValuationChart').getContext('2d');
            window.inventoryValuationChart = new Chart(valuationCtx, {
                type: 'pie',
                data: {
                    labels: ['Men', 'Women', 'Shoes', 'Accessories'],
                    datasets: [{
                        data: [12000, 8500, 4500, 3000],
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(155, 89, 182, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(241, 196, 15, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        // Initialize report charts
        function initReportCharts() {
            // Sales Report Chart
            const salesReportCtx = document.getElementById('salesReportChart').getContext('2d');
            window.salesReportChart = new Chart(salesReportCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Revenue',
                        data: [12000, 19000, 15000, 18000, 21000, 19000, 23000],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        yAxisID: 'y'
                    }, {
                        label: 'Orders',
                        data: [120, 190, 150, 180, 210, 190, 230],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Orders'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            
            // Order Volume Chart
            const orderVolumeCtx = document.getElementById('orderVolumeChart').getContext('2d');
            window.orderVolumeChart = new Chart(orderVolumeCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Orders',
                        data: [120, 190, 150, 180, 210, 190, 230],
                        backgroundColor: 'rgba(52, 152, 219, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Order Status Chart
            const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
            window.orderStatusChart = new Chart(orderStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['Completed', 'Shipped', 'Processing', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [350, 120, 80, 50, 30],
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(241, 196, 15, 0.7)',
                            'rgba(230, 126, 34, 0.7)',
                            'rgba(231, 76, 60, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Order Time Chart
            const orderTimeCtx = document.getElementById('orderTimeChart').getContext('2d');
            window.orderTimeChart = new Chart(orderTimeCtx, {
                type: 'line',
                data: {
                    labels: ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM'],
                    datasets: [{
                        label: 'Orders by Time of Day',
                        data: [10, 5, 8, 35, 45, 50, 30, 25],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // Product Performance Chart
            const productPerfCtx = document.getElementById('productPerformanceChart').getContext('2d');
            window.productPerformanceChart = new Chart(productPerfCtx, {
                type: 'bar',
                data: {
                    labels: ['T-Shirts', 'Pants', 'Dresses', 'Shoes', 'Accessories'],
                    datasets: [{
                        label: 'Units Sold',
                        data: [350, 220, 180, 150, 120],
                        backgroundColor: 'rgba(52, 152, 219, 0.7)'
                    }, {
                        label: 'Revenue',
                        data: [10500, 8800, 9000, 10500, 8400],
                        backgroundColor: 'rgba(46, 204, 113, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Tab switching functions
        function switchCustomerTab(tab) {
            document.querySelectorAll('#customers-section .tab').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`#customers-section .tab[onclick="switchCustomerTab('${tab}')"]`).classList.add('active');
            
            document.querySelectorAll('#customers-section .tab-content').forEach(el => {
                el.classList.remove('active');
            });
            document.getElementById(`${tab}-tab`).classList.add('active');
        }
        
        function switchInventoryTab(tab) {
            document.querySelectorAll('#inventory-section .tab').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`#inventory-section .tab[onclick="switchInventoryTab('${tab}')"]`).classList.add('active');
            
            document.querySelectorAll('#inventory-section .tab-content').forEach(el => {
                el.classList.remove('active');
            });
            document.getElementById(`${tab}-tab`).classList.add('active');
        }
        
        function switchOrderTab(tab) {
            document.querySelectorAll('#reports-section .tab').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`#reports-section .tab[onclick="switchOrderTab('${tab}')"]`).classList.add('active');
            
            document.querySelectorAll('#reports-section .tab-content').forEach(el => {
                el.classList.remove('active');
            });
            document.getElementById(`${tab}-tab`).classList.add('active');
        }
        
        // Filter functions
        function filterOrders() {
            const searchTerm = document.getElementById('order-search').value.toLowerCase();
            const statusFilter = document.getElementById('order-status-filter').value;
            
            // In a real app, you would make an AJAX call to filter orders
            // This is just a simulation
            alert(`Filtering orders with search: ${searchTerm} and status: ${statusFilter}`);
        }
        
        function filterProducts() {
            const searchTerm = document.getElementById('product-search').value.toLowerCase();
            const categoryFilter = document.getElementById('product-category-filter').value;
            
            // In a real app, you would make an AJAX call to filter products
            alert(`Filtering products with search: ${searchTerm} and category: ${categoryFilter}`);
        }
        
        function filterCustomers() {
            const searchTerm = document.getElementById('customer-search').value.toLowerCase();
            
            // In a real app, you would make an AJAX call to filter customers
            alert(`Filtering customers with search: ${searchTerm}`);
        }
        
        function filterInventory() {
            const searchTerm = document.getElementById('inventory-search').value.toLowerCase();
            const statusFilter = document.getElementById('inventory-status-filter').value;
            
            // In a real app, you would make an AJAX call to filter inventory
            alert(`Filtering inventory with search: ${searchTerm} and status: ${statusFilter}`);
        }
        
        // Generate reports
        function generateReport() {
            const startDate = document.getElementById('report-start-date').value;
            const endDate = document.getElementById('report-end-date').value;
            const reportType = document.getElementById('report-type').value;
            
            // In a real app, you would make an AJAX call to generate the report
            alert(`Generating ${reportType} report from ${startDate} to ${endDate}`);
            
            // Simulate updating the report data
            document.getElementById('sales-report-data').innerHTML = `
                <tr>
                    <td>January 2023</td>
                    <td>120</td>
                    <td>$12,000.00</td>
                    <td>$100.00</td>
                    <td>25</td>
                </tr>
                <tr>
                    <td>February 2023</td>
                    <td>150</td>
                    <td>$15,000.00</td>
                    <td>$100.00</td>
                    <td>30</td>
                </tr>
                <tr>
                    <td>March 2023</td>
                    <td>180</td>
                    <td>$18,000.00</td>
                    <td>$100.00</td>
                    <td>35</td>
                </tr>
            `;
        }
        
        function generateProductReport() {
            const category = document.getElementById('product-report-category').value;
            
            // In a real app, you would make an AJAX call to generate the product report
            alert(`Generating product performance report for category: ${category}`);
            
            // Simulate updating the product report data
            document.getElementById('product-report-data').innerHTML = `
                <tr>
                    <td>Men's T-Shirt</td>
                    <td>Men</td>
                    <td>120</td>
                    <td>$3,600.00</td>
                    <td>15%</td>
                </tr>
                <tr>
                    <td>Women's Dress</td>
                    <td>Women</td>
                    <td>85</td>
                    <td>$4,250.00</td>
                    <td>12%</td>
                </tr>
                <tr>
                    <td>Running Shoes</td>
                    <td>Shoes</td>
                    <td>65</td>
                    <td>$3,900.00</td>
                    <td>10%</td>
                </tr>
            `;
        }
        
        // Export functions
        function exportSalesReport() {
            alert('Exporting sales report to CSV');
            // In a real app, you would generate and download a CSV file
        }
        
        // View details functions
        function viewOrderDetails(orderId) {
            alert(`Viewing details for order #${orderId}`);
            // In a real app, you would show a modal with order details
        }
        
        // Modal functions
        function showAddProductModal() {
            document.getElementById('addProductModal').style.display = 'block';
        }
        
        function editProduct(productId) {
            window.location.href = '?edit_product=' + productId + '#products-section';
            document.getElementById('editProductModal').style.display = 'block';
        }
        
        function deleteProduct(productId) {
            document.getElementById('delete_product_id').value = productId;
            document.getElementById('deleteProductModal').style.display = 'block';
        }
        
        function updateOrderStatus(orderId) {
            window.location.href = '?edit_order=' + orderId + '#orders-section';
            document.getElementById('updateOrderModal').style.display = 'block';
        }
        
        function editCustomer(customerId) {
            window.location.href = '?edit_customer=' + customerId + '#customers-section';
            document.getElementById('editCustomerModal').style.display = 'block';
        }
        
        function adjustInventory(productId) {
            window.location.href = '?adjust_inventory=' + productId + '#inventory-section';
            document.getElementById('adjustInventoryModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Remove the edit parameters from URL
            if (window.location.href.includes('edit_product') || 
                window.location.href.includes('edit_order') || 
                window.location.href.includes('adjust_inventory') ||
                window.location.href.includes('edit_customer')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
        
        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Show modal if there are edit parameters in URL
            if (window.location.href.includes('edit_product')) {
                document.getElementById('editProductModal').style.display = 'block';
            }
            if (window.location.href.includes('edit_order')) {
                document.getElementById('updateOrderModal').style.display = 'block';
            }
            if (window.location.href.includes('adjust_inventory')) {
                document.getElementById('adjustInventoryModal').style.display = 'block';
            }
            if (window.location.href.includes('edit_customer')) {
                document.getElementById('editCustomerModal').style.display = 'block';
            }
            
            // Initialize charts
            initSalesChart();
            
            // Set default dates for reports
            const today = new Date();
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
            
            document.getElementById('report-start-date').valueAsDate = oneMonthAgo;
            document.getElementById('report-end-date').valueAsDate = today;
        });
    </script>
</body>
</html>