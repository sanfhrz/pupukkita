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

    // Handle order status updates
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] == 'update_status') {
            $order_id = (int)$_POST['order_id'];
            $new_status = $_POST['status'];
            $notes = trim($_POST['notes'] ?? '');

            $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

            if (in_array($new_status, $valid_statuses)) {
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_status, $notes, $order_id]);

                    // Send notification email (implement later)
                    // sendOrderStatusEmail($order_id, $new_status);

                    $message = 'Status pesanan berhasil diupdate!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
    }

    // Get filter parameters
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';

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

    // Valid sort columns
    $valid_sorts = ['created_at', 'total_amount', 'status', 'nama'];
    if (!in_array($sort, $valid_sorts)) {
        $sort = 'created_at';
    }

    $valid_orders = ['ASC', 'DESC'];
    if (!in_array($order, $valid_orders)) {
        $order = 'DESC';
    }

    // Get orders with user info - GANTI phone JADI no_hp
    try {
        $query = "SELECT o.*, u.nama, u.email, u.no_hp 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    $where_clause 
                    ORDER BY o.$sort $order";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
    } catch (PDOException $e) {
        $orders = [];
        $message = 'Error loading orders: ' . $e->getMessage();
        $message_type = 'danger';
    }

    // Get order statistics
    try {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'shipped' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'revenue' => 0
        ];

        $stmt = $pdo->query("SELECT status, COUNT(*) as count, SUM(total_amount) as revenue FROM orders GROUP BY status");
        $results = $stmt->fetchAll();

        foreach ($results as $result) {
            $stats[$result['status']] = $result['count'];
            $stats['total'] += $result['count'];
            if (in_array($result['status'], ['confirmed', 'shipped', 'delivered'])) {
                $stats['revenue'] += $result['revenue'];
            }
        }
    } catch (PDOException $e) {
        $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0, 'revenue' => 0];
    }
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin Sahabat Tani</title>
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
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-card.pending {
            border-left-color: #ffc107;
        }

        .stats-card.confirmed {
            border-left-color: #17a2b8;
        }

        .stats-card.shipped {
            border-left-color: #007bff;
        }

        .stats-card.delivered {
            border-left-color: #28a745;
        }

        .stats-card.cancelled {
            border-left-color: #dc3545;
        }

        .stats-card.revenue {
            border-left-color: #6f42c1;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
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

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }

        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #e9ecef;
            padding: 0.75rem 1rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
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
                <a href="orders.php" class="nav-link active">
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
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    Data User
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header animate-fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">
                        <i class="fas fa-shopping-cart me-3"></i>
                        Kelola Pesanan
                    </h2>
                    <p class="text-muted mb-0">Kelola semua pesanan pelanggan</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="exportOrders()">
                        <i class="fas fa-download me-2"></i>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="refreshOrders()">
                        <i class="fas fa-sync me-2"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid animate-fade-in">
            <div class="stats-card pending">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $stats['pending']; ?></h3>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card confirmed">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $stats['confirmed']; ?></h3>
                        <p class="text-muted mb-0">Dikonfirmasi</p>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card shipped">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $stats['shipped']; ?></h3>
                        <p class="text-muted mb-0">Dikirim</p>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-shipping-fast fa-2x"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card delivered">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $stats['delivered']; ?></h3>
                        <p class="text-muted mb-0">Selesai</p>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-double fa-2x"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card cancelled">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $stats['cancelled']; ?></h3>
                        <p class="text-muted mb-0">Dibatalkan</p>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card revenue">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">Rp <?php echo number_format($stats['revenue'], 0, ',', '.'); ?></h3>
                        <p class="text-muted mb-0">Total Revenue</p>
                    </div>
                    <div class="text-purple">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section animate-fade-in">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cari Pesanan</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="ID, nama, email...">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                        <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                        <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="date_from"
                        value="<?php echo htmlspecialchars($date_from); ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="date_to"
                        value="<?php echo htmlspecialchars($date_to); ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Urutkan</label>
                    <select class="form-select" name="sort">
                        <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Tanggal</option>
                        <option value="total_amount" <?php echo $sort == 'total_amount' ? 'selected' : ''; ?>>Total</option>
                        <option value="status" <?php echo $sort == 'status' ? 'selected' : ''; ?>>Status</option>
                        <option value="nama" <?php echo $sort == 'nama' ? 'selected' : ''; ?>>Nama</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-outline-primary d-block w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>

            <div class="mt-3">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-refresh me-1"></i>
                        Reset Filter
                    </a>
                    <a href="?status=pending" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-clock me-1"></i>
                        Pending
                    </a>
                    <a href="?status=confirmed" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-check-circle me-1"></i>
                        Dikonfirmasi
                    </a>
                    <span class="badge bg-info fs-6">
                        Total: <?php echo count($orders); ?> pesanan
                    </span>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card animate-fade-in">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>
                Daftar Pesanan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
                                        <h5>Belum ada pesanan</h5>
                                        <p>Pesanan akan muncul di sini ketika pelanggan melakukan pembelian</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($order['nama']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($order['email']); ?>
                                                    <?php if ($order['no_hp']): ?>
                                                        <br><?php echo htmlspecialchars($order['no_hp']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($order['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    $status_text = 'Pending';
                                                    break;
                                                case 'confirmed':
                                                    $status_class = 'bg-info';
                                                    $status_text = 'Dikonfirmasi';
                                                    break;
                                                case 'shipped':
                                                    $status_class = 'bg-primary';
                                                    $status_text = 'Dikirim';
                                                    break;
                                                case 'delivered':
                                                    $status_class = 'bg-success';
                                                    $status_text = 'Selesai';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger';
                                                    $status_text = 'Dibatalkan';
                                                    break;
                                                default:
                                                    $status_class = 'bg-secondary';
                                                    $status_text = ucfirst($order['status']);
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary"
                                                    onclick="viewOrder(<?php echo $order['id']; ?>)"
                                                    data-bs-toggle="tooltip" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success"
                                                    onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')"
                                                    data-bs-toggle="tooltip" title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($order['status'] == 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        onclick="cancelOrder(<?php echo $order['id']; ?>)"
                                                        data-bs-toggle="tooltip" title="Batalkan">
                                                        <i class="fas fa-times"></i>
                                                    </button>
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
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Detail Pesanan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetails">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Memuat detail pesanan...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="printOrder()">
                        <i class="fas fa-print me-2"></i>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Update Status Pesanan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="update_order_id">

                        <div class="mb-3">
                            <label class="form-label">Status Baru</label>
                            <select class="form-select" name="status" id="update_status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Dikonfirmasi</option>
                                <option value="shipped">Dikirim</option>
                                <option value="delivered">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan Admin (Opsional)</label>
                            <textarea class="form-control" name="notes" rows="3"
                                placeholder="Tambahkan catatan untuk pelanggan..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Info:</strong> Pelanggan akan mendapat notifikasi email tentang perubahan status ini.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Batalkan Pesanan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="cancel_order_id">
                        <input type="hidden" name="status" value="cancelled">

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Peringatan!</strong> Anda akan membatalkan pesanan ini.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alasan Pembatalan *</label>
                            <textarea class="form-control" name="notes" rows="3" required
                                placeholder="Jelaskan alasan pembatalan pesanan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>
                            Ya, Batalkan Pesanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // View order details
        async function viewOrder(orderId) {
            try {
                const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
                modal.show();

                const response = await fetch(`get_order_details.php?id=${orderId}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('orderDetails').innerHTML = data.html;
                } else {
                    document.getElementById('orderDetails').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error: ${data.message}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('orderDetails').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading order details
                    </div>
                `;
            }
        }

        // Update order status
        function updateStatus(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('update_status').value = currentStatus;

            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        }

        // Cancel order
        function cancelOrder(orderId) {
            document.getElementById('cancel_order_id').value = orderId;

            const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
            modal.show();
        }

        // Print order
        function printOrder() {
            const orderDetails = document.getElementById('orderDetails').innerHTML;
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Order</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        @media print {
                            .no-print { display: none !important; }
                        }
                        body { font-family: Arial, sans-serif; }
                    </style>
                </head>
                <body>
                    <div class="container mt-4">
                        <div class="text-center mb-4">
                            <h2>Sahabat Tani</h2>
                            <p>Detail Pesanan</p>
                        </div>
                        ${orderDetails}
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            window.onafterprint = function() {
                                window.close();
                            }
                        }
                    <\/script>
                </body>
                </html>
            `);

            printWindow.document.close();
        }

        // Export orders
        function exportOrders() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'export_orders.php?' + params.toString();
        }

        // Refresh orders
        function refreshOrders() {
            location.reload();
        }

        // Real-time search with debounce
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        }

        // Auto-hide alerts after 5 seconds
        // Initialize everything when DOM loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.classList.remove('show');
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alert.parentNode.removeChild(alert);
                            }
                        }, 150);
                    }
                }, 5000);
            });
        });


        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // F5 = Refresh
            if (e.key === 'F5') {
                e.preventDefault();
                refreshOrders();
            }

            // Ctrl + E = Export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportOrders();
            }

            // Escape = Close modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                });
            }
        });
    </script>
</body>

</html>