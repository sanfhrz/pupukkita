-- Database: pupukstore
-- Dibuat untuk Website Penjualan Pupuk

-- ========================================
-- 1. TABEL USERS (Pembeli & Admin)
-- ========================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_hp VARCHAR(20),
    alamat TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- 2. TABEL KATEGORI PUPUK
-- ========================================
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- 3. TABEL PRODUK PUPUK
-- ========================================
CREATE TABLE produk (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_produk VARCHAR(100) NOT NULL,
    kategori_id INT,
    brand VARCHAR(50),
    jenis_pupuk ENUM('organik', 'anorganik', 'hayati') NOT NULL,
    berat VARCHAR(20) NOT NULL, -- contoh: 1kg, 5kg, 25kg
    harga DECIMAL(10,2) NOT NULL,
    stok INT DEFAULT 0,
    deskripsi TEXT,
    manfaat TEXT,
    cara_pakai TEXT,
    gambar VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    is_featured BOOLEAN DEFAULT FALSE, -- untuk produk unggulan
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

-- ========================================
-- 4. TABEL KERANJANG BELANJA
-- ========================================
CREATE TABLE keranjang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    produk_id INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
);

-- ========================================
-- 5. TABEL PESANAN
-- ========================================
CREATE TABLE pesanan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    kode_pesanan VARCHAR(20) UNIQUE NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    ongkir DECIMAL(10,2) DEFAULT 0,
    total_bayar DECIMAL(10,2) NOT NULL,
    status_pesanan ENUM('pending', 'dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') DEFAULT 'pending',
    metode_pembayaran ENUM('transfer_bank', 'cod', 'ewallet') NOT NULL,
    bukti_pembayaran VARCHAR(255),
    nama_penerima VARCHAR(100) NOT NULL,
    alamat_pengiriman TEXT NOT NULL,
    no_hp_penerima VARCHAR(20) NOT NULL,
    catatan TEXT,
    tanggal_pesan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_konfirmasi TIMESTAMP NULL,
    tanggal_kirim TIMESTAMP NULL,
    tanggal_selesai TIMESTAMP NULL
);

-- ========================================
-- 6. TABEL DETAIL PESANAN
-- ========================================
CREATE TABLE detail_pesanan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pesanan_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(100) NOT NULL, -- simpan nama saat itu juga
    harga DECIMAL(10,2) NOT NULL, -- simpan harga saat itu juga
    jumlah INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
);

-- ========================================
-- 7. TABEL REVIEW PRODUK (OPSIONAL)
-- ========================================
CREATE TABLE review (
    id INT PRIMARY KEY AUTO_INCREMENT,
    produk_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- INSERT SAMPLE DATA
-- ========================================

-- Insert Admin User
INSERT INTO users (nama, email, password, role) VALUES 
('Admin Pupuk Store', 'admin@pupukstore.com', MD5('admin123'), 'admin');

-- Insert Sample Customer
INSERT INTO users (nama, email, password, no_hp, alamat) VALUES 
('Budi Petani', 'budi@gmail.com', MD5('123456'), '081234567890', 'Jl. Sawah Indah No. 123, Bogor'),
('Sari Tani', 'sari@gmail.com', MD5('123456'), '081987654321', 'Jl. Pertanian No. 45, Bandung');

-- Insert Kategori
INSERT INTO kategori (nama_kategori, deskripsi) VALUES 
('Pupuk Dasar', 'Pupuk untuk kebutuhan dasar tanaman'),
('Pupuk Buah', 'Pupuk khusus untuk tanaman buah'),
('Pupuk Sayur', 'Pupuk untuk tanaman sayuran'),
('Pupuk Padi', 'Pupuk khusus untuk tanaman padi'),
('Pupuk Hias', 'Pupuk untuk tanaman hias');

-- Insert Sample Produk
INSERT INTO produk (nama_produk, kategori_id, brand, jenis_pupuk, berat, harga, stok, deskripsi, manfaat, cara_pakai, gambar, is_featured) VALUES 
('NPK Phonska 25kg', 1, 'Petrokimia', 'anorganik', '25kg', 85000.00, 50, 'Pupuk NPK lengkap untuk semua jenis tanaman', 'Meningkatkan pertumbuhan dan hasil panen', 'Taburkan 2-3 sendok makan per tanaman', 'npk_phonska.jpg', TRUE),
('Pupuk Kandang Sapi 10kg', 1, 'Organik Nusantara', 'organik', '10kg', 25000.00, 100, 'Pupuk organik dari kotoran sapi yang sudah difermentasi', 'Memperbaiki struktur tanah dan kesuburan', 'Campurkan dengan tanah 1:3', 'kandang_sapi.jpg', TRUE),
('Urea 50kg', 1, 'Petrokimia', 'anorganik', '50kg', 165000.00, 30, 'Pupuk nitrogen untuk pertumbuhan daun', 'Mempercepat pertumbuhan vegetatif', 'Aplikasi 1-2 minggu sekali', 'urea_50kg.jpg', FALSE),
('Pupuk Buah Gandasil D 1kg', 2, 'Gandasil', 'anorganik', '1kg', 35000.00, 75, 'Pupuk khusus untuk tanaman buah-buahan', 'Meningkatkan kualitas dan kuantitas buah', 'Larutkan 1 sendok teh dalam 1 liter air', 'gandasil_d.jpg', TRUE),
('Kompos Organik 5kg', 1, 'Green Kompos', 'organik', '5kg', 15000.00, 200, 'Kompos organik berkualitas tinggi', 'Memperbaiki kesuburan tanah secara alami', 'Campurkan dengan media tanam', 'kompos_organik.jpg', FALSE);

-- Insert Sample Keranjang (untuk testing)
INSERT INTO keranjang (user_id, produk_id, jumlah) VALUES 
(2, 1, 2),
(2, 4, 1);

-- Insert Sample Pesanan
INSERT INTO pesanan (user_id, kode_pesanan, total_harga, ongkir, total_bayar, metode_pembayaran, nama_penerima, alamat_pengiriman, no_hp_penerima) VALUES 
(2, 'PS001', 205000.00, 15000.00, 220000.00, 'transfer_bank', 'Budi Petani', 'Jl. Sawah Indah No. 123, Bogor', '081234567890');

-- Insert Detail Pesanan
INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, harga, jumlah, subtotal) VALUES 
(1, 1, 'NPK Phonska 25kg', 85000.00, 2, 170000.00),
(1, 4, 'Pupuk Buah Gandasil D 1kg', 35000.00, 1, 35000.00);