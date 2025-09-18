-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 10:34 AM
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
-- Database: `payments_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `account_name` varchar(100) NOT NULL,
  `constant_value` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `code`, `account_name`, `constant_value`, `amount`, `created_at`) VALUES
(2, 'FIL1', 'FILING FEE - RA 9048', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(3, 'FIL2', 'FILING FEE - RA 10172/CFN', '3000.00', 3000.00, '2025-09-17 01:13:22'),
(4, 'LRF', 'LATE REGISTRATION FEE', '400.00', 400.00, '2025-09-17 01:13:22'),
(5, 'REG', 'REGISTRATION FEE', '300.00', 300.00, '2025-09-17 01:13:22'),
(6, 'SEC', 'SECRETARY\'S FEE', '200.00', 200.00, '2025-09-17 01:13:22'),
(7, 'LEG', 'LEGITIMATION FEE', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(8, 'MAF', 'MARRIAGE APPLICATION FEE', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(9, 'CCF', 'COUNSELLING FEE', '300.00', 300.00, '2025-09-17 01:13:22'),
(10, 'SOL', 'SOLEMNIZATION FEE', '200.00', 200.00, '2025-09-17 01:13:22'),
(11, 'SUP', 'SUPPLEMENTAL FEE', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(12, 'CEM', 'CEMETERY FEE', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(13, 'MF', 'MUNICIPAL FORM 103', '300.00', 300.00, '2025-09-17 01:13:22'),
(14, 'CTD', 'CERTIFICATION FEE (TD)', '200.00', 200.00, '2025-09-17 01:13:22'),
(15, 'CPH', 'CERTIFICATION FEE (PH)', '200.00', 200.00, '2025-09-17 01:13:22'),
(16, 'CTNI', 'CERT. FEE (IMPROVEMENT)', '200.00', 200.00, '2025-09-17 01:13:22'),
(17, 'CNP', 'CERTIFICATION FEE (NPD)', '200.00', 200.00, '2025-09-17 01:13:22'),
(18, 'OIF', 'OCULAR INSPECTION FEE', '300.00', 300.00, '2025-09-17 01:13:22'),
(19, 'VER', 'VERIFICATION FEE', '300.00', 300.00, '2025-09-17 01:13:22'),
(20, 'URI', 'URINALYSIS', '100.00', 100.00, '2025-09-17 01:13:22'),
(21, 'PT', 'PREGNANCY TEST', '100.00', 100.00, '2025-09-17 01:13:22'),
(22, 'FEC', 'FECALYSIS', '100.00', 100.00, '2025-09-17 01:13:22'),
(23, 'FOB', 'FOBT', '300.00', 300.00, '2025-09-17 01:13:22'),
(24, 'CBC', 'CBC W/ PC', '200.00', 200.00, '2025-09-17 01:13:22'),
(25, 'BT', 'BLOOD TYPING', '100.00', 100.00, '2025-09-17 01:13:22'),
(26, 'HB', 'HBsAG', '200.00', 200.00, '2025-09-17 01:13:22'),
(27, 'SYP', 'SYPHILIS', '200.00', 200.00, '2025-09-17 01:13:22'),
(28, 'DENG', 'DENGUE NS1', '300.00', 300.00, '2025-09-17 01:13:22'),
(29, 'GLU', 'GLUCOSE (FBS/RBS)', '150.00', 150.00, '2025-09-17 01:13:22'),
(30, 'TRI', 'TRIGLYCERIDE', '150.00', 150.00, '2025-09-17 01:13:22'),
(31, 'TC', 'TOTAL CHOLESTEROL', '150.00', 150.00, '2025-09-17 01:13:22'),
(32, 'HDL', 'HDL CHOLESTEROL', '150.00', 150.00, '2025-09-17 01:13:22'),
(33, 'LDL', 'CHOLESTEROL', '150.00', 150.00, '2025-09-17 01:13:22'),
(34, 'BUA', 'BUA', '150.00', 150.00, '2025-09-17 01:13:22'),
(35, 'CREA', 'CREATININE', '150.00', 150.00, '2025-09-17 01:13:22'),
(36, 'BUN', 'BUN', '150.00', 150.00, '2025-09-17 01:13:22'),
(37, 'SGPT', 'SGPT/ALT', '150.00', 150.00, '2025-09-17 01:13:22'),
(38, 'SGOT', 'SGOT/AST', '150.00', 150.00, '2025-09-17 01:13:22'),
(39, 'HEL', 'NO HELMET', '150.00', 150.00, '2025-09-17 01:13:22'),
(40, 'NDL', 'DRIVING W/O VALID LICENSE', '500.00', 500.00, '2025-09-17 01:13:22'),
(41, 'OBS', 'OBSTRUCTION', '200.00', 200.00, '2025-09-17 01:13:22'),
(42, 'ILL', 'ILLEGAL PARKING', '200.00', 200.00, '2025-09-17 01:13:22'),
(43, 'BLOCK', 'BLOCKING PEDESTRIAN LANE', '200.00', 200.00, '2025-09-17 01:13:22'),
(44, 'DOB', 'DOUBLE PARKING', '200.00', 200.00, '2025-09-17 01:13:22'),
(45, 'MUFF', 'NOISY MUFFLER', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(46, 'RECK', 'RECKLESS/ARROGANT DRIVER', '500.00', 500.00, '2025-09-17 01:13:22'),
(47, 'EVR', 'EXPIRED VEHICLE REG.', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(48, 'DEF', 'NO/DEFECTIVE PARTS', '500.00', 500.00, '2025-09-17 01:13:22'),
(49, 'ILLMOD', 'ILLEGAL MODIFICATION', '500.00', 500.00, '2025-09-17 01:13:22'),
(50, 'POT', 'PASSENGER ON TOP', '150.00', 150.00, '2025-09-17 01:13:22'),
(51, 'DIS', 'DISREGARDING OFFICER', '150.00', 150.00, '2025-09-17 01:13:22'),
(52, 'COL', 'COLORUM', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(53, 'NTB', 'NO TRASH BIN', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(54, 'CHAR', 'OVER/UNDER CHARGING', '500.00', 500.00, '2025-09-17 01:13:22'),
(55, 'SAN', 'DRIVING IN SANDO/SHORT', '200.00', 200.00, '2025-09-17 01:13:22'),
(56, 'OVER', 'OVERLOADED', '500.00', 500.00, '2025-09-17 01:13:22'),
(57, 'DRAG', 'DRAG RACING', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(58, 'NVS', 'NO VISA STICKER', '300.00', 300.00, '2025-09-17 01:13:22'),
(59, 'FAIL', 'FAILURE TO PRESENT EOV CARD', '200.00', 200.00, '2025-09-17 01:13:22'),
(60, 'POST', 'POSTING FEE', '200.00', 200.00, '2025-09-17 01:13:22'),
(61, 'LOAD', 'PAYLOADER', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(62, 'DT', 'DUMPTRUCK', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(63, 'BACK', 'BACKHOE', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(64, 'CRANE', 'CRANE', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(65, 'BULL', 'BULLDOZERS', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(66, 'GRADE', 'GRADER', '2500.00', 2500.00, '2025-09-17 01:13:22'),
(67, 'DOC', 'DOCUMENTARY STAMP', '30.00', 30.00, '2025-09-17 01:13:22'),
(68, 'PED', 'PEDDLERS', '50.00', 50.00, '2025-09-17 01:13:22'),
(69, 'PTR', 'PROFESSIONAL TAX RECEIPT', '300.00', 300.00, '2025-09-17 01:13:22'),
(70, 'SPF', 'SPECIAL PERMIT FEE', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(71, 'NBS', 'NEW BORN SCREENING', '1750.00', 1750.00, '2025-09-17 01:13:22'),
(72, 'OCCU', 'OCCUPATIONAL FEE', '200.00', 200.00, '2025-09-17 01:13:22'),
(73, 'VISA', 'VISA STICKER', '200.00', 200.00, '2025-09-17 01:13:22'),
(74, 'MTOP', 'MTOP', '200.00', 200.00, '2025-09-17 01:13:22'),
(75, 'AF', 'PAYMENT OF AF #51', '200.00', 200.00, '2025-09-17 01:13:22'),
(76, 'POL', 'POLLUTION', '200.00', 200.00, '2025-09-17 01:13:22'),
(77, 'SUPV', 'SUPERVISION FEE', '50.00', 50.00, '2025-09-17 01:13:22'),
(78, 'TER', 'TERMINAL FEE', '1000.00', 1000.00, '2025-09-17 01:13:22'),
(79, 'SPF1', 'SANITARY PERMIT FEE', '250.00', 250.00, '2025-09-17 01:13:22'),
(80, 'SPF2', 'SANITARY PERMIT FEE', '300.00', 300.00, '2025-09-17 01:13:22'),
(81, 'MC', 'MAYOR\'S CLEARANCE FEE', '200.00', 200.00, '2025-09-17 01:13:22'),
(82, 'MED', 'MEDICAL CERTIFICATE', '200.00', 200.00, '2025-09-17 01:13:22'),
(83, 'LDF', 'LDF', '100.00', 100.00, '2025-09-17 01:13:22'),
(84, 'VERMI', 'VERMI-COMPOST', '250.00', 250.00, '2025-09-17 01:13:22'),
(85, 'CERT', 'CERTIFICATION FEE', '200.00', 200.00, '2025-09-17 01:13:22'),
(88, 'TEST', 'TESTING', NULL, NULL, '2025-09-17 03:55:00'),
(89, 'ZCB', 'ZONING CLEARANCE BUSINESS', NULL, NULL, '2025-09-17 07:38:19');

-- --------------------------------------------------------

--
-- Table structure for table `or_numbers`
--

CREATE TABLE `or_numbers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `or_number` int(11) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `or_numbers`
--

