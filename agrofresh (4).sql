-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2025 at 06:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agrofresh`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `is_guest` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `is_guest`, `created_at`, `updated_at`) VALUES
(73, '1', 7, 1, 0, '2025-03-25 15:51:11', '2025-03-25 15:51:11'),
(74, 'e481d7kaj2u4frniahrv01atup', 9, 1, 1, '2025-03-25 16:49:43', '2025-03-25 16:49:43');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_address`, `payment_method`, `status`, `created_at`) VALUES
(1, 8, 21.00, 'hbhtybhynuimio8, pala, Nagaland - 684648', 'cod', 'pending', '2025-03-24 04:56:42'),
(3, 8, 21.00, 'hbhtybhynuimio8, pala, Nagaland - 684648', 'cod', 'pending', '2025-03-24 05:04:07'),
(28, 1, 42.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'cancelled', '2025-03-25 06:35:45'),
(29, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', 'cancelled', '2025-03-25 06:51:35'),
(30, 2, 700.00, 'palai, pala, Goa - 555555', 'cod', 'pending', '2025-03-25 06:52:49'),
(31, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', 'cancelled', '2025-03-25 06:53:25'),
(34, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', 'pending', '2025-03-25 08:07:48'),
(35, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'cancelled', '2025-03-25 08:08:16'),
(36, 1, 63.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'cancelled', '2025-03-25 08:51:11'),
(37, 1, 105.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'cancelled', '2025-03-25 09:10:05'),
(38, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'cancelled', '2025-03-25 15:49:45');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 7, 1, 21.00),
(2, 3, 7, 1, 21.00),
(3, 28, 7, 2, 21.00),
(4, 29, 9, 1, 350.00),
(5, 30, 9, 2, 350.00),
(6, 31, 9, 1, 350.00),
(7, 34, 9, 1, 350.00),
(8, 35, 7, 1, 21.00),
(9, 36, 7, 3, 21.00),
(10, 37, 7, 5, 21.00),
(11, 38, 7, 1, 21.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `description`, `price`, `image_url`, `category`, `stock`, `created_at`, `updated_at`) VALUES
(7, 2, 'Tomato', 'FGBGGGGGGGHNN', 21.00, 'uploads/1742786576_67e0d010e240d2.54484365.jpg', 'vegetables', 4, '2025-03-24 03:22:56', '2025-03-25 15:49:45'),
(9, 1, 'honey', 'kuttichans fresh honey', 350.00, 'uploads/1742802542_67e10e6ea8e4a2.15577871.jpg', 'other', 54, '2025-03-24 07:49:02', '2025-03-25 16:54:21');

-- --------------------------------------------------------

--
-- Table structure for table `seller`
--

CREATE TABLE `seller` (
  `seller_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `seller_name` varchar(255) NOT NULL,
  `location` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellerdetails`
--

CREATE TABLE `sellerdetails` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellerdetails`
--

INSERT INTO `sellerdetails` (`id`, `full_name`, `email`, `mobile`, `address`, `state`, `pincode`, `password`, `created_at`) VALUES
(1, 'rehan', 'rehansnair@gmail.com', '7907243685', 'cdhtyjukyukuyk', 'Jharkhand', '567869', '$2y$10$Udz6eSJrbUhHDKCf3wILh.neKnbcXaV.4i1mIybbY9EcDy5EaTl6S', '2025-03-20 08:07:10'),
(2, 'barroz', 'barrozghost@gmail.com', '9999945123', 'palai', 'Goa', '555555', '$2y$10$oi3xjpamh8P81FAoXpHhIO7Hkg9B0yJ58yX40swSx5PWZ4IPhC6E6', '2025-03-19 08:43:02'),
(3, 'divin babu', 'divin@gmail.com', '7684923732', 'bngnhnnm', 'Kerala', '678986', '$2y$10$o0CrYVIVbNUpWN1OlZ.sxuYkqe69KGSvqUtxgMJSEcnPU5w3aTfDi', '2025-03-19 09:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(10) NOT NULL DEFAULT 'user',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `city` varchar(100) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT 'default-avatar.png',
  `admin_email` varchar(255) DEFAULT NULL,
  `admin_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `mobile`, `address`, `state`, `pincode`, `password`, `created_at`, `role`, `is_admin`, `city`, `photo_url`, `admin_email`, `admin_password`) VALUES
(1, 'REHAN', 'rehansnair@gmail.com', '7907243685', 'cdhtyjukyukuyk', 'Jharkhand', '567869', '$2y$10$Udz6eSJrbUhHDKCf3wILh.neKnbcXaV.4i1mIybbY9EcDy5EaTl6S', '2025-03-19 08:18:52', 'user', 0, 'pala', 'uploads/profile_photos/profile_1_1742406644.png', NULL, NULL),
(2, 'barroz', 'barrozghost@gmail.com', '9999945123', 'palai', 'Goa', '555555', '$2y$10$oi3xjpamh8P81FAoXpHhIO7Hkg9B0yJ58yX40swSx5PWZ4IPhC6E6', '2025-03-19 08:40:36', 'user', 0, 'pala', 'uploads/profile_photos/profile_2_1742460441.jpg', NULL, NULL),
(3, 'divin babu', 'divin@gmail.com', '7684923732', 'bngnhnnm', 'Kerala', '678986', '$2y$10$o0CrYVIVbNUpWN1OlZ.sxuYkqe69KGSvqUtxgMJSEcnPU5w3aTfDi', '2025-03-19 09:10:50', 'user', 0, NULL, 'default-avatar.png', NULL, NULL),
(4, 'AgroFresh Admin', 'agrofresh.admin@gmail.com', '', '', '', '', '$2y$10$oi3xjpamh8P81FAoXpHhIO7Hkg9B0yJ58yX40swSx5PWZ4IPhC6E6', '2025-03-19 18:24:32', 'admin', 1, NULL, 'default-avatar.png', 'agrofresh.admin@gmail.com', '$2y$10$YOZ9qQ9P6RzJ.tWY5hP8vOXGK2AgLG/1PqP1Y2Q5Y6Q6Y6Q6Y6Q6Y'),
(6, 'alwin', 'alwin@gmail.com', '7863428393', 'dvgyhjujmjilol', 'Kerala', '686574', '$2y$10$A5pOWnRVwTaKKRPcBrQH3uoqetHYcssukfbXVi/Pw8z..QVQhHzdC', '2025-03-24 03:52:32', 'user', 0, NULL, 'default-avatar.png', NULL, NULL),
(7, 'rohini', 'rohini@gmail.com', '7889846489', 'fbhni,ol7iokuijtuhty', 'Kerala', '958476', '$2y$10$ZvOCHpSlyQKJxhT6PkK4PuQWMfjdMcojzMt/KYnFy4Elkp0E0VhHi', '2025-03-24 03:53:48', 'user', 0, NULL, 'default-avatar.png', NULL, NULL),
(8, 'pakku', 'pakku@gmail.com', '8676588464', 'hbhtybhynuimio8', 'Nagaland', '684648', '$2y$10$2Y3tiE/vnIeSYFz3iIJKSedFzc8x6zw/cNpXGVPyp7kxA8I3d6beW', '2025-03-24 03:58:46', 'delivery', 0, NULL, 'default-avatar.png', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `seller`
--
ALTER TABLE `seller`
  ADD PRIMARY KEY (`seller_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `sellerdetails`
--
ALTER TABLE `sellerdetails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admin_email` (`admin_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `seller`
--
ALTER TABLE `seller`
  MODIFY `seller_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellerdetails` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seller`
--
ALTER TABLE `seller`
  ADD CONSTRAINT `seller_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sellerdetails`
--
ALTER TABLE `sellerdetails`
  ADD CONSTRAINT `sellerdetails_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
