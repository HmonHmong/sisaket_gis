-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 02, 2026 at 04:58 AM
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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'BACKUP', 'database', NULL, 'ส่งออกข้อมูลระบบ GIS สำนักช่าง ทั้งหมด (.sql)', '::1', '2026-02-19 08:54:50'),
(2, 1, 'MAINTENANCE', 'storage', NULL, 'ลบไฟล์ขยะโครงการจำนวน 0 ไฟล์', '::1', '2026-02-25 08:54:18'),
(3, 1, 'UPDATE', 'project', 5, 'ย้ายโครงการลงถังขยะ: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-25 08:54:31'),
(4, 1, 'UPDATE', 'project', 6, 'ย้ายโครงการลงถังขยะ: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-25 08:57:34'),
(5, 1, 'DELETE', 'project', 7, 'ลบโครงการถาวร: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-25 09:01:59'),
(6, 1, 'UPDATE', 'project', 8, 'ย้ายโครงการลงถังขยะ: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-25 09:16:46'),
(7, 1, 'UPDATE', 'project', 9, 'ย้ายโครงการลงถังขยะ: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-25 09:23:19'),
(8, 1, 'DELETE', 'project', 9, 'ลบโครงการถาวร: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-26 02:39:18'),
(9, 1, 'DELETE', 'project', 8, 'ลบโครงการถาวร: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-26 02:39:20'),
(10, 1, 'UPDATE', 'project', 10, 'แก้ไข/เปลี่ยนสถานะโครงการ: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล (กำลังดำเนินการ 75%)', '::1', '2026-02-26 03:32:42'),
(11, 1, 'UPDATE', 'project', 10, 'แก้ไข/เปลี่ยนสถานะโครงการ: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล (กำลังดำเนินการ 62%)', '::1', '2026-02-26 03:32:50'),
(12, 1, 'UPDATE', 'project', 10, 'ย้ายโครงการลงถังขยะ: โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', '::1', '2026-02-26 03:33:12'),
(13, 1, 'UPDATE', 'project', 10, 'กู้คืนโครงการจากถังขยะ (ID: 10)', '::1', '2026-02-26 03:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `infrastructure_types`
--

CREATE TABLE `infrastructure_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `infrastructure_types`
--

INSERT INTO `infrastructure_types` (`id`, `type_name`, `category`) VALUES
(1, 'ถนนคอนกรีตเสริมเหล็ก', 'งานถนน'),
(2, 'ถนนลาดยาง (Tack Coat)', 'งานถนน'),
(3, 'ถนนลาดยาง (Recycling)', 'งานถนน'),
(4, 'ถนนหินคลุก', 'งานถนน'),
(5, 'ถนนลูกรัง', 'งานถนน'),
(6, 'ท่อลอดเหลี่ยม (Box Culvert)', 'งานระบายน้ำ'),
(7, 'ท่อกลม (Pipe Culvert)', 'งานระบายน้ำ'),
(8, 'ไฟฟ้าส่องสว่าง', 'งานไฟฟ้า'),
(9, 'ไฟฟ้าส่องสว่างโซลาร์เซลล์', 'งานไฟฟ้า');

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(3, NULL, 'ลบโครงการ', 'โครงการ \'ทดสอบซ่อมถนนหินคลุก\' ถูกลบออกจากระบบโดย ผู้ดูแลระบบสำนักช่าง', '', 1, '2026-02-17 04:05:09'),
(4, NULL, 'ลบโครงการ', 'โครงการ \'ทดสอบซ่อมถนนหินคลุก\' ถูกลบออกจากระบบโดย ผู้ดูแลระบบสำนักช่าง', '', 1, '2026-02-17 04:05:11'),
(5, NULL, 'ลบโครงการ', 'โครงการ \'โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล\' ถูกลบออกจากระบบโดย ผู้ดูแลระบบสำนักช่าง', '', 1, '2026-02-19 07:19:15'),
(6, NULL, 'ลบโครงการ', 'โครงการ \'โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล\' ถูกลบออกจากระบบโดย ผู้ดูแลระบบสำนักช่าง', '', 1, '2026-02-19 07:34:15'),
(7, NULL, 'ย้ายข้อมูลลงถังขยะ', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกย้ายลงถังขยะ', '', 0, '2026-02-25 08:54:31'),
(8, NULL, 'ย้ายข้อมูลลงถังขยะ', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกย้ายลงถังขยะ', '', 0, '2026-02-25 08:57:34'),
(9, NULL, 'ลบข้อมูลโครงการ', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกลบเรียบร้อยแล้ว', '', 0, '2026-02-25 09:01:59'),
(10, NULL, 'ย้ายข้อมูลลงถังขยะ', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกย้ายลงถังขยะ', '', 0, '2026-02-25 09:16:46'),
(11, NULL, 'ย้ายข้อมูลลงถังขยะ', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกย้ายลงถังขยะ', '', 0, '2026-02-25 09:23:19'),
(12, NULL, 'ลบข้อมูลโครงการถาวร', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกลบถาวรเรียบร้อยแล้ว', '', 0, '2026-02-26 02:39:18'),
(13, NULL, 'ลบข้อมูลโครงการถาวร', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกลบถาวรเรียบร้อยแล้ว', '', 0, '2026-02-26 02:39:20'),
(14, NULL, 'ย้ายข้อมูลลงถังขยะ', 'โครงการ โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล ถูกย้ายลงถังขยะ', '', 0, '2026-02-26 03:33:12');

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
  `progress_percent` int(3) NOT NULL DEFAULT 0,
  `status_remark` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `route_name`, `infrastructure_type`, `fiscal_year`, `district_name`, `distance`, `width`, `has_shoulder`, `shoulder_width`, `area`, `budget_amount`, `budget_type`, `supervisor_name`, `budget_detail`, `project_image`, `start_lat`, `start_long`, `end_lat`, `end_long`, `status`, `progress_percent`, `status_remark`, `created_by`, `created_at`, `deleted_at`) VALUES
(10, 'โครงการก่อสร้างถนนคอนกรีตเชื่อมระหว่างตำบล', 'บ.กระเดา - บ.หนองโง้ง', 'ถนนคอนกรีต', 2569, 'ราษีไศล', 148.00, 5.00, 0, 0.02, 740.00, 499000.00, 'งบตามข้อบัญญัติ', 'นายภูริภัทร  ประสาร', NULL, NULL, 15.43015810, 104.18342710, 15.42900100, 104.18394900, 'กำลังดำเนินการ', 62, '', 1, '2026-02-26 03:26:55', NULL);

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
(17, 10, 'กระเดา', '12', 'ดู่', 'ราษีไศล', 'ศรีสะเกษ', NULL, NULL, 0),
(18, 10, 'หนองโง้ง', '7', 'หว้านคำ', 'ราษีไศล', 'ศรีสะเกษ', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `project_status_history`
--

CREATE TABLE `project_status_history` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `remark` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT 'สำนักช่าง' COMMENT 'หน่วยงาน',
  `role` enum('admin','staff','viewer') DEFAULT 'staff' COMMENT 'ระดับสิทธิ์',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะบัญชี',
  `last_login` datetime DEFAULT NULL COMMENT 'เข้าสู่ระบบล่าสุด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `position`, `phone`, `department`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2a$12$zk8PKOASddu96PjkItPoFu9JyLOZMeoC4gg4.BoVElSsBKyKtcss2', 'ผู้ดูแลระบบสำนักช่าง', 'นักวิชาการคอมพิวเตอร์', '0991755809', 'สำนักช่าง', 'admin', 'active', '2026-03-02 10:37:32', '2026-01-08 03:56:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `infrastructure_types`
--
ALTER TABLE `infrastructure_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

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
-- Indexes for table `project_status_history`
--
ALTER TABLE `project_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `changed_by` (`changed_by`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `infrastructure_types`
--
ALTER TABLE `infrastructure_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `project_attachments`
--
ALTER TABLE `project_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_points`
--
ALTER TABLE `project_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `project_status_history`
--
ALTER TABLE `project_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `project_status_history`
--
ALTER TABLE `project_status_history`
  ADD CONSTRAINT `project_status_history_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
