<?php
session_start();


include('db.php');

$registration_error = "";


if (isset($_POST["register"])) {

    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);
    $confirm_password = filter_input(INPUT_POST, "confirm_password", FILTER_SANITIZE_SPECIAL_CHARS);
    $fullname = filter_input(INPUT_POST, "fullname", FILTER_SANITIZE_SPECIAL_CHARS);

   
    if ($password !== $confirm_password) {
        $registration_error = "Passwords do not match.";
    } else {
       
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    
        $query = "SELECT * FROM Registration WHERE Username = '$username' OR Email = '$email'";
        $execQuery = mysqli_query($conn, $query);

        if (mysqli_num_rows($execQuery) != 0) {
            $registration_error = "Username or Email already exists.";
        } else {
           
            $query = "INSERT INTO Registration (Username, Email, Password, FullName) VALUES ('$username', '$email', '$hashed_password', '$fullname')";
            $execQuery = mysqli_query($conn, $query);

            if ($execQuery) {
              
                header("Location: login.php?registered");
                exit();
            } else {
                $registration_error = "Error in registration. Please try again.";
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
    <title>Register</title>

    <style>

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
            font-size: 14px;
        }

        p {
            text-align: center;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .show-password {
            display: inline-flex;
            align-items: center;
            margin-top: 5px;
        }

        .show-password input {
            margin-right: 0px;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Register</h2>

    
        <?php if (!empty($registration_error)): ?>
            <div class="error-message"><?php echo $registration_error; ?></div>
        <?php endif; ?>

        
        <form method="POST" action="register.php">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required><br><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required><br><br>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required><br><br>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required><br><br>
            
                <label for="fullname">Full Name:</label>
            <input type="text" name="fullname" id="fullname" required><br><br>
            
            <div class="show-password">
                <input type="checkbox" id="showPassword"> <label for="showPassword">Show Password</label>
            </div><br>

            

            <button type="submit" name="register">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script>
    
        document.getElementById('showPassword').addEventListener('change', function() {
            var passwordField = document.getElementById('password');
            var confirmPasswordField = document.getElementById('confirm_password');
            
            if (this.checked) {
                passwordField.type = 'text';
                confirmPasswordField.type = 'text';
            } else {
                passwordField.type = 'password';
                confirmPasswordField.type = 'password';
            }
        });
    </script>

</body>
</html>

