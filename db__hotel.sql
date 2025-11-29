CREATE DATABASE IF NOT EXISTS db__hotel;
USE db__hotel;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2025 at 05:18 PM
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
-- Database: `db__hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` varchar(10) NOT NULL,
  `user_id` varchar(10) DEFAULT NULL,
  `hotel_id` varchar(10) DEFAULT NULL,
  `booking_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `user_id`, `hotel_id`, `booking_date`, `status`) VALUES
('B0001', 'U0001', 'H0001', '2025-10-10 14:00:00', 'confirmed'),
('B0002', 'U0002', 'H0002', '2025-10-09 09:30:00', 'confirmed'),
('B0003', 'U0003', 'H0003', '2025-10-08 12:45:00', 'pending'),
('B0004', 'U0004', 'H0004', '2025-10-07 08:00:00', 'confirmed'),
('B0005', 'U0005', 'H0005', '2025-10-06 15:30:00', 'cancelled'),
('B0006', 'U0006', 'H0006', '2025-10-05 17:20:00', 'confirmed'),
('B0007', 'U0007', 'H0007', '2025-10-04 11:00:00', 'pending'),
('B0008', 'U0008', 'H0008', '2025-10-03 19:10:00', 'confirmed'),
('B0009', 'U0009', 'H0009', '2025-10-02 22:00:00', 'confirmed'),
('B0010', 'U0010', 'H0010', '2025-10-01 07:40:00', 'pending'),
('B0011', 'U0001', 'H0001', '2025-10-18 17:40:45', 'confirmed'),
('B0012', 'U0001', 'H0001', '2025-10-18 21:18:10', 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `booking_detail`
--

CREATE TABLE `booking_detail` (
  `detail_id` varchar(10) NOT NULL,
  `booking_id` varchar(10) DEFAULT NULL,
  `room_id` varchar(10) DEFAULT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `price_per_night` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `special_request` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_detail`
--

