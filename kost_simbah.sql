-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 13, 2026 at 11:39 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kost_simbah`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('super_admin','admin') COLLATE utf8mb4_general_ci DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin Simbah', 'admin@kostsimbah.com', 'admin123', 'super_admin', '2026-06-12 15:46:58');

-- --------------------------------------------------------

--
-- Table structure for table `aktivitas`
--

CREATE TABLE `aktivitas` (
  `id` int NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `tipe` enum('Pembayaran','Booking','Maintenance','Lainnya') COLLATE utf8mb4_general_ci DEFAULT 'Lainnya',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aktivitas`
--

INSERT INTO `aktivitas` (`id`, `judul`, `deskripsi`, `tipe`, `created_at`) VALUES
(4, 'Kamar Baru Ditambah', 'Kamar Standard Room A1 (A1) berhasil ditambahkan.', 'Lainnya', '2026-06-13 02:43:22'),
(5, 'Booking Tour Baru', 'Fais Bayu ingin tour kamar A1 pada 2026-06-13', 'Booking', '2026-06-13 02:45:31'),
(6, 'Booking Tour Baru', 'sari kurniawan ingin tour kamar A1 pada 2026-06-14', 'Booking', '2026-06-13 04:14:27'),
(7, 'Booking Dikonfirmasi', 'Booking sari kurniawan dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 04:15:14'),
(8, 'Penghuni Baru dari Booking', 'sari kurniawan dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 04:16:18'),
(9, 'Booking Dikonfirmasi', 'Booking Fais Bayu dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 04:18:52'),
(10, 'Penghuni Baru dari Booking', 'Fais Bayu dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 04:19:38'),
(11, 'Kamar Baru Ditambah', 'Kamar Standard Room B1 (B2) berhasil ditambahkan.', 'Lainnya', '2026-06-13 04:34:38'),
(12, 'Booking Tour Baru', 'Fais Bayu ingin tour kamar A1 pada 2026-06-13', 'Booking', '2026-06-13 04:47:47'),
(13, 'Booking Tour Baru', 'Ari Wibowo ingin tour kamar B2 pada 2026-06-19', 'Booking', '2026-06-13 04:48:08'),
(14, 'Booking Tour Baru', 'sari kurniawan ingin tour kamar A1 pada 2026-06-13', 'Booking', '2026-06-13 04:48:33'),
(15, 'Booking Dikonfirmasi', 'Booking sari kurniawan dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 04:48:59'),
(16, 'Booking Dikonfirmasi', 'Booking Ari Wibowo dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 04:49:01'),
(17, 'Booking Dikonfirmasi', 'Booking Fais Bayu dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 04:49:03'),
(18, 'Penghuni Baru dari Booking', 'sari kurniawan dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 04:50:39'),
(19, 'Penghuni Baru dari Booking', 'Fais Bayu dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 04:52:35'),
(20, 'Data Penghuni Diperbarui', 'Data Fais Bayu telah diperbarui.', 'Lainnya', '2026-06-13 04:53:22'),
(21, 'Pembayaran Ditambahkan', 'Pembayaran dari sari kurniawan untuk bulan 2026-06-01.', 'Pembayaran', '2026-06-13 04:54:16'),
(22, 'Booking Tour Baru', 'Fais Bayu ingin tour kamar B2 pada 2026-06-13', 'Booking', '2026-06-13 04:57:18'),
(23, 'Booking Dikonfirmasi', 'Booking Fais Bayu dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 05:05:09'),
(24, 'Booking Dibatalkan', 'Booking sari kurniawan dibatalkan, kamar kembali Tersedia.', 'Booking', '2026-06-13 05:05:20'),
(25, 'Booking Dikonfirmasi', 'Booking sari kurniawan dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 05:05:49'),
(26, 'Penghuni Baru dari Booking', 'Fais Bayu dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 05:10:45'),
(27, 'Penghuni Baru', 'Bayu telah ditambahkan sebagai penghuni.', 'Lainnya', '2026-06-13 05:11:45'),
(28, 'Kamar Baru Ditambah', 'Kamar Standard Room A2 (A2) berhasil ditambahkan.', 'Lainnya', '2026-06-13 07:23:01'),
(29, 'Kamar Baru Ditambah', 'Kamar Standard Room A2 (A2) berhasil ditambahkan.', 'Lainnya', '2026-06-13 07:23:18'),
(30, 'Booking Tour Baru', 'Megawati ingin tour kamar A1 pada 2026-06-14', 'Booking', '2026-06-13 07:38:38'),
(31, 'Booking Dikonfirmasi', 'Booking Megawati dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 07:39:01'),
(32, 'Penghuni Baru dari Booking', 'Megawati dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 07:48:54'),
(33, 'Data Penghuni Diperbarui', 'Data Megawati telah diperbarui.', 'Lainnya', '2026-06-13 08:09:08'),
(34, 'Penghuni Keluar Otomatis', 'Megawati telah selesai masa sewanya, kamar dibebaskan.', 'Lainnya', '2026-06-13 08:09:11'),
(35, 'Data Penghuni Diperbarui', 'Data Megawati telah diperbarui.', 'Lainnya', '2026-06-13 08:09:43'),
(36, 'Data Penghuni Diperbarui', 'Data Megawati telah diperbarui.', 'Lainnya', '2026-06-13 08:10:02'),
(37, 'Kamar Baru Ditambah', 'Kamar Executive Room A2 (A2) berhasil ditambahkan.', 'Lainnya', '2026-06-13 08:13:08'),
(38, 'Booking Tour Baru', 'Windah Habatusauda ingin tour kamar A2 pada 2026-06-14', 'Booking', '2026-06-13 08:14:03'),
(39, 'Kamar Baru Ditambah', 'Kamar Executive Room A2 (A2) berhasil ditambahkan.', 'Lainnya', '2026-06-13 08:14:09'),
(40, 'Booking Dikonfirmasi', 'Booking Windah Habatusauda dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 08:14:24'),
(41, 'Penghuni Baru dari Booking', 'Windah Habatusauda dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 08:15:15'),
(42, 'Booking Tour Baru', 'ari saputra ingin tour kamar B2 pada 2026-06-14', 'Booking', '2026-06-13 08:18:22'),
(43, 'Booking Dikonfirmasi', 'Booking ari saputra dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 08:18:38'),
(44, 'Penghuni Baru dari Booking', 'ari saputra dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 08:20:15'),
(45, 'Kamar Baru Ditambah', 'Kamar Standard Room B2 (B2) berhasil ditambahkan.', 'Lainnya', '2026-06-13 08:21:23'),
(46, 'Booking Tour Baru', 'murni gunawan ingin tour kamar B2 pada 2026-06-18', 'Booking', '2026-06-13 08:21:59'),
(47, 'Kamar Baru Ditambah', 'Kamar Standard Room B2 (B2) berhasil ditambahkan.', 'Lainnya', '2026-06-13 08:22:05'),
(48, 'Booking Dikonfirmasi', 'Booking murni gunawan dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 08:22:11'),
(49, 'Penghuni Baru dari Booking', 'murni gunawan dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 08:22:54'),
(50, 'Data Penghuni Diperbarui', 'Data murni gunawan telah diperbarui.', 'Lainnya', '2026-06-13 08:23:27'),
(51, 'Kamar Baru Ditambah', 'Kamar Executive Room A4 (A4) berhasil ditambahkan.', 'Lainnya', '2026-06-13 08:24:47'),
(52, 'Booking Tour Baru', 'randi firmansyah ingin tour kamar A4 pada 2026-06-15', 'Booking', '2026-06-13 08:26:47'),
(53, 'Booking Dikonfirmasi', 'Booking randi firmansyah dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 08:27:05'),
(54, 'Penghuni Baru dari Booking', 'randi firmansyah dikonversi dari booking menjadi penghuni.', 'Lainnya', '2026-06-13 08:27:29'),
(55, 'Data Penghuni Diperbarui', 'Data randi firmansyah telah diperbarui.', 'Lainnya', '2026-06-13 10:37:52'),
(56, 'Data Penghuni Diperbarui', 'Data ari saputra telah diperbarui.', 'Lainnya', '2026-06-13 10:38:12'),
(57, 'Data Penghuni Diperbarui', 'Data randi firmansyah telah diperbarui.', 'Lainnya', '2026-06-13 10:55:15'),
(58, 'Data Penghuni Diperbarui', 'Data murni gunawan telah diperbarui.', 'Lainnya', '2026-06-13 10:57:31'),
(59, 'Data Penghuni Diperbarui', 'Data ari saputra telah diperbarui.', 'Lainnya', '2026-06-13 10:57:50'),
(60, 'Data Penghuni Diperbarui', 'Data randi firmansyah telah diperbarui.', 'Lainnya', '2026-06-13 10:58:23'),
(61, 'Booking Tour Baru', 'rudi ginanjar ingin tour kamar B3 pada 2026-06-22', 'Booking', '2026-06-13 11:19:49'),
(62, 'Booking Dikonfirmasi', 'Booking rudi ginanjar dikonfirmasi, kamar otomatis Terisi.', 'Booking', '2026-06-13 11:20:07');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kamar_id` int DEFAULT NULL,
  `tanggal_tour` date DEFAULT NULL,
  `pesan` text COLLATE utf8mb4_general_ci,
  `status` enum('Pending','Konfirmasi','Batal','Selesai') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`id`, `nama`, `no_hp`, `email`, `kamar_id`, `tanggal_tour`, `pesan`, `status`, `created_at`) VALUES
(11, 'Megawati', '0895673876', NULL, 5, '2026-06-14', '', 'Selesai', '2026-06-13 07:38:38'),
(12, 'Windah Habatusauda', '0893567893', NULL, 9, '2026-06-14', '', 'Selesai', '2026-06-13 08:14:03'),
(13, 'ari saputra', '093567823245', NULL, 6, '2026-06-14', '', 'Selesai', '2026-06-13 08:18:22'),
(14, 'murni gunawan', '0937738278', NULL, 11, '2026-06-18', '', 'Selesai', '2026-06-13 08:21:58'),
(15, 'randi firmansyah', '0926541728', NULL, 13, '2026-06-15', '', 'Selesai', '2026-06-13 08:26:47');

-- --------------------------------------------------------

--
-- Table structure for table `kamar`
--

CREATE TABLE `kamar` (
  `id` int NOT NULL,
  `nomor_kamar` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tipe` enum('Standard','Deluxe','Executive','Suite') COLLATE utf8mb4_general_ci NOT NULL,
  `lantai` int NOT NULL DEFAULT '1',
  `luas` int DEFAULT NULL COMMENT 'dalam m2',
  `harga` decimal(12,2) NOT NULL,
  `status` enum('Tersedia','Terisi','Maintenance') COLLATE utf8mb4_general_ci DEFAULT 'Tersedia',
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `fasilitas` text COLLATE utf8mb4_general_ci COMMENT 'JSON array fasilitas',
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kamar`
--

INSERT INTO `kamar` (`id`, `nomor_kamar`, `nama`, `tipe`, `lantai`, `luas`, `harga`, `status`, `deskripsi`, `fasilitas`, `foto`, `created_at`, `updated_at`) VALUES
(5, 'A1', 'Executive Room A1', 'Executive', 2, 20, '1500000.00', 'Terisi', 'Nyaman, Lengkap, dan Aman', '[\"AC\",\"Kamar Mandi Dalam\",\"Meja & Kursi\",\"WiFi\"]', '/kost_simbah/uploads/kamar/kamar_1781336179_267.jpg', '2026-06-13 02:43:22', '2026-06-13 08:17:13'),
(6, 'B2', 'Standard Room B1', 'Standard', 1, 10, '700000.00', 'Terisi', '', '[\"Kamar Mandi Dalam\",\"Meja & Kursi\",\"WiFi\"]', '/kost_simbah/uploads/kamar/kamar_1781336040_403.jpg', '2026-06-13 04:34:38', '2026-06-13 08:18:38'),
(9, 'A2', 'Executive Room A2', 'Executive', 2, 20, '1500000.00', 'Terisi', '', '[\"AC\",\"Wifi\",\"KM dalam\",\"Meja dan Kursi\"]', '/kost_simbah/uploads/kamar/kamar_1781338388_121.jpg', '2026-06-13 08:13:08', '2026-06-13 08:14:24'),
(11, 'B2', 'Standard Room B2', 'Standard', 2, 10, '700000.00', 'Terisi', '', '[\"Wifi\",\"KM dalam\",\"meja dan kursi\"]', NULL, '2026-06-13 08:21:23', '2026-06-13 08:22:11'),
(12, 'B3', 'Standard Room B2', 'Standard', 2, 10, '700000.00', 'Terisi', '', '[\"Wifi\",\"KM dalam\",\"meja dan kursi\"]', NULL, '2026-06-13 08:22:05', '2026-06-13 11:20:07'),
(13, 'A4', 'Executive Room A4', 'Executive', 2, 20, '1650000.00', 'Terisi', '', '[\"AC\",\"Wifi\",\"KM dalam\",\"Meja dan Kursi\"]', NULL, '2026-06-13 08:24:47', '2026-06-13 08:27:05');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int NOT NULL,
  `penghuni_id` int DEFAULT NULL,
  `kamar_id` int DEFAULT NULL,
  `bulan_bayar` date DEFAULT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `status` enum('Lunas','Pending','Telat') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `tanggal_bayar` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `catatan` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `penghuni_id`, `kamar_id`, `bulan_bayar`, `jumlah`, `status`, `tanggal_bayar`, `catatan`, `created_at`) VALUES
(2, 10, 5, '2026-06-01', '1500000.00', 'Lunas', '2026-06-13 10:45:25', NULL, '2026-06-13 08:34:07'),
(3, 12, 6, '2026-06-01', '700000.00', 'Lunas', '2026-06-13 10:37:10', NULL, '2026-06-13 08:34:08'),
(4, 11, 9, '2026-06-01', '1500000.00', 'Lunas', '2026-06-13 10:45:07', NULL, '2026-06-13 08:34:08'),
(5, 13, 11, '2026-06-01', '700000.00', 'Lunas', '2026-06-13 10:45:22', NULL, '2026-06-13 08:34:08'),
(6, 14, 13, '2026-06-01', '1650000.00', 'Lunas', '2026-06-13 10:45:29', NULL, '2026-06-13 08:34:08'),
(9, 14, 13, '2026-05-01', '1650000.00', 'Lunas', '2026-06-13 10:51:05', NULL, '2026-06-13 10:44:20'),
(10, 12, 6, '2026-04-01', '700000.00', 'Telat', '2026-06-13 10:58:09', NULL, '2026-06-13 10:58:09'),
(11, 12, 6, '2026-05-01', '700000.00', 'Telat', '2026-06-13 10:58:09', NULL, '2026-06-13 10:58:09');

-- --------------------------------------------------------

--
-- Table structure for table `penghuni`
--

CREATE TABLE `penghuni` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_ktp` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kamar_id` int DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') COLLATE utf8mb4_general_ci DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penghuni`
--

