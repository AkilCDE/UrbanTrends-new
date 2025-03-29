<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
        body {
            background-color: #e6e6e6;
            margin: 0;
            padding: 0;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #000000;
        }

        .left-selection {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            color: white;
            font-family: Helvetica;
        }

        .nav {
            display: flex;
            gap: 20px;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-btn {
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background: #22252b;
            min-width: 160px;
            box-shadow: 0px 4px 8px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: white;
            padding: 10px;
            display: block;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background: #30343c;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .right-selection {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .account-info {
            font-size: 16px;
            color: white;
        }

        .account-info:hover {
            color: #f0f0f0;
        }

        .account-info:active {
            color: #f0f0f0;
        }

        .account-info:focus {
            color: #f0f0f0;
        }

        .account-info:focus-visible {
            color: rgb(55, 0, 255);
        }

        .cart-icon {
            font-size: 18px;
            cursor: pointer;
        }

        .disabled {
            color: gray;
            cursor: not-allowed;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            max-height: calc(100vh - 80px);
        }

        .product-card {
            background: #fffff6;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }

        .product-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .product-title {
            margin: 5px 0;
        }

        .explore-title {
            font-size: 48px;
            text-align: center;
            margin-top: 40px;
            color: white;
            background-color: #000000;
        }

        .back-btn {
            background-color: #333;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            margin: 20px;
        }

        .back-btn:hover {
            background-color: #444;
        }
    </style>
</head>
<body>

<header>
    <div class="left-selection">
        <div class="brand">TrendsWear</div>
        <nav class="nav">
            <div class="dropdown">
                <button class="dropdown-btn" onclick="showMenProducts()">Men ⌄</button>
                <div class="dropdown-content">
                    <a href="#" onclick="showTShirts()">T-Shirts</a>
                    <a href="#" onclick="showPoloShirts()">Polo Shirts</a>
                    <a href="#" onclick="showPants()">Pants</a>
                    <a href="#" onclick="showHoodies()">Hoodies & Sweatshirts</a>
                </div>
            </div>

            <div class="dropdown">
                <button class="dropdown-btn" onclick="showWomenProducts()">Women ⌄</button>
                <div class="dropdown-content">
                    <a href="#" onclick="showDresses()">Dresses</a>
                    <a href="#" onclick="showTops()">Tops</a>
                    <a href="#" onclick="showBlouses()">Blouses</a>
                    <a href="#" onclick="showPantsWomen()">Pants</a>
                </div>
            </div>

            <div class="dropdown">
                <button class="dropdown-btn" onclick="showShoesProducts()">Shoes</button>
            </div>

            <div class="dropdown">
                <button class="dropdown-btn" onclick="showAccessoriesProducts()">Accessories ⌄</button>
                <div class="dropdown-content">
                    <a href="#" onclick="showEyewear()">Eyewear</a>
                    <a href="#" onclick="showNecklace()">Necklace</a>
                    <a href="#" onclick="showWatch()">Watch</a>
                    <a href="#" onclick="showWallet()">Wallet</a>
                </div>
            </div>
        </nav>
    </div>

    <div class="right-selection">
        <div class="account-info" id="account-info">Sign In/Register</div>
        <div class="cart-icon disabled" id="cart-icon">🛒</div>
    </div>
</header>

<h1 class="explore-title" id="explore-title">Explore Our Collections</h1>

<button class="back-btn" id="back-btn" style="display:none;" onclick="goBack()">Back</button>

<section class="products" id="products-section">
    <!-- Sample products here -->
</section>

<script>
    function showMenProducts() {
        document.getElementById('explore-title').textContent = "Men's Products";
        document.getElementById('back-btn').style.display = "block";
        document.getElementById('products-section').innerHTML = ``;
    }

    // Function to display T-Shirts for Men
    function showTShirts() {
        document.getElementById('explore-title').textContent = "Men's T-Shirts";
        document.getElementById('products-section').innerHTML = `
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+T-Shirt" alt="Product">
                <div class="product-title">Graphic T-Shirt</div>
            </div>
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+T-Shirt+2" alt="Product">
                <div class="product-title">Minimalist T-Shirt</div>
            </div>
        `;
    }

    // Function to display Polo Shirts for Men
    function showPoloShirts() {
        document.getElementById('explore-title').textContent = "Men's Polo Shirts";
        document.getElementById('products-section').innerHTML = `
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+Polo+Shirt" alt="Product">
                <div class="product-title">Casual Polo Shirt</div>
            </div>
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+Polo+Shirt+2" alt="Product">
                <div class="product-title">Striped Polo Shirt</div>
            </div>
        `;
    }

    // Function to display Pants for Men
    function showPants() {
        document.getElementById('explore-title').textContent = "Men's Pants";
        document.getElementById('products-section').innerHTML = `
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+Pants" alt="Product">
                <div class="product-title">Slim Fit Chinos</div>
            </div>
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+Pants+2" alt="Product">
                <div class="product-title">Comfortable Joggers</div>
            </div>
        `;
    }

    // Function to display Hoodies for Men
    function showHoodies() {
        document.getElementById('explore-title').textContent = "Men's Hoodies & Sweatshirts";
        document.getElementById('products-section').innerHTML = `
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+Hoodie" alt="Product">
                <div class="product-title">Cozy Hoodie</div>
            </div>
            <div class="product-card">
                <img src="https://via.placeholder.com/300?text=Men+Hoodie+2" alt="Product">
                <div class="product-title">Graphic Hoodie</div>
            </div>
        `;
    }

    // Function to go back to the main collection page
    function goBack() {
        document.getElementById('explore-title').textContent = "Explore Our Collections";
        document.getElementById('back-btn').style.display = "none";
        document.getElementById('products-section').innerHTML = ``;
    }
</script>

</body>
</html>

