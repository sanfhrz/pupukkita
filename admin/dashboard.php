<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
$stats = [
    'total_orders' => 0,
    'total_products' => 0,
    'total_customers' => 0,
    'total_revenue' => 0,
    'orders_today' => 0,
    'revenue_today' => 0,
    'orders_this_month' => 0,
    'revenue_this_month' => 0,
    'pending_orders' => 0,
    'low_stock_products' => 0
];

try {
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $result = $stmt->fetch();
    $stats['total_orders'] = $result['count'];

    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    $stats['total_products'] = $result['count'];

    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $result = $stmt->fetch();
    $stats['total_customers'] = $result['count'];

    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status IN ('confirmed', 'shipped', 'delivered')");
    $result = $stmt->fetch();
    $stats['total_revenue'] = $result['revenue'];

    // Orders today
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch();
    $stats['orders_today'] = $result['count'];

    // Revenue today
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('confirmed', 'shipped', 'delivered')");
    $result = $stmt->fetch();
    $stats['revenue_today'] = $result['revenue'];

    // Orders this month
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $result = $stmt->fetch();
    $stats['orders_this_month'] = $result['count'];

    // Revenue this month
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status IN ('confirmed', 'shipped', 'delivered')");
    $result = $stmt->fetch();
    $stats['revenue_this_month'] = $result['revenue'];

    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $result = $stmt->fetch();
    $stats['pending_orders'] = $result['count'];

    // Low stock products (stock <= 10)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stok <= 10");
    $result = $stmt->fetch();
    $stats['low_stock_products'] = $result['count'];
} catch (PDOException $e) {
    // Keep default stats
}

