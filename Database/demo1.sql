-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 11:05 PM
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
-- Database: `demo1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`) VALUES
(1, 'admin@gmail.com', '$2y$10$bpwbuzqBrbgVr/Hw5u9AgOXe57zOw84UkpEXMKAEXAd8KwqNlVefC');

-- --------------------------------------------------------

--
-- Table structure for table `approved_vacating_notices`
--

CREATE TABLE `approved_vacating_notices` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `tenant_name` varchar(255) NOT NULL,
  `house_number` varchar(50) NOT NULL,
  `planned_exit_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approved_vacating_notices`
--

INSERT INTO `approved_vacating_notices` (`id`, `tenant_id`, `tenant_name`, `house_number`, `planned_exit_date`, `created_at`) VALUES
(1, 7, 'Denis Harold', 'A1', '2025-05-07', '2025-04-12 14:30:50');

-- --------------------------------------------------------

--
-- Table structure for table `assesment_report`
--

CREATE TABLE `assesment_report` (
  `id` int(11) NOT NULL,
  `property` varchar(255) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `security_deposit` decimal(10,2) NOT NULL,
  `damages` varchar(255) NOT NULL,
  `repair_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `remaining_deposit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Accepted','Rejected') DEFAULT 'Accepted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assesment_report`
--

INSERT INTO `assesment_report` (`id`, `property`, `tenant_id`, `unit`, `security_deposit`, `damages`, `repair_amount`, `payment_method`, `remaining_deposit`, `created_at`, `status`) VALUES
(6, 'Wamue Prestige', 7, 'A1', 20000.00, 'Floor Damage', 2000.00, 'Security Deposit', 18000.00, '2025-04-18 14:39:06', 'Accepted');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `property` varchar(255) NOT NULL,
  `wifi` decimal(10,2) NOT NULL,
  `water` decimal(10,2) NOT NULL,
  `electricity` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`id`, `property`, `wifi`, `water`, `electricity`) VALUES
(1, 'Wamue Prestige', 1500.00, 0.00, 500.00),
(2, 'Esther House', 1500.00, 0.00, 500.00),
(3, 'KVTC', 1500.00, 0.00, 500.00),
(4, 'St. Peter Cleaver', 1500.00, 0.00, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `caretaker`
--

CREATE TABLE `caretaker` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `property` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `caretaker`
--

INSERT INTO `caretaker` (`id`, `name`, `id_number`, `phone`, `email`, `password`, `property`, `created_at`) VALUES
(1, 'Collins Mutugi', '23456799', '742212333', 'mutugi@gmail.com', '$2y$10$fkA6t7dvvxn8Ha680NDLZe.dt9YdhFO/8kwFWJ6Zvqvf/EE14wqxW', 'Esther House', '2025-05-05 19:00:23'),
(2, 'Brian Odhiambo', '36840300', '740398389', 'briantyrel98@gmail.com', '$2y$10$4.BiCZb0NAnGfmf68RSdROuOqVpRQcmuRHDhvljrqJPn4yzeGkNpC', 'Wamue Prestige', '2025-05-05 19:01:27'),
(3, 'Vanessa Liz', '4532567', '722286165', 'vanessa@gmail.com', '$2y$10$L78FSYv6I1Z9gOFMd1HnFuYTI76cVkzxxDzZtQR0i85a0tf3SmD2e', 'KVTC', '2025-05-05 19:02:30'),
(4, 'Lisa Mwangi', '3698776', '704804751', 'mwangi@gmail.com', '$2y$10$CM/Xx8WlJy8SzIYmJl7.fOJwVNXVVXo3K2UXYbT7lbCRW6wY9asXW', 'St. Peter Cleaver', '2025-05-05 19:03:19');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `maintenance_id` int(11) NOT NULL,
  `property` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `status` enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `tenant_name` varchar(255) NOT NULL,
  `property` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `rent_amount` decimal(10,2) NOT NULL,
  `wifi_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Paid','Unpaid') DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `admin_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:23:51'),
