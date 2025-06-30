<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.nama_produk as nama, p.harga, p.gambar, p.stok, p.berat
    FROM cart c
    JOIN produk p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$page_title = 'Keranjang Belanja';
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="fw-bold text-success mb-4">
                <i class="fas fa-shopping-cart me-2"></i>Keranjang Belanja
            </h2>
            
            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
                    <h4>Keranjang Anda Kosong</h4>
                    <p class="text-muted">Belum ada produk di keranjang belanja Anda</p>
                    <a href="produk.php" class="btn btn-success">
                        <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="<?php echo $item['gambar'] ?: 'assets/images/no-image.jpg'; ?>" 
                                                 alt="<?php echo $item['nama']; ?>" 
                                                 class="img-fluid rounded">
                                        </div>
                                        <div class="col-md-4">
                                            <h6><?php echo $item['nama']; ?></h6>
                                            <p class="text-muted mb-0"><?php echo format_rupiah($item['harga']); ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <button class="btn btn-outline-secondary btn-sm" type="button">-</button>
                                                <input type="text" class="form-control text-center" value="<?php echo $item['quantity']; ?>">
                                                <button class="btn btn-outline-secondary btn-sm" type="button">+</button>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <strong><?php echo format_rupiah($item['harga'] * $item['quantity']); ?></strong>
                                        </div>
                                        <div class="col-md-1">
                                            <button class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Ringkasan Belanja</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $total = 0;
                                foreach ($cart_items as $item) {
                                    $total += $item['harga'] * $item['quantity'];
                                }
                                ?>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Total:</span>
                                    <strong><?php echo format_rupiah($total); ?></strong>
                                </div>
                                <div class="d-grid">
                                    <a href="checkout.php" class="btn btn-success">
                                        <i class="fas fa-credit-card me-2"></i>Checkout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>