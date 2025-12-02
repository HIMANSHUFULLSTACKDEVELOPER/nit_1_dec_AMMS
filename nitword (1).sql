-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 28, 2025 at 05:22 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nitword`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `max_marks` int(11) DEFAULT 100,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `status` enum('active','expired','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `teacher_id`, `class_id`, `title`, `description`, `subject`, `due_date`, `max_marks`, `attachment_path`, `attachment_name`, `status`, `created_at`, `updated_at`) VALUES
(15, 51, 79, 'chemistry', 'all bring the book there on it', 'book check on there', '2025-12-01 13:34:00', 10, 'uploads/assignments/692411caaffa0_Screenshot 2025-11-21 at 7.26.37 PM.png', 'Screenshot 2025-11-21 at 7.26.37 PM.png', 'active', '2025-11-24 02:35:30', '2025-11-24 02:35:30');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_comments`
--

CREATE TABLE `assignment_comments` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('teacher','student') NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_text` longtext NOT NULL,
  `submission_file_path` varchar(500) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `marks_obtained` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `status` enum('submitted','late','graded','pending') DEFAULT 'submitted',
  `attachment_data` longblob DEFAULT NULL,
  `attachment_size` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_summary`
--

CREATE TABLE `attendance_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` int(11) DEFAULT 0,
  `present_days` int(11) DEFAULT 0,
  `absent_days` int(11) DEFAULT 0,
  `late_days` int(11) DEFAULT 0,
  `attendance_percentage` decimal(5,2) DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_summary`
--

INSERT INTO `attendance_summary` (`id`, `student_id`, `class_id`, `month`, `year`, `total_days`, `present_days`, `absent_days`, `late_days`, `attendance_percentage`, `last_updated`) VALUES
(552, 34, 53, 11, 2025, 4, 3, 1, 0, 75.00, '2025-11-20 01:30:15'),
(553, 35, 53, 11, 2025, 4, 3, 1, 0, 75.00, '2025-11-20 01:30:15'),
(554, 36, 53, 11, 2025, 4, 3, 1, 0, 75.00, '2025-11-20 01:30:15'),
(555, 37, 53, 11, 2025, 4, 3, 1, 0, 75.00, '2025-11-20 01:30:15'),
(556, 38, 53, 11, 2025, 4, 2, 2, 0, 50.00, '2025-11-20 01:30:15'),
(557, 59, 74, 11, 2025, 3, 3, 0, 0, 100.00, '2025-11-20 01:30:15'),
(558, 60, 74, 11, 2025, 3, 3, 0, 0, 100.00, '2025-11-20 01:30:15'),
(559, 61, 74, 11, 2025, 3, 3, 0, 0, 100.00, '2025-11-20 01:30:15'),
(560, 62, 74, 11, 2025, 3, 2, 1, 0, 66.67, '2025-11-20 01:30:15'),
(561, 63, 74, 11, 2025, 3, 2, 1, 0, 66.67, '2025-11-20 01:30:15'),
(562, 135, 79, 11, 2025, 2, 1, 1, 0, 50.00, '2025-11-20 01:30:15'),
(563, 136, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(564, 137, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(565, 138, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(566, 139, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(567, 140, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(568, 141, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(569, 142, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(570, 143, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(571, 144, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(572, 145, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(573, 146, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(574, 147, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(575, 148, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(576, 149, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(577, 150, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(578, 151, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(579, 152, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(580, 153, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(581, 154, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(582, 155, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(583, 156, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(584, 157, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(585, 158, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(586, 159, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(587, 160, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(588, 161, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(589, 162, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(590, 163, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(591, 164, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(592, 165, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(593, 166, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(594, 167, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(595, 168, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(596, 169, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(597, 170, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(598, 171, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(599, 172, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(600, 173, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(601, 174, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(602, 175, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(603, 176, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(604, 177, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(605, 178, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(606, 179, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(607, 180, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(608, 181, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(609, 182, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(610, 183, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(611, 184, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(612, 185, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(613, 186, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(614, 187, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(615, 188, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(616, 189, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(617, 190, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(618, 191, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(619, 192, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(620, 193, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(621, 194, 79, 11, 2025, 2, 2, 0, 0, 100.00, '2025-11-20 01:30:15'),
(622, 195, 79, 11, 2025, 2, 1, 1, 0, 50.00, '2025-11-20 01:30:15');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user1_type` enum('teacher','student') NOT NULL,
  `user2_id` int(11) NOT NULL,
  `user2_type` enum('teacher','student') NOT NULL,
  `last_message` text DEFAULT NULL,
  `last_message_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unread_count_user1` int(11) DEFAULT 0,
  `unread_count_user2` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('teacher','student') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `receiver_type` enum('teacher','student') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `sender_type`, `receiver_id`, `receiver_type`, `message`, `is_read`, `created_at`) VALUES
