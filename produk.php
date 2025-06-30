<?php
require_once 'includes/config.php';

// Parameter pencarian dan filter
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$jenis = isset($_GET['jenis']) ? clean_input($_GET['jenis']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'terbaru';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nama_produk LIKE ? OR p.deskripsi LIKE ? OR p.brand LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($kategori > 0) {
    $where_conditions[] = "p.kategori_id = ?";
    $params[] = $kategori;
}

if (!empty($jenis)) {
    $where_conditions[] = "p.jenis_pupuk = ?";
    $params[] = $jenis;
}

$where_clause = implode(' AND ', $where_conditions);

// Sorting
$order_by = "p.created_at DESC";
switch ($sort) {
    case 'nama_asc':
        $order_by = "p.nama_produk ASC";
        break;
    case 'nama_desc':
        $order_by = "p.nama_produk DESC";
        break;
    case 'harga_asc':
        $order_by = "p.harga ASC";
        break;
    case 'harga_desc':
        $order_by = "p.harga DESC";
        break;
    case 'terlaris':
        $order_by = "p.is_featured DESC, p.created_at DESC";
        break;
}

// Query produk
$sql = "SELECT p.*, k.nama_kategori 
        FROM produk p 
        LEFT JOIN kategori k ON p.kategori_id = k.id 
        WHERE $where_clause 
        ORDER BY $order_by 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk_list = $stmt->fetchAll();

// Hitung total untuk pagination
$count_sql = "SELECT COUNT(*) as total FROM produk p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_produk = $count_stmt->fetch()['total'];
$total_pages = ceil($total_produk / $limit);

// Ambil kategori untuk filter
$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

$page_title = 'Produk';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Produk</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold">Katalog Produk</h2>
            <p class="text-muted">Temukan pupuk terbaik untuk kebutuhan Anda</p>
        </div>
        <div class="col-md-6 text-md-end">
            <p class="text-muted">Menampilkan <?php echo count($produk_list); ?> dari <?php echo $total_produk; ?> produk</p>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Pencarian</label>
                    <input type="text" class="form-control" name="search"
                        value="<?php echo $search; ?>" placeholder="Cari produk...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" name="kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?php echo $kat['id']; ?>"
                                <?php echo $kategori == $kat['id'] ? 'selected' : ''; ?>>
                                <?php echo $kat['nama_kategori']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis Pupuk</label>
                    <select class="form-select" name="jenis">
                        <option value="">Semua Jenis</option>
                        <option value="organik" <?php echo $jenis == 'organik' ? 'selected' : ''; ?>>Organik</option>
                        <option value="anorganik" <?php echo $jenis == 'anorganik' ? 'selected' : ''; ?>>Anorganik</option>
                        <option value="hayati" <?php echo $jenis == 'hayati' ? 'selected' : ''; ?>>Hayati</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Urutkan</label>
                    <select class="form-select" name="sort">
                        <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="terlaris" <?php echo $sort == 'terlaris' ? 'selected' : ''; ?>>Terlaris</option>
                        <option value="nama_asc" <?php echo $sort == 'nama_asc' ? 'selected' : ''; ?>>Nama A-Z</option>
                        <option value="nama_desc" <?php echo $sort == 'nama_desc' ? 'selected' : ''; ?>>Nama Z-A</option>
                        <option value="harga_asc" <?php echo $sort == 'harga_asc' ? 'selected' : ''; ?>>Harga Terendah</option>
                        <option value="harga_desc" <?php echo $sort == 'harga_desc' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="produk.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Produk Grid -->
    <?php if (empty($produk_list)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4>Produk Tidak Ditemukan</h4>
            <p class="text-muted">Coba ubah kata kunci pencarian atau filter Anda</p>
            <a href="produk.php" class="btn btn-success">Lihat Semua Produk</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($produk_list as $produk): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card product-card h-100">
                        <div class="position-relative">
                            <img src="assets/img/pupuk/<?php echo $produk['gambar'] ?: 'default.jpg'; ?>"
                                class="card-img-top" alt="<?php echo $produk['nama_produk']; ?>">

                            <!-- Badges -->
                            <?php if ($produk['is_featured']): ?>
                                <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                    <i class="fas fa-star me-1"></i>Unggulan
                                </span>
                            <?php endif; ?>

                            <?php if ($produk['stok'] <= 5): ?>
                                <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                    Stok Terbatas
                                </span>
                            <?php endif; ?>

                            <!-- Quick Actions -->
                            <div class="position-absolute bottom-0 end-0 m-2">
                                <div class="btn-group-vertical">
                                    <a href="detail_produk.php?id=<?php echo $produk['id']; ?>"
                                        class="btn btn-sm btn-light" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                                        <button class="btn btn-sm btn-success"
                                            onclick="addToCart(<?php echo $produk['id']; ?>)"
                                            title="Tambah ke Keranjang">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title"><?php echo $produk['nama_produk']; ?></h6>

                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-tag me-1"></i><?php echo $produk['nama_kategori'] ?: 'Umum'; ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-leaf me-1"></i><?php echo ucfirst($produk['jenis_pupuk']); ?> |
                                    <i class="fas fa-weight me-1"></i><?php echo $produk['berat']; ?>
                                </small>
                                <?php if ($produk['brand']): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-industry me-1"></i><?php echo $produk['brand']; ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <p class="card-text small text-muted flex-grow-1">
                                <?php echo substr($produk['deskripsi'], 0, 100) . '...'; ?>
                            </p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="product-price"><?php echo format_rupiah($produk['harga']); ?></span>
                                    <small class="text-muted">
                                        Stok: <span class="fw-bold"><?php echo $produk['stok']; ?></span>
                                    </small>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="detail_produk.php?id=<?php echo $produk['id']; ?>"
                                        class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-eye me-1"></i>Lihat Detail
                                    </a>

                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                                        <?php if ($produk['stok'] > 0): ?>
                                            <button class="btn btn-success btn-sm"
                                                onclick="addToCart(<?php echo $produk['id']; ?>)">
                                                <i class="fas fa-cart-plus me-1"></i>Tambah ke Keranjang
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-times me-1"></i>Stok Habis
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-sign-in-alt me-1"></i>Login untuk Beli
                                        </a>
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
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
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
</div>

<script>
    function addToCart(productId) {
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
                    // Update cart count in navbar
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menambahkan ke keranjang');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem');
            });
    }
</script>

<?php include 'includes/footer.php'; ?>