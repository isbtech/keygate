-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 28, 2025 at 07:12 AM
-- Server version: 8.0.37
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `keygatevfull_clakeygatedbs`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_gates`
--

CREATE TABLE `access_gates` (
  `id` int NOT NULL,
  `gate_name` varchar(100) NOT NULL,
  `gate_status` enum('Open','Close') DEFAULT 'Close',
  `event_id` int NOT NULL,
  `access_time_start` time DEFAULT NULL,
  `access_time_end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `access_gates`
--

INSERT INTO `access_gates` (`id`, `gate_name`, `gate_status`, `event_id`, `access_time_start`, `access_time_end`, `created_at`, `updated_at`) VALUES
(1, 'Clients', 'Close', 1, '10:30:00', '05:30:00', '2025-03-28 00:58:42', '2025-03-28 01:06:14'),
(2, 'Partners', 'Close', 1, '10:30:00', '04:00:00', '2025-03-28 00:58:58', '2025-03-28 00:58:58'),
(3, 'Referals', 'Close', 1, '12:30:00', '07:00:00', '2025-03-28 00:59:15', '2025-03-28 00:59:15');

-- --------------------------------------------------------

--
-- Table structure for table `delegate_gate_access`
--

CREATE TABLE `delegate_gate_access` (
  `id` int NOT NULL,
  `delegate_id` int NOT NULL,
  `gate_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_organiser` varchar(255) NOT NULL,
  `event_venue` text NOT NULL,
  `access_gates_required` int NOT NULL,
  `created_by` int NOT NULL,
  `status` enum('Active','Completed','Cancelled','Scheduled','Pending Approval') DEFAULT 'Pending Approval',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `event_organiser`, `event_venue`, `access_gates_required`, `created_by`, `status`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'vShop Launch', 'vdefine', 'Digital World', 3, 2, 'Active', 2, '2025-03-28 00:55:27', '2025-03-28 00:55:27');

-- --------------------------------------------------------

--
-- Table structure for table `event_attendance`
--

CREATE TABLE `event_attendance` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `gate_id` int NOT NULL,
  `delegate_id` int NOT NULL,
  `marked_by` int NOT NULL,
  `check_in_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_dates`
--

CREATE TABLE `event_dates` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `event_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_dates`
--

INSERT INTO `event_dates` (`id`, `event_id`, `event_date`) VALUES
(1, 1, '2025-04-08'),
(2, 1, '2025-04-09'),
(3, 1, '2025-04-10');

-- --------------------------------------------------------

--
-- Table structure for table `event_delegates`
--

CREATE TABLE `event_delegates` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `verified_by` int DEFAULT NULL,
  `approved` enum('Yes','No') DEFAULT 'No',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_users`
--

CREATE TABLE `event_users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `event_id` int NOT NULL,
  `role_id` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT NULL,
  `last_active_gate` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_users`
--

INSERT INTO `event_users` (`id`, `name`, `mobile`, `email`, `password`, `event_id`, `role_id`, `approved_by`, `last_active`, `last_active_gate`, `created_at`, `updated_at`) VALUES
(1, 'gatekeeper', '7445222', 'gatekeeper@keygate.com', '$2y$10$0jRACXKXHijlo63yNE2zMezgAccglQufpMkhlWky0qsmUKOyemdxG', 1, 2, 2, '2025-03-27 20:03:22', NULL, '2025-03-28 01:22:31', '2025-03-28 01:33:22');

-- --------------------------------------------------------

--
-- Table structure for table `event_user_roles`
--

CREATE TABLE `event_user_roles` (
  `id` int NOT NULL,
  `role_name` enum('Event Manager','GateKeeper','Volunteer') NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_user_roles`
--

