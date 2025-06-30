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

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Wishlist berhasil dikosongkan']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>