INSERT INTO `booking_detail` (`detail_id`, `booking_id`, `room_id`, `check_in`, `check_out`, `price_per_night`, `total_price`, `special_request`) VALUES
('D0001', 'B0001', 'R0001', '2025-10-12', '2025-10-14', 750000.00, 1500000.00, 'Near window'),
('D0002', 'B0002', 'R0003', '2025-10-15', '2025-10-17', 500000.00, 1000000.00, 'Extra bed'),
('D0003', 'B0003', 'R0005', '2025-10-20', '2025-10-22', 2500000.00, 5000000.00, 'Ocean view'),
('D0004', 'B0004', 'R0006', '2025-10-10', '2025-10-13', 600000.00, 1800000.00, NULL),
('D0005', 'B0005', 'R0007', '2025-10-05', '2025-10-06', 950000.00, 950000.00, 'Late check-out'),
('D0006', 'B0006', 'R0008', '2025-10-08', '2025-10-10', 450000.00, 900000.00, NULL),
('D0007', 'B0007', 'R0009', '2025-10-14', '2025-10-16', 1100000.00, 2200000.00, 'High floor'),
('D0008', 'B0008', 'R0010', '2025-10-18', '2025-10-19', 700000.00, 700000.00, 'Smoking room'),
('D0009', 'B0009', 'R0002', '2025-10-11', '2025-10-12', 1200000.00, 1200000.00, NULL),
('D0010', 'B0010', 'R0004', '2025-10-09', '2025-10-11', 800000.00, 1600000.00, 'Quiet area'),
('D0011', 'B0011', 'R0001', '2025-10-18', '0000-00-00', 750000.00, 3000000.00, NULL),
('D0012', 'B0012', 'R0001', '2025-10-18', '0000-00-00', 750000.00, 2250000.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hotel`
--

CREATE TABLE `hotel` (
  `hotel_id` varchar(10) NOT NULL,
  `hotel_name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `star_rating` decimal(2,1) DEFAULT NULL,
  `available_room` int(11) DEFAULT NULL,
  `add_on` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel`
--

INSERT INTO `hotel` (`hotel_id`, `hotel_name`, `address`, `phone_no`, `email`, `star_rating`, `available_room`, `add_on`, `image_url`) VALUES
('H0001', 'Hotel Nusantara', 'Jl. Merdeka No.10, Jakarta', '021888111', 'nusantara@hotel.com', 4.5, 15, 'Free Breakfast', 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=500&h=300&fit=crop'),
('H0002', 'Hotel Harmoni', 'Jl. Sudirman No.21, Bandung', '022777222', 'harmoni@hotel.com', 4.2, 20, 'Airport Pickup', 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&q=80&w=1170'),
('H0003', 'Hotel Bahari', 'Jl. Pantai Indah No.3, Bali', '036177733', 'bahari@hotel.com', 5.0, 12, 'Ocean View', 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=500&h=300&fit=crop'),
('H0004', 'Hotel Gading', 'Jl. Gading Serpong, Tangerang', '021555666', 'gading@hotel.com', 4.0, 18, 'Gym Access', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=500&h=300&fit=crop'),
('H0005', 'Hotel Sakura', 'Jl. Asia Afrika, Bandung', '022333444', 'sakura@hotel.com', 4.8, 10, 'Onsen Spa', 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&q=80&w=1170'),
('H0006', 'Hotel Mawar', 'Jl. Diponegoro No.8, Yogyakarta', '027433355', 'mawar@hotel.com', 3.9, 25, 'Free Parking', 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&q=80&w=1174'),
('H0007', 'Hotel Mentari', 'Jl. Gatot Subroto No.5, Jakarta', '021998877', 'mentari@hotel.com', 4.1, 22, 'Rooftop Pool', 'https://images.unsplash.com/photo-1455587734955-081b22074882?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&q=80&w=1170'),
('H0008', 'Hotel Kenari', 'Jl. Malioboro No.12, Yogyakarta', '027455566', 'kenari@hotel.com', 4.3, 16, 'Live Music', 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&q=80&w=1170'),
('H0009', 'Hotel Duyung', 'Jl. Sanur No.7, Bali', '036188899', 'duyung@hotel.com', 4.9, 14, 'Private Beach', 'https://plus.unsplash.com/premium_photo-1661964402307-02267d1423f5?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&q=80&w=1073'),
('H0010', 'Hotel Pelangi', 'Jl. Ahmad Yani No.20, Surabaya', '031223344', 'pelangi@hotel.com', 4.4, 17, 'Kids Zone', 'https://images.unsplash.com/photo-1540541338287-41700207dee6?w=500&h=300&fit=crop');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` varchar(10) NOT NULL,
  `booking_id` varchar(10) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_method` enum('credit_card','debit_card','cash','transfer') DEFAULT NULL,
  `status` enum('unpaid','paid','refunded') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `booking_id`, `amount`, `payment_date`, `payment_method`, `status`) VALUES
('P0001', 'B0001', 1500000.00, '2025-10-11 10:00:00', 'credit_card', 'paid'),
('P0002', 'B0002', 1000000.00, '2025-10-10 09:00:00', 'transfer', 'paid'),
('P0003', 'B0003', 5000000.00, '2025-10-19 11:00:00', 'debit_card', 'unpaid'),
('P0004', 'B0004', 1800000.00, '2025-10-09 14:00:00', 'cash', 'paid'),
('P0005', 'B0005', 950000.00, '2025-10-06 16:00:00', 'credit_card', 'refunded'),
('P0006', 'B0006', 900000.00, '2025-10-07 15:00:00', 'transfer', 'paid'),
('P0007', 'B0007', 2200000.00, '2025-10-13 18:00:00', 'debit_card', 'unpaid'),
('P0008', 'B0008', 700000.00, '2025-10-17 20:00:00', 'cash', 'paid'),
('P0009', 'B0009', 1200000.00, '2025-10-10 13:00:00', 'credit_card', 'paid'),
('P0010', 'B0010', 1600000.00, '2025-10-08 09:00:00', 'transfer', 'unpaid'),
('P0011', 'B0011', 3000000.00, '2025-10-18 17:40:45', 'cash', 'paid'),
('P0012', 'B0012', 2250000.00, '2025-10-18 21:18:10', 'cash', 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `review_id` int(11) NOT NULL,
  `user_id` varchar(5) NOT NULL,
  `hotel_id` varchar(10) NOT NULL,
  `rating` decimal(2,1) NOT NULL COMMENT 'Skala rating 1.0 sampai 5.0',
  `comment` text DEFAULT NULL,
  `review_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`review_id`, `user_id`, `hotel_id`, `rating`, `comment`, `review_date`) VALUES
