<?php
require_once 'includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: produk.php');
    exit();
}

// Ambil detail produk
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p 
                       LEFT JOIN kategori k ON p.kategori_id = k.id 
                       WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    $_SESSION['error'] = 'Produk tidak ditemukan!';
    header('Location: produk.php');
    exit();
}

// Ambil produk terkait (kategori sama)
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p 
                       LEFT JOIN kategori k ON p.kategori_id = k.id 
                       WHERE p.kategori_id = ? AND p.id != ? AND p.status = 'active' 
                       ORDER BY RAND() LIMIT 4");
$stmt->execute([$produk['kategori_id'], $id]);
$produk_terkait = $stmt->fetchAll();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
        $_SESSION['error'] = 'Silakan login sebagai customer terlebih dahulu!';
    } else {
        $quantity = (int)$_POST['quantity'];
        $user_id = $_SESSION['user_id'];
        
        if ($quantity <= 0 || $quantity > $produk['stok']) {
            $_SESSION['error'] = 'Jumlah tidak valid!';
        } else {
            try {
                // Cek apakah sudah ada di keranjang
                $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE user_id = ? AND produk_id = ?");
                $stmt->execute([$user_id, $id]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $new_quantity = $existing['jumlah'] + $quantity;
                    if ($new_quantity > $produk['stok']) {
                        $_SESSION['error'] = 'Total quantity melebihi stok tersedia!';
                    } else {
                        $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
                        $stmt->execute([$new_quantity, $existing['id']]);
                        $_SESSION['success'] = 'Produk berhasil ditambahkan ke keranjang!';
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO keranjang (user_id, produk_id, jumlah) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $id, $quantity]);
                    $_SESSION['success'] = 'Produk berhasil ditambahkan ke keranjang!';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Terjadi kesalahan sistem!';
            }
        }
    }
    
    header('Location: detail_produk.php?id=' . $id);
    exit();
}

