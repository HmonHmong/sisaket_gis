-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2026 at 04:26 AM
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
-- Database: `sisaket_gis`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('project','system','user','alert') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `route_name` text DEFAULT NULL,
  `infrastructure_type` varchar(100) DEFAULT NULL,
  `fiscal_year` int(11) NOT NULL,
  `district_name` varchar(100) NOT NULL,
  `distance` decimal(10,2) DEFAULT 0.00,
  `width` decimal(10,2) DEFAULT 0.00,
  `has_shoulder` tinyint(1) DEFAULT 0,
  `shoulder_width` decimal(10,2) DEFAULT 0.00,
  `area` decimal(15,2) DEFAULT 0.00,
  `budget_amount` decimal(15,2) DEFAULT 0.00,
  `budget_type` varchar(50) DEFAULT 'statute',
  `supervisor_name` varchar(150) DEFAULT NULL,
  `budget_detail` text DEFAULT NULL,
  `project_image` varchar(255) DEFAULT NULL,
  `start_lat` decimal(11,8) DEFAULT NULL,
  `start_long` decimal(11,8) DEFAULT NULL,
  `end_lat` decimal(11,8) DEFAULT NULL,
  `end_long` decimal(11,8) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'รอนุมัติ',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `route_name`, `infrastructure_type`, `fiscal_year`, `district_name`, `distance`, `width`, `has_shoulder`, `shoulder_width`, `area`, `budget_amount`, `budget_type`, `supervisor_name`, `budget_detail`, `project_image`, `start_lat`, `start_long`, `end_lat`, `end_long`, `status`, `created_by`, `created_at`) VALUES
(1, 'ทดสอบซ่อมถนนหินคลุก', NULL, 'road', 2568, '', 501.00, 5.00, 0, 0.00, 2505.00, 499000.00, 'statute', NULL, NULL, NULL, 14.54978500, 104.78003400, 14.54699500, 104.77826200, 'รอนุมัติ', NULL, '2026-01-08 03:20:56'),
(2, 'ทดสอบซ่อมถนนหินคลุก', 'บ.บ้านค้อน้อย', 'ถนนลูกรัง', 2569, '', 25.00, 25.00, 0, 2.00, 675.00, 400000.00, 'งบตามข้อบัญญัติงบประมาณรายจ่ายประจำปี', 'นายช่างวิศวกร ก.', NULL, NULL, 14.54978500, 14.94970900, 14.54699500, 104.77826200, 'รอนุมัติ', 1, '2026-02-17 03:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `project_attachments`
--

CREATE TABLE `project_attachments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_points`
--

CREATE TABLE `project_points` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `village` varchar(100) DEFAULT NULL,
  `moo` varchar(10) DEFAULT NULL,
  `sub_district` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `lat` decimal(11,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_points`
--

INSERT INTO `project_points` (`id`, `project_id`, `village`, `moo`, `sub_district`, `district`, `province`, `lat`, `lng`, `order_index`) VALUES
(2, 1, 'บ้านบก', NULL, 'โพนข่า', 'เมืองศรีสะเกษ', 'ศรีสะเกษ', NULL, NULL, 0),
(3, 2, 'บ้านค้อน้อย', '', 'หนองหมี', 'ราษีไศล', 'ศรีสะเกษ', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้งาน',
  `password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน (Hash)',
  `full_name` varchar(100) NOT NULL COMMENT 'ชื่อ-นามสกุล',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่งงาน',
  `department` varchar(100) DEFAULT 'สำนักช่าง' COMMENT 'หน่วยงาน',
  `role` enum('admin','staff','viewer') DEFAULT 'staff' COMMENT 'ระดับสิทธิ์',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะบัญชี',
  `last_login` datetime DEFAULT NULL COMMENT 'เข้าสู่ระบบล่าสุด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `position`, `department`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2a$12$zk8PKOASddu96PjkItPoFu9JyLOZMeoC4gg4.BoVElSsBKyKtcss2', 'ผู้ดูแลระบบสำนักช่าง', 'นักวิชาการคอมพิวเตอร์', 'สำนักช่าง', 'admin', 'active', '2026-02-17 10:23:00', '2026-01-08 03:56:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_created_by` (`created_by`),
  ADD KEY `idx_fiscal_district` (`fiscal_year`,`district_name`);

--
-- Indexes for table `project_attachments`
--
ALTER TABLE `project_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `project_points`
--
ALTER TABLE `project_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

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
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_attachments`
--
ALTER TABLE `project_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_points`
--
ALTER TABLE `project_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_attachments`
--
ALTER TABLE `project_attachments`
  ADD CONSTRAINT `project_attachments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_points`
--
ALTER TABLE `project_points`
  ADD CONSTRAINT `project_points_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
