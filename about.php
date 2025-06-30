<?php
require_once 'includes/config.php';

$page_title = 'Tentang Kami';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Tentang Kami</li>
        </ol>
    </nav>

    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold text-success mb-4">Tentang Toko Pupuk Berkah</h1>
            <p class="lead text-muted mb-4">
                Sejak 2010, kami telah menjadi mitra terpercaya petani dan pecinta tanaman dalam menyediakan pupuk berkualitas tinggi untuk hasil panen yang optimal.
            </p>
            <div class="row">
                <div class="col-6">
                    <div class="text-center">
                        <h3 class="text-success fw-bold">13+</h3>
                        <p class="text-muted">Tahun Pengalaman</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-center">
                        <h3 class="text-success fw-bold">10K+</h3>
                        <p class="text-muted">Pelanggan Puas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="position-relative">
                <img src="assets/images/about-hero.jpg" alt="Toko Pupuk Berkah" class="img-fluid rounded shadow-lg">
                <div class="position-absolute top-0 start-0 w-100 h-100 bg-success opacity-10 rounded"></div>
            </div>
        </div>
    </div>

    <!-- Vision Mission -->
    <div class="row mb-5">
        <div class="col-lg-6 mb-4">
            <div class="card h-100 border-success">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-eye fa-3x text-success"></i>
                    </div>
                    <h4 class="fw-bold text-success mb-3">Visi Kami</h4>
                    <p class="text-muted">
                        Menjadi distributor pupuk terdepan yang mendukung ketahanan pangan nasional melalui penyediaan produk berkualitas dan layanan terbaik bagi seluruh petani Indonesia.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100 border-success">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-bullseye fa-3x text-success"></i>
                    </div>
                    <h4 class="fw-bold text-success mb-3">Misi Kami</h4>
                    <ul class="text-muted text-start">
                        <li>Menyediakan pupuk berkualitas dengan harga terjangkau</li>
                        <li>Memberikan edukasi dan konsultasi pertanian</li>
                        <li>Membangun kemitraan jangka panjang dengan petani</li>
                        <li>Mendukung pertanian berkelanjutan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Values -->
    <div class="text-center mb-5">
        <h2 class="fw-bold text-success mb-4">Nilai-Nilai Kami</h2>
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-shield-alt fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-bold">Kualitas</h5>
                        <p class="text-muted small">Hanya menjual produk pupuk berkualitas tinggi dari brand terpercaya</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-handshake fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-bold">Kepercayaan</h5>
                        <p class="text-muted small">Membangun hubungan jangka panjang berdasarkan kepercayaan</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-users fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-bold">Pelayanan</h5>
                        <p class="text-muted small">Memberikan pelayanan terbaik dengan tim yang berpengalaman</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-leaf fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-bold">Berkelanjutan</h5>
                        <p class="text-muted small">Mendukung praktik pertanian yang ramah lingkungan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Story -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Sejarah Kami</h4>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h5 class="fw-bold text-success">2010 - Awal Mula</h5>
                                <p class="text-muted">
                                    Toko Pupuk Berkah didirikan oleh Bapak Ahmad Subur dengan modal awal yang terbatas namun semangat yang besar untuk membantu petani lokal mendapatkan pupuk berkualitas.
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h5 class="fw-bold text-success">2013 - Ekspansi Produk</h5>
                                <p class="text-muted">
                                    Mulai memperluas jenis produk dengan menambahkan pupuk organik, pestisida ramah lingkungan, dan alat-alat pertanian modern.
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h5 class="fw-bold text-success">2016 - Sertifikasi Resmi</h5>
                                <p class="text-muted">
                                    Mendapatkan sertifikasi resmi sebagai distributor pupuk bersubsidi dan menjadi mitra resmi beberapa brand pupuk ternama.
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h5 class="fw-bold text-success">2019 - Layanan Konsultasi</h5>
                                <p class="text-muted">
                                    Meluncurkan layanan konsultasi pertanian gratis dengan tim ahli untuk membantu petani meningkatkan hasil panen.
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h5 class="fw-bold text-success">2022 - Era Digital</h5>
                                <p class="text-muted">
                                    Meluncurkan platform online untuk memudahkan petani berbelanja pupuk dengan sistem pengiriman ke seluruh Indonesia.
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h5 class="fw-bold text-success">2025 - Sekarang</h5>
                                <p class="text-muted">
                                    Terus berinovasi dengan teknologi terbaru dan memperluas jaringan untuk melayani lebih banyak petani di seluruh nusantara.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <div class="text-center mb-5">
        <h2 class="fw-bold text-success mb-4">Tim Kami</h2>
        <p class="text-muted mb-5">Dikelola oleh tim profesional yang berpengalaman di bidang pertanian</p>

        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <img src="assets/images/person.jpg" alt="Ahmad Subur" class="rounded-circle" width="100" height="100" style="object-fit: cover;">
                        </div>
                        <h5 class="fw-bold">Ahmad Subur</h5>
                        <p class="text-success mb-2">Founder & CEO</p>
                        <p class="text-muted small">
                            Berpengalaman 20+ tahun di bidang pertanian dan distribusi pupuk. Lulusan Fakultas Pertanian IPB.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <img src="assets/images/person2.jpg" alt="Siti Makmur" class="rounded-circle" width="100" height="100" style="object-fit: cover;">
                        </div>
                        <h5 class="fw-bold">Siti Makmur</h5>
                        <p class="text-success mb-2">Manajer Operasional</p>
                        <p class="text-muted small">
                            Ahli dalam manajemen supply chain dan quality control. Memastikan semua produk berkualitas tinggi.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <img src="assets/images/person3.jpg" alt="Budi Tani" class="rounded-circle" width="100" height="100" style="object-fit: cover;">
                        </div>
                        <h5 class="fw-bold">Budi Tani</h5>
                        <p class="text-success mb-2">Konsultan Pertanian</p>
                        <p class="text-muted small">
                            Sarjana Agroteknologi dengan pengalaman konsultasi untuk berbagai jenis tanaman dan kondisi lahan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Achievements -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-success text-white">
                <div class="card-body text-center p-5">
                    <h3 class="fw-bold mb-4">Pencapaian Kami</h3>
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="fw-bold">10,000+</h2>
                            <p class="mb-0">Pelanggan Setia</p>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="fw-bold">500+</h2>
                            <p class="mb-0">Produk Tersedia</p>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="fw-bold">50+</h2>
                            <p class="mb-0">Kota Terjangkau</p>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="fw-bold">98%</h2>
                            <p class="mb-0">Kepuasan Pelanggan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Certifications -->
    <div class="text-center mb-5">
        <h2 class="fw-bold text-success mb-4">Sertifikasi & Penghargaan</h2>
        <div class="row justify-content-center">
            <div class="col-lg-2 col-md-3 col-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <i class="fas fa-certificate fa-2x text-success mb-2"></i>
                        <small class="text-muted">Distributor Resmi Pupuk Bersubsidi</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <i class="fas fa-award fa-2x text-success mb-2"></i>
                        <small class="text-muted">Best Service Award 2022</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <i class="fas fa-medal fa-2x text-success mb-2"></i>
                        <small class="text-muted">ISO 9001:2015 Certified</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-4 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <i class="fas fa-star fa-2x text-success mb-2"></i>
                        <small class="text-muted">Top Rated Seller</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="text-center mb-5">
        <div class="card border-success">
            <div class="card-body p-5">
                <h3 class="fw-bold text-success mb-3">Bergabunglah dengan Ribuan Petani Sukses</h3>
                <p class="text-muted mb-4">
                    Dapatkan pupuk berkualitas, konsultasi gratis, dan dukungan penuh untuk kesuksesan pertanian Anda
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="produk.php" class="btn btn-success btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>Mulai Belanja
                    </a>
                    <button class="btn btn-outline-success btn-lg" onclick="contactWhatsApp('Halo, saya ingin konsultasi tentang pupuk untuk tanaman saya')">
                        <i class="fab fa-whatsapp me-2"></i>Konsultasi Gratis
                    </button>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-phone me-2"></i>Hubungi Kami
                    </a>
                </div>
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
        background: #198754;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 30px;
    }

    .timeline-marker {
        position: absolute;
        left: -23px;
        top: 5px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 2px #198754;
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #198754;
    }

    .timeline-content h5 {
        margin-bottom: 10px;
    }

    .timeline-content p {
        margin-bottom: 0;
        line-height: 1.6;
    }

    /* Team images placeholder */
    .card img {
        background: linear-gradient(135deg, #198754, #20c997);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .timeline {
            padding-left: 20px;
        }

        .timeline::before {
            left: 10px;
        }

        .timeline-marker {
            left: -18px;
        }

        .display-4 {
            font-size: 2rem;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>