
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'paul', 'dacoliatpaul@gmail.com', 'laptop', 'some keys are not working', '2026-06-17 16:46:43'),
(3, 'Maku', 'maku@gmail.com', 'Price', 'ang mahal', '2026-06-19 09:01:35');

-

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `shipping_address`, `payment_method`, `created_at`) VALUES
(1, 1, 3200.00, 'cancelled', NULL, NULL, '2026-06-11 18:24:41'),
(2, 1, 25000.00, 'delivered', NULL, NULL, '2026-06-11 18:27:03'),
(3, 1, 4200.00, 'processing', NULL, NULL, '2026-06-11 18:32:46'),
(4, 1, 4200.00, 'shipped', NULL, NULL, '2026-06-15 15:37:44'),
(5, 2, 3200.00, 'cancelled', NULL, NULL, '2026-06-16 02:00:39'),
(6, 1, 4800.00, 'processing', NULL, NULL, '2026-06-17 04:47:07'),
(7, 1, 120000.00, 'pending', NULL, NULL, '2026-06-17 05:12:14'),
(8, 1, 11800.00, 'shipped', NULL, NULL, '2026-06-17 16:27:42'),
(9, 1, 4800.00, 'pending', 'olivarex homes, barangay milagrosa, calamba city,  laguna', 'cod', '2026-06-18 08:42:36'),
(10, 1, 131800.00, 'pending', 'jan lang po', 'gcash', '2026-06-19 09:00:30'),
(11, 1, 11800.00, 'pending', 'olivarez homes, barangay milagorsa, calamba city, laguna', 'gcash', '2026-06-19 10:34:41');


CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 8, 1, 3200.00),
(2, 2, 11, 1, 5500.00),
(3, 2, 13, 1, 12600.00),
(4, 2, 1, 1, 4800.00),
(5, 2, 5, 1, 2100.00),
(6, 3, 12, 1, 4200.00),
(7, 4, 12, 1, 4200.00),
(8, 5, 8, 1, 3200.00),
(9, 6, 1, 1, 4800.00),
(10, 7, 16, 1, 120000.00),
(11, 8, 14, 1, 11800.00),
(12, 9, 4, 1, 4800.00),
(13, 10, 16, 1, 120000.00),
(14, 10, 14, 1, 11800.00),
(15, 11, 14, 1, 11800.00);


CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT '',
  `featured` tinyint(1) DEFAULT 0,
  `wide` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



INSERT INTO `products` (`id`, `name`, `category`, `price`, `image`, `featured`, `wide`, `created_at`) VALUES
(1, 'White Executive Longsleeve', 'Clothes', 4800.00, 'img/shirt2.png', 1, 1, '2026-06-11 16:07:42'),
(2, 'Black Executive Longsleeve', 'Clothes', 4800.00, 'img/shirt1.png', 0, 1, '2026-06-11 16:07:42'),
(3, 'Dark Blue Executive Longsleeve', 'Clothes', 4800.00, 'img/shirt3.png', 0, 1, '2026-06-11 16:07:42'),
(4, 'Biege Executive Longsleeve', 'Clothes', 4800.00, 'img/shirt4.png', 0, 1, '2026-06-11 16:07:42'),
(5, 'Raw Hem Denim Trousers', 'Clothes', 2100.00, 'img/baggy1.jpeg', 1, 0, '2026-06-11 16:07:42'),
(6, 'Washed Cotton Tee', 'Clothes', 890.00, 'img/cotton1.jpeg', 0, 0, '2026-06-11 16:07:42'),
(7, 'Merino Wool Crewneck', 'Clothes', 3400.00, 'img/wool1.jpeg', 0, 0, '2026-06-11 16:07:42'),
(8, 'Minimal Leather Bag', 'Accessories', 3200.00, 'img/bag1.jpeg', 1, 0, '2026-06-11 16:07:42'),
(9, 'Woven Leather Belt', 'Accessories', 1100.00, 'img/belt1.jpeg', 0, 0, '2026-06-11 16:07:42'),
(10, 'Brushed Silver Ring', 'Accessories', 980.00, 'img/ring1.jpeg', 0, 0, '2026-06-11 16:07:42'),
(11, 'DCO Wireless Speaker', 'Devices', 5500.00, 'img/speaker1.jpeg', 1, 0, '2026-06-11 16:07:42'),
(12, 'Noise-Cancel Earphones', 'Devices', 4200.00, 'img/device1.jpeg', 0, 0, '2026-06-11 16:07:42'),
(13, 'Noir Santal Eau de Parfum', 'Fragrance', 12600.00, 'img/perfume1.jpeg', 1, 0, '2026-06-11 16:07:42'),
(14, 'Amber & Oud Diffuser Set', 'Fragrance', 11800.00, 'img/perfume2.jpeg', 1, 0, '2026-06-11 16:07:42'),
(16, 'Venom Wave (5090)', 'Devices', 120000.00, 'img/venom-wave-5090-1781672757.jpg', 1, 0, '2026-06-17 05:05:57');



CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



INSERT INTO `users` (`id`, `email`, `full_name`, `phone`, `address`, `password_hash`, `created_at`) VALUES
(1, 'dacoliatpaul@gmail.com', 'Paul Joshua Dacoliat', '09692899764', 'olivarez homes, barangay milagorsa, calamba city, laguna', '$2y$10$7eJo2Ee3pGwDWMy84jbYWe1Po5nejLlbYpyB4tNJ.cJEIU.1ea0DC', '2026-06-09 02:38:58'),
(2, 'dco@admin.com', NULL, NULL, NULL, '$2y$10$5JQR4DTiITL0mc1FjVvaNe01k3sALv1yMaJfrRH.Pewm1O9fM6NZK', '2026-06-15 23:35:43'),
(3, 'maku@gmail.com', NULL, NULL, NULL, '$2y$10$0K2rBwlQkErh0/pjjiSvne/SZOMAUb43R6ttVA9V2wS9SU6Ii.yIa', '2026-06-18 17:04:52');


ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);


ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);


ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
