<?php
require_once 'includes/config.php';

$query = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$kategori = isset($_GET['kategori']) ? clean_input($_GET['kategori']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'terbaru';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build search query
$where_conditions = ["status = 'active'"];
$params = [];

if (!empty($query)) {
    $where_conditions[] = "(nama LIKE ? OR deskripsi LIKE ? OR brand LIKE ?)";
    $search_term = "%$query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($kategori)) {
    $where_conditions[] = "kategori_id = ?";
    $params[] = $kategori;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'terbaru' => 'created_at DESC',
    'nama_asc' => 'nama ASC',
    'nama_desc' => 'nama DESC',
    'harga_asc' => 'harga ASC',
        'harga_desc' => 'harga DESC',
    'populer' => 'views DESC'
];

$order_by = $sort_options[$sort] ?? 'created_at DESC';

// Get total count
$count_sql = "SELECT COUNT(*) FROM produk WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();

// Get products
$sql = "SELECT p.*, k.nama as kategori_nama 
        FROM produk p 
        LEFT JOIN kategori k ON p.kategori_id = k.id 
        WHERE $where_clause 
        ORDER BY $order_by 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$kategori_stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama");
$categories = $kategori_stmt->fetchAll();

// Pagination
$total_pages = ceil($total_products / $limit);

$page_title = 'Pencarian: ' . ($query ?: 'Semua Produk');
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Pencarian</li>
        </ol>
    </nav>
    
    <!-- Search Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <h4 class="mb-2">
                                <?php if (!empty($query)): ?>
                                    Hasil pencarian untuk: <span class="text-success">"<?php echo htmlspecialchars($query); ?>"</span>
                                <?php else: ?>
                                    <span class="text-success">Semua Produk</span>
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0">
                                Ditemukan <?php echo $total_products; ?> produk
                                <?php if (!empty($kategori)): ?>
                                    <?php
                                    $cat_stmt = $pdo->prepare("SELECT nama FROM kategori WHERE id = ?");
                                    $cat_stmt->execute([$kategori]);
                                    $cat_name = $cat_stmt->fetchColumn();
                                    ?>
                                    dalam kategori <strong><?php echo $cat_name; ?></strong>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-lg-6">
                            <!-- Advanced Search Form -->
                            <form method="GET" class="d-flex gap-2">
                                <input type="text" class="form-control" name="q" 
                                       value="<?php echo htmlspecialchars($query); ?>" 
                                       placeholder="Cari produk...">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Sidebar Filter -->
        <div class="col-lg-3 mb-4">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Produk</h6>
                </div>
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                        
                        <!-- Category Filter -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Kategori</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="kategori" value="" id="cat_all"
                                       <?php echo empty($kategori) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cat_all">
                                    Semua Kategori
                                </label>
                            </div>
                            <?php foreach ($categories as $cat): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="kategori" 
                                           value="<?php echo $cat['id']; ?>" id="cat_<?php echo $cat['id']; ?>"
                                           <?php echo $kategori == $cat['id'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat_<?php echo $cat['id']; ?>">
                                        <?php echo $cat['nama']; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Price Range Filter -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Rentang Harga</h6>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" 
                                           name="min_price" placeholder="Min" 
                                           value="<?php echo $_GET['min_price'] ?? ''; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" 
                                           name="max_price" placeholder="Max"
                                           value="<?php echo $_GET['max_price'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Brand</h6>
                            <?php
                            $brand_sql = "SELECT DISTINCT brand FROM produk WHERE status = 'active' AND brand IS NOT NULL ORDER BY brand";
                            $brand_stmt = $pdo->query($brand_sql);
                            $brands = $brand_stmt->fetchAll();
                            ?>
                            <?php foreach ($brands as $brand): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="brand[]" 
                                           value="<?php echo $brand['brand']; ?>" id="brand_<?php echo md5($brand['brand']); ?>"
                                           <?php echo in_array($brand['brand'], $_GET['brand'] ?? []) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="brand_<?php echo md5($brand['brand']); ?>">
                                        <?php echo $brand['brand']; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-search me-2"></i>Terapkan Filter
                            </button>
                            <a href="search.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Reset Filter
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Popular Products -->
            <div class="card border-success mt-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-fire me-2"></i>Produk Populer</h6>
                </div>
                <div class="card-body">
                    <?php
                    $popular_stmt = $pdo->query("SELECT * FROM produk WHERE status = 'active' ORDER BY views DESC LIMIT 5");
                    $popular_products = $popular_stmt->fetchAll();
                    ?>
                    <?php foreach ($popular_products as $pop_product): ?>
                        <div class="d-flex mb-3">
                            <img src="<?php echo $pop_product['gambar'] ?: 'assets/images/no-image.jpg'; ?>" 
                                 alt="<?php echo $pop_product['nama']; ?>" 
                                 class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="produk_detail.php?id=<?php echo $pop_product['id']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <?php echo substr($pop_product['nama'], 0, 30) . (strlen($pop_product['nama']) > 30 ? '...' : ''); ?>
                                    </a>
                                </h6>
                                <small class="text-success fw-bold"><?php echo format_rupiah($pop_product['harga']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Sort Options -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="text-muted">
                        Menampilkan <?php echo min($offset + 1, $total_products); ?>-<?php echo min($offset + $limit, $total_products); ?> 
                        dari <?php echo $total_products; ?> produk
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0">Urutkan:</label>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="changeSorting(this.value)">
                        <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="nama_asc" <?php echo $sort == 'nama_asc' ? 'selected' : ''; ?>>Nama A-Z</option>
                        <option value="nama_desc" <?php echo $sort == 'nama_desc' ? 'selected' : ''; ?>>Nama Z-A</option>
                        <option value="harga_asc" <?php echo $sort == 'harga_asc' ? 'selected' : ''; ?>>Harga Terendah</option>
                        <option value="harga_desc" <?php echo $sort == 'harga_desc' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                        <option value="populer" <?php echo $sort == 'populer' ? 'selected' : ''; ?>>Terpopuler</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
                <!-- No Results -->
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Tidak ada produk ditemukan</h4>
                    <p class="text-muted">Coba ubah kata kunci pencarian atau filter yang digunakan</p>
                    <a href="produk.php" class="btn btn-success">
                        <i class="fas fa-arrow-left me-2"></i>Lihat Semua Produk
                    </a>
                </div>
            <?php else: ?>
                <!-- Products Grid -->
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm product-card">
                                <div class="position-relative">
                                    <img src="<?php echo $product['gambar'] ?: 'assets/images/no-image.jpg'; ?>" 
                                         class="card-img-top" alt="<?php echo $product['nama']; ?>" 
                                         style="height: 200px; object-fit: cover;">
                                    
                                    <?php if ($product['stok'] <= 5): ?>
                                        <span class="badge bg-warning position-absolute top-0 start-0 m-2">
                                            Stok Terbatas
                                        </span>
                                    <?php endif; ?>
                                    
                                    <div class="product-overlay">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-light" onclick="quickView(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-light" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <small class="text-muted"><?php echo $product['kategori_nama']; ?></small>
                                        <?php if ($product['brand']): ?>
                                            <small class="text-muted"> • <?php echo $product['brand']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h6 class="card-title">
                                        <a href="produk_detail.php?id=<?php echo $product['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo $product['nama']; ?>
                                        </a>
                                    </h6>
                                    
                                    <p class="card-text text-muted small flex-grow-1">
                                        <?php echo substr(strip_tags($product['deskripsi']), 0, 80) . '...'; ?>
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="h5 text-success mb-0"><?php echo format_rupiah($product['harga']); ?></span>
                                            <small class="text-muted">Stok: <?php echo $product['stok']; ?></small>
                                        </div>
                                        
                                        <div class="d-grid">
                                                                                        <?php if ($product['stok'] > 0): ?>
                                                <button class="btn btn-success btn-sm" onclick="addToCart(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-times me-2"></i>Stok Habis
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Product pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo build_pagination_url($page - 1); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo build_pagination_url(1); ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo build_pagination_url($i); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo build_pagination_url($total_pages); ?>"><?php echo $total_pages; ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo build_pagination_url($page + 1); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="quickViewContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.page-link {
    color: #198754;
}

.page-item.active .page-link {
    background-color: #198754;
    border-color: #198754;
}

.page-link:hover {
    color: #198754;
    background-color: #f8f9fa;
    border-color: #198754;
}
</style>

<script>
function changeSorting(sort) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location.href = url.toString();
}

function quickView(productId) {
    $('#quickViewModal').modal('show');
    
    fetch(`ajax/quick_view.php?id=${productId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('quickViewContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('quickViewContent').innerHTML = 
                '<div class="alert alert-danger">Gagal memuat data produk</div>';
        });
}

function addToWishlist(productId) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Silakan login terlebih dahulu');
        return;
    <?php endif; ?>
    
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
            showToast('Produk ditambahkan ke wishlist', 'success');
        } else {
            showToast(data.message || 'Gagal menambahkan ke wishlist', 'error');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan sistem', 'error');
    });
}

// Auto-submit filter form when radio buttons change
document.querySelectorAll('input[name="kategori"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php
function build_pagination_url($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'search.php?' . http_build_query($params);
}

include 'includes/footer.php';
?>
