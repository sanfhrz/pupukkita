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

// Fungsi validasi input
function validateInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $nama = validateInput($_POST['nama']);
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                $no_hp = validateInput($_POST['no_hp']);
                $alamat = validateInput($_POST['alamat']);
                $role = in_array($_POST['role'], ['admin', 'customer']) ? $_POST['role'] : 'customer';
                $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

                // Validasi input
                if (!$email) {
                    $message = 'Format email tidak valid!';
                    $message_type = 'danger';
                    break;
                }

                if (strlen($_POST['password']) < 6) {
                    $message = 'Password minimal 6 karakter!';
                    $message_type = 'danger';
                    break;
                }

                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

                try {
                    // Check if email already exists
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check_stmt->execute([$email]);

                    if ($check_stmt->fetch()) {
                        $message = 'Email sudah terdaftar!';
                        $message_type = 'danger';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, no_hp, alamat, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$nama, $email, $password, $no_hp, $alamat, $role, $status]);

                        $message = 'User berhasil ditambahkan!';
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;

            case 'update_user':
                $id = (int)$_POST['id'];
                $nama = validateInput($_POST['nama']);
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                $no_hp = validateInput($_POST['no_hp']);
                $alamat = validateInput($_POST['alamat']);
                $role = in_array($_POST['role'], ['admin', 'customer']) ? $_POST['role'] : 'customer';
                $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

                if (!$email) {
                    $message = 'Format email tidak valid!';
                    $message_type = 'danger';
                    break;
                }

                try {
                    // Check if email already exists for other users
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $check_stmt->execute([$email, $id]);

                    if ($check_stmt->fetch()) {
                        $message = 'Email sudah digunakan user lain!';
                        $message_type = 'danger';
                    } else {
                        $sql = "UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ?, role = ?, status = ?, updated_at = NOW()";
                        $params = [$nama, $email, $no_hp, $alamat, $role, $status];

                        // Update password if provided
                        if (!empty($_POST['password'])) {
                            if (strlen($_POST['password']) < 6) {
                                $message = 'Password minimal 6 karakter!';
                                $message_type = 'danger';
                                break;
                            }
                            $sql .= ", password = ?";
                            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        }

                        $sql .= " WHERE id = ?";
                        $params[] = $id;

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);

                        $message = 'User berhasil diupdate!';
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;

            case 'delete_user':
                $id = (int)$_POST['id'];

                // Prevent deleting current admin
                if ($id == $_SESSION['user_id']) {
                    $message = 'Tidak dapat menghapus akun sendiri!';
                    $message_type = 'danger';
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$id]);

                        $message = 'User berhasil dihapus!';
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
                break;

            case 'toggle_status':
                $id = (int)$_POST['id'];
                $current_status = $_POST['current_status'];
                $new_status = $current_status === 'active' ? 'inactive' : 'active';

                try {
                    $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_status, $id]);

                    $message = "Status user berhasil diubah menjadi $new_status!";
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;

            case 'bulk_action':
                $action_type = $_POST['bulk_action_type'];
                $selected_users = $_POST['selected_users'] ?? [];

                if (empty($selected_users)) {
                    $message = 'Pilih minimal satu user!';
                    $message_type = 'warning';
                } else {
                    try {
                        $placeholders = str_repeat('?,', count($selected_users) - 1) . '?';

                        switch ($action_type) {
                            case 'activate':
                                $stmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id IN ($placeholders)");
                                $stmt->execute($selected_users);
                                $message = count($selected_users) . ' user berhasil diaktifkan!';
                                break;

                            case 'deactivate':
                                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id IN ($placeholders)");
                                $stmt->execute($selected_users);
                                $message = count($selected_users) . ' user berhasil dinonaktifkan!';
                                break;

                            case 'delete':
                                // Prevent deleting current admin
                                if (in_array($_SESSION['user_id'], $selected_users)) {
                                    $message = 'Tidak dapat menghapus akun sendiri!';
                                    $message_type = 'danger';
                                } else {
                                    $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                                    $stmt->execute($selected_users);
                                    $message = count($selected_users) . ' user berhasil dihapus!';
                                }
                                break;
                        }

                        if ($message_type !== 'danger') {
                            $message_type = 'success';
                        }
                    } catch (PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
                break;
        }
    }
}

// Get filters dengan validasi
$search = validateInput($_GET['search'] ?? '');
$role_filter = in_array($_GET['role'] ?? '', ['admin', 'customer']) ? $_GET['role'] : '';
$status_filter = in_array($_GET['status'] ?? '', ['active', 'inactive']) ? $_GET['status'] : '';

