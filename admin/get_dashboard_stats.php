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
    // Get current stats
    $stats = [];
    
    // Check for new orders since last check
    $lastCheck = $_SESSION['last_dashboard_check'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as new_orders FROM orders WHERE created_at > ?");
    $stmt->execute([$lastCheck]);
    $result = $stmt->fetch();
    $hasNewOrders = $result['new_orders'] > 0;
    
    // Check for low stock products
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stok <= 10");
    $result = $stmt->fetch();
    $hasLowStock = $result['low_stock'] > 0;
    
    // Get pending orders count
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'");
    $result = $stmt->fetch();
    $pendingOrders = $result['pending'];
    
    // Get today's stats
    $stmt = $pdo->query("SELECT COUNT(*) as orders_today FROM orders WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch();
    $ordersToday = $result['orders_today'];
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue_today FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('confirmed', 'shipped', 'delivered')");
    $result = $stmt->fetch();
    $revenueToday = $result['revenue_today'];
    
    // Update last check time
    $_SESSION['last_dashboard_check'] = date('Y-m-d H:i:s');
    
    echo json_encode([
        'success' => true,
        'hasNewOrders' => $hasNewOrders,
        'hasLowStock' => $hasLowStock,
        'pendingOrders' => $pendingOrders,
        'ordersToday' => $ordersToday,
        'revenueToday' => $revenueToday,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>