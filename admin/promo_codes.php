<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_promo':
                $code = strtoupper(trim($_POST['code']));
                $description = trim($_POST['description']);
                $type = $_POST['type'];
                $value = (float)$_POST['value'];
                $min_purchase = !empty($_POST['min_purchase']) ? (float)$_POST['min_purchase'] : 0;
                $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
                $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
                $start_date = $_POST['start_date'];
                $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                try {
                    // Check if code already exists
                    $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ?");
                    $stmt->execute([$code]);
                    if ($stmt->fetch()) {
                        $message = 'Kode promo sudah ada! Gunakan kode yang berbeda.';
                        $message_type = 'danger';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO promo_codes (code, description, type, value, min_purchase, max_discount, usage_limit, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$code, $description, $type, $value, $min_purchase, $max_discount, $usage_limit, $start_date, $end_date, $is_active]);

                        $message = 'Kode promo berhasil ditambahkan!';
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;

            case 'update_promo':
                $id = (int)$_POST['id'];
                $code = strtoupper(trim($_POST['code']));
                $description = trim($_POST['description']);
                $type = $_POST['type'];
                $value = (float)$_POST['value'];
                $min_purchase = !empty($_POST['min_purchase']) ? (float)$_POST['min_purchase'] : 0;
                $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
                $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
                $start_date = $_POST['start_date'];
                $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                try {
                    // Check if code already exists (excluding current record)
                    $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ? AND id != ?");
                    $stmt->execute([$code, $id]);
                    if ($stmt->fetch()) {
                        $message = 'Kode promo sudah ada! Gunakan kode yang berbeda.';
                        $message_type = 'danger';
                    } else {
                        $stmt = $pdo->prepare("UPDATE promo_codes SET code = ?, description = ?, type = ?, value = ?, min_purchase = ?, max_discount = ?, usage_limit = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$code, $description, $type, $value, $min_purchase, $max_discount, $usage_limit, $start_date, $end_date, $is_active, $id]);

                        $message = 'Kode promo berhasil diupdate!';
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;

            case 'delete_promo':
                $id = (int)$_POST['id'];

                try {
                    $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE id = ?");
                    $stmt->execute([$id]);

                    $message = 'Kode promo berhasil dihapus!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;

            case 'toggle_status':
                $id = (int)$_POST['id'];
                $current_status = (int)$_POST['current_status'];
                $new_status = $current_status ? 0 : 1;

                try {
                    $stmt = $pdo->prepare("UPDATE promo_codes SET is_active = ? WHERE id = ?");
                    $stmt->execute([$new_status, $id]);

                    $status_text = $new_status ? 'aktif' : 'nonaktif';
                    $message = "Kode promo berhasil diubah menjadi $status_text!";
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;

            case 'reset_usage':
                $id = (int)$_POST['id'];

                try {
                    $stmt = $pdo->prepare("UPDATE promo_codes SET used_count = 0 WHERE id = ?");
                    $stmt->execute([$id]);

                    $message = 'Usage count berhasil direset!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$expired_filter = $_GET['expired'] ?? '';

// Build query
$sql = "SELECT * FROM promo_codes WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (code LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter !== '') {
    $sql .= " AND is_active = ?";
    $params[] = (int)$status_filter;
}

if ($type_filter) {
    $sql .= " AND type = ?";
    $params[] = $type_filter;
}

if ($expired_filter === '1') {
    $sql .= " AND end_date < CURDATE()";
} elseif ($expired_filter === '0') {
    $sql .= " AND (end_date IS NULL OR end_date >= CURDATE())";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$promo_codes = $stmt->fetchAll();

// Get statistics
$stats = [
    'total_promos' => 0,
    'active_promos' => 0,
    'expired_promos' => 0,
    'used_today' => 0
];

try {
    // Total promos
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM promo_codes");
    $result = $stmt->fetch();
    $stats['total_promos'] = $result['count'];

    // Active promos
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM promo_codes WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())");
    $result = $stmt->fetch();
    $stats['active_promos'] = $result['count'];

    // Expired promos
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM promo_codes WHERE end_date < CURDATE()");
    $result = $stmt->fetch();
    $stats['expired_promos'] = $result['count'];

    // Used today (if you have usage tracking)
    $stmt = $pdo->query("SELECT COALESCE(SUM(used_count), 0) as count FROM promo_codes WHERE DATE(updated_at) = CURDATE()");
    $result = $stmt->fetch();
    $stats['used_today'] = $result['count'];
} catch (PDOException $e) {
    // Keep default stats
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Promo - Sahabat Tani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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


        /* Dark/Light Mode Toggle Styles */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }

        .theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #764ba2 0%, #667eea 100%);
        }

        .theme-toggle i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .theme-toggle:hover i {
            transform: rotate(180deg);
        }

        /* Dark Theme Variables */
        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-card: #363636;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --border-color: #404040;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        [data-theme="light"] {
            --bg-primary: #f8f9fa;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Apply theme variables */
        [data-theme="dark"] body {
            background-color: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .card {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
            box-shadow: var(--shadow) !important;
        }

        [data-theme="dark"] .table {
            color: var(--text-primary) !important;
            background-color: var(--bg-card) !important;
        }

        [data-theme="dark"] .table th {
            background-color: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .modal-content {
            background-color: var(--bg-card) !important;
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .bg-dark {
            background-color: #000000 !important;
        }

        /* Smooth transitions for everything */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
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
            box-shadow: 0 2px 10px rgba(255, 255, 255, 0.05);
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

        .promo-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .promo-code:hover {
            background: #e9ecef;
            border-color: #28a745;
        }

        .usage-progress {
            min-width: 100px;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
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

    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
        <i class="fas fa-moon" id="themeIcon"></i>
        <span id="themeText">Dark Mode</span>
    </button>

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
                <a href="promo_codes.php" class="nav-link active">
                    <i class="fas fa-tags"></i>
                    Kode Promo
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link" >
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
                <h4 class="mb-0">Kelola Kode Promo</h4>
                <small class="text-muted">Kelola kode promo dan diskon untuk pelanggan 🎫</small>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromoModal">
                    <i class="fas fa-plus me-2"></i>
                    Tambah Promo
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show animate-fade-in" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in">
                    <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_promos']); ?></div>
                    <div class="stats-label">Total Promo</div>
                    <div class="stats-change">
                        <span class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            Semua kode promo
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1); color:rgb(0, 123, 255);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['active_promos']); ?></div>
                    <div class="stats-label">Promo Aktif</div>
                    <div class="stats-change">
                        <span class="text-success">
                            <i class="fas fa-check me-1"></i>
                            Dapat digunakan
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['expired_promos']); ?></div>
                    <div class="stats-label">Promo Expired</div>
                    <div class="stats-change">
                        <?php if ($stats['expired_promos'] > 0): ?>
                            <span class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Perlu dibersihkan
                            </span>
                        <?php else: ?>
                            <span class="text-success">
                                <i class="fas fa-check me-1"></i>
                                Semua aktif
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['used_today']); ?></div>
                    <div class="stats-label">Digunakan Hari Ini</div>
                    <div class="stats-change">
                        <span class="text-info">
                            <i class="fas fa-calendar-day me-1"></i>
                            Total penggunaan
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Pencarian</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari kode atau deskripsi...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipe</label>
                    <select class="form-select" name="type">
                        <option value="">Semua Tipe</option>
                        <option value="percentage" <?php echo $type_filter === 'percentage' ? 'selected' : ''; ?>>Persentase</option>
                        <option value="fixed" <?php echo $type_filter === 'fixed' ? 'selected' : ''; ?>>Nominal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Expired</label>
                    <select class="form-select" name="expired">
                        <option value="">Semua</option>
                        <option value="0" <?php echo $expired_filter === '0' ? 'selected' : ''; ?>>Belum Expired</option>
                        <option value="1" <?php echo $expired_filter === '1' ? 'selected' : ''; ?>>Sudah Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search me-1"></i>
                            Cari
                        </button>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-refresh"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Promo Codes Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-tags me-2"></i>
                    Daftar Kode Promo
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-success" onclick="exportPromos('excel')">
                        <i class="fas fa-file-excel me-1"></i>
                        Excel
                    </button>
                    <button class="btn btn-outline-danger" onclick="exportPromos('pdf')">
                        <i class="fas fa-file-pdf me-1"></i>
                        PDF
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode Promo</th>
                                <th>Deskripsi</th>
                                <th>Tipe</th>
                                <th>Nilai</th>
                                <th>Min. Pembelian</th>
                                <th>Penggunaan</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($promo_codes)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-tags fa-3x text-muted mb-3 d-block"></i>
                                        <h5 class="text-muted">Belum ada kode promo</h5>
                                        <p class="text-muted mb-3">Mulai buat kode promo pertama untuk menarik lebih banyak pelanggan</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromoModal">
                                            <i class="fas fa-plus me-2"></i>
                                            Buat Kode Promo
                                        </button>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($promo_codes as $promo): ?>
                                    <?php
                                    $is_expired = $promo['end_date'] && strtotime($promo['end_date']) < time();
                                    $usage_percentage = 0;
                                    if ($promo['usage_limit'] && $promo['usage_limit'] > 0) {
                                        $usage_percentage = ($promo['used_count'] / $promo['usage_limit']) * 100;
                                    }
                                    ?>
                                    <tr class="<?php echo $is_expired ? 'table-secondary' : ''; ?>">
                                        <td>
                                            <div class="promo-code" onclick="copyToClipboard('<?php echo $promo['code']; ?>')" title="Klik untuk copy">
                                                <?php echo htmlspecialchars($promo['code']); ?>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                ID: <?php echo $promo['id']; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($promo['description']); ?></div>
                                            <?php if ($promo['max_discount']): ?>
                                                <small class="text-muted">
                                                    Max: Rp <?php echo number_format($promo['max_discount'], 0, ',', '.'); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $promo['type'] === 'percentage' ? 'info' : 'primary'; ?>">
                                                <?php echo $promo['type'] === 'percentage' ? 'Persentase' : 'Nominal'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>
                                                <?php if ($promo['type'] === 'percentage'): ?>
                                                    <?php echo $promo['value']; ?>%
                                                <?php else: ?>
                                                    Rp <?php echo number_format($promo['value'], 0, ',', '.'); ?>
                                                <?php endif; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ($promo['min_purchase'] > 0): ?>
                                                <span class="text-success">
                                                    Rp <?php echo number_format($promo['min_purchase'], 0, ',', '.'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak ada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($promo['usage_limit']): ?>
                                                <div class="usage-progress">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small><?php echo $promo['used_count']; ?>/<?php echo $promo['usage_limit']; ?></small>
                                                        <small><?php echo round($usage_percentage); ?>%</small>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-<?php echo $usage_percentage >= 80 ? 'danger' : ($usage_percentage >= 50 ? 'warning' : 'success'); ?>"
                                                            style="width: <?php echo $usage_percentage; ?>%"></div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <span class="badge bg-secondary">Unlimited</span>
                                                    <div><small class="text-muted"><?php echo $promo['used_count']; ?> digunakan</small></div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div class="text-success">
                                                    <i class="fas fa-play me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?>
                                                </div>
                                                <?php if ($promo['end_date']): ?>
                                                    <div class="<?php echo $is_expired ? 'text-danger' : 'text-warning'; ?>">
                                                        <i class="fas fa-stop me-1"></i>
                                                        <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-info">
                                                        <i class="fas fa-infinity me-1"></i>
                                                        Tidak terbatas
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($is_expired): ?>
                                                <span class="badge bg-danger">Expired</span>
                                            <?php elseif ($promo['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-info"
                                                    onclick="viewPromo(<?php echo htmlspecialchars(json_encode($promo)); ?>)"
                                                    title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-warning"
                                                    onclick="editPromo(<?php echo htmlspecialchars(json_encode($promo)); ?>)"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$is_expired): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo $promo['is_active']; ?>">
                                                        <button type="submit" class="btn btn-outline-<?php echo $promo['is_active'] ? 'secondary' : 'success'; ?>"
                                                            title="<?php echo $promo['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                            <i class="fas fa-<?php echo $promo['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($promo['used_count'] > 0): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="reset_usage">
                                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-primary"
                                                            onclick="return confirm('Reset usage count ke 0?')"
                                                            title="Reset Usage">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_promo">
                                                    <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger"
                                                        onclick="return confirm('Yakin ingin menghapus kode promo ini?')"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-magic fa-2x text-primary mb-3"></i>
                        <h6>Generator Kode</h6>
                        <p class="text-muted small">Buat kode promo otomatis</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="generatePromoCode()">
                            Generate Kode
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-copy fa-2x text-success mb-3"></i>
                        <h6>Duplikat Promo</h6>
                        <p class="text-muted small">Salin promo yang sudah ada</p>
                        <button class="btn btn-outline-success btn-sm" onclick="showDuplicateModal()">
                            Duplikat
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-broom fa-2x text-warning mb-3"></i>
                        <h6>Bersihkan Expired</h6>
                        <p class="text-muted small">Hapus promo yang sudah expired</p>
                        <button class="btn btn-outline-warning btn-sm" onclick="cleanExpiredPromos()">
                            Bersihkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Promo Modal -->
    <div class="modal fade" id="addPromoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Tambah Kode Promo Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_promo">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode Promo *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="code" id="addCode" required
                                            style="text-transform: uppercase;" maxlength="20">
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateRandomCode('addCode')">
                                            <i class="fas fa-random"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Huruf besar, tanpa spasi, max 20 karakter</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipe Diskon *</label>
                                    <select class="form-select" name="type" id="addType" required onchange="toggleDiscountType('add')">
                                        <option value="">Pilih Tipe</option>
                                        <option value="percentage">Persentase (%)</option>
                                        <option value="fixed">Nominal (Rp)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nilai Diskon *</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="addValuePrefix">%</span>
                                        <input type="number" class="form-control" name="value" id="addValue" required
                                            min="0" step="0.01">
                                    </div>
                                    <small class="text-muted" id="addValueHelp">Masukkan persentase diskon</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Minimum Pembelian</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="min_purchase" min="0">
                                    </div>
                                    <small class="text-muted">Kosongkan jika tidak ada minimum</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Maksimum Diskon</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="max_discount" min="0">
                                    </div>
                                    <small class="text-muted">Untuk tipe persentase, batasi maksimum diskon</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Batas Penggunaan</label>
                                    <input type="number" class="form-control" name="usage_limit" min="1">
                                    <small class="text-muted">Kosongkan untuk unlimited</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai *</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Berakhir</label>
                                    <input type="date" class="form-control" name="end_date">
                                    <small class="text-muted">Kosongkan untuk tidak terbatas</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="description" rows="3" required
                                placeholder="Contoh: Diskon 20% untuk pembelian pertama"></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="addActive" checked>
                                <label class="form-check-label" for="addActive">
                                    Aktifkan promo setelah dibuat
                                </label>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-eye me-2"></i>Preview Promo</h6>
                            <div id="addPreview">
                                <p class="mb-0">Isi form untuk melihat preview</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Simpan Promo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Promo Modal -->
    <div class="modal fade" id="editPromoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Kode Promo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_promo">
                        <input type="hidden" name="id" id="editId">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode Promo *</label>
                                    <input type="text" class="form-control" name="code" id="editCode" required
                                        style="text-transform: uppercase;" maxlength="20">
                                    <small class="text-muted">Hati-hati mengubah kode yang sudah digunakan</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipe Diskon *</label>
                                    <select class="form-select" name="type" id="editType" required onchange="toggleDiscountType('edit')">
                                        <option value="percentage">Persentase (%)</option>
                                        <option value="fixed">Nominal (Rp)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nilai Diskon *</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="editValuePrefix">%</span>
                                        <input type="number" class="form-control" name="value" id="editValue" required
                                            min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Minimum Pembelian</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="min_purchase" id="editMinPurchase" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Maksimum Diskon</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="max_discount" id="editMaxDiscount" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Batas Penggunaan</label>
                                    <input type="number" class="form-control" name="usage_limit" id="editUsageLimit" min="1">
                                    <small class="text-muted">Kosongkan untuk unlimited</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai *</label>
                                    <input type="date" class="form-control" name="start_date" id="editStartDate" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Berakhir</label>
                                    <input type="date" class="form-control" name="end_date" id="editEndDate">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editActive">
                                <label class="form-check-label" for="editActive">
                                    Promo aktif
                                </label>
                            </div>
                        </div>

                        <!-- Usage Info -->
                        <div class="alert alert-warning" id="editUsageInfo" style="display: none;">
                            <h6><i class="fas fa-info-circle me-2"></i>Informasi Penggunaan</h6>
                            <p class="mb-0" id="editUsageText"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>
                            Update Promo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Promo Modal -->
    <div class="modal fade" id="viewPromoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Detail Kode Promo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Informasi Dasar</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td width="40%">Kode:</td>
                                            <td><strong id="viewCode"></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Deskripsi:</td>
                                            <td id="viewDescription"></td>
                                        </tr>
                                        <tr>
                                            <td>Tipe:</td>
                                            <td id="viewType"></td>
                                        </tr>
                                        <tr>
                                            <td>Nilai:</td>
                                            <td><strong id="viewValue"></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Min. Pembelian:</td>
                                            <td id="viewMinPurchase"></td>
                                        </tr>
                                        <tr>
                                            <td>Max. Diskon:</td>
                                            <td id="viewMaxDiscount"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Status & Penggunaan</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td width="40%">Status:</td>
                                            <td id="viewStatus"></td>
                                        </tr>
                                        <tr>
                                            <td>Periode:</td>
                                            <td id="viewPeriod"></td>
                                        </tr>
                                        <tr>
                                            <td>Batas Penggunaan:</td>
                                            <td id="viewUsageLimit"></td>
                                        </tr>
                                        <tr>
                                            <td>Sudah Digunakan:</td>
                                            <td id="viewUsedCount"></td>
                                        </tr>
                                        <tr>
                                            <td>Sisa:</td>
                                            <td id="viewRemaining"></td>
                                        </tr>
                                        <tr>
                                            <td>Dibuat:</td>
                                            <td id="viewCreated"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Chart -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Grafik Penggunaan</h6>
                        </div>
                        <div class="card-body">
                            <div id="viewUsageChart">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <p>Grafik penggunaan akan ditampilkan di sini</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="sharePromo()">
                        <i class="fas fa-share me-2"></i>
                        Bagikan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Duplicate Promo Modal -->
    <div class="modal fade" id="duplicatePromoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-copy me-2"></i>
                        Duplikat Kode Promo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="duplicate_promo">

                        <div class="mb-3">
                            <label class="form-label">Pilih Promo yang akan diduplikat</label>
                            <select class="form-select" name="source_id" required>
                                <option value="">Pilih Promo</option>
                                <?php foreach ($promo_codes as $promo): ?>
                                    <option value="<?php echo $promo['id']; ?>">
                                        <?php echo htmlspecialchars($promo['code']); ?> - <?php echo htmlspecialchars($promo['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode Promo Baru *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="new_code" required
                                    style="text-transform: uppercase;" maxlength="20">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateRandomCode('new_code')">
                                    <i class="fas fa-random"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Baru</label>
                            <textarea class="form-control" name="new_description" rows="2"
                                placeholder="Kosongkan untuk menggunakan deskripsi yang sama"></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Catatan:</strong> Semua pengaturan lain akan disalin dari promo asli,
                            kecuali usage count yang akan direset ke 0.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-copy me-2"></i>
                            Duplikat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- dark mode -->
    <script>
        // Theme Toggle Magic! ✨
        class ThemeManager {
            constructor() {
                this.currentTheme = localStorage.getItem('theme') || 'light';
                this.init();
            }

            init() {
                document.documentElement.setAttribute('data-theme', this.currentTheme);
                this.updateToggleButton();
                this.addKeyboardShortcut();
            }

            toggleTheme() {
                this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';

                // Smooth transition effect
                document.body.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

                document.documentElement.setAttribute('data-theme', this.currentTheme);
                localStorage.setItem('theme', this.currentTheme);

                this.updateToggleButton();
                this.showToast();
            }

            updateToggleButton() {
                const icon = document.getElementById('themeIcon');
                const text = document.getElementById('themeText');
                const button = document.getElementById('themeToggle');

                if (this.currentTheme === 'dark') {
                    icon.className = 'fas fa-sun';
                    text.textContent = 'Light Mode';
                    button.style.background = 'linear-gradient(45deg, #f093fb 0%, #f5576c 100%)';
                } else {
                    icon.className = 'fas fa-moon';
                    text.textContent = 'Dark Mode';
                    button.style.background = 'linear-gradient(45deg, #667eea 0%, #764ba2 100%)';
                }
            }

            showToast() {
                const toast = document.createElement('div');
                toast.className = 'position-fixed top-0 end-0 p-3';
                toast.style.zIndex = '1060';
                toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header">
                    <i class="fas fa-palette text-primary me-2"></i>
                    <strong class="me-auto">Theme Changed</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Switched to ${this.currentTheme} mode! 🎨
                </div>
            </div>
        `;

                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }

            addKeyboardShortcut() {
                document.addEventListener('keydown', (e) => {
                    // Ctrl + Shift + T = Toggle Theme
                    if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                        e.preventDefault();
                        this.toggleTheme();
                    }
                });
            }
        }

        // Initialize theme manager
        const themeManager = new ThemeManager();

        // Global function for button onclick
        function toggleTheme() {
            themeManager.toggleTheme();
        }

        // Auto-detect system preference on first visit
        if (!localStorage.getItem('theme')) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                themeManager.currentTheme = 'dark';
                themeManager.init();
            }
        }
    </script>

    <script>
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="start_date"]').value = today;

            // Add event listeners for preview
            const addForm = document.querySelector('#addPromoModal form');
            if (addForm) {
                addForm.addEventListener('input', updateAddPreview);
                addForm.addEventListener('change', updateAddPreview);
            }
        });

        // Generate random promo code
        function generateRandomCode(inputId) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < 8; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById(inputId).value = result;

            if (inputId === 'addCode') {
                updateAddPreview();
            }
        }

        // Toggle discount type
        function toggleDiscountType(mode) {
            const typeSelect = document.getElementById(mode + 'Type');
            const valuePrefix = document.getElementById(mode + 'ValuePrefix');
            const valueInput = document.getElementById(mode + 'Value');
            const valueHelp = document.getElementById(mode + 'ValueHelp');

            if (typeSelect.value === 'percentage') {
                valuePrefix.textContent = '%';
                valueInput.max = '100';
                if (valueHelp) valueHelp.textContent = 'Masukkan persentase diskon (0-100)';
            } else if (typeSelect.value === 'fixed') {
                valuePrefix.textContent = 'Rp';
                valueInput.removeAttribute('max');
                if (valueHelp) valueHelp.textContent = 'Masukkan nominal diskon dalam rupiah';
            }

            if (mode === 'add') {
                updateAddPreview();
            }
        }

        // Update preview for add modal
        function updateAddPreview() {
            const code = document.getElementById('addCode').value;
            const type = document.getElementById('addType').value;
            const value = document.getElementById('addValue').value;
            const description = document.querySelector('#addPromoModal textarea[name="description"]').value;
            const minPurchase = document.querySelector('#addPromoModal input[name="min_purchase"]').value;
            const maxDiscount = document.querySelector('#addPromoModal input[name="max_discount"]').value;

            let preview = '';

            if (code && type && value && description) {
                preview = `<strong>Kode:</strong> ${code}<br>`;
                preview += `<strong>Deskripsi:</strong> ${description}<br>`;

                if (type === 'percentage') {
                    preview += `<strong>Diskon:</strong> ${value}%`;
                    if (maxDiscount) {
                        preview += ` (max Rp ${parseInt(maxDiscount).toLocaleString('id-ID')})`;
                    }
                } else {
                    preview += `<strong>Diskon:</strong> Rp ${parseInt(value).toLocaleString('id-ID')}`;
                }

                if (minPurchase) {
                    preview += `<br><strong>Min. Pembelian:</strong> Rp ${parseInt(minPurchase).toLocaleString('id-ID')}`;
                }
            } else {
                preview = 'Isi form untuk melihat preview';
            }

            document.getElementById('addPreview').innerHTML = preview;
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show toast notification
                showToast('Kode promo berhasil disalin!', 'success');
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('Kode promo berhasil disalin!', 'success');
            });
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close ms-2" onclick="this.parentElement.remove()"></button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 3000);
        }

        // Edit promo function
        function editPromo(promo) {
            document.getElementById('editId').value = promo.id;
            document.getElementById('editCode').value = promo.code;
            document.getElementById('editType').value = promo.type;
            document.getElementById('editValue').value = promo.value;
            document.getElementById('editMinPurchase').value = promo.min_purchase || '';
            document.getElementById('editMaxDiscount').value = promo.max_discount || '';
            document.getElementById('editUsageLimit').value = promo.usage_limit || '';
            document.getElementById('editStartDate').value = promo.start_date;
            document.getElementById('editEndDate').value = promo.end_date || '';
            document.getElementById('editDescription').value = promo.description;
            document.getElementById('editActive').checked = promo.is_active == 1;

            // Toggle discount type
            toggleDiscountType('edit');

            // Show usage info if promo has been used
            if (promo.used_count > 0) {
                document.getElementById('editUsageInfo').style.display = 'block';
                document.getElementById('editUsageText').textContent =
                    `Promo ini sudah digunakan ${promo.used_count} kali. Hati-hati saat mengubah pengaturan.`;
            } else {
                document.getElementById('editUsageInfo').style.display = 'none';
            }

            const modal = new bootstrap.Modal(document.getElementById('editPromoModal'));
            modal.show();
        }

        // View promo function
        function viewPromo(promo) {
            document.getElementById('viewCode').textContent = promo.code;
            document.getElementById('viewDescription').textContent = promo.description;
            document.getElementById('viewType').innerHTML = `<span class="badge bg-${promo.type === 'percentage' ? 'info' : 'primary'}">${promo.type === 'percentage' ? 'Persentase' : 'Nominal'}</span>`;

            if (promo.type === 'percentage') {
                document.getElementById('viewValue').textContent = promo.value + '%';
            } else {
                document.getElementById('viewValue').textContent = 'Rp ' + parseInt(promo.value).toLocaleString('id-ID');
            }

            document.getElementById('viewMinPurchase').textContent = promo.min_purchase ?
                'Rp ' + parseInt(promo.min_purchase).toLocaleString('id-ID') : 'Tidak ada';

            document.getElementById('viewMaxDiscount').textContent = promo.max_discount ?
                'Rp ' + parseInt(promo.max_discount).toLocaleString('id-ID') : 'Tidak ada';

            // Status
            let statusBadge = '';
            const isExpired = promo.end_date && new Date(promo.end_date) < new Date();
            if (isExpired) {
                statusBadge = '<span class="badge bg-danger">Expired</span>';
            } else if (promo.is_active == 1) {
                statusBadge = '<span class="badge bg-success">Aktif</span>';
            } else {
                statusBadge = '<span class="badge bg-secondary">Nonaktif</span>';
            }
            document.getElementById('viewStatus').innerHTML = statusBadge;

            // Period
            let period = 'Mulai: ' + new Date(promo.start_date).toLocaleDateString('id-ID');
            if (promo.end_date) {
                period += '<br>Berakhir: ' + new Date(promo.end_date).toLocaleDateString('id-ID');
            } else {
                period += '<br>Tidak terbatas';
            }
            document.getElementById('viewPeriod').innerHTML = period;

            // Usage
            document.getElementById('viewUsageLimit').textContent = promo.usage_limit || 'Unlimited';
            document.getElementById('viewUsedCount').textContent = promo.used_count;

            if (promo.usage_limit) {
                const remaining = promo.usage_limit - promo.used_count;
                document.getElementById('viewRemaining').innerHTML = `<span class="badge bg-${remaining > 0 ? 'success' : 'danger'}">${remaining}</span>`;
            } else {
                document.getElementById('viewRemaining').innerHTML = '<span class="badge bg-secondary">Unlimited</span>';
            }

            document.getElementById('viewCreated').textContent = new Date(promo.created_at).toLocaleDateString('id-ID');

            const modal = new bootstrap.Modal(document.getElementById('viewPromoModal'));
            modal.show();
        }

        // Generate promo code
        function generatePromoCode() {
            const patterns = [
                'SAVE##',
                'DISC##',
                'PROMO##',
                'DEAL##',
                'OFFER##'
            ];

            const pattern = patterns[Math.floor(Math.random() * patterns.length)];
            const numbers = Math.floor(Math.random() * 100).toString().padStart(2, '0');
            const code = pattern.replace('##', numbers);

            showToast(`Kode yang disarankan: <strong>${code}</strong>`, 'info');
        }

        // Show duplicate modal
        function showDuplicateModal() {
            const modal = new bootstrap.Modal(document.getElementById('duplicatePromoModal'));
            modal.show();
        }

        // Clean expired promos
        function cleanExpiredPromos() {
            if (confirm('Yakin ingin menghapus semua promo yang sudah expired?\n\nTindakan ini tidak dapat dibatalkan!')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'clean_expired';
                form.appendChild(actionInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Export promos
        function exportPromos(format) {
            const url = `export_promos.php?format=${format}`;
            window.open(url, '_blank');
        }

        // Share promo
        function sharePromo() {
            const code = document.getElementById('viewCode').textContent;
            const description = document.getElementById('viewDescription').textContent;

            const shareText = `🎉 Kode Promo: ${code}\n${description}\n\nGunakan sekarang di website kami!`;

            if (navigator.share) {
                navigator.share({
                    title: 'Kode Promo',
                    text: shareText,
                    url: window.location.origin
                });
            } else {
                copyToClipboard(shareText);
                showToast('Teks promo berhasil disalin untuk dibagikan!', 'success');
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + N = New Promo
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('addPromoModal'));
                modal.show();
            }

            // Escape = Close modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(function(modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                });
            }
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });

                    // Validate promo code format
                    const codeInputs = form.querySelectorAll('input[name="code"], input[name="new_code"]');
                    codeInputs.forEach(function(input) {
                        if (input.value) {
                            const code = input.value.trim().toUpperCase();
                            if (!/^[A-Z0-9]{3,20}$/.test(code)) {
                                input.classList.add('is-invalid');
                                isValid = false;
                                showToast('Kode promo harus 3-20 karakter, hanya huruf dan angka!', 'danger');
                            } else {
                                input.classList.remove('is-invalid');
                                input.value = code;
                            }
                        }
                    });

                    // Validate percentage value
                    const percentageInputs = form.querySelectorAll('input[name="value"]');
                    percentageInputs.forEach(function(input) {
                        const typeSelect = form.querySelector('select[name="type"]');
                        if (typeSelect && typeSelect.value === 'percentage' && input.value) {
                            const value = parseFloat(input.value);
                            if (value <= 0 || value > 100) {
                                input.classList.add('is-invalid');
                                isValid = false;
                                showToast('Persentase diskon harus antara 1-100!', 'danger');
                            } else {
                                input.classList.remove('is-invalid');
                            }
                        }
                    });

                    // Validate date range
                    const startDateInputs = form.querySelectorAll('input[name="start_date"]');
                    const endDateInputs = form.querySelectorAll('input[name="end_date"]');

                    if (startDateInputs.length && endDateInputs.length) {
                        const startDate = startDateInputs[0].value;
                        const endDate = endDateInputs[0].value;

                        if (startDate && endDate && new Date(startDate) >= new Date(endDate)) {
                            endDateInputs[0].classList.add('is-invalid');
                            isValid = false;
                            showToast('Tanggal berakhir harus setelah tanggal mulai!', 'danger');
                        } else {
                            endDateInputs[0].classList.remove('is-invalid');
                        }
                    }

                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            });
        });

        // Auto-format inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Auto uppercase promo codes
            const codeInputs = document.querySelectorAll('input[name="code"], input[name="new_code"]');
            codeInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                });
            });

            // Format currency inputs
            const currencyInputs = document.querySelectorAll('input[name="min_purchase"], input[name="max_discount"], input[name="value"]');
            currencyInputs.forEach(function(input) {
                input.addEventListener('blur', function() {
                    if (this.value && !isNaN(this.value)) {
                        // Add thousand separators for display (optional)
                        // this.setAttribute('data-formatted', parseInt(this.value).toLocaleString('id-ID'));
                    }
                });
            });
        });

        // Bulk actions
        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][data-promo-id]');
            const selected = Array.from(checkboxes).filter(cb => cb.checked);

            if (selected.length === 0) {
                showToast('Pilih minimal satu promo untuk melakukan aksi bulk!', 'warning');
                return;
            }

            let confirmMessage = '';
            switch (action) {
                case 'activate':
                    confirmMessage = `Aktifkan ${selected.length} promo yang dipilih?`;
                    break;
                case 'deactivate':
                    confirmMessage = `Nonaktifkan ${selected.length} promo yang dipilih?`;
                    break;
                case 'delete':
                    confirmMessage = `Hapus ${selected.length} promo yang dipilih? Tindakan ini tidak dapat dibatalkan!`;
                    break;
                case 'reset':
                    confirmMessage = `Reset usage count ${selected.length} promo yang dipilih ke 0?`;
                    break;
            }

            if (confirm(confirmMessage)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'bulk_' + action;
                form.appendChild(actionInput);

                selected.forEach(function(checkbox) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_promos[]';
                    input.value = checkbox.getAttribute('data-promo-id');
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Search with debounce
        let searchTimeout;

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                document.querySelector('form').submit();
            }, 500);
        }

        // Add event listener to search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', debounceSearch);
            }
        });

        // Promo analytics (placeholder for future implementation)
        function showPromoAnalytics(promoId) {
            // This would show detailed analytics for a specific promo
            showToast('Fitur analytics akan segera hadir!', 'info');
        }

        // Quick actions
        function quickCreatePromo(type) {
            const modal = new bootstrap.Modal(document.getElementById('addPromoModal'));
            modal.show();

            // Pre-fill based on type
            setTimeout(() => {
                switch (type) {
                    case 'welcome':
                        document.getElementById('addCode').value = 'WELCOME' + Math.floor(Math.random() * 100);
                        document.getElementById('addType').value = 'percentage';
                        document.getElementById('addValue').value = '10';
                        document.querySelector('#addPromoModal textarea[name="description"]').value = 'Diskon 10% untuk pelanggan baru';
                        break;
                    case 'flash':
                        document.getElementById('addCode').value = 'FLASH' + Math.floor(Math.random() * 100);
                        document.getElementById('addType').value = 'percentage';
                        document.getElementById('addValue').value = '25';
                        document.querySelector('#addPromoModal textarea[name="description"]').value = 'Flash Sale - Diskon 25%';
                        // Set end date to tomorrow
                        const tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        document.querySelector('#addPromoModal input[name="end_date"]').value = tomorrow.toISOString().split('T')[0];
                        break;
                    case 'loyalty':
                        document.getElementById('addCode').value = 'LOYAL' + Math.floor(Math.random() * 100);
                        document.getElementById('addType').value = 'fixed';
                        document.getElementById('addValue').value = '50000';
                        document.querySelector('#addPromoModal input[name="min_purchase"]').value = '500000';
                        document.querySelector('#addPromoModal textarea[name="description"]').value = 'Diskon Rp 50.000 untuk pembelian minimal Rp 500.000';
                        break;
                }
                toggleDiscountType('add');
                updateAddPreview();
            }, 100);
        }

        // Promo templates
        const promoTemplates = {
            'new-customer': {
                code: 'NEWBIE##',
                type: 'percentage',
                value: 15,
                description: 'Diskon 15% untuk pelanggan baru',
                usage_limit: 100
            },
            'bulk-order': {
                code: 'BULK##',
                type: 'fixed',
                value: 100000,
                min_purchase: 1000000,
                description: 'Diskon Rp 100.000 untuk pembelian minimal Rp 1.000.000'
            },
            'weekend-sale': {
                code: 'WEEKEND##',
                type: 'percentage',
                value: 20,
                max_discount: 200000,
                description: 'Weekend Sale - Diskon 20% maksimal Rp 200.000'
            }
        };

        function applyTemplate(templateKey) {
            const template = promoTemplates[templateKey];
            if (!template) return;

            const modal = new bootstrap.Modal(document.getElementById('addPromoModal'));
            modal.show();

            setTimeout(() => {
                const randomNum = Math.floor(Math.random() * 100).toString().padStart(2, '0');
                document.getElementById('addCode').value = template.code.replace('##', randomNum);
                document.getElementById('addType').value = template.type;
                document.getElementById('addValue').value = template.value;
                document.querySelector('#addPromoModal textarea[name="description"]').value = template.description;

                if (template.min_purchase) {
                    document.querySelector('#addPromoModal input[name="min_purchase"]').value = template.min_purchase;
                }
                if (template.max_discount) {
                    document.querySelector('#addPromoModal input[name="max_discount"]').value = template.max_discount;
                }
                if (template.usage_limit) {
                    document.querySelector('#addPromoModal input[name="usage_limit"]').value = template.usage_limit;
                }

                toggleDiscountType('add');
                updateAddPreview();
            }, 100);
        }

        // Add template buttons to the page
        document.addEventListener('DOMContentLoaded', function() {
            const quickActionsSection = document.querySelector('.row.mt-4');
            if (quickActionsSection) {
                const templateCard = document.createElement('div');
                templateCard.className = 'col-md-12 mb-3';
                templateCard.innerHTML = `
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-magic me-2"></i>Template Promo Cepat</h6>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary" onclick="applyTemplate('new-customer')">
                                    <i class="fas fa-user-plus me-1"></i>Pelanggan Baru
                                </button>
                                <button class="btn btn-outline-success" onclick="applyTemplate('bulk-order')">
                                    <i class="fas fa-boxes me-1"></i>Pembelian Besar
                                </button>
                                <button class="btn btn-outline-warning" onclick="applyTemplate('weekend-sale')">
                                    <i class="fas fa-calendar-weekend me-1"></i>Weekend Sale
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                quickActionsSection.parentNode.insertBefore(templateCard, quickActionsSection);
            }
        });

        // Performance optimization: Lazy load heavy features
        let analyticsLoaded = false;

        function loadAnalytics() {
            if (analyticsLoaded) return;

            // Load chart library and analytics features
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = function() {
                analyticsLoaded = true;
                initializeCharts();
            };
            document.head.appendChild(script);
        }

        function initializeCharts() {
            // Initialize charts for promo analytics
            // This would be implemented when viewing promo details
        }

        // Error handling
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            showToast('Terjadi kesalahan. Silakan refresh halaman.', 'danger');
        });

        // Service worker for offline functionality (optional)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(function(error) {
                console.log('Service Worker registration failed:', error);
            });
        }
    </script>

    <!-- Additional CSS for animations and effects -->
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .promo-code:active {
            transform: scale(0.95);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .progress {
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
        }

        .alert {
            border-left: 4px solid;
        }

        .alert-success {
            border-left-color: #28a745;
        }

        .alert-danger {
            border-left-color: #dc3545;
        }

        .alert-warning {
            border-left-color: #ffc107;
        }

        .alert-info {
            border-left-color: #17a2b8;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Custom scrollbar for modals */
        .modal-body {
            scrollbar-width: thin;
            scrollbar-color: #28a745 #f1f1f1;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #28a745;
            border-radius: 3px;
        }

        /* Responsive improvements */
        @media (max-width: 576px) {
            .btn-group-sm .btn {
                padding: 0.25rem 0.4rem;
                font-size: 0.7rem;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            .stats-number {
                font-size: 1.5rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            .filter-section .row>div {
                margin-bottom: 1rem;
            }
        }

        /* Print styles */
        @media print {

            .sidebar,
            .btn,
            .modal,
            .alert {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .table {
                font-size: 12px;
            }

            .card {
                border: 1px solid #000;
                box-shadow: none;
            }
        }

        /* Dark mode support (optional) */
        /* @media (prefers-color-scheme: dark) {
            .bg-light {
                background-color:rgb(164, 137, 137) !important;
            }

            .card {
                background-color: #2d2d2d;
                border-color: #404040;
            }

            .table {
                color: #fff;
            }

            .table th {
                background-color: #404040;
                color: #fff;
            }

            .form-control,
            .form-select {
                background-color: #ffffff;
                border-color: #404040;
                color: #000;
            }

            .form-control:focus,
            .form-select:focus {
                background-color: #2d2d2d;
                border-color: #28a745;
                color: #fff;
            }
        } */

        /* Accessibility improvements */
        .btn:focus,
        .form-control:focus,
        .form-select:focus {
            outline: 2px solid #28a745;
            outline-offset: 2px;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* High contrast mode */
        @media (prefers-contrast: high) {
            .btn {
                border-width: 2px;
            }

            .card {
                border-width: 2px;
            }

            .table th,
            .table td {
                border-width: 2px;
            }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .animate-fade-in {
                animation: none;
            }

            .btn:hover,
            .card:hover,
            .stats-card:hover {
                transform: none;
            }
        }
    </style>
</body>

</html>