(1, 51, 'teacher', 44, 'student', 'fddf', 0, '2025-11-28 16:21:50'),
(2, 51, 'teacher', 149, 'student', 'hello hello', 1, '2025-11-28 16:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `section` varchar(10) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `department_id`, `year`, `section`, `teacher_id`, `semester`, `academic_year`, `created_at`) VALUES
(41, 'Electrical Engineering - 1st Year (Mr. Prashant Dange)', 4, 1, 'Electrical', 23, 1, '2025-26', '2025-11-14 16:46:50'),
(49, 'Civil Engineering - 1st Year (Mr. Dhiraj Meghe)', 4, 1, 'Civil', 22, 1, '2025-26', '2025-11-15 04:29:38'),
(50, 'Civil Engineering - 1st Year (Dr. Mohammad Sabir)', 4, 1, 'Civil', 26, 1, '2025-26', '2025-11-15 04:29:54'),
(51, 'Civil Engineering - 1st Year (Mr. Ghufran Ahmad Khan)', 4, 1, 'Civil', 38, 1, '2025-26', '2025-11-15 04:30:26'),
(52, 'Civil Engineering - 1st Year (Dr. Amit Kharwade)', 4, 1, 'Civil', 36, 1, '2025-26', '2025-11-15 04:34:09'),
(53, 'Civil Engineering - 1st Year (Dr. Abdul Ghaffar)', 4, 1, 'Civil', 37, 1, '2025-26', '2025-11-15 04:34:36'),
(56, 'Electrical Engineering - 1st Year (Dr. Mohammad Sabir)', 4, 1, 'Electrical', 26, 1, '2025-26', '2025-11-15 04:36:11'),
(57, 'Electrical Engineering - 1st Year (Mrs Rachna Daga)', 4, 1, 'Electrical', 28, 1, '2025-26', '2025-11-15 04:36:31'),
(58, 'Electrical Engineering - 1st Year (Mr. Rohan Deshmukh)', 4, 1, 'Electrical', 39, 1, '2025-26', '2025-11-15 04:38:11'),
(59, 'Electrical Engineering - 1st Year (Mr. Harshal Ghatole)', 4, 1, 'Electrical', 34, 1, '2025-26', '2025-11-15 04:38:47'),
(60, 'Mechanical Engineering - 1st Year (Mr. Prashant Dange)', 4, 1, 'Mechanical', 23, 1, '2025-26', '2025-11-15 04:39:16'),
(61, 'Mechanical Engineering - 1st Year (Mr. Dhiraj Meghe)', 4, 1, 'Mechanical', 22, 1, '2025-26', '2025-11-15 04:39:36'),
(62, 'Mechanical Engineering - 1st Year (Dr. Mohammad Sabir)', 4, 1, 'Mechanical', 26, 1, '2025-26', '2025-11-15 04:39:56'),
(63, 'Mechanical Engineering - 1st Year (Mr. Samrat Kavishwar)', 4, 1, 'Mechanical', 35, 1, '2025-26', '2025-11-15 04:40:11'),
(65, 'Computer Science & Engineering - A - 1st Year (Mrs. Mona Dange)', 4, 1, 'CSE-A', 25, 1, '2025-26', '2025-11-15 04:41:24'),
(66, 'Computer Science & Engineering - A - 1st Year (Dr. (Mrs.) Sonika Kochhar)', 4, 1, 'CSE-A', 24, 1, '2025-26', '2025-11-15 04:41:49'),
(68, 'Computer Science & Engineering - A - 1st Year (Mrs Rachna Daga)', 4, 1, 'CSE-A', 28, 1, '2025-26', '2025-11-15 04:42:34'),
(69, 'Computer Science & Engineering - A - 1st Year (Mr. Ayaz Sheikh)', 4, 1, 'CSE-A', 27, 1, '2025-26', '2025-11-15 04:42:51'),
(70, 'Computer Science & Engineering - A - 1st Year (Ms. Pournima Bhuyar)', 4, 1, 'CSE-A', 29, 1, '2025-26', '2025-11-15 04:43:05'),
(71, 'Computer Science & Engineering - B - 1st Year (Mrs. Mona Dange)', 4, 1, 'CSE-B', 25, 1, '2025-26', '2025-11-15 04:43:35'),
(72, 'Computer Science & Engineering - B - 1st Year (Dr. (Mrs.) Sonika Kochhar)', 4, 1, 'CSE-B', 24, 1, '2025-26', '2025-11-15 04:43:51'),
(74, 'Computer Science & Engineering - B - 1st Year (Mr. Rahul Kadam)', 4, 1, 'CSE-B', 40, 1, '2025-26', '2025-11-15 04:46:04'),
(75, 'Computer Science & Engineering - B - 1st Year (Mr. Ayaz Sheikh)', 4, 1, 'CSE-B', 27, 1, '2025-26', '2025-11-15 04:46:21'),
(76, 'Computer Science & Engineering - B - 1st Year (Ms. Pournima Bhuyar)', 4, 1, 'CSE-B', 29, 1, '2025-26', '2025-11-15 04:46:38'),
(78, 'Mechanical Engineering - 1st Year (Ms. Pournima Bhuyar)', 4, 1, 'Mechanical', 29, 1, '2025-26', '2025-11-15 05:00:58'),
(79, 'IT - 1st Year (Dr. (Mrs.) Meghna Jumde ', 4, 1, 'IT', 51, 1, '2025-26', '2025-11-16 09:09:41'),
(80, 'IT - 1st Year (Ms. Vidya Raut)', 4, 1, 'IT', 52, 1, '2025-26', '2025-11-17 09:44:46'),
(81, 'IT - 1st Year (Ms. Pournima Bhuyar)', 4, 1, 'IT', 29, 1, '2025-26', '2025-11-17 12:50:56'),
(82, 'IT - 1st Year (Mr. Tushar Shelke)', 4, 1, 'IT', 55, 1, '2025-26', '2025-11-17 12:55:47'),
(83, 'IT - 1st Year (Ms. Divya Lande)', 4, 1, 'IT', 57, 1, '2025-26', '2025-11-17 12:56:08'),
(84, 'IT - 1st Year (Ms. Hitaishi Chauhan)', 4, 1, 'IT', 53, 1, '2025-26', '2025-11-17 12:57:29'),
(86, 'IT - 1st Year (Ms. Aayushi Sharma)', 4, 1, 'IT', 54, 1, '2025-26', '2025-11-17 13:00:12'),
(87, 'Civil Engineering - 1st Year (Ms. Vidya Raut)', 4, 1, 'Civil', 52, 1, '2025-26', '2025-11-17 16:56:56'),
(88, 'Mechanical Engineering - 1st Year (Ms. Aayushi Sharma)', 4, 1, 'Mechanical', 54, 1, '2025-26', '2025-11-17 16:59:11'),
(89, 'Electrical Engineering - 1st Year (Dr. jitendrabhaiswar)', 4, 1, 'Electrical', 59, 1, '2025-26', '2025-11-17 17:03:44'),
(90, 'Computer Science & Engineering - B - 1st Year (Ms. Hitaishi Chauhan)', 4, 1, 'CSE-B', 53, 1, '2025-26', '2025-11-17 17:05:13'),
(91, 'Computer Science & Engineering - A - 1st Year (Ms. Hitaishi Chauhan)', 4, 1, 'CSE-A', 53, 1, '2025-26', '2025-11-17 17:05:37');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_teachers`
--

CREATE TABLE `class_teachers` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `hod_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_name`, `dept_code`, `hod_id`, `created_at`) VALUES
(4, '1st year', '1st Year -', 15, '2025-11-13 18:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notice_type` enum('info','warning','success','danger') DEFAULT 'info',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `target_audience` enum('all','students','teachers','hods','parents') DEFAULT 'all',
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `message`, `notice_type`, `priority`, `target_audience`, `is_active`, `start_date`, `end_date`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'meeting', 'good evening', 'success', 'medium', 'all', 1, '2025-11-28', NULL, 1, '2025-11-28 13:00:54', '2025-11-28 13:00:54');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `parent_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `student_id` int(11) NOT NULL,
  `relationship` enum('father','mother','guardian') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `parent_name`, `email`, `phone`, `photo`, `password`, `student_id`, `relationship`, `created_at`) VALUES
(13, 'Mr. Rajendra Patil', 'rajendrapatil@gmail.com', '9545966656', 'parent_1763386691_691b2543570b2.jpeg', '$2y$10$70jxX4SoxjllzA2CG8TKAe5atjGEkAXVV4N8yZllH2B9EUcF6xF7S', 149, 'father', '2025-11-17 13:38:11');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `admission_year` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `roll_number`, `full_name`, `email`, `phone`, `photo`, `password`, `department_id`, `class_id`, `year`, `semester`, `admission_year`, `is_active`, `created_at`) VALUES
(135, 'IT-01', 'AAVANYA VILAS KHANDAL', 'aavanyakhandal_7254it25@nit.edu.in', '9309050268', '', '$2y$10$dkE2CiqDrIgZ8XmNUflph.Jbvka55b3M4BPnk.jNM1fOMtU3x3dia', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:34:29'),
(136, 'IT-02', 'ADITYA ANIL GOUR', 'adityagour_7139it25@nit.edu.in', '7517629740', '', '$2y$10$.8u5vCvxhSiz7qA7SdJNouMmVkTpQnh2NbmebUNEGqzGWTEjWAe7S', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:36:21'),
(137, 'IT-03', 'ANSHIKA SANTOSH KUMAR NAGDEVE', '[anshikanagdeve_7072it25@nit.edu.in](mailto:anshikanagdeve_7072it25@nit.edu.in)', '9623957788', 'student_1763384871_691b1e2726538.jpeg', '$2y$10$ZFAF0KmRc7zn9kgM3eZRX..BvKMrdMpEA4SuoMB03GY3.Kxn11Yem', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:37:51'),
(138, 'IT-04', 'ANUJ BISEK NAKPURE', '[anujnakpure_7295it25@nit.edu.in](mailto:anujnakpure_7295it25@nit.edu.in)', '9209464838', 'student_1763384952_691b1e784bf8e.jpeg', '$2y$10$xqY50yU3GOBte0FMEvoBCexC0/.jfHZEwF2t8oPZTZWHwDEeWyKmq', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:39:12'),
(139, 'IT-05', 'ARYAN GAJANAN GHANMODE', '[aryanghanmode_6945it25@nit.edu.in](mailto:aryanghanmode_6945it25@nit.edu.in)', '8983216759', 'student_1763385007_691b1eaf1c9dc.jpeg', '$2y$10$f6foBcEUsevJ7YU3EXbmoudAxMnCoUn1dfVmyUEu.JLfNr7KKV.rC', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:40:07'),
(140, 'IT-06', 'ARYAN SUNIL PATIL', '[aryanpatil_7307it25@nit.edu.in](mailto:aryanpatil_7307it25@nit.edu.in)', '9322954125', 'student_1763385051_691b1edb8d40a.jpeg', '$2y$10$srQloiojStAK61YfLFsLnum0kwlQnZ0aQToyK5s0ZII4q70VQZpXS', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:40:51'),
(141, 'IT-07', 'ARYAN VINOD MANGHATE', '[aryanmanghate_7081it25@nit.edu.in](mailto:aryanmanghate_7081it25@nit.edu.in)', '8329871648', 'student_1763385098_691b1f0abf1c8.jpeg', '$2y$10$rAATM3rKnEIDrO0nvij17u8pRTVMlA3YnMdc4JAeyv0O6cxY54ahC', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:41:38'),
(142, 'IT-08', 'ATHARVA RAJENDRA SATIKOSARE', '[atharvasatikosare_7114it25@nit.edu.in](mailto:atharvasatikosare_7114it25@nit.edu.in)', '9860052615', 'student_1763385142_691b1f3657db4.jpeg', '$2y$10$E7KIYNCIiTA5tF/2M0QE2uLlyOXygtz/TDgNH3D/8WrnN0YbPXkyK', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:42:22'),
(143, 'IT-09', 'ATHARVA SANTOSH DEULKAR', '[atharvadeulkar_7187it25@nit.edu.in](mailto:atharvadeulkar_7187it25@nit.edu.in)', '8956960820', 'student_1763385201_691b1f7129e7b.jpeg', '$2y$10$N5OYTGMALfObTdQNBOIlYeH.G6FkvEH/5CsNqCRMGkpO3lkypEB.u', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:43:21'),
(144, 'IT-10', 'BHAKTI BALIRAM KURWADE', '[bhaktikurwade_7210it25@nit.edu.in](mailto:bhaktikurwade_7210it25@nit.edu.in)', '7218427723', 'student_1763385249_691b1fa1ae71c.jpeg', '$2y$10$XH91g.XtwQJwE2q3C8YFJO9GtTsagcaIOupOWJh7z9zJA9ggT/Q3u', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:44:09'),
(145, 'IT-11', 'DEVYANI RAJENDRA PAL', '[devyanipal_7263it25@nit.edu.in](mailto:devyanipal_7263it25@nit.edu.in)', '9209540013', 'student_1763385301_691b1fd5afc10.jpeg', '$2y$10$1TUICo9X3sT0R7zKV6ZGP.YNq7yMHzUsIH5QGifVYCzd1TlfbfGXS', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:45:01'),
(146, 'IT-12', 'DHANASHREE DINESH MAHORKAR', '[dhanashreemahorkar_7180it25@nit.edu.in](mailto:dhanashreemahorkar_7180it25@nit.edu.in)', '8237397356', 'student_1763385361_691b2011ae1a1.jpeg', '$2y$10$hjtkJ76wKhrbgsB8etlit.Vwtq/BmBMdGOj5q8oByDBvdbZGDZF.G', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:46:01'),
(147, 'IT-13', 'DHANASHREE SHIVAJI KAYANDE', '[dhanashreekayande_7189it25@nit.edu.in](mailto:dhanashreekayande_7189it25@nit.edu.in)', '9834835808', 'student_1763385471_691b207fcb159.jpeg', '$2y$10$rf5bXqFF8zvjoRj69I2uROjGeefmbOlGP7cE29Bmbk2ZNtzz7P9iu', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:47:51'),
(148, 'IT-14', 'GAYATRI ARUN SHEWALKAR', '[gayatrishewalkar_7145it25@nit.edu.in](mailto:gayatrishewalkar_7145it25@nit.edu.in)', '8446456270', 'student_1763385516_691b20acec94e.jpeg', '$2y$10$3vv8toFxEuZQJp3d.wa4q.Y77OcdIZtn/K1Zswtz1XIA07Emhnygi', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:48:36'),
(149, 'IT-15', 'HIMANSHU RAJENDRA PATIL', '[himanshupatil_7094it25@nit.edu.in](mailto:himanshupatil_7094it25@nit.edu.in)', '8788209773', 'student_149_1763823801.png', '$2y$10$iRceNQNH6/P0enHQiplJgucmc16lGXe25WaQJgNFMVFNqzsj1yA8m', 4, 79, 1, 1, '2025', 1, '2025-11-17 07:50:30'),
(150, 'IT-16', 'KOMAL ATISH SHASTRAKAR', '[komalshastrakar_7275it25@nit.edu.in](mailto:komalshastrakar_7275it25@nit.edu.in)', '9637002755', 'student_1763386809_691b25b95743c.jpeg', '$2y$10$lewB/5edzUuI8h0vxZYbDe7fgv11XLaMTWUkV7iypJMI.sJrfNwA6', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:10:09'),
(151, 'IT-17', 'KRUTIKA PRITAM BALPANDE', '[krutikabalpande_6998it25@nit.edu.in](mailto:krutikabalpande_6998it25@nit.edu.in)', '9881869681', 'student_151_1763386899.jpeg', '$2y$10$DwgYu0p3ie5uKPwf36yjquduwJfuARsApTy6JlDYBfymyhyVB5IOO', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:11:01'),
(152, 'IT-18', 'KUNDAN GUNARAM GAIDHANE', '[kundangaidhane_7197it25@nit.edu.in](mailto:kundangaidhane_7197it25@nit.edu.in)', '9764851134', 'student_1763386976_691b26601c457.jpeg', '$2y$10$D2TUdCkkILsywoI9d9PnPegwTbN9PRv/PRqEE60/BqjfWtVj.ADWm', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:12:56'),
(153, 'IT-19', 'MAMTA SUBHASH KOTHE', '[mamtakothe_7250it25@nit.edu.in](mailto:mamtakothe_7250it25@nit.edu.in)', '8888442596', 'student_1763387034_691b269a722a0.jpeg', '$2y$10$LoYRC8i2hCjROonDA3Hn1.0o4Ec4KS5PLRCYE4eGJEZIkJigY0sHG', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:13:54'),
(154, 'IT-20', 'MANASI VILAS SHEGAONKAR', '[manasishegaonkar_7151it25@nit.edu.in](mailto:manasishegaonkar_7151it25@nit.edu.in)', '7385216816', 'student_1763387102_691b26dea258f.jpeg', '$2y$10$ZM78Bv7acBz9R9EqAqe0TuIeB0g86gYydBCtl49rMj4gX37au6x36', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:15:02'),
(155, 'IT-21', 'MANASWI PRAKASH BHAWALKAR', '[manaswibhawalkar_7214it25@nit.edu.in](mailto:manaswibhawalkar_7214it25@nit.edu.in)', '8830546235', 'student_1763387143_691b27070d959.jpeg', '$2y$10$8.fULD9sYlRVsanj.sIsiOOC29t63pvY19jMFOpx/lAa2zbuozH0i', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:15:43'),
(156, 'IT-22', 'NALINI RANJEET BISWAS', '[nalinibiswas_7111it25@nit.edu.in](mailto:nalinibiswas_7111it25@nit.edu.in)', '7517903753', 'student_1763387183_691b272fda7a5.jpeg', '$2y$10$AfuBeswNzKPUGaaxwCQ5/OW20OAAFTBLrB9fg0V4XeBoBwPrryiRi', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:16:23'),
(157, 'IT-23', 'NARGIS BRAMHANAND CHAUDHARI', '[nargischaudhari_7108it25@nit.edu.in](mailto:nargischaudhari_7108it25@nit.edu.in)', '9356848498', 'student_1763387223_691b27570024d.jpeg', '$2y$10$wu8Hq1zrBXGN/Z6C7r8OP.DtHIgMZOtefNKPVYiLq5U6bkNzoAKM.', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:17:03'),
(158, 'IT-24', 'NEHA DHANRAJ WAKDE', '[nehawakde_7062it25@nit.edu.in](mailto:nehawakde_7062it25@nit.edu.in)', '9552179076', 'student_1763387264_691b2780abc29.jpeg', '$2y$10$Igb.6Odr6iMgSCF77/E.F.QeZBw4sFwWMvPkF1wehrre7JTNMiy4q', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:17:44'),
(159, 'IT-25', 'NIKITA DADARAO PATIL', '[nikitapatil_7084it25@nit.edu.in](mailto:nikitapatil_7084it25@nit.edu.in)', '8600112994', 'student_1763387304_691b27a805899.jpeg', '$2y$10$xwMNyZkTVSrAiE2EdvnNaeHm9iD32Ej7QNeFFeBRTB4kmYN5FewuO', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:18:24'),
(160, 'IT-26', 'OM NARENDRA AUSARMOL', '[omausarmol_7178it25@nit.edu.in](mailto:omausarmol_7178it25@nit.edu.in)', '8421214208', 'student_1763387360_691b27e0c0659.jpeg', '$2y$10$GEBnbAYWThQj7D.RjJHnsORINLl6jXlQmho3zVsa2On4Fj7ap5mUC', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:19:20'),
(161, 'IT-27', 'OM RAMKRISHNA KHADATKAR', '[omkhadatkar_7274it25@nit.edu.in](mailto:omkhadatkar_7274it25@nit.edu.in)', '9028510918', 'student_1763387416_691b28189b382.jpeg', '$2y$10$S8RJY7Ch17Ol8XPF6IBeEuEt.nJ1U.Yjgo/EQ.vJ/50EfU7rz9gKi', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:20:16'),
(162, 'IT-28', 'PIYUSH JAYRAM PRASAD', '[piyushprasad_7224it25@nit.edu.in](mailto:piyushprasad_7224it25@nit.edu.in)', '7870276275', 'student_1763387460_691b28448f1aa.jpeg', '$2y$10$XNLCawLBofZ8H2kYwf3M0OVNmzFXRuHgCFMeDNNnUDkCQVT6Mmevm', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:21:00'),
(163, 'IT-29', 'PRANAY YOGRAJ PANORE', '[pranaypanore_6981it25@nit.edu.in](mailto:pranaypanore_6981it25@nit.edu.in)', '9699151494', 'student_163_1763448683.png', '$2y$10$iUSIqHJMYL/GXcUiO4AoRO4.dbG3tEGEuiYvteiYVqrqb6RQWXryu', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:21:43'),
(164, 'IT-30', 'PRANJALI RAJESH BARGATH', '[pranjalibargath_7238it25@nit.edu.in](mailto:pranjalibargath_7238it25@nit.edu.in)', '9699128614', 'student_1763387557_691b28a567b61.jpeg', '$2y$10$nIEOL2wpjtzXSFBntUgNn.D1yP0EwokHxC7s7i9gURV7k8OHtqjRq', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:22:37'),
(165, 'IT-31', 'PRATHMESH PREMDAS NICHANT', 'prathmeshnichant_7291it25@nit.edu.in', '9689070135', 'student_1763387613_691b28ddc88ce.jpeg', '$2y$10$2Cc.7ghNoeXlg9LvxTYf.en5n.CNwn6IsaBNt.8V.TWe.E2kmrqsK', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:23:33'),
(166, 'IT-32', 'PREETI JIYALAL SHAHU', 'preetishahu_7052it25@nit.edu.in', '9322278183', 'student_1763387691_691b292b90340.jpeg', '$2y$10$0G0igYIKAtk7QvR/SCoUYe307b8E24dfsO.KuPFUyl1EJAMDpiRA2', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:24:51'),
(167, 'IT-33', 'PURVA RAJESH REWATKAR', 'purvarewatkar_7184it25@nit.edu.in', '9699627399', 'student_1763387748_691b296428b27.jpeg', '$2y$10$G2o9rY9qrGzaJzG9V17r4OdGrlblUqCo1t8xaVsSneXg.Ql.JwlFe', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:25:48'),
(168, 'IT-34', 'RITESH PANJAB DHULASE', 'riteshdhulase_7095it25@nit.edu.in', '9356608789', 'student_1763387806_691b299e7f455.jpeg', '$2y$10$VAfBH9AAiASyoIQm5y3wme/CEb6n/QoKXW9.ntlt2Y9enZ22I3MWi', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:26:46'),
(169, 'IT-35', 'RIYA KISHOR YELEKAR', 'riyayelekar_7153it25@nit.edu.in', '8928068265', 'student_1763387874_691b29e2a451a.jpeg', '$2y$10$JwtfsuyjcQWSH6nW9C5AjeG1.T2emQ4FnIEnt7aHsF6vzl5wJLeAe', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:27:54'),
(170, 'IT-36', 'RIYA PRASHANT JAMGADE', 'riyajamgade_7271it25@nit.edu.in', '9022093269', 'student_1763387926_691b2a163d218.jpeg', '$2y$10$0F/u588dcCl71OyMOE38BOhLTmKStoM7xr7XjLF1MxyE.BWCauLD6', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:28:46'),
(171, 'IT-37', 'RIYA SANTOSH BHAGAT', 'riyabhagat_7130it25@nit.edu.in', '8390008309', 'student_1763387976_691b2a48bf6e3.jpeg', '$2y$10$G52n5MZrXkpteYfyxjVBkezsYNSfRaguifL4eD8XiX7mtj0Znwdwm', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:29:36'),
(172, 'IT-38', 'RIYA SANTOSH MUSALE', 'riyamusale_7194it25@nit.edu.in', '9307275418', 'student_1763388034_691b2a823f864.jpeg', '$2y$10$3jtSpMgRPiHUn.6WVRRbleX9wZTLSUGwbfoxmanIff/tRgnB5XOR.', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:30:34'),
(173, 'IT-39', 'ROHAN PRAMOD KHADSE', 'rohankhadse_7107it25@nit.edu.in', '9284023176', 'student_1763388086_691b2ab6aed84.jpeg', '$2y$10$57CyN28UvEiMRLUoJCPyRePI73Q25zNHrae6vMCq2lh9mMZ7PXH8e', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:31:26'),
(174, 'IT-40', 'ROHIT RUPCHAND KHOBRAGADE', 'rohitkhobragade_7313it25@nit.edu.in', '9021550328', 'student_1763388134_691b2ae6d9f56.jpeg', '$2y$10$jA3Oqp8nbl/t5UFGr9PJaukgIyayJuLH4/Pv6imM4lqcB.AuhBlc2', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:32:14'),
(175, 'IT-41', 'ROHIT SANDIP RATHOD', 'rohitrathod_6996it25@nit.edu.in', '7410761022', 'student_1763388185_691b2b19d297d.jpeg', '$2y$10$G3Ib1avO86y7sQrsu9pMeON3ESIMVAtTQV/h9ZGER2Nb1.EFV5wJS', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:33:05'),
(176, 'IT-42', 'SAKSHI MORESHWAR MESHRAM', 'sakshimeshram_7192it25@nit.edu.in', '9322403889', 'student_1763388235_691b2b4bd7da9.jpeg', '$2y$10$uF4qubwL53kNj3NSh4LUG.RSEkY0LdtW3yu2YzRg0BCoTxEPGK6Pi', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:33:55'),
(177, 'IT-43', 'SALONI PURUSHOTTAM CHOPDE', 'salonichopde_7248it25@nit.edu.in', '8459599077', 'student_1763388287_691b2b7fe3fa3.jpeg', '$2y$10$EOpqqQyK7hQC0SP9LyhSkuPlyk4OZCMw6uUm1c8Ms/zi.wZpAcrty', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:34:47'),
(178, 'IT-44', 'SAMARTH KISHOR BHOYAR', 'samarthbhoyar_7099it25@nit.edu.in', '9322348535', 'student_1763388350_691b2bbe25e3b.jpeg', '$2y$10$awkbgkCglr/s9dKNk0paXe8gj2tkhUa7ulHR9VcSS9dQXqxodjoEW', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:35:50'),
(179, 'IT-45', 'SAMIKSHA PRAKASH KANERE', 'samikshakanere_7278it25@nit.edu.in', '9764573116', 'student_1763388395_691b2beba7e17.jpeg', '$2y$10$Iu8nf.FJC8XLtWi8icU/u.TDUJMjJBi99aJ6YDS4M/IK72YMkt/Ru', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:36:35'),
(180, 'IT-46', 'SAROJ HARIDAS BAGDE', 'sarojbagde_7306it25@nit.edu.in', '8983816886', 'student_1763388456_691b2c2806b6e.jpeg', '$2y$10$a8PyNcNbhUrqqU4gPFgWb.qaUWvG9qgxMbNfzhgnw60PKD22.Vp8q', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:37:36'),
(181, 'IT-47', 'SARTHAK VILAS MESHRAM', 'sarthakmeshram_7102it25@nit.edu.in', '9766302812', 'student_1763388505_691b2c59505a8.jpeg', '$2y$10$LupvlwPUDZJQ/z8Iqo49WeCK5HEhCsUd/slCuqfK/QO.IjoUBA0vq', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:38:25'),
(182, 'IT-48', 'SHIVRAJ GANGADHAR DHAVALE', 'shivrajdhavale_7201it25@nit.edu.in', '8805577509', 'student_1763388548_691b2c8497b78.jpeg', '$2y$10$sQ4asvrlndQd0nfO4Ncf8.p9VapXjh.jC2VUHjqIYrrZr0VCa9MaW', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:39:08'),
(183, 'IT-49', 'SHRAVANI RAMESHWAR AMBULKAR', 'shravaniambulkar_7196it25@nit.edu.in', '9284517546', 'student_1763388598_691b2cb62fd77.jpeg', '$2y$10$2vjcqoyM.1O/uIvAG7DYGesN85RXJJrhksGNoBLKe2j7DQMpzhuR6', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:39:58'),
(184, 'IT-50', 'SHRUTI SANJAY WANDEKAR', 'shrutiwandekar_7205it25@nit.edu.in', '9673908512', 'student_1763388642_691b2ce29776e.jpeg', '$2y$10$kLJZfe21nWAdmQIn5QBvwebiD4oWuHxBPalA6NxT/DCnQqgMRhuGq', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:40:42'),
(185, 'IT-51', 'SHRUTI SEWAK KOHAD', 'shrutikohad_7303it25@nit.edu.in', '9075288540', 'student_1763388691_691b2d1341579.jpeg', '$2y$10$qZ.0oMEGSxgZhdiFt9jVCeloYRggZe/cE5TWDSchs6ndjFGfdjhZa', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:41:31'),
(186, 'IT-52', 'SHUBHAM SANJAY PAWAR', 'shubhampawar_7142it25@nit.edu.in', '9373964092', 'student_1763388746_691b2d4abca7f.jpeg', '$2y$10$CmcUjWZYzioQHo2E5X./m.Q6AmrTBAzgs3symBhFel.or4ICZ47xu', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:42:26'),
(187, 'IT-53', 'SIDDHANT RAJESH MAGARDE', 'siddhantmagarde_7159it25@nit.edu.in', '9322165638', 'student_1763388792_691b2d78733ce.jpeg', '$2y$10$J/EzAWJH4U8Z2iCFshj1KucM9RktPGq3mK4Oo1Fz0gkT19rSN.DYu', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:43:12'),
(188, 'IT-54', 'SOHAM DINESH GULHANE', 'sohamgulhane_7193it25@nit.edu.in', '9359947568', 'student_1763388831_691b2d9f9734c.jpeg', '$2y$10$yUoz/29ENe0mMVKUV2Bx/ey3PTd0YcjDL1YQyq4z3aaqIhxJdowtO', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:43:51'),
(189, 'IT-55', 'SUJAL BHANUDAS WANODE', 'sujalwanode_7302it25@nit.edu.in', '9359045425', 'student_1763388882_691b2dd2881cf.jpeg', '$2y$10$KT0VjmvgzfUd2UM4ToZL..nHXAXJ0SSie8CNu8u4I3TM67OwyvDAy', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:44:42'),
(190, 'IT-56', 'SUJAL GAUTAM DABRASE', 'sujaldabrase_6932it25@nit.edu.in', '9767738051', 'student_1763388935_691b2e078daeb.jpeg', '$2y$10$Kd7BJO5U7OKqSmaSv5cA5OtlBqw9pdBScKrZI0MFSLjS4E8q8NYqC', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:45:35'),
(191, 'IT-57', 'TANVI SUNIL GHATOL', 'tanvighatol_7133it25@nit.edu.in', '9850794193', 'student_1763388981_691b2e35a8cfc.jpeg', '$2y$10$c6bvI8nBghabX8yAINrp7OstgISbuYeymi98NWX4Ts2cCv74mTg3e', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:46:21'),
(192, 'IT-59', 'UTTARA RAVINDRA BHOYAR', 'uttarabhoyar_7249it25@nit.edu.in', '9370338423', 'student_1763389078_691b2e961124c.jpeg', '$2y$10$tu5M62uK1aZPjB7yA5/h2.aTLov/eUV4LqYcUbVDLJ0tjeWwPr5d.', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:47:58'),
(193, 'IT-60', 'UTTARANSHI PANKAJ CHOUDHARY', 'uttaranshichoudhary_7085it25@nit.edu.in', '7276070340', 'student_1763389131_691b2ecb6d309.jpeg', '$2y$10$AeWvsQoqLdaMmz2kCZUZ/OBT1DdjbIfvZUC9Ds4OZQUxG45G/1QIG', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:48:51'),
(194, 'IT-61', 'VANSHIKA SANJAY NAGPURE', 'vanshikanagpure_7243it25@nit.edu.in', '7620103872', 'student_1763389181_691b2efd9d812.jpeg', '$2y$10$sLuHFAo8Kj6ZtP5VPttFJuPLEoyCankjq6Uycavz2RoCMGCE.zSiO', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:49:41'),
(195, 'IT-62', 'VEDANT VIJAYRAO GHARDE', 'vedantgharde_7143it25@nit.edu.in', '7276039131', 'student_1763389228_691b2f2c2b7a8.jpeg', '$2y$10$RPirpA6Ajk5oIgbKfuSf2eK8.Vv1wILm2QvobQnJe24nTVsN4Av2G', 4, 79, 1, 1, '2025', 1, '2025-11-17 08:50:28'),
(34, 'CE-01', 'ADITYA CHANDRASHEKHAR MESHRAM', 'adityameshram_7229ce25@nit.edu.in', '8408903740', 'student_34_1763719715.png', '$2y$10$kEBPK1NMIF2ZZdYpgU71xuGZMioS9A356e5D851BZXxP1tYez6vBm', 4, 53, 1, 1, '2025', 1, '2025-11-15 01:09:24'),
(35, 'CE-02', 'ANIKET RAJENDRA BANSOD', 'aniketbansod_7241ce25@nit.edu.in', '7709878368', 'student_35_1763719744.png', '$2y$10$Hv7fs6N4bWeDckyQJc1amOyyGW7k6wy4ojiOh6n.wzXwzIIB.GNhO', 4, 53, 1, 1, '2025', 1, '2025-11-15 01:10:45'),
(36, 'CE-03', 'ANKUSH RAJENDRA ADAY', 'ankushaday_6969ce25@nit.edu.in', '9699841693', 'student_36_1763719769.png', '$2y$10$b/Qv6DZZbnESf2qPnArhz.d3DZpT/x.tb1m9oJktfUD0xgBx1e7oO', 4, 53, 1, 1, '2025', 1, '2025-11-15 01:11:25'),
(37, 'CE-04', 'ANSH RAHUL GAJBHIYE', 'anshgajbhiye_7209ce25@nit.edu.in', '7773994195', 'student_37_1763719804.png', '$2y$10$ATmsKToMYC7UAgPINEKXtuMtFcZh39PGZKksnosluvkNDijBdBGMO', 4, 53, 1, 1, '2025', 1, '2025-11-15 01:12:27'),
(38, 'CE-05', 'ANUSHKA DHANRAJ RAKSHASKAR', 'anushkarakshaskar_7144ce25@nit.edu.in', '8975703331', 'student_38_1763719828.png', '$2y$10$KRxM4G06bNX/56BV6ACZwO0B4UFz3xbmraDLN.8YRAqSNo0CsTWzq', 4, 53, 1, 1, '2025', 1, '2025-11-15 01:13:16'),
(39, 'ME-01', 'ABDUL ZISHAN ABDUL JAVED SHEIKH', 'abdulzishan_6967me25@nit.edu.in', '8767644897', 'student_39_1763720270.png', '$2y$10$leLV0K/KiaiHhAqsHV/Hvu7BxmGFoPmvUCPQsDjTsrw3.f/C9ASmW', 4, 63, 1, 1, '2025', 1, '2025-11-15 01:16:36'),
(40, 'ME-02', 'ADESH PURUSHOTTAM GAURAV', 'adeshgurav_6989me25@nit.edu.in', '9284298135', 'student_40_1763399270.jpeg', '$2y$10$tcgL2nj52d4NYblmOFEE/OBqa4ZJ7FhkjAnJqyQUxKGPrD2N6JrFe', 4, 63, 1, 1, '2025', 1, '2025-11-15 01:18:44'),
(41, 'ME-03', 'ADITYA RAJENDRA WADBUDHE', 'adityawadbudhe_6962me25@nit.edu.in', '9226853072', 'student_41_1763720428.png', '$2y$10$tcgL2nj52d4NYblmOFEE/OBqa4ZJ7FhkjAnJqyQUxKGPrD2N6JrFe', 4, 63, 1, 1, '2025', 1, '2025-11-15 01:19:45'),
(42, 'ME-04', 'AKASH RAJKUMAR BINZADE', 'akashbinzade_7035me25@nit.edu.in', '7030642853', 'student_42_1763720487.png', '$2y$10$tcgL2nj52d4NYblmOFEE/OBqa4ZJ7FhkjAnJqyQUxKGPrD2N6JrFe', 4, 63, 1, 1, '2025', 1, '2025-11-15 01:20:39'),
(43, 'ME-05', 'AMAN DINESH SHINGARE', 'amanshingare_7019me25@nit.edu.in', '9356375511', 'student_43_1763720546.png', '$2y$10$tcgL2nj52d4NYblmOFEE/OBqa4ZJ7FhkjAnJqyQUxKGPrD2N6JrFe', 4, 63, 1, 1, '2025', 1, '2025-11-15 01:21:31'),
(322, 'ME-06', 'ANISHA SACHIN BHAGAT', 'anishabhagat_6988me25@nit.edu.in', '7218170799', 'student_1763706639_6920070f9fd52.png', '$2y$10$RzdUEah30APc5gT5uPCeIONd..klG81jg8Q7OjtZ81s.sCmxCy31G', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:00:39'),
(323, 'ME-07', 'ANUSHKA GAJANAN ANTURKAR', 'anushkaanturkar_7066me25@nit.edu.in', '9119502249', 'student_1763706684_6920073ca7097.png', '$2y$10$.rPjVLrX/ygBb8eDd2zT8.Xo0p9AsLpHodruTnN/uelS45lcar6ka', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:01:24'),
(324, 'ME-08', 'ARNAV VIJAY DANGE', 'arnavdange_7060me25@nit.edu.in', '9699825193', 'student_1763706733_6920076dac770.png', '$2y$10$6FCOYT.hzwidy1QP9tqvUelDMMzGmNShOh2uOGXPTpogHAju71KlK', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:02:13'),
(325, 'ME-09', 'ARYAN BABANAND THOOL', 'aryanthool_7098me25@nit.edu.in', '8262912684', 'student_1763706791_692007a701b9d.png', '$2y$10$euS9AkLOG0KGsbqfZXM3G.SP5nPylhyUj546izSlMhXFtZx.CYHqe', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:03:11'),
(326, 'ME-10', 'ATHARVA RAJESH MALEWAR', 'atharvamalewar_6979me25@nit.edu.in', '9404905866', 'student_1763706831_692007cfb5d56.png', '$2y$10$hTVZ4kMtDtiwyhAV8Ay5KOmtiHLGsuDJP5ul4xXydzreaFcXa86aW', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:03:51'),
(327, 'ME-11', 'BHUPESH NITIN NAVGHARE', 'bhupeshnavghare_7283me25@nit.edu.in', '8623800153', 'student_1763706870_692007f6f2180.png', '$2y$10$A/bS5TP7YuEbLKt6EfvIyO.kMMzBF2hGW.epDHsxOLNGFZb5elLW2', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:04:30'),
(328, 'ME-12', 'BHUSHAN UMESH THAWARE', 'bhushanthaware_7134me25@nit.edu.in', '8087183989', 'student_1763706967_69200857269c8.png', '$2y$10$J2Rq2WppQVue37q4FNGE9OiKWFxDZWVH0fV/zFycR9daPujvOgygW', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:06:07'),
(329, 'ME-13', 'CHAITANYA RAJESH MANOHARKAR', 'chaitanyamanoharkar_7050me25@nit.edu.in', '9503993703', 'student_1763707006_6920087e393ab.png', '$2y$10$ZsqdsQh6cSenBDY6Cv5/ZulnhkFarSC8TPWHU.LiGCsjjWox8DFzS', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:06:46'),
(330, 'ME-14', 'CHETANA MADAN PANDE', 'chetanapande_7239me25@nit.edu.in', '9370771305', 'student_1763707062_692008b677300.png', '$2y$10$LXub7pQbu5mxFPY4BLF1y.74YyvicNImX04eyb35nQwO1yPnxJLDy', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:07:42'),
(331, 'ME-15', 'DEEPAK RAJKUMAR DOSHIYA', 'deepakdoshiya_6986me25@nit.edu.in', '9730987038', 'student_1763707119_692008ef5ae32.png', '$2y$10$DvDUXzRa5PFhkJ7a/LNECO.yhH9.rvwW5mk0qUQQaPR2asO3uCv3K', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:08:39'),
(332, 'ME-16', 'DEVESH NAVIN GIRI', 'deveshgiri_7222me25@nit.edu.in', '9067643290', 'student_1763707199_6920093fa30f5.png', '$2y$10$Tjp9OksYkExFai6Vl5Ptr.MSOCn9kRfadn/hieMotaCspU5cL1yXy', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:09:59'),
(335, 'ME-18', 'DEVYANI DIGAMBAR KOLHE', 'devyanikolhe_7234me25@nit.edu.in', '9322103153', 'student_1763707475_69200a53a4bd0.png', '$2y$10$NYjZm70fLMPJuPCRg8CVsOVge80x8bzswBwCp5Sx2F84pBw725XQy', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:14:35'),
(336, 'ME-19', 'DHANANJAY GAJANAN THAKRE', 'dhananjaythakre_7010me25@nit.edu.in', '8999269460', 'student_1763707522_69200a826bdc9.png', '$2y$10$ttl1fwMbucX0oP9jXU3oduZZz5x1wyua/2CNyfKT/DUgIXZ380LlS', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:15:22'),
(337, 'ME-20', 'DIKSHA MORESHWAR KAWALE', 'dikshakawale_7003me25@nit.edu.in', '9699062284', 'student_1763707585_69200ac1869c6.png', '$2y$10$M1..Ca9O/Aem9epTLj4F1uxMkxx.dWu/eajxgrDXU0gwvw0dsB00C', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:16:25'),
(338, 'ME-21', 'DIVYANSH PURUSHOTTAM ALAM', 'divyanshalam_7044me25@nit.edu.in', '9270589356', 'student_1763707670_69200b16c9a1a.png', '$2y$10$PsOZas6OgCL80J/WFoV7eO87B8e3zqfY69iLARf7QXSh2AgMk2Zpa', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:17:50'),
(339, 'ME-22', 'GAURAV RAKESH ATRAHE', 'gauravatrahe_7206me25@nit.edu.in', '9370175920', 'student_1763707713_69200b413bb2c.png', '$2y$10$9xfyfKjtNQV5T3VKwlABJe/ZPJMZ6MsLQrpbeB45OMmhBHj/f.tg.', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:18:33'),
(340, 'ME-23', 'HARSH VIJAY JAMOTKAR', 'harshjamotkar_7290me25@nit.edu.in', '7218898873', 'student_1763707767_69200b77792f7.png', '$2y$10$V2Ollet.3XgGXUtnNSCMYe6Zy8Js2TNCWYLDkvkpjiKvKEIzcXJ8i', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:19:27'),
(341, 'ME-24', 'KAIF MEHMUD SHEIKH', 'kaifsheikh_7012me25@nit.edu.in', '9156233742', 'student_1763706321_69200bad02c69.png', '$2y$10$4QV/IaLKIcNOv0YNoM.mQeU8bNTCYuU1J069fKBgQh8GRksoVGqAC', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:20:21'),
(342, 'ME-25', 'KARTIK NARAYAN POINKAR', 'kartikpoinkar_7129me25@nit.edu.in', '9307522884', 'student_1763706368_69200bdcf2fdf.png', '$2y$10$DbUOcK.CjcOqJnrwCMi.1uhDekgFImQPe/z9yEgxGfPV4RbN5Qs7e', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:21:08'),
(343, 'ME-26', 'KASHISH DIVAKAR OKTE', 'kashishokte_7015me25@nit.edu.in', '9906373812', 'student_1763707915_69200c0b54263.png', '$2y$10$zFlWaWkVfKDVbAfFkJDz2.HDq1WSzsZ2QXwhTIdfm0VMgxq5NIvSO', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:21:55'),
(344, 'ME-27', 'KUNAL RAMSWARTH PRASAD', 'kunalprasad_7064me25@nit.edu.in', '8638401630', 'student_1763707959_69200c3775ba2.png', '$2y$10$AIGKRkJVqb4l8zJuj0hezev9oVN4D5o6.t3da4qx7o1SUTSKC3Nka', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:22:39'),
(345, 'ME-28', 'MALLIKA BHOJRAJ THAKUR', 'mallikathakur_6993me25@nit.edu.in', '9579629958', 'student_1763708016_69200c7002a94.png', '$2y$10$xC.5YJOWBYLcGwxHz06Q.O8cU6HQKn/8VYH6yRgkgxSRO6w4UdRO6', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:23:36'),
(346, 'ME-29', 'MANSI KAILAS KSHIRSAGAR', 'mansikshirsagar_7089me25@nit.edu.in', '8600902684', 'student_1763708058_69200c9a4c3d3.png', '$2y$10$Q92YNU36IjCr1.vpx.pNPOQT92vnR3SX4exrpm36ftX74PJkAW9Wu', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:24:18'),
(347, 'ME-30', 'MAYUR PALIK BISEN', 'mayurbisen_7281me25@nit.edu.in', '9307397039', 'student_1763708130_69200ce2b633e.png', '$2y$10$kJF3dFz2KMyLzcnc9H3GLewFLoln2fhPgGBqnHqcE0bQ3593iibWG', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:25:30'),
(348, 'ME-31', 'NIDHI BHUPESH PETHE', 'nidhipethe_7233me25@nit.edu.in', '7083965584', 'student_1763708191_69200d1fddf99.png', '$2y$10$UuE0ClpYuFgeIeFNh.8/ceds1Fjt2IpQZMhiS93FYArUGte5Bf8nC', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:26:31'),
(349, 'ME-32', 'NIKITA NAMDEV RATHOD', 'nikitarathod_7031me25@nit.edu.in', '8793894187', 'student_1763708233_69200d493a0f7.png', '$2y$10$uJGa6WxyIQvi9MbzIc.XDufyiMF8Bh5igsG3uWRf.ouBC5PoQ4c8i', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:27:13'),
(350, 'ME-33', 'OM KUNDAN DESHMUKH', 'omdeshmukh_7096me25@nit.edu.in', '9579581022', 'student_1763708277_69200d75b4f24.png', '$2y$10$JUOGGov2SuhD2wV05fzxJuOWZqfyotTc3bLoNb253Uu.xgxSsCPvO', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:27:57'),
(351, 'ME-34', 'PAYAL GIRIDHAR MOHOD', 'payalmohod_7047me25@nit.edu.in', '9579776133', 'student_1763708329_69200da906542.png', '$2y$10$p1drybF2.EC2wQssmMa..u8.IqkNJxSFIaa0ECefZVJzS584O7PHq', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:28:49'),
(352, 'ME-35', 'PAYAL JAGDISH TATTE', 'payaltatte_7228me25@nit.edu.in', '8638604026', 'student_1763708377_69200dd90aff1.png', '$2y$10$rjh8ZLuAMWafObcM7zRl3u98Z2hDeFg7RcMbxA0N331iAPz3eQiUq', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:29:37'),
(353, 'ME-36', 'PIYUSH MUKESH ATHAWALE', 'piyushathawale_7285me25@nit.edu.in', '9371655365', 'student_1763708422_69200e06799c9.png', '$2y$10$pBRXqVsSQYIpPbQaB6Seo.PyjjRtUJsRQKKypaNbzYli/L/43fYjO', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:30:22'),
(354, 'ME-37', 'PIYUSH NANDKISHOR DHANDE', 'piyushdhande_7048me25@nit.edu.in', '9226305122', 'student_1763708477_69200e3d083d2.png', '$2y$10$XKCC1dLtpTCZk1t7MbajeekUDAYx6uA5bVPGRerFg9DKWl36Vr0ou', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:31:17'),
(355, 'ME-38', 'PRAJWAL PRABHAKAR JAMBHULE', 'prajwaljambhule_7124me25@nit.edu.in', '8767155420', 'student_1763708516_69200e64eeddb.png', '$2y$10$ZRJyZKVtEQRB2PsDfjw4lO86k/FI9IAB1qsm/9welV63ctSj/P/S6', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:31:56'),
(356, 'ME-39', 'PRATHMESH GAJANAN DAGWAR', 'prathmeshdagwar_7128me25@nit.edu.in', '6321852659', 'student_1763708561_69200e913faa9.png', '$2y$10$zl6Z.wIbJdoetHkWOsR4iu0vxLPHyPzmSF9KgQnSD5FKsfzUnsVwC', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:32:41'),
(357, 'ME-40', 'PRITESH PRAMOD DIGAL', 'priteshdigal_7005me25@nit.edu.in', '9309397375', 'student_1763708601_69200eb92c28b.png', '$2y$10$7i34oLLkwE6mtsJQ02bojem6n4orsA7dps7C0sadZ7cIyQvHuSwCm', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:33:21'),
(358, 'ME-17', 'DEVYANI CHHAGAN GOMKAR', 'devyanigomkar_7097me25@nit.edu.in', '7666429873', 'student_1763708770_69200f6244758.png', '$2y$10$IH6AM352YD8loh/iwWaBae/DMH02x91t.X9UtLmbx3s7.fkmMLtJW', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:36:10'),
(359, 'ME-41', 'RAGHAVENDRA VIJAY WAGHE', 'raghavendrawaghe_7001me25@nit.edu.in', '7796106631', 'student_1763708827_69200f9be36ec.png', '$2y$10$yXW.K6RyaAVjVyOce9pxJOdJ8p8n2eSX9uViEIfdr8tGD66LHVd0K', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:37:07'),
(360, 'ME-42', 'RAM TULSHIDAS PATIL', 'rampatil_7104me25@nit.edu.in', '9607481639', 'student_1763708865_69200fc16daa0.png', '$2y$10$lnY9T1kJ9t4ivMETl4j.MOdb7pcjXRDe6iEAcO.AvTHdwTGH2nCA2', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:37:45'),
(361, 'ME-43', 'RITIK RAVINDRA LADHAIKAR', 'ritikladhaikar_6991me25@nit.edu.in', '7743915898', 'student_1763708906_69200fea00680.png', '$2y$10$tCGBH3qAy8wMPi8qoheQKeVem2qoqaz7qwDDBAQuCVg3E6MW9lTDK', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:38:26'),
(362, 'ME-44', 'ROHAN SHRIKRUSHNA SHERKI', 'rohansherki_7021me25@nit.edu.in', '7666720775', 'student_1763708960_6920102024094.png', '$2y$10$jobL5wLg37PIzxOkt9TEtur8HOFNd7Q1sjqXzeb2kWUcfjTap.aNi', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:39:20'),
(363, 'ME-45', 'ROHIT DEEPAK RAMAVAT', 'rohitramavat_7116me25@nit.edu.in', '9322124539', 'student_1763709007_6920104f5c17c.png', '$2y$10$aWxRzcSYg5izlNj7hCOD5.mP1kNiqYoK.Sv3FJfax67Zh07w2wyOm', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:40:07'),
(364, 'ME-46', 'SAHIL DHANRAJ NINAWE', 'sahilninawe_7162me25@nit.edu.in', '9730243241', 'student_1763709050_6920107add5b9.png', '$2y$10$TRhV5R011GGNVdsuGbGqzejjUkzEJJnNfVk5BZ/HN0/1HEtqCSa.W', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:40:50'),
(365, 'ME-47', 'SAYALI DAMODHAR HEDAOO', 'sayalihedaoo_7269me25@nit.edu.in', '8087229477', 'student_1763709094_692010a6bbbe6.png', '$2y$10$6gg1dRMx2dXTyRMUInLBCOxHawb.oaP/I33kNpOuhkLe51qg7UvH6', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:41:34'),
(366, 'ME-48', 'SHAURYA RAJESH RAMPURKAR', 'shauryarampurkar_7026me25@nit.edu.in', '9272129198', 'student_1763709137_692010d1cfcef.png', '$2y$10$2ULs75AnCTP7y7e58mkld.6gRWQxhwBbU83pY5z3JsNRoeCCW9xuy', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:42:17'),
(367, 'ME-49', 'SHIVAM RAVI DAMODHARE', 'shivamdamodhare_7065me25@nit.edu.in', '9226393572', 'student_1763709163_692010fae9ca9.png', '$2y$10$BuBbSmdL5xp80tanw7JcKepp3YHR/.2OyEw7Gyd0ktNyTErHEBjFq', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:42:58'),
(368, 'ME-50', 'SHRAVNI SUNILRAO DIDSHE', 'shravnididshe_7268me25@nit.edu.in', '9309728764', 'student_1763709227_6920112b3d91b.png', '$2y$10$4izSw/WsDALr.E6jYxhcLuHApWJt3X16cUq4ywuSr43q42A/swO4G', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:43:47'),
(369, 'ME-51', 'SIMON RAJESH BINZADE', 'simonbinzade_6963me25@nit.edu.in', '9226076486', 'student_1763709274_6920115a769cf.png', '$2y$10$MalLqQ.07ddCGmodiEkmJOScj1VF0cs6A/zGt8VLDxvUE.pW380ge', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:44:34'),
(370, 'ME-52', 'SNEHA SUDHAKAR BAWANE', 'snehabawane_7115me25@nit.edu.in', '9588457235', 'student_1763709315_69201183bcc2b.png', '$2y$10$Bru4aarl.rh2PDMCuKg7GeC6VwoXHDGv8r5XpCQpOQN868xQ3tuaa', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:45:15'),
(371, 'ME-53', 'SUMIT NANDU BASHINE', 'sumitbashine_7070me25@nit.edu.in', '9604748776', 'student_1763709361_692011b15b5fb.png', '$2y$10$OQOtFGxUj1lq5KziPk382Of3C0JxvEg3p1SSa.vdual6Gqpdw5d6W', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:46:01'),
(372, 'ME-54', 'TANISHQ DHANANJAY SHENDE', 'tanishqshende_7135me25@nit.edu.in', '8459304196', 'student_1763709401_692011d982f63.png', '$2y$10$Z.GCP1OBGVp6fSA9hz3YUeGQ6iegozAbokVDpT1M1gR/TJGXpLfWy', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:46:41'),
(373, 'ME-55', 'TANMAY DNYANESHWAR MOTGHARE', 'tanmaymotghare_7014me25@nit.edu.in', '8080067214', 'student_1763709438_692011fe023c5.png', '$2y$10$3IqHgAnf9Ae8tkKwmAduEOICLqPXjakHB2B686zFu0gWhm09WwZx.', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:47:18'),
(374, 'ME-56', 'TEJAS SANTOSH GAJBHAR', 'tejasgajbhar_7018me25@nit.edu.in', '8208381504', 'student_1763709482_6920122a0f757.png', '$2y$10$BUitrrWCVeGMuySvXFNV0.Qz4nWKhLxexUtDIYD/MgcXOgrQW4Th6', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:48:02'),
(375, 'ME-57', 'VANSH VIJAY NAGOSE', 'vanshnagose_7025me25@nit.edu.in', '9822656310', 'student_1763709520_692012507b703.png', '$2y$10$tfQwPH3hcsMlxJMLGfdgA.l/p2Lhg1iyzAFQ1uxefjgJCsyR19h1C', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:48:40'),
(376, 'ME-58', 'VANSH VIPINKUMAR NIMR', 'vanshnimr_6977me25@nit.edu.in', '9636397725', 'student_1763709559_6920127726d0b.png', '$2y$10$ppzjhVJXUZxI2z4a63JrLewDe9roI68zu/NYpt12KdKB2FrYFqeeS', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:49:19'),
(377, 'ME-59', 'VEDANT NITIN LADSE', 'vedantladse_7282me25@nit.edu.in', '7741881420', 'student_1763709624_692012b8373ed.png', '$2y$10$nvD4CAYu84sVkpUUAqUb8OQCQ4HCdJKWQgolWT4hDUy3Kp0Zb26FG', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:50:24'),
(378, 'ME-60', 'VEDANT RAVINDRA BIGHANE', 'vedantbighane_7073me25@nit.edu.in', '8483995495', 'student_1763709672_692012e8eec29.png', '$2y$10$8uykysJZFFH4Ji1UY.wCQuW.G6G.i6Lf7qklprGxBcYans13AwDk6', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:51:12'),
(379, 'ME-61', 'VEDANTI DEEPAK RANGARI', 'vedantirangari_7059me25@nit.edu.in', '7276312104', 'student_1763709718_6920131677749.png', '$2y$10$i4coFykbN0DmNVA1o6rBjul.r8RYlENPBjShJMp35C/ZIlje7mo0K', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:51:58'),
(380, 'ME-62', 'VINAY DHARMARAJ SAWWALAKHE', 'vinaysawwalakhe_7011me25@nit.edu.in', '9284182670', 'student_1763709762_692013420e86f.png', '$2y$10$NGaTuxVDfrP9VA9AIHNKnOludpGfHZ21xWuP5eV91rKh/hrJLtgIW', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:52:42'),
(381, 'ME-63', 'YASH DEVANAND BINZADE', 'yashbinzade_6960me25@nit.edu.in', '8007573521', 'student_1763709809_6920137163234.png', '$2y$10$TxnJXklZvTnBkpYzRppoBOe2y23k6W4WPm4I56D7hMcUEd1v7xI9m', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:53:29'),
(382, 'ME-64', 'YASH DILIP NARNAWARE', 'yashnarnaware_7058me25@nit.edu.in', '8432632107', 'student_1763709849_69201399de301.png', '$2y$10$u6REyNFaskvoRn1R/0D3q.kCZpRsY9bLKQKFAQ6xSmRNSA5AOoL0a', 4, 63, 1, 1, '2025', 1, '2025-11-21 01:54:09'),
(196, 'CE-06', 'ARIYA HARIDAS SOMKUWAR', 'ariyasomkuwar_6966ce25@nit.edu.in', '8830864633', 'student_1763689821_691fc55de313e.png', '$2y$10$S1hWs1tJ0kKJmqI1iHNn9.8FPhFxcSfiDZr3ZM/nRWPtY7T2IklJi', 4, 53, 1, 1, '2025', 1, '2025-11-20 20:20:21'),
(197, 'CE-07', 'ARYAN PRAKASH RAMTEKE', 'aryanramteke_7013ce25@nit.edu.in', '7397808140', 'student_197_1763689942.png', '$2y$10$YcsMPcuPd58e1g2/aOBYfewbJMeHas7z2UEqZfP/VYu5YTXfkMhmy', 4, 53, 1, 1, '2025', 1, '2025-11-20 20:21:28'),
(203, 'CE-08', 'ARYAN PUSARAM WANJARI', 'aryanwanjari_6987ce25@nit.edu.in', '9579309429', 'student_1763699679_691febdf23870.png', '$2y$10$CGegOlGbvNigKYnEf.Xhh./LXynSULjE448A.x8.6bnflSgqsQeN6', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:04:39'),
(207, 'CE-09', 'CHAITALI GOURISHANKAR DURBUDE', 'chaitalidurbude_7057ce25@nit.edu.in', '8153989912', 'student_1763699941_691fece50f000.png', '$2y$10$Pw.K1HPCFnOWrw9dkaM9HOKuOYGEFWxruovmwdhqldTSYQf7Oc9JW', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:09:01'),
(208, 'CE-10', 'CHAITANYA SUBHASH BHAJBHUJE', 'chaitanyabhajbhuje_7288ce25@nit.edu.in', '7767854471', 'student_1763699978_691fed0ad1b57.png', '$2y$10$PkHTePvcrCzdhKP4RF04COV6lcMCSZ11nID48tkqUi9Jzj58hRdMa', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:09:38'),
(209, 'CE-11', 'DEEP RAVINDRA WAKADE', 'deepwakade_7147ce25@nit.edu.in', '9975289996', 'student_209_1763719959.png', '$2y$10$1hoNX94JzO.tdSQFg4cKKO2cqpk6WDgAhupezpHoN8OLiFlrrt6ge', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:10:21'),
(210, 'CE-12', 'DHANASHRI VISHVESHWAR SOMKUWAR', 'dhanashrisomkuwar_6974ce25@nit.edu.in', '9673927505', 'student_1763700062_691fed5e002d1.png', '$2y$10$tCkO7EUFlHSf6dn7m4hgWOyOIIGPXgTNY4sLqGGXq208lJZ7hU/8S', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:11:02'),
(211, 'CE-13', 'DISHANT RAMESH PATIL', 'dishantpatil_7083ce25@nit.edu.in', '9890285063', 'student_1763700104_691fed88b2d82.png', '$2y$10$klTqGOV5dFgjxuuUSUjZ/e/sJAWvg1aWat65Yfz3KRX5KVYYvmzsG', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:11:44'),
(212, 'CE-14', 'DIYA RAKESH POREDDIWAR', 'diyaporeddiwar_7216ce25@nit.edu.in', '8010554690', 'student_1763700147_691fedb357e4c.png', '$2y$10$nHmuizmKV4Hnao0v51lOJer4T3mE/lvQLrhCL0eOZ1P04qMgeNuBq', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:12:27'),
(213, 'CE-15', 'DIYA TEJKUMAR PUNVATKAR', 'diyapunvatkar_7272ce25@nit.edu.in', '8432402050', 'student_1763700184_691fedd8ef488.png', '$2y$10$xV.kBKqTpRvqfyP7Q2NLkecBvA1omDV66nW8jbgbP1xGoceRQkE4K', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:13:04'),
(214, 'CE-16', 'HITESH PRASHANT WAGHE', 'hiteshwaghe_6997ce25@nit.edu.in', '8767734289', 'student_1763700239_691fee0f7db29.png', '$2y$10$HL30fxLmWxzlBYRP4fXwhO/Ksu1sDO16ZkMyqkb9IbWOtMumkQPfi', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:13:59'),
(215, 'CE-17', 'KANAK AMOL GAIDHANI', 'kanakgaidhani_7008ce25@nit.edu.in', '9834528465', 'student_1763700289_691fee41f2c0d.png', '$2y$10$IDzql/AyRBuyB6ytSUdgEOOPobnfCkCna0y/BL4ChKTpVz0VJqrT.', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:14:53'),
(216, 'CE-18', 'KRUTIKA PRAVIN KELWADKAR', 'krutikakelwadkar_7220ce25@nit.edu.in', '9356766727', 'student_1763700350_691fee7e630b0.png', '$2y$10$VpSldaQ1jer.2UF0WIckzeS53lyFDdyOXrzYRRxAD9NKDhGLrCLB.', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:15:50'),
(217, 'CE-19', 'MADHAVI RAVINDRA BAWANTHADE', 'madhavibawanthade_7257ce25@nit.edu.in', '9922637581', 'student_1763700388_691feea5398da.png', '$2y$10$Du6ilFq1PFQh8Pd/Hm9CLe.KLAi0G8AOY3ZvfoirfjxgOseRtrDyC', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:16:28'),
(218, 'CE-20', 'MAHI SOMESHWAR ILAMKAR', 'mahiilamkar_6985ce25@nit.edu.in', '9850802290', 'student_1763700425_691feec90ff39.png', '$2y$10$VxgUnwKIQpvLP7yqu0LJJOHuuM9stg9egxA.Y4lSr2H.fhoq0kr7.', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:17:05'),
(219, 'CE-21', 'MANASVI AMAR DHANREL', 'manasvidhanrel_7061ce25@nit.edu.in', '9766162139', 'student_1763700464_691feef058707.png', '$2y$10$urp4BrTA7cp5.BFrV4qnc.YnMyjnpQlKw7Nfd9iadsnL0atwvfytO', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:17:44'),
(220, 'CE-22', 'MAYANK KAILAS KHORGADE', 'mayankkhorgade_7053ce25@nit.edu.in', '9766643002', 'student_1763700503_691fef17217de.png', '$2y$10$1FgDnLE1NiTwbkxf.Nwac.i0V1cKGIHMyWtlWIk.XrfBnsTCtncCS', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:18:23'),
(221, 'CE-23', 'MEET LAXMINARAYAN MANE', 'meetmane_7204ce25@nit.edu.in', '8010844253', 'student_1763700547_691fef43b5cbf.png', '$2y$10$hDZlqtjlHSkIFmjO9vgRFeCFXoSWc0igBcIjhPAT.GpXdwgTC5rIe', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:23:07'),
(222, 'CE-24', 'MOHD TAHA MOHD NADEEMUDDIN KHATIB', 'mohdtaha_7006ce25@nit.edu.in', '8275936533', 'student_1763700588_691fef6c168b4.png', '$2y$10$7ViIUjpDwSA9f63BEWtbDeU46nrlYprgZiHMIfFepRQ6t/2LkmQk.', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:23:48'),
(223, 'CE-25', 'MUSKAN PRAVIN MESHRAM', 'muskanmeshram_7043ce25@nit.edu.in', '9764885585', 'student_1763700641_691fefa16818e.png', '$2y$10$jzKmAEZgDVjy2PMLKK3AvOMT7DjWAjFxdd51oDbdDjyAo/yq3Rv4C', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:20:41'),
(224, 'CE-27', 'NIKHIL PRAMOD BRAMHANE', 'nikhilbramhane_7024ce25@nit.edu.in', '8010026623', 'student_1763700692_691fefd46bf2e.png', '$2y$10$UopqiERr2qitZrlSundQAuUnaS1rucKIkXX4oDT1EZ03HC0aNmCjK', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:21:32'),
(225, 'CE-28', 'NISHA DEVENDRA DHARMIK', 'nishadharmik_6959ce25@nit.edu.in', '9373197042', 'student_1763700742_691ff00634a35.png', '$2y$10$z6M0tcPvMucMhYpjgHDnqe0qfSrlxwZ.zUSwaiO5T3ylQ0S5YkfgC', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:22:22'),
(226, 'CE-29', 'PAYAL ANIL LENDE', 'payallende_7309ce25@nit.edu.in', '8007685773', 'student_1763700784_691ff030436c8.png', '$2y$10$9YmGBC9iwhcLi3tG85NSuuZkRIKlCEA.Yt6DgOo0FTJFDn26RfoEi', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:23:04'),
(227, 'CE-30', 'PRACHI RAJESH PATIL', 'prachipatil_7105ce25@nit.edu.in', '9325388673', 'student_1763700890_691ff09ae67ac.png', '$2y$10$PFYj5.K.BSePuZR.whf8LOnA1Dsu70EtL4mPmkwdnEkfKcwGUvxua', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:24:50'),
(228, 'CE-31', 'PRAJWAL ISHWAR BORKAR', 'prajwalborkar_7121ce25@nit.edu.in', '7057394146', 'student_1763700930_691ff0c267736.png', '$2y$10$Jc1MCUdsx6AgTUzAOgMbeOT9BsvQ61ZGS3bIvWKEXH6QWyUSgCz0G', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:25:30'),
(229, 'CE-32', 'RADHESHAM RAJENDRA RATHOD', 'radheshamrathod_6983ce25@nit.edu.in', '8329095135', 'student_1763700985_691ff0f9539fc.png', '$2y$10$u2MY4XhXzLH3mBLSjfg1yO.Jdu7XZWDAEMn3XMSfi0FZXJJfhwvje', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:26:25'),
(230, 'CE-33', 'RAGHAV MOTIDAS NAGPURE', 'raghavnagpure_7109ce25@nit.edu.in', '9403005389', 'student_1763701038_691ff12e88b2c.png', '$2y$10$nspYN8x9SqI80sxwr70cGO4pmUYZTv8OIZjAkfsGtUFnagXOb81L6', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:27:18'),
(231, 'CE-34', 'RESHMI PRAMOD NIKHADE', 'reshminikhade_6940ce25@nit.edu.in', '9172101958', 'student_1763701085_691ff15d5f9f6.png', '$2y$10$q3.cKkcIJbqwOg.ALfkPe.UVHjbwy7e9hlMNUSSYFJEwSQOmf7SIS', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:28:05'),
(232, 'CE-35', 'RUDRAKSH ROSHAN NANETKAR', 'rudrakshnanetkar_6935ce25@nit.edu.in', '9503333085', 'student_1763701135_691ff18f7bd4a.png', '$2y$10$DJRXqSs2sdJdjHuw2BrHEeCiztRBqH0wy4y93IIwrf0xfgri2Op4C', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:28:55'),
(233, 'CE-36', 'SAHIL BALU PAWAR', 'sahilpawar_7113ce25@nit.edu.in', '7538747134', 'student_1763701173_691ff1b5edbf2.png', '$2y$10$SseS2WW81Pt6FRWD1PQTTOoyRPqOk83UgYbDplHuET1aJCEFYF17i', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:29:33'),
(234, 'CE-37', 'SAKSHI SUNIL LAMBKANE', 'sakshilambkane_7086ce25@nit.edu.in', '9921381419', 'student_1763701238_691ff1f6b7131.png', '$2y$10$Md7VJhJqUCvWkLw/SsthEu.lt8KhkKJy5g5ix5Zl5OQTxDpwJ4Nea', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:30:38'),
(235, 'CE-38', 'SALONI SUDESH GAJBHIYE', 'salonigajbhiye_7004ce25@nit.edu.in', '7666532561', 'student_1763701283_691ff2231eb6e.png', '$2y$10$q/TX3DWjiEpG/h3kFV5UMuETWx2F9UUX4jBv2OG5T3ylQ0S5YkfgC', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:31:23'),
(236, 'CE-39', 'SANKET DARASING CHOUDHARI', 'sanketchoudhari_7298ce25@nit.edu.in', '8626009469', 'student_1763701325_691ff24d1e7ab.png', '$2y$10$eu7UzOXfl.FMunhTf.E.3.dsKcjvgHVUlWVnEaOvn2S2OcRIlK8k2', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:32:05'),
(237, 'CE-40', 'SHITAL RAMPAT WARTHI', 'shitalwarthi_7023ce25@nit.edu.in', '9209188194', 'student_1763701363_691ff2737fe51.png', '$2y$10$7cwjYELwzS.yqCFwcStF/eAKcqYFdu9zci2Usw89luqnakBzLp/va', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:32:43'),
(238, 'CE-41', 'SHREYA BANDUJI SONTAKKE', 'shreyasontakke_7175ce25@nit.edu.in', '9518305816', 'student_1763701406_691ff29ea9dcd.png', '$2y$10$ro4x6hXH/es9DDr8duqu2e4uryUxoMom.cZoBDhuG3WCNyoW1bGCO', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:33:26'),
(239, 'CE-42', 'SHUBHAM VILAS CHAVHAN', 'shubhamchavhan_7311ce25@nit.edu.in', '9822454682', 'student_1763701454_691ff2ce16395.png', '$2y$10$H/fDsLQV/EXOeN1lzmUKX.kj2lt53eXwe/avs7UEV6.yzbnR3MjIy', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:34:14'),
(240, 'CE-43', 'SUBODH UMESH KHANDEKAR', 'subodhkhandekar_7063ce25@nit.edu.in', '9356291762', 'student_1763701534_691ff2f6098b3.png', '$2y$10$ckD80ec09/GawWMOdGIekeDwMYx1.OlHP2UTF95ME/yz3zZ2KTIXa', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:34:54'),
(241, 'CE-44', 'SUDHANSHU NISHILESH WANDRE', 'sudhanshuwandre_7173ce25@nit.edu.in', '9527007795', 'student_1763701537_691ff3215908c.png', '$2y$10$M939Gdh0MpNDggbcghI97eixO7V4R3KlDZPN5aEDSkU4XvPzgttyi', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:35:37'),
(242, 'CE-45', 'SUMIT JAGDISH KAWALE', 'sumitkawale_7120ce25@nit.edu.in', '9322746313', 'student_1763701571_691ff343466c0.png', '$2y$10$TtjeMbeFmQAu4LrIaNWEGeR9V9PiX67BvzrhjY/Tq/QrY1t04vHoy', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:36:11'),
(243, 'CE-46', 'SUNABH NAVNEET BORKAR', 'sunabhborkar_6973ce25@nit.edu.in', '8668696295', 'student_1763701605_691ff365a53dc.png', '$2y$10$q2flAMHtUDMxOmsiUuSeNuA0ZmOMcagsIezBVdbPh9o7JDoTEJhr2', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:36:45'),
(244, 'CE-47', 'SUSHANT YOGENDRA TAMBE', 'sushanttambe_7218ce25@nit.edu.in', '9373275533', 'student_1763701644_691ff38ceaa0a.png', '$2y$10$9PC9GRpR8xSgliRb.8pkI.4yjhfTmdr379k3EBvRqv4Pd9VKNwAUa', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:37:24'),
(245, 'CE-48', 'SWAMINI PURUSHOTTAM RAUT', 'swaminiraut_7040ce25@nit.edu.in', '8208794239', 'student_1763701685_691ff3b5c132f.png', '$2y$10$5qdZZR/92lxAIlKMfen8Uur8DqCaFV3SJ.5Q7w0fcx73ESwRE11JC', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:38:05'),
(246, 'CE-53', 'TANMAY PRASHANT WALKE', 'tanmaywalke_7232ce25@nit.edu.in', '9209385689', 'student_1763701724_691ff3dc5b6ea.png', '$2y$10$Oo4Bc.TMJDR0aTQ61naubuNeIIFQdp2MrNL6hVUZZDXtGx6WHr1DC', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:38:44'),
(247, 'CE-50', 'TANMAY SURESH BHANARE', 'tanmaybhanare_7132ce25@nit.edu.in', '9930026331', 'student_1763701772_691ff40c43258.png', '$2y$10$iC6XpusOKdl7uBvwPDHn2eWfoMYrY9SHL03Q5X4KxbsmPWQ8Ujepi', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:39:32'),
(248, 'CE-51', 'TANUJA PURUSHOTTAM LOLE', 'tanujalole_7041ce25@nit.edu.in', '9579625729', 'student_1763701858_691ff462c93ce.png', '$2y$10$oNb3nbMXEXhfNzH8ya2sFOE1ILTq.73NXoyehtp5o4El4U0JUYxye', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:40:58'),
(253, 'CE-52', 'TEJAS SUNIL SONWANE', 'tejassonwane_7075ce25@nit.edu.in', '7559258315', 'student_1763701903_691ff48f52afc.png', '$2y$10$V97OPMsiLdm2pdM8e.94heRUBOFq7NOYulf2QQvIrtWgc/PdRU0tW', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:41:43'),
(250, 'CE-53', 'TRUPTI MURLIDHAR DESHBHRATAR', 'truptideshbhratar_7261ce25@nit.edu.in', '8888623074', 'student_1763701953_691ff4c17c751.png', '$2y$10$Er43WfFaAwrSNb26oGJDxu9Ib.8JP9QTkcPDYY4uPlu/a86H2cxUK', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:42:33'),
(251, 'CE-54', 'TRUSHNA DILIP SOMANKAR', 'trushnasomankar_7165ce25@nit.edu.in', '9699961308', 'student_1763702010_691ff4fa7b853.png', '$2y$10$gHjYZmwxJlCpCM/XiynLcOb8/VqB6N6tmWQmpMwKT.eT7Y.7B9wzO', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:43:30'),
(252, 'CE-55', 'TUSHAR NANDULAL THAKUR', 'tusharthakur_6931ce25@nit.edu.in', '8999917723', 'student_1763702072_691ff538e67c3.png', '$2y$10$NBZQnYSEVlO48KRr57tozOnVbjKXSFPn0KypZfSTTvY6F.4C9kUq.', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:44:32'),
(253, 'CE-56', 'UBAID JAVED SAYYED', 'ubaidsayyed_6972ce25@nit.edu.in', '7058881277', 'student_1763702170_691ff59a55553.png', '$2y$10$Ls5lfA9dJDbd0DX7Yy2IWe32WDp7XEMP8WSwJUXdbi5UmV/Sg2FNq', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:46:10'),
(254, 'CE-57', 'UNNATI NITIN KASAR', 'unnatikasar_7017ce25@nit.edu.in', '9322809535', 'student_1763702225_691ff5d125d24.png', '$2y$10$Y1hzcnjK8U6btU6YMUv.8.IAbPc7CgNDU9jO12m2R5RW58/LN/RR6', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:47:05'),
(256, 'CE-58', 'VAISHNAVI EKNATH BARSAGADE', 'vaishnavibarsagade_7030ce25@nit.edu.in', '9022082577', 'student_1763702346_691ff64a51397.png', '$2y$10$ri7HI3OCLhUKNa4vYYQWZekfeztvGa20qV25wMzmzy/ZEh5BXmolK', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:49:06'),
(257, 'CE-59', 'VEDANT PRASHANT CHAUDHARI', 'vedantchaudhari_7203ce25@nit.edu.in', '9359727851', 'student_1763702400_691ff68059d5b.png', '$2y$10$YF1844wLxamsBgEU2FT7JeCI4koSKboYJ8ZiqEuwsA5oGs2ECtH86', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:50:00'),
(258, 'CE-60', 'VEDANTI RAJU PUNATKAR', 'vedantipunatkar_6980ce25@nit.edu.in', '8607724648', 'student_1763702447_691ff6af53124.png', '$2y$10$Ot8kFo0fJLkP6tOBjwo0leo8ZlKm5w3M57LE8UmExlFrh9lSPA4Vi', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:50:47'),
(259, 'CE-61', 'VINAY RAJESH UIKEY', 'vinayuikey_7033ce25@nit.edu.in', '7822985901', 'student_1763702519_691ff6f7ab04d.png', '$2y$10$qGy4AkjFEKeex/FjMk857eRPv1hiCcA5LXMEkRHA0kV.rYm8.8S7G', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:51:59'),
(260, 'CE-62', 'VISHAKHA NARAYAN BHOYAR', 'vishakhabhoyar_7300ce25@nit.edu.in', '9511732059', 'student_1763702564_691ff72537871.png', '$2y$10$48RQjntfUZ.NHYJMPQeGCOJjAP5oVyLa8yI2EVpU45DyvjeWUmWjG', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:52:44'),
(261, 'CE-63', 'YASHIKA SUDHIR KALNAKE', 'yashikakalnake_7183ce25@nit.edu.in', '9356845334', 'student_1763702621_691ff75d38af6.png', '$2y$10$K2bdWIspHqB1uIGoumKKUuI.fm26HJFgD0BDXJMJMneG/H7RhPCxy', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:53:41'),
(262, 'CE-64', 'YUGAL DINKAR GAIDHANE', 'yugalgaidhane_7299ce25@nit.edu.in', '9561972541', 'student_1763702669_691ff78d76487.png', '$2y$10$57prWKjpYJ3nqQ8VQ.3A.e31Z5FBBB9bf6AXpTBSAxGt/uQjA0066', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:54:29'),
(263, 'CE-26', 'NACHIKET MUKESH PARAYE', 'nachiketparaye_7240ce25@nit.edu.in', '9209160386', 'student_1763702748_691ff7dcb3df.png', '$2y$10$sXF3aB282tS77R42dVNzfu90f4bkUEWt9bGn0wsPVq0z3mQREF8h.', 4, 53, 1, 1, '2025', 1, '2025-11-20 23:55:48'),
(44, 'EE-01', 'AASTHA WASUDEO WANKHADE', 'aasthawankhade_7137ee25@nit.edu.in', '7666683239', 'student_44_1763720002.png', '$2y$10$ulz9EJkmvdWFqS4cVe7D6Oq0D//OFMzVxaQ8aWZTucpbqxYwp3Jvq', 4, 59, 1, 1, '2025', 1, '2025-11-15 01:24:07'),
(45, 'EE-02', 'AISHWARYA SANJAY THAKUR', 'aishwaryathakur_6939ee25@nit.edu.in', '9699755971', 'student_45_1763720025.png', '$2y$10$XvHKi/d8CuzfFzL34x9HbOCc/GjZyxg200GGkvtDoy/6KzoRxjUra', 4, 59, 1, 1, '2025', 1, '2025-11-15 01:25:15'),
(46, 'EE-03', 'AMBAR GAGAN KURANKAR', 'ambarkurankar_6946ee25@nit.edu.in', '9404679905', 'student_46_1763720041.png', '$2y$10$HVgvSgD9C5nfRhAy5xbE4e9RzJ3VRbPXUi49ynUkPPP8WR1Ybz3Qy', 4, 59, 1, 1, '2025', 1, '2025-11-15 01:26:27'),
(50, 'EE-04', 'ANJALI BANDU YEWALE', 'anjaliyewale_7054ee25@nit.edu.in', '9850560617', 'student_50_1763720069.png', '$2y$10$W1OWqk/h1v2G8V0x2xTgZ.C.pX9cnO2.XDIkQEteSwQJLkci53EXm', 4, 59, 1, 1, '2025', 1, '2025-11-15 01:31:14'),
(51, 'EE-05', 'ANKUSH BHUWANLAL TURKAR', 'ankushturkar_7276ee25@nit.edu.in', '8623037063', 'student_51_1763720095.png', '$2y$10$L8yNVNSBWt.ZkmNkKT8zOubyNnfMvIsBY7DvBnhDARDEncKlhwnxq', 4, 59, 1, 1, '2025', 1, '2025-11-15 01:33:02'),
(52, 'EE-06', 'ARYAN HEMRAJ NANDANWAR', 'aryannandanwar_6948ee25@nit.edu.in', '9579970030', 'student_52_1763720247.png', '$2y$10$tcgL2nj52d4NYblmOFEE/OBqa4ZJ7FhkjAnJqyQUxKGPrD2N6JrFe', 4, 59, 1, 1, '2025', 1, '2025-11-15 01:34:31'),
(53, 'EE-07', 'ARYAN VASANTA SAKHARE', 'aryansakhare_7168ee25@nit.edu.in', '9021201815', 'student_53_1763720162.png', '$2y$10$ce5b/LLJ1BniXoWw0PlUyexn7nF3BL4ZVHn/9Pit6glbplj7Q5DPK', 4, 59, 1, 1, '2025', 1, '2025-11-15 01:35:41'),
(264, 'EE-08', 'AYUSHI UMESH WATH', 'ayushiwath_7244ee25@nit.edu.in', '9011022559', 'student_1763702892_691ff86cd6820.png', '$2y$10$UlPsMuGlUMDo7SSDv69gV.vCB2luZH.EX01u4wQyZooQ128WtG.Ye', 4, 59, 1, 1, '2025', 1, '2025-11-20 23:58:12'),
(265, 'EE-09', 'BHUMIKA RAVINDRA PATRIVAR', 'bhumikapatrivar_7199ee25@nit.edu.in', '7588151236', 'student_1763702949_691ff8a55078f.png', '$2y$10$iHZS54VXjn7BHj18jq5XV.owVCjTP4.4tqXgkuIDm1Zj/BI6NpFFe', 4, 59, 1, 1, '2025', 1, '2025-11-20 23:59:09'),
(267, 'EE-11', 'CHETAN SANJAY BASKAWARE', 'chetanbaskaware_7122ee25@nit.edu.in', '8329276305', 'student_1763703040_691ff900edcbd.png', '$2y$10$dFJQv4./tmiInEczPSPny.pCrStIuBZPPGisjAkL4dStAz6NTvblG', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:00:40'),
(269, 'EE-13', 'EKTA BANDU MORE', 'ektamore_7093ee25@nit.edu.in', '9325707165', 'student_1763703130_691ff95aedff7.png', '$2y$10$6F6MtrXIQtV53Juvkb4SP.vcZ3bE.ofKVkwlZnIW3F3/9J.hpFiHe', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:02:10'),
(270, 'EE-14', 'GAJANAN PIRAJI CHUNUPWAD', 'gajananchunupwad_7087ee25@nit.edu.in', '8010721848', 'student_1763703171_691ff9835e77b.png', '$2y$10$gII8yjzTE83gUVaSU13OS.UayJ5wf2ZliJoD9hbEtZ8zu6SWBByyS', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:02:51'),
(271, 'EE-15', 'GAURI HEMRAJ GADHAVE', 'gaurigadhave_7009ee25@nit.edu.in', '9309985552', 'student_1763703212_691ff9ac617ab.png', '$2y$10$3ogDPMfwDVyGhdbaj4ISM.qmqZaNZby1fr5pLBKNYzyfVT0/SIIQq', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:03:32');
INSERT INTO `students` (`id`, `roll_number`, `full_name`, `email`, `phone`, `photo`, `password`, `department_id`, `class_id`, `year`, `semester`, `admission_year`, `is_active`, `created_at`) VALUES
(272, 'EE-16', 'GULSHAN VINAYAKRAO CHAUDHARI', 'gulshanchaudhari_7027ee25@nit.edu.in', '8767532840', 'student_1763703269_691ff9e56763c.png', '$2y$10$Do0iIFgJtG8P2zMsSigWfuevq6Gx/vYf75OYHmacCkwklqHBrjoVi', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:04:29'),
(273, 'EE-17', 'GUNJAN HEMRAJ MANMODE', 'gunjanmanmode_7080ee25@nit.edu.in', '9322595982', 'student_1763703309_691ffa0d7676f.png', '$2y$10$9YwxnpRt38LRDsFAHm7Lu.UxErFODIzS2WLOntm6mKn9NTWLA52Wy', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:05:09'),
(274, 'EE-18', 'HEMANT BANDUJI BHOYAR', 'hemantbhoyar_6949ee25@nit.edu.in', '8010205693', 'student_1763703360_691ffa4047206.png', '$2y$10$3vHKzX8nn5935RMh5t5kTO09CH40gdosh9vVej3gWOxXC0DnZSAEC', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:06:00'),
(275, 'EE-19', 'HIMANI SUDAM TAKIT', 'himanitakit_6990ee25@nit.edu.in', '7666429873', 'student_1763703405_691ffa6dc4a33.png', '$2y$10$e/7gwC.Gprg6I4pnf/vENuzb8wIKEf8cfnQuV.cXH3olQ/Qs8XCsy', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:06:45'),
(276, 'EE-20', 'KHUSHAL NARENDRA NANDANWAR', 'khushalnandanwar_7237ee25@nit.edu.in', '9921384594', 'student_1763703504_691ffad06a7aa.png', '$2y$10$nxYVxb2K5wsZ7blwIM0QEOZnCJ/pwvn3MxLXz0DvaMjWiu//5DO2a', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:08:24'),
(277, 'EE-10', 'CHAITANYA AVINASH JAGTAP', 'chaitanyajagtap_7103ee25@nit.edu.in', '9011602152', 'student_1763703897_691ffc5953dcb.png', '$2y$10$Wm5mTO2JlPZomZV9ISSFouCsZ1vkGY2/dKlmVU.yymjqGpCczrd7u', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:14:57'),
(278, 'EE-12', 'DEVYANI VIJAY WAGHMARE', 'devyaniwaghmare_7227ee25@nit.edu.in', '7498873429', 'student_1763703983_691ffcafd5c45.png', '$2y$10$bW4W1CXXwAb8n1EtYEq9O.icHbrVGX/v8ijlh/gdZC2wEfiXsqPWe', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:16:23'),
(279, 'EE-21', 'KHUSHI SANJAY SAWARKAR', 'khushisawarkar_6937ee25@nit.edu.in', '8805369178', 'student_1763704049_691ffcf19b513.png', '$2y$10$aoe0s520JXjBRglFwS28Bu.mvI6iB3FZ30EK0VAhFFSKstoxqBW1C', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:17:29'),
(280, 'EE-22', 'KHUSHI SATISH NANWATKAR', 'khushinanwatkar_7212ee25@nit.edu.in', '7757073465', 'student_1763704088_691ffd182f19e.png', '$2y$10$35TY/KfxV.vnpg5G9y8DIePggbPzWr86js9n45SU1VdU79CVny5n.', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:18:08'),
(281, 'EE-23', 'KHUSHIYA TUKARAM BAGDE', 'khushiyabagde_7304ee25@nit.edu.in', '8999317878', 'student_1763705936_691ffd487f582.png', '$2y$10$usVFFYAbEx3o5xz2dH0gZ.0MF1kYXCLIeuixLZQsVxRI.X9Su/gs.', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:18:56'),
(282, 'EE-24', 'KIRTI SUBHASH KAMLE', 'kirtikamle_7289ee25@nit.edu.in', '7498879759', 'student_1763705970_691ffd6ad1999.png', '$2y$10$pTMMTvnBbkNUVx8cdoBtRuvHr8L0w8HxPrq1Xq/yvO6tBjlTRQz0W', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:19:30'),
(283, 'EE-25', 'LAWANYA NARESH RAMTEKE', 'lawanyaramteke_7258ee25@nit.edu.in', '7249455475', 'student_1763704216_691ffd983d820.png', '$2y$10$btlhVQtDdf2tVcXrOBapD.Ral3aQIJPuo8zkbhPsu06dn2JBca4Se', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:20:16'),
(284, 'EE-26', 'MANISH MANOJ PANDIT', 'manishpandit_6970ee25@nit.edu.in', '9699174787', 'student_1763704255_691ffdbf14604.png', '$2y$10$Wx/x/Lc/cWjs5zZq8LNBE.hIfLNCD614P6RBJqHk6UvQFyyH8jGu6', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:20:55'),
(285, 'EE-27', 'MANTHAN SHEKHAR TELRANDHE', 'manthantelrandhe_7287ee25@nit.edu.in', '7666672491', 'student_1763704292_691ffde450500.png', '$2y$10$1ItL7HpjC2lcshr1KhjFWeTr8UJHNuFXhSRfFNt4sTAYzW8g9dPyO', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:21:32'),
(286, 'EE-28', 'NIRALI ULHAS BORKAR', 'niraliborkar_7126ee25@nit.edu.in', '7498255674', 'student_1763704339_691ffe1369493.png', '$2y$10$e4ChZKck16zQbL/u04Pp2eLzz2f4AWDnFxjG09JKowgbyXpB5Zfri', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:22:19'),
(287, 'EE-29', 'PRACHI ABHAY BHOYAR', 'prachibhoyar_7160ee25@nit.edu.in', '8010998697', 'student_1763704375_691ffe3791ab4.png', '$2y$10$40E2SZpGD4unec/GuirpJeBeuo/vCtVyModYyBTJM.SLRQ9wYj.MS', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:22:55'),
(288, 'EE-30', 'PRADNYA NIRAJ RAUT', 'pradnyaraut_7131ee25@nit.edu.in', '7447704249', 'student_1763704420_691ffe646eaba.png', '$2y$10$HYhEG7nkUU4LKob2Cd1tYuprfMu59WfxBe4LLNsCgIawhN0pSwzES', 4, 59, 1, 2, '2025', 1, '2025-11-21 00:23:40'),
(289, 'EE-31', 'PRAGATI RADHESHYAMJI DHARPURE', 'pragatidharpure_7055ee25@nit.edu.in', '9370879204', 'student_1763704461_691ffe8d39b80.png', '$2y$10$rNJ6gflokfybBG3DfrwSIOt0FXp/R/ZcIE0UwDeqe7aF2TWnmKnRq', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:24:21'),
(290, 'EE-32', 'PRAJWAL LAXMAN KUTTARMARE', 'prajwalkuttarmare_7039ee25@nit.edu.in', '7776894590', 'student_1763704507_691ffebb3da57.png', '$2y$10$HJORR0N.xz/UWCNkVNsdqOz.Un555Q7pvVM2LMEk.EHnlzjOyK4cu', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:25:07'),
(291, 'EE-33', 'PRASHIKA PRAKASH GATHE', 'prashikagathe_7231ee25@nit.edu.in', '8265065351', 'student_1763704554_691ffeea75f07.png', '$2y$10$KBLCTvsF4s6Ed4LlrEXoveDLcTKC9HN.aNqQEoP11zVNePsSg2Ixe', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:25:54'),
(292, 'EE-34', 'PRATIK LALIT KADREL', 'pratikkadrel_6975ee25@nit.edu.in', '7721856928', 'student_1763704602_691fff1ac80e9.png', '$2y$10$TSn/8lfVetbCoC7gtEublOdfQh3uO/Y/Z1Njs5isC2pXHz/RtXcqS', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:26:42'),
(293, 'EE-35', 'PREM DHARMESHWAR KIRDE', 'premkirde_7251ee25@nit.edu.in', '8999709074', 'student_1763704639_691fff3f04a3d.png', '$2y$10$MkXTcQFT3u6BMcP6RKlGwOezxePcdbb8gIFTMK3kG9/sjzzs6U6wm', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:27:19'),
(294, 'EE-36', 'PRERNA SANJAY BAVISKAR', 'prernabaviskar_7159ee25@nit.edu.in', '7020671947', 'student_1763704690_691fff72dd161.png', '$2y$10$gHAdI.hrI/fKiTcJU04lhe5VVWGrmbZ.k0xVKjfdjpBB7iF2Ia14a', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:28:10'),
(295, 'EE-37', 'PURVA GAJANAN GORDE', 'purvagorde_7171ee25@nit.edu.in', '9422515498', 'student_1763704754_691fffb23971e.png', '$2y$10$boRfXZe7/8iWINhHoMsDpuXfRvx9nWzYZ4NqozVuLlnPvl/r3esHK', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:29:14'),
(296, 'EE-38', 'PURVA JITENDRA DEVGHARE', 'purvadevghare_7079ee25@nit.edu.in', '9545753173', 'student_1763704800_691fffe05de59.png', '$2y$10$8cknwK.Tpqos.iDYCGdaeuA7DxKuKd2DVK307tZhFi.YBfKLJs5C2', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:30:00'),
(297, 'EE-39', 'RIYA SAHENDRA SURYAWANSHI', 'riyasuryawanshi_7260ee25@nit.edu.in', '8554897885', 'student_1763704843_6920000b8a342.png', '$2y$10$QvocNksBgGJDi0qCjv1GEupga3xoRSR.v303gYUe9SsDT.SqV9H2y', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:30:43'),
(298, 'EE-40', 'ROHIT VIKAS BHUTE', 'rohitbhute_6976ee25@nit.edu.in', '8767696452', 'student_1763704913_69200051502f0.png', '$2y$10$6Bjs3F9hgQV0EVXPmhpz2.XxSrXgsjMRgJJPmua5ynJ/7cKWEkv.K', 4, 56, 1, 1, '2025', 1, '2025-11-21 00:31:53'),
(299, 'EE-59', 'RUTUJA SHILWANT PATIL', 'rutujapatil_7167ee25@nit.edu.in', '8208259570', 'student_1763705590_69200242a19aa.png', '$2y$10$J3bYNd0HZTyMprb.KKLKjeR3L3HanD437uUVbcwq3FWb3A5kwtkg6', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:40:10'),
(300, 'EE-42', 'SAMIKSHA GAJANAN BHATE', 'samikshabhate_6959ee25@nit.edu.in', '9373777584', 'student_1763705475_69200283cde95.png', '$2y$10$EIg8PnE83pIxUVT92rWjI.oShGOvG1qaQ4m4LKjhVmXxZ9txNqGnC', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:41:15'),
(301, 'EE-43', 'SAMYAK ANIL LOHAKARE', 'samyaklohakare_7221ee25@nit.edu.in', '7558580696', 'student_1763705526_692002b6994ab.png', '$2y$10$9PQvt7pAHJgbIN3u8ko49.o7fqLTSIdHD1J5.IQ4omtS0ebh5gPVy', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:42:06'),
(302, 'EE-44', 'SANIKA LAXMAN KANTODE', 'sanikakantode_7038ee25@nit.edu.in', '9421927342', 'student_1763705576_692002e85319d.png', '$2y$10$vVjjhow.Q18Y32RXecWatO0WorzitCY8fhaPgJn5yqpx8MhBZFzty', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:42:56'),
(303, 'EE-45', 'SANIYA RAMRAO JAMBHULKAR', 'saniyajambhulkar_7277ee25@nit.edu.in', '7875951626', 'student_1763705611_6920030bc2593.png', '$2y$10$IINwLeWgCy/lm/O7iiQiPuotzsA9DmhLtPyDSLO8xp3fDhdcaMEle', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:43:31'),
(304, 'EE-46', 'SANSKRUTI NITIN PAWAR', 'sanskrutipawar_7042ee25@nit.edu.in', '7666340208', 'student_1763705658_6920033a389ac.png', '$2y$10$ayLej3qlp1cpRTg999NmT.emGCfFNyBw4sbo51ICz5shDiEGKZl9W', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:44:18'),
(305, 'EE-47', 'SATYAM MANGALCHANDI GHOSH', 'satyamghosh_7246ee25@nit.edu.in', '8793163140', 'student_1763705757_6920039d7dae5.png', '$2y$10$nfnTGmFd3KovXyid8BKvyO5V3t4qItTAWvDm4lhGfj4W2z5FxXlJW', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:45:57'),
(306, 'EE-48', 'SHILPKAR SURESH RAMTEKE', 'shilpkarramteke_7280ee25@nit.edu.in', '8767009286', 'student_1763705848_692003f8aa93b.png', '$2y$10$MCW.vC3U2gB4LBKwDaCAOOWtGWSarpP/L3ZzptATAovrIDX5WXKna', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:47:28'),
(307, 'EE-49', 'SHIVANI RAVI INGALE', 'shivaniingale_7182ee25@nit.edu.in', '7020755162', 'student_1763705901_6920042dd404e.png', '$2y$10$DzTWRJZPAdDifMIE0AbKaOeF6Mo/ZYudKJYn2Y81xvsVqF0Vws.9W', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:48:21'),
(308, 'EE-50', 'SHRUTI MAHESH MESHRAM', 'shrutimeshram_7256ee25@nit.edu.in', '8698157281', 'student_1763705943_69200457e3bf9.png', '$2y$10$oFBnnypsM7wGjLoufmFEjujdqsqxOdAeEsoUcNiwGOfquQ8PQiz12', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:49:03'),
(309, 'EE-51', 'SHRUTI MUKINDA MANERAO', 'shrutimanerao_7101ee25@nit.edu.in', '9209421053', 'student_1763705984_692004807968d.png', '$2y$10$zonI3fXvJmDf0B6tGPO2IOKYF2nM7ZQkcU/MJNVIxhzpRo2N3MaBu', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:49:44'),
(310, 'EE-52', 'SHUBHAM GAJANAN KHORGADE', 'shubhamkhorgade_7208ee25@nit.edu.in', '8999976180', 'student_1763706028_692004ac286ef.png', '$2y$10$r//SAWs6Hpe6dyd9TNURTuP3z2XliBeljZfIT6usDR2iX/JSov.8G', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:50:28'),
(311, 'EE-53', 'SOHAM VINOD DHOTE', 'sohamdhote_7286ee25@nit.edu.in', '8390366168', 'student_1763706066_692004d2211d6.png', '$2y$10$GvUIMwhn8cHCcD.c21ZKXu0TwtYkB/ZA1ncBsZYUXjc4ljVpuXJ9q', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:51:06'),
(314, 'EE-54', 'SUHANI JAYCHAND PATLE', 'suhanipatle_7117ee25@nit.edu.in', '8459761379', 'student_1763706252_6920058caab07.png', '$2y$10$S1sFCVSBGPpugl1nzmOcnOqiLXU.x2E7Ssk.tTRJphtPMR468fjL6', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:54:12'),
(315, 'EE-55', 'SUHANI VIJAY CHAUDHARI', 'suhanichaudhari_7119ee25@nit.edu.in', '9890506163', 'student_1763706296_692005b881620.png', '$2y$10$yJZycFmNsZyoYVggPh4XOe3Q7/QKeSW.2VO9P8QGLzMS.raFjEXPK', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:54:56'),
(316, 'EE-56', 'SWATI KESHAVRAO BHOPE', 'swatibhope_7230ee25@nit.edu.in', '8263808582', 'student_1763706347_692005eb87a93.png', '$2y$10$.mL94z98UyntBX.BrxG0tuTMOU6LrNJv2iY6VX3fiu.cbCq.Piqmu', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:55:47'),
(317, 'EE-57', 'TANISHA WASUDEO BADODE', 'tanishabadode_7091ee25@nit.edu.in', '7498002763', 'student_1763706379_6920060b5e491.png', '$2y$10$IJVoBKVr7UA7jgrPnzS8OujIzN4NGI97tRGcbyhFGsbklA6iLQLc6', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:56:19'),
(318, 'EE-58', 'TRUPTI PURUSHOTTAM MORE', 'truptimore_7301ee25@nit.edu.in', '9637807519', 'student_1763706595_6920062faccf5.png', '$2y$10$m7BLAxDhlOt.khFt27xFK.H4mt2Y2LFr1ZNfK07HPvO/Rd1mHTu8q', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:56:55'),
(319, 'EE-59', 'VIPUL VIJAY DHANDE', 'vipuldhande_7082ee25@nit.edu.in', '8767966842', 'student_1763706458_6920065a127d3.png', '$2y$10$7z29.l.GMNR8/BnB0kVLguBKSVhPQIUEGbnZuaBUiyi0k5vAKdxca', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:57:38'),
(320, 'EE-60', 'YASH RAVINDRA DAFAR', 'yashdafar_7007ee25@nit.edu.in', '9552402792', 'student_1763706492_6920067c2fd21.png', '$2y$10$K7w0JC9Ua0cpvXgCRO6jBu83602IIyi8RI.HrLQmXBv4SaRRe9/BG', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:58:12'),
(321, 'EE-61', 'YOGITA CHANDRASHEKHAR THAKUR', 'yogitathakur_7297ee25@nit.edu.in', '7558464380', 'student_1763706530_692006a210f1f.png', '$2y$10$DWx7g3OObqlrhEQhEc3iiONwz3jQVWTPTgWR7srtPjgU7beclMUsm', 4, 59, 1, 1, '2025', 1, '2025-11-21 00:58:50'),
(59, 'BCSE-01', 'ANKITA ANIL CHANDANKHEDE', 'ankitachandankhede_7157cse25@nit.edu.in', '9960989041', 'student_59_1763719374.png', '$2y$10$/S6GT/.4CmWruxHwryOreuzWp2msqDjYg1MTcTKpHkbCVEDrEB6NG', 4, 74, 1, 1, '2025', 1, '2025-11-15 01:51:08'),
(60, 'BCSE-02', 'ANUSHKA SANJAYRAO RAUT', 'anushkaraut_7088cse25@nit.edu.in', '8208962296', 'student_60_1763719399.png', '$2y$10$DA9DpwVUq0Q52ppKxv5sK.qqLyTHfEVcU.bFDbE8ArkeTQkrU2Ed.', 4, 74, 1, 1, '2025', 1, '2025-11-15 01:52:09'),
(61, 'BCSE-03', 'ARYA GUNWANT LEDANGE', 'aryaledange_7188cse25@nit.edu.in', '9284356283', 'student_61_1763719452.png', '$2y$10$3bDsI7Ork2HDhMhye4FZCO9ZhGYu2Ew/1X/Tf2Io4QTTOQ/B6vbl2', 4, 74, 1, 1, '2025', 1, '2025-11-15 01:52:59'),
(62, 'BCSE-04', 'ARYA JOGENDRA BINZADE', 'aryabinzade_7028cse25@nit.edu.in', '8378912413', 'student_62_1763719474.png', '$2y$10$Hra6Tm7GSHXDkCpNR6RbRuEIX7k/4thGRiB.VQs3Gs5JYG2gtFdK.', 4, 74, 1, 1, '2025', 1, '2025-11-15 01:53:43'),
(63, 'BCSE-05', 'ARYAN HANSRAJ PATIL', 'aryanpatil_7198cse25@nit.edu.in', '8329139697', 'student_63_1763719643.png', '$2y$10$TK0KK6MZptebURyjdP7CGeycBkpY3SLoQjmHY/hrrnVsPonvGITtW', 4, 74, 1, 1, '2025', 1, '2025-11-15 01:54:32'),
(451, 'BCSE-06', 'ARYAN NANDKISHOR PAL', 'aryanpal_7152cse25@nit.edu.in', '9834659244', 'student_1763714879_6920273fd4c06.png', '$2y$10$IY1lWh1V1le4QR2PXkd61e4p.qfoKEnT8aLrbiTRXrSkpQOJYDxZW', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:17:59'),
(452, 'BCSE-07', 'ARYAN VIJAY KITUKALE', 'aryankitukale_7235cse25@nit.edu.in', '8411914251', 'student_1763714921_692027692f1df.png', '$2y$10$GkKpmx39UVTQtKEHSnoYy.672yZ4TS0BQYfBtaOcZzDSxC3Si.qLa', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:18:41'),
(453, 'BCSE-08', 'BRIJESH BABALU MADHEKAR', 'brijeshmadhekar_6958cse25@nit.edu.in', '7620001752', 'student_1763714961_69202791e4ec5.png', '$2y$10$RMa9o7Jb4xA29WStgx3Qie4BebGogsBbmOkqWEYKgYOQav.2GMR1y', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:19:21'),
(454, 'BCSE-09', 'CHAITANYA WASUDEO RAUT', 'chaitanyaraut_6943cse25@nit.edu.in', '8600193146', 'student_1763715002_692027baaf127.png', '$2y$10$gc96a50H0p5Md7qU7fNTveJ6WtkA1imvitw8kW19JK4uCEq9AVlRO', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:20:02'),
(455, 'BCSE-10', 'DHRUVESH RAMKRRUSHNA PARATE', 'dhruveshparate_7305cse25@nit.edu.in', '9130508625', 'student_1763715039_692027dfd48f8.png', '$2y$10$SjP33vYkpYhsRMQUxXmvhOm9HnQQgBqtfArbMv3sTHWGq5N827IuK', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:20:39'),
(456, 'BCSE-11', 'DINESH DILIP KALE', 'dineshkale_7077cse25@nit.edu.in', '8975669285', 'student_1763715077_692028057f500.png', '$2y$10$vibihUKVUYVey3RAqpLnnOM4hWMoDKzJpErADMZgdt62FkCFxKjKC', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:21:17'),
(457, 'BCSE-12', 'HARSHITA RAVINDRA MANUSMARE', 'harshitamanusmare_7034cse25@nit.edu.in', '9921420921', 'student_1763715168_6920286020b81.png', '$2y$10$oot5lw2v5fhYBYlXc7QYtuXHsZr8tLxh.uxxdTZhcVVgDP4Y4Rnw.', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:22:48'),
(458, 'BCSE-13', 'JANHVI PRAKASH BAMBAL', 'janhvibambal_7150cse25@nit.edu.in', '9322401157', 'student_1763715211_6920288b526cd.png', '$2y$10$3zIJPZ/VzEMy.bb8oy8D5uKRx8sD9RrUCLArXZBDTwg5llc/bcSb.', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:23:31'),
(459, 'BCSE-14', 'JAYENDRA JAYPAL FUNDE', 'jayendrafunde_7164cse25@nit.edu.in', '7030533246', 'student_1763715270_692028c64aa35.png', '$2y$10$NeSqlR.jboGCXWAGQdJmnudLgpZfSZYBg1GIJBnL2G34DjKPjG0Ty', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:24:30'),
(460, 'BCSE-15', 'KALASH VINOD KOLHATKAR', 'kalashkolhatkar_7202cse25@nit.edu.in', '8767813403', 'student_1763715308_692028ec54286.png', '$2y$10$p0v4fgojYga7msN.DXg4.ejc5re.f65WgDbt7d7SIoQpMtelS2RDC', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:25:08'),
(461, 'BCSE-16', 'KARTIK VASUDEO CHOUDHARY', 'kartikchoudhary_7020cse25@nit.edu.in', '8999328701', 'student_1763715353_6920291972fa1.png', '$2y$10$ycDU3YEMi7LD2JYOzwUfp.BeCj4L6ysJbV4xg4jrUv1zQsN1OSPom', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:25:53'),
(462, 'BCSE-17', 'LOBHANSHA KISHOR DAHAKE', 'lobhanshadahake_6942cse25@nit.edu.in', '9730721764', 'student_1763715412_692029546efe9.png', '$2y$10$dbaXJeVjOzutAVjjECfK7u.Wl6sY.WsseWYAdLCq3nln5WUXNLg.i', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:26:52'),
(467, 'BCSE-20', 'MEGHA UMESH DHARMIK', 'meghadharmik_7265cse25@nit.edu.in', '9158327881', 'student_1763715856_69202b10d044e.png', '$2y$10$PV99PZHjkPuAfUtLscOgWOIdndCdOqrgCPV6jQAmr/qwwxkcgopC2', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:34:16'),
(468, 'BCSE-21', 'MEHUL GAUTAM MESHRAM', 'mehulmeshram_7071cse25@nit.edu.in', '7066948241', 'student_1763715929_69202b59cc2e5.png', '$2y$10$ljCdLmFLSaONJW9MVEAb3evb0J4Iej8T4.lIXuzDb8RysUiJRYREO', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:35:29'),
(469, 'BCSE-22', 'MILIND BHUNESHWAR GIRI', 'milindgiri_6999cse25@nit.edu.in', '9699736085', 'student_1763715984_69202b74c7955.png', '$2y$10$ed6mdXmNPwnLfJfSLy9AbuE8um0/XR225et4EWKQM8mZW0Cog5VMG', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:36:24'),
(470, 'BCSE-23', 'MOHAMMAD FARHAN SHAKIL AHMED', 'mohammadfarhan_7049cse25@nit.edu.in', '8657860835', 'student_1763716044_69202bcc25294.png', '$2y$10$rymJyT12g3BeiwJy6ZbMAeD06HEQCkjwRt1uX.NJpq/OGEDPn7QsG', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:37:24'),
(471, 'BCSE-24', 'MRUNALI VITHOBA MARASKOLHE', 'mrunalimaraskolhe_7161cse25@nit.edu.in', '8421948262', 'student_1763716074_69202bfa8ff12.png', '$2y$10$SGpuAIMeJaE2VvxF5.Z/ceK6dQr6Wqa/a8MR57R1ZaWfsFau2dEQC', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:38:10'),
(472, 'BCSE-25', 'NAMRATA ANIL NAVALE', 'namratanavale_7225cse25@nit.edu.in', '7058963610', 'student_1763716129_69202c21e2cbd.png', '$2y$10$7HwpORz7N4.OWmvYKx/Pbu36624RkEGHDarVOiD1gJAT4jtrF4xUe', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:38:49'),
(473, 'BCSE-26', 'NANDINI GAURISHANKAR BANKAR', 'nandinibankar_7211cse25@nit.edu.in', '9156912062', 'student_1763716170_69202c4a7a921.png', '$2y$10$JO34XjAN.RQub7Fu2tmyGeJyP9IKojyPFbLaicbZU/.nSLPDlQuVe', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:39:30'),
(474, 'BCSE-27', 'NEHA GANESH JIBHAKATE', 'nehajibhakate_7191cse25@nit.edu.in', '7798285192', 'student_1763716218_69202c7a666e0.png', '$2y$10$DJ/RVPZETmTKuk2s5mokSOWy7LTCEeIHHX1hcFevvhyRztgIuwaTa', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:40:18'),
(475, 'BCSE-28', 'NIDHI ANIL RAUT', 'nidhiraut_7293cse25@nit.edu.in', '9579206328', 'student_1763716258_69202ca2213a7.png', '$2y$10$9OJMpr5AsW3HD7GOa.iQr.7QWsESvnEgRDi/ljycr8zK7YS9adwji', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:40:58'),
(476, 'BCSE-29', 'NIDHI ASHOK BASANWAR', 'nidhibasanwar_7215cse25@nit.edu.in', '8208264560', 'student_1763716293_69202cc507e66.png', '$2y$10$A79SjMqiQszkCH0.ut/ZguhWjc2xm.KCBJfXxWrBB/zhEgBLobcGa', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:41:33'),
(477, 'BCSE-30', 'NIHARIKA SHANTILAL PACHDHARE', 'niharikapachdhare_7051cse25@nit.edu.in', '7517662821', 'student_1763716331_69202ceb9e122.png', '$2y$10$kkNG8baoDpapmCUr5fLEjuy/QUmm20it1viQTxcSBC2oKn2UDSHXu', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:42:11'),
(478, 'BCSE-31', 'NIKHIL SURESH NINAWE', 'nikhilninawe_7156cse25@nit.edu.in', '8503801634', 'student_1763716393_69202d29d3fe8.png', '$2y$10$Z/FxCby.7c37G8AUEWET3uPxYibzCzdBCq1VOxMvlQ25JLauPcBoO', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:43:13'),
(479, 'BCSE-32', 'NIKHILESH PRAKASH DIKONDWAR', 'nikhileshdikondwar_6971cse25@nit.edu.in', '7496343589', 'student_1763716432_69202d50cd57c.png', '$2y$10$.0rasAeX.CquDes48RMPeOVp8Ztuq7CelBLB/93fKixmR5jkyy3wi', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:43:52'),
(480, 'BCSE-33', 'NISARG ATUL SATHAWANE', 'nisargsathawane_7264cse25@nit.edu.in', '8484974082', 'student_1763716470_69202d76a68c4.png', '$2y$10$KJ2w1RBEmHOmrVs.rdf9W.H7E.cGPYaK7mej7oBCGfoLSZ9Ty3vMq', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:44:30'),
(481, 'BCSE-34', 'PRACHI VIJAY GADGE', 'prachigadge_7174cse25@nit.edu.in', '7020572143', 'student_1763716526_69202daeab977.png', '$2y$10$X7Rr8TTUuYsb7AHn1qH3xuzkU7PX3KShebh4kVggq5KpcYxzQ0l/6', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:45:26'),
(482, 'BCSE-35', 'PRANALI SUBHASH MOON', 'pranalimoon_7076cse25@nit.edu.in', '8830240886', 'student_1763716568_69202dd852541.png', '$2y$10$g7CDPGI/fdms.x.cHVDBIumaJ4b4Dzdcq0ToJkpH87kaWUhvZWCUq', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:46:08'),
(483, 'BCSE-36', 'PRANJAL KRUSHNA FULE', 'pranjalfule_6936cse25@nit.edu.in', '9243579304', 'student_1763716632_69202e18b624c.png', '$2y$10$lGKHW31MkNhXTVZbXlds3.s8.gXool4kpF4qF/I.0Jl3ssEgGofxK', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:47:12'),
(484, 'BCSE-37', 'PRIYA BABANRAO NAGLE', 'priyanagle_6992cse25@nit.edu.in', '7498358199', 'student_1763716683_69202e4b14780.png', '$2y$10$kxrFxjoo1w213hrdHtF./O.21ru.n1p0dfrOLd24wFPoYnEGdGkb2', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:48:03'),
(485, 'BCSE-38', 'PUNAM KRUSHNAKUMAR BISEN', 'punambisen_7138cse25@nit.edu.in', '9834997178', 'student_1763716722_69202e72586ec.png', '$2y$10$c6j20b3G5sdgRE/Rn685f.bnwUsExA6N/RjfF6/DCUKTeSg64XMx.', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:48:42'),
(486, 'BCSE-39', 'RAXIT PRAVIN KADU', 'raxitkadu_6954cse25@nit.edu.in', '7449350608', 'student_1763716774_69202ea6377fa.png', '$2y$10$QBh5Cj691F2lhbxu0wysdOwhfeUzsGqedwqSZluQHT48rNPcx9AN.', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:49:34'),
(487, 'BCSE-40', 'RISHABH JAYPAL PATIL', 'rishabhpatil_7136cse25@nit.edu.in', '9322789943', 'student_1763716818_69202ed21b7d3.png', '$2y$10$0WKP7NmwaaIrb2B4rNNM2uzVpA6.SPsYy3FkjFktZ.anAaK2jHESW', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:50:18'),
(488, 'BCSE-41', 'RIYA NILESH BANKAR', 'riyabankar_7118cse25@nit.edu.in', '8999881356', 'student_1763716869_69202f051836b.png', '$2y$10$6qo3u3OQc3ZCtSZTD.6f4OwFeni54AbfzK9.Nq7O1t4XfpMwAe5vi', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:51:09'),
(489, 'BCSE-42', 'RIYA SANJAY KOLURWAR', 'riyakolurwar_7247cse25@nit.edu.in', '9665252699', 'student_1763716912_69202f30bb264.png', '$2y$10$fX4.MT7m9aedCYp7PHe5t.ermcu28CmmKaaA1glMDbhFuvljYaBeO', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:51:52'),
(474, 'BCSE-43', 'ROUNAK VINAY SINGH', 'rounaksingh_6965cse25@nit.edu.in', '9209393056', 'student_1763716977_69202f7127069.png', '$2y$10$pKsd3Vd7HCixQ57Cc/nbz.ud339r7SYCkfACzUqswk8ZNBQUEkZMO', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:52:57'),
(491, 'BCSE-44', 'RUCHIKA PRAKASH SONKUSARE', 'ruchikasonkusare_7177cse25@nit.edu.in', '9270488827', 'student_1763717030_69202fa66d785.png', '$2y$10$kltCR9rZs0DiLfI62lAHqeWNaaU/3jSow0xTVvZ2u4fiqJJWv0umS', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:53:50'),
(492, 'BCSE-45', 'SAHIL GUNVANT CHAVHAN', 'sahilchavhan_6947cse25@nit.edu.in', '8263802270', 'student_1763717081_69202fd9899c6.png', '$2y$10$jSHnJVeZ9PyEccDmstQwJe7I6Xao3GpBDXjPU71Uh9O1Pntq0D0uW', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:54:41'),
(493, 'BCSE-46', 'SAMIKSHA MAHESH FUNDE', 'samikshafunde_7125cse25@nit.edu.in', '7875273266', 'student_1763717150_6920301e17896.png', '$2y$10$PPvkeWlikUpKeCTVGARnk.L9KP1yOfSW56EZB7IDo2i8gANNPXdT.', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:55:50'),
(494, 'BCSE-47', 'SAMRAT SARNATH MOHOD', 'samratmohod_7106cse25@nit.edu.in', '9923588348', 'student_1763717204_69203054a32f9.png', '$2y$10$BVHlIaOxOS.LIWVwRXuoreWhmYKBRBGEIwl9eqLzssjSjBaOmAzKG', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:56:44'),
(495, 'BCSE-48', 'SANCHITI SANJAY NIRWAN', 'sanchitinirwan_7100cse25@nit.edu.in', '9730123552', 'student_1763717283_692030a3ef25f.png', '$2y$10$xKcjCsLukgDb1hIEDpa8muwUHSbqe6lX8dgx3FpkkzoYItLkTxqjK', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:58:03'),
(496, 'BCSE-49', 'SANI GAUTAM DESHBHRATAR', 'sanideshbhratar_7032cse25@nit.edu.in', '9637235141', 'student_1763717343_692030df99bb8.png', '$2y$10$uV4eRD7leqJXitReq9atveRohSLLCRv8y52XBuT0goKou/cW.dZ6y', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:59:03'),
(497, 'BCSE-50', 'SATYAM HANUMAN PRASAD SHUKLA', 'satyamshukla_7067cse25@nit.edu.in', '9561431595', 'student_1763717394_6920311207238.png', '$2y$10$bTz1Vd42uSwjlWP.aMWnJOjT9GQrqHCf6O0DBJ6ti1JxxUkXiW53q', 4, 74, 1, 1, '2025', 1, '2025-11-21 03:59:54'),
(498, 'BCSE-51', 'SHREYASH GAUTAM SORDE', 'shreyashsorde_7045cse25@nit.edu.in', '9356514243', 'student_1763717438_6920313e78611.png', '$2y$10$TYzmGzYPoQ733.OvPrkLousTLAONty1OYyQ25m..53Qo/LKJonLiK', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:00:38'),
(499, 'BCSE-52', 'SHRUTI SHISHUPAL WALKE', 'shrutiwalke_7255cse25@nit.edu.in', '9209579343', 'student_1763717484_6920316ce1b36.png', '$2y$10$KwyJ7/VtWc1Hu4/ucxYX/uso1yl4rPB2uhTUo1kdfakGm4x825bLO', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:01:24'),
(500, 'BCSE-53', 'SHUBHANGI ASARAM ANDHALE', 'shubhangiandhale_6964cse25@nit.edu.in', '9307432108', 'student_1763717533_6920319db4dca.png', '$2y$10$NvyO.fj57il85DpdHqn5hOxM5CqfZMmHMFbKxq0I8CI5cl2f6K8rO', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:02:13'),
(501, 'BCSE-54', 'SHUBHANGI SANJAY JAYBHAYE', 'shubhangijaybhaye_6956cse25@nit.edu.in', '9518914035', 'student_1763717575_692031c7e9ba4.png', '$2y$10$yfsWbfODrmgIFwms65RqyucSBsrVq29WAAnyyzny0.yAm..iCTN4e', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:02:55'),
(502, 'BCSE-55', 'SIDDHARTH SHRIKANT UKEY', 'siddharthukey_7284cse25@nit.edu.in', '8975714701', 'student_1763717630_692031fe804e6.png', '$2y$10$.1Dv2zNw0Rm2pDKGfpYv5eL57Uwz20LNHo57lTQBtOR0UwJt6dYo2', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:03:50'),
(503, 'BCSE-56', 'SMITA ANIL KAYANDE', 'smitakayande_6952cse25@nit.edu.in', '9272003573', 'student_1763717671_69203227c0caf.png', '$2y$10$pM8uC7NvmpDod7QdMbcxJeYJZQWYB60GbyaCt8R/KPL49msizpmFO', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:04:31'),
(504, 'BCSE-57', 'SUDHANSHU NANDKISHOR BHASAKHETRE', 'sudhanshubhasakhetre_7186cse25@nit.edu.in', '9325262638', 'student_1763717723_6920325bb55d4.png', '$2y$10$OMujVr7kpQ0a/WHkKPHB.eJx9qHrexyeDQxCP68mm3Hd8lcF9eee.', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:05:23'),
(505, 'BCSE-58', 'TANISH NITIN SONDOWLE', 'tanishsondowle_7148cse25@nit.edu.in', '9372182858', 'student_1763717769_692032874e61b.png', '$2y$10$zhXH16nM5arEpiWEz1TpEejxj1XjQQoinJRQLNFZiLvwfkCLr4iby', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:06:09'),
(506, 'BCSE-59', 'TANUSHREE GANESH PANPATTE', 'tanushreepanpatte_7253cse25@nit.edu.in', '7499886745', 'student_1763717817_692032b988919.png', '$2y$10$b42Vw/LHyqCBZtgieW6/t.WaptoH25.q8HUfvseoTCZkvvl1myW0W', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:06:57'),
(507, 'BCSE-60', 'TRISHA ANANDRAO THAMKE', 'trishathamke_7310cse25@nit.edu.in', '8698862591', 'student_1763717867_692032ebde3cd.png', '$2y$10$yrf.TrtAVadjP4HtLU2y..h2eTCpoiQeSVe79ZwInrWymLQkSxOWK', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:07:47'),
(508, 'BCSE-61', 'VAISHNAVI UMESH MUDIRAJ', 'vaishnavimudiraj_7170cse25@nit.edu.in', '9270295043', 'student_1763717749_69203315d58eb.png', '$2y$10$CDm5nQ34gPTDl/f5uosu7O.j53TF96.1Pn/LIxhXz59nlOVYcBl.q', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:08:29'),
(509, 'BCSE-62', 'VEDANT RAMESH BAGDE', 'vedantbagde_7242cse25@nit.edu.in', '7350128814', 'student_1763717952_69203340e684f.png', '$2y$10$VO.NCl170lSqUMTGfgIE3Og68aAFaUeK6NwNcINfnKLi85wTvNSP.', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:09:12'),
(510, 'BCSE-63', 'VIDHI BHUVANESHWAR BASEWAR', 'vidhibasewar_7181cse25@nit.edu.in', '9923476461', 'student_1763718006_6920337628240.png', '$2y$10$7flVL8zTKCmWqTgbijTobeY6B/WLEWeV.CBKIHQ6RL3fjD6W8VDCG', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:10:06'),
(511, 'BCSE-64', 'VIDISHA SHITAL DODKE', 'vidishadodke_7267cse25@nit.edu.in', '9552662344', 'student_1763718049_692033a19e24b.png', '$2y$10$uoBJwX95Za6F5IqHLJNg7uP6pH8NuQNNCfkjbsK.nRRq1bzt2YlZi', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:10:49'),
(512, 'BCSE-65', 'YAMINI ANIL WASADE', 'yaminiwasade_6982cse25@nit.edu.in', '9604121249', 'student_1763718093_692033cdd1d48.png', '$2y$10$R2jc.6pEJjmMJiFa7FWbBu8bUQGVLwmShtZU5rQkVHxj3u4oxOqmu', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:11:33'),
(514, 'BCSE-18', 'MANMEET KAUR NARENDRA POTHIWAL', 'manmeetkaur_7000cse25@nit.edu.in', '7498462870', 'student_1763718248_6920346809416.png', '$2y$10$/8zBLfzEFWaoW8msgHHx6.OXGWXHWvCqLA0q9bOhqlB9L2TNNHPWe', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:14:08'),
(518, 'BCSE-19', 'MANTHAN RAVINDRA ARGHODE', 'mathan@gmail.com', '9545966622', 'student_1763718600_692035c80b2e1.png', '$2y$10$T7kjM4tVYdE3lMN6hkrcbuqJvGGL0JBR1DExznACc0fIyLpsYrjS6', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:20:00'),
(513, 'BCSE-66', 'YASH KAILASH NANNAWARE', 'yashnandeshwar_7200cse25@nit.edu.in', '7447325744', 'student_1763718141_692033fd020e1.png', '$2y$10$Of0VwZv7RIfLKy30WKxpI.p9DUVRYkH.kfFlzXoK3FN/ZG5cRDdrq', 4, 74, 1, 1, '2025', 1, '2025-11-21 04:12:21'),
(383, 'ACSE-01', 'ABIR GANESH GUJAR', 'abirgujar_7245cse25@nit.edu.in', '9309539934', 'student_1763710518_692016364b239.png', '$2y$10$tcgL2nj52d4NYblmOFEE/OBqa4ZJ7FhkjAnJqyQUxKGPrD2N6JrFe', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:05:18'),
(384, 'ACSE-02', 'ADIBA AFROZ HAMID SAYYED', 'adibasayyed_7219cse25@nit.edu.in', '9595912738', 'student_1763710934_692017d6d9bb0.png', '$2y$10$TK0KK6MZptebURyjdP7CGeycBkpY3SLoQjmHY/hrrnVsPonvGITtW', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:12:14'),
(385, 'ACSE-03', 'ADITYA NIWRUTTI PATIL', 'adityapatil_7154cse25@nit.edu.in', '9322357760', 'student_1763711016_69201828919be.png', '$2y$10$ID9O3m7ALZXKFhmpWohJaeIDZnML2WUxjiIkwtXpcWaSP8vo/UVFW', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:13:36'),
(386, 'ACSE-04', 'ALIZA PRAVEEN NAIM KHAN', 'alizakhan_7217cse25@nit.edu.in', '9322057131', 'student_1763711086_6920186e6b927.png', '$2y$10$iJXRuGVAtqF29ynkOxquQOeabFEpK3ehx4b1hy0kjte7fOqOISh9W', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:14:46'),
(387, 'ACSE-05', 'ANIKET DINKAR MUSALE', 'aniketmusale_7146cse25@nit.edu.in', '9921317433', 'student_1763711146_692018aa99de4.png', '$2y$10$YfrdTPqkS/IR5aPt0W2FQeG.1U8B9iIsDHfq7LcdMNwTLFcNGLULO', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:15:46'),
(388, 'ACSE-06', 'ANJALI SAMEER KIRNAKE', 'anjalikirnake_7158cse25@nit.edu.in', '7219457097', 'student_1763711242_6920190a60fa6.png', '$2y$10$GJwwZ0bpMCV/Xj20R8n1GOPpwH6gOoSLPbclO10rbaw9oBN0u4lyW', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:17:22'),
(389, 'ACSE-07', 'ARYAN GANESH BHUTE', 'aryanbhute_7262cse25@nit.edu.in', '9272026092', 'student_1763711302_69201946eedc1.png', '$2y$10$saGT8KVplhbxuc7.j/1IuuSxDMN2d3DPFg.Ct5XS2xp3IpiGDtMTy', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:18:22'),
(390, 'ACSE-08', 'ASHWINI JITESH GAYAKWAD', 'ashwinigayakwad_7068cse25@nit.edu.in', '7758077186', 'student_1763711393_692019a1ca95f.png', '$2y$10$NfJks.5VIoRf9vhnP4rfVeiej1.A11YJT25qB/D13RD/fut7fyHyu', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:19:53'),
(391, 'ACSE-09', 'AYUSH NARESH SOMKUWAR', 'ayushsomkuwar_7112cse25@nit.edu.in', '9975072667', 'student_1763711453_692019ddf1ced.png', '$2y$10$ULKVAkj0SZxGk2T6aoz2BOXmnDD7ZelCLRe1lMqmIC4lzcrko117O', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:20:53'),
(392, 'ACSE-10', 'AYUSH SUDHAKAR CHATULE', 'ayushchatule_7149cse25@nit.edu.in', '7249589689', 'student_1763711516_69201a1c5a4ea.png', '$2y$10$AVVPK5yFy5P7WmiBH8gHqeNslAoJZcT5tkbxr/XdQhAlHpVSdEkGi', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:21:56'),
(393, 'ACSE-11', 'BHARGAV SANTOSH DESHPANDE', 'bhargavdeshpande_7022cse25@nit.edu.in', '9420902238', 'student_1763711571_69201a53bdb11.png', '$2y$10$cyBT9xIGewdFRMqG4CJCqOKk.3VgHxD0eAEHIk0jG1V27ijFhJ6/S', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:22:51'),
(394, 'ACSE-12', 'BINTIKUMAR KANHAIYA SINGH', 'binitkumarsingh_6933cse25@nit.edu.in', '9511651722', 'student_1763711625_69201a8917c4c.png', '$2y$10$nVsiOuQ4DeRs0VODaptkWuciFmubrnI.Rr7layriqqfs7DTHHg8Ba', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:23:45'),
(395, 'ACSE-13', 'CHAITANYA ALANKAR BHAISARE', 'chaitanyabhaisare_6938cse25@nit.edu.in', '8275013559', 'student_1763711677_69201abd678ba.png', '$2y$10$bYfE66jSorUMU8J8CdFXM.dERC2Dy3KYwl3hTMSWlfkjdHTFcJeSu', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:24:37'),
(396, 'ACSE-14', 'DEETI KALYANI SHRINIWAS', 'deetishriniwas_7127cse25@nit.edu.in', '7498449397', 'student_1763711750_69201b06b2568.png', '$2y$10$2zewGAfjbWTp193urbCj9eJJsEhKPIN2PDgmkN7T1k6jFoVmePFTy', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:25:50'),
(398, 'ACSE-16', 'GURPREET KAUR SWARN SINGH MINHAS', 'gurpreetkaur_7140cse25@nit.edu.in', '7391828688', 'student_1763711870_69201b7e53a1d.png', '$2y$10$44ZBVM0ee9DldNz31l1fxePCnxzx7Ct1PLyESSYK5gqVyXwembBKm', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:27:50'),
(399, 'ACSE-15', 'DIVYA RAMDASJI KALBANDE', 'divyakalbande_7179cse25@nit.edu.in', '9322029651', 'student_1763711975_69201be70185b.png', '$2y$10$HjTKo/ZwFrDq5eeFWsNBcuuMnkg8l5hD4gXND6idIlyFz7We6qpZi', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:29:35'),
(400, 'ACSE-17', 'HARSH ANIL CHAVHAN', 'harshchavhan_7046cse25@nit.edu.in', '9579255257', 'student_1763712032_69201c20c215a.png', '$2y$10$4mN3m9I8wpXBDIIvt5yCc.5pe6p.eXIfOQmBD6/eWI9zwxTr0oxYq', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:30:32'),
(401, 'ACSE-18', 'JANHAVI VINOD SAWANT', 'janhavisawant_6995cse25@nit.edu.in', '9370695870', 'student_1763712101_69201c65c7f7b.png', '$2y$10$Cj5oldNxrbEPUMgogtExNeZPmzd.gpjXA1dbKU4stPLGWOtsB2eb6', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:31:41'),
(402, 'ACSE-19', 'JANKI JAYANT PATHAK', 'jankipathak_7036cse25@nit.edu.in', '7249238153', 'student_1763712157_69201c9d38e41.png', '$2y$10$TeaRqISzDLwxezXIi5jTiOhRLYUuaNXiudq0zSsJ8vfss9p9HTI0G', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:32:37'),
(403, 'ACSE-20', 'JATAN GAJANAN JAMBHULKAR', 'jatanjambhulkar_6955cse25@nit.edu.in', '7775011793', 'student_1763712212_69201cd4dfdfc.png', '$2y$10$cOnJWdPJarZEhDs17vnxL.McbdLej1zd4h63Dn39b1p06dir3JXa2', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:33:32'),
(404, 'ACSE-21', 'KOMAL RAVINDRA SARODE', 'komalsarode_7213cse25@nit.edu.in', '9152761350', 'student_1763712286_69201d1edc723.png', '$2y$10$qIBQS/Yikiz1inMlh9Zm8.fnPnNZahDhUSFkDqkdA5RYKNDqE.ENW', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:34:46'),
(405, 'ACSE-22', 'KUNAL YOGIRAJ DESHMUKH', 'kunaldeshmukh_7092cse25@nit.edu.in', '7262050011', 'student_1763712343_69201d57103b8.png', '$2y$10$ohbMWVcfSYsESe/G8THh6upJd8xplY24PZWR6zeOpIJe8Jx9oY4ra', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:35:43'),
(406, 'ACSE-23', 'MADHURA SURESH GAJBHIYE', 'madhuragajbhiye_7223cse25@nit.edu.in', '9657874676', 'student_1763712397_69201d8d46230.png', '$2y$10$VDc.8SBFsFb2XgaaRc6ev.iyUc9D03PHdYLPMQxrngX5hV/fCJeUW', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:36:37'),
(407, 'ACSE-24', 'MAHEK VASUDEO DEKATE', 'mahekdekate_7176cse25@nit.edu.in', '9226180160', 'student_1763712444_69201dbc82d17.png', '$2y$10$yM1anTffQOWJtzNHG6f9IO7w.70YH/eSYZiQR22M9tjKH6.YOqYRe', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:37:24'),
(408, 'ACSE-25', 'MAHESH MADAN WAGH', 'maheshwagh_6951cse25@nit.edu.in', '9503919506', 'student_1763712508_69201dfc04af1.png', '$2y$10$MSS9wvk9L.8iBOf9H8dqz.3Pnv84eaN9dZtp9YZcK6iI/mc2YZWw.', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:38:28'),
(409, 'ACSE-26', 'MAISA VINOD KALMEGH', 'maisakalmegh_7169cse25@nit.edu.in', '7276129425', 'student_1763712568_69201e385fb63.png', '$2y$10$YpRI4eRywOpl3/dle5ewQeP4L6XIyA4dchXUlKbjxKqJkOa.y6Nwq', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:39:28'),
(410, 'ACSE-27', 'MANASHRI VIJAY PUND', 'manashripund_7172cse25@nit.edu.in', '9579516987', 'student_1763712611_69201e63e4cc4.png', '$2y$10$qXkQm7DstEQvqkosIDqAG.d1F8TPzuOjhEq.O6/Eb0mdLqzFOEVF.', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:40:11'),
(411, 'ACSE-28', 'MANDAR SANTOSH BANGALE', 'mandarbangale_6968cse25@nit.edu.in', '8390817925', 'student_1763712658_69201e92828c0.png', '$2y$10$eYXB9.114fqKa.B9CiWwiOui6DFhG0hujzwUfbNSMitvAJ1/cw3/.', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:40:58'),
(412, 'ACSE-29', 'MANTASHA NOOR MOBEEN KAMAL', 'manthanarghode_7252cse25@nit.edu.in', '8668610316', 'student_1763712881_69201f7179250.png', '$2y$10$WY.yP4c0Op5wp/KHhadSE.2vUVbOK98MizCD5BuizowYLSumH8iDe', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:44:41'),
(413, 'ACSE-30', 'MAYANK SHAM YADAV', 'mayankyadav_6961cse25@nit.edu.in', '9860401784', 'student_1763712934_69201fa658c56.png', '$2y$10$ngpYJPFaPgG.hvm5YQaxnujDpn1XzCA9kyg07/EMzkcULGyZ01DN6', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:45:34'),
(414, 'ACSE-31', 'NIKITA PRABHAKAR RAUT', 'nikitaraut_7259cse25@nit.edu.in', '8429830427', 'student_1763713017_69201ff9b761d.png', '$2y$10$nKtUZDjYPD1jYkBLZmljTu3DoF.gaBC/jh1dLAaBsoarylwjqkXQC', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:46:57'),
(415, 'ACSE-32', 'NILIMA NIRANJANDAS CHAURE', 'nilimachaure_7029cse25@nit.edu.in', '9545301600', 'student_1763713062_6920202675289.png', '$2y$10$rsJNDkYe2x2fb8JKksqX7.fivP0OizVYaXGaBmKUmDysimesTN64W', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:47:42'),
(416, 'ACSE-33', 'NISHIKA SANDIP JICHKAR', 'nishikajichkar_7090cse25@nit.edu.in', '9970377128', 'student_1763713110_692020567869e.png', '$2y$10$/KwGFsy6YV5U9rfLjYMGDOuOiMu0TT0PRfL3XNUfg3/hkz1bdSLU6', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:48:30'),
(417, 'ACSE-34', 'OM VISHNU PATIL', 'ompatil_7069cse25@nit.edu.in', '7020796534', 'student_1763713156_6920208457c67.png', '$2y$10$NUkmPAlaLIuzmdkmBgPtv.6aB9SII1q4UcncefxCVjx5wFQiklUcm', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:49:16'),
(418, 'ACSE-35', 'PARI VIJAY WANDHARE', 'pariwandhare_7190cse25@nit.edu.in', '9511794547', 'student_1763713197_692020ade27fa.png', '$2y$10$aYuZGClH.4O.0qY7n2CoHejRKEr5JQqq0DZrH.tcC.rRdSGtlxaBa', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:49:57'),
(419, 'ACSE-36', 'PARTH AVINASH THAKRE', 'parththakre_7002cse25@nit.edu.in', '8237890176', 'student_1763713241_692020d9dcfd9.png', '$2y$10$s5r1H2qAphx42bkpoqYCaOtIVwnFjBS0d7SelaLuIzhbU/9.U9RAa', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:50:41'),
(420, 'ACSE-37', 'PAYAL GAJANAN SALODE', 'payalsalode_7292cse25@nit.edu.in', '7709576195', 'student_1763713306_6920211a4365f.png', '$2y$10$SBdF9bFX7EiV58r1z2ifPeCv8QgkU5lj7Nf4M4gNECzX.HxPA5be2', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:51:46'),
(421, 'ACSE-38', 'PRAJAKTA SUNIL BOPULKAR', 'prajaktabopulkar_7078cse25@nit.edu.in', '7448075839', 'student_1763713353_69202149daec7.png', '$2y$10$aIet1nbZYtWK3XV38NhqkeUuCZG.Fm84EvJBIaJjbeuzmGkrkwBIu', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:52:33'),
(422, 'ACSE-39', 'PRATHAMESH VINOD DEHANKAR', 'prathameshdehankar_7312cse25@nit.edu.in', '9322549076', 'student_1763713404_6920217c0129d.png', '$2y$10$H./YyhIYk4XxdVJ8Vn1Cje5BhVTMpXUtAT7Jv0PC50I6IR.tlmwHy', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:53:24'),
(423, 'ACSE-40', 'PREM CHOPRAM ZINGARE', 'premzingare_7163cse25@nit.edu.in', '8806457120', 'student_1763713439_6920219f2cdbe.png', '$2y$10$bTxRzIwIQ5wToOYFHZQHSOEqdeog0dv6cxrMlK0rgvKyDNEux3GhW', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:53:59'),
(424, 'ACSE-41', 'PRIME LILADHAR SAMRIT', 'primesamrit_7195cse25@nit.edu.in', '9359142015', 'student_1763713474_692021c2995f9.png', '$2y$10$Tau.n7aJLJYYI.vNF4i/EOALHpf0Gxdlqmr5WuiQH6caI0YWSyCai', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:54:34'),
(425, 'ACSE-42', 'PRIYANKA BANDU NINAVE', 'priyankaninave_7123cse25@nit.edu.in', '9960854125', 'student_1763713525_692021f56ca75.png', '$2y$10$RVIHUYOh7lP4mq4gL.n0sOYpZaWadiL8GWsl25zxWgRiGzCgrnYju', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:55:25'),
(426, 'ACSE-43', 'RAJVEER CHANDRABHAN GUPTA', 'rajveergupta_7236cse25@nit.edu.in', '8806571042', 'student_1763713571_69202223de69f.png', '$2y$10$NbHZXSmRMJmEk.7oJHtwfuYrOk6I82O2D9vHzgEJGuTcXlRywJpyS', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:56:11'),
(427, 'ACSE-44', 'RUDRA RAJESH NINAWE', 'rudraninawe_7270cse25@nit.edu.in', '8010510174', 'student_1763713616_692022506c936.png', '$2y$10$j0JSYyxMXp6KTpnc6jlife3fVNI13rwtZDQgXWMxi4RLFVFY3m7Xi', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:56:56'),
(428, 'ACSE-45', 'SAIRAM SHIVAJI PALLEKONDWAD', 'sairampallekondwad_7037cse25@nit.edu.in', '9021635659', 'student_1763713655_69202277ca5c7.png', '$2y$10$Wfu0OhNgkfayao9WEM9WHuDUceBOF0NLcQGWmpP5LqCX4Ac9U/oLW', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:57:35'),
(429, 'ACSE-46', 'SAKSHI DILIP KOLHE', 'sakshikolhe_7110cse25@nit.edu.in', '9890126481', 'student_1763713697_692022a1ca6d7.png', '$2y$10$yPjeKIRv7RhRQTv7Rn9Fae1ABTfFIewQ3QcOvN2OUjmAFdut7A.um', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:58:17'),
(430, 'ACSE-47', 'SAMYAK MUNNESHWAR NAGRARE', 'samyaknagrare_7279cse25@nit.edu.in', '9561867658', 'student_1763713763_692022e3adba0.png', '$2y$10$Kxc57q4d268J/tngSBbvRuuFr1trIK.BvEHPtI7vp6UiJ9PJwFBzu', 4, 68, 1, 1, '2025', 1, '2025-11-21 02:59:23'),
(431, 'ACSE-48', 'SANSKRUTI SANJAY RATHOD', 'sanskrutirathod_6957cse25@nit.edu.in', '9699407741', 'student_1763713818_6920231a404a3.png', '$2y$10$pMK8FENbnR510cl3JuJ5EeONsgIT57IEvg/ZAHrfne0SvjZX0PlQa', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:00:18'),
(432, 'ACSE-49', 'SAYALI NANDKISHOR JUGSENIYA', 'sayalijugseniya_7207cse25@nit.edu.in', '7264062612', 'student_1763713855_6920233fba2fc.png', '$2y$10$x6Gp4.hU/QH/JvNh08zpJ.HHag2Dmq1og1cQrc7Rq99wTBrq3bYLG', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:00:55'),
(433, 'ACSE-50', 'SEJAL ROSHAN RAMTEKE', 'sejalramteke_7155cse25@nit.edu.in', '7822018764', 'student_1763713891_69202363c1a95.png', '$2y$10$qyakSaQoksjrP2M2lwLzXe7qhvG3nMLwO4AN0.v7m482XZ7/AbuJC', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:01:31'),
(434, 'ACSE-51', 'SHIVAM MANOJSINGH CHAVHAN', 'shivamchavhan_6994cse25@nit.edu.in', '8817183868', 'student_1763713935_6920238f0ef8c.png', '$2y$10$mqoAMJWE6Byo4.EWtXTx7OclTws9QWuWH7C20qnbJeHkK37ek9Jui', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:02:15'),
(435, 'ACSE-52', 'SHRADDHA HEMRAJ DONGARE', 'shraddhadongare_7308cse25@nit.edu.in', '9112604357', 'student_1763713981_692023bd27ac9.png', '$2y$10$kODqsh6rD30vYEVgJp3qp.kcFAN.3JB12mnxvD5x2BD53bGy3T8Su', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:03:01'),
(436, 'ACSE-53', 'SONALI ARVIND RAMTEKE', 'sonaliramteke_7016cse25@nit.edu.in', '9579556254', 'student_1763714024_692023e87f488.png', '$2y$10$FQaio43WUW4yBiCekhGel.OUYOGy8tOHbB94KxTy.iymrj6cCmYLW', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:03:44'),
(437, 'ACSE-54', 'TANISHKA SUNIL BORKAR', 'tanishkaborkar_7226cse25@nit.edu.in', '8275640574', 'student_1763714082_69202422f1b95.png', '$2y$10$hMUvVxkBTklqWED8miDSyeKT8diCw9EjP6cgM7aoxAVChVLECp0xe', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:04:42'),
(438, 'ACSE-55', 'TANMAY SHARAD KAUTKAR', 'tanmaykautkar_7294cse25@nit.edu.in', '7498834855', 'student_1763714131_6920245322c21.png', '$2y$10$7zi8MPDTIxu5xvMS7shxw.vDXogtWPzl66Zhdo4lPxPEvw2T30tfq', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:05:31'),
(439, 'ACSE-56', 'TANUJA DNYANESHWAR NARINGE', 'tanujanaringe_6950cse25@nit.edu.in', '9823532604', 'student_1763714185_6920248957fcb.png', '$2y$10$IRNtJUIb5WcUq5tS4Q6/4esB8t93Y3dNnA5nDM.uIvI09qMhNT0Gq', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:06:25'),
(440, 'ACSE-57', 'TASMIYA NAUSHAD PATHAN', 'tasmiyapathan_6953cse25@nit.edu.in', '7038725948', 'student_1763714226_692024b26c171.png', '$2y$10$w45x8NsreW9vjUE/1RPHHuMVtOzln3OgQu0o.MdcQOK4cDB1SjpfC', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:07:06'),
(441, 'ACSE-58', 'TEJAS DEEPAK KACHHAWAH', 'tejaskachhawah_7074cse25@nit.edu.in', '9699362131', 'student_1763714297_692024f9f26a2.png', '$2y$10$9kS6/RElMOBMiqKmRSY6h.BUs4uDdHhVq.1mg0FUM6sXGJlrePW1K', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:08:17'),
(442, 'ACSE-59', 'TEJAS SANTOSH SAHARE', 'tejassahare_6944cse25@nit.edu.in', '7028037890', 'student_1763714337_69202521f2445.png', '$2y$10$VPz0JLLVgIntK9tSPpBsYOsKBoHu5qfSxNLAiU7SXOBu.feITqA6W', 4, 66, 1, 1, '2025', 1, '2025-11-21 03:08:57'),
(443, 'ACSE-60', 'TULSI SANTOSH MESHRAM', 'tulsimeshram_6984cse25@nit.edu.in', '7264965211', 'student_1763714397_6920255d90ecc.png', '$2y$10$j58SjJLos.YRvawt6d/DU.ig6ymfubAGx7.8AKNC/U475hhTsH3Hq', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:09:57'),
(444, 'ACSE-61', 'VEDANSHREE GAJANAN SAWAI', 'vedanshreesawai_6934cse25@nit.edu.in', '8956420803', 'student_1763714442_6920258a67717.png', '$2y$10$sCfgqbJjFnWxA0uxiMSgV.1H2f5goAlKltwO1d11e3d8MpBQVHb3W', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:10:42'),
(445, 'ACSE-62', 'VEDANT SUNIL PAWAR', 'vedantpawar_7056cse25@nit.edu.in', '8459394586', 'student_1763714504_692025c85b8f9.png', '$2y$10$SitgMey/DKb/S5V3Ly0i2.eBm4dGA1B3qFllnNzzfITAwlqkopZvG', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:11:44'),
(446, 'ACSE-63', 'VIDHI PRAVIN TUPKAR', 'vidhitupkar_7266cse25@nit.edu.in', '9403349477', 'student_1763714551_692025f7510fc.png', '$2y$10$9mgeSvCbNhXrbEeKmvtVXeFIcSJXOKnjijoc7dBYYxqYsvFk9K02y', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:12:31'),
(447, 'ACSE-64', 'VISHESH SANTOSH BANGRE', 'visheshbangre_7166cse25@nit.edu.in', '9322090576', 'student_1763714625_692026417cff1.png', '$2y$10$WcIZiPgDK35PmIYt3yvbLendELao4Mh3PaYZlmCazyomxGpm6dugW', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:13:45'),
(448, 'ACSE-65', 'VISHWARI RAVINDRA CHINCHMALATPURE', 'vishwarichinchmalatpure_7296cse25@nit.edu.in', '7875336342', 'student_1763714680_69202678553a3.png', '$2y$10$xPVfwvr6s/iMsa8VVT5SPOUJ6KDjW5aOzAvxIwqQLwb08DGRkFQQS', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:14:40'),
(449, 'ACSE-66', 'YASH KUNDLIK NANDESHWAR', 'yashnannaware_7273cse25@nit.edu.in', '9579849311', 'student_1763714724_692026a496fae.png', '$2y$10$f/8Q8dct54rU.0axBbNeGeRZ9oj2chwX2G1NrTa8mzZH2ke5i.6IC', 4, 68, 1, 1, '2025', 1, '2025-11-21 03:15:24');

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `marked_by` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `student_id`, `class_id`, `subject_id`, `attendance_date`, `status`, `marked_by`, `remarks`, `marked_at`, `teacher_id`) VALUES
(543, 59, 90, NULL, '2025-11-19', 'present', 53, '', '2025-11-19 02:01:57', NULL),
(544, 60, 90, NULL, '2025-11-19', 'present', 53, '', '2025-11-19 02:01:57', NULL),
(545, 61, 90, NULL, '2025-11-19', 'present', 53, '', '2025-11-19 02:01:57', NULL),
(546, 62, 90, NULL, '2025-11-19', 'absent', 53, '', '2025-11-19 02:01:57', NULL),
(547, 63, 90, NULL, '2025-11-19', 'present', 53, '', '2025-11-19 02:01:57', NULL),
(548, 59, 90, NULL, '2025-11-18', 'present', 53, '', '2025-11-19 02:02:40', NULL),
(549, 60, 90, NULL, '2025-11-18', 'present', 53, '', '2025-11-19 02:02:40', NULL),
(550, 61, 90, NULL, '2025-11-18', 'present', 53, '', '2025-11-19 02:02:40', NULL),
(551, 62, 90, NULL, '2025-11-18', 'present', 53, '', '2025-11-19 02:02:40', NULL),
(552, 63, 90, NULL, '2025-11-18', 'absent', 53, '', '2025-11-19 02:02:40', NULL),
(553, 59, 90, NULL, '2025-11-17', 'present', 53, '', '2025-11-19 02:03:20', NULL),
(554, 60, 90, NULL, '2025-11-17', 'present', 53, '', '2025-11-19 02:03:20', NULL),
(555, 61, 90, NULL, '2025-11-17', 'present', 53, '', '2025-11-19 02:03:20', NULL),
(556, 62, 90, NULL, '2025-11-17', 'present', 53, '', '2025-11-19 02:03:20', NULL),
(557, 63, 90, NULL, '2025-11-17', 'present', 53, '', '2025-11-19 02:03:20', NULL),
(558, 34, 87, NULL, '2025-11-19', 'present', 52, '', '2025-11-19 05:10:17', NULL),
(559, 35, 87, NULL, '2025-11-19', 'present', 52, '', '2025-11-19 05:10:17', NULL),
(560, 36, 87, NULL, '2025-11-19', 'present', 52, '', '2025-11-19 05:10:17', NULL),
(561, 37, 87, NULL, '2025-11-19', 'present', 52, '', '2025-11-19 05:10:17', NULL),
(562, 38, 87, NULL, '2025-11-19', 'absent', 52, '', '2025-11-19 05:10:17', NULL),
(563, 34, 87, NULL, '2025-11-18', 'present', 52, '', '2025-11-19 05:10:28', NULL),
(564, 35, 87, NULL, '2025-11-18', 'present', 52, '', '2025-11-19 05:10:28', NULL),
(565, 36, 87, NULL, '2025-11-18', 'present', 52, '', '2025-11-19 05:10:28', NULL),
(566, 37, 87, NULL, '2025-11-18', 'present', 52, '', '2025-11-19 05:10:28', NULL),
(567, 38, 87, NULL, '2025-11-18', 'present', 52, '', '2025-11-19 05:10:28', NULL),
(568, 34, 87, NULL, '2025-11-17', 'absent', 52, '', '2025-11-19 05:10:43', NULL),
(569, 35, 87, NULL, '2025-11-17', 'absent', 52, '', '2025-11-19 05:10:43', NULL),
(570, 36, 87, NULL, '2025-11-17', 'absent', 52, '', '2025-11-19 05:10:43', NULL),
(571, 37, 87, NULL, '2025-11-17', 'absent', 52, '', '2025-11-19 05:10:43', NULL),
(572, 38, 87, NULL, '2025-11-17', 'absent', 52, '', '2025-11-19 05:10:43', NULL),
(573, 34, 87, NULL, '2025-11-16', 'present', 52, '', '2025-11-19 05:06:35', NULL),
(574, 35, 87, NULL, '2025-11-16', 'present', 52, '', '2025-11-19 05:06:35', NULL),
(575, 36, 87, NULL, '2025-11-16', 'present', 52, '', '2025-11-19 05:06:35', NULL),
(576, 37, 87, NULL, '2025-11-16', 'present', 52, '', '2025-11-19 05:06:35', NULL),
(577, 38, 87, NULL, '2025-11-16', 'present', 52, '', '2025-11-19 05:06:35', NULL),
(578, 135, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(579, 136, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(580, 137, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(581, 138, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(582, 139, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(583, 140, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(584, 141, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(585, 142, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(586, 143, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(587, 144, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(588, 145, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(589, 146, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(590, 147, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(591, 148, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(592, 149, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(593, 150, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(594, 151, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(595, 152, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(596, 153, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(597, 154, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(598, 155, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(599, 156, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(600, 157, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(601, 158, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(602, 159, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(603, 160, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(604, 161, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(605, 162, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(606, 163, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(607, 164, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(608, 165, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(609, 166, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(610, 167, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(611, 168, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(612, 169, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(613, 170, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(614, 171, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(615, 172, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(616, 173, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(617, 174, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(618, 175, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(619, 176, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(620, 177, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(621, 178, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(622, 179, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(623, 180, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(624, 181, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(625, 182, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(626, 183, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(627, 184, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(628, 185, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(629, 186, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(630, 187, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(631, 188, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(632, 189, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(633, 190, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(634, 191, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(635, 192, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(636, 193, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(637, 194, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(638, 195, 79, NULL, '2025-11-19', 'present', 51, '', '2025-11-19 17:04:17', NULL),
(639, 135, 79, NULL, '2025-11-18', 'absent', 51, '', '2025-11-19 17:04:33', NULL),
(640, 136, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(641, 137, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(642, 138, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(643, 139, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(644, 140, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(645, 141, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(646, 142, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(647, 143, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(648, 144, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(649, 145, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(650, 146, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(651, 147, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(652, 148, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(653, 149, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(654, 150, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(655, 151, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(656, 152, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(657, 153, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(658, 154, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(659, 155, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(660, 156, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(661, 157, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(662, 158, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(663, 159, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(664, 160, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(665, 161, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(666, 162, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(667, 163, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(668, 164, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(669, 165, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(670, 166, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(671, 167, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(672, 168, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(673, 169, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(674, 170, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(675, 171, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(676, 172, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(677, 173, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(678, 174, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(679, 175, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(680, 176, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(681, 177, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(682, 178, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(683, 179, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(684, 180, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(685, 181, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(686, 182, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(687, 183, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(688, 184, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(689, 185, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(690, 186, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(691, 187, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(692, 188, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(693, 189, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(694, 190, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(695, 191, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(696, 192, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(697, 193, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(698, 194, 79, NULL, '2025-11-18', 'present', 51, '', '2025-11-19 17:04:33', NULL),
(699, 195, 79, NULL, '2025-11-18', 'absent', 51, '', '2025-11-19 17:04:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `notification_date` date NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_notifications`
--

