<?php
// Database connection
include 'db.php';

// Check if user is logged in

// Get username from session
$username = $_SESSION['admin_username'] ?? 'Admin';

// Get stats data
$total_sales = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'Completed'")->fetch()['total'] ?? 0;
$total_orders = $pdo->query("SELECT COUNT(*) as total FROM orders")->fetch()['total'] ?? 0;
$total_customers = $pdo->query("SELECT COUNT(*) as total FROM customers")->fetch()['total'] ?? 0;
$low_stock = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity < 10")->fetch()['total'] ?? 0;

// Get recent orders
$recent_orders = $pdo->query("
    SELECT o.order_id, o.order_date, o.total_amount, o.status, 
           c.first_name, c.last_name 
    FROM orders o
    JOIN customers c ON o.customer_id = c.customer_id
    ORDER BY o.order_date DESC
    LIMIT 5
");

// Get orders for order management
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

// Get products
$products = $pdo->query("
    SELECT * FROM products
    ORDER BY created_at DESC
    LIMIT 10
");

// Get customers
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

// Get inventory
$inventory = $pdo->query("
    SELECT p.product_id, p.name, p.category, p.stock_quantity, p.updated_at
    FROM products p
    ORDER BY p.stock_quantity ASC, p.updated_at DESC
    LIMIT 10
");
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 60%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        
        .close-btn {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .modal h2 {
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        /* Form Styles */
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
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Font Awesome icons */
        .fas {
            width: 24px;
            text-align: center;
        }
    </style>
    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>
                    <i class="fas fa-tshirt"></i>
                    <span>TrendsWear</span>
                </h2>
                <p>Admin Dashboard</p>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-item active" onclick="showSection('dashboard')">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </div>
                <div class="menu-item" onclick="showSection('orders')">
                    <i class="fas fa-boxes"></i>
                    <span>Orders</span>
                </div>
                <div class="menu-item" onclick="showSection('products')">
                    <i class="fas fa-tshirt"></i>
                    <span>Products</span>
                </div>
                <div class="menu-item" onclick="showSection('customers')">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </div>
                <div class="menu-item" onclick="showSection('inventory')">
                    <i class="fas fa-warehouse"></i>
                    <span>Inventory</span>
                </div>
                <div class="menu-item" onclick="showSection('reports')">
                    <i class="fas fa-chart-pie"></i>
                    <span>Reports</span>
                </div>
                <div class="menu-item" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1 id="section-title">Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="section-content">
                <div class="stats-container">
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
                            <?php while ($order = $recent_orders->fetch()): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><span class="status <?php echo strtolower($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                    <td><a href="#" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)" class="btn btn-primary">View</a></td>
                                </tr>
                            <?php endwhile; ?>
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
                            <?php while ($order = $orders->fetch()): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo $order['item_count']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><span class="status <?php echo strtolower($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                    <td>
                                        <a href="#" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)" class="btn btn-primary">View</a>
                                        <a href="#" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>)" class="btn btn-primary">Update</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
                            <?php while ($product = $products->fetch()): ?>
                                <tr>
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td><img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:50px;height:50px;object-fit:cover;"></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <a href="#" onclick="editProduct(<?php echo $product['product_id']; ?>)" class="btn btn-primary">Edit</a>
                                        <a href="#" onclick="deleteProduct(<?php echo $product['product_id']; ?>)" class="btn btn-danger">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
                            <?php while ($customer = $customers->fetch()): ?>
                                <?php $total_spent = $customer['total_spent'] ? number_format($customer['total_spent'], 2) : '0.00'; ?>
                                <tr>
                                    <td><?php echo $customer['customer_id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo $customer['order_count']; ?></td>
                                    <td>$<?php echo $total_spent; ?></td>
                                    <td>
                                        <a href="#" onclick="viewCustomerDetails(<?php echo $customer['customer_id']; ?>)" class="btn btn-primary">View</a>
                                        <a href="#" onclick="editCustomer(<?php echo $customer['customer_id']; ?>)" class="btn btn-primary">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
                            <?php while ($item = $inventory->fetch()): ?>
                                <?php
                                $status = '';
                                if ($item['stock_quantity'] == 0) {
                                    $status = '<span class="status cancelled">Out of Stock</span>';
                                } elseif ($item['stock_quantity'] < 10) {
                                    $status = '<span class="status pending">Low Stock</span>';
                                } else {
                                    $status = '<span class="status completed">In Stock</span>';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $item['product_id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo $item['stock_quantity']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></td>
                                    <td><?php echo $status; ?></td>
                                    <td>
                                        <a href="#" onclick="adjustInventory(<?php echo $item['product_id']; ?>)" class="btn btn-primary">Adjust</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
    
    <!-- Modals -->
    <div id="order-details-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('order-details-modal')">&times;</span>
            <h2>Order Details</h2>
            <div id="order-details-content"></div>
        </div>
    </div>
    
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('product-modal')">&times;</span>
            <h2 id="product-modal-title">Add Product</h2>
            <div id="product-modal-content">
                <!-- Form will be loaded here -->
            </div>
        </div>
    </div>
    
    <div id="inventory-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('inventory-modal')">&times;</span>
            <h2>Adjust Inventory</h2>
            <div id="inventory-modal-content">
                <!-- Form will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        // Global variables
        let currentOrderId = null;
        let currentProductId = null;

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts
            initSalesChart();
            
            // Set default dates for reports
            const today = new Date();
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
            
            document.getElementById('report-start-date').valueAsDate = oneMonthAgo;
            document.getElementById('report-end-date').valueAsDate = today;
            
            // Add event listeners
            document.getElementById('order-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterOrders();
            });
            
            document.getElementById('product-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterProducts();
            });
            
            document.getElementById('customer-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterCustomers();
            });
            
            document.getElementById('inventory-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterInventory();
            });
        });

        // Section navigation
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.section-content').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected section
            const sectionElement = document.getElementById(section + '-section');
            if (sectionElement) {
                sectionElement.style.display = 'block';
            }
            
            // Update title
            const titles = {
                'dashboard': 'Dashboard',
                'orders': 'Order Management',
                'products': 'Product Management',
                'customers': 'Customer Management',
                'inventory': 'Inventory Management',
                'reports': 'Reports'
            };
            
            const titleElement = document.getElementById('section-title');
            if (titleElement && titles[section]) {
                titleElement.textContent = titles[section];
            }
            
            // Update active menu item
            document.querySelectorAll('.menu-item').forEach(el => {
                el.classList.remove('active');
            });
            
            const activeMenuItem = document.querySelector(`.menu-item[onclick*="showSection('${section}')"]`);
            if (activeMenuItem) {
                activeMenuItem.classList.add('active');
            }
            
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

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Order functions
        function viewOrderDetails(orderId) {
            currentOrderId = orderId;
            
            // Simulate AJAX call - in real app you would fetch from server
            const orderDetails = {
                order_id: orderId,
                customer_name: "John Doe",
                order_date: "May 15, 2023",
                status: "Processing",
                total_amount: "125.99",
                items: [
                    { product_name: "Men's T-Shirt", quantity: 2, price: "25.00", total: "50.00" },
                    { product_name: "Jeans", quantity: 1, price: "75.99", total: "75.99" }
                ]
            };
            
            document.getElementById('order-details-content').innerHTML = `
                <div class="order-info">
                    <p><strong>Order ID:</strong> #${orderDetails.order_id}</p>
                    <p><strong>Customer:</strong> ${orderDetails.customer_name}</p>
                    <p><strong>Date:</strong> ${orderDetails.order_date}</p>
                    <p><strong>Status:</strong> <span class="status ${orderDetails.status.toLowerCase()}">${orderDetails.status}</span></p>
                    <p><strong>Total:</strong> $${orderDetails.total_amount}</p>
                </div>
                <div class="order-items">
                    <h3>Items</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${orderDetails.items.map(item => `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>$${item.price}</td>
                                    <td>$${item.total}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            openModal('order-details-modal');
        }

        function updateOrderStatus(orderId) {
            currentOrderId = orderId;
            
            // Simulate getting current status - in real app you would fetch from server
            const currentStatus = "Processing";
            
            document.getElementById('order-details-content').innerHTML = `
                <form id="update-status-form">
                    <div class="form-group">
                        <label for="order-status">Status</label>
                        <select id="order-status" name="status">
                            <option value="Pending" ${currentStatus === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Processing" ${currentStatus === 'Processing' ? 'selected' : ''}>Processing</option>
                            <option value="Shipped" ${currentStatus === 'Shipped' ? 'selected' : ''}>Shipped</option>
                            <option value="Completed" ${currentStatus === 'Completed' ? 'selected' : ''}>Completed</option>
                            <option value="Cancelled" ${currentStatus === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="saveOrderStatus()">Save</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal('order-details-modal')">Cancel</button>
                    </div>
                </form>
            `;
            openModal('order-details-modal');
        }

        function saveOrderStatus() {
            const status = document.getElementById('order-status').value;
            
            // Simulate AJAX call - in real app you would send to server
            setTimeout(() => {
                alert('Order status updated successfully to: ' + status);
                closeModal('order-details-modal');
                // In a real app, you would refresh the orders table
            }, 500);
        }

        // Product functions
        function showAddProductModal() {
            currentProductId = null;
            document.getElementById('product-modal-title').textContent = 'Add Product';
            
            document.getElementById('product-modal-content').innerHTML = `
                <form id="product-form">
                    <div class="form-group">
                        <label for="product-name">Product Name</label>
                        <input type="text" id="product-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" name="category" required>
                            <option value="Men">Men</option>
                            <option value="Women">Women</option>
                            <option value="Shoes">Shoes</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product-price">Price</label>
                        <input type="number" id="product-price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-stock">Stock Quantity</label>
                        <input type="number" id="product-stock" name="stock_quantity" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-description">Description</label>
                        <textarea id="product-description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="product-image">Image URL</label>
                        <input type="text" id="product-image" name="image_path">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="saveProduct()">Save</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal('product-modal')">Cancel</button>
                    </div>
                </form>
            `;
            openModal('product-modal');
        }

        function editProduct(productId) {
            currentProductId = productId;
            document.getElementById('product-modal-title').textContent = 'Edit Product';
            
            // Simulate getting product data - in real app you would fetch from server
            const productData = {
                product_id: productId,
                name: "Men's T-Shirt",
                category: "Men",
                price: "25.00",
                stock_quantity: "50",
                description: "High quality cotton t-shirt for men",
                image_path: "images/mens-tshirt.jpg"
            };
            
            document.getElementById('product-modal-content').innerHTML = `
                <form id="product-form">
                    <div class="form-group">
                        <label for="product-name">Product Name</label>
                        <input type="text" id="product-name" name="name" value="${productData.name}" required>
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" name="category" required>
                            <option value="Men" ${productData.category === 'Men' ? 'selected' : ''}>Men</option>
                            <option value="Women" ${productData.category === 'Women' ? 'selected' : ''}>Women</option>
                            <option value="Shoes" ${productData.category === 'Shoes' ? 'selected' : ''}>Shoes</option>
                            <option value="Accessories" ${productData.category === 'Accessories' ? 'selected' : ''}>Accessories</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product-price">Price</label>
                        <input type="number" id="product-price" name="price" step="0.01" min="0" value="${productData.price}" required>
                    </div>
                    <div class="form-group">
                        <label for="product-stock">Stock Quantity</label>
                        <input type="number" id="product-stock" name="stock_quantity" min="0" value="${productData.stock_quantity}" required>
                    </div>
                    <div class="form-group">
                        <label for="product-description">Description</label>
                        <textarea id="product-description" name="description">${productData.description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="product-image">Image URL</label>
                        <input type="text" id="product-image" name="image_path" value="${productData.image_path || ''}">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="saveProduct()">Save</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal('product-modal')">Cancel</button>
                    </div>
                </form>
            `;
            openModal('product-modal');
        }

        function saveProduct() {
            // Simulate saving product - in real app you would send to server
            setTimeout(() => {
                alert('Product saved successfully');
                closeModal('product-modal');
                // In a real app, you would refresh the products table
            }, 500);
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                // Simulate deleting product - in real app you would send to server
                setTimeout(() => {
                    alert('Product deleted successfully');
                    // In a real app, you would refresh the products table
                }, 500);
            }
        }

        // Inventory functions
        function adjustInventory(productId) {
            currentProductId = productId;
            
            // Simulate getting product data - in real app you would fetch from server
            const productData = {
                product_id: productId,
                name: "Men's T-Shirt",
                stock_quantity: 5
            };
            
            document.getElementById('inventory-modal-content').innerHTML = `
                <form id="inventory-form">
                    <div class="form-group">
                        <label>Product</label>
                        <p>${productData.name}</p>
                    </div>
                    <div class="form-group">
                        <label>Current Stock</label>
                        <p>${productData.stock_quantity}</p>
                    </div>
                    <div class="form-group">
                        <label for="adjustment-type">Adjustment Type</label>
                        <select id="adjustment-type" name="adjustment_type">
                            <option value="add">Add Stock</option>
                            <option value="subtract">Subtract Stock</option>
                            <option value="set">Set Exact Quantity</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="adjustment-quantity">Quantity</label>
                        <input type="number" id="adjustment-quantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="adjustment-reason">Reason</label>
                        <input type="text" id="adjustment-reason" name="reason">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="saveInventoryAdjustment()">Save</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal('inventory-modal')">Cancel</button>
                    </div>
                </form>
            `;
            openModal('inventory-modal');
        }

        function saveInventoryAdjustment() {
            // Simulate saving inventory adjustment - in real app you would send to server
            setTimeout(() => {
                alert('Inventory adjusted successfully');
                closeModal('inventory-modal');
                // In a real app, you would refresh the inventory table
            }, 500);
        }

        // Filter functions
        function filterOrders() {
            const searchTerm = document.getElementById('order-search').value.toLowerCase();
            const statusFilter = document.getElementById('order-status-filter').value;
            
            // Simulate filtering - in real app you would fetch from server
            alert(`Filtering orders with search: ${searchTerm} and status: ${statusFilter}`);
        }
        
        function filterProducts() {
            const searchTerm = document.getElementById('product-search').value.toLowerCase();
            const categoryFilter = document.getElementById('product-category-filter').value;
            
            // Simulate filtering - in real app you would fetch from server
            alert(`Filtering products with search: ${searchTerm} and category: ${categoryFilter}`);
        }
        
        function filterCustomers() {
            const searchTerm = document.getElementById('customer-search').value.toLowerCase();
            
            // Simulate filtering - in real app you would fetch from server
            alert(`Filtering customers with search: ${searchTerm}`);
        }
        
        function filterInventory() {
            const searchTerm = document.getElementById('inventory-search').value.toLowerCase();
            const statusFilter = document.getElementById('inventory-status-filter').value;
            
            // Simulate filtering - in real app you would fetch from server
            alert(`Filtering inventory with search: ${searchTerm} and status: ${statusFilter}`);
        }

        // Report functions
        function generateReport() {
            const startDate = document.getElementById('report-start-date').value;
            const endDate = document.getElementById('report-end-date').value;
            const reportType = document.getElementById('report-type').value;
            
            // Simulate generating report - in real app you would fetch from server
            alert(`Generating ${reportType} report from ${startDate} to ${endDate}`);
            
            // Sample data for demo
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
            
            // Simulate generating report - in real app you would fetch from server
            alert(`Generating product performance report for category: ${category}`);
            
            // Sample data for demo
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

        function exportSalesReport() {
            // Simulate exporting report - in real app you would generate and download
            alert('Exporting sales report to CSV');
        }

        // Customer functions
        function viewCustomerDetails(customerId) {
            // Simulate viewing customer details - in real app you would fetch from server
            alert(`Viewing details for customer #${customerId}`);
        }

        function editCustomer(customerId) {
            // Simulate editing customer - in real app you would fetch from server
            alert(`Editing customer #${customerId}`);
        }

        // Chart initialization functions
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
    </script>
</body>
</html>