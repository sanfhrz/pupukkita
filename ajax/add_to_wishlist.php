<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak valid']);
    exit();
}

try {
    // Check if product exists and active
    $stmt = $pdo->prepare("SELECT id FROM produk WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
        exit();
    }
    
    // Check if already in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    
    if ($stmt->fetch()) {
        // Remove from wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Produk dihapus dari wishlist', 'action' => 'removed']);
        } else {
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Produk ditambahkan ke wishlist', 'action' => 'added']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>
