<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    echo json_encode(['success' => false, 'message' => 'Order number required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT status, payment_status, tracking_number, updated_at
        FROM orders 
        WHERE order_number = ? AND user_id = ?
    ");
    $stmt->execute([$order_number, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'status' => $order['status'],
        'payment_status' => $order['payment_status'],
        'tracking_number' => $order['tracking_number'],
        'updated_at' => $order['updated_at']
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>