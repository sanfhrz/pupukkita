<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Get date filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today
$period = $_GET['period'] ?? 'month';

// Sales Analytics
$sales_stats = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'avg_order_value' => 0,
    'completed_orders' => 0,
    'pending_orders' => 0,
    'cancelled_orders' => 0,
    'growth_rate' => 0
];

try {
    // Total orders in period
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $sales_stats['total_orders'] = $stmt->fetch()['count'];

    // Total revenue in period
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as revenue FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status IN ('confirmed', 'shipped', 'delivered')");
    $stmt->execute([$start_date, $end_date]);
    $sales_stats['total_revenue'] = $stmt->fetch()['revenue'];

    // Average order value
    if ($sales_stats['total_orders'] > 0) {
        $sales_stats['avg_order_value'] = $sales_stats['total_revenue'] / $sales_stats['total_orders'];
    }

    // Orders by status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
    $stmt->execute([$start_date, $end_date]);
    $status_data = $stmt->fetchAll();
    
    foreach ($status_data as $status) {
        switch ($status['status']) {
            case 'delivered':
            case 'confirmed':
            case 'shipped':
                $sales_stats['completed_orders'] += $status['count'];
                break;
            case 'pending':
                $sales_stats['pending_orders'] = $status['count'];
                break;
            case 'cancelled':
                $sales_stats['cancelled_orders'] = $status['count'];
                break;
        }
    }

    // Growth rate calculation (compare with previous period)
    $prev_start = date('Y-m-d', strtotime($start_date . ' -1 month'));
    $prev_end = date('Y-m-d', strtotime($end_date . ' -1 month'));
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as prev_revenue FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status IN ('confirmed', 'shipped', 'delivered')");
    $stmt->execute([$prev_start, $prev_end]);
    $prev_revenue = $stmt->fetch()['prev_revenue'];
    
    if ($prev_revenue > 0) {
        $sales_stats['growth_rate'] = (($sales_stats['total_revenue'] - $prev_revenue) / $prev_revenue) * 100;
    }

} catch (PDOException $e) {
    // Keep default values
}

// Daily sales data for chart (last 30 days)
$daily_sales = [];
try {
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders,
            COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND status IN ('confirmed', 'shipped', 'delivered')
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $daily_sales = $stmt->fetchAll();
} catch (PDOException $e) {
    $daily_sales = [];
}

// Top products
$top_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.nama_produk,
            p.gambar,
            p.harga,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COALESCE(SUM(oi.subtotal), 0) as total_revenue
        FROM produk p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id 
        WHERE o.status IN ('confirmed', 'shipped', 'delivered')
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.id, p.nama_produk, p.gambar, p.harga
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $top_products = [];
}

// Top customers
$top_customers = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.nama,
            u.email,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total), 0) as total_spent
        FROM users u
        JOIN orders o ON u.id = o.user_id
        WHERE o.status IN ('confirmed', 'shipped', 'delivered')
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY u.id, u.nama, u.email
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $top_customers = [];
}

// Monthly revenue trend (last 12 months)
$monthly_trend = [];
try {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as orders,
            COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        AND status IN ('confirmed', 'shipped', 'delivered')
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_trend = $stmt->fetchAll();
} catch (PDOException $e) {
    $monthly_trend = [];
}

