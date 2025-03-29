<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account</title>
    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body{
           background-image: url(Login\ Bg.jpg);
           background-size: cover;
           background-position: center;
           background-repeat: no-repeat;
           height: 100vh;
           margin: 0;
           display: flex;
           justify-content: center;
           align-items: center;
        }
        .account-panel{
            background-color: rgb(12, 14, 16);
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            
        }
        .account-panel h1{
            color: aliceblue;
        }
        .account-panel label{
            color: aliceblue;
        }
        .account-panel p{
            color: aliceblue;
        }
        form label{
            display: block;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        form input{
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: none;
            border-radius: 5px;
        }
        form input:focus{
            outline: 2px solid #007bff;
        }
        button{
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover{
            background-color: #0056b3;
        }

        p{
            margin-top: 20px;
            font-size: 0.9rem;
        }

        p a{
            color: #007bff;
            text-decoration: none;
        }
        p a:hover{
            text-decoration: underline;
        }
        
    </style>
</head>
<body>
    <div class="account-panel">
        <h1>Login</h1>
        <div class="form-container">
            <form>
                <label for="username">Username</label>
                <input type="text" id="username" placeholder="Enter your username" required/>

                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Enter your password" required/>

                <button type="submit">Login</button>

                <p>Don't have an account? <a href="#">Signup</a></p>

            </form>
        </div>
    </div>
</body>
</html>
