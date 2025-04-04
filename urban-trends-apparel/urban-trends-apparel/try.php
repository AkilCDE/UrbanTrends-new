<?php
// [Previous database configuration and session start remains the same]
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'urban_trends');

session_start();
// Create database connection
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
// Handle order shipping confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_shipping'])) {
    $order_id = intval($_POST['order_id']);
    
    try {
        // Update order status to 'shipped' and set shipping date
        $stmt = $db->prepare("UPDATE orders SET status = 'shipped', shipping_date = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
        
        $_SESSION['success_message'] = "Order #$order_id has been marked as shipped!";
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
        header("Location: dashboard.php");
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new product
    if (isset($_POST['add_product'])) {
        $name = htmlspecialchars($_POST['name']);
        $description = htmlspecialchars($_POST['description']);
        $price = floatval($_POST['price']);
        $category = htmlspecialchars($_POST['category']);
        $stock = intval($_POST['stock']);
        
        // Handle image upload
        $image = 'default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/products/';
            $image = basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $image;
            
            // Check if image file is valid
            $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($imageFileType, $allowedExtensions)) {
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile);
            } else {
                $image = 'default.jpg';
            }
        }
        
        $stmt = $db->prepare("INSERT INTO products (name, description, price, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $category, $stock, $image]);
    }
    
    // Update stock
    if (isset($_POST['update_stock'])) {
        $product_id = intval($_POST['product_id']);
        $stock_change = intval($_POST['stock_change']);
        
        $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->execute([$stock_change, $product_id]);
    }
}