INSERT INTO `or_numbers` (`id`, `user_id`, `or_number`, `is_used`) VALUES
(1, 2, 51, 0),
(2, 2, 52, 0),
(3, 2, 53, 0),
(4, 2, 54, 0),
(5, 2, 55, 0),
(6, 2, 56, 0),
(7, 2, 57, 0),
(8, 2, 58, 0),
(9, 2, 59, 0),
(10, 2, 60, 1),
(11, 2, 61, 0),
(12, 2, 62, 0),
(13, 2, 63, 0),
(14, 2, 64, 0),
(15, 2, 65, 0),
(16, 2, 66, 0),
(17, 2, 67, 0),
(18, 2, 68, 0),
(19, 2, 69, 0),
(20, 2, 70, 0),
(21, 2, 71, 0),
(22, 2, 72, 0),
(23, 2, 73, 0),
(24, 2, 74, 0),
(25, 2, 75, 0),
(26, 2, 76, 0),
(27, 2, 77, 0),
(28, 2, 78, 0),
(29, 2, 79, 0),
(30, 2, 80, 0),
(31, 2, 81, 0),
(32, 2, 82, 0),
(33, 2, 83, 0),
(34, 2, 84, 0),
(35, 2, 85, 0),
(36, 2, 86, 0),
(37, 2, 87, 0),
(38, 2, 88, 0),
(39, 2, 89, 0),
(40, 2, 90, 0),
(41, 2, 91, 0),
(42, 2, 92, 0),
(43, 2, 93, 0),
(44, 2, 94, 0),
(45, 2, 95, 0),
(46, 2, 96, 0),
(47, 2, 97, 0),
(48, 2, 98, 0),
(49, 2, 99, 0),
(50, 2, 100, 0),
(51, 3, 51, 0),
(52, 3, 52, 0),
(53, 3, 53, 0),
(54, 3, 54, 0),
(55, 3, 55, 0),
(56, 3, 56, 0),
(57, 3, 57, 0),
(58, 3, 58, 0),
(59, 3, 59, 0),
(60, 3, 60, 0),
(61, 3, 61, 0),
(62, 3, 62, 0),
(63, 3, 63, 0),
(64, 3, 64, 0),
(65, 3, 65, 0),
(66, 3, 66, 0),
(67, 3, 67, 0),
(68, 3, 68, 0),
(69, 3, 69, 0),
(70, 3, 70, 0),
(71, 3, 71, 0),
(72, 3, 72, 0),
(73, 3, 73, 0),
(74, 3, 74, 0),
(75, 3, 75, 0),
(76, 3, 76, 0),
(77, 3, 77, 0),
(78, 3, 78, 0),
(79, 3, 79, 0),
(80, 3, 80, 0),
(81, 3, 81, 0),
(82, 3, 82, 0),
(83, 3, 83, 0),
(84, 3, 84, 0),
(85, 3, 85, 0),
(86, 3, 86, 0),
(87, 3, 87, 0),
(88, 3, 88, 0),
(89, 3, 89, 0),
(90, 3, 90, 0),
(91, 3, 91, 0),
(92, 3, 92, 0),
(93, 3, 93, 0),
(94, 3, 94, 0),
(95, 3, 95, 0),
(96, 3, 96, 0),
(97, 3, 97, 0),
(98, 3, 98, 0),
(99, 3, 99, 0),
(100, 3, 100, 0),
(101, 4, 51, 0),
(102, 4, 52, 0),
(103, 4, 53, 0),
(104, 4, 54, 0),
(105, 4, 55, 0),
(106, 4, 56, 0),
(107, 4, 57, 0),
(108, 4, 58, 0),
(109, 4, 59, 0),
(110, 4, 60, 0),
(111, 4, 61, 0),
(112, 4, 62, 0),
(113, 4, 63, 0),
(114, 4, 64, 0),
(115, 4, 65, 0),
(116, 4, 66, 0),
(117, 4, 67, 0),
(118, 4, 68, 0),
(119, 4, 69, 0),
(120, 4, 70, 0),
(121, 4, 71, 0),
(122, 4, 72, 0),
(123, 4, 73, 0),
(124, 4, 74, 0),
(125, 4, 75, 0),
(126, 4, 76, 0),
(127, 4, 77, 0),
(128, 4, 78, 0),
(129, 4, 79, 0),
(130, 4, 80, 0),
(131, 4, 81, 0),
(132, 4, 82, 0),
(133, 4, 83, 0),
(134, 4, 84, 0),
(135, 4, 85, 0),
(136, 4, 86, 0),
(137, 4, 87, 0),
(138, 4, 88, 0),
(139, 4, 89, 0),
(140, 4, 90, 0),
(141, 4, 91, 0),
(142, 4, 92, 0),
(143, 4, 93, 0),
(144, 4, 94, 0),
(145, 4, 95, 0),
(146, 4, 96, 0),
(147, 4, 97, 0),
(148, 4, 98, 0),
(149, 4, 99, 0),
(150, 4, 100, 0),
(151, 5, 1, 0),
(152, 5, 2, 0),
(153, 5, 3, 0),
(154, 5, 4, 0),
(155, 5, 5, 0),
(156, 5, 6, 0),
(157, 5, 7, 0),
(158, 5, 8, 0),
(159, 5, 9, 0),
(160, 5, 10, 0),
(161, 5, 11, 0),
(162, 5, 12, 0),
(163, 5, 13, 0),
(164, 5, 14, 0),
(165, 5, 15, 0),
(166, 5, 16, 0),
(167, 5, 17, 0),
(168, 5, 18, 0),
(169, 5, 19, 0),
(170, 5, 20, 0),
(171, 5, 21, 0),
(172, 5, 22, 0),
(173, 5, 23, 0),
(174, 5, 24, 0),
(175, 5, 25, 0),
(176, 5, 26, 0),
(177, 5, 27, 0),
(178, 5, 28, 0),
(179, 5, 29, 0),
(180, 5, 30, 0),
(181, 5, 31, 0),
(182, 5, 32, 0),
(183, 5, 33, 0),
(184, 5, 34, 0),
(185, 5, 35, 0),
(186, 5, 36, 0),
(187, 5, 37, 0),
(188, 5, 38, 0),
(189, 5, 39, 0),
(190, 5, 40, 0),
(191, 5, 41, 0),
(192, 5, 42, 0),
(193, 5, 43, 0),
(194, 5, 44, 0),
(195, 5, 45, 0),
(196, 5, 46, 0),
(197, 5, 47, 0),
(198, 5, 48, 0),
(199, 5, 49, 0),
(200, 5, 50, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `payee` varchar(255) DEFAULT NULL,
  `reference_no` int(11) DEFAULT NULL,
  `code1` varchar(50) DEFAULT NULL,
  `account_name1` varchar(255) DEFAULT NULL,
  `amount1` decimal(10,2) DEFAULT NULL,
  `code2` varchar(50) DEFAULT NULL,
  `account_name2` varchar(255) DEFAULT NULL,
  `amount2` decimal(10,2) DEFAULT NULL,
  `code3` varchar(50) DEFAULT NULL,
  `account_name3` varchar(255) DEFAULT NULL,
  `amount3` decimal(10,2) DEFAULT NULL,
  `code4` varchar(50) DEFAULT NULL,
  `account_name4` varchar(255) DEFAULT NULL,
  `amount4` decimal(10,2) DEFAULT NULL,
  `code5` varchar(50) DEFAULT NULL,
  `account_name5` varchar(255) DEFAULT NULL,
  `amount5` decimal(10,2) DEFAULT NULL,
  `code6` varchar(50) DEFAULT NULL,
  `account_name6` varchar(255) DEFAULT NULL,
  `amount6` decimal(10,2) DEFAULT NULL,
  `code7` varchar(50) DEFAULT NULL,
  `account_name7` varchar(255) DEFAULT NULL,
  `amount7` decimal(10,2) DEFAULT NULL,
  `code8` varchar(50) DEFAULT NULL,
  `account_name8` varchar(255) DEFAULT NULL,
  `amount8` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `cash_received` decimal(12,2) DEFAULT 0.00,
  `change_amount` decimal(12,2) DEFAULT 0.00,
  `archived` tinyint(1) DEFAULT 0,
  `archive_reason` text DEFAULT NULL,
  `archived_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `date`, `payee`, `reference_no`, `code1`, `account_name1`, `amount1`, `code2`, `account_name2`, `amount2`, `code3`, `account_name3`, `amount3`, `code4`, `account_name4`, `amount4`, `code5`, `account_name5`, `amount5`, `code6`, `account_name6`, `amount6`, `code7`, `account_name7`, `amount7`, `code8`, `account_name8`, `amount8`, `total`, `cash_received`, `change_amount`, `archived`, `archive_reason`, `archived_date`) VALUES
(1, '2025-09-17', 'rosete', 600, 'AF', 'PAYMENT OF AF #51', 200.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 200.00, 0.00, 0.00, 0, NULL, NULL),
(2, '2025-09-17', 'rosete', 602, 'CPH', 'CERTIFICATION FEE (PH)', 200.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 200.00, 0.00, 0.00, 0, NULL, NULL),
(3, '2025-09-17', 'rosete', 605, 'OTHER', 'PAYMENT OF AF #51', 600.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 600.00, 0.00, 0.00, 0, NULL, NULL),
(4, '2025-09-17', 'rosete', 601, 'BACK', 'BACKHOE', 2500.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 2500.00, 0.00, 0.00, 0, NULL, NULL),
(5, '2025-09-17', 'rosete', 655, 'COL', 'COLORUM', 2500.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 2500.00, 0.00, 0.00, 0, NULL, NULL),
(6, '2025-09-17', 'rosete', 12301, 'BUN', 'BUN', 150.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 150.00, 0.00, 0.00, 0, NULL, NULL),
(7, '2025-09-17', 'rosete', 12302, 'ZCB', 'ZONING CLEARANCE BUSINESS', 740.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 740.00, 0.00, 0.00, 0, NULL, NULL),
(9, '2025-09-17', 'rosete', 12323, 'CRANE', 'CRANE', 2500.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 2500.00, 3000.00, 500.00, 0, NULL, NULL),
(10, '2025-09-18', 'pohj', 800, 'CPH', 'CERTIFICATION FEE (PH)', 200.00, 'CPH', 'CERTIFICATION FEE (PH)', 200.00, 'CRANE', 'CRANE', 2500.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 2900.00, 3000.00, 100.00, 0, NULL, NULL),
(11, '2025-09-18', 'Eduardo', 12333, 'AF', 'PAYMENT OF AF #51', 200.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 200.00, 500.00, 300.00, 0, NULL, NULL),
(12, '2025-09-18', 'HJHJH', 12334, 'CERT', 'CERTIFICATION FEE', 200.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, '', '', 0.00, 200.00, 500.00, 300.00, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Cashier','Encoder') NOT NULL,
  `or_start` int(11) NOT NULL,
  `or_end` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password`, `role`, `or_start`, `or_end`, `created_at`) VALUES