(2, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:24:04'),
(3, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:24:06'),
(4, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:24:07'),
(5, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:24:08'),
(6, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:24:09'),
(7, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:24:10'),
(8, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:24:30'),
(9, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:25:10'),
(10, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:25:11'),
(11, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:25:12'),
(12, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:25:32'),
(13, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:25:33'),
(14, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:25:33'),
(15, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:26:02'),
(16, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:27:40'),
(17, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:27:47'),
(18, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:28:17'),
(19, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:29:48'),
(20, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:29:58'),
(21, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:30:19'),
(22, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:30:24'),
(23, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:30:26'),
(24, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:30:29'),
(25, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:30:31'),
(26, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:30:52'),
(27, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:31:30'),
(28, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:31:31'),
(29, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:31:33'),
(30, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:32:05'),
(31, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:32:54'),
(32, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:33:12'),
(33, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:34:33'),
(34, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:34:34'),
(35, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:34:51'),
(36, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:34:54'),
(37, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:35:02'),
(38, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:35:03'),
(39, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:39:52'),
(40, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:39:53'),
(41, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:39:53'),
(42, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:42:50'),
(43, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:43:25'),
(44, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:43:27'),
(45, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:44:19'),
(46, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:22'),
(47, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:35'),
(48, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:37'),
(49, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:39'),
(50, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:47'),
(51, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:47'),
(52, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:51'),
(53, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:54'),
(54, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:56'),
(55, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:57'),
(56, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:56:58'),
(57, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:00'),
(58, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:00'),
(59, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:01'),
(60, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:02'),
(61, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:03'),
(62, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:04'),
(63, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:06'),
(64, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:07'),
(65, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:07'),
(66, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:08'),
(67, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:09'),
(68, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:57:15'),
(69, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:09'),
(70, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:13'),
(71, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:13'),
(72, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:15'),
(73, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:16'),
(74, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:18'),
(75, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:19'),
(76, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:21'),
(77, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:21'),
(78, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:22'),
(79, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:23'),
(80, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:24'),
(81, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:26'),
(82, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:29'),
(83, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:31'),
(84, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:32'),
(85, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:33'),
(86, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:34'),
(87, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:35'),
(88, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:36'),
(89, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:37'),
(90, 1, 'Login', 'Admin logged in successfully.', '2025-04-19 17:58:38'),
(91, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 18:54:26'),
(92, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:03:26'),
(93, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:03:26'),
(94, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:03:29'),
(95, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:03:29'),
(96, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:04:45'),
(97, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:04:45'),
(98, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:05:08'),
(99, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:05:10'),
(100, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:05:13'),
(101, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:06:06'),
(102, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:06:06'),
(103, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:22:59'),
(104, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:22:59'),
(105, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:23:03'),
(106, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:23:03'),
(107, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:25:45'),
(108, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:25:45'),
(109, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:25:46'),
(110, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:25:46'),
(111, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:28:06'),
(112, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 19:28:06'),
(113, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:06:01'),
(114, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:06:01'),
(115, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:11:57'),
(116, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:11:59'),
(117, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:12:05'),
(118, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:12:06'),
(119, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:12:08'),
(120, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:12:10'),
(121, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:39:16'),
(122, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:39:16'),
(123, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:39:20'),
(124, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:39:20'),
(125, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:40:15'),
(126, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:40:15'),
(127, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:40:16'),
(128, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:40:16'),
(129, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:42:38'),
(130, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:42:39'),
(131, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:42:41'),
(132, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:43:08'),
(133, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:43:20'),
(134, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:43:21'),
(135, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:48:15'),
(136, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:48:15'),
(137, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:48:28'),
(138, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:48:31'),
(139, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:48:34'),
(140, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:48:37'),
(141, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:48:40'),
(142, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:49:39'),
(143, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:49:44'),
(144, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:49:49'),
(145, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:54:33'),
(146, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:54:33'),
(147, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:54:37'),
(148, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:54:39'),
(149, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:08'),
(150, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:09'),
(151, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:12'),
(152, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:14'),
(153, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:20'),
(154, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:22'),
(155, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:25'),
(156, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:30'),
(157, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:33'),
(158, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:36'),
(159, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:46'),
(160, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:52'),
(161, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 20:59:55'),
(162, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:01'),
(163, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:03'),
(164, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:10'),
(165, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:13'),
(166, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:16'),
(167, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:18'),
(168, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:21'),
(169, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:25'),
(170, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:28'),
(171, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:31'),
(172, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:34'),
(173, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:36'),
(174, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:38'),
(175, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:42'),
(176, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:45'),
(177, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:48'),
(178, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:49'),
(179, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:54'),
(180, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:00:56'),
(181, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:04'),
(182, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:07'),
(183, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:10'),
(184, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:13'),
(185, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:32'),
(186, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:34'),
(187, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:36'),
(188, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:38'),
(189, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:40'),
(190, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:42'),
(191, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:01:44'),
(192, 1, 'Login', 'Admin logged in successfully.', '2025-05-05 21:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `shortsummary` varchar(255) NOT NULL,
  `property` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `category` enum('open','ongoing','closed') NOT NULL,
  `expense` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`id`, `shortsummary`, `property`, `unit`, `category`, `expense`, `date`, `payment_method`) VALUES
(5, 'Painting', 'Wamue Prestige', 'A1', 'closed', 1500.00, '2025-04-09', 'Mpesa'),
(6, 'Painting', 'Wamue Prestige', 'A1', 'ongoing', 0.00, '2025-04-15', 'Bank'),
(7, 'Painting', 'Esther House', '1W', 'open', 0.00, '2025-04-15', 'Cash'),
(8, 'Plumbing', 'Wamue Prestige', 'A1', 'closed', 3000.00, '2025-04-17', 'Mpesa'),
(9, 'Woodwork', 'Wamue Prestige', 'A1', 'open', 0.00, '2025-04-18', 'Cash');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `timestamp`, `is_read`) VALUES
(2, 7, 1, 'Hello can my scheduled maintenance commence please?', '2025-04-08 14:10:35', 1),
(3, 1, 7, 'It will be looked upon', '2025-04-08 14:15:51', 0),
(4, 1, 7, 'Who\'s Mark?', '2025-04-08 14:25:23', 0),
(5, 7, 1, 'Mark is my friend', '2025-04-08 14:25:37', 1),
(6, 7, 1, 'Mark is my friend', '2025-04-09 10:17:49', 1),
(7, 1, 7, 'Mark is my friend', '2025-04-09 10:18:25', 0),
(8, 7, 1, 'Who\'s Mark?', '2025-04-12 15:19:38', 1),
(9, 7, 1, 'yoooooo', '2025-04-12 15:27:48', 1),
(10, 7, 1, 'obiezeeeee', '2025-04-12 15:30:03', 1),
(11, 1, 7, 'yooooo', '2025-04-12 15:36:30', 0),
(12, 1, 7, 'Your request to vacate has been rejected as per request.', '2025-04-12 17:14:41', 0),
(13, 1, 7, 'Your request to vacate the unit has been accepted for the date 2025-05-02, an administrator will reach out to you with comprehensive information on the next steps. Thank you for staying with us!', '2025-04-12 17:15:38', 0),
(14, 1, 7, 'Your request to vacate the unit has been accepted for the date 2025-05-07, an administrator will reach out to you with comprehensive information on the next steps. Thank you for staying with us!', '2025-04-12 17:30:50', 0),
(18, 1, 7, 'This is an official message to all users', '2025-04-13 15:40:19', 0),
(19, 1, 8, 'This is an official message to all users', '2025-04-13 15:40:19', 0),
(20, 7, 1, 'Alright', '2025-04-17 01:18:31', 1);

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `property` varchar(255) NOT NULL,
  `numberofunits` int(11) NOT NULL,
  `city` varchar(255) NOT NULL,
  `water` decimal(10,2) NOT NULL,
  `electricity` decimal(10,2) NOT NULL,
  `mpesa` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `property`, `numberofunits`, `city`, `water`, `electricity`, `mpesa`) VALUES
(4, 'Wamue Prestige', 29, 'Kitui- Kwa Vonza', 120.00, 1500.00, 389898),
(5, 'Esther House', 20, 'Kitui- Kwa Vonza', 120.00, 1500.00, 389989),
(7, 'KVTC', 20, 'Kitui- Kwa Vonza', 120.00, 1500.00, 378377),
(8, 'St. Peter Cleaver', 20, 'Kitui- Kwa Vonza', 120.00, 1500.00, 389787);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_code` varchar(255) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `property` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_code`, `payment_method`, `phone_number`, `name`, `tenant_id`, `property`, `unit`, `amount`, `date`) VALUES
(1, 'QRUI80171', 'Mpesa', '0769345790', 'Denis Harold', 7, 'Wamue Prestige', 'A1', 20000.00, '2025-04-10'),
(2, 'QRUI80169', 'Mpesa', '0711909831', 'Brian Obado', 8, 'Esther House', '1W', 20000.00, '2025-04-10'),
(3, 'QRUI80188', 'Mpesa', '0769345790', 'Denis Harold', 7, 'Wamue Prestige', 'A1', 27000.00, '2025-04-10'),
(4, 'QRUI80176', 'Mpesa', '0769345790', 'Denis Harold', 7, 'Wamue Prestige', 'A1', 12000.00, '2025-04-09'),
(5, 'ZRUI80188', 'Mpesa', '0711909765', 'Dennis Mulwa Ngunu', 12, 'KVTC', 'UG2', 11500.00, '2025-04-19');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `property` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `status` enum('Vacant','Occupied') NOT NULL,
  `rent` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `floor` varchar(50) NOT NULL DEFAULT 'Ground Floor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `property`, `unit`, `status`, `rent`, `created_at`, `floor`) VALUES
(10, 'Wamue Prestige', 'A1', 'Occupied', 20000.00, '2025-04-08 02:26:09', '1st Floor'),
(11, 'Esther House', '1W', 'Occupied', 20000.00, '2025-04-08 11:45:56', 'Ground Floor'),
(12, 'KVTC', 'G2', 'Occupied', 20000.00, '2025-04-08 11:46:15', '2nd Floor'),
(13, 'St. Peter Cleaver', 'D2D', 'Occupied', 30000.00, '2025-04-14 15:06:23', '3rd Floor'),
(14, 'Esther House', 'S2', 'Occupied', 20000.00, '2025-04-15 21:33:37', 'Ground Floor'),
(15, 'KVTC', 'UG1', 'Vacant', 10000.00, '2025-04-17 20:39:09', '1st Floor'),
(16, 'KVTC', 'UG2', 'Occupied', 10000.00, '2025-04-17 20:39:30', '1st Floor'),
(17, 'KVTC', 'UG3', 'Vacant', 10000.00, '2025-04-17 20:39:51', '1st Floor'),
(18, 'KVTC', 'UG4', 'Vacant', 10000.00, '2025-04-17 20:40:29', '1st Floor'),
(19, 'KVTC', 'UG5', 'Vacant', 10000.00, '2025-04-17 20:40:45', '1st Floor'),
(20, 'KVTC', 'UG6', 'Vacant', 10000.00, '2025-04-17 20:41:06', '1st Floor'),
(21, 'KVTC', 'UG7', 'Vacant', 10000.00, '2025-04-17 20:41:21', '1st Floor'),
(22, 'KVTC', 'UG8', 'Vacant', 10000.00, '2025-04-17 20:41:37', '1st Floor'),
(23, 'KVTC', 'UG9', 'Vacant', 10000.00, '2025-04-17 20:41:53', '1st Floor'),
(24, 'KVTC', 'UG10', 'Vacant', 10000.00, '2025-04-17 20:42:27', '1st Floor'),
(25, 'KVTC', 'UG11', 'Vacant', 10000.00, '2025-04-17 20:42:44', '1st Floor'),
(26, 'KVTC', 'UGM1', 'Vacant', 10000.00, '2025-04-17 20:43:40', '1st Floor'),
(27, 'KVTC', 'UGM2', 'Vacant', 10000.00, '2025-04-17 20:43:57', '1st Floor'),
(28, 'KVTC', 'St. Charles Lwanga', 'Vacant', 15000.00, '2025-04-17 20:49:14', 'Ground Floor'),
(29, 'KVTC', 'St. Joseph Mukasa', 'Vacant', 15000.00, '2025-04-17 20:49:43', 'Ground Floor'),
(30, 'KVTC', 'St. Andrew Kaggwa', 'Vacant', 15000.00, '2025-04-17 20:50:13', 'Ground Floor'),
(31, 'KVTC', 'St. Noa Mawaggali', 'Vacant', 15000.00, '2025-04-17 20:51:00', 'Ground Floor'),
(32, 'KVTC', 'St. Achilles Kiwanuka', 'Vacant', 15000.00, '2025-04-17 20:52:06', 'Ground Floor'),
(33, 'KVTC', 'St. Jean Marie Muzeeyi', 'Vacant', 15000.00, '2025-04-17 20:52:58', 'Ground Floor'),
(34, 'KVTC', 'ST. GYAVIRA MAYANJA', 'Vacant', 15000.00, '2025-04-17 20:54:17', '3rd Floor'),
(35, 'KVTC', 'ST. ATHANASIUS BAZZE KUKELA', 'Vacant', 15000.00, '2025-04-17 20:54:42', '3rd Floor'),
(36, 'KVTC', 'ST. GONZAGA GONZA', 'Vacant', 15000.00, '2025-04-17 20:55:18', '3rd Floor'),
(37, 'KVTC', 'ST. ADOLPHUS MUKASA', 'Vacant', 15000.00, '2025-04-17 20:55:58', '3rd Floor'),
(38, 'KVTC', 'ST. KIZITO JEAN BAPTISTE', 'Vacant', 15000.00, '2025-04-17 20:57:02', '3rd Floor'),
(39, 'KVTC', 'ST. PONTIANO NGONDWE', 'Vacant', 15000.00, '2025-04-17 20:57:27', '3rd Floor'),
(40, 'KVTC', 'ST. DENIS SSEBUGWAWO', 'Vacant', 15000.00, '2025-04-17 20:57:54', '3rd Floor'),
(41, 'KVTC', 'ST. AMBROSE KIBUUKA', 'Vacant', 15000.00, '2025-04-17 20:58:18', '3rd Floor'),
(42, 'KVTC', 'ST. MUGAGGA LUBOWA', 'Vacant', 15000.00, '2025-04-17 20:58:47', '3rd Floor'),
(43, 'KVTC', 'ST. BRUNO', 'Vacant', 15000.00, '2025-04-17 20:59:14', '3rd Floor'),
(44, 'Wamue Prestige', 'St Peter(A1)', 'Vacant', 15000.00, '2025-04-17 21:03:25', 'Ground Floor'),
(45, 'Wamue Prestige', 'St John (A2)', 'Vacant', 15000.00, '2025-04-17 21:03:54', 'Ground Floor'),
(46, 'Wamue Prestige', 'St Andrew(B1)', 'Vacant', 15000.00, '2025-04-17 21:04:25', '1st Floor'),
(47, 'Wamue Prestige', 'St James(B2)', 'Vacant', 15000.00, '2025-04-17 21:05:06', '1st Floor'),
(48, 'Wamue Prestige', 'St Jude(B3)', 'Vacant', 15000.00, '2025-04-17 21:05:37', '1st Floor'),
(49, 'Wamue Prestige', 'St Nathaniel(C1)', 'Vacant', 15000.00, '2025-04-17 21:06:18', '2nd Floor'),
(50, 'Wamue Prestige', 'St Thomas(C2)', 'Vacant', 15000.00, '2025-04-17 21:06:43', '2nd Floor'),
(51, 'Wamue Prestige', 'St Mathias(C3)', 'Vacant', 15000.00, '2025-04-17 21:07:41', '2nd Floor'),
(52, 'Wamue Prestige', 'St Philiph(D1)', 'Vacant', 15000.00, '2025-04-17 21:08:16', '3rd Floor'),
(53, 'Esther House', 'St Cecilia', 'Vacant', 10000.00, '2025-04-17 21:09:04', 'Ground Floor'),
(54, 'Esther House', 'St Christina', 'Vacant', 10000.00, '2025-04-17 21:09:34', 'Ground Floor'),
(55, 'Esther House', 'St Isabella', 'Vacant', 10000.00, '2025-04-17 21:10:01', 'Ground Floor'),
(56, 'Esther House', 'St Malia Theresa', 'Vacant', 10000.00, '2025-04-17 21:10:55', 'Ground Floor'),
(57, 'Esther House', 'St John Marie Vianey', 'Vacant', 10000.00, '2025-04-17 21:11:45', 'Ground Floor'),
(58, 'Esther House', 'St Paul', 'Vacant', 10000.00, '2025-04-17 21:13:30', 'Ground Floor'),
(59, 'Esther House', 'St Maximillan Kolbe', 'Vacant', 10000.00, '2025-04-17 21:13:48', 'Ground Floor'),
(60, 'Esther House', 'St Juan Diego', 'Vacant', 10000.00, '2025-04-17 21:14:12', 'Ground Floor'),
(61, 'Esther House', 'St Padre Pio', 'Vacant', 10000.00, '2025-04-17 21:14:39', 'Ground Floor'),
(62, 'Esther House', 'St Josephine Bakhita', 'Vacant', 10000.00, '2025-04-17 21:15:00', 'Ground Floor'),
(63, 'Esther House', 'St Augustine', 'Vacant', 10000.00, '2025-04-17 21:15:25', 'Ground Floor'),
(64, 'Esther House', 'St Mathias Mulumba', 'Vacant', 10000.00, '2025-04-17 21:15:46', 'Ground Floor'),
(65, 'Esther House', 'St Mbagga Tuzide', 'Vacant', 10000.00, '2025-04-17 21:16:17', 'Ground Floor');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `property` varchar(100) DEFAULT NULL,
  `move_in_date` date DEFAULT NULL,
  `rent_amount` decimal(10,2) DEFAULT NULL,
  `security_deposit` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `id_number`, `phone`, `email`, `password`, `unit`, `property`, `move_in_date`, `rent_amount`, `security_deposit`) VALUES
(1, 'Admin', NULL, '0000000000', 'admin@example.com', 'adminpassword', NULL, NULL, NULL, NULL, NULL),
(7, 'Denis Harold', '38227207', '769345790', 'denisobadoharold00@gmail.com', '$2y$10$vMeTuLxRnp2cH7/OhJJA/uifz5wKASHirn2xL0q9Ow3J/vG3ORsdy', 'A1', 'Wamue Prestige', '2025-04-08', 20000.00, 18000.00),
(8, 'Brian Obado', '36840300', '711909831', 'Briantyrel98@gmail.com', '$2y$10$Rfown/zCgMvttBxiN/Pl3eZLQ/rJkiIhzmrSJDks5yE3.qimYOn1e', '1W', 'Esther House', '2025-04-08', 20000.00, 20000.00),
(9, 'Mark Jasson', '3683022', '716366761', 'Markjasson@gmail.com', '$2y$10$.lso1MVE3t3nGGrDZp.SfuvjyBHsTvEimn5jVDjfrH0/P46TaCm56', 'S2', 'Esther House', '2025-04-15', 20000.00, 20000.00),
(10, 'Jerry Neal', '36830234', '712505778', 'Jneal@gmail.com', '$2y$10$1OsJHdxme3PwxA5sDMGNb.SMarphciiojQChuhKdvdEKIYC/8SQZS', 'D2D', 'St. Peter Cleaver', '2025-04-15', 30000.00, 30000.00),
(11, 'Francis Kalelu Mboya', '3403200', '769345626', 'Kalelumboya98@gmail.com', '$2y$10$4h9RQkgVkIv0SPyZaCNTtuxfIuP8P7imV.QfEcJym4qkQSOalRrt6', 'G2', 'KVTC', '2025-04-17', 20000.00, 20000.00),
(12, 'Dennis Mulwa Ngunu', '34567892', '712345443', 'Dennismulwa@gmail.com', '$2y$10$PxXza/mWg5pqDCd.kDVRje0Y7iSr3.bpCeuNRWlxK.hBpJItny1au', 'UG2', 'KVTC', '2025-04-19', 10000.00, 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `vacating_notices`
--

CREATE TABLE `vacating_notices` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `tenant_name` varchar(255) NOT NULL,
  `house_number` varchar(50) NOT NULL,
  `date_of_notice` date NOT NULL,
  `planned_exit_date` date NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `approved_vacating_notices`
--
ALTER TABLE `approved_vacating_notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assesment_report`
--
ALTER TABLE `assesment_report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `caretaker`
--
ALTER TABLE `caretaker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maintenance_id` (`maintenance_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vacating_notices`
--
ALTER TABLE `vacating_notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `approved_vacating_notices`
--
ALTER TABLE `approved_vacating_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assesment_report`
--
ALTER TABLE `assesment_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `caretaker`
--
ALTER TABLE `caretaker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vacating_notices`
--
ALTER TABLE `vacating_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assesment_report`
--
ALTER TABLE `assesment_report`
  ADD CONSTRAINT `assesment_report_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`maintenance_id`) REFERENCES `maintenance` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `vacating_notices`
--
ALTER TABLE `vacating_notices`
  ADD CONSTRAINT `vacating_notices_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
