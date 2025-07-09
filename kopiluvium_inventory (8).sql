-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2025 at 05:17 PM
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
-- Database: `kopiluvium_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int(11) NOT NULL,
  `kode_barang` varchar(20) DEFAULT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `satuan_utuh_id` int(11) NOT NULL,
  `satuan_pecahan_id` int(11) DEFAULT NULL,
  `is_aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `kode_barang`, `nama_barang`, `kategori_id`, `satuan_utuh_id`, `satuan_pecahan_id`, `is_aktif`, `created_at`, `updated_at`) VALUES
(8, 'EB-001', 'Es Batu', 2, 1, NULL, 1, '2025-05-21 15:32:01', '2025-05-21 15:32:01'),
(9, 'SMM-001', 'Sirup Mojito Mint', 2, 2, 5, 1, '2025-05-21 15:33:09', '2025-05-21 15:33:09'),
(10, 'SWM-002', 'Sirup Wild Mint', 2, 2, 5, 1, '2025-05-21 15:33:35', '2025-05-21 15:36:00'),
(11, 'SP-001', 'Sirup Peach', 2, 2, 5, 1, '2025-05-21 15:40:37', '2025-05-21 15:40:37'),
(12, 'SJ-001', 'Sirup Jahe', 2, 2, 5, 1, '2025-05-21 15:40:59', '2025-05-21 15:40:59'),
(13, 'SL-001', 'Sirup Lemon', 2, 2, 5, 1, '2025-05-21 15:41:15', '2025-05-21 15:41:15'),
(14, 'SS-001', 'Sirup Strawberry', 2, 2, 5, 1, '2025-05-21 15:41:28', '2025-05-21 15:41:28'),
(15, 'SV-001', 'Sirup Vanilla', 2, 2, 5, 1, '2025-05-21 15:41:41', '2025-05-21 15:41:41'),
(16, 'SH-001', 'Sirup Hazelnut', 2, 2, 5, 1, '2025-05-21 15:41:49', '2025-05-21 15:41:49'),
(17, 'SC-001', 'Sirup Caramel', 2, 2, 5, 1, '2025-05-21 15:42:00', '2025-05-21 15:42:00'),
(18, 'SC-002', 'Sirup Calamansi', 2, 2, 5, 1, '2025-05-21 15:42:10', '2025-05-23 15:30:09'),
(19, 'SCC-001', 'Sirup Cotton Candy', 2, 2, 5, 1, '2025-05-21 15:42:22', '2025-05-21 15:42:22'),
(20, 'SGA-001', 'Sirup Green Apple', 2, 2, 5, 1, '2025-05-21 15:42:28', '2025-05-21 15:42:28'),
(21, 'SM-001', 'Sirup Markisa', 2, 2, 5, 1, '2025-05-21 15:42:39', '2025-05-21 15:42:39'),
(22, 'SMO-001', 'Sirup Mandarin Orange', 2, 2, 5, 1, '2025-05-21 15:42:47', '2025-05-21 15:42:47'),
(23, 'GA-001', 'Gula Aren', 2, 3, 5, 1, '2025-05-21 15:44:31', '2025-05-21 15:44:31'),
(24, 'SC-003', 'Sirup Cranberry', 2, 3, 5, 1, '2025-05-21 15:49:04', '2025-05-21 15:49:04'),
(25, 'UD-001', 'UHT Diamond', 2, 4, 9, 1, '2025-05-21 15:49:42', '2025-05-21 15:49:42'),
(26, 'FS-001', 'Fanta Soda', 2, 4, 9, 1, '2025-05-21 15:50:12', '2025-05-21 15:50:12'),
(27, 'CLH-001', 'Cup Lid Hot', 2, 4, 9, 1, '2025-05-21 15:50:30', '2025-05-21 15:50:30'),
(28, 'CH-001', 'Cup Hot', 2, 4, 9, 1, '2025-05-21 15:50:44', '2025-05-21 15:50:44'),
(29, 'CLI-001', 'Cup Lid Ice', 2, 4, 9, 1, '2025-05-21 15:50:51', '2025-05-21 15:50:51'),
(30, 'IL-001', 'Injection Large', 2, 4, 9, 1, '2025-05-21 15:51:01', '2025-05-21 15:51:01'),
(31, 'IS-001', 'Injection Small', 2, 4, 9, 1, '2025-05-21 15:51:16', '2025-05-21 15:51:16'),
(32, 'CLC-001', 'Cup Lid Can Cup', 2, 4, 9, 1, '2025-05-21 15:51:31', '2025-05-21 15:51:31'),
(33, 'CC-001', 'Can Cup', 2, 4, 9, 1, '2025-05-24 09:04:19', '2025-05-26 05:16:43'),
(34, 'BBX-001', 'Bawang Bombai Gede', 4, 6, 5, 1, '2025-05-26 07:34:46', '2025-05-27 06:34:07'),
(35, 'DBF-001', 'Daun Basil Fresh', 4, 6, 5, 1, '2025-05-26 07:36:04', '2025-05-26 07:37:28');

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id` int(11) NOT NULL,
  `nomor_transaksi` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `tujuan` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `total_nilai` decimal(12,2) DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_keluar`
