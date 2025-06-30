<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get analytics data
$analytics = [];

// Total promo codes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM promo_codes");
$analytics['total_promos'] = $stmt->fetch()['total'];

// Active promo codes
$stmt = $pdo->query("SELECT COUNT(*) as active FROM promo_codes WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())");
$analytics['active_promos'] = $stmt->fetch()['active'];

// Expired promo codes
$stmt = $pdo->query("SELECT COUNT(*) as expired FROM promo_codes WHERE end_date < CURDATE() AND end_date IS NOT NULL");
$analytics['expired_promos'] = $stmt->fetch()['expired'];

// Total usage
$stmt = $pdo->query("SELECT SUM(used_count) as total_usage FROM promo_codes");
$analytics['total_usage'] = $stmt->fetch()['total_usage'] ?: 0;

// Most used promo codes
$stmt = $pdo->query("SELECT code, description, used_count FROM promo_codes WHERE used_count > 0 ORDER BY used_count DESC LIMIT 10");
$analytics['most_used'] = $stmt->fetchAll();

// Recent promo codes
$stmt = $pdo->query("SELECT code, description, created_at FROM promo_codes ORDER BY created_at DESC LIMIT 5");
$analytics['recent_promos'] = $stmt->fetchAll();

// Promo types distribution
$stmt = $pdo->query("SELECT type, COUNT(*) as count FROM promo_codes GROUP BY type");
$analytics['type_distribution'] = $stmt->fetchAll();

// Monthly usage statistics (last 6 months)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM promo_codes 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
$analytics['monthly_stats'] = $stmt->fetchAll();

// Return JSON for AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode($analytics);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Promo - Admin PupukKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white p-0">
                <div class="d-flex flex-column min-vh-100">
                    <div class="p-3">
                        <h5><i class="fas fa-leaf"></i> PupukKita Admin</h5>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link text-white" href="products.php">
                            <i class="fas fa-box"></i> Kelola Produk
                        </a>
                        <a class="nav-link text-white" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Kelola Pesanan
                        </a>
                        <a class="nav-link text-white" href="categories.php">
                            <i class="fas fa-tags"></i> Kategori
                        </a>
                        <a class="nav-link text-white" href="users.php">
                            <i class="fas fa-users"></i> Pengguna
                        </a>
                        <a class="nav-link text-white" href="promo_codes.php">
                            <i class="fas fa-percent"></i> Kode Promo
                        </a>
                        <a class="nav-link text-white active bg-primary" href="promo_analytics.php">
                            <i class="fas fa-chart-bar"></i> Analytics Promo
                        </a>
                    </nav>
                    <div class="mt-auto p-3">
                        <a href="../logout.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar"></i> Analytics Kode Promo</h2>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="exportAnalytics('pdf')">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn btn-outline-success" onclick="exportAnalytics('excel')">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-tags fa-2x text-primary mb-2"></i>
                                <h3 class="text-primary"><?php echo $analytics['total_promos']; ?></h3>
                                <p class="text-muted mb-0">Total Promo</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-play-circle fa-2x text-success mb-2"></i>
                                <h3 class="text-success"><?php echo $analytics['active_promos']; ?></h3>
                                <p class="text-muted mb-0">Promo Aktif</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h3 class="text-warning"><?php echo $analytics['expired_promos']; ?></h3>
                                <p class="text-muted mb-0">Promo Expired</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                                <h3 class="text-info"><?php echo $analytics['total_usage']; ?></h3>
                                <p class="text-muted mb-0">Total Penggunaan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-pie-chart"></i> Distribusi Tipe Promo</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="typeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-line-chart"></i> Trend Pembuatan Promo (6 Bulan Terakhir)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-trophy"></i> Promo Paling Banyak Digunakan</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($analytics['most_used'])): ?>
                                    <p class="text-muted text-center">Belum ada promo yang digunakan</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Deskripsi</th>
                                                    <th>Penggunaan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($analytics['most_used'] as $promo): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($promo['code']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($promo['description']); ?></td>
                                                        <td><span class="badge bg-primary"><?php echo $promo['used_count']; ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-clock"></i> Promo Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($analytics['recent_promos'])): ?>
                                    <p class="text-muted text-center">Belum ada promo</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Deskripsi</th>
                                                    <th>Dibuat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($analytics['recent_promos'] as $promo): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($promo['code']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($promo['description']); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($promo['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        function initializeCharts() {
            // Type Distribution Chart
            const typeData = <?php echo json_encode($analytics['type_distribution']); ?>;
            const typeLabels = typeData.map(item => item.type === 'percentage' ? 'Persentase' : 'Nominal');
            const typeValues = typeData.map(item => item.count);

            const typeCtx = document.getElementById('typeChart').getContext('2d');
            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        data: typeValues,
                        backgroundColor: [
                            '#28a745',
                            '#007bff',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Monthly Trend Chart
            const monthlyData = <?php echo json_encode($analytics['monthly_stats']); ?>;
            const monthLabels = monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
            }).reverse();
            const monthValues = monthlyData.map(item => item.count).reverse();

            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Promo Dibuat',
                        data: monthValues,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Export analytics
        function exportAnalytics(format) {
            const url = `export_analytics.php?format=${format}`;
            window.open(url, '_blank');
        }

        // Refresh data
        function refreshData() {
            location.reload();
        }

        // Auto refresh every 5 minutes
        setInterval(refreshData, 300000);
    </script>
</body>
</html>