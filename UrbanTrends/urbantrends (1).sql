-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2025 at 03:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `urbantrends`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Admin login', NULL, '::1', '2025-03-30 14:31:57');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@example.com', 'admin', '2025-03-31 01:38:06', '2025-03-29 05:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `customer_id`, `product_id`, `quantity`, `added_at`) VALUES
(1, 1, 5, 1, '2025-03-31 00:19:29');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `address`, `city`, `state`, `zip_code`, `country`, `created_at`, `updated_at`) VALUES
(1, 'Hermars', 'ubarbie', 'akil@gmail.com', '$2y$10$RtfSeuJVMmy/61pGFsBOuuomAVTn1dGRzp/1t6xu6eeASxy0WRLme', '09876565655', 'ambuan\r\nsteert', 'catigbian', 'bohol', '6343', 'CA', '2025-03-30 16:38:11', '2025-03-31 01:32:39');

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `address_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `address_name` varchar(50) NOT NULL,
  `street_address` varchar(255) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `country` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `log_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `change_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Shipped','Completed','Cancelled') DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `order_date`, `total_amount`, `status`, `payment_method`) VALUES
(1, 1, '2023-01-10 00:45:00', 99.98, 'Completed', 'Credit Card'),
(2, 2, '2023-01-12 03:30:00', 45.50, 'Shipped', 'PayPal'),
(3, 1, '2023-01-25 05:15:00', 75.25, 'Pending', 'Debit Card'),
(4, 3, '2023-02-01 01:00:00', 120.75, 'Processing', 'Credit Card'),
(5, 2, '2023-02-15 08:45:00', 29.99, 'Completed', 'PayPal');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 5, 1, 49.99),
(2, 1, 8, 2, 25.00),
(3, 2, 3, 1, 45.50),
(4, 3, 12, 3, 25.08),
(5, 4, 5, 2, 49.99),
(6, 4, 7, 1, 20.77),
(7, 5, 2, 1, 29.99);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sku` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `category`, `price`, `stock_quantity`, `image_path`, `created_at`, `updated_at`, `is_active`, `sku`, `weight`, `color`, `size`, `brand`) VALUES
(1, 'Men\'s Classic T-Shirt', '100% cotton classic fit t-shirt for men', 'Men', 24.99, 50, 'images/mens-tshirt.jpg', '2025-03-30 17:23:35', '2025-03-30 17:23:35', 1, 'MTS-001', 0.25, 'White', 'M,L,XL', 'TrendsWear'),
(2, 'Women\'s Summer Dress', 'Lightweight summer dress with floral pattern', 'Women', 49.99, 30, 'images/womens-dress.jpg', '2025-03-30 17:23:35', '2025-03-30 17:23:35', 1, 'WDR-001', 0.35, 'Blue', 'S,M,L', 'Fashionista'),
(3, 'Running Shoes', 'Comfortable running shoes with cushioned soles', 'Shoes', 89.99, 25, 'images/running-shoes.jpg', '2025-03-30 17:23:35', '2025-03-30 17:23:35', 1, 'SHO-001', 0.80, 'Black', '8,9,10,11', 'RunFast'),
(4, 'Leather Wallet', 'Genuine leather wallet with multiple card slots', 'Accessories', 34.99, 40, 'images/leather-wallet.jpg', '2025-03-30 17:23:35', '2025-03-30 17:23:35', 1, 'ACC-001', 0.15, 'Brown', 'One Size', 'LeatherCraft'),
(5, 'Denim Jeans', 'Classic straight fit denim jeans for men', 'Men', 59.99, 35, 'images/denim-jeans.jpg', '2025-03-30 17:23:35', '2025-03-30 17:23:35', 1, 'MJE-001', 0.60, 'Blue', '30,32,34,36', 'DenimCo'),
(6, 'Silk Scarf', 'Luxury silk scarf with geometric pattern', 'Accessories', 29.99, 20, 'images/silk-scarf.jpg', '2025-03-30 17:23:35', '2025-03-30 17:23:35', 1, 'ACC-002', 0.10, 'Red', 'One Size', 'SilkStyle'),
(7, 'High Heels', 'Elegant high heels for special occasions', 'Shoes', 79.99, 15, 'images/high-heels.jpg', '2025-03-30 17:23:35', '2025-03-30 17:23:35', 1, 'SHO-002', 0.50, 'Black', '6,7,8', 'Elegance');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `User_Id` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `FullName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`User_Id`, `Username`, `Email`, `Password`, `FullName`) VALUES
(1, 'akil', 'akilcyrylle@gmail.com', '$2y$10$ENdubzy0sJyZwmRk8YPi6eYfFqNruqt.PeSmYKPV2Lr', 'akill'),
(2, 'eykil', 'akilylle@gmail.com', '$2y$10$eRB1pi31ZldO6grk2opYr.ISs62GjkOxswMhqCXM1jf', 'asw'),
(3, 'akil', 'akilkylle@gmail.com', '$2y$10$6w/Cgh9bm8wWN.z2vR945.dJL60C8XGzd12EbJFH6lv', 'asw'),
(4, 'john', 'jon@gmail.com', '$2y$10$LvIfoPyahfGwP62PnXAt0O3FYAk9nATp3HG7vV6VBoE', 'janny'),
(5, 'johny', 'jjan@gmail.com', '$2y$10$qKfB3w0gz20QMs.mvXphi./DtzaMPZZznF0bl5I.RQK', 'asq'),
(6, 'jane', 'jane@gmail.com', '$2y$10$AyW.sBUJNPI7fKkpTjk3p.X1/S7WP2wuZoXgyhQEPXV', ''),
(7, 'asy', 'asy@gmail.com', '$2y$10$PT2fNtt8eRtfBklZAUOXhu1VVNnKB66Yq.unmBQqHWCEPnIkx21Ua', 'asym');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `customer_id`, `product_id`, `added_at`) VALUES
(11, 1, 5, '2023-01-15 02:30:00'),
(12, 1, 8, '2023-01-20 06:45:00'),
(13, 2, 3, '2023-02-05 01:15:00'),
(14, 3, 5, '2023-02-10 08:20:00'),
(15, 3, 12, '2023-02-10 08:20:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_default` (`customer_id`,`is_default`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category` (`category`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`User_Id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `User_Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
