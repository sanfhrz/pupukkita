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
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.product_id, oi.quantity, p.nama_produk, p.stok, p.status
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN produk p ON oi.product_id = p.id
        WHERE o.order_number = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_number, $user_id]);
    $items = $stmt->fetchAll();
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Order not found or no items']);
        exit();
    }
    
    $added_items = 0;
    $unavailable_items = [];
    
    foreach ($items as $item) {
        // Check if product is still available
        if ($item['status'] !== 'active') {
            $unavailable_items[] = $item['nama_produk'] . ' (tidak tersedia)';
            continue;
        }
        
        // Check stock
        if ($item['stok'] < $item['quantity']) {
            $unavailable_items[] = $item['nama_produk'] . ' (stok tidak cukup)';
            continue;
        }
        
        // Check if item already in cart
        $check_stmt = $pdo->prepare("
            SELECT id, jumlah FROM keranjang 
            WHERE user_id = ? AND produk_id = ?
        ");
        $check_stmt->execute([$user_id, $item['product_id']]);
        $existing = $check_stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $new_quantity = min($existing['jumlah'] + $item['quantity'], $item['stok']);
            $update_stmt = $pdo->prepare("
                UPDATE keranjang 
                SET jumlah = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->execute([$new_quantity, $existing['id']]);
        } else {
            // Add new item
            $insert_stmt = $pdo->prepare("
                INSERT INTO keranjang (user_id, produk_id, jumlah) 
                VALUES (?, ?, ?)
            ");
            $insert_stmt->execute([$user_id, $item['product_id'], $item['quantity']]);
        }
        
        $added_items++;
    }
    
    $message = "$added_items item berhasil ditambahkan ke keranjang";
    if (!empty($unavailable_items)) {
        $message .= ". Beberapa item tidak dapat ditambahkan: " . implode(', ', $unavailable_items);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'added_items' => $added_items,
        'unavailable_items' => count($unavailable_items)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>