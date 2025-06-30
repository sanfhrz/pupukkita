<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    $_SESSION['error'] = 'Silakan login sebagai customer terlebih dahulu!';
    header('Location: login.php');
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: checkout.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get cart items
    $stmt = $pdo->prepare("
        SELECT k.*, p.nama_produk, p.harga, p.stok
        FROM keranjang k
        JOIN produk p ON k.produk_id = p.id
        WHERE k.user_id = ? AND p.status = 'active'
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    if (empty($cart_items)) {
        throw new Exception('Keranjang kosong!');
    }

    // Validate stock
    foreach ($cart_items as $item) {
        if ($item['jumlah'] > $item['stok']) {
            throw new Exception("Stok {$item['nama_produk']} tidak mencukupi!");
        }
    }

    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['harga'] * $item['jumlah'];
    }

    // Get form data
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $telepon = trim($_POST['telepon']);
    $email = trim($_POST['email']);
    $alamat = trim($_POST['alamat']);
    $provinsi = trim($_POST['provinsi']);
    $kota = trim($_POST['kota']);
    $kode_pos = trim($_POST['kode_pos']);
    $payment_method = $_POST['payment_method'];
    $catatan = trim($_POST['catatan'] ?? '');
    // Tambahkan di bagian get form data
    $province_id = $_POST['provinsi']; // Sekarang berisi ID
    $city_id = $_POST['kota']; // Sekarang berisi ID  
    $courier = $_POST['kurir'];
    $shipping_service = $_POST['layanan'];

    // Validate required fields
    if (
        empty($nama_lengkap) || empty($telepon) || empty($email) ||
        empty($alamat) || empty($provinsi) || empty($kota) || empty($kode_pos)
    ) {
        throw new Exception('Semua field wajib harus diisi!');
    }

    // Calculate shipping and fees
    $shipping_cost = 50000; // Default shipping cost
    $cod_fee = ($payment_method == 'cod') ? 5000 : 0;
    $total = $subtotal + $shipping_cost + $cod_fee;

    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id, order_number, nama_lengkap, telepon, email, alamat, 
            provinsi, kota, kode_pos, payment_method, subtotal, 
            shipping_cost, cod_fee, total, catatan, status, payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
    ");

    $stmt->execute([
        $user_id,
        $order_number,
        $nama_lengkap,
        $telepon,
        $email,
        $alamat,
        $provinsi,
        $kota,
        $kode_pos,
        $payment_method,
        $subtotal,
        $shipping_cost,
        $cod_fee,
        $total,
        $catatan
    ]);

    $order_id = $pdo->lastInsertId();

    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, nama_produk, harga, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        $item_subtotal = $item['harga'] * $item['jumlah'];
        $stmt->execute([
            $order_id,
            $item['produk_id'],
            $item['nama_produk'],
            $item['harga'],
            $item['jumlah'],
            $item_subtotal
        ]);

        // Update product stock
        $stmt_stock = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        $stmt_stock->execute([$item['jumlah'], $item['produk_id']]);
    }

    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Commit transaction
    $pdo->commit();

    // Set success message
    $_SESSION['success'] = 'Pesanan berhasil dibuat! Nomor pesanan: ' . $order_number;

    // Redirect to order success page
    header('Location: order_success.php?order=' . $order_number);
    exit();
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollback();

    $_SESSION['error'] = $e->getMessage();
    header('Location: checkout.php');
    exit();
}