INSERT INTO `event_user_roles` (`id`, `role_name`, `description`, `created_at`) VALUES
(1, 'Event Manager', 'Can manage and assign roles, open/close gates', '2025-03-27 23:47:47'),
(2, 'GateKeeper', 'Mark attendance at each gate during authorized window', '2025-03-27 23:47:47'),
(3, 'Volunteer', 'See total delegates authorized for each gate vs attendance marked delegates', '2025-03-27 23:47:47');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `events_allowed` int NOT NULL,
  `delegates_per_event` int NOT NULL,
  `event_auto_approval` enum('yes','no') DEFAULT 'no',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `plan_name`, `events_allowed`, `delegates_per_event`, `event_auto_approval`, `created_at`, `updated_at`) VALUES
(1, 'Basic', 1, 100, 'no', '2025-03-27 23:47:47', '2025-03-27 23:47:47'),
(2, 'Standard', 5, 500, 'no', '2025-03-27 23:47:47', '2025-03-27 23:47:47'),
(3, 'Premium', 10, 1000, 'yes', '2025-03-27 23:47:47', '2025-03-27 23:47:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','keygate_staff','Event_admin') NOT NULL,
  `plan_id` int DEFAULT NULL,
  `activated` enum('yes','no') DEFAULT 'no',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `default_staff_password` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `password`, `role`, `plan_id`, `activated`, `created_at`, `updated_at`, `default_staff_password`) VALUES
(1, 'Admin', 'admin@keygate.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', NULL, 'yes', '2025-03-27 23:47:47', '2025-03-28 00:44:15', NULL),
(2, 'event admin', 'eventadmin@keygate.com', '789456123', '$2y$10$OKs/zi3NcF91PmrlmOcSxuOszACeNJgaqcZVmMXHnadTorRswAX12', 'Event_admin', 3, 'yes', '2025-03-28 00:54:15', '2025-03-28 01:31:53', 'H&amp;cwp2GUd+');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_gates`
--
ALTER TABLE `access_gates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `delegate_gate_access`
--
ALTER TABLE `delegate_gate_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_delegate_gate` (`delegate_id`,`gate_id`),
  ADD KEY `gate_id` (`gate_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`event_id`,`gate_id`,`delegate_id`),
  ADD KEY `gate_id` (`gate_id`),
  ADD KEY `delegate_id` (`delegate_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `event_dates`
--
ALTER TABLE `event_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_delegates`
--
ALTER TABLE `event_delegates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `event_users`
--
ALTER TABLE `event_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `last_active_gate` (`last_active_gate`);

--
-- Indexes for table `event_user_roles`
--
ALTER TABLE `event_user_roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_plan` (`plan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_gates`
--
ALTER TABLE `access_gates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `delegate_gate_access`
--
ALTER TABLE `delegate_gate_access`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_attendance`
--
ALTER TABLE `event_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_dates`
--
ALTER TABLE `event_dates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `event_delegates`
--
ALTER TABLE `event_delegates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_users`
--
ALTER TABLE `event_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_user_roles`
--
ALTER TABLE `event_user_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_gates`
--
ALTER TABLE `access_gates`
  ADD CONSTRAINT `access_gates_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delegate_gate_access`
--
ALTER TABLE `delegate_gate_access`
  ADD CONSTRAINT `delegate_gate_access_ibfk_1` FOREIGN KEY (`delegate_id`) REFERENCES `event_delegates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delegate_gate_access_ibfk_2` FOREIGN KEY (`gate_id`) REFERENCES `access_gates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD CONSTRAINT `event_attendance_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendance_ibfk_2` FOREIGN KEY (`gate_id`) REFERENCES `access_gates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendance_ibfk_3` FOREIGN KEY (`delegate_id`) REFERENCES `event_delegates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendance_ibfk_4` FOREIGN KEY (`marked_by`) REFERENCES `event_users` (`id`);

--
-- Constraints for table `event_dates`
--
ALTER TABLE `event_dates`
  ADD CONSTRAINT `event_dates_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_delegates`
--
ALTER TABLE `event_delegates`
  ADD CONSTRAINT `event_delegates_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_delegates_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_users`
--
ALTER TABLE `event_users`
  ADD CONSTRAINT `event_users_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_users_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `event_users_ibfk_3` FOREIGN KEY (`last_active_gate`) REFERENCES `access_gates` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
