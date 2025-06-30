<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pupukstore');

// Konfigurasi Website
define('SITE_NAME', 'Sahabat Tani');
define('SITE_URL', 'http://localhost/pupukstore');
define('ADMIN_EMAIL', 'admin@pupukstore.com');

// Konfigurasi Upload
define('UPLOAD_PATH', 'assets/img/pupuk/');
define('UPLOAD_PATH_BUKTI', 'uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB

// Koneksi Database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk sanitasi input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk format rupiah
function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi untuk generate kode pesanan
function generate_kode_pesanan() {
    return 'PS' . date('Ymd') . rand(1000, 9999);
}

// Start session jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tambahkan di bagian bawah file config.php
// define('RAJAONGKIR_API_KEY', 'MDim9mft0c5b8f45bd3c789aPEnF1izV'); // Ganti dengan API key asli
// define('RAJAONGKIR_BASE_URL', 'https://api.rajaongkir.com/starter/');

?>