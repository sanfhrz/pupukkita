<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$order_number = $_POST['order_number'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($order_number) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Order number and reason required']);
    exit();
}

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT id, status FROM orders 
        WHERE order_number = ? AND user_id = ?
    ");
    $stmt->execute([$order_number, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Check if order can be cancelled
    if (!in_array($order['status'], ['pending', 'payment_uploaded', 'confirmed'])) {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update order status to cancelled
        $update_stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled', 
                cancelled_at = NOW(),
                cancellation_reason = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update_stmt->execute([$reason, $order['id']]);
        
        // Restore product stock
        $items_stmt = $pdo->prepare("
            SELECT product_id, quantity FROM order_items WHERE order_id = ?
        ");
        $items_stmt->execute([$order['id']]);
        $items = $items_stmt->fetchAll();
        
        foreach ($items as $item) {
            $stock_stmt = $pdo->prepare("
                UPDATE produk SET stok = stok + ? WHERE id = ?
            ");
            $stock_stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Add tracking record
        $tracking_stmt = $pdo->prepare("
            INSERT INTO order_tracking (order_id, status, title, description, created_by) 
            VALUES (?, 'cancelled', 'Pesanan Dibatalkan', ?, ?)
        ");
        $tracking_stmt->execute([$order['id'], 'Dibatalkan oleh pelanggan: ' . $reason, $user_id]);
        
        // Create notification for admin
        $notif_stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, order_id, type, title, message) 
            SELECT id, ?, 'order', 'Pesanan Dibatalkan', CONCAT('Pesanan #', ?, ' telah dibatalkan oleh pelanggan') 
            FROM users WHERE role = 'admin'
        ");
        $notif_stmt->execute([$order['id'], $order_number]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>