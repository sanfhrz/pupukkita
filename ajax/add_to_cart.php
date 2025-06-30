<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit();
}

try {
    // Cek produk exists dan stok
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    $produk = $stmt->fetch();
    
    if (!$produk) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
        exit();
    }
    
    if ($produk['stok'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
        exit();
    }
    
    // Cek apakah produk sudah ada di keranjang
    $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE user_id = ? AND produk_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update quantity
        $new_quantity = $existing['jumlah'] + $quantity;
        if ($new_quantity > $produk['stok']) {
            echo json_encode(['success' => false, 'message' => 'Total quantity melebihi stok tersedia']);
            exit();
        }
        
        $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $existing['id']]);
    } else {
        // Insert baru
        $stmt = $pdo->prepare("INSERT INTO keranjang (user_id, produk_id, jumlah) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
    
    // Hitung total item di keranjang
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM keranjang WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Produk berhasil ditambahkan ke keranjang',
        'cart_count' => $cart_count
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>