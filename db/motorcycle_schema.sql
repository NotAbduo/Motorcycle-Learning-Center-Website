-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2025 at 07:49 AM
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
-- Database: `motorcycle`
--

-- --------------------------------------------------------

--
-- Table structure for table `approval_logs`
--

CREATE TABLE `approval_logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `instructor_id` varchar(20) NOT NULL,
  `training_hours` decimal(3,1) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `request_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks approval requests for trainee practice hours';

-- --------------------------------------------------------

--
-- Table structure for table `approved_billing_logs`
--

CREATE TABLE `approved_billing_logs` (
  `ID` int(11) NOT NULL,
  `Instructor_ID` varchar(20) DEFAULT NULL,
  `Hours` decimal(3,1) DEFAULT NULL,
  `Date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_national_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `trainee_national_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_hourly_rates`
--

CREATE TABLE `custom_hourly_rates` (
  `id` int(11) NOT NULL,
  `Instructor_ID` varchar(50) NOT NULL,
  `pay_month` date NOT NULL,
  `log_id` int(11) NOT NULL,
  `custom_rate` decimal(10,1) NOT NULL,
  `hours` decimal(10,1) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_billing_hours`
--

CREATE TABLE `deleted_billing_hours` (
  `ID` float NOT NULL,
  `Instructor_ID` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Hours` decimal(3,1) DEFAULT NULL,
  `Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_logs`
--

CREATE TABLE `deleted_logs` (
  `log_id` int(10) NOT NULL,
  `trainee_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instructor_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `training_hours` decimal(3,1) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `request_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `national_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `role` enum('staff','admin','supervisor','registration') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor_payments`
--

CREATE TABLE `instructor_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `instructor_id` varchar(20) NOT NULL,
  `pay_month` date NOT NULL,
  `paid_hours` decimal(11,1) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks how many hours of each month have been paid per instructor';

-- --------------------------------------------------------

--
-- Table structure for table `instructor_rates`
--

CREATE TABLE `instructor_rates` (
  `Instructor_ID` varchar(50) NOT NULL,
  `default_rate` decimal(10,1) NOT NULL DEFAULT 8.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `option1` text NOT NULL,
  `option2` text NOT NULL,
  `option3` text NOT NULL,
  `correct_answer` int(11) NOT NULL,
  `is_image` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `share_links`
--

CREATE TABLE `share_links` (
  `id` int(11) NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `columns_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `companies_json` longtext DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE `sources` (
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_controls`
--

CREATE TABLE `system_controls` (
  `id` int(11) NOT NULL,
  `control_key` varchar(50) NOT NULL,
  `control_value` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainees`
--

CREATE TABLE `trainees` (
  `id` int(11) NOT NULL,
  `national_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `quiz` int(11) DEFAULT NULL,
  `sign` tinyint(1) NOT NULL DEFAULT 0,
  `added_by` varchar(255) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `source` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL DEFAULT 'male',
  `number_of_trails` int(11) DEFAULT NULL,
  `try_road` int(11) DEFAULT NULL,
  `batch` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainee_columns`
--

CREATE TABLE `trainee_columns` (
  `id` int(11) NOT NULL,
  `column_name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainee_company_filters`
--

CREATE TABLE `trainee_company_filters` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `view_passwords`
--

CREATE TABLE `view_passwords` (
  `id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waiting_billing_logs`
--

CREATE TABLE `waiting_billing_logs` (
  `ID` int(11) NOT NULL,
  `Instructor_ID` varchar(20) DEFAULT NULL,
  `Hours` decimal(3,1) DEFAULT NULL,
  `Date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waiting_logs`
--

CREATE TABLE `waiting_logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `instructor_id` varchar(20) NOT NULL,
  `training_hours` decimal(3,1) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `request_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Holds trainee‑hour requests awaiting approval';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approval_logs`
--
ALTER TABLE `approval_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_approval_trainee` (`trainee_id`),
  ADD KEY `fk_approval_instructor` (`instructor_id`);

--
-- Indexes for table `approved_billing_logs`
--
ALTER TABLE `approved_billing_logs`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Instructor_ID` (`Instructor_ID`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comment_employee` (`employee_national_id`),
  ADD KEY `fk_comment_trainee` (`trainee_national_id`);

--
-- Indexes for table `custom_hourly_rates`
--
ALTER TABLE `custom_hourly_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `log_id` (`log_id`),
  ADD KEY `idx_instructor_month` (`Instructor_ID`,`pay_month`);

--
-- Indexes for table `deleted_billing_hours`
--
ALTER TABLE `deleted_billing_hours`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `deleted_logs`
--
ALTER TABLE `deleted_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`national_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `national_id` (`national_id`);

--
-- Indexes for table `instructor_payments`
--
ALTER TABLE `instructor_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_instructor_month` (`instructor_id`,`pay_month`);

--
-- Indexes for table `instructor_rates`
--
ALTER TABLE `instructor_rates`
  ADD PRIMARY KEY (`Instructor_ID`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `share_links`
--
ALTER TABLE `share_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sources`
--
ALTER TABLE `sources`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `system_controls`
--
ALTER TABLE `system_controls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_key` (`control_key`);

--
-- Indexes for table `trainees`
--
ALTER TABLE `trainees`
  ADD PRIMARY KEY (`national_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `fk_source` (`source`);

--
-- Indexes for table `trainee_columns`
--
ALTER TABLE `trainee_columns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trainee_company_filters`
--
ALTER TABLE `trainee_company_filters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_name` (`company_name`);

--
-- Indexes for table `view_passwords`
--
ALTER TABLE `view_passwords`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `waiting_billing_logs`
--
ALTER TABLE `waiting_billing_logs`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Instructor_ID` (`Instructor_ID`);

--
-- Indexes for table `waiting_logs`
--
ALTER TABLE `waiting_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_wait_trainee` (`trainee_id`),
  ADD KEY `fk_wait_instructor` (`instructor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_logs`
--
ALTER TABLE `approval_logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approved_billing_logs`
--
ALTER TABLE `approved_billing_logs`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_hourly_rates`
--
ALTER TABLE `custom_hourly_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deleted_billing_hours`
--
ALTER TABLE `deleted_billing_hours`
  MODIFY `ID` float NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deleted_logs`
--
ALTER TABLE `deleted_logs`
  MODIFY `log_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instructor_payments`
--
ALTER TABLE `instructor_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `share_links`
--
ALTER TABLE `share_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_controls`
--
ALTER TABLE `system_controls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainees`
--
ALTER TABLE `trainees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainee_columns`
--
ALTER TABLE `trainee_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainee_company_filters`
--
ALTER TABLE `trainee_company_filters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `view_passwords`
--
ALTER TABLE `view_passwords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waiting_billing_logs`
--
ALTER TABLE `waiting_billing_logs`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waiting_logs`
--
ALTER TABLE `waiting_logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approval_logs`
--
ALTER TABLE `approval_logs`
  ADD CONSTRAINT `fk_approval_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_approval_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `approved_billing_logs`
--
ALTER TABLE `approved_billing_logs`
  ADD CONSTRAINT `approved_billing_logs_ibfk_1` FOREIGN KEY (`Instructor_ID`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_employee` FOREIGN KEY (`employee_national_id`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comment_trainee` FOREIGN KEY (`trainee_national_id`) REFERENCES `trainees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `custom_hourly_rates`
--
ALTER TABLE `custom_hourly_rates`
  ADD CONSTRAINT `custom_hourly_rates_ibfk_1` FOREIGN KEY (`Instructor_ID`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `custom_hourly_rates_ibfk_2` FOREIGN KEY (`log_id`) REFERENCES `approved_billing_logs` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `instructor_payments`
--
ALTER TABLE `instructor_payments`
  ADD CONSTRAINT `fk_instructor_payments_employee` FOREIGN KEY (`instructor_id`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `instructor_rates`
--
ALTER TABLE `instructor_rates`
  ADD CONSTRAINT `instructor_rates_ibfk_1` FOREIGN KEY (`Instructor_ID`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainees`
--
ALTER TABLE `trainees`
  ADD CONSTRAINT `fk_source` FOREIGN KEY (`source`) REFERENCES `sources` (`name`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `waiting_billing_logs`
--
ALTER TABLE `waiting_billing_logs`
  ADD CONSTRAINT `waiting_billing_logs_ibfk_1` FOREIGN KEY (`Instructor_ID`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `waiting_logs`
--
ALTER TABLE `waiting_logs`
  ADD CONSTRAINT `fk_wait_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `employees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wait_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`national_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
