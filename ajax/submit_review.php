<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$order_id = clean_input($_POST['order_id']);
$rating = intval($_POST['rating']);
$review = clean_input($_POST['review']);
$user_id = $_SESSION['user_id'];

// Validasi
if (empty($order_id) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit();
}

try {
    // Cek apakah pesanan milik user dan sudah completed
    $stmt = $pdo->prepare("SELECT id FROM pesanan WHERE id = ? AND user_id = ? AND status = 'completed'");
    $stmt->execute([$order_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak valid']);
        exit();
    }
    
    // Cek apakah sudah pernah review
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah memberikan review untuk pesanan ini']);
        exit();
    }
    
    // Insert review
    $stmt = $pdo->prepare("INSERT INTO reviews (order_id, user_id, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$order_id, $user_id, $rating, $review]);
    
    echo json_encode(['success' => true, 'message' => 'Review berhasil dikirim']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>