// Secure sort parameters
$allowed_sorts = ['created_at', 'nama', 'email', 'role', 'status'];
$allowed_orders = ['ASC', 'DESC'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sorts) ? $_GET['sort'] : 'created_at';
$order = in_array($_GET['order'] ?? '', $allowed_orders) ? $_GET['order'] : 'DESC';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (nama LIKE ? OR email LIKE ? OR no_hp LIKE ?)";
    $count_sql .= " AND (nama LIKE ? OR email LIKE ? OR no_hp LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($role_filter) {
    $sql .= " AND role = ?";
    $count_sql .= " AND role = ?";
    $params[] = $role_filter;
}

if ($status_filter) {
    $sql .= " AND status = ?";
    $count_sql .= " AND status = ?";
    $params[] = $status_filter;
}

// Get total count
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_users = $count_stmt->fetch()['total'];
$total_pages = ceil($total_users / $limit);

// Get users with pagination - SECURE
$sql .= " ORDER BY `$sort` $order LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get user statistics
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'inactive_users' => 0,
    'admin_users' => 0,
    'customer_users' => 0,
    'new_users_today' => 0,
    'new_users_week' => 0
];

try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];

    // Active/Inactive users
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
    $status_data = $stmt->fetchAll();
    foreach ($status_data as $status) {
        if ($status['status'] === 'active') {
            $stats['active_users'] = $status['count'];
        } else {
            $stats['inactive_users'] = $status['count'];
        }
    }

    // Admin/Customer users
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $role_data = $stmt->fetchAll();
    foreach ($role_data as $role) {
        if ($role['role'] === 'admin') {
            $stats['admin_users'] = $role['count'];
        } else {
            $stats['customer_users'] = $role['count'];
        }
    }

    // New users today
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_users_today'] = $stmt->fetch()['count'];

    // New users this week
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stats['new_users_week'] = $stmt->fetch()['count'];
} catch (PDOException $e) {
    // Keep default values
}

// Get user registration trend (last 30 days)
$registration_trend = [];
try {
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as registrations
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $registration_trend = $stmt->fetchAll();
} catch (PDOException $e) {
    $registration_trend = [];
}

