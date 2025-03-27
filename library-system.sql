-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2024 at 08:53 AM
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
-- Database: `library-system`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `id` int(50) NOT NULL,
  `message` varchar(5000) NOT NULL,
  `date` varchar(50) NOT NULL,
  `image` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`id`, `message`, `date`, `image`) VALUES
(1, 'Bossing!', '2024-09-23 17:04:13', '../uploaded_file/may pasok.jpg'),
(2, 'To all Student in NwSSU Sana D masarap ulam nyo mamaya! HAHAHAHA', '2024-09-23 17:05:45', NULL),
(3, 'Bossing! Musta Buhay-buhay...', '2024-09-23 17:10:31', '../uploaded_file/boss.jpg'),
(4, 'adsasdasdadad', '2024-09-23 17:15:58', NULL),
(5, 'dadsdasdawdeqeew', '2024-09-24 09:23:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(50) NOT NULL,
  `call_no` varchar(250) NOT NULL,
  `books_title` varchar(250) NOT NULL,
  `author` varchar(250) DEFAULT NULL,
  `publish_date` varchar(250) NOT NULL,
  `publisher` varchar(250) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `copies` int(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `ISBN` varchar(50) NOT NULL,
  `edition` varchar(50) NOT NULL,
  `page_number` int(100) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `content` varchar(250) NOT NULL,
  `books_summary` varchar(500) NOT NULL,
  `books_image` varchar(400) DEFAULT NULL,
  `date_acquired` varchar(50) NOT NULL,
  `catalog_date` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `call_no`, `books_title`, `author`, `publish_date`, `publisher`, `category`, `copies`, `status`, `ISBN`, `edition`, `page_number`, `subject`, `content`, `books_summary`, `books_image`, `date_acquired`, `catalog_date`) VALUES
(1, '12444664', 'The King of The Jungle', 'Mark Francis', '1998', 'Molleda Fam', 'BSIT', 0, 'not available', '1224411', 'N/A', 100, 'Programming', '', '', NULL, '', ''),
(3, '642255', 'dasdasd', 'christian', '1922', '', 'BEED', 1, 'available', '111111', '', 0, '', '', '', '../uploaded_file/memes.jpg', '2022-11-25', '2024-11-25'),
(4, '126655', 'Python', 'Mark Francis', '1998', 'Molleda Fam', 'BSIT', 1, 'available', '1332255', '', 100, 'Programming', '', '', NULL, '', ''),
(5, '642255', 'dasdasd', 'christian', '1922', '', 'BEED', 1, 'available', '111111', '', 0, '', '', '', NULL, '2022-11-25', '2024-11-25'),
(6, '33333', 'sdfsdf', 'sadad', '2024', 'mark', 'BEED', 1, 'available', '44444', '', 0, '', '', '', NULL, '', ''),
(7, '212131', 'erwr', '', ' ', '', ' ', 1, 'available', '1', '', 0, '', '', '', NULL, '2024-11-25', '2024-11-25');

-- --------------------------------------------------------

--
-- Table structure for table `pay`
--

CREATE TABLE `pay` (
  `id` int(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(250) NOT NULL,
  `patron_type` varchar(250) NOT NULL,
  `total_pay` int(50) NOT NULL,
  `payment_date` varchar(50) NOT NULL,
  `ISBN` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pay`
--

INSERT INTO `pay` (`id`, `user_id`, `user_name`, `patron_type`, `total_pay`, `payment_date`, `ISBN`) VALUES
(1, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 6, '2024-11-24 11:23:25', ''),
(2, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 9, '2024-11-24 11:23:32', ''),
(3, '21-SJ00318', 'Angelique B. Villanueva', 'student-BSIT', 3, '2024-11-24 18:36:53', ''),
(4, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 9, '2024-11-25 09:29:47', ''),
(5, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 0, '2024-11-25 09:47:00', ''),
(6, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 0, '2024-11-25 09:54:03', ''),
(7, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 2, '2024-11-25 09:54:56', ''),
(8, '21-SJ00318', 'Angelique B. Villanueva', 'student-BSIT', 3, '2024-11-25 10:05:01', ''),
(9, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 4, '2024-11-25 10:06:03', ''),
(10, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 3, '2024-11-25 11:08:53', '3322');

-- --------------------------------------------------------

--
-- Table structure for table `reserve_books`
--

CREATE TABLE `reserve_books` (
  `reserve_id` int(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `patron_type` varchar(50) NOT NULL DEFAULT '',
  `book_title` varchar(250) NOT NULL,
  `reserved_date` varchar(250) NOT NULL,
  `call_no` varchar(100) NOT NULL,
  `copies` int(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `ISBN` varchar(50) NOT NULL,
  `borrowed_date` varchar(50) NOT NULL,
  `return_sched` varchar(50) NOT NULL,
  `fine` varchar(50) NOT NULL,
  `return_date` varchar(50) NOT NULL,
  `cancel_date` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `reserve_books`
--

INSERT INTO `reserve_books` (`reserve_id`, `user_id`, `user_name`, `patron_type`, `book_title`, `reserved_date`, `call_no`, `copies`, `status`, `ISBN`, `borrowed_date`, `return_sched`, `fine`, `return_date`, `cancel_date`) VALUES
(5, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 'The King of The Jungle', '', '12444664', 1, 'returned', '1224411', '2024-11-23 06:36:23', '2024-11-21', '0', '2024-11-24 11:23:32', ''),
(6, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 'King of IT', '', '642255', 1, 'returned', '3322', '2024-11-23 06:36:33', '2024-11-22', '0', '2024-11-24 11:23:25', ''),
(7, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 'King of IT', '2024-11-24 12:52:08', '642255', 1, 'canceled', '3322', '', '', '0', '', '2024-11-24 05:52:14'),
(8, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 'King of IT', '2024-11-24 12:54:03', '642255', 1, 'canceled', '3322', '', '', '0', '', '2024-11-24 06:04:56'),
(9, '21-SJ00126', 'Modesto Elizalde', 'student-BSIT', 'The King of The Jungle', '', '12444664', 1, 'returned', '1224411', '2024-11-24 10:58:02', '2024-11-24', '0', '2024-11-24 11:31:49', ''),
(10, '21-SJ00318', 'Angelique B. Villanueva', 'student-BSIT', 'King of IT', '', '642255', 1, 'returned', '3322', '2024-11-24 10:58:11', '2024-11-24', '0', '2024-11-24 11:31:52', ''),
(11, '21-SJ00318', 'Angelique B. Villanueva', 'student-BSIT', 'The King of The Jungle', '', '12444664', 1, 'returned', '1224411', '2024-11-24 11:32:00', '2024-11-23', '0', '2024-11-24 18:36:53', ''),
(12, '21-SJ00126', 'Modesto Elizalde', 'student-BSIT', 'King of IT', '', '642255', 1, 'returned', '3322', '2024-11-24 11:32:08', '2024-11-22', '0', '2024-11-25 09:47:00', ''),
(13, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 'King of IT', '', '642255', 1, 'returned', '3322', '2024-11-25 02:49:23', '2024-11-24', '0', '2024-11-25 09:54:03', ''),
(14, '21-SJ00318', 'Angelique B. Villanueva', 'student-BSIT', 'King of IT', '', '642255', 1, 'returned', '3322', '2024-11-25 03:03:30', '2024-11-24', '0', '2024-11-25 10:05:01', ''),
(16, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 'King of IT', '2024-11-25 10:39:02', '642255', 1, 'returned', '3322', '2024-11-25 04:08:42', '2024-11-23', '3', '2024-11-25 11:08:53', ''),
(17, '21-SJ00318', 'Christian Lamoste', 'student-BSIT', 'Python', '', '126655', 1, 'returned', '1332255', '2024-11-25 03:52:46', '2024-11-25', '0', '2024-11-25 03:54:23', '');

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `id` int(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(250) NOT NULL,
  `patron_type` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `address` varchar(255) NOT NULL,
  `password` varchar(300) NOT NULL,
  `images` varchar(300) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `account_status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_info`
--

INSERT INTO `user_info` (`id`, `user_id`, `user_name`, `patron_type`, `email`, `address`, `password`, `images`, `status`, `account_status`) VALUES
(1, '21-SJ00126', 'Modesto Morado', 'student-BSIT', 'modestoelizalde1@gmail.com', 'San jorge', '$2y$10$CL5Eg.cDP/Suolh/mXup9ObBGCT6fKLo3gtlSnOlXzuD4aYWE2GJ2', 'mco.png', 'approved', 'active'),
(2, '21-SJ00318', 'Christian Lamoste', 'student-BSIT', 'christianmacorol2002@gmail.com', 'Gandara', '$2y$10$1UTg/xbjlEZNnKs3U6ikcOtvvv4BVxnoGmSVG4K5LYLVtR.eoH20C', 'file.png', 'approved', 'inactive');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `pay`
--
ALTER TABLE `pay`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reserve_books`
--
ALTER TABLE `reserve_books`
  ADD PRIMARY KEY (`reserve_id`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pay`
--
ALTER TABLE `pay`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reserve_books`
--
ALTER TABLE `reserve_books`
  MODIFY `reserve_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_info`
--
ALTER TABLE `user_info`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
