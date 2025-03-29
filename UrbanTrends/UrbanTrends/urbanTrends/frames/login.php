<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Check in customers table first
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();
        
        if ($customer && password_verify($password, $customer['password_hash'])) {
            // Customer login successful
            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['customer_email'] = $customer['email'];
            $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
            $_SESSION['user_type'] = 'customer';
            
            // Update last login time
            $pdo->prepare("UPDATE customers SET last_login = NOW() WHERE customer_id = ?")
               ->execute([$customer['customer_id']]);
            
            // Redirect to account page
            header('Location: index.php');
            exit;
        } else {
            // Check in admin table if not found in customers
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Admin login successful
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['user_type'] = 'admin';
                
                // Update last login time
                $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?")
                   ->execute([$admin['admin_id']]);
                
                // Redirect to admin dashboard
                header('Location: Admin.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendsWear - Customer Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .required:after {
            content: " *";
            color: #e74c3c;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            background-color: #000;
            color: white;
            border: none;
            padding: 14px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #333;
        }
        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .links-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .link {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        .link:hover {
            text-decoration: underline;
        }
        .login-type-toggle {
            text-align: center;
            margin: 25px 0;
        }
        .toggle-btn {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 14px;
            padding: 5px 10px;
        }
        .toggle-btn:hover {
            text-decoration: underline;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to TrendsWear</h1>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="login-type-toggle">
            <button id="customer-toggle" class="toggle-btn">Customer Login</button> | 
            <button id="admin-toggle" class="toggle-btn">Admin Login</button>
        </div>
        
        <!-- Customer Login Form -->
        <form id="customer-form" method="POST" action="">
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password" class="required">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Sign In</button>
            
            <div class="links-container">
                <a href="forgot_password.php" class="link">Forgot password?</a>
                <a href="register.php" class="link">Create an account</a>
            </div>
        </form>
        
        <!-- Admin Login Form (Hidden by default) -->
        <form id="admin-form" method="POST" action="" class="hidden">
            <input type="hidden" name="admin_login" value="1">
            <div class="form-group">
                <label for="admin-email" class="required">Admin Email</label>
                <input type="email" id="admin-email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="admin-password" class="required">Password</label>
                <input type="password" id="admin-password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Admin Sign In</button>
            
            <div class="links-container">
                <a href="forgot_password.php" class="link">Forgot password?</a>
                <a href="#" id="back-to-customer" class="link">Back to customer login</a>
            </div>
        </form>
    </div>

    <script>
        // Toggle between customer and admin login forms
        document.getElementById('admin-toggle').addEventListener('click', function() {
            document.getElementById('customer-form').classList.add('hidden');
            document.getElementById('admin-form').classList.remove('hidden');
        });
        
        document.getElementById('back-to-customer').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('admin-form').classList.add('hidden');
            document.getElementById('customer-form').classList.remove('hidden');
        });
        
        document.getElementById('customer-toggle').addEventListener('click', function() {
            document.getElementById('admin-form').classList.add('hidden');
            document.getElementById('customer-form').classList.remove('hidden');
        });
    </script>
</body>
</html>