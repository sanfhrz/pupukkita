<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid order ID');
}

$orderId = (int)$_GET['id'];

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.nama as customer_name, u.email as customer_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die('Order not found');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.nama as product_name 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Order - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #28a745;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
        }
        
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .order-info div {
            flex: 1;
        }
        
        .order-info h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .total-section {
            text-align: right;
            margin-bottom: 30px;
        }
        
        .total-row {
            margin-bottom: 5px;
        }
        
        .total-final {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        
        @media print {
            body {
                margin: 0;
            }
            
            .container {
                max-width: none;
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .print-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="print-btn no-print" onclick="window.print()">🖨️ Print Invoice</button>
        
        <div class="header">
            <h1>🌱 SAHABAT TANI</h1>
            <p>Toko Pertanian Terpercaya</p>
            <p>Email: info@sahabattani.com | Phone: (021) 123-4567</p>
        </div>
        
        <div class="order-info">
            <div>
                <h3>INVOICE TO:</h3>
                <strong><?php echo htmlspecialchars($order['customer_name'] ?: 'Unknown Customer'); ?></strong><br>
                <?php echo htmlspecialchars($order['customer_email'] ?: 'No email'); ?><br>
                <?php if ($order['shipping_address']): ?>
                    <br><strong>Alamat Pengiriman:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                <?php endif; ?>
            </div>
            
            <div>
                <h3>ORDER DETAILS:</h3>
                <strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?><br>
                <strong>Order Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?><br>
                <strong>Status:</strong> <?php echo strtoupper($order['status']); ?><br>
                <?php if ($order['payment_method']): ?>
                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?><br>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>No