// Get statistics for dashboard
$totalRevenue = $db->query("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered'")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Get low stock products (less than 10 in stock)
$lowStockProducts = $db->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get recent orders
$recentOrders = $db->query("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY order_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get popular products (most ordered)
$popularProducts = $db->query("
    SELECT p.id, p.name, p.image, SUM(oi.quantity) as total_ordered 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY p.id 
    ORDER BY total_ordered DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get frequent customers
$frequentCustomers = $db->query("
    SELECT u.id, u.email, u.firstname, u.lastname, COUNT(o.id) as order_count 
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE u.is_admin = 0 
    GROUP BY u.id 
    ORDER BY order_count DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get order volume by month
$orderVolume = $db->query("
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month, 
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders 
    GROUP BY DATE_FORMAT(order_date, '%Y-%m') 
    ORDER BY month DESC 
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
// [Rest of your existing code remains the same until the HTML starts]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Trends Apparel - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #5649c0;
            --accent-color: #00cec9;
            --danger-color: #ff7675;
            --warning-color: #fdcb6e;
            --success-color: #55efc4;
            --dark-color: #2d3436;
            --darker-color: #1e272e;
            --light-color: #dfe6e9;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--darker-color);
            color: var(--light-color);
            display: flex;
            min-height: 100vh;
        }
        
        /* Dark Theme Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
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
            color: var(--accent-color);
        }
        
        .admin-sidebar ul li a {
            color: rgba(255,255,255,0.7);
            transition: all 0.3s;
        }
        
        .admin-sidebar ul li a:hover, 
        .admin-sidebar ul li a.active {
            background-color: rgba(108, 92, 231, 0.2);
            color: white;
            border-left: 3px solid var(--accent-color);
        }
        
        /* Dark Theme Main Content */
        .admin-main {
            background-color: var(--darker-color);
        }
        
        .admin-header {
            background-color: var(--dark-color);
            box-shadow: 0 2px 15px rgba(0,0,0,0.3);
        }
        
        .admin-header h2 {
            color: var(--light-color);
        }
        
        .admin-actions a {
            color: var(--light-color);
        }
        
        .admin-actions a:hover {
            color: var(--accent-color);
        }
        
        /* Dark Theme Cards and Tables */
        .stat-card, 
        .table-container, 
        .chart-container {
            background-color: var(--dark-color);
            box-shadow: 0 2px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .stat-card h3 {
            color: var(--accent-color);
        }
        
        table {
            color: var(--light-color);
        }
        
        th {
            background-color: rgba(108, 92, 231, 0.2);
            color: var(--light-color);
        }
        
        tr:hover {
            background-color: rgba(108, 92, 231, 0.1);
        }
        
        /* Status Badges */
        .status.pending {
            background-color: rgba(253, 203, 110, 0.2);
            color: #fdcb6e;
        }
        
        .status.processing {
            background-color: rgba(85, 239, 196, 0.2);
            color: #55efc4;
        }
        
        .status.shipped {
            background-color: rgba(0, 206, 201, 0.2);
            color: #00cec9;
        }
        
        .status.delivered {
            background-color: rgba(108, 92, 231, 0.2);
            color: #6c5ce7;
        }
        
        .status.cancelled {
            background-color: rgba(255, 118, 117, 0.2);
            color: #ff7675;
        }
        
        .status.low-stock {
            background-color: rgba(255, 118, 117, 0.3);
            color: #ff7675;
        }
        
        /* Buttons */
        .btn {
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border: 1px solid var(--success-color);
            color: var(--dark-color);
        }
        
        .btn-success:hover {
            background-color: #00b894;
            border-color: #00b894;
        }
        
        .btn-ship {
            background-color: var(--accent-color);
            border: 1px solid var(--accent-color);
            color: var(--dark-color);
        }
        
        .btn-ship:hover {
            background-color: #00a8a5;
            border-color: #00a8a5;
        }
        
        /* Form Elements */
        .form-control {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--light-color);
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(0, 206, 201, 0.2);
        }
        
        /* Modal Styles */
        .modal-content {
            background-color: var(--dark-color);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Shipping Confirmation Button */
        .btn-confirm-ship {
            background-color: var(--accent-color);
            color: var(--dark-color);
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
        }
        
        .btn-confirm-ship:hover {
            background-color: #00a8a5;
            transform: translateY(-2px);
        }
        
        .btn-confirm-ship i {
            margin-right: 5px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
            }
            
            .admin-main {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- [Previous HTML structure remains the same until the Recent Orders table] -->
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2><i class="fas fa-crown"></i> <span>Admin Panel</span></h2>
        </div>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="products.php"><i class="fas fa-tshirt"></i> <span>Products</span></a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-header">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2>
            <div class="admin-actions">
                <a href="../index.php"><i class="fas fa-home"></i> View Site</a>
                <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="switchTab('dashboard')">Dashboard</div>
            <div class="tab" onclick="switchTab('inventory')">Inventory Management</div>
            <div class="tab" onclick="switchTab('reports')">Reports</div>
            <div class="tab" onclick="switchTab('add-product')">Add Product</div>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><i class="fas fa-dollar-sign"></i> Total Revenue</h3>
                    <p>$<?php echo number_format($totalRevenue ?: 0, 2); ?></p>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-shopping-bag"></i> Total Orders</h3>
                    <p><?php echo $totalOrders; ?></p>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 8% from last month
                    </div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> Total Customers</h3>
                    <p><?php echo $totalCustomers; ?></p>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 5% from last month
                    </div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-tshirt"></i> Total Products</h3>
                    <p><?php echo $totalProducts; ?></p>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i> 2% from last month
                    </div>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="table-container">
                    <h3><i class="fas fa-clock"></i> Recent Orders</h3>
    <!-- Modified Recent Orders Table with Shipping Button -->
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $order): ?>
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
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php if ($order['status'] == 'processing'): ?>
                            <form method="POST" style="display: inline-block; margin-left: 5px;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="confirm_shipping" class="btn-confirm-ship">
                                    <i class="fas fa-truck"></i> Ship Order
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    </div>

<div class="table-container">
    <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Products</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lowStockProducts as $product): ?>
                <tr>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $product['category'])); ?></td>
                    <td>
                        <span class="status <?php echo $product['stock'] < 5 ? 'low-stock' : 'warning'; ?>">
                            <?php echo $product['stock']; ?> left
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary" onclick="openStockModal(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>')">
                            <i class="fas fa-plus"></i> Restock
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>
</div>

<!-- Inventory Management Tab -->
<div id="inventory" class="tab-content">
<div class="table-container">
<h3><i class="fas fa-boxes"></i> Product Inventory</h3>
<div class="search-bar" style="margin-bottom: 15px;">
    <input type="text" id="inventorySearch" placeholder="Search products..." class="form-control">
</div>
<table id="inventoryTable">
    <thead>
        <tr>
            <th>Image</th>
            <th>Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $allProducts = $db->query("SELECT * FROM products ORDER BY stock ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allProducts as $product): 
        ?>
            <tr>
                <td>
                    <img src="../assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td><?php echo $product['name']; ?></td>
                <td><?php echo ucfirst(str_replace('_', ' ', $product['category'])); ?></td>
                <td>$<?php echo number_format($product['price'], 2); ?></td>
                <td>
                    <span class="status <?php 
                        echo $product['stock'] < 5 ? 'low-stock' : 
                             ($product['stock'] < 10 ? 'warning' : 'success'); 
                    ?>">
                        <?php echo $product['stock']; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-primary" onclick="openStockModal(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>')">
                        <i class="fas fa-edit"></i> Update
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<!-- Reports Tab -->
<div id="reports" class="tab-content">
<div class="chart-container">
<h3><i class="fas fa-chart-line"></i> Sales Trends</h3>
<canvas id="salesChart" height="300"></canvas>
</div>

<div class="grid-2-col">
<div class="table-container">
    <h3><i class="fas fa-star"></i> Popular Products</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Total Ordered</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($popularProducts as $product): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center;">
                            <img src="../assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px;">
                            <?php echo $product['name']; ?>
                        </div>
                    </td>
                    <td><?php echo $product['total_ordered']; ?></td>
                    <td>
                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="table-container">
    <h3><i class="fas fa-users"></i> Frequent Customers</h3>
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Orders</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($frequentCustomers as $customer): ?>
                <tr>
                    <td><?php echo $customer['firstname'] . ' ' . $customer['lastname']; ?><br><small><?php echo $customer['email']; ?></small></td>
                    <td><?php echo $customer['order_count']; ?></td>
                    <td>
                        <a href="customers.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>
</div>

<!-- Add Product Tab -->
<div id="add-product" class="tab-content">
<div class="table-container">
<h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
<form method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="name">Product Name</label>
        <input type="text" id="name" name="name" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
    </div>
    
    <div class="form-group">
        <label for="price">Price</label>
        <input type="number" id="price" name="price" step="0.01" min="0" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category" class="form-control" required>
            <option value="">Select Category</option>
            <option value="men_tshirts">Men's T-Shirts</option>
            <option value="men_polos">Men's Polo Shirts</option>
            <option value="men_pants">Men's Pants</option>
            <option value="men_hoodies">Men's Hoodies</option>
            <option value="women_dresses">Women's Dresses</option>
            <option value="women_tops">Women's Tops</option>
            <option value="women_blouses">Women's Blouses</option>
            <option value="women_pants">Women's Pants</option>
            <option value="shoes">Shoes</option>
            <option value="access_eyewear">Eyewear</option>
            <option value="access_necklace">Necklace</option>
            <option value="access_watch">Watch</option>
            <option value="access_wallet">Wallet</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="stock">Initial Stock</label>
        <input type="number" id="stock" name="stock" min="0" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label for="image">Product Image</label>
        <input type="file" id="image" name="image" class="form-control" accept="image/*">
    </div>
    
    <button type="submit" name="add_product" class="btn btn-success">
        <i class="fas fa-save"></i> Add Product
    </button>
</form>
</div>
</div>
</div>

<!-- Stock Update Modal -->
<div id="stockModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
<div style="background-color: white; margin: 10% auto; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%;">
<h3 id="modalTitle" style="margin-bottom: 20px;"></h3>
<form id="stockForm" method="POST">
<input type="hidden" id="product_id" name="product_id">
<div class="form-group">
    <label for="stock_change">Stock Adjustment</label>
    <div style="display: flex; align-items: center;">
        <button type="button" class="btn btn-primary" onclick="adjustStock(-1)">-</button>
        <input type="number" id="stock_change" name="stock_change" value="0" min="-1000" max="1000" class="form-control" style="margin: 0 10px; text-align: center;">
        <button type="button" class="btn btn-primary" onclick="adjustStock(1)">+</button>
    </div>
    <small>Positive numbers add stock, negative numbers remove stock</small>
</div>
<div style="display: flex; justify-content: flex-end; margin-top: 20px;">
    <button type="button" class="btn btn-danger" style="margin-right: 10px;" onclick="closeStockModal()">
        <i class="fas fa-times"></i> Cancel
    </button>
    <button type="submit" name="update_stock" class="btn btn-success">
        <i class="fas fa-save"></i> Update Stock
    </button>
</div>
</form>
</div>
</div>
    <!-- [Rest of your HTML remains the same] -->

    <script>
        // [Previous JavaScript remains the same]
        // Switch between tabs
        function switchTab(tabId) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelector(`.tab[onclick="switchTab('${tabId}')"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
        
        // Stock modal functions
        function openStockModal(productId, productName) {
            document.getElementById('product_id').value = productId;
            document.getElementById('modalTitle').textContent = `Update Stock: ${productName}`;
            document.getElementById('stock_change').value = 0;
            document.getElementById('stockModal').style.display = 'block';
        }
        
        function closeStockModal() {
            document.getElementById('stockModal').style.display = 'none';
        }
        
        function adjustStock(change) {
            const input = document.getElementById('stock_change');
            let value = parseInt(input.value) + change;
            if (value < -1000) value = -1000;
            if (value > 1000) value = 1000;
            input.value = value;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('stockModal')) {
                closeStockModal();
            }
        }
        
        function confirmShipping(orderId) {
            if (confirm(`Are you sure you want to mark Order #${orderId} as shipped?`)) {
                document.getElementById(`shipForm${orderId}`).submit();
            }
        }
        // Inventory search
        document.getElementById('inventorySearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#inventoryTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Initialize sales chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column(array_reverse($orderVolume), 'month')); ?>,
                    datasets: [
                        {
                            label: 'Order Volume',
                            data: <?php echo json_encode(array_column(array_reverse($orderVolume), 'order_count')); ?>,
                            borderColor: '#4361ee',
                            backgroundColor: 'rgba(67, 97, 238, 0.1)',
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Revenue ($)',
                            data: <?php echo json_encode(array_column(array_reverse($orderVolume), 'revenue')); ?>,
                            borderColor: '#4cc9f0',
                            backgroundColor: 'rgba(76, 201, 240, 0.1)',
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Order Volume'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        });
        
        // Active sidebar link highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const links = document.querySelectorAll('.admin-sidebar ul li a');
            
            links.forEach(link => {
                const linkPage = link.getAttribute('href');
                if (currentPage === linkPage) {
                    link.classList.add('active');
                }
            });
        });
        // Add shipping confirmation dialog
       
    </script>
</body>
</html>