INSERT INTO `penghuni` (`id`, `nama`, `email`, `no_hp`, `no_ktp`, `kamar_id`, `tanggal_masuk`, `tanggal_keluar`, `status`, `created_at`) VALUES
(10, 'Megawati', 'mega@gmail.com', '0895673876', '92531177256', 5, '2026-06-13', '2026-06-17', 'Aktif', '2026-06-13 07:48:53'),
(11, 'Windah Habatusauda', 'wind@gmail.com', '0893567893', '9286112635617651', 9, '2026-06-15', NULL, 'Aktif', '2026-06-13 08:15:15'),
(12, 'ari saputra', 'ari@gmail.com', '093567823248', '34271567466718', 6, '2026-04-16', NULL, 'Aktif', '2026-06-13 08:20:15'),
(13, 'murni gunawan', 'murni@gmail.com', '0937738278', '8796967859', 11, '2026-06-13', '2026-06-14', 'Aktif', '2026-06-13 08:22:54'),
(14, 'randi firmansyah', 'randi@gmail.com', '0926541721', '89937486739', 13, '2026-05-13', '2026-06-16', 'Aktif', '2026-06-13 08:27:29');

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
-- Indexes for table `aktivitas`
--
ALTER TABLE `aktivitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indexes for table `kamar`
--
ALTER TABLE `kamar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penghuni_id` (`penghuni_id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indexes for table `penghuni`
--
ALTER TABLE `penghuni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `aktivitas`
--
ALTER TABLE `aktivitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `kamar`
--
ALTER TABLE `kamar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `penghuni`
--
ALTER TABLE `penghuni`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`penghuni_id`) REFERENCES `penghuni` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `penghuni`
--
ALTER TABLE `penghuni`
  ADD CONSTRAINT `penghuni_ibfk_1` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
