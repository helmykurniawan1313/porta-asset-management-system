-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2026 at 10:39 AM
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
-- Database: `it_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `email`, `purpose`, `created_at`, `updated_at`) VALUES
(1, 'portadesigner01@gmail.com', NULL, '2026-03-05 01:38:20', '2026-03-05 01:38:20'),
(2, 'portadesigner07@gmail.com', NULL, '2026-03-11 19:43:40', '2026-03-11 19:43:40'),
(3, 'asset@portabranding.com', NULL, '2026-03-11 20:44:06', '2026-05-11 02:08:24');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `device_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `device_id`, `employee_id`, `action`, `description`, `created_at`, `updated_at`) VALUES
(16, 4, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:09'),
(17, 4, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:09', '2026-03-06 00:42:09'),
(18, 3, NULL, 'Component Attached', 'Installed Motherboard: MOBO-01.', '2025-11-16 17:00:00', '2026-03-06 00:42:09'),
(19, 5, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:22'),
(20, 5, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:22', '2026-03-06 00:42:22'),
(21, 3, NULL, 'Component Attached', 'Installed Processor: PROC-01.', '2025-11-16 17:00:00', '2026-03-06 00:42:22'),
(22, 6, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:36'),
(23, 6, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:36', '2026-03-06 00:42:36'),
(24, 3, NULL, 'Component Attached', 'Installed RAM: RAM-01.', '2025-11-16 17:00:00', '2026-03-06 00:42:36'),
(25, 7, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:49'),
(26, 7, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:49', '2026-03-06 00:42:49'),
(27, 3, NULL, 'Component Attached', 'Installed RAM: RAM-02.', '2025-11-16 17:00:00', '2026-03-06 00:42:49'),
(28, 8, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:47:44'),
(29, 8, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:47:44', '2026-03-06 00:47:44'),
(30, 3, NULL, 'Component Attached', 'Installed SSD: SSD-01.', '2025-11-16 17:00:00', '2026-03-06 00:47:44'),
(31, 9, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:48:00'),
(32, 9, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:48:00', '2026-03-06 00:48:00'),
(33, 3, NULL, 'Component Attached', 'Installed HDD: HDD-01.', '2025-11-16 17:00:00', '2026-03-06 00:48:00'),
(34, 10, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:48:10'),
(35, 10, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:48:10', '2026-03-06 00:48:10'),
(36, 3, NULL, 'Component Attached', 'Installed GPU: GPU-01.', '2025-11-16 17:00:00', '2026-03-06 00:48:10'),
(37, 11, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-03-10 17:00:00', '2026-03-10 23:55:35'),
(38, 11, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-10 23:55:35', '2026-03-10 23:55:35'),
(39, 3, NULL, 'Component Attached', 'Installed Monitor: MON-14.', '2026-03-10 17:00:00', '2026-03-10 23:55:35'),
(40, 11, NULL, 'Transfer', 'Asset assigned from Sonya to STOCK.', '2026-03-10 23:56:08', '2026-03-10 23:56:08'),
(41, 11, NULL, 'Detach', 'Removed from Porta-PC-01 and returned to STOCK.', '2025-11-16 17:00:00', '2026-03-10 23:56:09'),
(42, 3, NULL, 'Component Detached', 'Removed Monitor: MON-14.', '2025-11-16 17:00:00', '2026-03-10 23:56:09'),
(43, 11, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-10 23:56:17'),
(44, 11, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-10 23:56:17', '2026-03-10 23:56:17'),
(45, 3, NULL, 'Component Attached', 'Installed Monitor: MON-14.', '2025-11-16 17:00:00', '2026-03-10 23:56:17'),
(46, 14, NULL, 'Attach', 'Installed into main device: Porta-LP-31.', '2026-03-11 17:00:00', '2026-03-11 20:41:15'),
(47, 16, NULL, 'Component Attached', 'Installed Keyboard: Keyboard-01.', '2026-03-11 17:00:00', '2026-03-11 20:41:15'),
(48, 12, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-02-19 17:00:00', '2026-03-26 20:35:20'),
(49, 12, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-26 20:35:20', '2026-03-26 20:35:20'),
(50, 3, NULL, 'Component Attached', 'Installed UPS: UPS-01.', '2026-02-19 17:00:00', '2026-03-26 20:35:20'),
(51, 8, NULL, 'Health Log: 83%', 'Diagnostic recorded.', '2026-03-26 17:00:00', '2026-03-26 20:37:06'),
(52, 13, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-04-09 19:04:33'),
(53, 13, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-04-09 19:04:33', '2026-04-09 19:04:33'),
(54, 3, NULL, 'Component Attached', 'Installed Mouse: Mouse-01.', '2025-11-16 17:00:00', '2026-04-09 19:04:33'),
(55, 15, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-04-09 19:05:40'),
(56, 15, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-04-09 19:05:40', '2026-04-09 19:05:40'),
(57, 3, NULL, 'Component Attached', 'Installed OS: OS-01.', '2025-11-16 17:00:00', '2026-04-09 19:05:40'),
(58, 14, NULL, 'Detach', 'Removed from Porta-LP-31 and returned to STOCK.', '2026-04-09 17:00:00', '2026-04-09 19:06:01'),
(59, 16, NULL, 'Component Detached', 'Removed Keyboard: Keyboard-01.', '2026-04-09 17:00:00', '2026-04-09 19:06:01'),
(60, 14, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-04-09 19:06:15'),
(61, 14, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-04-09 19:06:15', '2026-04-09 19:06:15'),
(62, 3, NULL, 'Component Attached', 'Installed Keyboard: Keyboard-01.', '2025-11-16 17:00:00', '2026-04-09 19:06:15'),
(63, 23, NULL, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:17'),
(64, 22, NULL, 'Component Attached', 'Installed Motherboard: LP-MOBO-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:17'),
(65, 24, NULL, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:23'),
(66, 22, NULL, 'Component Attached', 'Installed Processor: LP-PROC-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:23'),
(67, 25, NULL, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:27'),
(68, 22, NULL, 'Component Attached', 'Installed RAM: LP-RAM-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:27'),
(69, 26, NULL, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:32'),
(70, 22, NULL, 'Component Attached', 'Installed SSD: SSD-17.', '2026-05-10 17:00:00', '2026-05-11 00:05:32'),
(71, 27, NULL, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:26'),
(72, 22, NULL, 'Component Attached', 'Installed GPU: LP-GPU-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:26'),
(73, 28, NULL, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:29'),
(74, 22, NULL, 'Component Attached', 'Installed Battery: LP-Batt-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:29'),
(75, 29, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-05-10 17:00:00', '2026-05-11 00:44:18'),
(76, 29, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-05-11 00:44:18', '2026-05-11 00:44:18'),
(77, 3, NULL, 'Component Attached', 'Installed Power Supply: PSU-01.', '2026-05-10 17:00:00', '2026-05-11 00:44:18'),
(78, 30, NULL, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-05-10 17:00:00', '2026-05-11 00:44:24'),
(79, 30, NULL, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-05-11 00:44:24', '2026-05-11 00:44:24'),
(80, 3, NULL, 'Component Attached', 'Installed UPS: UPS-01.', '2026-05-10 17:00:00', '2026-05-11 00:44:24'),
(81, 31, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:39:09'),
(82, 31, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:39:09', '2026-05-11 02:39:09'),
(83, 17, NULL, 'Component Attached', 'Installed GPU: PC-GPU-02.', '2025-11-16 17:00:00', '2026-05-11 02:39:09'),
(84, 32, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-12-10 17:00:00', '2026-05-11 02:39:24'),
(85, 32, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:39:24', '2026-05-11 02:39:24'),
(86, 17, NULL, 'Component Attached', 'Installed HDD: HDD-02.', '2025-12-10 17:00:00', '2026-05-11 02:39:24'),
(87, 33, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-12-10 17:00:00', '2026-05-11 02:39:35'),
(88, 33, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:39:35', '2026-05-11 02:39:35'),
(89, 17, NULL, 'Component Attached', 'Installed HDD: HDD-03.', '2025-12-10 17:00:00', '2026-05-11 02:39:35'),
(90, 34, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-12-16 17:00:00', '2026-05-11 02:40:08'),
(91, 34, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:08', '2026-05-11 02:40:08'),
(92, 17, NULL, 'Component Attached', 'Installed Keyboard: Keyboard-02.', '2025-12-16 17:00:00', '2026-05-11 02:40:08'),
(93, 35, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:19'),
(94, 35, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:19', '2026-05-11 02:40:19'),
(95, 17, NULL, 'Component Attached', 'Installed Monitor: MON-13.', '2025-11-16 17:00:00', '2026-05-11 02:40:19'),
(96, 36, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:29'),
(97, 36, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:29', '2026-05-11 02:40:29'),
(98, 17, NULL, 'Component Attached', 'Installed Motherboard: PC-MOBO-02.', '2025-11-16 17:00:00', '2026-05-11 02:40:29'),
(99, 37, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:42'),
(100, 37, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:42', '2026-05-11 02:40:42'),
(101, 17, NULL, 'Component Attached', 'Installed Mouse: Mouse-02.', '2025-11-16 17:00:00', '2026-05-11 02:40:42'),
(102, 38, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:50'),
(103, 38, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:50', '2026-05-11 02:40:50'),
(104, 17, NULL, 'Component Attached', 'Installed Processor: PC-PROC-02.', '2025-11-16 17:00:00', '2026-05-11 02:40:50'),
(105, 39, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:41:01'),
(106, 39, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:41:01', '2026-05-11 02:41:01'),
(107, 17, NULL, 'Component Attached', 'Installed RAM: PC-RAM-03.', '2025-11-16 17:00:00', '2026-05-11 02:41:01'),
(108, 40, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:43:07'),
(109, 40, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:43:07', '2026-05-11 02:43:07'),
(110, 17, NULL, 'Component Attached', 'Installed RAM: PC-RAM-04.', '2025-11-16 17:00:00', '2026-05-11 02:43:07'),
(111, 41, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:43:15'),
(112, 41, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:43:15', '2026-05-11 02:43:15'),
(113, 17, NULL, 'Component Attached', 'Installed SSD: SSD-02.', '2025-11-16 17:00:00', '2026-05-11 02:43:15'),
(114, 42, NULL, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:43:25'),
(115, 42, NULL, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:43:25', '2026-05-11 02:43:25'),
(116, 17, NULL, 'Component Attached', 'Installed UPS: UPS-02.', '2025-11-16 17:00:00', '2026-05-11 02:43:25'),
(117, 49, NULL, 'Health Log: 80%', 'Diagnostic recorded.', '2025-12-29 17:00:00', '2026-05-11 02:56:05'),
(118, 50, NULL, 'Transfer', 'Asset assigned from STOCK to Zaldy.', '2026-05-12 01:51:01', '2026-05-12 01:51:01'),
(120, 52, NULL, 'Asset Registered', 'Asset was added to the inventory.', '2026-05-12 19:12:01', '2026-05-12 19:12:01');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-helmykurniawan1313@gmail.com|127.0.0.1', 'i:1;', 1775792721),
('laravel-cache-helmykurniawan1313@gmail.com|127.0.0.1:timer', 'i:1775792721;', 1775792721);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Designer', '2026-03-05 01:38:45', '2026-03-05 01:38:45'),
(2, 'IT', '2026-05-12 00:19:31', '2026-05-12 00:19:31');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `device_group` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `device_password` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `component_type` varchar(255) DEFAULT NULL,
  `size` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `rom` varchar(255) DEFAULT NULL,
  `current_health` int(11) DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `image1` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `device_group`, `category`, `name`, `device_password`, `manufacturer`, `model`, `purchase_date`, `price`, `component_type`, `size`, `ram`, `rom`, `current_health`, `employee_id`, `parent_id`, `created_at`, `updated_at`, `image1`, `image2`, `image3`) VALUES
(3, 'Device', 'PC', 'Porta-PC-01', NULL, '-', 'Generic PC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-03-06 00:38:52', '2026-03-06 00:38:52', NULL, NULL, NULL),
(4, 'Peripheral', 'Motherboard', 'PC-MOBO-01', NULL, 'ASRock', 'A320M-HDV R4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 3, '2026-03-06 00:39:55', '2026-05-10 23:26:11', NULL, NULL, NULL),
(5, 'Peripheral', 'Processor', 'PC-PROC-01', NULL, 'AMD', 'Ryzen 5 2600', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 3, '2026-03-06 00:40:24', '2026-05-10 23:25:11', NULL, NULL, NULL),
(6, 'Peripheral', 'RAM', 'PC-RAM-01', NULL, 'KLEVV', NULL, NULL, NULL, 'DDR4', '8 GB', NULL, NULL, NULL, 2, 3, '2026-03-06 00:41:14', '2026-05-10 23:52:13', NULL, NULL, NULL),
(7, 'Peripheral', 'RAM', 'PC-RAM-02', NULL, 'KLEVV', NULL, NULL, NULL, 'DDR 4', '8 GB', NULL, NULL, NULL, 2, 3, '2026-03-06 00:41:47', '2026-05-10 23:52:20', NULL, NULL, NULL),
(8, 'Peripheral', 'SSD', 'SSD-01', NULL, 'SK HYNIX', 'BC501 HFM256GDJT', NULL, NULL, 'M.2 NVMe', '256 GB', NULL, NULL, 83, 2, 3, '2026-03-06 00:44:59', '2026-03-26 20:37:06', NULL, NULL, NULL),
(9, 'Peripheral', 'HDD', 'HDD-01', NULL, 'Toshiba', 'HDWD220', NULL, NULL, 'Sata 3.5', '2 TB', NULL, NULL, 100, 2, 3, '2026-03-06 00:46:20', '2026-03-06 00:48:00', NULL, NULL, NULL),
(10, 'Peripheral', 'GPU', 'PC-GPU-01', NULL, 'Zotac', 'Nvidia GTX 1050 Ti', NULL, NULL, NULL, '4 GB', NULL, NULL, NULL, 2, 3, '2026-03-06 00:47:13', '2026-05-11 00:19:14', NULL, NULL, NULL),
(11, 'Peripheral', 'Monitor', 'MON-4', NULL, 'Acer', 'EK240Y', NULL, NULL, 'LCD', '24 Inch', NULL, NULL, NULL, 2, 3, '2026-03-06 00:49:31', '2026-03-26 20:33:06', NULL, NULL, NULL),
(12, 'Peripheral', 'UPS', 'UPS-06', NULL, 'Acer', 'Altos City 120', '2026-02-20', NULL, NULL, '1200 VA', NULL, NULL, NULL, 2, 3, '2026-03-11 19:06:00', '2026-03-26 20:35:55', NULL, NULL, NULL),
(13, 'Peripheral', 'Mouse', 'Mouse-01', NULL, 'Fantech', 'Crypto II', NULL, NULL, 'Wired', NULL, NULL, NULL, NULL, 2, 3, '2026-03-11 19:06:52', '2026-05-11 00:42:37', NULL, NULL, NULL),
(14, 'Peripheral', 'Keyboard', 'Keyboard-01', NULL, 'Logitech', 'K120', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 3, '2026-03-11 19:07:22', '2026-04-09 19:06:15', NULL, NULL, NULL),
(15, 'Peripheral', 'OS', 'OS-01', NULL, 'Microsoft', 'Windows 10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 3, '2026-03-11 19:07:53', '2026-04-09 19:05:40', NULL, NULL, NULL),
(16, 'Device', 'Laptop', 'Porta-LP-31', NULL, 'Asus', 'Vivobook Go E1404FA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-11 20:37:27', '2026-03-11 20:37:27', NULL, NULL, NULL),
(17, 'Device', 'PC', 'Porta-PC-02', NULL, '-', 'Generic PC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, '2026-04-09 19:12:15', '2026-04-09 19:12:15', NULL, NULL, NULL),
(21, 'Device', 'Phone', 'Porta-HP-01', '0000', 'Apple', 'Iphone 7', NULL, NULL, NULL, NULL, '2 GB', '128 GB', 77, NULL, NULL, '2026-05-10 20:18:12', '2026-05-10 21:09:23', NULL, NULL, NULL),
(22, 'Device', 'Laptop', 'Porta-LP-01', 'portabranding01', 'Lenovo', 'ThinkPad X260', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-10 23:14:54', '2026-05-10 23:14:54', NULL, NULL, NULL),
(23, 'Peripheral', 'Motherboard', 'LP-MOBO-01', NULL, 'Lenovo', '20F5S2NG00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 22, '2026-05-10 23:26:52', '2026-05-11 00:05:17', NULL, NULL, NULL),
(24, 'Peripheral', 'Processor', 'LP-PROC-01', NULL, 'Intel', 'Core i5-6300U', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 22, '2026-05-10 23:51:44', '2026-05-11 00:05:23', NULL, NULL, NULL),
(25, 'Peripheral', 'RAM', 'LP-RAM-01', NULL, '-', '-', NULL, NULL, 'DDR 4', '8 GB', NULL, NULL, NULL, NULL, 22, '2026-05-10 23:53:38', '2026-05-11 00:05:27', NULL, NULL, NULL),
(26, 'Peripheral', 'SSD', 'SSD-17', NULL, 'V-Gen', NULL, NULL, NULL, 'Sata 2.5', '256 GB', NULL, NULL, 100, NULL, 22, '2026-05-10 23:56:14', '2026-05-11 00:05:32', NULL, NULL, NULL),
(27, 'Peripheral', 'GPU', 'LP-GPU-01', NULL, 'Intel', 'UHD 520', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 22, '2026-05-11 00:15:33', '2026-05-11 00:38:26', NULL, NULL, NULL),
(28, 'Peripheral', 'Battery', 'LP-Batt-01', NULL, 'Lenovo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, 22, '2026-05-11 00:37:47', '2026-05-11 02:51:31', NULL, NULL, NULL),
(29, 'Peripheral', 'Power Supply', 'PSU-01', NULL, NULL, NULL, NULL, NULL, NULL, '650 V', NULL, NULL, NULL, 2, 3, '2026-05-11 00:40:59', '2026-05-11 00:44:18', NULL, NULL, NULL),
(30, 'Peripheral', 'UPS', 'UPS-01', NULL, 'APC', 'Back Ups 650', NULL, NULL, NULL, '650 V', NULL, NULL, NULL, 2, 3, '2026-05-11 00:44:00', '2026-05-11 00:44:24', NULL, NULL, NULL),
(31, 'Peripheral', 'GPU', 'PC-GPU-02', NULL, 'Nvidia', 'GTX 1660', NULL, NULL, NULL, '6 GB', NULL, NULL, NULL, 3, 17, '2026-05-11 02:17:47', '2026-05-11 02:39:09', NULL, NULL, NULL),
(32, 'Peripheral', 'HDD', 'HDD-02', NULL, 'Toshiba', 'HDWD220', NULL, NULL, 'Sata 3.5', '2 TB', NULL, NULL, 100, 3, 17, '2026-05-11 02:21:37', '2026-05-11 02:39:24', NULL, NULL, NULL),
(33, 'Peripheral', 'HDD', 'HDD-03', NULL, 'Toshiba', 'DT01ACA100', NULL, NULL, 'Sata 3.5', '1 TB', NULL, NULL, 100, 3, 17, '2026-05-11 02:22:19', '2026-05-11 02:39:35', NULL, NULL, NULL),
(34, 'Peripheral', 'Keyboard', 'Keyboard-02', NULL, 'Logitech', 'K120', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 17, '2026-05-11 02:23:05', '2026-05-11 02:40:08', NULL, NULL, NULL),
(35, 'Peripheral', 'Monitor', 'MON-13', NULL, 'LG', '24MK430H', NULL, NULL, NULL, '24 Inch', NULL, NULL, NULL, 3, 17, '2026-05-11 02:25:05', '2026-05-11 02:40:19', NULL, NULL, NULL),
(36, 'Peripheral', 'Motherboard', 'PC-MOBO-02', NULL, 'Gigabyte', 'B550M Gaming', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 17, '2026-05-11 02:25:39', '2026-05-11 02:40:29', NULL, NULL, NULL),
(37, 'Peripheral', 'Mouse', 'Mouse-02', NULL, 'Fantech', 'Crypto VX7', NULL, NULL, 'Wired', NULL, NULL, NULL, NULL, 3, 17, '2026-05-11 02:26:30', '2026-05-11 02:40:42', NULL, NULL, NULL),
(38, 'Peripheral', 'Processor', 'PC-PROC-02', NULL, 'AMD', 'Ryzen 5 Pro 4650G', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 17, '2026-05-11 02:27:07', '2026-05-11 02:40:50', NULL, NULL, NULL),
(39, 'Peripheral', 'RAM', 'PC-RAM-03', NULL, 'Teamgroup', NULL, NULL, NULL, 'DDR4', '16 GB', NULL, NULL, NULL, 3, 17, '2026-05-11 02:28:15', '2026-05-11 02:41:01', NULL, NULL, NULL),
(40, 'Peripheral', 'RAM', 'PC-RAM-04', NULL, 'Teamgroup', NULL, NULL, NULL, 'DDR4', '16 GB', NULL, NULL, NULL, 3, 17, '2026-05-11 02:28:38', '2026-05-11 02:43:07', NULL, NULL, NULL),
(41, 'Peripheral', 'SSD', 'SSD-02', NULL, 'V-Gen', 'V-GEN 07SM19EG512GP3X4IT', NULL, NULL, 'M.2 NVMe 2280', '512 GB', NULL, NULL, 83, 3, 17, '2026-05-11 02:32:04', '2026-05-11 02:49:37', NULL, NULL, NULL),
(42, 'Peripheral', 'UPS', 'UPS-02', NULL, 'APC', 'Back Ups 650', NULL, NULL, NULL, '650 V', NULL, NULL, NULL, 3, 17, '2026-05-11 02:37:58', '2026-05-11 02:43:25', NULL, NULL, NULL),
(43, 'Device', 'Laptop', 'Porta-LP-02', NULL, 'Lenovo', 'Thinkpad X260', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 02:44:52', '2026-05-11 02:44:52', NULL, NULL, NULL),
(44, 'Peripheral', 'Motherboard', 'LP-MOBO-02', NULL, 'Lenovo', '20F5S77Y00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 02:45:27', '2026-05-11 02:45:27', NULL, NULL, NULL),
(45, 'Peripheral', 'Processor', 'LP-PROC-02', NULL, 'Intel', 'Core i5 6300U', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 02:46:11', '2026-05-11 02:46:11', NULL, NULL, NULL),
(46, 'Peripheral', 'RAM', 'LP-RAM-02', NULL, NULL, NULL, NULL, NULL, 'DDR 4', '8 GB', NULL, NULL, NULL, NULL, NULL, '2026-05-11 02:47:49', '2026-05-11 02:47:49', NULL, NULL, NULL),
(47, 'Peripheral', 'SSD', 'SSD-18', NULL, 'Adata', 'SU650', NULL, NULL, 'Sata 2.5', '256 GB', NULL, NULL, 80, NULL, NULL, '2026-05-11 02:49:14', '2026-05-11 02:49:14', NULL, NULL, NULL),
(48, 'Peripheral', 'Battery', 'LP-Batt-02', NULL, 'Lenovo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 42, NULL, NULL, '2026-05-11 02:51:22', '2026-05-11 02:51:39', NULL, NULL, NULL),
(49, 'Device', 'Phone', 'Porta-HP-07', NULL, 'Apple', 'Iphone X', NULL, NULL, NULL, NULL, '3 GB', '256 GB', 80, NULL, NULL, '2026-05-11 02:54:46', '2026-05-11 02:56:05', NULL, NULL, NULL),
(50, 'Device', 'PC', 'Porta-PC-03', 'porta', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, '2026-05-11 02:57:47', '2026-05-12 01:51:01', NULL, NULL, NULL),
(52, 'Peripheral', 'Processor', 'testt', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-12 19:12:01', '2026-05-12 19:21:05', 'assets/ddhtAjRszloAXcBlxazMjgvQP8L24zYDUpLOdpGB.webp', 'assets/vbgt1njwzZnaGoHuE8hbicOx4VHbmIG44zjJqggZ.webp', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `email`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'Helmy', NULL, 2, '2026-03-05 01:39:21', '2026-05-12 00:19:39'),
(2, 'Sonya', NULL, 1, '2026-03-06 00:38:42', '2026-03-06 00:38:42'),
(3, 'Putra', NULL, 1, '2026-04-09 19:12:02', '2026-04-09 19:12:02'),
(4, 'Zaldy', NULL, 1, '2026-05-12 00:27:32', '2026-05-12 00:27:32');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `device_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recorded_date` date DEFAULT NULL,
  `health_percentage` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_records`
--

INSERT INTO `health_records` (`id`, `device_id`, `recorded_date`, `health_percentage`, `notes`, `created_at`, `updated_at`) VALUES
(2, 8, '2026-03-27', 83, NULL, '2026-03-26 20:37:06', '2026-03-26 20:37:06'),
(3, 49, '2025-12-30', 80, NULL, '2026-05-11 02:56:05', '2026-05-11 02:56:05');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_02_24_023021_create_departments_table', 1),
(5, '2026_02_24_023022_create_employees_table', 1),
(6, '2026_02_24_023023_create_devices_table', 1),
(7, '2026_02_24_023024_create_activity_logs_table', 1),
(8, '2026_02_24_023024_create_health_records_table', 1),
(9, '2026_02_24_023024_create_service_histories_table', 1),
(10, '2026_03_04_043512_create_accounts_table', 1),
(11, '2026_03_04_043512_create_subscriptions_table', 1),
(12, '2026_03_04_043513_create_subscription_allocations_table', 1),
(13, '2026_03_06_035021_add_new_fields_to_health_records_table', 2),
(14, '2026_04_30_073146_add_price_and_images_to_devices_table', 3),
(15, '2026_05_06_012031_make_device_columns_nullable', 4),
(16, '2026_05_11_024500_add_device_password_to_devices_table', 5);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_histories`
--

CREATE TABLE `service_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `device_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('ck26XpR4jZM5gWndOdujFXZmlfnlgFrIKmbDkWMX', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZzdFSDkxeFNhRWJFWWM1UkFiZHdIWndialZ0eXZVR3laNG5Bb2Z2UyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1778812860),
('HtIPneUrNVRAMI5YNo0B9ugAI7GAwbl47Pl2l8At', 2, '192.168.1.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZmNuaG5YZHYxQ0JPQTRvOTUxQnkxSlVzbWV4d1VwbVplb0JScGVjWiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xOTIuMTY4LjEuMjAwOjgwMDAvaW52ZW50b3J5L1Bob25lIjtzOjU6InJvdXRlIjtzOjE1OiJpbnZlbnRvcnkuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1778814268),
('jDIeySO0jgwz22S6o0duJ5dCHF4rFuZWs4TQh9l7', NULL, '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR0ZzSklWcVM4NzhRY0Z1d1c1TW9ZSkJndnBMTzNMRmhON0h1dEREayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xOTIuMTY4LjEuMjAwOjgwMDAvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1778639626),
('pHNVgPjHqiaCBOrWs92pF3isZqnq1gsSEHJg2rm9', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoib0JsSFg3SnNteWNGRmN4Tk9xcG1BQjRQamdYZU56Z0IzWUxHQWJ3OSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozODoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2ludmVudG9yeS9MYXB0b3AiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1778656207),
('pW8RVdW72sx5HyhQqQxoYFypzYHlLETICkHyBR5M', 2, '192.168.1.120', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSlZjd2hacVR6SnBpMkFEekpQWmhLM3FEcXZGak1pYVFiUEdPekRpOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDI6Imh0dHA6Ly8xOTIuMTY4LjEuMjAwOjgwMDAvaW52ZW50b3J5L2l0ZW0vMyI7czo1OiJyb3V0ZSI7czoxNDoiaW52ZW50b3J5LnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1778818871),
('SEvoUFe138KpQjjNqYITAHu9LVji9QGETZNPjd9N', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQ3VUUHBpV05CNzNpN1pWdTNYTDBndHB1TkNrYm5vaUo2MVByUU51VSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9pbnZlbnRvcnkvUEMiO3M6NToicm91dGUiO3M6MTU6ImludmVudG9yeS5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1778646359),
('tkokeCijXtdifMTVmIx8y5yRmTrPeCRn4UsxXosQ', NULL, '192.168.1.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUGZTbUtKdGh0dW9nYzJLRkp3MWhBSHlyNW9rS0tGME0xSXNiUlltZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xOTIuMTY4LjEuMjAwOjgwMDAvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1778825654),
('YtpF4Pb8ygRjA4PGxwfmeIuVVYm5IOCBxpdD2652', 2, '192.168.1.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRXRnUVg0blpaTnlFOFFtVlc4OW55YXZuMWtIUkxKVmhuZk42d1d0YyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDM6Imh0dHA6Ly8xOTIuMTY4LjEuMjAwOjgwMDAvaW52ZW50b3J5L2l0ZW0vNTIiO3M6NToicm91dGUiO3M6MTQ6ImludmVudG9yeS5zaG93Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1778641203);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `plan` varchar(255) NOT NULL,
  `period` varchar(255) NOT NULL,
  `bill` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `next_payment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `account_id`, `plan`, `period`, `bill`, `currency`, `next_payment_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Adobe Photoshop & Lightroom', 'Yearly', 782000.00, 'IDR', '2026-03-19', '2026-03-05 01:54:05', '2026-03-11 00:15:08'),
(3, 2, 'Adobe Photoshop & Lightroom', 'Yearly', 1579752.00, 'IDR', '2027-05-07', '2026-03-11 20:28:32', '2026-05-11 02:08:51'),
(4, 2, 'Adobe Ilustrator', 'Yearly', 1722276.00, 'IDR', '2027-01-29', '2026-03-11 20:30:10', '2026-03-11 20:30:25');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_allocations`
--

CREATE TABLE `subscription_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `assignable_type` varchar(255) NOT NULL,
  `assignable_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_allocations`
--

INSERT INTO `subscription_allocations` (`id`, `subscription_id`, `assignable_type`, `assignable_id`, `created_at`, `updated_at`) VALUES
(4, 1, 'App\\Models\\Employee', 2, '2026-03-08 19:25:08', '2026-03-08 19:25:08'),
(5, 4, 'App\\Models\\Device', 3, '2026-03-11 20:31:26', '2026-03-11 20:31:26'),
(6, 3, 'App\\Models\\Device', 3, '2026-03-11 20:31:52', '2026-03-11 20:31:52'),
(7, 4, 'App\\Models\\Employee', 2, '2026-05-05 22:01:37', '2026-05-05 22:01:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'test@example.com', '2026-03-05 01:35:45', '$2y$12$BezEwtgW6u2Wfq7/zcQdzOGVJniEo9AO1nhlNBXnbUi6rWWSgsu7u', 'GgM9oiwP0s', '2026-03-05 01:35:45', '2026-03-05 01:35:45'),
(2, 'helmy', 'helmy@gmail.com', NULL, '$2y$12$VWh3qVm.HQod5mjtSvcMMOc30qgR2N5.6viKHbdJNqqtNBA3O.v5m', NULL, '2026-04-09 19:47:31', '2026-04-09 19:47:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `accounts_email_unique` (`email`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_device_id_foreign` (`device_id`),
  ADD KEY `activity_logs_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_name_unique` (`name`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `devices_employee_id_foreign` (`employee_id`),
  ADD KEY `devices_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_email_unique` (`email`),
  ADD KEY `employees_department_id_foreign` (`department_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `health_records_device_id_foreign` (`device_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `service_histories`
--
ALTER TABLE `service_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_histories_device_id_foreign` (`device_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_account_id_foreign` (`account_id`);

--
-- Indexes for table `subscription_allocations`
--
ALTER TABLE `subscription_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_allocations_subscription_id_foreign` (`subscription_id`),
  ADD KEY `subscription_allocations_assignable_type_assignable_id_index` (`assignable_type`,`assignable_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `service_histories`
--
ALTER TABLE `service_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscription_allocations`
--
ALTER TABLE `subscription_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_logs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `devices_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_histories`
--
ALTER TABLE `service_histories`
  ADD CONSTRAINT `service_histories_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_allocations`
--
ALTER TABLE `subscription_allocations`
  ADD CONSTRAINT `subscription_allocations_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