INSERT INTO `student_notifications` (`id`, `student_id`, `teacher_id`, `class_id`, `message`, `notification_date`, `is_read`, `created_at`) VALUES
(1, 149, 52, 80, 'Dear HIMANSHU RAJENDRA PATIL,\\r\\n\\r\\nWe noticed you were absent from class today. Please ensure to attend regularly and catch up on missed coursework.\\r\\n\\r\\nIf you have any valid reason for absence, please contact us.\\r\\n\\r\\nBest regards,\\r\\nYour Teacher', '2025-11-19', 0, '2025-11-19 05:26:57'),
(9, 135, 51, 79, 'mmmmm', '2025-11-20', 0, '2025-11-20 01:46:04'),
(10, 135, 51, 79, 'Dear AAVANYA VILAS KHANDAL,\\r\\n\\r\\nWe noticed you were absent from class today. Please ensure to attend regularly and catch up on missed coursework.\\r\\n\\r\\nIf you have any valid reason for absence, please contact us.\\r\\n\\r\\nBest regards,\\r\\nYour Teacher', '2025-11-20', 0, '2025-11-20 05:27:09');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `department_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_health`
--

CREATE TABLE `system_health` (
  `id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `memory_usage` float DEFAULT NULL,
  `database_size` bigint(20) DEFAULT NULL,
  `active_users` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `academic_year` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('admin','hod','teacher') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `photo`, `role`, `department_id`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin@nitcollege.edu', NULL, NULL, 'admin', NULL, 1, '2025-11-15 17:17:27'),
(15, 'hod_firstyear', '$2y$10$6yLpaDjkGNbt.GfA1oqsCOH6ppRcHYDuS.mA4lQJKWigKJmo58JEW', 'Dr. Jitendra Bhaiswar', 'jbbhaiswar@nit.edu.in', '8007673735', 'hod_15_1763297436.jpg', 'hod', 4, 1, '2025-11-14 14:37:59'),
(22, 'dhiraj', '$2y$10$Ze0r.7KC46rVhJVVN8wk6OUwoNSvORnne24edbTL59tLQZb2JgKku', 'Mr. Dhiraj Meghe', 'dpmeghe@nit.edu.in', '9923329483', NULL, 'teacher', 4, 1, '2025-11-14 15:41:41'),
(23, 'prashant', '$2y$10$0dNZ5mQ99dug1BzT5MfYmusAZ7eZoxw8qeQxqNaAdd3sQWbmHEiIy', 'Mr. Prashant Dange', 'pddange@nit.edu.in', '9881244183', NULL, 'teacher', 4, 1, '2025-11-14 15:43:09'),
(24, 'sonika', '$2y$10$WpsS2zF96r8VPZSX2hJBiuRd4dkCZnI7Iq8SFuFO4PRxO9XO6b9o.', 'Dr. (Mrs.) Sonika Kochhar', 'srkochhar@nit.edu.in', '9011856565', NULL, 'teacher', 4, 1, '2025-11-14 15:44:05'),
(25, 'mona', '$2y$10$b8c4CvKSl9T6X/CWJJgOAeWYpPMM3catfyCKUg0fbODHyVuuJKmHm', 'Mrs. Mona Dange', 'mpdange@nit.edu.in', '9850064955', NULL, 'teacher', 4, 1, '2025-11-14 15:44:51'),
(26, 'mohammad', '$2y$10$A7gHHDc7NbFPeaTpVI8yheGWuIOc08FyLTx4Rw29cEPNu6OGSKVnS', 'Dr. Mohammad Sabir', 'mmsabir@nit.edu.in', '9850671525', NULL, 'teacher', 4, 1, '2025-11-14 15:46:29'),
(27, 'ayaz', '$2y$10$YSvi.gbLlHZRo3MHTxzb8OurKmBDgF.VTRiBxEvQGxl99qZppv45.', 'Mr. Ayaz Sheikh', 'sheikhayaz@nit.edu.in', '9834804020', NULL, 'teacher', 4, 1, '2025-11-14 15:48:28'),
(28, 'rachna', '$2y$10$ahSWAy3962Irvc/Z9wZHe.FyNnwB7kWBScWgisCyZQh79dWg0OeCi', 'Mrs Rachna Daga', 'dagarachna@nit.edu.in', '8766775204', NULL, 'teacher', 4, 1, '2025-11-15 04:10:48'),
(29, 'pournima', '$2y$10$afTCC9P36Sbz9BWPgGw4gOG8OxSUY3taGMEETUoPBHm5UFC/m92AC', 'Ms. Pournima Bhuyar', 'bhuyarpournima@nit.edu.in', '8668573942', 'uploads/photos/user_29_1763383774.jpeg', 'teacher', 4, 1, '2025-11-15 04:12:17'),
(34, 'harshal', '$2y$10$89cEXEb1OnetcxtZw4XiAOxGe42EkgnkWMBtDrfWyOmSWxKOSO04a', 'Mr. Harshal Ghatole', 'ghatoleharshal@nit.edu.in', '8390601774', NULL, 'teacher', 4, 1, '2025-11-15 04:20:16'),
(35, 'samrat', '$2y$10$/tedtmlrQawAQOKojUw3GOa4bpCBwWdqqwT5JqRATVe8W76jG1hsa', 'Mr. Samrat Kavishwar', 'smkavishwar@nit.edu.in', '9834095486', NULL, 'teacher', 4, 1, '2025-11-15 04:21:37'),
(36, 'amit', '$2y$10$NK69bVfkAg6Az06jaC8JoeBfipTO7MZ7iPBhSqQJweK2MW/8Kj6ma', 'Dr. Amit Kharwade', 'amkharwade@nit.edu.in', '7972641522', NULL, 'teacher', 4, 1, '2025-11-15 04:22:44'),
(37, 'abdul', '$2y$10$s/KO8W4qRPA6H.Ep/1/cdObGm1ZRFXGJGixKRkRJqu6ZH03KV9WTW', 'Dr. Abdul Ghaffar', 'abdulghaffar@nit.edu.in', '9881047800', NULL, 'teacher', 4, 1, '2025-11-15 04:23:43'),
(38, 'ghufran', '$2y$10$ClODYHjrw4Z2KKq62AJgpuiR1Zo.ZlBOKhsrRP2y6Vj2UNrMNdYcS', 'Mr. Ghufran Ahmad Khan', 'khangurfan@nit.edu.in', '8999941317', NULL, 'teacher', 4, 1, '2025-11-15 04:26:03'),
(39, 'rohan', '$2y$10$r3ZEWCxeoUvuyh83CN3ECOwbiJUw/.BTufBMq4Vw3PBUi8XGJow3m', 'Mr. Rohan Deshmukh', 'deshmukhrohan@nit.edu.in', '9370594377', NULL, 'teacher', 4, 1, '2025-11-15 04:37:47'),
(40, 'rahul', '$2y$10$5Fs.KkxPsaNYIFUV39Qpb.S3P5gX5e98naqhnmq6jLnxs6O0PBJN2', 'Mr. Rahul Kadam', 'rrkadam@nit.edu.in', '8806309018', NULL, 'teacher', 4, 1, '2025-11-15 04:45:36'),
(51, 'meghna', '$2y$10$6WQmppVpqB5e/u0TF8.foeq2NiZB9N3LRtKed17WsFsGLt6fGbWX6', 'Dr. (Mrs.) Meghna Jumde ', 'mhjumde@nit.edu.in', '9511664867', 'uploads/photos/teacher_51_691b2fc68de3f.jpeg', 'teacher', 4, 1, '2025-11-16 09:09:17'),
(52, 'vidya', '$2y$10$zTFFA0lX.pxnBwhbu8RqKuSvKCaeexolC.g5lFQewYQpW/mfgDOai', 'Ms. Vidya Raut', 'rautvidya@nit.edu.in', '9890701053', 'uploads/teachers/teacher_1763372594_691aee324fdb4.jpeg', 'teacher', 4, 1, '2025-11-17 09:43:14'),
(53, 'hitaishi', '$2y$10$qG6fQFtWH3GKcjeuWSNwNum4GqySankNNfQeYP0iGRMeL7R2Gy9ga', 'Ms. Hitaishi Chauhan', 'chauhanhitaishi@nit.edu.in', '7821949253', 'uploads/teachers/teacher_1763373074_691af0128ffb3.jpeg', 'teacher', 4, 1, '2025-11-17 09:51:14'),
(54, 'aayushi', '$2y$10$IG.ca5zcoZmRb.H1/r.MFuUgVzRD37f.gpazw4ah.DkDxWdhqreKG', 'Ms. Aayushi Sharma', 'sharmaaayushi@nit.edu.in', '9589344599', 'uploads/teachers/teacher_1763373261_691af0cdc222e.png', 'teacher', 4, 1, '2025-11-17 09:54:21'),
(55, 'tushar', '$2y$10$yQ5JL5gMZ78HhUTG1bmao.1pwiQT2QTJBw82L6S7G7ha.oLjDICAq', 'Mr. Tushar Shelke', 'tvshelke@nit.edu.in', '9970935793', 'uploads/teachers/teacher_1763378190_691b040ea1b8a.jpeg', 'teacher', 4, 1, '2025-11-17 11:16:30'),
(57, 'divya', '$2y$10$xbV/qaZvXSDIOFN.HPg1p.38JST53/ccnpzkDfuplj5XnALAFX4G6', 'Ms. Divya Lande', 'landedivya@nit.edu.in', '8806569892', 'uploads/teachers/teacher_1763384069_691b1b05227b4.jpeg', 'teacher', 4, 1, '2025-11-17 12:54:29'),
(59, 'jitendra', '$2y$10$0JgTPGWOqXhemUnVwkKPRueN6xJ3ki5p8JWQcRbQwdNERB23BZa86', 'Dr. jitendrabhaiswar', 'jbhaiswar@nit.edu.in', '8007673734', 'uploads/teachers/teacher_1763398976_691b55405d25a.jpeg', 'teacher', 4, 1, '2025-11-17 17:02:56');

-- --------------------------------------------------------

--
-- Table structure for table `v_class_student_count`
--

CREATE TABLE `v_class_student_count` (
  `class_id` int(11) DEFAULT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `dept_name` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `teacher_name` varchar(100) DEFAULT NULL,
  `student_count` bigint(21) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v_student_details`
--

CREATE TABLE `v_student_details` (
  `id` int(11) DEFAULT NULL,
  `roll_number` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `dept_name` varchar(100) DEFAULT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `admission_year` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v_today_attendance`
--

CREATE TABLE `v_today_attendance` (
  `id` int(11) DEFAULT NULL,
  `roll_number` varchar(50) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `dept_name` varchar(100) DEFAULT NULL,
  `status` enum('present','absent','late') DEFAULT NULL,
  `marked_by` varchar(100) DEFAULT NULL,
  `marked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_idx` (`sender_id`,`sender_type`),
  ADD KEY `receiver_idx` (`receiver_id`,`receiver_type`),
  ADD KEY `created_at_idx` (`created_at`),
  ADD KEY `is_read_idx` (`is_read`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_audience` (`target_audience`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
