-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 27 Jun 2025 pada 06.22
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pupukstore`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `province_id` int(11) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `city_name` varchar(100) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `produk_id`, `nama_produk`, `harga`, `jumlah`, `subtotal`) VALUES
(1, 1, 1, 'NPK Phonska 25kg', 85000.00, 2, 170000.00),
(2, 1, 4, 'Pupuk Buah Gandasil D 1kg', 35000.00, 1, 35000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `created_at`) VALUES
(1, 'Pupuk Dasar', 'Pupuk untuk kebutuhan dasar tanaman', '2025-06-24 10:06:21'),
(2, 'Pupuk Buah', 'Pupuk khusus untuk tanaman buah', '2025-06-24 10:06:21'),
(3, 'Pupuk Sayur', 'Pupuk untuk tanaman sayuran', '2025-06-24 10:06:21'),
(4, 'Pupuk Padi', 'Pupuk khusus untuk tanaman padi', '2025-06-24 10:06:21'),
(5, 'Pupuk Hias', 'Pupuk untuk tanaman hias', '2025-06-24 10:06:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `keranjang`
--

INSERT INTO `keranjang` (`id`, `user_id`, `produk_id`, `jumlah`, `created_at`) VALUES
(12, 2, 2, 4, '2025-06-24 21:59:11'),
(13, 2, 5, 4, '2025-06-24 21:59:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` enum('order','payment','shipping','general') NOT NULL DEFAULT 'general',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `telepon` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `provinsi` varchar(50) NOT NULL,
  `province_id` int(11) DEFAULT NULL,
  `kota` varchar(50) NOT NULL,
  `city_id` int(11) DEFAULT NULL,
  `kode_pos` varchar(10) NOT NULL,
  `payment_method` enum('bank_transfer','cod') NOT NULL,
  `courier` varchar(20) DEFAULT NULL,
  `shipping_service` varchar(100) DEFAULT NULL,
  `shipping_etd` varchar(20) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_verified_at` datetime DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `nama_lengkap`, `telepon`, `email`, `alamat`, `provinsi`, `province_id`, `kota`, `city_id`, `kode_pos`, `payment_method`, `courier`, `shipping_service`, `shipping_etd`, `subtotal`, `shipping_cost`, `cod_fee`, `discount`, `total`, `catatan`, `status`, `payment_status`, `payment_proof`, `payment_verified_at`, `tracking_number`, `shipped_at`, `delivered_at`, `created_at`, `updated_at`, `total_amount`, `shipping_address`, `notes`, `admin_notes`, `customer_notes`) VALUES
(1, 4, 'ORD-20250624-3817', 'Ihsan Fahriza', '0823000000', 'fahrizaihsan06@gmail.com', 'kisaran barat', 'Jawa Timur', NULL, 'Batu', NULL, '21217', 'bank_transfer', NULL, NULL, NULL, 360000.00, 50000.00, 0.00, 0.00, 410000.00, 'mantap', 'delivered', 'pending', NULL, NULL, NULL, NULL, NULL, '2025-06-24 12:42:06', '2025-06-26 03:20:35', 410000.00, NULL, NULL, '', NULL),
(2, 4, 'ORD-20250624-9907', 'Ihsan Fahriza', '082188907765', 'fahrizaihsan06@gmail.com', 'kisaran barat', 'Banten', NULL, 'Cilegon', NULL, '21212', 'bank_transfer', NULL, NULL, NULL, 25000.00, 50000.00, 0.00, 0.00, 75000.00, '', 'cancelled', 'pending', NULL, NULL, NULL, NULL, NULL, '2025-06-24 12:44:06', '2025-06-26 06:42:03', 75000.00, NULL, NULL, 'maap, gajelas', NULL),
(3, 4, 'ORD-20250626-1032', 'Ihsan Fahriza', '0823000000', 'fahrizaihsan06@gmail.com', 'kisaran barat', 'Sumatera Utara', NULL, 'Kisaran Barat', NULL, '21217', 'bank_transfer', NULL, NULL, NULL, 1175000.00, 50000.00, 0.00, 0.00, 1225000.00, 'mantap', 'confirmed', 'pending', NULL, NULL, NULL, NULL, NULL, '2025-06-26 12:57:40', '2025-06-26 12:58:34', 1225000.00, NULL, NULL, '', NULL),
(4, 4, 'ORD-20250627-9959', 'Ihsan Fahriza', '08981102887777', 'fahrizaihsan06@gmail.com', 'kisaran barat', 'Jawa Tengah', NULL, 'kesawan', NULL, '13232', 'bank_transfer', NULL, NULL, NULL, 435000.00, 50000.00, 0.00, 0.00, 485000.00, '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, '2025-06-27 03:53:07', '2025-06-27 04:13:49', 485000.00, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `nama_produk`, `harga`, `quantity`, `subtotal`) VALUES
(1, 1, 3, 'Urea 50kg', 165000.00, 1, 165000.00),
(2, 1, 2, 'Pupuk Kandang Sapi 10kg', 25000.00, 3, 75000.00),
(3, 1, 1, 'NPK Phonska 25kg', 85000.00, 1, 85000.00),
(4, 1, 4, 'Pupuk Buah Gandasil D 1kg', 35000.00, 1, 35000.00),
(5, 2, 2, 'Pupuk Kandang Sapi 10kg', 25000.00, 1, 25000.00),
(6, 3, 1, 'NPK Phonska 25kg', 85000.00, 8, 680000.00),
(7, 3, 5, 'Kompos Organik 5kg', 15000.00, 33, 495000.00),
(8, 4, 1, 'NPK Phonska 25kg', 85000.00, 1, 85000.00),
(9, 4, 2, 'Pupuk Kandang Sapi 10kg', 25000.00, 14, 350000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','payment_uploaded','payment_verified','processing','shipped','delivered','cancelled') NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kode_pesanan` varchar(20) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `ongkir` decimal(10,2) DEFAULT 0.00,
  `total_bayar` decimal(10,2) NOT NULL,
  `status_pesanan` enum('pending','dikonfirmasi','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
  `metode_pembayaran` enum('transfer_bank','cod','ewallet') NOT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `alamat_pengiriman` text NOT NULL,
  `no_hp_penerima` varchar(20) NOT NULL,
  `catatan` text DEFAULT NULL,
  `tanggal_pesan` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_konfirmasi` timestamp NULL DEFAULT NULL,
  `tanggal_kirim` timestamp NULL DEFAULT NULL,
  `tanggal_selesai` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `user_id`, `kode_pesanan`, `total_harga`, `ongkir`, `total_bayar`, `status_pesanan`, `metode_pembayaran`, `bukti_pembayaran`, `nama_penerima`, `alamat_pengiriman`, `no_hp_penerima`, `catatan`, `tanggal_pesan`, `tanggal_konfirmasi`, `tanggal_kirim`, `tanggal_selesai`) VALUES
(1, 2, 'PS001', 205000.00, 15000.00, 220000.00, 'pending', 'transfer_bank', NULL, 'Budi Petani', 'Jl. Sawah Indah No. 123, Bogor', '081234567890', NULL, '2025-06-24 10:06:21', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `jenis_pupuk` enum('organik','anorganik','hayati') NOT NULL,
  `berat` varchar(20) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `deskripsi` text DEFAULT NULL,
  `manfaat` text DEFAULT NULL,
  `cara_pakai` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `views` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `kategori_id`, `brand`, `jenis_pupuk`, `berat`, `harga`, `stok`, `deskripsi`, `manfaat`, `cara_pakai`, `gambar`, `status`, `is_featured`, `created_at`, `updated_at`, `views`) VALUES
(1, 'NPK Phonska 25kg', 1, 'Petrokimia', 'anorganik', '5.0', 85000.00, 0, 'Pupuk NPK lengkap untuk semua jenis tanaman', 'Meningkatkan pertumbuhan dan hasil panen', 'Taburkan 2-3 sendok makan per tanaman', '1750994307_685e0d8372bb4.jpg', 'active', 1, '2025-06-24 10:06:21', '2025-06-27 03:53:07', 0),
(2, 'Pupuk Kandang Sapi 10kg', 1, 'Organik Nusantara', 'organik', '10.0', 25000.00, 82, 'Pupuk organik dari kotoran sapi yang sudah difermentasi', 'Memperbaiki struktur tanah dan kesuburan', 'Campurkan dengan tanah 1:3', '1750994334_685e0d9e8052e.png', 'active', 1, '2025-06-24 10:06:21', '2025-06-27 03:53:07', 0),
(3, 'Urea 50kg', 1, 'Petrokimia', 'anorganik', '50.0', 165000.00, 90, 'Pupuk nitrogen untuk pertumbuhan daun', 'Mempercepat pertumbuhan vegetatif', 'Aplikasi 1-2 minggu sekali', '1750994411_685e0deb3033d.jpg', 'active', 1, '2025-06-24 10:06:21', '2025-06-27 03:20:11', 0),
(4, 'Pupuk Buah Gandasil', 2, 'Gandasil', 'anorganik', '500gr', 35000.00, 74, 'Pupuk khusus untuk tanaman buah-buahan', 'Meningkatkan kualitas dan kuantitas buah', 'Larutkan 1 sendok teh dalam 1 liter air', '1750994453_685e0e15081f4.jpg', 'active', 1, '2025-06-24 10:06:21', '2025-06-27 03:21:10', 0),
(5, 'Kompos Organik 5kg', 1, 'Green Kompos', 'organik', '5.0', 15000.00, 167, 'Kompos organik berkualitas tinggi', 'Memperbaiki kesuburan tanah secara alami', 'Campurkan dengan media tanam', '1750994486_685e0e3687fa2.jpg', 'active', 1, '2025-06-24 10:06:21', '2025-06-27 03:21:26', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `promo_codes`
--

INSERT INTO `promo_codes` (`id`, `code`, `description`, `type`, `value`, `min_purchase`, `max_discount`, `usage_limit`, `used_count`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, 'WELCOME10', 'Diskon 10% untuk pelanggan baru', 'percentage', 10.00, 100000.00, 50000.00, 0, 0, '2025-06-24', '2025-07-24', 1, '2025-06-24 11:55:16'),
(2, 'HEMAT50K', 'Diskon Rp 50.000 untuk pembelian min Rp 500.000', 'fixed', 50000.00, 500000.00, 50000.00, 0, 0, '2025-06-24', '2025-07-24', 1, '2025-06-24 11:55:16'),
(3, 'PUPUK15', 'Diskon 15% khusus pupuk organik', 'percentage', 15.00, 200000.00, 100000.00, 0, 0, '2025-06-24', '2025-07-09', 1, '2025-06-24 11:55:16'),
(9, 'WEEKEND79', 'Weekend Sale - Diskon 20% maksimal Rp 200.000', 'percentage', 20.00, 1000000.00, 200000.00, 10, 0, '2025-06-25', '2025-06-26', 1, '2025-06-25 23:48:24'),
(10, 'WEEKEND14', 'Weekend Sale - Diskon 20% maksimal Rp 290.000', 'percentage', 20.00, 10000.00, 290000.00, 5, 0, '2025-06-26', '2025-06-28', 1, '2025-06-26 02:11:43'),
(11, 'WEEKEND95', 'Weekend Sale - Diskon 20% maksimal Rp 250.000', 'fixed', 35000.00, 50000.00, 250000.00, 10, 0, '2025-06-26', '2025-07-04', 0, '2025-06-26 02:13:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `provinces`
--

CREATE TABLE `provinces` (
  `id` int(11) NOT NULL,
  `province` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `review`
--

CREATE TABLE `review` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `komentar` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `telepon` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `no_hp`, `alamat`, `role`, `status`, `created_at`, `updated_at`, `telepon`) VALUES
(1, 'Admin Pupuk Store', 'admin@pupukstore.com', '0192023a7bbd73250516f069df18b500', NULL, NULL, 'admin', 'active', '2025-06-24 10:06:21', '2025-06-24 10:06:21', NULL),
(2, 'Budi Petani', 'budi@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '081234567890', 'Jl. Sawah Indah No. 123, Bogor', 'customer', 'active', '2025-06-24 10:06:21', '2025-06-24 10:06:21', NULL),
(3, 'Sari Tani', 'sari@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '081987654321', 'Jl. Pertanian No. 45, Bandung', 'customer', 'active', '2025-06-24 10:06:21', '2025-06-24 10:06:21', NULL),
(4, 'Ihsan Fahriza', 'fahrizaihsan06@gmail.com', 'ea1e2b66eb6f4d6a8c6d8555e1b46cea', '08981102887777', 'kisaran barat', 'customer', 'active', '2025-06-24 11:45:49', '2025-06-26 04:37:31', NULL),
(5, 'Admin', 'admin@sahabattani.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'admin', 'active', '2025-06-24 13:32:14', '2025-06-24 13:32:14', NULL),
(7, 'Aditya Ashari', 'adit@gmail.com', '$2y$10$7bu9.v33ZEQSPaQFMrjebOk63krITV1f8k3XPq53QHMshE.dQl9aC', '081234567890', 'Kisaran Barat', 'customer', 'inactive', '2025-06-26 11:08:42', '2025-06-26 11:09:20', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_notifications_user_id` (`user_id`),
  ADD KEY `idx_notifications_is_read` (`is_read`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_payment_status` (`payment_status`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_order_tracking_order_id` (`order_id`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indeks untuk tabel `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_order` (`user_id`,`product_id`,`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `review`
--
ALTER TABLE `review`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`);

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `produk` (`id`);

--
-- Ketidakleluasaan untuk tabel `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_tracking_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
