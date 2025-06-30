<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Get filter parameters (sama seperti di orders.php)
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.nama LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_from) {
    $where_conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

try {
    $query = "SELECT o.*, u.nama, u.email, u.no_hp 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              $where_clause 
              ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Set headers for CSV download
    $filename = 'orders_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    fputcsv($output, [
        'ID Pesanan',
        'Nama Pelanggan',
        'Email',
        'No. HP',
        'Tanggal Pesanan',
        'Status',
        'Total Amount',
        'Catatan Admin'
    ]);
    
    // CSV data
    foreach ($orders as $order) {
        $status_text = '';
        switch ($order['status']) {
            case 'pending': $status_text = 'Pending'; break;
            case 'confirmed': $status_text = 'Dikonfirmasi'; break;
            case 'shipped': $status_text = 'Dikirim'; break;
            case 'delivered': $status_text = 'Selesai'; break;
            case 'cancelled': $status_text = 'Dibatalkan'; break;
            default: $status_text = ucfirst($order['status']);
        }
        
        fputcsv($output, [
            '#' . str_pad($order['id'], 4, '0', STR_PAD_LEFT),
            $order['nama'],
            $order['email'],
            $order['no_hp'] ?? '-',
            date('d/m/Y H:i', strtotime($order['created_at'])),
            $status_text,
            'Rp ' . number_format($order['total_amount'], 0, ',', '.'),
            $order['admin_notes'] ?? '-'
        ]);
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>