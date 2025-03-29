<?php
session_start();
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $country = trim($_POST['country']);

    // Validate inputs
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT email FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Email already registered.';
    }

    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new customer with all fields
        $stmt = $pdo->prepare("
            INSERT INTO customers 
            (first_name, last_name, email, password_hash, phone, address, city, state, zip_code, country) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $first_name, $last_name, $email, $password_hash, 
            $phone, $address, $city, $state, $zip_code, $country
        ])) {
            // Get the new customer ID
            $customer_id = $pdo->lastInsertId();
            
            // Set session variables
            $_SESSION['customer_id'] = $customer_id;
            $_SESSION['customer_email'] = $email;
            $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_type'] = 'customer';
            
            // Set success message and redirect
            $success = 'Registration successful! Redirecting to your account...';
            header("Refresh: 2; url=index.php");
        } else {
            $error = 'Registration failed. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendsWear - Customer Registration</title>
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
        .form-section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-height: 80px;
        }
        .required:after {
            content: " *";
            color: #e74c3c;
        }
        .btn {
            background-color: #000;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
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
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Your Customer Account</h1>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-section">
                <h2 class="section-title">Personal Information</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="required">First Name</label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name" class="required">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="required">Email</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="required">Password (min 8 characters)</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="required">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">Address Information</h2>
                
                <div class="form-group">
                    <label for="address">Street Address</label>
                    <textarea id="address" name="address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city"
                               value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="state">State/Province</label>
                        <input type="text" id="state" name="state"
                               value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="zip_code">ZIP/Postal Code</label>
                        <input type="text" id="zip_code" name="zip_code"
                               value="<?php echo isset($_POST['zip_code']) ? htmlspecialchars($_POST['zip_code']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="country">Country</label>
                        <select id="country" name="country">
                            <option value="">Select Country</option>
                            <option value="US" <?php echo (isset($_POST['country']) && $_POST['country'] == 'US') ? 'selected' : ''; ?>>United States</option>
                            <option value="CA" <?php echo (isset($_POST['country']) && $_POST['country'] == 'CA') ? 'selected' : ''; ?>>Canada</option>
                            <option value="UK" <?php echo (isset($_POST['country']) && $_POST['country'] == 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                            <option value="AU" <?php echo (isset($_POST['country']) && $_POST['country'] == 'AU') ? 'selected' : ''; ?>>Australia</option>
                            <option value="JP" <?php echo (isset($_POST['country']) && $_POST['country'] == 'JP') ? 'selected' : ''; ?>>Japan</option>
                            <option value="SG" <?php echo (isset($_POST['country']) && $_POST['country'] == 'SG') ? 'selected' : ''; ?>>Singapore</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Create Account</button>
            </div>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>
</body>
</html>

