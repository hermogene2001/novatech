-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 22, 2025 at 01:30 PM
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
-- Database: `kinginvest`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_page`
--

CREATE TABLE `about_page` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_page`
--

INSERT INTO `about_page` (`id`, `content`) VALUES
(1, 'hello');

-- --------------------------------------------------------

--
-- Table structure for table `bank_updates_log`
--

CREATE TABLE `bank_updates_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_bank_name` varchar(255) DEFAULT NULL,
  `old_account_number` varchar(20) DEFAULT NULL,
  `old_account_holder` varchar(255) DEFAULT NULL,
  `new_bank_name` varchar(255) DEFAULT NULL,
  `new_account_number` varchar(20) DEFAULT NULL,
  `new_account_holder` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carousel_slides`
--

CREATE TABLE `carousel_slides` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_name` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `slide_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_slides`
--

INSERT INTO `carousel_slides` (`id`, `title`, `description`, `image_name`, `alt_text`, `slide_order`, `active`, `created_at`, `updated_at`) VALUES
(4, 'slide4', 'her', 'slide_67c80b969bb611.93646768.jpg', 'rr', 1, 1, '2025-03-04 11:27:58', '2025-03-05 08:30:36'),
(5, 'slide2', 'Will King Investment have the same three dashboards (client, admin, agent) as DeltaOneInvestment, or are you planning to introduce new roles?', 'WhatsApp Image 2024-11-25 at 10.48.09_81eaddd7.jpg', 'si2', 2, 1, '2025-03-04 11:53:51', '2025-03-05 08:31:18');

-- --------------------------------------------------------

--
-- Table structure for table `clients_finances`
--

CREATE TABLE `clients_finances` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_revenue` decimal(10,2) DEFAULT 0.00,
  `invitation_income` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compound_investments`
--

CREATE TABLE `compound_investments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `investment_amount` decimal(10,2) NOT NULL,
  `daily_profit` decimal(10,2) NOT NULL,
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `maturity_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lock_in_period` int(11) NOT NULL DEFAULT 30,
  `profit_accumulated` decimal(10,2) DEFAULT 0.00,
  `withdrawable_amount` decimal(10,2) DEFAULT 0.00,
  `last_profit_update` timestamp NOT NULL DEFAULT current_timestamp(),
  `pending_withdrawal` tinyint(1) DEFAULT 0,
  `early_withdrawal_penalty` decimal(5,2) DEFAULT 0.00,
  `total_balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','completed','withdrawn') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, 'ert', 'serty@gmail.com', 'erety', '2025-03-02 21:06:05');

-- --------------------------------------------------------

--
-- Table structure for table `investments`
--

CREATE TABLE `investments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `invested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed') DEFAULT 'active',
  `daily_profit` decimal(10,2) DEFAULT 0.00,
  `last_profit_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `daily_earning` decimal(10,2) NOT NULL,
  `cycle` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `profit_rate` int(11) DEFAULT NULL,
  `min_withdraw` decimal(10,2) DEFAULT 3000.00,
  `referral_level1_percentage` decimal(5,2) DEFAULT 3.00,
  `referral_level2_percentage` decimal(5,2) DEFAULT 1.00,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products_compound`
--

CREATE TABLE `products_compound` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `profit_rate` decimal(5,2) NOT NULL,
  `cycle` int(11) NOT NULL,
  `daily_earning` decimal(10,2) NOT NULL,
  `image` varchar(232) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `purchase_date` datetime DEFAULT NULL,
  `last_earned` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `status` enum('active','completed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recharges`
--

CREATE TABLE `recharges` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `amount` float NOT NULL,
  `recharge_time` datetime DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `referred_id` int(11) NOT NULL,
  `referral_code` varchar(20) NOT NULL,
  `referral_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `investment_cycle` int(11) NOT NULL,
  `daily_profit_rate` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(11) NOT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `telegram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `whatsapp`, `telegram`, `facebook`, `twitter`) VALUES
(1, 'https://wa.me/256766921920', 'https://t.me/yourchannel', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('withdrawal','deposit','compound','purchase','daily_earning','investment','capital_return','recharge') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(210) DEFAULT NULL,
  `phone_number` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `invitation_code` varchar(50) DEFAULT NULL,
  `referral_code` varchar(50) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('client','admin','agent') DEFAULT 'client',
  `referral_bonus` decimal(10,2) DEFAULT 0.00,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `email` varchar(100) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `phone_number`, `password`, `invitation_code`, `referral_code`, `balance`, `created_at`, `role`, `referral_bonus`, `profile_picture`, `status`, `email`, `updated_at`) VALUES
(1, 'UKUNDAYEZU', NULL, '0788912775', '$2y$10$.Y7oPDx5TOllTBJjWksOK.cSd5ZNJ1l5xqzsyBZUZVXjmE7ggdXei', NULL, 'K3LEH6A0', 0.00, '2025-03-08 08:13:07', 'client', 0.00, NULL, 'active', '', '2025-03-08 08:13:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_banks`
--

CREATE TABLE `user_banks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `account_holder` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('withdrawal') NOT NULL,
  `date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `source` enum('compound','main') NOT NULL DEFAULT 'main',
  `agent_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_page`
--
ALTER TABLE `about_page`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_updates_log`
--
ALTER TABLE `bank_updates_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients_finances`
--
ALTER TABLE `clients_finances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `compound_investments`
--
ALTER TABLE `compound_investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`client_id`),
  ADD KEY `compound_investments_ibfk_2` (`product_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products_compound`
--
ALTER TABLE `products_compound`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `recharges`
--
ALTER TABLE `recharges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `referred_id` (`referred_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `referral_code` (`referral_code`);

--
-- Indexes for table `user_banks`
--
ALTER TABLE `user_banks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `fk_withdrawals_agents` (`agent_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_page`
--
ALTER TABLE `about_page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank_updates_log`
--
ALTER TABLE `bank_updates_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clients_finances`
--
ALTER TABLE `clients_finances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compound_investments`
--
ALTER TABLE `compound_investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products_compound`
--
ALTER TABLE `products_compound`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recharges`
--
ALTER TABLE `recharges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_banks`
--
ALTER TABLE `user_banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients_finances`
--
ALTER TABLE `clients_finances`
  ADD CONSTRAINT `clients_finances_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `compound_investments`
--
ALTER TABLE `compound_investments`
  ADD CONSTRAINT `compound_investments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `compound_investments_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products_compound` (`id`);

--
-- Constraints for table `investments`
--
ALTER TABLE `investments`
  ADD CONSTRAINT `investments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `recharges`
--
ALTER TABLE `recharges`
  ADD CONSTRAINT `recharges_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `recharges_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
