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
        .left-selection{
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            color: white;
            font-family:Helvetica;
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
        .right-selection{
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .account-info{
            font-size: 16px;
            cursor: pointer;
        }
        .cart-icon{
            font-size: 18px;
            cursor: pointer;
        }
        .disabled{
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
        .explore-title{
            font-size: 48px;
            text-align: center;
            margin-top: 40px;
            color: white;
            background-color: #000000; 
        }
    </style>
</head>
<body>

    <header>
        <div class="left-selection">
        <div class="brand">TrendsWear</div>
        <nav class="nav">
            <div class="dropdown">
                <button class="dropdown-btn">Men ⌄</button>
                <div class="dropdown-content">
                    <a href="#">T-shirts</a>
                    <a href="#">Polo Shirts</a>
                    <a href="#">Shorts</a>
                    <a href="#">Pants</a>
                    <a href="#">Hoodies & Sweatshirts</a>
                </div>
            </div>
       
            <div class="dropdown">
                <button class="dropdown-btn">Women ⌄</button>
                <div class="dropdown-content">
                    <a href="#">Dresses</a>
                    <a href="#">Tops</a>
                    <a href="#">Blouses</a>
                    <a href="#">Pants</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropdown-btn">Shoes</button>
            </div>
            <div class="dropdown">
                <button class="dropdown-btn">Accessories ⌄</button>
                <div class="dropdown-content">
                    <a href="#">Eyewear</a>
                    <a href="#">Necklace</a>
                    <a href="#">Watch</a>
                    <a href="#">Wallet</a>
                </div>
            </div>
        </nav>
    </div>

    <div class="right-selection">
        <div class="account-info" id="account-info">Sign In/Register</div>
        <div class="cart-icon disabled" id="cart-icon">🛒</div>
    </div>
    </header>
    <h1 class="explore-title">Explore Our Collections</h1>


    <section class="products">
        <div class="product-card">
            <img src="prod3M.png" alt="Product">
            <div class="product-title">Casual Khaki Minimal Print Shirt</div>
        </div>
        <div class="product-card">
            <img src="prod4M.png" alt="Product">
            <div class="product-title">Men's Cartoon Print Khaki Shirt</div>
        </div>
        <div class="product-card">
            <img src="prod5M.png" alt="Product">
            <div class="product-title">CozeMod Plus Size Men</div>
        </div>
        <div class="product-card">
            <img src="Prod6Mh.png" alt="Product">
            <div class="product-title"> Men Funny Graphic Hoodie</div>
        </div>
        <div class="product-card">
            <img src="Prod7Mp.png" alt="Product">
            <div class="product-title">Men High-Waist Pants Dark</div>
        </div>
        <div class="product-card">
            <img src="prod8Ms.png" alt="Product">
            <div class="product-title">Men Drawstring Waist Pocket Shorts</div>
        </div>

    <div class="product-card">
        <img src="W3pants.png" alt="Product">
        <div class="product-title">Solid Gray Waist Sweatpants</div>
    </div>
    <div class="product-card">
        <img src="W2pants.png" alt="Product">
        <div class="product-title">White Casual Straight Pants</div>
    </div>
    <div class="product-card">
        <img src="W1pants.png" alt="Product">
        <div class="product-title">Camo Print Black Pants</div>
    </div>
    <div class="product-card">
        <img src="Block Polo Shirt.jpg" alt="Product">
        <div class="product-title">Block Polo Shirt</div>
    </div>
    <div class="product-card">
        <img src="Button Front Jacket.jpg" alt="Product">
        <div class="product-title">Button Front Jacket</div>
    </div>
    <div class="product-card">
        <img src="Casual Floral Print Graphic Short Sleeve Collar Shirt.jpg" alt="Product">
        <div class="product-title">Casual Floral Print Graphic Short Sleeve Collar Shirt</div>
    </div>
    <div class="product-card">
        <img src="Casual Loose Fit Woven Shirt.jpg" alt="Product">
        <div class="product-title">Casual Loose Fit Woven Shirt</div>
    </div>
    <div class="product-card">
        <img src="Floral Short Sleeve.jpg" alt="Product">
        <div class="product-title">Floral Short Sleeve</div>
    </div>
    <div class="product-card">
        <img src="Geometric Striped Long Sleeve Shirt.jpg" alt="Product">
        <div class="product-title">Geometric Striped Long Sleeve Shirt</div>
    </div>
    <div class="product-card">
        <img src="Pocket Patched Tee.jpg" alt="Product">
        <div class="product-title">Pocket Patched Tee</div>
    </div>
    <div class="product-card">
        <img src="Printed Short Sleeve T-Shirt.jpg" alt="Product">
        <div class="product-title">Printed Short Sleeve T-Shirt</div>
    </div>
    <div class="product-card">
        <img src="Puff Sleeve Blouse.jpg" alt="Product">
        <div class="product-title">Puff Sleeve Blouse</div>
    </div>
    <div class="product-card">
        <img src="Rib-Knit Polo Shirt, Plain Collar Shirt.jpg" alt="Product">
        <div class="product-title">Rib-Knit Polo Shirt, Plain Collar Shirt</div>
    </div>
    <div class="product-card">
        <img src="Striped Print Twist Front Asymmetrical Hem Blouse Peplum Top.jpg" alt="Product">
        <div class="product-title">Striped Print Twist Front Asymmetrical Hem Blouse Peplum Top</div>
    </div>
    <div class="product-card">
        <img src="Women Woven Cropped Shirt.jpg" alt="Product">
        <div class="product-title">Women Woven Cropped Shirt</div>
    </div>
    <div class="product-card">
        <img src="Women_s Casual & Business Loose .jpg" alt="Product">
        <div class="product-title">Women_s Casual & Business Loose </div>
    </div>
    <div class="product-card">
        <img src="Zip-Up Long Sleeve Hoodie.jpg" alt="Product">
        <div class="product-title">Zip-Up Long Sleeve Hoodie</div>
    </div>
    <div class="product-card">
        <img src="Collar 3D Rose Mini Tube Dress.jpg" alt="Product">
        <div class="product-title">Collar 3D Rose Mini Tube Dress</div>
    </div>
    <div class="product-card">
        <img src="Collar Halter Back Metal Decorative Buckle.jpg" alt="Product">
        <div class="product-title">Collar Halter Back Metal Decorative Buckle</div>
    </div>
    <div class="product-card">
        <img src="Drawstring Halter Backless Tank Top.jpg" alt="Product">
        <div class="product-title">Drawstring Halter Backless Tank Top</div>
    </div>
    <div class="product-card">
        <img src="Drawstring Waist Wide Leg Pants.jpg" alt="Product">
        <div class="product-title">Drawstring Waist Wide Leg Pants</div>
    </div>
    <div class="product-card">
        <img src="Floral Knitted Long Dress.jpg" alt="Product">
        <div class="product-title">Floral Knitted Long Dress</div>
    </div>
    <div class="product-card">
        <img src="Floral Print Ruched Tube Top.jpg" alt="Product">
        <div class="product-title">Floral Print Ruched Tube Top</div>
    </div>
    <div class="product-card">
        <img src="Halter Plunging Neck Fitted Mermaid Dress.jpg" alt="Product">
        <div class="product-title">Halter Plunging Neck Fitted Mermaid Dress</div>
    </div>
    <div class="product-card">
        <img src="Minimalist Casual Straight Leg Pants.jpg" alt="Product">
        <div class="product-title">Minimalist Casual Straight Leg Pants</div>
    </div>
    <div class="product-card">
        <img src="Ruffle Hem Cami Top" alt="Product">
        <div class="product-title">Ruffle Hem Cami Top</div>
    </div>
    <div class="product-card">
        <img src="Sleeveless Knot Back Crisscross Tank Top.jpg" alt="Product">
        <div class="product-title">Sleeveless Knot Back Crisscross Tank Top</div>
    </div>
    <div class="product-card">
        <img src="Star Patchwork Fringe Baggy Denim Jeans Pants" alt="Product">
        <div class></div>




    </section>
    </div>
    
    <script>
        const isLoggedIn = true;
        const username = "Cristel";

        const accountInfo = document.getElementById("account-info");
        const cartIcon = document.getElementById("cart-icon");

        if (isLoggedIn) {
            accountInfo.textContent = username;
            cartIcon.classList.remove("disabled"); 
        }
    </script>

</body>
</html>
