<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create orders table
        $sql = "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT,
            payment_method VARCHAR(50),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        
        // Create order_items table
        $sql = "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES produk(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        
        // Update users table
        $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user' AFTER password";
        $pdo->exec($sql);
        
        // Update admin user
        $sql = "UPDATE users SET role = 'admin' WHERE email = 'admin@sahabattani.com'";
        $pdo->exec($sql);
        
        $success = true;
        
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - Sahabat Tani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-database me-2"></i>Database Setup</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Database berhasil disetup! Silakan kembali ke dashboard.
                            </div>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                            </a>
                                                    <?php elseif (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$success): ?>
                            <p>Setup ini akan membuat tabel yang diperlukan untuk dashboard admin:</p>
                            <ul>
                                <li><strong>orders</strong> - Tabel untuk menyimpan data pesanan</li>
                                <li><strong>order_items</strong> - Tabel untuk detail item pesanan</li>
                                <li><strong>role column</strong> - Menambah kolom role di tabel users</li>
                            </ul>
                            
                            <form method="POST">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-magic me-2"></i>Setup Database
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
