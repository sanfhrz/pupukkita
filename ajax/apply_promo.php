<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

$promo_code = clean_input($_POST['promo_code']);
$user_id = $_SESSION['user_id'];

if (empty($promo_code)) {
    echo json_encode(['success' => false, 'message' => 'Kode promo tidak boleh kosong']);
    exit();
}

try {
    // Cek apakah kode promo valid (contoh sederhana)
    $valid_promos = [
        'PUPUK10' => ['discount' => 10, 'type' => 'percent', 'min_purchase' => 100000],
        'HEMAT50' => ['discount' => 50000, 'type' => 'fixed', 'min_purchase' => 500000],
        'NEWBIE15' => ['discount' => 15, 'type' => 'percent', 'min_purchase' => 200000]
    ];
    
    if (!isset($valid_promos[$promo_code])) {
        echo json_encode(['success' => false, 'message' => 'Kode promo tidak valid']);
        exit();
    }
    
    $promo = $valid_promos[$promo_code];
    
    // Hitung total belanja
    $stmt = $pdo->prepare("SELECT SUM(p.harga * k.jumlah) as total 
                           FROM keranjang k 
                           JOIN produk p ON k.produk_id = p.id 
                           WHERE k.user_id = ?");
    $stmt->execute([$user_id]);
    $total_belanja = $stmt->fetch()['total'] ?? 0;
    
    if ($total_belanja < $promo['min_purchase']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum pembelian untuk kode ini adalah ' . format_rupiah($promo['min_purchase'])
        ]);
        exit();
    }
    
    // Simpan promo ke session
    $_SESSION['applied_promo'] = [
        'code' => $promo_code,
        'discount' => $promo['discount'],
        'type' => $promo['type']
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Kode promo berhasil diterapkan',
        'discount' => $promo['discount'],
        'type' => $promo['type']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>