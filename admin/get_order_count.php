<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set content type
header('Content-Type: application/json');

try {
    // Get current order count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $result = $stmt->fetch();
    $currentCount = $result['total'];
    
    // Check if there are new orders (you can implement this based on your needs)
    // For now, we'll just return the current count
    $lastKnownCount = $_SESSION['last_order_count'] ?? 0;
    $hasNewOrders = $currentCount > $lastKnownCount;
    
    // Update session
    $_SESSION['last_order_count'] = $currentCount;
    
    // Get pending orders count
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'");
    $result = $stmt->fetch();
    $pendingCount = $result['pending'];
    
    echo json_encode([
        'success' => true,
        'totalOrders' => $currentCount,
        'pendingOrders' => $pendingCount,
        'hasNewOrders' => $hasNewOrders
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>