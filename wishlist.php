<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT w.*, p.nama, p.harga, p.gambar, p.stok, p.satuan, k.nama as kategori_nama
    FROM wishlist w
    JOIN produk p ON w.product_id = p.id
    LEFT JOIN kategori k ON p.kategori_id = k.id
    WHERE w.user_id = ? AND p.status = 'active'
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

$page_title = 'Wishlist Saya';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Wishlist</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold text-success mb-1">
                        <i class="fas fa-heart me-2"></i>Wishlist Saya
                    </h2>
                    <p class="text-muted mb-0"><?php echo count($wishlist_items); ?> produk dalam wishlist</p>
                </div>
                <?php if (!empty($wishlist_items)): ?>
                    <button class="btn btn-outline-danger" onclick="clearWishlist()">
                        <i class="fas fa-trash me-2"></i>Kosongkan Wishlist
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (empty($wishlist_items)): ?>
        <!-- Empty Wishlist -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-heart fa-4x text-muted"></i>
            </div>
            <h4 class="text-muted mb-3">Wishlist Anda Kosong</h4>
            <p class="text-muted mb-4">Belum ada produk yang ditambahkan ke wishlist. Mulai jelajahi produk favorit Anda!</p>
            <a href="produk.php" class="btn btn-success btn-lg">
                <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
            </a>
        </div>
    <?php else: ?>
        <!-- Wishlist Items -->
        <div class="row">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-lg-4 col-md-6 mb-4" id="wishlist-item-<?php echo $item['product_id']; ?>">
                    <div class="card h-100 border-0 shadow-sm wishlist-card">
                        <div class="position-relative">
                            <img src="<?php echo $item['gambar'] ?: 'assets/images/no-image.jpg'; ?>" 
                                 class="card-img-top" alt="<?php echo $item['nama']; ?>" 
                                 style="height: 200px; object-fit: cover;">
                            
                            <!-- Remove from wishlist button -->
                            <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" 
                                    onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)"
                                    title="Hapus dari wishlist">
                                <i class="fas fa-times"></i>
                            </button>
                            
                            <?php if ($item['stok'] <= 5 && $item['stok'] > 0): ?>
                                <span class="badge bg-warning position-absolute top-0 start-0 m-2">
                                    Stok Terbatas
                                </span>
                            <?php elseif ($item['stok'] == 0): ?>
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                    Stok Habis
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <small class="text-muted"><?php echo $item['kategori_nama']; ?></small>
                            </div>
                            
                            <h6 class="card-title">
                                <a href="produk_detail.php?id=<?php echo $item['product_id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo $item['nama']; ?>
                                </a>
                            </h6>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="h5 text-success mb-0"><?php echo format_rupiah($item['harga']); ?></span>
                                    <small class="text-muted">
                                        Stok: <?php echo $item['stok']; ?> <?php echo $item['satuan']; ?>
                                    </small>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <?php if ($item['stok'] > 0): ?>
                                        <button class="btn btn-success btn-sm" 
                                                onclick="addToCartFromWishlist(<?php echo $item['product_id']; ?>)">
                                            <i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="fas fa-times me-2"></i>Stok Habis
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="produk_detail.php?id=<?php echo $item['product_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-2"></i>Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent border-0">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Ditambahkan <?php echo time_ago($item['created_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Bulk Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h5 class="text-success mb-3">Aksi Cepat</h5>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <button class="btn btn-success" onclick="addAllToCart()">
                                <i class="fas fa-cart-plus me-2"></i>Tambah Semua ke Keranjang
                            </button>
                            <button class="btn btn-outline-primary" onclick="shareWishlist()">
                                <i class="fas fa-share me-2"></i>Bagikan Wishlist
                            </button>
                            <a href="produk.php" class="btn btn-outline-success">
                                <i class="fas fa-plus me-2"></i>Tambah Produk Lain
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wishlist-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.wishlist-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.wishlist-card .card-img-top {
    transition: transform 0.3s ease;
}

.wishlist-card:hover .card-img-top {
    transform: scale(1.05);
}
</style>

<script>
function removeFromWishlist(productId) {
    if (!confirm('Hapus produk dari wishlist?')) return;
    
    fetch('ajax/add_to_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove item from DOM
            const item = document.getElementById(`wishlist-item-${productId}`);
            item.style.transition = 'opacity 0.3s ease';
            item.style.opacity = '0';
            
            setTimeout(() => {
                item.remove();
                
                // Check if wishlist is empty
                const remainingItems = document.querySelectorAll('[id^="wishlist-item-"]');
                if (remainingItems.length === 0) {
                    location.reload();
                }
            }, 300);
            
            showToast('Produk dihapus dari wishlist', 'success');
        } else {
            showToast(data.message || 'Gagal menghapus dari wishlist', 'error');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan sistem', 'error');
    });
}

function addToCartFromWishlist(productId) {
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Produk berhasil ditambahkan ke keranjang', 'success');
            updateCartCount();
        } else {
            showToast(data.message || 'Gagal menambahkan ke keranjang', 'error');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan sistem', 'error');
    });
}

function addAllToCart() {
    const availableItems = document.querySelectorAll('[id^="wishlist-item-"]');
    let addedCount = 0;
    let totalItems = availableItems.length;
    
    if (totalItems === 0) return;
    
    if (!confirm(`Tambah semua ${totalItems} produk ke keranjang?`)) return;
    
    availableItems.forEach(item => {
        const productId = item.id.replace('wishlist-item-', '');
        const stockElement = item.querySelector('.btn-success');
        
        if (stockElement && !stockElement.disabled) {
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addedCount++;
                }
                
                if (addedCount === totalItems) {
                    showToast(`${addedCount} produk berhasil ditambahkan ke keranjang`, 'success');
                    updateCartCount();
                }
            });
        }
    });
}

function clearWishlist() {
    if (!confirm('Hapus semua produk dari wishlist?')) return;
    
    fetch('ajax/clear_wishlist.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast('Gagal mengosongkan wishlist', 'error');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan sistem', 'error');
    });
}

function shareWishlist() {
    const url = window.location.href;
    const text = 'Lihat wishlist produk pupuk favorit saya di Toko Pupuk Berkah!';
    
    if (navigator.share) {
        navigator.share({
            title: 'Wishlist Saya - Toko Pupuk Berkah',
            text: text,
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(`${text} ${url}`).then(() => {
            showToast('Link wishlist berhasil disalin', 'success');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>