// Product categories performance
$category_stats = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            k.nama_kategori,
            COUNT(DISTINCT p.id) as total_products,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COALESCE(SUM(oi.subtotal), 0) as total_revenue
        FROM kategori k
        LEFT JOIN produk p ON k.id = p.kategori_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.status IN ('confirmed', 'shipped', 'delivered')
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY k.id, k.nama_kategori
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $category_stats = $stmt->fetchAll();
} catch (PDOException $e) {
    $category_stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sahabat Tani</title>
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
            height: 350px;
            margin-bottom: 1rem;
        }
        .progress {
            height: 8px;
            border-radius:            10px;
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
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .export-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
            .export-buttons {
                justify-content: center;
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
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Kelola Pesanan
                </a>
            </div>
            <div class="nav-item">
                <a href="products.php" class="nav-link">
                    <i class="fas fa-box"></i>
                    Kelola Produk
                </a>
            </div>
            <div class="nav-item">
                <a href="promo_codes.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    Kode Promo
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link active">
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
                <h4 class="mb-0">📊 Laporan & Analytics</h4>
                <small class="text-muted">Analisis performa bisnis dan penjualan</small>
            </div>
            <div class="export-buttons">
                <button class="btn btn-outline-success btn-sm" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf me-1"></i> Export PDF
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section animate-fade-in">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select class="form-select" name="period">
                        <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Harian</option>
                        <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Mingguan</option>
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Bulanan</option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Tahunan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in">
                    <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($sales_stats['total_orders']); ?></div>
                    <div class="stats-label">Total Pesanan</div>
                    <div class="stats-change">
                        <span class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            <?php echo number_format($sales_stats['completed_orders']); ?> selesai
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stats-number" style="font-size: 1.8rem;">
                        Rp <?php echo number_format($sales_stats['total_revenue'] / 1000000, 1); ?>M
                    </div>
                    <div class="stats-label">Total Revenue</div>
                    <div class="stats-change">
                        <?php if ($sales_stats['growth_rate'] >= 0): ?>
                            <span class="text-success">
                                <i class="fas fa-arrow-up me-1"></i>
                                +<?php echo number_format($sales_stats['growth_rate'], 1); ?>%
                            </span>
                        <?php else: ?>
                            <span class="text-danger">
                                <i class="fas fa-arrow-down me-1"></i>
                                <?php echo number_format($sales_stats['growth_rate'], 1); ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stats-number" style="font-size: 1.5rem;">
                        Rp <?php echo number_format($sales_stats['avg_order_value'], 0, ',', '.'); ?>
                    </div>
                    <div class="stats-label">Rata-rata Pesanan</div>
                    <div class="stats-change">
                        <span class="text-info">
                            <i class="fas fa-calculator me-1"></i>
                            Per transaksi
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($sales_stats['pending_orders']); ?></div>
                    <div class="stats-label">Pesanan Pending</div>
                    <div class="stats-change">
                        <span class="text-warning">
                            <i class="fas fa-hourglass-half me-1"></i>
                            Perlu diproses
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Sales Trend Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-chart-line me-2"></i>
                            Tren Penjualan Harian (30 Hari Terakhir)
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" onclick="switchChart('daily')">Harian</button>
                            <button type="button" class="btn btn-outline-primary" onclick="switchChart('monthly')">Bulanan</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Status Pie Chart -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i>
                        Status Pesanan
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-success fw-bold"><?php echo $sales_stats['completed_orders']; ?></div>
                                    <small class="text-muted">Selesai</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-warning fw-bold"><?php echo $sales_stats['pending_orders']; ?></div>
                                    <small class="text-muted">Pending</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-danger fw-bold"><?php echo $sales_stats['cancelled_orders']; ?></div>
                                    <small class="text-muted">Dibatalkan</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue Trend -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-area me-2"></i>
                        Tren Revenue Bulanan (12 Bulan Terakhir)
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products & Customers -->
        <div class="row">
            <!-- Top Products -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-trophy me-2"></i>
                            Produk Terlaris
                        </div>
                                                <span class="badge bg-success">Top 10</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Terjual</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_products)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Belum ada data produk</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_products as $index => $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <span class="badge bg-primary rounded-pill"><?php echo $index + 1; ?></span>
                                                        </div>
                                                        <?php if ($product['gambar']): ?>
                                                            <img src="../assets/img/pupuk/<?php echo htmlspecialchars($product['gambar']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($product['nama_produk']); ?>" 
                                                                 class="product-img me-3">
                                                        <?php else: ?>
                                                            <div class="product-img me-3 bg-light d-flex align-items-center justify-content-center">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($product['nama_produk']); ?></div>
                                                            <small class="text-muted">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo number_format($product['total_sold']); ?></span>
                                                </td>
                                                <td>
                                                    <strong>Rp <?php echo number_format($product['total_revenue'], 0, ',', '.'); ?></strong>
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

            <!-- Top Customers -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-users me-2"></i>
                            Customer Terbaik
                        </div>
                        <span class="badge bg-info">Top 10</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Orders</th>
                                        <th>Total Belanja</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_customers)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Belum ada data customer</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_customers as $index => $customer): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <span class="badge bg-info rounded-pill"><?php echo $index + 1; ?></span>
                                                        </div>
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                                             style="width: 40px; height: 40px; font-size: 0.9rem;">
                                                            <?php echo strtoupper(substr($customer['nama'], 0, 2)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($customer['nama']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning"><?php echo number_format($customer['total_orders']); ?></span>
                                                </td>
                                                <td>
                                                    <strong>Rp <?php echo number_format($customer['total_spent'], 0, ',', '.'); ?></strong>
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
        </div>

        <!-- Category Performance -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-tags me-2"></i>
                        Performa Kategori Produk
                    </div>
                    <div class="card-body">
                        <?php if (empty($category_stats)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum ada data kategori</h5>
                                <p class="text-muted">Data akan muncul setelah ada transaksi</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php 
                                $max_revenue = max(array_column($category_stats, 'total_revenue'));
                                foreach ($category_stats as $category): 
                                    $percentage = $max_revenue > 0 ? ($category['total_revenue'] / $max_revenue) * 100 : 0;
                                ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($category['nama_kategori']); ?></h6>
                                            <small class="text-muted"><?php echo number_format($category['total_products']); ?> produk</small>
                                        </div>
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">
                                                <i class="fas fa-shopping-cart me-1"></i>
                                                <?php echo number_format($category['total_sold']); ?> terjual
                                            </small>
                                            <small class="fw-bold text-success">
                                                Rp <?php echo number_format($category['total_revenue'], 0, ',', '.'); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="orders.php?status=pending" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-clock me-2"></i>
                                    Pesanan Pending
                                    <span class="badge bg-warning ms-2"><?php echo $sales_stats['pending_orders']; ?></span>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="products.php?stok_rendah=1" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Stok Rendah
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="products.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus me-2"></i>
                                    Tambah Produk
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="promo_codes.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-tags me-2"></i>
                                    Kelola Promo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart.js Configuration
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = '#6c757d';

        // Daily Sales Chart
        const dailySalesCtx = document.getElementById('salesChart').getContext('2d');
        const dailySalesData = <?php echo json_encode($daily_sales); ?>;
        
        const salesChart = new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: dailySalesData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('id-ID', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue (Rp)',
                    data: dailySalesData.map(item => item.revenue),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }, {
                    label: 'Jumlah Pesanan',
                    data: dailySalesData.map(item => item.orders),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#007bff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return 'Revenue: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                } else {
                                    return 'Pesanan: ' + context.parsed.y;
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
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
                            callback: function(value) {
                                return value + ' orders';
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Order Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Pending', 'Dibatalkan'],
                datasets: [{
                    data: [                        <?php echo $sales_stats['completed_orders']; ?>,
                        <?php echo $sales_stats['pending_orders']; ?>,
                        <?php echo $sales_stats['cancelled_orders']; ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });

        // Monthly Revenue Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_trend); ?>;
        
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => {
                    const [year, month] = item.month.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short' });
                }),
                datasets: [{
                    label: 'Revenue (Rp)',
                    data: monthlyData.map(item => item.revenue),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }, {
                    label: 'Jumlah Pesanan',
                    data: monthlyData.map(item => item.orders),
                    type: 'line',
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#007bff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return 'Revenue: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                } else {
                                    return 'Pesanan: ' + context.parsed.y;
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
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
                            callback: function(value) {
                                return value + ' orders';
                            }
                        }
                    }
                }
            }
        });

        // Chart switching function
        function switchChart(type) {
            // Update active button
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Here you can implement different chart data loading
            // For now, we'll just show a loading state
            console.log('Switching to ' + type + ' view');
        }

        // Export functions
        function exportReport(format) {
            const startDate = '<?php echo $start_date; ?>';
            const endDate = '<?php echo $end_date; ?>';
            
            // Show loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Exporting...';
            btn.disabled = true;

            // Simulate export process
            setTimeout(() => {
                if (format === 'pdf') {
                    // In real implementation, you would call a PHP script to generate PDF
                    window.open(`export_report.php?format=pdf&start_date=${startDate}&end_date=${endDate}`, '_blank');
                } else if (format === 'excel') {
                    // In real implementation, you would call a PHP script to generate Excel
                    window.open(`export_report.php?format=excel&start_date=${startDate}&end_date=${endDate}`, '_blank');
                }

                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        }

        // Auto refresh data every 5 minutes
        setInterval(() => {
            // In real implementation, you would fetch new data via AJAX
            console.log('Auto refreshing data...');
        }, 300000);

        // Animate numbers on page load
        function animateNumbers() {
            const numbers = document.querySelectorAll('.stats-number');
            numbers.forEach(number => {
                const target = parseInt(number.textContent.replace(/[^\d]/g, ''));
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    
                    // Format number based on original format
                    if (number.textContent.includes('M')) {
                        number.textContent = 'Rp ' + (current / 1000000).toFixed(1) + 'M';
                    } else if (number.textContent.includes('Rp')) {
                        number.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.floor(current));
                    } else {
                        number.textContent = new Intl.NumberFormat('id-ID').format(Math.floor(current));
                    }
                }, 20);
            });
        }

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to cards
            const cards = document.querySelectorAll('.stats-card, .card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animate numbers after a short delay
            setTimeout(animateNumbers, 500);
        });

        // Responsive sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth <= 768) {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
            }
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.querySelector('.sidebar').style.transform = 'translateX(0px)';
            }
        });

        // Print optimization
        window.addEventListener('beforeprint', function() {
            // Hide sidebar and adjust layout for printing
            document.querySelector('.sidebar').style.display = 'none';
            document.querySelector('.main-content').style.marginLeft = '0';
            document.querySelector('.export-buttons').style.display = 'none';
        });

        window.addEventListener('afterprint', function() {
            // Restore layout after printing
            document.querySelector('.sidebar').style.display = 'block';
            document.querySelector('.main-content').style.marginLeft = '250px';
            document.querySelector('.export-buttons').style.display = 'flex';
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + P for print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Ctrl + E for export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportReport('excel');
            }
        });

        // Tooltip initialization
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Success message for actions
        function showSuccessMessage(message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Error handling for charts
        Chart.defaults.plugins.legend.onHover = function(event, legendItem, legend) {
            legend.chart.canvas.style.cursor = 'pointer';
        };

        Chart.defaults.plugins.legend.onLeave = function(event, legendItem, legend) {
            legend.chart.canvas.style.cursor = 'default';
        };

        // Add loading states for better UX
        function showLoading(element) {
            element.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Loading...</p></div>';
        }

        // Real-time updates simulation
        function simulateRealTimeUpdates() {
            // This would be replaced with actual WebSocket or polling in production
            setInterval(() => {
                // Randomly update some stats to simulate real-time data
                const pendingOrders = document.querySelector('.stats-card:nth-child(4) .stats-number');
                if (pendingOrders && Math.random() > 0.8) {
                    const currentValue = parseInt(pendingOrders.textContent);
                    const change = Math.random() > 0.5 ? 1 : -1;
                    const newValue = Math.max(0, currentValue + change);
                    pendingOrders.textContent = newValue.toString();
                    
                    // Show notification
                    if (change > 0) {
                        showSuccessMessage('Pesanan baru masuk!');
                    }
                }
            }, 30000); // Check every 30 seconds
        }

        // Initialize real-time updates
        simulateRealTimeUpdates();
    </script>
</body>
</html>