$page_title = $produk['nama_produk'];
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="produk.php">Produk</a></li>
            <li class="breadcrumb-item active"><?php echo $produk['nama_produk']; ?></li>
        </ol>
    </nav>
    
    <!-- Detail Produk -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <!-- Gambar Produk -->
            <div class="card">
                <div class="position-relative">
                    <img src="assets/img/pupuk/<?php echo $produk['gambar'] ?: 'default.jpg'; ?>" 
                         class="card-img-top" alt="<?php echo $produk['nama_produk']; ?>" 
                         style="height: 400px; object-fit: cover;">
                    
                    <!-- Badges -->
                    <?php if ($produk['is_featured']): ?>
                        <span class="badge bg-success position-absolute top-0 start-0 m-3">
                            <i class="fas fa-star me-1"></i>Produk Unggulan
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($produk['stok'] <= 5): ?>
                        <span class="badge bg-warning position-absolute top-0 end-0 m-3">
                            <i class="fas fa-exclamation-triangle me-1"></i>Stok Terbatas
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title h3 mb-3"><?php echo $produk['nama_produk']; ?></h1>
                    
                    <!-- Info Dasar -->
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Kategori</small>
                            <span class="badge bg-secondary"><?php echo $produk['nama_kategori'] ?: 'Umum'; ?></span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Jenis Pupuk</small>
                            <span class="badge bg-info"><?php echo ucfirst($produk['jenis_pupuk']); ?></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Berat</small>
                            <strong><?php echo $produk['berat']; ?></strong>
                        </div>
                        <?php if ($produk['brand']): ?>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Brand</small>
                            <strong><?php echo $produk['brand']; ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Harga -->
                    <div class="mb-4">
                        <h2 class="text-success fw-bold mb-2"><?php echo format_rupiah($produk['harga']); ?></h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-box me-1"></i>Stok tersedia: 
                            <strong class="<?php echo $produk['stok'] <= 5 ? 'text-warning' : 'text-success'; ?>">
                                <?php echo $produk['stok']; ?> unit
                            </strong>
                        </p>
                    </div>
                    
                    <!-- Form Add to Cart -->
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                        <?php if ($produk['stok'] > 0): ?>
                            <form method="POST" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-sm-4">
                                        <label class="form-label">Jumlah</label>
                                        <div class="input-group">
                                            <button type="button" class="btn btn-outline-secondary" onclick="decreaseQty()">-</button>
                                            <input type="number" class="form-control text-center" id="quantity" name="quantity" 
                                                   value="1" min="1" max="<?php echo $produk['stok']; ?>">
                                            <button type="button" class="btn btn-outline-secondary" onclick="increaseQty()">+</button>
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <button type="submit" name="add_to_cart" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Maaf, produk ini sedang habis stok
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="d-grid mb-4">
                            <a href="login.php" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login untuk Membeli
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quick Actions -->
                    <div class="d-flex gap-2 mb-4">
                        <button class="btn btn-outline-success" onclick="contactWhatsApp('Halo, saya tertarik dengan produk <?php echo $produk['nama_produk']; ?>')">
                            <i class="fab fa-whatsapp me-1"></i>Tanya via WhatsApp
                        </button>
                                                <button class="btn btn-outline-secondary" onclick="shareProduct()">
                            <i class="fas fa-share-alt me-1"></i>Bagikan
                        </button>
                    </div>
                    
                    <!-- Informasi Pengiriman -->
                    <div class="border rounded p-3 bg-light">
                        <h6 class="fw-bold mb-2"><i class="fas fa-truck me-2"></i>Informasi Pengiriman</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li><i class="fas fa-check text-success me-2"></i>Gratis ongkir untuk pembelian di atas Rp 500.000</li>
                            <li><i class="fas fa-check text-success me-2"></i>Estimasi pengiriman 2-5 hari kerja</li>
                            <li><i class="fas fa-check text-success me-2"></i>Garansi produk original</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs Detail -->
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" 
                            data-bs-target="#description" type="button" role="tab">
                        <i class="fas fa-info-circle me-2"></i>Deskripsi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specification-tab" data-bs-toggle="tab" 
                            data-bs-target="#specification" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>Spesifikasi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="usage-tab" data-bs-toggle="tab" 
                            data-bs-target="#usage" type="button" role="tab">
                        <i class="fas fa-seedling me-2"></i>Cara Penggunaan
                    </button>
                </li>
            </ul>
            
            <div class="tab-content border border-top-0 p-4" id="productTabsContent">
                <!-- Deskripsi -->
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <h5 class="mb-3">Deskripsi Produk</h5>
                    <div class="text-muted">
                        <?php echo nl2br($produk['deskripsi']); ?>
                    </div>
                </div>
                
                <!-- Spesifikasi -->
                <div class="tab-pane fade" id="specification" role="tabpanel">
                    <h5 class="mb-3">Spesifikasi</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Nama Produk</td>
                                    <td><?php echo $produk['nama_produk']; ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Kategori</td>
                                    <td><?php echo $produk['nama_kategori'] ?: 'Umum'; ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Jenis Pupuk</td>
                                    <td><?php echo ucfirst($produk['jenis_pupuk']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Berat</td>
                                    <td><?php echo $produk['berat']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <?php if ($produk['brand']): ?>
                                <tr>
                                    <td class="fw-bold">Brand</td>
                                    <td><?php echo $produk['brand']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="fw-bold">Stok</td>
                                    <td><?php echo $produk['stok']; ?> unit</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Harga</td>
                                    <td class="text-success fw-bold"><?php echo format_rupiah($produk['harga']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Cara Penggunaan -->
                <div class="tab-pane fade" id="usage" role="tabpanel">
                    <h5 class="mb-3">Cara Penggunaan</h5>
                    <div class="text-muted">
                        <?php if ($produk['cara_pakai']): ?>
                            <?php echo nl2br($produk['cara_pakai']); ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Informasi cara penggunaan belum tersedia. Silakan hubungi kami untuk konsultasi.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Produk Terkait -->
    <?php if (!empty($produk_terkait)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="fw-bold mb-4">Produk Terkait</h3>
            <div class="row">
                <?php foreach ($produk_terkait as $related): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="assets/img/pupuk/<?php echo $related['gambar'] ?: 'default.jpg'; ?>" 
                                     class="card-img-top" alt="<?php echo $related['nama_produk']; ?>">
                                <?php if ($related['is_featured']): ?>
                                    <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                        <i class="fas fa-star me-1"></i>Unggulan
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title"><?php echo $related['nama_produk']; ?></h6>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-tag me-1"></i><?php echo $related['nama_kategori'] ?: 'Umum'; ?> | 
                                    <i class="fas fa-weight me-1"></i><?php echo $related['berat']; ?>
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="product-price"><?php echo format_rupiah($related['harga']); ?></span>
                                        <small class="text-muted">Stok: <?php echo $related['stok']; ?></small>
                                    </div>
                                    <div class="d-grid">
                                        <a href="detail_produk.php?id=<?php echo $related['id']; ?>" 
                                           class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-eye me-1"></i>Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function increaseQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.getAttribute('max'));
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('quantity');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}

function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo $produk['nama_produk']; ?>',
            text: 'Lihat produk pupuk berkualitas ini!',
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link produk berhasil disalin!');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
