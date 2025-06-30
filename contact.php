<?php
require_once 'includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $telepon = clean_input($_POST['telepon']);
    $subjek = clean_input($_POST['subjek']);
    $pesan = clean_input($_POST['pesan']);
    
    $errors = [];
    
    if (empty($nama)) $errors[] = 'Nama harus diisi';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (empty($subjek)) $errors[] = 'Subjek harus diisi';
    if (empty($pesan)) $errors[] = 'Pesan harus diisi';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (nama, email, telepon, subjek, pesan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$nama, $email, $telepon, $subjek, $pesan]);
            
            $_SESSION['success'] = 'Pesan berhasil dikirim! Kami akan segera menghubungi Anda.';
            header('Location: contact.php');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Terjadi kesalahan sistem!';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$page_title = 'Hubungi Kami';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Hubungi Kami</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-success">Hubungi Kami</h1>
        <p class="lead text-muted">Kami siap membantu Anda dengan pertanyaan dan kebutuhan pupuk berkualitas</p>
    </div>
    
    <div class="row">
        <!-- Contact Info -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Kontak</h5>
                </div>
                <div class="card-body">
                    <div class="contact-info">
                        <div class="contact-item mb-4">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-details">
                                    <h6 class="fw-bold mb-1">Alamat Toko</h6>
                                    <p class="mb-0 text-muted">
                                        Jl. Teuku Umar No. 84<br>
                                        Kisaran Barat<br>
                                        Kabupaten Asahan, Sumatera Utara<br>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-4">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-details">
                                    <h6 class="fw-bold mb-1">Telepon</h6>
                                    <p class="mb-0 text-muted">
                                        <a href="tel:+6281234567890" class="text-decoration-none">+62 821-6572-2838</a><br>
                                        <a href="tel:+6281234567891" class="text-decoration-none">+62 812-3456-7891</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-4">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-details">
                                    <h6 class="fw-bold mb-1">Email</h6>
                                    <p class="mb-0 text-muted">
                                        <a href="mailto:info@tokopupuk.com" class="text-decoration-none">info@tokopupuk.com</a><br>
                                        <a href="mailto:cs@tokopupuk.com" class="text-decoration-none">cs@tokopupuk.com</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-4">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <div class="contact-details">
                                    <h6 class="fw-bold mb-1">WhatsApp</h6>
                                    <p class="mb-0 text-muted">
                                        <button class="btn btn-success btn-sm" onclick="contactWhatsApp('Halo, saya ingin bertanya tentang produk pupuk')">
                                            <i class="fab fa-whatsapp me-1"></i>Chat Sekarang
                                        </button>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-details">
                                    <h6 class="fw-bold mb-1">Jam Operasional</h6>
                                    <p class="mb-0 text-muted">
                                        Senin - Jumat: 08:00 - 17:00<br>
                                        Sabtu: 08:00 - 15:00<br>
                                        Minggu: Tutup
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="col-lg-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Kirim Pesan</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" name="nama" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" name="telepon">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subjek *</label>
                                <select class="form-select" name="subjek" required>
                                    <option value="">Pilih Subjek</option>
                                    <option value="Pertanyaan Produk">Pertanyaan Produk</option>
                                    <option value="Konsultasi Pupuk">Konsultasi Pupuk</option>
                                    <option value="Keluhan Pesanan">Keluhan Pesanan</option>
                                    <option value="Kerjasama">Kerjasama</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pesan *</label>
                            <textarea class="form-control" name="pesan" rows="6" required 
                                      placeholder="Tuliskan pesan Anda di sini..."></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">* Field wajib diisi</small>
                            <button type="submit" name="send_message" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Pesan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Map Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i>Lokasi Toko</h5>
                </div>
                <div class="card-body p-0">
                    <div class="map-container" style="height: 400px; background: #f8f9fa;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                                                <i class="fas fa-map-marked-alt fa-3x text-success mb-3"></i>
                                <h5>Peta Lokasi</h5>
                                <p class="text-muted">Jl. Pertanian No. 123, Kelurahan Subur<br>Kecamatan Makmur, Kota Berkah</p>
                                <button class="btn btn-success" onclick="openGoogleMaps()">
                                    <i class="fas fa-directions me-2"></i>Buka di Google Maps
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Pertanyaan yang Sering Diajukan</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    Bagaimana cara memilih pupuk yang tepat untuk tanaman saya?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Pemilihan pupuk tergantung pada jenis tanaman, kondisi tanah, dan fase pertumbuhan. Tim ahli kami siap memberikan konsultasi gratis untuk membantu Anda memilih pupuk yang tepat. Hubungi kami melalui WhatsApp atau datang langsung ke toko.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    Apakah ada garansi untuk produk pupuk?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ya, kami memberikan garansi kualitas untuk semua produk pupuk. Jika ada masalah dengan produk yang Anda beli, silakan hubungi kami dalam waktu 7 hari setelah pembelian dengan membawa bukti pembelian.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    Bagaimana cara pengiriman dan berapa lama estimasinya?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Kami melayani pengiriman ke seluruh Indonesia melalui ekspedisi terpercaya. Estimasi pengiriman 2-5 hari kerja untuk area Jawa dan 3-7 hari kerja untuk luar Jawa. Gratis ongkir untuk pembelian minimal Rp 500.000.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                                    Apakah bisa konsultasi gratis tentang masalah tanaman?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Tentu saja! Kami memiliki tim ahli pertanian yang siap memberikan konsultasi gratis. Anda bisa konsultasi melalui WhatsApp, telepon, atau datang langsung ke toko. Kirimkan foto tanaman dan deskripsi masalahnya untuk mendapat solusi terbaik.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5">
                                    Metode pembayaran apa saja yang tersedia?
                                </button>
                            </h2>
                            <div id="collapse5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Kami menerima berbagai metode pembayaran: Transfer Bank (BCA, Mandiri), E-wallet (OVO, GoPay, DANA), dan Cash on Delivery (COD) untuk area tertentu. Semua transaksi dijamin aman dan terpercaya.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contact-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #198754, #20c997);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin-right: 15px;
    flex-shrink: 0;
}

.contact-details h6 {
    color: #198754;
}

.contact-item {
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
}

.contact-item:last-child {
    border-bottom: none;
}

.map-container {
    position: relative;
    overflow: hidden;
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #198754;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}
</style>

<script>
function openGoogleMaps() {
    // Ganti dengan koordinat sebenarnya
    const lat = -6.2088;
    const lng = 106.8456;
    const url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
    window.open(url, '_blank');
}

// Auto-fill form jika user sudah login
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php
        $stmt = $pdo->prepare("SELECT nama, email, telepon FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();
        if ($user_data):
        ?>
        document.querySelector('input[name="nama"]').value = '<?php echo $user_data['nama']; ?>';
        document.querySelector('input[name="email"]').value = '<?php echo $user_data['email']; ?>';
        document.querySelector('input[name="telepon"]').value = '<?php echo $user_data['telepon']; ?>';
        <?php endif; ?>
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>