(1, 'VILMA', 'LOPEZ', 'admin1', '$2y$10$lOOJJsYguaOj3yRGLsY1be8GCiVjm6a4H5Y5MVLj1CRnYpa6gpLUy', 'Encoder', 1, 50, '2025-09-17 04:29:27'),
(2, 'VILMA', 'LOPEZ', 'cashier1', '$2y$10$ZGoDle.3ziS5Tf3Y.fg35u2lTjCak47yP8vM6wXAgSWaiX8UHY4CG', 'Encoder', 51, 100, '2025-09-17 04:35:16'),
(3, 'VILMA', 'LOPEZ', 'cashier2', '$2y$10$ZGoDle.3ziS5Tf3Y.fg35u2lTjCak47yP8vM6wXAgSWaiX8UHY4CG', 'Encoder', 400, 500, '2025-09-17 04:41:08'),
(4, 'VILMA', 'LOPEZ', 'user2', '$2y$10$vtRiMWqU4S6R9i//36Ea7Oqe3zIz7kSYEOvePjskv32FGvXOrlS5G', '', 600, 700, '2025-09-17 04:57:50'),
(5, 'rchmond', 'rosete', 'ictmon', '$2y$10$dNU3aOVJ3UHwTTSlVy5i9OydwRQxgz8dVMwmB87wuQ/1cHQEYdAm.', 'Admin', 800, 900, '2025-09-17 05:09:55'),
(6, 'EDU', 'GUILLERMO', 'EDU', '$2y$10$cvZ32LFhN5ltGZiY4y1T7uC2fiPfRlKA7wj.eoUxzBopS.heeMQyy', '', 12301, 12350, '2025-09-17 07:35:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `or_numbers`
--
ALTER TABLE `or_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `or_numbers`
--
ALTER TABLE `or_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `or_numbers`
--
ALTER TABLE `or_numbers`
  ADD CONSTRAINT `or_numbers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
