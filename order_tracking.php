<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get order number from URL
$order_number = $_GET['order'] ?? '';
if (empty($order_number)) {
    header('Location: my_orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.nama as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Pesanan tidak ditemukan!';
    header('Location: my_orders.php');
    exit();
}

// Get tracking history
$tracking_stmt = $pdo->prepare("
    SELECT ot.*, u.nama as updated_by_name
    FROM order_tracking ot
    LEFT JOIN users u ON ot.created_by = u.id
    WHERE ot.order_id = ?
    ORDER BY ot.created_at ASC
");
$tracking_stmt->execute([$order['id']]);
$tracking_history = $tracking_stmt->fetchAll();

// Get order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.nama_produk, p.gambar 
    FROM order_items oi 
    JOIN produk p ON oi.produk_id = p.id 
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order['id']]);
$order_items = $items_stmt->fetchAll();

$page_title = 'Lacak Pesanan';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="my_orders.php">Pesanan Saya</a></li>
            <li class="breadcrumb-item active">Lacak Pesanan</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <!-- Order Header -->
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-search me-2"></i>Lacak Pesanan #<?php echo $order['order_number']; ?>
                            </h5>
                        </div>
                        <div>
                            <span class="badge bg-light text-dark fs-6">
                                <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success mb-3">📦 Status Pesanan</h6>
                            <div class="d-flex align-items-center mb-3">
                                <div class="status-icon me-3">
                                    <?php
                                    $status_icons = [
                                        'pending' => ['icon' => 'clock', 'color' => 'warning'],
                                        'payment_uploaded' => ['icon' => 'upload', 'color' => 'info'],
                                        'confirmed' => ['icon' => 'check-circle', 'color' => 'success'],
                                        'processing' => ['icon' => 'cog', 'color' => 'primary'],
                                        'shipped' => ['icon' => 'truck', 'color' => 'info'],
                                        'delivered' => ['icon' => 'check-double', 'color' => 'success'],
                                        'cancelled' => ['icon' => 'times-circle', 'color' => 'danger']
                                    ];
                                    $current_status = $status_icons[$order['status']] ?? ['icon' => 'question', 'color' => 'secondary'];
                                    ?>
                                    <i class="fas fa-<?php echo $current_status['icon']; ?> fa-2x text-<?php echo $current_status['color']; ?>"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-<?php echo $current_status['color']; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => 'Menunggu Pembayaran',
                                            'payment_uploaded' => 'Bukti Pembayaran Diunggah',
                                            'confirmed' => 'Pembayaran Dikonfirmasi',
                                            'processing' => 'Sedang Diproses',
                                            'shipped' => 'Dalam Pengiriman',
                                            'delivered' => 'Pesanan Diterima',
                                            'cancelled' => 'Pesanan Dibatalkan'
                                        ];
                                        echo $status_text[$order['status']] ?? 'Status Tidak Diketahui';
                                        ?>
                                    </h5>
                                    <small class="text-muted">
                                        Diperbarui: <?php echo date('d M Y, H:i', strtotime($order['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php if ($order['tracking_number']): ?>
                            <h6 class="fw-bold text-success mb-3">🚚 Informasi Pengiriman</h6>
                            <div class="border rounded p-3 bg-light">
                                <div class="mb-2">
                                    <strong>Kurir:</strong> <?php echo strtoupper($order['courier'] ?? 'Reguler'); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>No. Resi:</strong> 
                                    <span class="fw-bold text-primary" style="cursor: pointer;" 
                                          onclick="copyToClipboard('<?php echo $order['tracking_number']; ?>', 'Nomor resi')"
                                          title="Klik untuk copy">
                                        <?php echo $order['tracking_number']; ?>
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <a href="https://www.jne.co.id/id/tracking/trace" target="_blank" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-external-link-alt me-1"></i>Lacak di JNE
                                    </a>
                                    <a href="https://www.posindonesia.co.id/id/tracking" target="_blank" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-external-link-alt me-1"></i>Lacak di POS
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                            <h6 class="fw-bold text-muted mb-3">🚚 Informasi Pengiriman</h6>
                            <div class="text-center py-3">
                                <i class="fas fa-truck fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Nomor resi akan tersedia setelah pesanan dikirim</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>Timeline Pesanan
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($tracking_history)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clock fa-2x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada riwayat tracking untuk pesanan ini</p>
                    </div>
                    <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($tracking_history as $index => $track): ?>
                        <div class="timeline-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="timeline-marker">
                                <i class="fas fa-<?php 
                                    echo $track['status'] === 'pending' ? 'clock' :
                                        ($track['status'] === 'payment_uploaded' ? 'upload' :
                                        ($track['status'] === 'payment_verified' ? 'check' :
                                        ($track['status'] === 'processing' ? 'cog' :
                                        ($track['status'] === 'shipped' ? 'truck' :
                                        ($track['status'] === 'delivered' ? 'check-double' : 'circle')))));
                                ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($track['title']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('d M Y, H:i', strtotime($track['created_at'])); ?>
                                        <?php if ($track['updated_by_name']): ?>
                                        • oleh <?php echo htmlspecialchars($track['updated_by_name']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <?php if ($track['description']): ?>
                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($track['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-box me-2"></i>Item Pesanan (<?php echo count($order_items); ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                    <div class="d-flex align-items-center mb-3 pb-3 <?php echo end($order_items) !== $item ? 'border-bottom' : ''; ?>">
                        <img src="<?php echo $item['gambar'] ?: 'assets/images/no-image.jpg'; ?>" 
                             alt="<?php echo $item['nama_produk']; ?>"
                             class="rounded me-3" width="60" height="60" style="object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['nama_produk']); ?></h6>
                            <div class="text-muted small">
                                <?php echo $item['quantity']; ?> × <?php echo format_rupiah($item['harga']); ?>
                            </div>
                        </div>
                                                <div class="text-end">
                            <div class="fw-bold text-success">
                                <?php echo format_rupiah($item['subtotal']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card border-success mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Ringkasan Pesanan
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end"><?php echo format_rupiah($order['subtotal']); ?></td>
                        </tr>
                        <tr>
                            <td>Ongkos Kirim:</td>
                            <td class="text-end"><?php echo format_rupiah($order['shipping_cost']); ?></td>
                        </tr>
                        <?php if ($order['cod_fee'] > 0): ?>
                        <tr>
                            <td>Biaya COD:</td>
                            <td class="text-end"><?php echo format_rupiah($order['cod_fee']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($order['discount'] > 0): ?>
                        <tr class="text-success">
                            <td>Diskon:</td>
                            <td class="text-end">-<?php echo format_rupiah($order['discount']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="border-top fw-bold">
                            <td>Total:</td>
                            <td class="text-end text-success"><?php echo format_rupiah($order['total']); ?></td>
                        </tr>
                    </table>

                    <hr>

                    <!-- Payment Status -->
                    <div class="mb-3">
                        <h6 class="fw-bold mb-2">💳 Status Pembayaran</h6>
                        <span class="badge bg-<?php 
                            echo $order['payment_status'] === 'paid' ? 'success' : 
                                ($order['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                        ?> w-100 p-2">
                            <?php 
                            $payment_status_text = [
                                'pending' => 'Menunggu Pembayaran',
                                'paid' => 'Sudah Dibayar',
                                'failed' => 'Pembayaran Gagal'
                            ];
                            echo $payment_status_text[$order['payment_status']] ?? $order['payment_status'];
                            ?>
                        </span>
                    </div>

                    <!-- Shipping Address -->
                    <div class="mb-3">
                        <h6 class="fw-bold mb-2">📍 Alamat Pengiriman</h6>
                        <div class="small text-muted">
                            <strong><?php echo htmlspecialchars($order['nama_lengkap']); ?></strong><br>
                            <?php echo htmlspecialchars($order['telepon']); ?><br>
                            <?php echo htmlspecialchars($order['alamat']); ?><br>
                            <?php echo htmlspecialchars($order['kota']); ?>, <?php echo htmlspecialchars($order['provinsi']); ?> <?php echo htmlspecialchars($order['kode_pos']); ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-grid gap-2">
                        <?php if ($order['status'] === 'pending' && $order['payment_method'] === 'bank_transfer'): ?>
                        <a href="order_success.php?order=<?php echo $order['order_number']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload me-1"></i>Upload Bukti Bayar
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'shipped' && $order['tracking_number']): ?>
                        <button class="btn btn-info btn-sm" onclick="trackExternal()">
                            <i class="fas fa-external-link-alt me-1"></i>Lacak di Website Kurir
                        </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['pending', 'payment_uploaded']) && $order['payment_method'] === 'bank_transfer'): ?>
                        <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder()">
                            <i class="fas fa-times me-1"></i>Batalkan Pesanan
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-headset me-2"></i>Butuh Bantuan?
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-comments fa-2x text-info mb-2"></i>
                        <p class="small text-muted mb-3">Tim customer service kami siap membantu Anda 24/7</p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="https://wa.me/6281234567890?text=Halo, saya butuh bantuan untuk pesanan <?php echo $order['order_number']; ?>" 
                           target="_blank" class="btn btn-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                        </a>
                        <a href="mailto:support@sahabattani.com?subject=Bantuan Pesanan <?php echo $order['order_number']; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-1"></i>Email Support
                        </a>
                        <a href="tel:+6281234567890" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-phone me-1"></i>Telepon
                        </a>
                    </div>

                    <hr>

                    <div class="small text-muted">
                        <h6 class="fw-bold">FAQ:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-1">• Berapa lama verifikasi pembayaran?</li>
                            <li class="mb-1">• Bagaimana cara mengubah alamat?</li>
                            <li class="mb-1">• Kapan pesanan dikirim?</li>
                        </ul>
                        <a href="faq.php" class="text-decoration-none">Lihat semua FAQ →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="text-center mt-4">
        <a href="my_orders.php" class="btn btn-outline-success">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Pesanan
        </a>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="copyToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-copy text-success me-2"></i>
            <strong class="me-auto">Berhasil!</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Nomor resi berhasil disalin!
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Batalkan Pesanan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin membatalkan pesanan ini?</p>
                <div class="alert alert-warning">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Pesanan yang sudah dibatalkan tidak dapat dikembalikan. 
                        Jika sudah melakukan pembayaran, dana akan dikembalikan dalam 3-5 hari kerja.
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan pembatalan:</label>
                    <select class="form-select" id="cancelReason">
                        <option value="">Pilih alasan...</option>
                        <option value="Berubah pikiran">Berubah pikiran</option>
                        <option value="Salah pesan">Salah pesan</option>
                        <option value="Terlalu lama">Proses terlalu lama</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan tambahan (opsional):</label>
                    <textarea class="form-control" id="cancelNote" rows="3" placeholder="Jelaskan alasan pembatalan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancel()">
                    <i class="fas fa-times me-1"></i>Ya, Batalkan Pesanan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Styles */
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

.timeline-item:last-child {
    margin-bottom: 0;
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
    border: 3px solid #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.timeline-item.active .timeline-marker {
    background: #28a745;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #28a745;
    margin-left: 15px;
}

.timeline-item.active .timeline-content {
    background: #d4edda;
    border-left-color: #28a745;
}

.timeline-header h6 {
    color: #28a745;
    font-weight: 600;
}

/* Status Icon Animation */
.status-icon i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Card Hover Effects */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Responsive Timeline */
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
    
    .timeline-content {
        margin-left: 10px;
        padding: 10px;
    }
}

/* Badge Animations */
.badge {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Button Hover Effects */
.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Sticky Sidebar */
.sticky-top {
    z-index: 1020;
}
</style>

<script>
// Copy to clipboard function
function copyToClipboard(text, label) {
    navigator.clipboard.writeText(text).then(function() {
        document.getElementById('toastMessage').textContent = `${label} berhasil disalin!`;
        const toast = new bootstrap.Toast(document.getElementById('copyToast'));
        toast.show();
    }).catch(function() {
        alert(`Gagal menyalin ${label}`);
    });
}

// Track external function
function trackExternal() {
    const courier = '<?php echo $order['courier'] ?? ''; ?>';
    const trackingNumber = '<?php echo $order['tracking_number'] ?? ''; ?>';
    
    let url = '';
    switch(courier.toLowerCase()) {
        case 'jne':
            url = `https://www.jne.co.id/id/tracking/trace/${trackingNumber}`;
            break;
        case 'pos':
            url = `https://www.posindonesia.co.id/id/tracking/${trackingNumber}`;
            break;
        case 'tiki':
            url = `https://www.tiki.id/id/tracking/${trackingNumber}`;
            break;
        default:
                        url = 'https://www.google.com/search?q=' + encodeURIComponent(`lacak paket ${trackingNumber}`);
    }
    
    window.open(url, '_blank');
}

// Cancel order function
function cancelOrder() {
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

// Confirm cancel function
function confirmCancel() {
    const reason = document.getElementById('cancelReason').value;
    const note = document.getElementById('cancelNote').value;
    
    if (!reason) {
        alert('Pilih alasan pembatalan terlebih dahulu');
        return;
    }
    
    // Show loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Membatalkan...';
    btn.disabled = true;
    
    // Send cancel request
    fetch('ajax/cancel_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_number=<?php echo $order['order_number']; ?>&reason=${encodeURIComponent(reason)}&note=${encodeURIComponent(note)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Pesanan berhasil dibatalkan');
            location.reload();
        } else {
            alert('Gagal membatalkan pesanan: ' + data.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan sistem');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Auto refresh status every 60 seconds
setInterval(function() {
    fetch(`ajax/check_order_status.php?order=<?php echo $order['order_number']; ?>`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.status !== '<?php echo $order['status']; ?>') {
            // Status changed, show notification and reload
            showNotification('Status pesanan telah diperbarui!', 'success');
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(error => console.log('Status check failed'));
}, 60000);

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Smooth scroll to timeline on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a hash in URL
    if (window.location.hash === '#timeline') {
        document.querySelector('.timeline').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }
    
    // Add click handlers for timeline items
    document.querySelectorAll('.timeline-item').forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all items
            document.querySelectorAll('.timeline-item').forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
        });
    });
});

// Print order function
function printOrder() {
    const printContent = `
        <html>
        <head>
            <title>Pesanan #<?php echo $order['order_number']; ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .order-info { margin-bottom: 20px; }
                .table { width: 100%; border-collapse: collapse; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f2f2f2; }
                .total { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Sahabat Tani</h2>
                <h3>Detail Pesanan #<?php echo $order['order_number']; ?></h3>
            </div>
            <div class="order-info">
                <p><strong>Tanggal:</strong> <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?></p>
                <p><strong>Status:</strong> <?php echo $status_text[$order['status']] ?? $order['status']; ?></p>
                <p><strong>Alamat:</strong> <?php echo htmlspecialchars($order['alamat']); ?></p>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                        <td><?php echo format_rupiah($item['harga']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo format_rupiah($item['subtotal']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total">
                        <td colspan="3">Total</td>
                        <td><?php echo format_rupiah($order['total']); ?></td>
                    </tr>
                </tbody>
            </table>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// Add print button to page
document.addEventListener('DOMContentLoaded', function() {
    const actionsDiv = document.querySelector('.d-grid.gap-2');
    if (actionsDiv) {
        const printBtn = document.createElement('button');
        printBtn.className = 'btn btn-outline-secondary btn-sm';
        printBtn.innerHTML = '<i class="fas fa-print me-1"></i>Cetak Pesanan';
        printBtn.onclick = printOrder;
        actionsDiv.appendChild(printBtn);
    }
});
</script>

<?php include 'includes/footer.php'; ?>