-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2025 at 01:42 PM
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
-- Database: `mess_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `sno` int(11) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `mobile` bigint(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`sno`, `fname`, `lname`, `email`, `password`, `mobile`) VALUES
(1, 'Admin', 'Admin', 'admin@gmail.com', 'admin@123', 9988776655);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `attendance_date` date NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `meal_type`, `attendance_date`, `created_at`) VALUES
(1, 5, 'Breakfast', '2025-03-29', '2025-03-29 16:42:57'),
(2, 6, 'Breakfast', '2025-03-29', '2025-03-29 16:42:57');

-- --------------------------------------------------------

--
-- Table structure for table `attendance1`
--

CREATE TABLE `attendance1` (
  `sno` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `attendance` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance1`
--

INSERT INTO `attendance1` (`sno`, `id`, `date`, `attendance`) VALUES
(1, 1, '2021-04-17', 'Present'),
(2, 2, '2021-04-17', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `attendance2`
--

CREATE TABLE `attendance2` (
  `sno` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `attendance` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance2`
--

INSERT INTO `attendance2` (`sno`, `id`, `date`, `attendance`) VALUES
(1, 1, '2021-04-17', 'Present'),
(2, 2, '2021-04-17', 'Present'),
(3, 5, '2025-03-29', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `attendance3`
--

CREATE TABLE `attendance3` (
  `sno` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `attendance` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance3`
--

INSERT INTO `attendance3` (`sno`, `id`, `date`, `attendance`) VALUES
(1, 1, '2021-04-17', 'Absent'),
(2, 2, '2021-04-17', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `attendance4`
--

CREATE TABLE `attendance4` (
  `sno` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `attendance` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance4`
--

INSERT INTO `attendance4` (`sno`, `id`, `date`, `attendance`) VALUES
(1, 1, '2021-04-17', 'Present'),
(2, 2, '2021-04-17', 'Present'),
(3, 3, '2021-04-17', 'Present'),
(4, 6, '2025-03-29', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `sno` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `rating` varchar(100) NOT NULL,
  `feedback` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`sno`, `uid`, `date`, `rating`, `feedback`) VALUES
(2, 2, '2021-04-16', 'Good', 'Food is awesome'),
(3, 1, '2021-04-16', 'Excellent', 'Delicious food.'),
(4, 5, '2025-03-29', 'Excellent', 'very good');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `frequency` enum('one-time','monthly','quarterly','yearly') NOT NULL DEFAULT 'one-time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `name`, `description`, `amount`, `frequency`, `created_at`) VALUES
(1, 'tiffin', '.', 1000.00, 'monthly', '2025-03-29 16:42:36');

-- --------------------------------------------------------

--
-- Table structure for table `fee_transactions`
--

CREATE TABLE `fee_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('add','payment') NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `food_orders`
--

CREATE TABLE `food_orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_type` varchar(50) NOT NULL,
  `order_date` date NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `food_prices`
--

CREATE TABLE `food_prices` (
  `id` int(11) NOT NULL,
  `meal_type` varchar(20) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_prices`
--

INSERT INTO `food_prices` (`id`, `meal_type`, `price`, `last_updated`) VALUES
(1, 'Breakfast', 50.00, '2025-03-29 15:04:41'),
(2, 'Lunch', 80.00, '2025-03-29 15:04:41'),
(3, 'Snacks', 30.00, '2025-03-29 15:04:41'),
(4, 'Dinner', 80.00, '2025-03-29 15:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `sno` int(11) NOT NULL,
  `day` varchar(100) NOT NULL,
  `meal1` varchar(250) NOT NULL,
  `meal2` varchar(250) NOT NULL,
  `meal3` varchar(250) NOT NULL,
  `meal4` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`sno`, `day`, `meal1`, `meal2`, `meal3`, `meal4`) VALUES
(1, 'Monday', 'One grapefruit, Two poached eggs (or fried in a non-stick pan)', 'Chicken breast (5-ounce portion), baked or roasted (not breaded or fried), Large garden salad with tomato and onion with one cup croutons, topped with one tablespoon oil and vinegar (or salad dressing)', 'One-half piece of pita bread, Glass of water or herbal tea', 'Two cup steamed broccoli, One cup of brown rice, Small garden salad with one cup spinach leaves, tomato, and onion topped with two tablespoons oil and vinegar or salad dressing'),
(2, 'Tuesday', 'One whole-wheat English muffin with two tablespoons peanut butter, One orange, Large glass (12 ounces) non-fat milk, One cup of black coffee or herbal tea', 'A turkey sandwich (six ounces of turkey breast meat, large tomato slice, green lettuce and mustard on two slices of whole wheat bread\r\nOne cup low-sodium vegetable soup', 'One cup (about 30) grapes, Glass of water or herbal tea', 'Five-ounce sirloin steak, One cup mashed potatoes, One cup cooked spinach, One cup green beans'),
(3, 'Wednesday', 'One medium bran muffin, One serving turkey breakfast sausage, One orange, One cup non-fat milk', 'Low sodium chicken noodle soup with six saltine crackers, One medium apple', 'One apple, One slice Swiss cheese, Sparkling water with lemon or lime slice', '8-ounce serving of turkey breast meat, One cup baked beans, One cup cooked carrots, One cup cooked kale'),
(4, 'Thursday', 'One cup whole wheat flakes with one cup non-fat milk and one teaspoon sugar, One banana, One slice whole-grain toast with one tablespoon peanut butter, One cup of black coffee or herbal tea', 'Tuna wrap with one wheat flour tortilla, one-half can water-packed tuna (drained), one tablespoon mayonnaise, lettuce, and sliced tomato\r\nOne sliced avocado, One cup non-fat milk', 'One cup cottage cheese (1-percent fat), One fresh pineapple slice, Four graham crackers, Sparkling water with lemon or lime slice', 'One serving lasagna, Small garden salad with tomatoes and onions topped with one tablespoon salad dressing, One cup non-fat milk'),
(5, 'Friday', 'One piece of French toast with one tablespoon maple syrup, One scrambled or poached egg', 'Veggie burger on a whole grain bun, One cup northern (or other dry) beans, One cup non-fat milk', 'One apple, One pita with two tablespoons hummus, Sparkling water with lemon or lime slice', 'One trout filet, One cup green beans, One cup brown rice, One small garden salad with two tablespoons salad dressing'),
(6, 'Saturday', 'One cup corn flakes with two teaspoons sugar and one cup non-fat milk, One banana, One hard-boiled egg', 'One cup whole wheat pasta with one-half cup red pasta sauce, Medium garden salad with tomatoes and onions and two tablespoons salad dressing', 'One and one-half cup cottage cheese, One fresh peach', 'Four and one-half ounce serving of pork loin,Small garden salad with tomatoes and onions topped with two tablespoons oil and vinegar (or salad dressing), One small baked sweet potato'),
(7, 'Sunday', 'One cup cooked oatmeal with one-half cup blueberries, one-half cup non-fat milk, and one tablespoon almond slivers', 'Six-ounce baked chicken breast, Large garden salad with tomatoes and onions and two tablespoons salad dressing, One baked sweet potato', 'ne cup raw broccoli florets, One cup raw sliced carrot, Two tablespoons veggie dip or salad dressing', '3-ounce serving of baked or grilled salmon, One-half cup black beans, One cup Swiss chard, One cup brown rice, One whole wheat dinner roll with a pat of butter');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `items` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `day`, `meal_type`, `items`, `created_at`, `updated_at`) VALUES
(1, 'Monday', 'Breakfast', 'tea', '2025-03-29 15:20:26', '2025-03-29 15:20:26');

-- --------------------------------------------------------

--
-- Table structure for table `mess_stock`
--

CREATE TABLE `mess_stock` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mess_stock`
--

INSERT INTO `mess_stock` (`id`, `item_name`, `quantity`, `unit`, `price_per_unit`, `created_at`, `updated_at`) VALUES
(1, 'Rice', 100.00, 'kg', 40.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(2, 'Wheat Flour', 50.00, 'kg', 35.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(3, 'Cooking Oil', 15.00, 'l', 120.00, '2025-03-29 15:03:48', '2025-03-29 16:42:03'),
(4, 'Onions', 30.00, 'kg', 25.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(5, 'Tomatoes', 20.00, 'kg', 30.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(6, 'Potatoes', 40.00, 'kg', 20.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(7, 'Salt', 10.00, 'kg', 15.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(8, 'Sugar', 15.00, 'kg', 45.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(9, 'Tea Leaves', 5.00, 'kg', 200.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48'),
(10, 'Milk', 20.00, 'l', 60.00, '2025-03-29 15:03:48', '2025-03-29 15:03:48');

-- --------------------------------------------------------

--
-- Table structure for table `stock_usage_history`
--

CREATE TABLE `stock_usage_history` (
  `id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity_used` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `used_by` varchar(100) NOT NULL,
  `purpose` varchar(255) DEFAULT 'Regular kitchen usage',
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_usage_history`
--

INSERT INTO `stock_usage_history` (`id`, `stock_id`, `item_name`, `quantity_used`, `unit`, `used_by`, `purpose`, `used_at`) VALUES
(1, 3, 'Cooking Oil', 5.00, 'l', 'admin@gmail.com', '', '2025-03-29 16:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `sno` int(11) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `mobile` bigint(12) NOT NULL,
  `address` varchar(250) NOT NULL,
  `fee_status` int(11) NOT NULL,
  `fee_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`sno`, `fname`, `lname`, `email`, `password`, `mobile`, `address`, `fee_status`, `fee_amount`) VALUES
(5, 'chetan', 'deshmukh', 'chetan@gmail.com', '$2y$10$SrHIFaprWZ4eTutp9IfpFuw49tgUVIT04VkY8wLrBYK/.WaATqiP6', 7654321098, 'nsk', 0, 0.00),
(6, 'ritesh', 'ahirrao', 'ritesh@gmail.com', '$2y$10$b6rGmakzEPMcnQNOUGH5iORzc44sQ5sCijmlaN7AN6OqrAzvsF8sW', 2345986271, '', 0, 0.00),
(7, 'Om', 'Deshmukh', 'om575@gmail.com', '$2y$10$Q/ERkmV09mxXStA8GBiBX..P3eYtfAPXwIOVg0NkE.j5QcbxF9fze', 1234567890, 'lkajhgf', 0, 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`sno`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`meal_type`,`attendance_date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `attendance_date` (`attendance_date`);

--
-- Indexes for table `attendance1`
--
ALTER TABLE `attendance1`
  ADD PRIMARY KEY (`sno`);

--
-- Indexes for table `attendance2`
--
ALTER TABLE `attendance2`
  ADD PRIMARY KEY (`sno`);

--
-- Indexes for table `attendance3`
--
ALTER TABLE `attendance3`
  ADD PRIMARY KEY (`sno`);

--
-- Indexes for table `attendance4`
--
ALTER TABLE `attendance4`
  ADD PRIMARY KEY (`sno`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`sno`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_transactions`
--
ALTER TABLE `fee_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_orders`
--
ALTER TABLE `food_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `food_prices`
--
ALTER TABLE `food_prices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`sno`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `day_meal` (`day`,`meal_type`);

--
-- Indexes for table `mess_stock`
--
ALTER TABLE `mess_stock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_usage_history`
--
ALTER TABLE `stock_usage_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_id` (`stock_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`sno`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance1`
--
ALTER TABLE `attendance1`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance2`
--
ALTER TABLE `attendance2`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance3`
--
ALTER TABLE `attendance3`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance4`
--
ALTER TABLE `attendance4`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fee_transactions`
--
ALTER TABLE `fee_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_orders`
--
ALTER TABLE `food_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_prices`
--
ALTER TABLE `food_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mess_stock`
--
ALTER TABLE `mess_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_usage_history`
--
ALTER TABLE `stock_usage_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `food_orders`
--
ALTER TABLE `food_orders`
  ADD CONSTRAINT `food_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`sno`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
