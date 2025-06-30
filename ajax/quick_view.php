<?php
require_once '../includes/config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    echo '<div class="alert alert-danger">Produk tidak ditemukan</div>';
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, k.nama as kategori_nama 
        FROM produk p 
        LEFT JOIN kategori k ON p.kategori_id = k.id 
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo '<div class="alert alert-danger">Produk tidak ditemukan</div>';
        exit();
    }
    
    // Update view count
    $update_stmt = $pdo->prepare("UPDATE produk SET views = views + 1 WHERE id = ?");
    $update_stmt->execute([$product_id]);
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Terjadi kesalahan sistem</div>';
    exit();
}
?>

<div class="row">
    <div class="col-md-6">
        <img src="<?php echo $product['gambar'] ?: '../assets/images/no-image.jpg'; ?>" 
             alt="<?php echo $product['nama']; ?>" 
             class="img-fluid rounded">
    </div>
    <div class="col-md-6">
        <div class="mb-2">
            <small class="text-muted"><?php echo $product['kategori_nama']; ?></small>
            <?php if ($product['brand']): ?>
                <small class="text-muted"> • <?php echo $product['brand']; ?></small>
            <?php endif; ?>
        </div>
        
        <h4 class="fw-bold mb-3"><?php echo $product['nama']; ?></h4>
        
        <div class="mb-3">
            <span class="h4 text-success"><?php echo format_rupiah($product['harga']); ?></span>
            <span class="text-muted ms-2">per <?php echo $product['satuan']; ?></span>
        </div>
        
        <div class="mb-3">
            <strong>Stok:</strong> 
            <span class="<?php echo $product['stok'] <= 5 ? 'text-warning' : 'text-success'; ?>">
                <?php echo $product['stok']; ?> <?php echo $product['satuan']; ?>
            </span>
        </div>
        
        <div class="mb-3">
            <strong>Deskripsi:</strong>
            <p class="text-muted mt-2"><?php echo nl2br(substr(strip_tags($product['deskripsi']), 0, 200)) . '...'; ?></p>
        </div>
        
        <?php if ($product['stok'] > 0): ?>
            <div class="mb-3">
                <div class="row">
                    <div class="col-4">
                        <label class="form-label">Jumlah:</label>
                        <input type="number" class="form-control" id="quickViewQuantity" 
                               value="1" min="1" max="<?php echo $product['stok']; ?>">
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button class="btn btn-success flex-fill" onclick="addToCartFromQuickView(<?php echo $product['id']; ?>)">
                    <i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang
                </button>
                <button class="btn btn-outline-success" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>Produk sedang habis stok
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="produk_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                <i class="fas fa-eye me-2"></i>Lihat Detail Lengkap
            </a>
        </div>
    </div>
</div>

<script>
function addToCartFromQuickView(productId) {
    const quantity = document.getElementById('quickViewQuantity').value;
    
    fetch('../ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Produk berhasil ditambahkan ke keranjang', 'success');
            updateCartCount();
            $('#quickViewModal').modal('hide');
        } else {
            showToast(data.message || 'Gagal menambahkan ke keranjang', 'error');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan sistem', 'error');
    });
}
</script>