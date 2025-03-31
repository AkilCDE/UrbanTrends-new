<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore | URBAN TRENDS</title>
    <style>
        body {
            background-color: #e6e6e6;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #000000;
            position: sticky;
            top: 0;
            z-index: 100;
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
            cursor: pointer;
        }
        
        .account-info:hover {
            color: #f0f0f0;
        }
        
        .cart-icon {
            font-size: 18px;
            cursor: pointer;
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
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
        }

        .product-card {
            background: #fffff6;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
        }

        .product-title {
            margin: 10px 0 5px;
            font-weight: bold;
        }
        
        .product-price {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .product-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
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
        
        .btn-wishlist {
            background-color: #f39c12;
            color: white;
        }
        
        .btn-wishlist:hover {
            background-color: #e67e22;
        }
        
        .btn-buy {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-buy:hover {
            background-color: #27ae60;
        }
        
        .explore-title {
            font-size: 36px;
            text-align: center;
            margin: 20px 0;
            color: #333;
            padding: 20px;
            background-color: #f8f8f8;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: #333;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px;
            display: none;
        }
        
        .back-btn:hover {
            background: #555;
        }
        
        /* Product Detail Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }
        
        .product-detail {
            display: flex;
            gap: 30px;
        }
        
        .product-image {
            flex: 1;
        }
        
        .product-image img {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 5px;
        }
        
        .product-info {
            flex: 1;
            text-align: left;
        }
        
        .product-info h2 {
            margin-top: 0;
            color: #333;
        }
        
        .product-info .price {
            font-size: 24px;
            color: #e74c3c;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .product-info .description {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .size-selector {
            margin: 20px 0;
        }
        
        .size-selector label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .size-options {
            display: flex;
            gap: 10px;
        }
        
        .size-option {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .size-option.selected {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .quantity-selector {
            margin: 20px 0;
        }
        
        .quantity-selector label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        /* Payment Modal */
        .payment-methods {
            margin-top: 20px;
            display: none;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .payment-option:hover {
            background-color: #f5f5f5;
        }
        
        .payment-option.selected {
            border-color: #3498db;
            background-color: #ebf5fb;
        }
        
        .payment-option img {
            width: 40px;
            margin-right: 15px;
        }
        
        .confirm-purchase {
            margin-top: 20px;
            display: none;
        }
        
        @media (max-width: 768px) {
            .product-detail {
                flex-direction: column;
            }
            
            .modal-content {
                width: 90%;
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="left-selection">
        <div class="brand">Urban Trends</div>
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
        <div class="cart-icon" id="cart-icon">🛒 <span class="cart-count" id="cart-count">0</span></div>
    </div>
</header>

<h1 class="explore-title" id="explore-title">Explore Our Collections</h1>

<button class="back-btn" id="back-btn" style="display:none;" onclick="goBack()">Back</button>

<section class="products" id="products-section">
    <!-- Products will be loaded here dynamically -->
</section>

<!-- Product Detail Modal -->
<div id="product-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="product-detail" id="product-detail">
            <!-- Product details will be loaded here -->
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="payment-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closePaymentModal()">&times;</span>
        <div id="payment-content">
            <!-- Payment options will be loaded here -->
        </div>
    </div>
</div>

<script>
    // Sample product data with your images
    const products = [
        {
            id: 1,
            title: "Casual Khaki Minimal Print Shirt",
            image: "./images/prod3M.png",
            price: 29.99,
            description: "Comfortable casual shirt with minimal print design. Made from high-quality cotton for all-day comfort.",
            sizes: ["S", "M", "L", "XL"],
            category: "men",
            type: "tshirts"
        },
        {
            id: 2,
            title: "Men's Cartoon Print Khaki Shirt",
            image: "./images/prod4M.png",
            price: 34.99,
            description: "Fun cartoon print shirt perfect for casual outings. Lightweight and breathable fabric.",
            sizes: ["S", "M", "L"],
            category: "men",
            type: "tshirts"
        },
        {
            id: 3,
            title: "CozeMod Plus Size Men",
            image: "./images/prod5M.png",
            price: 39.99,
            description: "Plus size shirt designed for comfort and style. Available in extended sizes.",
            sizes: ["L", "XL", "XXL"],
            category: "men",
            type: "tshirts"
        },
        {
            id: 4,
            title: "Men Funny Graphic Hoodie",
            image: "./images/Prod6Mh.png",
            price: 49.99,
            description: "Warm and comfortable hoodie with funny graphic print. Perfect for cooler days.",
            sizes: ["S", "M", "L", "XL"],
            category: "men",
            type: "hoodies"
        },
        {
            id: 5,
            title: "Men High-Waist Pants Dark",
            image: "./images/Prod7Mp.png",
            price: 59.99,
            description: "Stylish high-waist pants in dark color. Comfortable fit for all-day wear.",
            sizes: ["30", "32", "34", "36"],
            category: "men",
            type: "pants"
        },
        {
            id: 6,
            title: "Men Drawstring Waist Pocket Shorts",
            image: "./images/prod8Ms.png",
            price: 35.99,
            description: "Comfortable shorts with drawstring waist and convenient pockets.",
            sizes: ["S", "M", "L"],
            category: "men",
            type: "pants"
        },
        {
            id: 7,
            title: "Solid Gray Waist Sweatpants",
            image: "./images/W3pants.png",
            price: 45.99,
            description: "Comfortable sweatpants with solid gray color and elastic waistband.",
            sizes: ["S", "M", "L"],
            category: "women",
            type: "pants"
        },
        {
            id: 8,
            title: "White Casual Straight Pants",
            image: "./images/W2pants.png",
            price: 49.99,
            description: "Elegant white straight pants for a casual yet stylish look.",
            sizes: ["S", "M", "L"],
            category: "women",
            type: "pants"
        },
        {
            id: 9,
            title: "Camo Print Black Pants",
            image: "./images/W1pants.png",
            price: 54.99,
            description: "Trendy camo print pants in black color. Perfect for a bold fashion statement.",
            sizes: ["S", "M", "L"],
            category: "women",
            type: "pants"
        },
        {
            id: 10,
            title: "Block Polo Shirt",
            image: "./images/Block Polo Shirt.jpg",
            price: 39.99,
            description: "Classic block polo shirt with comfortable fit and breathable fabric.",
            sizes: ["S", "M", "L", "XL"],
            category: "men",
            type: "polos"
        },
        {
            id: 11,
            title: "Button Front Jacket",
            image: "./images/Button Front Jacket.jpg",
            price: 79.99,
            description: "Stylish button-front jacket for a sophisticated look.",
            sizes: ["S", "M", "L", "XL"],
            category: "men",
            type: "jackets"
        },
        {
            id: 12,
            title: "Casual Floral Print Shirt",
            image: "./images/Casual Floral Print Graphic Short Sleeve Collar Shirt.jpg",
            price: 44.99,
            description: "Casual floral print shirt with short sleeves and collar.",
            sizes: ["S", "M", "L"],
            category: "men",
            type: "tshirts"
        },
        {
            id: 13,
            title: "Floral Short Sleeve",
            image: "./images/Floral Short Sleeve.jpg",
            price: 34.99,
            description: "Lightweight floral short sleeve shirt for summer.",
            sizes: ["S", "M", "L"],
            category: "women",
            type: "tops"
        },
        {
            id: 14,
            title: "Puff Sleeve Blouse",
            image: "./images/Puff Sleeve Blouse.jpg",
            price: 49.99,
            description: "Elegant puff sleeve blouse for a feminine look.",
            sizes: ["S", "M", "L"],
            category: "women",
            type: "blouses"
        },
        {
            id: 15,
            title: "Zip-Up Long Sleeve Hoodie",
            image: "./images/Zip-Up Long Sleeve Hoodie.jpg",
            price: 59.99,
            description: "Comfortable zip-up hoodie with long sleeves.",
            sizes: ["S", "M", "L", "XL"],
            category: "women",
            type: "hoodies"
        }
    ];

    // Payment methods
    const paymentMethods = [
        { id: 1, name: "GCash", image: "https://www.gcash.com/resources/img/gcash-logo.png" },
        { id: 2, name: "PayPal", image: "https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" },
        { id: 3, name: "PayMaya", image: "https://www.paymaya.com/enterprise/sites/default/files/2020-09/paymaya-logo.png" },
        { id: 4, name: "Credit Card", image: "https://cdn-icons-png.flaticon.com/512/179/179457.png" },
        { id: 5, name: "Bank Transfer", image: "https://cdn-icons-png.flaticon.com/512/2659/2659360.png" }
    ];

    // Cart, wishlist, and orders data
    let cart = [];
    let wishlist = [];
    let orders = [];
    let isLoggedIn = false;
    const username = "Cristel";

    // DOM elements
    const accountInfo = document.getElementById("account-info");
    const cartIcon = document.getElementById("cart-icon");
    const cartCount = document.getElementById("cart-count");
    const productsSection = document.getElementById("products-section");
    const productModal = document.getElementById("product-modal");
    const productDetail = document.getElementById("product-detail");
    const paymentModal = document.getElementById("payment-modal");
    const paymentContent = document.getElementById("payment-content");
    const exploreTitle = document.getElementById("explore-title");
    const backBtn = document.getElementById("back-btn");

    // Initialize the page
    function init() {
        updateCartCount();
        displayAllProducts();
        
        // Check if user is logged in
        if (isLoggedIn) {
            accountInfo.textContent = username;
        } else {
            accountInfo.textContent = "Sign In/Register";
        }
        
        // Add event listeners
        accountInfo.addEventListener("click", toggleLogin);
        cartIcon.addEventListener("click", viewCart);
    }

    // Display all products
    function displayAllProducts() {
        productsSection.innerHTML = "";
        products.forEach(product => {
            const productCard = createProductCard(product);
            productsSection.appendChild(productCard);
        });
    }

    // Create product card HTML
    function createProductCard(product) {
        const card = document.createElement("div");
        card.className = "product-card";
        card.onclick = () => showProductDetail(product.id);
        
        card.innerHTML = `
            <img src="${product.image}" alt="${product.title}">
            <div class="product-title">${product.title}</div>
            <div class="product-price">$${product.price.toFixed(2)}</div>
        `;
        
        return card;
    }

    // Show product detail modal
    function showProductDetail(productId) {
        const product = products.find(p => p.id === productId);
        if (!product) return;
        
        productDetail.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.title}">
            </div>
            <div class="product-info">
                <h2>${product.title}</h2>
                <div class="price">$${product.price.toFixed(2)}</div>
                <div class="description">${product.description}</div>
                
                <div class="size-selector">
                    <label>Size:</label>
                    <div class="size-options">
                        ${product.sizes.map(size => 
                            `<div class="size-option" onclick="selectSize(this, '${size}')">${size}</div>`
                        ).join("")}
                    </div>
                </div>
                
                <div class="quantity-selector">
                    <label>Quantity:</label>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                        <input type="text" class="quantity-input" value="1" readonly>
                        <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="addToCart(${product.id})">Add to Cart</button>
                    <button class="btn btn-wishlist" onclick="addToWishlist(${product.id})">Add to Wishlist</button>
                    <button class="btn btn-buy" onclick="buyNow(${product.id})">Buy Now</button>
                </div>
            </div>
        `;
        
        productModal.style.display = "block";
    }

    // Close modal
    function closeModal() {
        productModal.style.display = "none";
    }

    // Close payment modal
    function closePaymentModal() {
        paymentModal.style.display = "none";
    }

    // Select size
    function selectSize(element, size) {
        // Remove selected class from all size options
        const sizes = document.querySelectorAll(".size-option");
        sizes.forEach(s => s.classList.remove("selected"));
        
        // Add selected class to clicked size
        element.classList.add("selected");
    }

    // Change quantity
    function changeQuantity(change) {
        const input = document.querySelector(".quantity-input");
        let quantity = parseInt(input.value) + change;
        
        // Ensure quantity doesn't go below 1
        if (quantity < 1) quantity = 1;
        
        input.value = quantity;
    }

    // Add to cart
    function addToCart(productId) {
        const product = products.find(p => p.id === productId);
        if (!product) return;
        
        const selectedSize = document.querySelector(".size-option.selected");
        if (!selectedSize) {
            alert("Please select a size");
            return;
        }
        
        const size = selectedSize.textContent;
        const quantity = parseInt(document.querySelector(".quantity-input").value);
        
        // Check if product already in cart
        const existingItem = cart.find(item => 
            item.product.id === productId && item.size === size
        );
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            cart.push({
                product: product,
                size: size,
                quantity: quantity
            });
        }
        
        updateCartCount();
        alert(`${quantity} ${product.title} (Size: ${size}) added to cart!`);
        closeModal();
    }

    // Add to wishlist
    function addToWishlist(productId) {
        if (!isLoggedIn) {
            alert("Please sign in to add items to your wishlist");
            return;
        }
        
        const product = products.find(p => p.id === productId);
        if (!product) return;
        
        // Check if product already in wishlist
        const existingItem = wishlist.find(item => item.id === productId);
        
        if (!existingItem) {
            wishlist.push(product);
            alert(`${product.title} added to wishlist!`);
        } else {
            alert(`${product.title} is already in your wishlist!`);
        }
        
        closeModal();
    }

    // Buy now function
    function buyNow(productId) {
        const product = products.find(p => p.id === productId);
        if (!product) return;
        
        const selectedSize = document.querySelector(".size-option.selected");
        if (!selectedSize) {
            alert("Please select a size");
            return;
        }
        
        const size = selectedSize.textContent;
        const quantity = parseInt(document.querySelector(".quantity-input").value);
        
        // Show payment options
        showPaymentOptions(product, size, quantity);
    }

    // Show payment options
    function showPaymentOptions(product, size, quantity) {
        closeModal(); // Close product detail modal
        
        paymentContent.innerHTML = `
            <h2>Complete Your Purchase</h2>
            <div class="product-info">
                <h3>${product.title}</h3>
                <p>Size: ${size}</p>
                <p>Quantity: ${quantity}</p>
                <p class="price">Total: $${(product.price * quantity).toFixed(2)}</p>
            </div>
            
            <div class="payment-methods">
                <h3>Select Payment Method</h3>
                ${paymentMethods.map(method => `
                    <div class="payment-option" onclick="selectPaymentMethod(${method.id})">
                        <img src="${method.image}" alt="${method.name}">
                        <span>${method.name}</span>
                    </div>
                `).join("")}
            </div>
            
            <div class="confirm-purchase">
                <button class="btn btn-primary" onclick="confirmPurchase()">Confirm Purchase</button>
            </div>
        `;
        
        // Show payment modal
        paymentModal.style.display = "block";
    }

    // Select payment method
    function selectPaymentMethod(methodId) {
        const options = document.querySelectorAll(".payment-option");
        options.forEach(option => option.classList.remove("selected"));
        
        const selectedOption = document.querySelector(`.payment-option[onclick="selectPaymentMethod(${methodId})"]`);
        selectedOption.classList.add("selected");
        
        document.querySelector(".confirm-purchase").style.display = "block";
    }

    // Confirm purchase
    function confirmPurchase() {
        const selectedOption = document.querySelector(".payment-option.selected");
        if (!selectedOption) {
            alert("Please select a payment method");
            return;
        }
        
        const paymentMethod = selectedOption.querySelector("span").textContent;
        
        // In a real app, this would process the payment and save the order to a database
        // For this demo, we'll just simulate it
        
        // Get product details from the modal
        const productInfo = document.querySelector(".product-info");
        const productTitle = productInfo.querySelector("h3").textContent;
        const size = productInfo.querySelector("p").textContent.replace("Size: ", "");
        const quantity = parseInt(productInfo.querySelectorAll("p")[1].textContent.replace("Quantity: ", ""));
        const total = productInfo.querySelector(".price").textContent.replace("Total: $", "");
        
        // Find the product in our products array
        const product = products.find(p => p.title === productTitle);
        
        if (product) {
            // Create order
            const order = {
                id: orders.length + 1,
                product: product,
                size: size,
                quantity: quantity,
                total: parseFloat(total),
                paymentMethod: paymentMethod,
                date: new Date().toLocaleDateString(),
                status: "Processing"
            };
            
            // Add to orders
            orders.push(order);
            
            // In a real app, you would save to database here
            // saveOrderToDatabase(order);
            
            alert(`Thank you for your purchase! Your order (#${order.id}) is being processed.`);
            closePaymentModal();
            
            // If user is logged in, this would show in their profile
            if (isLoggedIn) {
                // In a real app, you would update the user's order history
                console.log("Order saved to user profile:", order);
            }
        }
    }

    // Update cart count
    function updateCartCount() {
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = count;
    }

    // View cart
    function viewCart() {
        if (cart.length === 0) {
            alert("Your cart is empty");
            return;
        }
        
        // In a real app, this would open a cart page or modal
        let cartContent = "Your Cart:\n\n";
        cart.forEach(item => {
            cartContent += `${item.product.title} (Size: ${item.size}) - ${item.quantity} x $${item.product.price.toFixed(2)} = $${(item.quantity * item.product.price).toFixed(2)}\n`;
        });
        
        const total = cart.reduce((sum, item) => sum + (item.quantity * item.product.price), 0);
        cartContent += `\nTotal: $${total.toFixed(2)}`;
        
        alert(cartContent);
    }

    // Toggle login
    function toggleLogin() {
        isLoggedIn = !isLoggedIn;
        
        if (isLoggedIn) {
            accountInfo.textContent = username;
            cartIcon.classList.remove("disabled");
        } else {
            accountInfo.textContent = "Sign In/Register";
            cartIcon.classList.add("disabled");
        }
    }

    // Filter products by category
    function showMenProducts() {
        const menProducts = products.filter(p => p.category === "men");
        displayFilteredProducts(menProducts, "Men's Products");
    }

    function showWomenProducts() {
        const womenProducts = products.filter(p => p.category === "women");
        displayFilteredProducts(womenProducts, "Women's Products");
    }

    function showTShirts() {
        const tshirts = products.filter(p => p.type === "tshirts");
        displayFilteredProducts(tshirts, "Men's T-Shirts");
    }

    function showPoloShirts() {
        const polos = products.filter(p => p.type === "polos");
        displayFilteredProducts(polos, "Men's Polo Shirts");
    }

    function showPants() {
        const pants = products.filter(p => p.type === "pants" && p.category === "men");
        displayFilteredProducts(pants, "Men's Pants");
    }

    function showHoodies() {
        const hoodies = products.filter(p => p.type === "hoodies");
        displayFilteredProducts(hoodies, "Men's Hoodies");
    }

    function showDresses() {
        // In a real app, you would filter for dresses
        const sampleProducts = products.slice(0, 3);
        displayFilteredProducts(sampleProducts, "Women's Dresses");
    }

    function showTops() {
        // In a real app, you would filter for tops
        const sampleProducts = products.slice(3, 6);
        displayFilteredProducts(sampleProducts, "Women's Tops");
    }

    function showBlouses() {
        // In a real app, you would filter for blouses
        const sampleProducts = products.slice(6, 9);
        displayFilteredProducts(sampleProducts, "Women's Blouses");
    }

    function showPantsWomen() {
        const pants = products.filter(p => p.type === "pants" && p.category === "women");
        displayFilteredProducts(pants, "Women's Pants");
    }

    function showShoesProducts() {
        // In a real app, you would have shoes data
        const sampleProducts = products.slice(0, 3);
        displayFilteredProducts(sampleProducts, "Shoes");
    }

    function showAccessoriesProducts() {
        // In a real app, you would have accessories data
        const sampleProducts = products.slice(3, 6);
        displayFilteredProducts(sampleProducts, "Accessories");
    }

    function showEyewear() {
        // In a real app, you would filter for eyewear
        const sampleProducts = products.slice(6, 9);
        displayFilteredProducts(sampleProducts, "Eyewear");
    }

    function showNecklace() {
        // In a real app, you would filter for necklaces
        const sampleProducts = products.slice(0, 3);
        displayFilteredProducts(sampleProducts, "Necklaces");
    }

    function showWatch() {
        // In a real app, you would filter for watches
        const sampleProducts = products.slice(3, 6);
        displayFilteredProducts(sampleProducts, "Watches");
    }

    function showWallet() {
        // In a real app, you would filter for wallets
        const sampleProducts = products.slice(6, 9);
        displayFilteredProducts(sampleProducts, "Wallets");
    }

    // Display filtered products
    function displayFilteredProducts(filteredProducts, title) {
        productsSection.innerHTML = "";
        exploreTitle.textContent = title;
        backBtn.style.display = "block";
        
        if (filteredProducts.length === 0) {
            productsSection.innerHTML = "<p>No products found in this category.</p>";
            return;
        }
        
        filteredProducts.forEach(product => {
            const productCard = createProductCard(product);
            productsSection.appendChild(productCard);
        });
    }

    // Go back to all products
    function goBack() {
        exploreTitle.textContent = "Explore Our Collections";
        backBtn.style.display = "none";
        displayAllProducts();
    }

    // Initialize the page
    window.onload = init;
</script>

</body>
</html>