// Get recent orders
$recent_orders = [];
try {
    $stmt = $pdo->query("
            SELECT o.*, u.nama as customer_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
    $recent_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // Keep empty array
}

// Get top products
$top_products = [];
try {
    $stmt = $pdo->query("
            SELECT p.nama, p.gambar, COALESCE(SUM(oi.quantity), 0) as total_sold
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('confirmed', 'shipped', 'delivered')
            GROUP BY p.id, p.nama, p.gambar
            ORDER BY total_sold DESC
            LIMIT 5
        ");
    $top_products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Keep empty array
}

// Get low stock products
$low_stock = [];
try {
    $stmt = $pdo->query("
            SELECT nama, stok, gambar 
            FROM products 
            WHERE stok <= 10 
            ORDER BY stok ASC 
            LIMIT 5
        ");
    $low_stock = $stmt->fetchAll();
} catch (PDOException $e) {
    // Keep empty array
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sahabat Tani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8f9fa;
            color: #2c3e50;
        }

        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            margin: 0 10px;
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            margin: 0 10px;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #28a745, #20c997);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            border-bottom: 2px solid #e9ecef;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8f9fa;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }

        .product-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 2rem;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            transform: translateY(-1px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-number {
                font-size: 1.8rem;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #28a745;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #20c997;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-leaf me-2"></i>
                Sahabat Tani
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Kelola Pesanan
                    <?php if ($stats['pending_orders'] > 0): ?>
                        <span class="badge bg-warning ms-auto"><?php echo $stats['pending_orders']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="nav-item">
                <a href="products.php" class="nav-link">
                    <i class="fas fa-box"></i>
                    Kelola Produk
                    <?php if ($stats['low_stock_products'] > 0): ?>
                        <span class="badge bg-danger ms-auto"><?php echo $stats['low_stock_products']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="nav-item">
                <a href="promo_codes.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    Kode Promo
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Laporan
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    Data User
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="../admin/logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar animate-fade-in">
            <div>
                <h4 class="mb-0">Dashboard Admin</h4>
                <small class="text-muted">Selamat datang kembali, <?php echo htmlspecialchars($_SESSION['nama']); ?>! 👋</small>
            </div>
            <div>
                <span class="badge bg-success fs-6">
                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                    Online
                </span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in">
                    <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stats-label">Total Pesanan</div>
                    <div class="stats-change">
                        <span class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?php echo $stats['orders_today']; ?> hari ini
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_products']); ?></div>
                    <div class="stats-label">Total Produk</div>
                    <div class="stats-change">
                        <?php if ($stats['low_stock_products'] > 0): ?>
                            <span class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <?php echo $stats['low_stock_products']; ?> stok rendah
                            </span>
                        <?php else: ?>
                            <span class="text-success">
                                <i class="fas fa-check me-1"></i>
                                Stok aman
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_customers']); ?></div>
                    <div class="stats-label">Total Customer</div>
                    <div class="stats-change">
                        <span class="text-info">
                            <i class="fas fa-user-plus me-1"></i>
                            Aktif
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stats-number" style="font-size: 1.8rem;">
                        Rp <?php echo number_format($stats['total_revenue'] / 1000000, 1); ?>M
                    </div>
                    <div class="stats-label">Total Revenue</div>
                    <div class="stats-change">
                        <span class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            Rp <?php echo number_format($stats['revenue_today'], 0, ',', '.'); ?> hari ini
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="text-warning">
                            <i class="fas fa-clock me-2"></i>
                            <?php echo number_format($stats['pending_orders']); ?>
                        </h5>
                        <p class="mb-0 text-muted">Pesanan Pending</p>
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <a href="orders.php?status=pending" class="btn btn-sm btn-outline-warning mt-2">
                                Lihat Semua
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="text-primary">
                            <i class="fas fa-calendar-day me-2"></i>
                            <?php echo number_format($stats['orders_this_month']); ?>
                        </h5>
                        <p class="mb-0 text-muted">Pesanan Bulan Ini</p>
                        <small class="text-success">
                            Revenue: Rp <?php echo number_format($stats['revenue_this_month'], 0, ',', '.'); ?>
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo number_format($stats['low_stock_products']); ?>
                        </h5>
                        <p class="mb-0 text-muted">Produk Stok Rendah</p>
                        <?php if ($stats['low_stock_products'] > 0): ?>
                            <a href="products.php?low_stock=1" class="btn btn-sm btn-outline-danger mt-2">
                                Lihat Semua
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts & Tables Row -->
        <div class="row">
            <!-- Recent Orders -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-shopping-cart me-2"></i>
                            Pesanan Terbaru
                        </div>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                Belum ada pesanan
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['customer_name'] ?: 'Unknown'); ?></td>
                                                <td>
                                                    <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.');
                                                                ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_badges = [
                                                        'pending' => 'bg-warning',
                                                        'confirmed' => 'bg-info',
                                                        'shipped' => 'bg-primary',
                                                        'delivered' => 'bg-success',
                                                        'cancelled' => 'bg-danger'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $status_badges[$order['status']] ?? 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-star me-2"></i>
                        Produk Terlaris
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_products)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                Belum ada data penjualan
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_products as $index => $product): ?>
                                <div class="d-flex align-items-center mb-3 <?php echo $index === count($top_products) - 1 ? '' : 'border-bottom pb-3'; ?>">
                                    <div class="me-3">
                                        <?php if ($product['gambar'] && file_exists('../uploads/' . $product['gambar'])): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($product['gambar']); ?>"
                                                alt="<?php echo htmlspecialchars($product['nama']); ?>"
                                                class="product-img">
                                        <?php else: ?>
                                            <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['nama']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-shopping-cart me-1"></i>
                                            <?php echo number_format($product['total_sold']); ?> terjual
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">#<?php echo $index + 1; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <?php if (!empty($low_stock)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Peringatan Stok Rendah!</h5>
                                <p class="mb-0">Beberapa produk memiliki stok yang rendah dan perlu segera diisi ulang.</p>
                            </div>
                        </div>

                        <div class="row">
                            <?php foreach ($low_stock as $product): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <?php if ($product['gambar'] && file_exists('../uploads/' . $product['gambar'])): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($product['gambar']); ?>"
                                                            alt="<?php echo htmlspecialchars($product['nama']); ?>"
                                                            class="product-img">
                                                    <?php else: ?>
                                                        <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($product['nama']); ?></h6>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-danger me-2">
                                                            <?php echo $product['stok']; ?> tersisa
                                                        </span>
                                                        <div class="progress flex-grow-1" style="height: 6px;">
                                                            <div class="progress-bar bg-danger"
                                                                style="width: <?php echo min(($product['stok'] / 50) * 100, 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-end mt-3">
                            <a href="products.php?low_stock=1" class="btn btn-warning">
                                <i class="fas fa-boxes me-2"></i>
                                Kelola Stok Produk
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sales Chart -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i>
                        Grafik Penjualan 7 Hari Terakhir
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');

        // Generate sample data for last 7 days
        const last7Days = [];
        const salesData = [];
        const revenueData = [];

        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            last7Days.push(date.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short'
            }));

            // Sample data - replace with real data from database
            salesData.push(Math.floor(Math.random() * 20) + 5);
            revenueData.push(Math.floor(Math.random() * 5000000) + 1000000);
        }

        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: last7Days,
                datasets: [{
                    label: 'Jumlah Pesanan',
                    data: salesData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Revenue (Juta Rp)',
                    data: revenueData.map(val => val / 1000000),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 1) {
                                    return 'Revenue: Rp ' + (context.parsed.y * 1000000).toLocaleString('id-ID');
                                }
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#6c757d',
                            beginAtZero: true
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Pesanan',
                            color: '#28a745'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: '#6c757d',
                            beginAtZero: true,
                            callback: function(value) {
                                return 'Rp ' + value + 'M';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Revenue (Juta Rp)',
                            color: '#007bff'
                        }
                    },
                }
            }
        });

        // Real-time updates
        let updateInterval;

        function startRealTimeUpdates() {
            updateInterval = setInterval(async () => {
                try {
                    const response = await fetch('get_dashboard_stats.php');
                    const data = await response.json();

                    if (data.success) {
                        // Update stats if needed
                        if (data.hasNewOrders) {
                            showNotification('Ada pesanan baru!', 'success');
                        }

                        if (data.hasLowStock) {
                            showNotification('Ada produk dengan stok rendah!', 'warning');
                        }
                    }
                } catch (error) {
                    console.error('Error fetching updates:', error);
                }
            }, 60000); // Check every minute
        }

        function showNotification(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-bell me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation classes
            const cards = document.querySelectorAll('.stats-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';

                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });

            // Start real-time updates
            startRealTimeUpdates();
        });

        // Cleanup
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });

        // Mobile sidebar toggle (if needed)
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }
    </script>
</body>

</html>