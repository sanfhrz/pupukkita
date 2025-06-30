<?php
require_once 'includes/config.php';

// Ambil produk unggulan
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p 
                       LEFT JOIN kategori k ON p.kategori_id = k.id 
                       WHERE p.is_featured = 1 AND p.status = 'active' 
                       ORDER BY p.created_at DESC LIMIT 8");
$stmt->execute();
$produk_unggulan = $stmt->fetchAll();

// Ambil produk terbaru
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p 
                       LEFT JOIN kategori k ON p.kategori_id = k.id 
                       WHERE p.status = 'active' 
                       ORDER BY p.created_at DESC LIMIT 4");
$stmt->execute();
$produk_terbaru = $stmt->fetchAll();

// Statistik untuk hero section
$stmt = $pdo->query("SELECT COUNT(*) as total_produk FROM produk WHERE status = 'active'");
$total_produk = $stmt->fetch()['total_produk'];

$stmt = $pdo->query("SELECT COUNT(*) as total_customer FROM users WHERE role = 'customer'");
$total_customer = $stmt->fetch()['total_customer'];

$page_title = 'Beranda';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-success text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">
                    Pupuk Berkualitas untuk Pertanian Anda
                </h1>
                <p class="lead mb-4">
                    Dapatkan pupuk terbaik dengan harga terjangkau. Tingkatkan hasil panen dengan produk berkualitas dari kami.
                </p>
                <div class="d-flex gap-3 mb-4">
                    <a href="produk.php" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Belanja Sekarang
                    </a>
                    <button class="btn btn-outline-light btn-lg" onclick="contactWhatsApp('Halo, saya ingin konsultasi tentang pupuk yang cocok untuk tanaman saya')">
                        <i class="fab fa-whatsapp me-2"></i>Konsultasi
                    </button>
                </div>

                <!-- Stats -->
                <div class="row text-center">
                    <div class="col-4">
                        <h3 class="fw-bold"><?php echo $total_produk; ?>+</h3>
                        <small>Produk Tersedia</small>
                    </div>
                    <div class="col-4">
                        <h3 class="fw-bold"><?php echo $total_customer; ?>+</h3>
                        <small>Pelanggan</small>
                    </div>
                    <div class="col-4">
                        <h3 class="fw-bold">5+</h3>
                        <small>Tahun Pengalaman</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="assets/img/pupukgacor.jpg" alt="Pupuk Berkualitas" class="img-fluid rounded shadow"
                    style="max-height: 400px; object-fit: cover;">
            </div>
        </div>
    </div>
</section>

<!-- Produk Unggulan -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3">Produk Unggulan</h2>
                <p class="text-muted">Pilihan terbaik untuk kebutuhan pertanian Anda</p>
            </div>
        </div>

        <div class="row">
            <?php if (empty($produk_unggulan)): ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Belum ada produk unggulan.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($produk_unggulan as $produk): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="assets/img/pupuk/<?php echo $produk['gambar'] ?: 'default.jpg'; ?>"
                                    class="card-img-top" alt="<?php echo $produk['nama_produk']; ?>">
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-star me-1"></i>Unggulan
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title"><?php echo $produk['nama_produk']; ?></h6>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-tag me-1"></i><?php echo $produk['nama_kategori'] ?: 'Umum'; ?> |
                                    <i class="fas fa-weight me-1"></i><?php echo $produk['berat']; ?>
                                </p>
                                <p class="card-text small text-muted flex-grow-1">
                                    <?php echo substr($produk['deskripsi'], 0, 80) . '...'; ?>
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="product-price"><?php echo format_rupiah($produk['harga']); ?></span>
                                        <small class="text-muted">Stok: <?php echo $produk['stok']; ?></small>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="detail_produk.php?id=<?php echo $produk['id']; ?>"
                                            class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-eye me-1"></i>Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="produk.php" class="btn btn-success btn-lg">
                <i class="fas fa-th-large me-2"></i>Lihat Semua Produk
            </a>
        </div>
    </div>
</section>

<!-- Keunggulan -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3">Mengapa Memilih Kami?</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-certificate fa-2x"></i>
                    </div>
                    <h5>Produk Berkualitas</h5>
                    <p class="text-muted">Semua produk telah tersertifikasi dan terjamin kualitasnya</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-shipping-fast fa-2x"></i>
                    </div>
                    <h5>Pengiriman Cepat</h5>
                    <p class="text-muted">Pengiriman ke seluruh Indonesia dengan layanan terpercaya</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-headset fa-2x"></i>
                    </div>
                    <h5>Konsultasi Gratis</h5>
                    <p class="text-muted">Tim ahli siap membantu memilih pupuk yang tepat</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <h5>Harga Terjangkau</h5>
                    <p class="text-muted">Harga kompetitif dengan kualitas terbaik</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Produk Terbaru -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3">Produk Terbaru</h2>
                <p class="text-muted">Produk-produk terbaru yang baru saja kami tambahkan</p>
            </div>
        </div>

        <div class="row">
            <?php foreach ($produk_terbaru as $produk): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card product-card h-100">
                        <div class="position-relative">
                            <img src="assets/img/pupuk/<?php echo $produk['gambar'] ?: 'default.jpg'; ?>"
                                class="card-img-top" alt="<?php echo $produk['nama_produk']; ?>">
                            <span class="badge bg-info position-absolute top-0 end-0 m-2">
                                <i class="fas fa-sparkles me-1"></i>Baru
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title"><?php echo $produk['nama_produk']; ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-tag me-1"></i><?php echo $produk['nama_kategori'] ?: 'Umum'; ?> |
                                <i class="fas fa-weight me-1"></i><?php echo $produk['berat']; ?>
                            </p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="product-price"><?php echo format_rupiah($produk['harga']); ?></span>
                                    <small class="text-muted">Stok: <?php echo $produk['stok']; ?></small>
                                </div>
                                <div class="d-grid">
                                    <a href="detail_produk.php?id=<?php echo $produk['id']; ?>"
                                        class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-success text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Siap Meningkatkan Hasil Panen Anda?</h2>
        <p class="lead mb-4">Bergabunglah dengan ribuan petani yang telah merasakan manfaat pupuk berkualitas dari kami</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="produk.php" class="btn btn-light btn-lg">
                <i class="fas fa-shopping-cart me-2"></i>Mulai Belanja
            </a>
            <button class="btn btn-outline-light btn-lg" onclick="contactWhatsApp()">
                <i class="fab fa-whatsapp me-2"></i>Hubungi Kami
            </button>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>