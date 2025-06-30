<?php
    require_once 'includes/config.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Get filters
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Build query
    $sql = "SELECT * FROM orders WHERE user_id = ?";
    $count_sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
    $params = [$user_id];

    if ($status_filter) {
        $sql .= " AND status = ?";
        $count_sql .= " AND status = ?";
        $params[] = $status_filter;
    }

    if ($search) {
        $sql .= " AND (order_number LIKE ? OR shipping_name LIKE ?)";
        $count_sql .= " AND (order_number LIKE ? OR shipping_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Get total count
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_orders = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_orders / $limit);

    // Get orders with pagination
    $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $page_title = 'Pesanan Saya';
    include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Pesanan Saya</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-success">
                <i class="fas fa-shopping-bag me-2"></i>Pesanan Saya
            </h2>
            <p class="text-muted">Kelola dan lacak semua pesanan Anda</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-success">
                <i class="fas fa-plus me-2"></i>Belanja Lagi
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-success mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cari Pesanan:</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nomor pesanan atau nama penerima...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status:</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                        <option value="payment_uploaded" <?php echo $status_filter === 'payment_uploaded' ? 'selected' : ''; ?>>Bukti Diunggah</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Diproses</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Diterima</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Cari
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-refresh me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <?php if (empty($orders)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">Belum Ada Pesanan</h4>
            <p class="text-muted mb-4">Anda belum memiliki pesanan. Mulai berbelanja sekarang!</p>
            <a href="index.php" class="btn btn-success btn-lg">
                <i class="fas fa-shopping-cart me-2"></i>Mulai Belanja
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($orders as $order): ?>
        <div class="col-12 mb-4">
            <div class="card border-success">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Pesanan #<?php echo $order['order_number']; ?>
                            </h6>
                            <small class="text-muted">
                                <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <?php
                            $status_colors = [
                                'pending' => 'warning',
                                'payment_uploaded' => 'info',
                                'confirmed' => 'primary',
                                'processing' => 'primary',
                                'shipped' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $status_labels = [
                                'pending' => 'Menunggu Pembayaran',
                                'payment_uploaded' => 'Bukti Diunggah',
                                'confirmed' => 'Dikonfirmasi',
                                'processing' => 'Diproses',
                                'shipped' => 'Dikirim',
                                'delivered' => 'Diterima',
                                'cancelled' => 'Dibatalkan'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $status_colors[$order['status']] ?? 'secondary'; ?> fs-6">
                                <?php echo $status_labels[$order['status']] ?? ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Order Items Preview -->
                            <?php
                                                        // Get order items for preview
                            $items_stmt = $pdo->prepare("
                                SELECT oi.*, p.nama_produk, p.gambar 
                                FROM order_items oi
                                JOIN produk p ON oi.product_id = p.id
                                WHERE oi.order_id = ?
                                LIMIT 3
                            ");
                            $items_stmt->execute([$order['id']]);
                            $order_items = $items_stmt->fetchAll();
                            
                            // Get total items count
                            $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM order_items WHERE order_id = ?");
                            $count_stmt->execute([$order['id']]);
                            $total_items = $count_stmt->fetch()['total'];
                            ?>
                            
                            <div class="row">
                                <?php foreach ($order_items as $item): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $item['gambar'] ?: 'assets/images/no-image.jpg'; ?>" 
                                             alt="<?php echo $item['nama_produk']; ?>"
                                             class="rounded me-2" width="40" height="40" style="object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <small class="fw-medium d-block">
                                                <?php echo substr($item['nama_produk'], 0, 25) . '...'; ?>
                                            </small>
                                            <small class="text-muted">
                                                <?php echo $item['quantity']; ?>x <?php echo format_rupiah($item['harga']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if ($total_items > 3): ?>
                                <div class="col-12">
                                    <small class="text-muted">
                                        +<?php echo $total_items - 3; ?> produk lainnya
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Shipping Info -->
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shipping-fast me-1"></i>
                                    Dikirim ke: <?php echo htmlspecialchars($order['shipping_name']); ?> - 
                                    <?php echo htmlspecialchars($order['shipping_city']); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Order Summary -->
                            <div class="text-md-end">
                                <div class="mb-2">
                                    <small class="text-muted">Total Pembayaran:</small>
                                    <div class="fs-5 fw-bold text-success">
                                        <?php echo format_rupiah($order['total']); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        Pembayaran: <?php echo $order['payment_method'] === 'bank_transfer' ? 'Transfer Bank' : 'COD'; ?>
                                    </small>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-grid gap-2">
                                    <a href="order_detail.php?order=<?php echo $order['order_number']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </a>
                                    
                                    <?php if ($order['status'] === 'pending' && $order['payment_method'] === 'bank_transfer'): ?>
                                    <a href="upload_payment.php?order=<?php echo $order['order_number']; ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-upload me-1"></i>Upload Bukti
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] === 'shipped'): ?>
                                    <button class="btn btn-success btn-sm" 
                                            onclick="confirmReceived('<?php echo $order['order_number']; ?>')">
                                        <i class="fas fa-check me-1"></i>Konfirmasi Diterima
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] === 'delivered'): ?>
                                    <button class="btn btn-outline-success btn-sm" 
                                            onclick="reorder('<?php echo $order['order_number']; ?>')">
                                        <i class="fas fa-redo me-1"></i>Pesan Lagi
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="cancelOrder('<?php echo $order['order_number']; ?>')">
                                        <i class="fas fa-times me-1"></i>Batalkan
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="card-footer bg-light">
                    <div class="progress" style="height: 6px;">
                        <?php
                        $progress_steps = [
                            'pending' => 20,
                            'payment_uploaded' => 40,
                            'confirmed' => 50,
                            'processing' => 70,
                            'shipped' => 90,
                            'delivered' => 100,
                            'cancelled' => 0
                        ];
                        $progress = $progress_steps[$order['status']] ?? 0;
                        $progress_color = $order['status'] === 'cancelled' ? 'bg-danger' : 'bg-success';
                        ?>
                        <div class="progress-bar <?php echo $progress_color; ?>" 
                             style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">Pesanan Dibuat</small>
                        <small class="text-muted">
                            <?php echo $order['status'] === 'cancelled' ? 'Dibatalkan' : 'Selesai'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Orders pagination" class="mt-4">
        <ul class="pagination justify-content-center">
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
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
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
    <?php endif; ?>
    
    <!-- Order Statistics -->
    <div class="row mt-5">
        <div class="col-12">
            <h4 class="fw-bold mb-3">Statistik Pesanan</h4>
        </div>
        <?php
        // Get order statistics
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status IN ('pending', 'payment_uploaded', 'confirmed', 'processing', 'shipped') THEN 1 ELSE 0 END) as active_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                COALESCE(SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END), 0) as total_spent
            FROM orders 
            WHERE user_id = ?
        ");
        $stats_stmt->execute([$user_id]);
        $stats = $stats_stmt->fetch();
        ?>
        
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-bag text-primary fa-2x mb-2"></i>
                    <h4 class="fw-bold text-primary"><?php echo $stats['total_orders']; ?></h4>
                    <small class="text-muted">Total Pesanan</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h4 class="fw-bold text-success"><?php echo $stats['completed_orders']; ?></h4>
                    <small class="text-muted">Pesanan Selesai</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                    <h4 class="fw-bold text-warning"><?php echo $stats['active_orders']; ?></h4>
                    <small class="text-muted">Pesanan Aktif</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave text-info fa-2x mb-2"></i>
                    <h4 class="fw-bold text-info"><?php echo format_rupiah($stats['total_spent']); ?></h4>
                    <small class="text-muted">Total Belanja</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: all 0.3s ease;
    border-radius: 10px;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}