(1, 'U0001', 'H0001', 4.5, 'Pelayanan sangat cepat dan kamar bersih. Sarapan lezat!', '2025-10-18 21:23:21'),
(2, 'U0002', 'H0002', 4.2, 'Lokasi strategis, dekat pusat kota. Hanya saja kamar mandi sedikit tua.', '2025-10-18 21:23:21'),
(3, 'U0003', 'H0003', 5.0, 'Pemandangan laut yang luar biasa dari vila! Pengalaman bintang 5 sejati.', '2025-10-18 21:23:21'),
(4, 'U0004', 'H0004', 3.9, 'Fasilitas gym lengkap. Sayangnya, kamar agak sempit dari yang saya bayangkan.', '2025-10-18 21:23:21'),
(5, 'U0006', 'H0006', 4.0, 'Hotel yang nyaman untuk keluarga. Tempat parkir luas dan gratis.', '2025-10-18 21:23:21'),
(6, 'U0008', 'H0008', 4.5, 'Suka sekali dengan live music di lobby-nya. Kamar Deluxe juga sangat nyaman.', '2025-10-18 21:23:21'),
(7, 'U0009', 'H0009', 5.0, 'Pantai pribadi yang indah dan tenang. Staf sangat ramah dan membantu.', '2025-10-18 21:23:21'),
(8, 'U0001', 'H0007', 4.3, 'Kolam renang rooftop-nya keren! Sempurna untuk melihat matahari terbenam.', '2025-10-18 21:23:21'),
(9, 'U0005', 'H0005', 4.8, 'Onsen Spa yang otentik dan menenangkan. Benar-benar pengalaman menginap yang mewah.', '2025-10-18 21:23:21'),
(10, 'U0010', 'H0010', 4.1, 'Kids Zone sangat membantu! Anak-anak senang, orang tua juga santai.', '2025-10-18 21:23:21');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` varchar(10) NOT NULL,
  `hotel_id` varchar(10) DEFAULT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `availability` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `hotel_id`, `room_type`, `price`, `availability`) VALUES
('R0001', 'H0001', 'Deluxe', 750000.00, 1),
('R0002', 'H0001', 'Suite', 1200000.00, 1),
('R0003', 'H0002', 'Standard', 500000.00, 1),
('R0004', 'H0002', 'Deluxe', 800000.00, 1),
('R0005', 'H0003', 'Villa', 2500000.00, 1),
('R0006', 'H0004', 'Superior', 600000.00, 1),
('R0007', 'H0005', 'Premium', 950000.00, 1),
('R0008', 'H0006', 'Standard', 450000.00, 1),
('R0009', 'H0007', 'Executive', 1100000.00, 1),
('R0010', 'H0008', 'Deluxe', 700000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` varchar(5) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`) VALUES
('U0001', 'Andi Saputra', 'andi@example.com', 'a589ffa7732ffd2f26d23953e26af5c8f6c006690b7982d5f07f671915c0b561', 'customer'),
('U0002', 'Budi Rahman', 'budi@example.com', 'e8979d2eb704c94fa2fa5044edba1c29232526eec3965ffc64308b6783f2de12', 'customer'),
('U0003', 'Citra Dewi', 'citra@example.com', '34dbbc8f279e1e724a4faa512603c56d47e9860a66173d2a0074a8b06916e796', 'customer'),
('U0004', 'Dina Anggraini', 'dina@example.com', '4e2e269ba516629c0f5abf6ff4f686730a2bd477b1ee51d6d69ee3ea344ed80a', 'customer'),
('U0005', 'Eko Santoso', 'eko@example.com', '23e290bcc403cbdecd44305586908da8312f18f59c5a059488ba57fdb8c90335', 'customer'),
('U0006', 'Farah Nabila', 'farah@example.com', '51cf0790b992c94647ea03923ecf8de272b1f8836fc1cfebb788d02d534f9ed7', 'customer'),
('U0007', 'Gilang Pratama', 'gilang@example.com', 'd96c797aec2a1310f0b5913f503f7b96e573d7b0120f942b352e02784bb16ad4', 'customer'),
('U0008', 'Hana Fitri', 'hana@example.com', '2e0ebdaba0e8e726daf3cab3200afa51365d6d2527f4a4847c19ecc91c310608', 'customer'),
('U0009', 'Irfan Maulana', 'irfan@example.com', '52b460ebf051833dd95ace66190bfb36c097a5e3a4e2bb07655254450acb2048', 'customer'),
('U0010', 'Joko Wibowo', 'joko@example.com', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `booking_detail`
--
ALTER TABLE `booking_detail`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `hotel`
--
ALTER TABLE `hotel`
  ADD PRIMARY KEY (`hotel_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotel` (`hotel_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_detail`
--
ALTER TABLE `booking_detail`
  ADD CONSTRAINT `booking_detail_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_detail_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotel` (`hotel_id`) ON DELETE CASCADE;

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `room_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotel` (`hotel_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
