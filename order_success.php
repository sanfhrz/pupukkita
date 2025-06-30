<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    header('Location: index.php');
    exit();
}

// Get order details
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Clear cart after successful order
$clear_cart = $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
$clear_cart->execute([$_SESSION['user_id']]);

$page_title = 'Pesanan Berhasil';
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Card -->
            <div class="card border-success shadow-lg">
                <div class="card-body text-center py-5">
                    <!-- Success Icon -->
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    
                    <!-- Success Message -->
                    <h2 class="text-success fw-bold mb-3">Pesanan Berhasil Dibuat!</h2>
                    <p class="text-muted mb-4">
                        Terima kasih telah berbelanja di Sahabat Tani. Pesanan Anda telah berhasil dibuat dan akan segera diproses.
                    </p>
                    
                    <!-- Order Info -->
                    <div class="bg-light rounded p-4 mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Nomor Pesanan:</strong><br>
                                <span class="text-success fs-5">#<?php echo $order['order_number']; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Total Pembayaran:</strong><br>
                                <span class="text-success fs-5"><?php echo format_rupiah($order['total']); ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Metode Pembayaran:</strong><br>
                                <?php echo $order['payment_method'] === 'bank_transfer' ? 'Transfer Bank' : 'COD'; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Estimasi Pengiriman:</strong><br>
                                <?php echo $order['shipping_etd']; ?> hari kerja
                            </div>
                        </div>
                    </div>
                    
                    <!-- Next Steps -->
                    <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                    <div class="alert alert-warning">
                        <h5 class="fw-bold mb-3">Langkah Selanjutnya:</h5>
                        <ol class="text-start">
                            <li>Lakukan pembayaran ke salah satu rekening berikut:
                                <ul class="mt-2">
                                    <li><strong>BCA:</strong> 1234567890</li>
                                    <li><strong>Mandiri:</strong> 0987654321</li>
                                    <li><strong>BRI:</strong> 5678901234</li>
                                    <li><strong>BNI:</strong> 4321098765</li>
                                </ul>
                                <small class="text-muted">A.n: Sahabat Tani</small>
                            </li>
                            <li class="mt-2">Upload bukti pembayaran melalui halaman pesanan</li>
                            <li class="mt-2">Tunggu konfirmasi dari admin (1x24 jam)</li>
                            <li class="mt-2">Pesanan akan segera dikirim setelah pembayaran dikonfirmasi</li>
                                                    </ol>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <h5 class="fw-bold mb-3">Informasi COD:</h5>
                        <ul class="text-start">
                            <li>Pesanan akan segera diproses</li>
                            <li>Kami akan menghubungi Anda untuk konfirmasi pengiriman</li>
                            <li>Siapkan uang pas saat barang tiba</li>
                            <li>Biaya COD: Rp 5.000</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="order_detail.php?order=<?php echo $order['order_number']; ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-eye me-2"></i>Lihat Detail Pesanan
                        </a>
                        <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                        <a href="upload_payment.php?order=<?php echo $order['order_number']; ?>" class="btn btn-warning btn-lg">
                            <i class="fas fa-upload me-2"></i>Upload Bukti Bayar
                        </a>
                        <?php endif; ?>
                        <a href="my_orders.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-list me-2"></i>Semua Pesanan
                        </a>
                    </div>
                    
                    <!-- Continue Shopping -->
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-outline-success">
                            <i class="fas fa-shopping-cart me-2"></i>Lanjut Belanja
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Additional Info -->
            <div class="row mt-4">
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-shipping-fast text-primary fa-2x mb-3"></i>
                            <h6 class="fw-bold">Pengiriman Cepat</h6>
                            <small class="text-muted">Pesanan dikirim dalam 1-2 hari kerja</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt text-success fa-2x mb-3"></i>
                            <h6 class="fw-bold">Garansi Kualitas</h6>
                            <small class="text-muted">Produk berkualitas dengan garansi</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-headset text-info fa-2x mb-3"></i>
                            <h6 class="fw-bold">Customer Support</h6>
                            <small class="text-muted">Siap membantu 24/7</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Support -->
            <div class="card border-info mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Butuh Bantuan?</h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">Jika ada pertanyaan tentang pesanan Anda, jangan ragu untuk menghubungi kami:</p>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <a href="https://wa.me/6281234567890" class="btn btn-success w-100" target="_blank">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp: 0812-3456-7890
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="mailto:support@sahabattani.com" class="btn btn-outline-primary w-100">
                                <i class="fas fa-envelope me-2"></i>support@sahabattani.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 15px;
}

.btn {
    border-radius: 8px;
}

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

.card {
    animation: fadeInUp 0.6s ease-out;
}

.fa-check-circle {
    animation: fadeInUp 0.8s ease-out;
}
</style>

<script>
// Auto-save order info to localStorage for easy access
document.addEventListener('DOMContentLoaded', function() {
    const orderInfo = {
        order_number: '<?php echo $order['order_number']; ?>',
        total: '<?php echo $order['total']; ?>',
        payment_method: '<?php echo $order['payment_method']; ?>',
        created_at: '<?php echo $order['created_at']; ?>'
    };
    
    localStorage.setItem('lastOrder', JSON.stringify(orderInfo));
    
    // Clear checkout form data
    localStorage.removeItem('checkoutFormData');
});

// Show notification reminder for bank transfer
<?php if ($order['payment_method'] === 'bank_transfer'): ?>
setTimeout(function() {
    if (confirm('Jangan lupa untuk melakukan pembayaran dan upload bukti transfer ya! Klik OK untuk langsung ke halaman upload.')) {
        window.location.href = 'upload_payment.php?order=<?php echo $order['order_number']; ?>';
    }
}, 5000);
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
