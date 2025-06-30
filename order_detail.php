<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    header('Location: my_orders.php');
    exit();
}

// Get order details with items
$stmt = $pdo->prepare("
    SELECT o.*, u.nama as customer_name, u.email as customer_email, u.no_hp as customer_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Pesanan tidak ditemukan';
    header('Location: my_orders.php');
    exit();
}

// Get order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.nama_produk, p.gambar, p.kategori
    FROM order_items oi
    JOIN produk p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$items_stmt->execute([$order['id']]);
$order_items = $items_stmt->fetchAll();

// Get order tracking
$tracking_stmt = $pdo->prepare("
    SELECT * FROM order_tracking 
    WHERE order_id = ? 
    ORDER BY created_at ASC
");
$tracking_stmt->execute([$order['id']]);
$tracking = $tracking_stmt->fetchAll();

$page_title = 'Detail Pesanan #' . $order_number;
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="my_orders.php">Pesanan Saya</a></li>
            <li class="breadcrumb-item active">Detail Pesanan</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-success">
                <i class="fas fa-receipt me-2"></i>Detail Pesanan
            </h2>
            <p class="text-muted mb-0">Pesanan #<?php echo $order_number; ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="mt-2">
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
                <span class="badge bg-<?php echo $status_colors[$order['status']] ?? 'secondary'; ?> fs-6 px-3 py-2">
                    <?php echo $status_labels[$order['status']] ?? ucfirst($order['status']); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Order Info -->
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Item Pesanan</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $item['gambar'] ?: 'assets/images/no-image.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['nama_produk']); ?>"
                                                 class="rounded me-3" width="60" height="60" style="object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['nama_produk']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['kategori']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <?php echo format_rupiah($item['price']); ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-light text-dark"><?php echo $item['quantity']; ?></span>
                                    </td>
                                    <td class="align-middle fw-bold text-success">
                                        <?php echo format_rupiah($item['price'] * $item['quantity']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3">Subtotal</th>
                                    <th><?php echo format_rupiah($order['subtotal']); ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3">Ongkos Kirim</th>
                                    <th><?php echo format_rupiah($order['shipping_cost']); ?></th>
                                </tr>
                                <?php if ($order['discount_amount'] > 0): ?>
                                <tr>
                                    <th colspan="3">Diskon</th>
                                    <th class="text-danger">-<?php echo format_rupiah($order['discount_amount']); ?></th>
                                </tr>
                                <?php endif; ?>
                                <tr class="table-success">
                                    <th colspan="3">Total</th>
                                    <th class="fs-5"><?php echo format_rupiah($order['total']); ?></th>
                                                                    </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="card border-info mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Informasi Pengiriman</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Alamat Pengiriman:</h6>
                            <address class="mb-0">
                                <strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong><br>
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                                <?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_postal_code']); ?><br>
                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($order['shipping_phone']); ?>
                            </address>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Detail Pengiriman:</h6>
                            <div class="mb-2">
                                <strong>Kurir:</strong> <?php echo strtoupper($order['shipping_courier']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Layanan:</strong> <?php echo htmlspecialchars($order['shipping_service']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Estimasi:</strong> <?php echo $order['shipping_etd']; ?> hari
                            </div>
                            <?php if ($order['tracking_number']): ?>
                            <div class="mb-2">
                                <strong>No. Resi:</strong> 
                                <span class="badge bg-primary"><?php echo $order['tracking_number']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($order['customer_notes']): ?>
                    <hr>
                    <h6 class="fw-bold">Catatan Pesanan:</h6>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Tracking -->
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-route me-2"></i>Lacak Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tracking)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada update tracking</p>
                    </div>
                    <?php else: ?>
                    <div class="timeline">
                        <?php foreach (array_reverse($tracking) as $index => $track): ?>
                        <div class="timeline-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="timeline-marker">
                                <i class="fas fa-<?php echo getTrackingIcon($track['status']); ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="timeline-title"><?php echo htmlspecialchars($track['title']); ?></h6>
                                <p class="timeline-description"><?php echo htmlspecialchars($track['description']); ?></p>
                                <small class="timeline-time text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('d F Y, H:i', strtotime($track['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Summary & Actions -->
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card border-warning mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Ringkasan Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6"><strong>Nomor Pesanan:</strong></div>
                        <div class="col-6"><?php echo $order['order_number']; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Tanggal Pesan:</strong></div>
                        <div class="col-6"><?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Metode Bayar:</strong></div>
                        <div class="col-6">
                            <?php echo $order['payment_method'] === 'bank_transfer' ? 'Transfer Bank' : 'COD'; ?>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Status Bayar:</strong></div>
                        <div class="col-6">
                            <?php
                            $payment_status_colors = [
                                'pending' => 'warning',
                                'paid' => 'success',
                                'failed' => 'danger'
                            ];
                            $payment_status_labels = [
                                'pending' => 'Menunggu',
                                'paid' => 'Lunas',
                                'failed' => 'Gagal'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $payment_status_colors[$order['payment_status']] ?? 'secondary'; ?>">
                                <?php echo $payment_status_labels[$order['payment_status']] ?? ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6"><strong>Total Bayar:</strong></div>
                        <div class="col-6"><strong class="text-success fs-5"><?php echo format_rupiah($order['total']); ?></strong></div>
                    </div>
                </div>
            </div>

            <!-- Payment Proof -->
            <?php if ($order['payment_method'] === 'bank_transfer'): ?>
            <div class="card border-secondary mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Bukti Pembayaran</h5>
                </div>
                <div class="card-body">
                    <?php if ($order['payment_proof']): ?>
                    <div class="text-center">
                        <img src="uploads/payments/<?php echo $order['payment_proof']; ?>" 
                             alt="Bukti Pembayaran" class="img-fluid rounded mb-3" 
                             style="max-height: 300px; cursor: pointer;"
                             onclick="showImageModal(this.src)">
                        <div>
                            <small class="text-muted">Klik gambar untuk memperbesar</small>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-upload fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada bukti pembayaran</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Aksi</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <!-- Upload Payment Proof -->
                        <?php if ($order['status'] === 'pending' && $order['payment_method'] === 'bank_transfer' && !$order['payment_proof']): ?>
                        <a href="upload_payment.php?order=<?php echo $order_number; ?>" class="btn btn-warning">
                            <i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran
                        </a>
                        <?php endif; ?>

                        <!-- Confirm Received -->
                        <?php if ($order['status'] === 'shipped'): ?>
                        <button class="btn btn-success" onclick="confirmReceived('<?php echo $order_number; ?>')">
                            <i class="fas fa-check me-2"></i>Konfirmasi Diterima
                        </button>
                        <?php endif; ?>

                        <!-- Cancel Order -->
                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                        <button class="btn btn-outline-danger" onclick="cancelOrder('<?php echo $order_number; ?>')">
                            <i class="fas fa-times me-2"></i>Batalkan Pesanan
                        </button>
                        <?php endif; ?>

                        <!-- Reorder -->
                        <?php if ($order['status'] === 'delivered'): ?>
                        <button class="btn btn-outline-primary" onclick="reorder('<?php echo $order_number; ?>')">
                            <i class="fas fa-redo me-2"></i>Pesan Lagi
                        </button>
                        <?php endif; ?>

                        <!-- Print Invoice -->
                        <a href="invoice.php?order=<?php echo $order_number; ?>" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-print me-2"></i>Cetak Invoice
                        </a>

                        <!-- Back to Orders -->
                        <a href="my_orders.php" class="btn btn-outline-dark">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Pesanan
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="card border-info mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Butuh Bantuan?</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Hubungi customer service kami:</p>
                    <div class="d-grid gap-2">
                        <a href="https://wa.me/6281234567890" class="btn btn-success btn-sm" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                        </a>
                        <a href="mailto:support@sahabattani.com" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-1"></i>Email Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bukti Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Bukti Pembayaran" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-item.active .timeline-marker {
    background: #28a745;
    color: white;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 3px solid white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #28a745;
}

.timeline-item.active .timeline-content {
    background: #d4edda;
    border-left-color: #28a745;
}

.timeline-title {
    margin-bottom: 5px;
    color: #2c3e50;
}

.timeline-description {
    margin-bottom: 10px;
    color: #6c757d;
}

.timeline-time {
    font-size: 0.85rem;
}

.card {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

@media (max-width: 768px) {
    .timeline {
        padding-left: 20px;
    }
    
    .timeline-marker {
        left: -15px;
        width: 25px;
        height: 25px;
        font-size: 10px;
    }
    
    .timeline::before {
        left: 10px;
    }
}
</style>

<script>
// Show image modal
function showImageModal(src) {
    document.getElementById('modalImage').src = src;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

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
</script>

<?php
// Helper function for tracking icons
function getTrackingIcon($status) {
    $icons = [
        'pending' => 'clock',
        'payment_uploaded' => 'upload',
        'confirmed' => 'check-circle',
        'processing' => 'cog',
        'shipped' => 'truck',
        'delivered' => 'check-double',
        'cancelled' => 'times-circle'
    ];
    
    return $icons[$status] ?? 'circle';
}

include 'includes/footer.php';
?>
