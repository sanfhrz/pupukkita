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
$user_id = $_SESSION['user_id'];

if (empty($order_number)) {
    echo json_encode(['success' => false, 'message' => 'Order number required']);
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
    
    // Check if order can be confirmed as received
    if ($order['status'] !== 'shipped') {
        echo json_encode(['success' => false, 'message' => 'Order cannot be confirmed at this stage']);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update order status to delivered
        $update_stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'delivered', 
                delivered_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $update_stmt->execute([$order['id']]);
        
        // Add tracking record
        $tracking_stmt = $pdo->prepare("
            INSERT INTO order_tracking (order_id, status, title, description, created_by) 
            VALUES (?, 'delivered', 'Pesanan Diterima', 'Pesanan telah dikonfirmasi diterima oleh pelanggan', ?)
        ");
        $tracking_stmt->execute([$order['id'], $user_id]);
        
        // Create notification for admin
        $notif_stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, order_id, type, title, message) 
            SELECT id, ?, 'order', 'Pesanan Diterima', CONCAT('Pesanan #', ?, ' telah dikonfirmasi diterima') 
            FROM users WHERE role = 'admin'
        ");
        $notif_stmt->execute([$order['id'], $order_number]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Order confirmed as received successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