.btn {
    border-radius: 6px;
}

@media (max-width: 768px) {
    .card-body .row {
        flex-direction: column;
    }
    
    .text-md-end {
        text-align: left !important;
        margin-top: 1rem;
    }
}
</style>

<script>
// Confirm order received
function confirmReceived(orderNumber) {
    if (confirm('Apakah Anda yakin telah menerima pesanan ini?')) {
        fetch('ajax/confirm_received.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_number=${orderNumber}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Pesanan berhasil dikonfirmasi diterima!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Gagal mengkonfirmasi pesanan', 'error');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan sistem', 'error');
        });
    }
}

// Cancel order
function cancelOrder(orderNumber) {
    const reason = prompt('Alasan pembatalan pesanan:');
    if (reason && reason.trim()) {
        fetch('ajax/cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_number=${orderNumber}&reason=${encodeURIComponent(reason.trim())}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Pesanan berhasil dibatalkan!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Gagal membatalkan pesanan', 'error');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan sistem', 'error');
        });
    }
}

// Reorder function
function reorder(orderNumber) {
    if (confirm('Tambahkan semua item dari pesanan ini ke keranjang?')) {
        fetch('ajax/reorder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_number=${orderNumber}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Item berhasil ditambahkan ke keranjang!', 'success');
                setTimeout(() => {
                                        window.location.href = 'keranjang.php';
                }, 1500);
            } else {
                showToast(data.message || 'Gagal menambahkan item ke keranjang', 'error');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan sistem', 'error');
        });
    }
}

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-triangle' : 'info-circle')} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

// Auto-refresh page every 5 minutes to check for status updates
setInterval(() => {
    // Only refresh if there are active orders
    const activeOrders = document.querySelectorAll('.badge.bg-warning, .badge.bg-info, .badge.bg-primary');
    if (activeOrders.length > 0) {
        location.reload();
    }
}, 300000); // 5 minutes
</script>

<?php include 'includes/footer.php'; ?>