--

INSERT INTO `barang_keluar` (`id`, `nomor_transaksi`, `tanggal`, `lokasi_id`, `tujuan`, `user_id`, `total_nilai`, `keterangan`, `created_at`, `updated_at`) VALUES
(5, 'BK202505268847', '2025-05-26', 1, NULL, 2, 0.00, 'asd', '2025-05-26 05:23:32', '2025-05-26 05:23:32');

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar_detail`
--

CREATE TABLE `barang_keluar_detail` (
  `id` int(11) NOT NULL,
  `barang_keluar_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `jumlah_utuh` decimal(12,2) DEFAULT 0.00,
  `jumlah_pecahan` decimal(12,2) DEFAULT 0.00,
  `harga_satuan` decimal(12,2) DEFAULT 0.00,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang_keluar_detail`
--

INSERT INTO `barang_keluar_detail` (`id`, `barang_keluar_id`, `barang_id`, `lokasi_id`, `jumlah_utuh`, `jumlah_pecahan`, `harga_satuan`, `subtotal`, `keterangan`, `created_at`, `updated_at`) VALUES
(4, 5, 33, 1, 2.00, 0.00, 0.00, 0.00, '', '2025-05-26 05:29:33', '2025-05-26 05:29:33');

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id` int(11) NOT NULL,
  `nomor_transaksi` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `gambar_barang` varchar(255) DEFAULT NULL,
  `struk` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk_detail`
--

CREATE TABLE `barang_masuk_detail` (
  `id` int(11) NOT NULL,
  `barang_masuk_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jumlah_utuh` decimal(12,2) DEFAULT 0.00,
  `jumlah_pecahan` decimal(12,2) DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_barang`
--

CREATE TABLE `kategori_barang` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_barang`
--

INSERT INTO `kategori_barang` (`id`, `nama_kategori`, `lokasi_id`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Minuman', 1, 'Kategori minuman di bar', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(2, 'Bahan Minuman', 1, 'Bahan untuk minuman di bar', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(3, 'Makanan', 2, 'Kategori makanan di kitchen', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(4, 'Bahan Makanan', 2, 'Bahan untuk makanan di kitchen', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(5, 'Bumbu', 2, 'Bumbu masakan di kitchen', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(6, 'Perlengkapan Kasir', 3, 'Perlengkapan untuk kasir', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(7, 'Kemasan', 3, 'Kemasan produk', '2025-05-19 15:16:30', '2025-05-19 15:16:30');

-- --------------------------------------------------------

--
-- Table structure for table `lokasi`
--

CREATE TABLE `lokasi` (
  `id` int(11) NOT NULL,
  `nama_lokasi` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lokasi`
--