// Get user activity (users with recent orders)
$active_users = [];
try {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.nama,
            u.email,
            u.created_at,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total), 0) as total_spent,
            MAX(o.created_at) as last_order
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'customer'
        GROUP BY u.id, u.nama, u.email, u.created_at
        ORDER BY last_order DESC, total_spent DESC
        LIMIT 10
    ");
    $active_users = $stmt->fetchAll();
} catch (PDOException $e) {
    $active_users = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Sahabat Tani</title>
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
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
            border-radius: 8px;
            margin: 0 10px;
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        .top-bar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stats-change {
            font-size: 0.8rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #2c3e50;
            padding: 1rem 0.75rem;
        }

        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #f1f3f4;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }

        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            border-radius: 8px;
            margin: 0 2px;
            border: none;
            color: #28a745;
        }

        .page-link:hover,
        .page-item.active .page-link {
            background: #28a745;
            color: white;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .bulk-actions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }

        .bulk-actions.show {
            display: block;
        }

        .user-status-online {
            position: relative;
        }

        .user-status-online::after {
            content: '';
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #28a745;
            border: 2px solid white;
            border-radius: 50%;
        }

        .activity-timeline {
            max-height: 400px;
            overflow-y: auto;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .timeline-content {
            flex: 1;
            min-width: 0;
        }

        .timeline-content .fw-semibold {
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .timeline-content small {
            font-size: 0.8rem;
        }

        .timeline-content .d-flex {
            margin-top: 8px;
            gap: 10px;
        }

        .timeline-content .text-primary {
            white-space: nowrap;
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
                font-size: 2rem;
            }

            .top-bar {
                flex-direction: column;
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
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Laporan
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link active">
                    <i class="fas fa-users"></i>
                    Data User
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="logout.php" class="nav-link text-danger">
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
                <h4 class="mb-0">👥 User Management</h4>
                <small class="text-muted">Kelola pengguna dan hak akses sistem</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm" onclick="exportUsers()">
                    <i class="fas fa-download me-1"></i> Export
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-1"></i> Tambah User
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show animate-fade-in" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in">
                    <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stats-label">Total Users</div>
                    <div class="stats-change">
                        <span class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?php echo $stats['new_users_week']; ?> minggu ini
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['active_users']); ?></div>
                    <div class="stats-label">Active Users</div>
                    <div class="stats-change">
                        <span class="text-info">
                            <i class="fas fa-percentage me-1"></i>
                            <?php echo $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100, 1) : 0; ?>% dari total
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['admin_users']); ?></div>
                    <div class="stats-label">Admin Users</div>
                    <div class="stats-change">
                        <span class="text-warning">
                            <i class="fas fa-shield-alt me-1"></i>
                            <?php echo number_format($stats['customer_users']); ?> customers
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['new_users_today']); ?></div>
                    <div class="stats-label">New Today</div>
                    <div class="stats-change">
                        <span class="text-danger">
                            <i class="fas fa-calendar-day me-1"></i>
                            Hari ini
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="card animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-chart-line me-2 text-success"></i>
                            Trend Registrasi User (30 Hari Terakhir)
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="registrationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card animate-fade-in" style="animation-delay: 0.5s;">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2 text-info"></i>
                        Distribusi Role
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="roleChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Users Timeline -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card animate-fade-in" style="animation-delay: 0.6s;">
                    <div class="card-header">
                        <i class="fas fa-star me-2 text-warning"></i>
                        Top Active Users
                    </div>
                    <div class="card-body p-3">
                        <div class="activity-timeline">
                            <?php if (empty($active_users)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Belum ada aktivitas user</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($active_users as $user): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-icon bg-success text-white">
                                            <?php echo strtoupper(substr($user['nama'], 0, 2)); ?>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($user['nama']); ?></div>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($user['email']); ?></small>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-success">
                                                    <i class="fas fa-shopping-cart me-1"></i>
                                                    <?php echo $user['total_orders']; ?> orders
                                                </small>
                                                <small class="text-primary fw-medium">
                                                    Rp <?php echo number_format($user['total_spent'], 0, ',', '.'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <form method="POST" onsubmit="return confirmBulkAction()">
                <input type="hidden" name="action" value="bulk_action">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Pilih Aksi:</label>
                        <select class="form-select" name="bulk_action_type" required>
                            <option value="">Pilih aksi...</option>
                            <option value="activate">Aktifkan</option>
                            <option value="deactivate">Nonaktifkan</option>
                            <option value="delete">Hapus</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAllUsers()">
                            <label class="form-check-label" for="selectAll">
                                Pilih semua user yang ditampilkan
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-bolt me-1"></i> Jalankan Aksi
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card animate-fade-in" style="animation-delay: 0.7s;">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <i class="fas fa-table me-2"></i>
                        Data Users
                        <small class="text-muted ms-2">
                            (<?php echo count($users); ?> dari <?php echo $total_users; ?> users)
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="toggleBulkActions()">
                            <i class="fas fa-tasks me-1"></i> Bulk Actions
                        </button>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-sort me-1"></i> Tanggal
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'nama', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-sort-alpha-down me-1"></i> Nama
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filter Form -->
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">🔍</span>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nama, email, atau telepon...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="role">
                            <option value="">👤 Semua Role</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">📊 Semua Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="?" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-refresh"></i>
                            </a>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAllTable" onchange="toggleAllUsers()">
                                    </div>
                                </th>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Bergabung</th>
                                <th>Aktivitas</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Tidak ada user ditemukan</h5>
                                        <p class="text-muted">Coba ubah filter pencarian atau tambah user baru</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input user-checkbox" type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>">
                                            </div>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3 <?php echo $user['status'] === 'active' ? 'user-status-online' : ''; ?>" style="background: <?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>;">
                                                    <?php echo strtoupper(substr($user['nama'], 0, 2)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($user['nama']); ?></div>
                                                    <small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium"><?php echo htmlspecialchars($user['email']); ?></div>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i>
                                                    <?php echo htmlspecialchars($user['no_hp'] ?: 'Tidak ada'); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-success' : 'btn-secondary'; ?>"
                                                    onclick="return confirm('Yakin ingin mengubah status user ini?')"
                                                    <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-<?php echo $user['status'] === 'active' ? 'check' : 'times'; ?> me-1"></i>
                                                    <?php echo ucfirst($user['status']); ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted">
                                                    <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <?php
                                                    $days = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
                                                    echo $days . ' hari lalu';
                                                    ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            // Get user's last activity (last order)
                                            try {
                                                $activity_stmt = $pdo->prepare("SELECT MAX(created_at) as last_activity FROM orders WHERE user_id = ?");
                                                $activity_stmt->execute([$user['id']]);
                                                $last_activity = $activity_stmt->fetch()['last_activity'];
                                                if ($last_activity) {
                                                    $activity_days = floor((time() - strtotime($last_activity)) / (60 * 60 * 24));
                                                    if ($activity_days == 0) {
                                                        echo '<span class="badge bg-success">Hari ini</span>';
                                                    } elseif ($activity_days <= 7) {
                                                        echo '<span class="badge bg-warning">' . $activity_days . ' hari lalu</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">' . $activity_days . ' hari lalu</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-light text-dark">Belum ada</span>';
                                                }
                                            } catch (PDOException $e) {
                                                echo '<span class="badge bg-light text-dark">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-primary" onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus user ini? Data tidak dapat dikembalikan!')">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="User pagination" class="mt-4">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Tambah User Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="addPassword" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('addPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">No. HP</label>
                                    <input type="text" class="form-control" name="no_hp">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Pilih Role</option>
                                        <option value="customer">Customer</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>
                        Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="id" id="editId">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" name="nama" id="editNama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" id="editEmail" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="editPassword">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('editPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">No. HP</label>
                                    <input type="text" class="form-control" name="no_hp" id="editNoHp">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" id="editAlamat" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-select" name="role" id="editRole" required>
                                        <option value="">Pilih Role</option>
                                        <option value="customer">Customer</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" id="editStatus" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>
                        Detail User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="user-avatar mx-auto mb-3" id="viewAvatar" style="width: 100px; height: 100px; font-size: 2rem;">
                            </div>
                            <h5 id="viewNama"></h5>
                            <span class="badge" id="viewRoleBadge"></span>
                        </div>
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>ID User:</strong></td>
                                    <td id="viewId"></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td id="viewEmail"></td>
                                </tr>
                                <tr>
                                    <td><strong>No. HP:</strong></td>
                                    <td id="viewNoHp"></td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat:</strong></td>
                                    <td id="viewAlamat"></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td><span class="badge" id="viewStatusBadge"></span></td>
                                </tr>
                                <tr>
                                    <td><strong>Bergabung:</strong></td>
                                    <td id="viewCreatedAt"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-warning" onclick="editUserFromView()">
                        <i class="fas fa-edit me-1"></i> Edit User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        // Global variables
        let currentUser = null;
        let registrationChart = null;
        let roleChart = null;

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        // Initialize Charts
        function initializeCharts() {
            // Registration Trend Chart
            const registrationData = <?php echo json_encode($registration_trend); ?>;
            const ctx1 = document.getElementById('registrationChart').getContext('2d');
            
            registrationChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: registrationData.map(function(item) {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                    }),
                    datasets: [{
                        label: 'Registrasi Harian',
                        data: registrationData.map(function(item) { return item.registrations; }),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Role Distribution Chart
            const ctx2 = document.getElementById('roleChart').getContext('2d');
            
            roleChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Admin', 'Customer'],
                    datasets: [{
                        data: [<?php echo $stats['admin_users']; ?>, <?php echo $stats['customer_users']; ?>],
                        backgroundColor: ['#dc3545', '#007bff'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Toggle bulk actions
        function toggleBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            bulkActions.classList.toggle('show');
        }

        // Toggle all users selection
        function toggleAllUsers() {
            const selectAll = document.getElementById('selectAll') || document.getElementById('selectAllTable');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });

            // Sync both select all checkboxes
            const selectAll1 = document.getElementById('selectAll');
            const selectAll2 = document.getElementById('selectAllTable');
            if (selectAll1 && selectAll2) {
                selectAll1.checked = selectAll.checked;
                selectAll2.checked = selectAll.checked;
            }
        }

        // Confirm bulk action
        function confirmBulkAction() {
            const selectedUsers = document.querySelectorAll('.user-checkbox:checked');
            const actionType = document.querySelector('select[name="bulk_action_type"]').value;
            
            if (selectedUsers.length === 0) {
                alert('Pilih minimal satu user!');
                return false;
            }

            let message = '';
            switch (actionType) {
                case 'activate':
                    message = 'Yakin ingin mengaktifkan ' + selectedUsers.length + ' user?';
                    break;
                case 'deactivate':
                    message = 'Yakin ingin menonaktifkan ' + selectedUsers.length + ' user?';
                    break;
                case 'delete':
                    message = 'Yakin ingin menghapus ' + selectedUsers.length + ' user? Data tidak dapat dikembalikan!';
                    break;
                default:
                    message = 'Yakin ingin menjalankan aksi pada ' + selectedUsers.length + ' user?';
            }

            return confirm(message);
        }

        // View user details
        function viewUser(user) {
            currentUser = user;
            
            // Set avatar
            const avatar = document.getElementById('viewAvatar');
            avatar.textContent = user.nama.substring(0, 2).toUpperCase();
            avatar.style.background = '#' + Math.floor(Math.random()*16777215).toString(16);
            
            // Set user details
            document.getElementById('viewNama').textContent = user.nama;
            document.getElementById('viewId').textContent = user.id;
            document.getElementById('viewEmail').textContent = user.email;
            document.getElementById('viewNoHp').textContent = user.no_hp || 'Tidak ada';
            document.getElementById('viewAlamat').textContent = user.alamat || 'Tidak ada';
            
            // Set role badge
            const roleBadge = document.getElementById('viewRoleBadge');
            roleBadge.textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
            roleBadge.className = 'badge ' + (user.role === 'admin' ? 'bg-danger' : 'bg-primary');
            
            // Set status badge
            const statusBadge = document.getElementById('viewStatusBadge');
            statusBadge.textContent = user.status.charAt(0).toUpperCase() + user.status.slice(1);
            statusBadge.className = 'badge ' + (user.status === 'active' ? 'bg-success' : 'bg-secondary');
            
            // Set created date
            const createdDate = new Date(user.created_at);
            document.getElementById('viewCreatedAt').textContent = createdDate.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
            modal.show();
        }

        // Edit user
        function editUser(user) {
            currentUser = user;
            
            // Fill form fields
            document.getElementById('editId').value = user.id;
            document.getElementById('editNama').value = user.nama;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editNoHp').value = user.no_hp || '';
            document.getElementById('editAlamat').value = user.alamat || '';
            document.getElementById('editRole').value = user.role;
            document.getElementById('editStatus').value = user.status;
            
            // Clear password field
            document.getElementById('editPassword').value = '';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }

        // Edit user from view modal
        function editUserFromView() {
            if (currentUser) {
                // Hide view modal
                const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewUserModal'));
                viewModal.hide();
                
                // Show edit modal after view modal is hidden
                setTimeout(function() {
                    editUser(currentUser);
                }, 300);
            }
        }

        // Export users
        function exportUsers() {
            // Create CSV content
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "ID,Nama,Email,No HP,Alamat,Role,Status,Tanggal Bergabung\n";
            
            // Add user data
            <?php echo "const usersData = " . json_encode($users) . ";\n"; ?>
            
            usersData.forEach(function(user) {
                const row = [
                    user.id,
                    '"' + user.nama + '"',
                    user.email,
                    '"' + (user.no_hp || '') + '"',
                    '"' + (user.alamat || '') + '"',
                    user.role,
                    user.status,
                    user.created_at
                ].join(',');
                csvContent += row + "\n";
            });
            
            // Download file
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "users_export_" + new Date().toISOString().split('T')[0] + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Print functionality
        function printUserList() {
            window.print();
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

        // Update checkbox states
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('user-checkbox')) {
                const checkboxes = document.querySelectorAll('.user-checkbox');
                const selectAll1 = document.getElementById('selectAll');
                const selectAll2 = document.getElementById('selectAllTable');
                
                const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
                const totalCount = checkboxes.length;
                
                // Update select all checkboxes
                if (selectAll1) selectAll1.checked = checkedCount === totalCount;
                if (selectAll2) selectAll2.checked = checkedCount === totalCount;
                
                // Show/hide bulk actions based on selection
                const bulkActions = document.getElementById('bulkActions');
                if (checkedCount > 0) {
                    bulkActions.classList.add('show');
                } else {
                    bulkActions.classList.remove('show');
                }
            }
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Add user form validation
            const addUserForm = document.querySelector('#addUserModal form');
            if (addUserForm) {
                addUserForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('addPassword').value;
                    if (password.length < 6) {
                        e.preventDefault();
                        alert('Password minimal 6 karakter!');
                        return false;
                    }
                });
            }

            // Edit user form validation
            const editUserForm = document.querySelector('#editUserModal form');
            if (editUserForm) {
                editUserForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('editPassword').value;
                    if (password && password.length < 6) {
                        e.preventDefault();
                        alert('Password minimal 6 karakter!');
                        return false;
                    }
                });
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + N = New User
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                const addModal = new bootstrap.Modal(document.getElementById('addUserModal'));
                addModal.show();
            }
        });

        console.log('User Management System initialized successfully');
    </script>
</body>
</html>