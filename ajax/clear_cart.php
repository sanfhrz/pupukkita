<?php
require_once '../includes/config.php';

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    $_SESSION['error'] = 'Silakan login terlebih dahulu!';
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $_SESSION['success'] = 'Keranjang berhasil dikosongkan!';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Terjadi kesalahan sistem!';
}

header('Location: ../keranjang.php');
exit();
?>