INSERT INTO `lokasi` (`id`, `nama_lokasi`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Bar', 'Lokasi bar Kopiluvium', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(2, 'Kitchen', 'Lokasi dapur Kopiluvium', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(3, 'Kasir', 'Lokasi kasir Kopiluvium', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(4, 'Waiters', 'Lokasi untuk staff waiters', '2025-05-24 07:57:11', '2025-05-24 07:57:11');

-- --------------------------------------------------------

--
-- Table structure for table `satuan`
--

CREATE TABLE `satuan` (
  `id` int(11) NOT NULL,
  `nama_satuan` varchar(20) NOT NULL,
  `deskripsi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `satuan`
--

INSERT INTO `satuan` (`id`, `nama_satuan`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Bal', 'Satuan bal', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(2, 'Botol', 'Satuan botol', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(3, 'Drigen', 'Satuan drigen', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(4, 'Dus', 'Satuan dus', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(5, 'Gram', 'Satuan gram', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(6, 'Kg', 'Satuan kilogram', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(7, 'Buah', 'Satuan buah/unit', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(8, 'Pack', 'Satuan pack', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(9, 'Pcs', 'Satuan pieces', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(10, 'Slop', 'Satuan slop', '2025-05-19 15:16:30', '2025-05-19 15:16:30'),
(11, 'Bungkus', 'Satuan bungkus', '2025-05-19 15:16:30', '2025-05-19 15:16:30');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `jumlah_utuh` decimal(12,2) DEFAULT 0.00,
  `jumlah_pecahan` decimal(12,2) DEFAULT 0.00,
  `stok_minimal` decimal(12,2) DEFAULT 0.00,
  `tanggal_update` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `barang_id`, `lokasi_id`, `jumlah_utuh`, `jumlah_pecahan`, `stok_minimal`, `tanggal_update`, `created_at`, `updated_at`) VALUES
(1, 8, 1, 4.00, 0.00, 0.00, '2025-05-21', '2025-05-21 16:54:09', '2025-05-21 16:54:09'),
(2, 26, 1, 5.00, 900.00, 0.00, '2025-05-21', '2025-05-21 16:54:09', '2025-05-21 16:54:09'),
(3, 23, 1, 3.00, 400.00, 0.00, '2025-05-21', '2025-05-21 16:54:09', '2025-05-21 16:54:09'),
(7, 29, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:00:51', '2025-05-24 09:00:51'),
(8, 27, 1, 6.00, 0.00, 0.00, '2025-05-24', '2025-05-24 09:00:56', '2025-05-24 10:18:26'),
(9, 28, 1, 19.00, 800.00, 0.00, '2025-05-27', '2025-05-24 09:01:02', '2025-05-27 06:25:11'),
(10, 32, 1, 8.00, 0.00, 0.00, '2025-05-24', '2025-05-24 09:01:05', '2025-05-24 09:07:29'),
(11, 18, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:10', '2025-05-24 09:01:10'),
(12, 30, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:15', '2025-05-24 09:01:15'),
(13, 31, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:21', '2025-05-24 09:01:21'),
(14, 17, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:30', '2025-05-24 09:01:30'),
(15, 19, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:36', '2025-05-24 09:01:36'),
(16, 24, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:40', '2025-05-24 09:01:40'),
(17, 20, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:44', '2025-05-24 09:01:44'),
(18, 16, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:49', '2025-05-24 09:01:49'),
(19, 13, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:53', '2025-05-24 09:01:53'),
(20, 12, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:01:57', '2025-05-24 09:01:57'),
(21, 22, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:01', '2025-05-24 09:02:01'),
(22, 21, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:05', '2025-05-24 09:02:05'),
(23, 9, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:10', '2025-05-24 09:02:10'),
(24, 14, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:14', '2025-05-24 09:02:14'),
(25, 11, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:18', '2025-05-24 09:02:18'),
(26, 15, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:22', '2025-05-24 09:02:22'),
(27, 10, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:26', '2025-05-24 09:02:26'),
(28, 25, 1, 0.00, 0.00, 0.00, '0000-00-00', '2025-05-24 09:02:30', '2025-05-24 09:02:30'),
(29, 33, 1, 10.00, 800.00, 0.00, '2025-05-27', '2025-05-24 09:04:19', '2025-05-27 06:25:11'),
(30, 34, 2, 10.00, 0.00, 0.00, '2025-05-27', '2025-05-26 07:34:46', '2025-05-27 06:35:29'),
(31, 35, 2, 5.00, 0.00, 0.00, '2025-05-27', '2025-05-26 07:36:04', '2025-05-27 06:35:29');

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname`
--

CREATE TABLE `stock_opname` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `lokasi_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('draft','selesai','batal') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname_details`
--

CREATE TABLE `stock_opname_details` (
  `id` int(11) NOT NULL,
  `stock_opname_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jumlah_sistem_utuh` decimal(12,2) DEFAULT 0.00,
  `jumlah_sistem_pecahan` decimal(12,2) DEFAULT 0.00,
  `actual_qty_whole` decimal(12,2) DEFAULT 0.00,
  `actual_qty_fraction` decimal(12,2) DEFAULT 0.00,
  `selisih_utuh` decimal(12,2) GENERATED ALWAYS AS (`actual_qty_whole` - `jumlah_sistem_utuh`) VIRTUAL,
  `selisih_pecahan` decimal(12,2) GENERATED ALWAYS AS (`actual_qty_fraction` - `jumlah_sistem_pecahan`) VIRTUAL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('kepala_toko','staf_gudang','manager_keuangan','bartender','kitchen','kasir','waiters') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lokasi_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`, `updated_at`, `lokasi_id`) VALUES
(2, 'admin', '$2y$10$oqJ/uj4bc.0ca/lwK0dr.eJSjZiUfxkjxuqm2HbNlBEXZciDEE9f.', 'Administrator', 'kepala_toko', '2025-05-19 16:04:26', '2025-05-26 06:43:59', NULL),
(3, 'dian', '$2y$10$KZL9tNYYf4v8vwNRylvo4ugtWNoz7hdpOsCqSWPR4gztz9UBWIj4.', 'Dian Tri', 'kasir', '2025-05-19 16:28:27', '2025-05-21 16:37:35', 3),
(4, 'rival', '$2y$10$6lPa.eSntsEIxdeDurg4KOwuEFLjP0hm76chf/s6FNLDBA9l9brTO', 'Rival', 'bartender', '2025-05-19 16:30:44', '2025-05-24 07:35:30', 1),
(5, 'lukman', '$2y$10$PR2GNwuSqK8IjZCekcy1Pexq5D2teOREFxfrPvFA9pI7Q5q5SmB.i', 'Lukman', 'kitchen', '2025-05-19 16:31:46', '2025-05-21 16:31:39', 2),
(6, 'manager', '$2y$10$cztBneA1MG0qezafYRQRxenYNgdv.Kw2QERbPJuNgZY0cAOZnCY0S', 'Manager', 'staf_gudang', '2025-05-20 06:54:54', '2025-05-24 08:44:16', NULL),
(8, 'didi', '$2y$10$rvuwcKtbyyOZ9BYDIS5O5uYsL3bDxF/t.e.5aPwyG2TQxggDkPUuO', 'Didi', 'manager_keuangan', '2025-05-24 07:38:04', '2025-05-24 07:38:04', NULL),
(9, 'waiters', '$2y$10$5bSWHUb2XozfE6S1mZNR2.yxaSRvw.k9MxI0rS1rxAOiZu/X4Du4y', 'Waiters', 'waiters', '2025-05-24 07:58:44', '2025-05-24 07:58:44', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `satuan_utuh_id` (`satuan_utuh_id`),
  ADD KEY `satuan_pecahan_id` (`satuan_pecahan_id`);

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_transaksi` (`nomor_transaksi`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barang_keluar_ibfk_2` (`lokasi_id`);

--
-- Indexes for table `barang_keluar_detail`
--
ALTER TABLE `barang_keluar_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_keluar_id` (`barang_keluar_id`),
  ADD KEY `lokasi_id` (`lokasi_id`),
  ADD KEY `barang_keluar_detail_ibfk_2` (`barang_id`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_transaksi` (`nomor_transaksi`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barang_masuk_ibfk_2` (`lokasi_id`);

--
-- Indexes for table `barang_masuk_detail`
--
ALTER TABLE `barang_masuk_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_masuk_id` (`barang_masuk_id`),
  ADD KEY `barang_masuk_detail_ibfk_2` (`barang_id`);

--
-- Indexes for table `kategori_barang`
--
ALTER TABLE `kategori_barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`),
  ADD KEY `lokasi_id` (`lokasi_id`);

--
-- Indexes for table `lokasi`
--
ALTER TABLE `lokasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_lokasi` (`nama_lokasi`);

--
-- Indexes for table `satuan`
--
ALTER TABLE `satuan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_satuan` (`nama_satuan`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_barang_lokasi_tanggal` (`barang_id`,`lokasi_id`,`tanggal_update`),
  ADD KEY `lokasi_id` (`lokasi_id`);

--
-- Indexes for table `stock_opname`
--
ALTER TABLE `stock_opname`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lokasi_id` (`lokasi_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `stock_opname_details`
--
ALTER TABLE `stock_opname_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_so_barang` (`stock_opname_id`,`barang_id`),
  ADD KEY `stock_opname_details_ibfk_2` (`barang_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `users_ibfk_1` (`lokasi_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `barang_keluar_detail`
--
ALTER TABLE `barang_keluar_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `barang_masuk_detail`
--
ALTER TABLE `barang_masuk_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kategori_barang`
--
ALTER TABLE `kategori_barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lokasi`
--
ALTER TABLE `lokasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `satuan`
--
ALTER TABLE `satuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `stock_opname`
--
ALTER TABLE `stock_opname`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `stock_opname_details`
--
ALTER TABLE `stock_opname_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_barang` (`id`),
  ADD CONSTRAINT `barang_ibfk_2` FOREIGN KEY (`satuan_utuh_id`) REFERENCES `satuan` (`id`),
  ADD CONSTRAINT `barang_ibfk_3` FOREIGN KEY (`satuan_pecahan_id`) REFERENCES `satuan` (`id`);

--
-- Constraints for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `barang_keluar_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `barang_keluar_ibfk_2` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`);

--
-- Constraints for table `barang_keluar_detail`
--
ALTER TABLE `barang_keluar_detail`
  ADD CONSTRAINT `barang_keluar_detail_ibfk_1` FOREIGN KEY (`barang_keluar_id`) REFERENCES `barang_keluar` (`id`),
  ADD CONSTRAINT `barang_keluar_detail_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`),
  ADD CONSTRAINT `barang_keluar_detail_ibfk_3` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`);

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `barang_masuk_ibfk_2` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`);

--
-- Constraints for table `barang_masuk_detail`
--
ALTER TABLE `barang_masuk_detail`
  ADD CONSTRAINT `barang_masuk_detail_ibfk_1` FOREIGN KEY (`barang_masuk_id`) REFERENCES `barang_masuk` (`id`),
  ADD CONSTRAINT `barang_masuk_detail_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`);

--
-- Constraints for table `kategori_barang`
--
ALTER TABLE `kategori_barang`
  ADD CONSTRAINT `kategori_barang_ibfk_1` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`);

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`),
  ADD CONSTRAINT `stock_ibfk_2` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`);

--
-- Constraints for table `stock_opname`
--
ALTER TABLE `stock_opname`
  ADD CONSTRAINT `stock_opname_ibfk_1` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`),
  ADD CONSTRAINT `stock_opname_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stock_opname_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stock_opname_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_opname_details`
--
ALTER TABLE `stock_opname_details`
  ADD CONSTRAINT `stock_opname_details_ibfk_1` FOREIGN KEY (`stock_opname_id`) REFERENCES `stock_opname` (`id`),
  ADD CONSTRAINT `stock_opname_details_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
