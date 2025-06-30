<?php
require_once 'includes/config.php';

// Cek login customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    $_SESSION['error'] = 'Silakan login sebagai customer terlebih dahulu!';
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $telepon = clean_input($_POST['telepon']);
    $alamat = clean_input($_POST['alamat']);
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    $errors = [];
    
    // Validasi basic
    if (empty($nama)) $errors[] = 'Nama harus diisi';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    
    // Cek email duplikat
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = 'Email sudah digunakan pengguna lain';
    }
    
    // Validasi password jika ingin diubah
    if (!empty($password_baru)) {
        if (empty($password_lama)) {
            $errors[] = 'Password lama harus diisi';
        } else {
            // Cek password lama
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_password = $stmt->fetch()['password'];
            
                if (!password_verify($password_lama, $current_password)) {
                $errors[] = 'Password lama tidak sesuai';
            }
        }
        
        if (strlen($password_baru) < 6) {
            $errors[] = 'Password baru minimal 6 karakter';
        }
        
        if ($password_baru !== $konfirmasi_password) {
            $errors[] = 'Konfirmasi password tidak sesuai';
        }
    }
    
    if (empty($errors)) {
        try {
            if (!empty($password_baru)) {
                // Update dengan password baru
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, telepon = ?, alamat = ?, password = ? WHERE id = ?");
                $stmt->execute([$nama, $email, $telepon, $alamat, $hashed_password, $user_id]);
            } else {
                // Update tanpa password
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, telepon = ?, alamat = ? WHERE id = ?");
                $stmt->execute([$nama, $email, $telepon, $alamat, $user_id]);
            }
            
            $_SESSION['success'] = 'Profile berhasil diupdate!';
            header('Location: profile.php');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Terjadi kesalahan sistem!';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil riwayat pesanan
$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Statistik user
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_pesanan,
    SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_belanja,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pesanan_pending
    FROM pesanan WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$page_title = 'Profile Saya';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Profile</li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-success"></i>
                    </div>
                    <h5 class="card-title"><?php echo $user['nama']; ?></h5>
                    <p class="text-muted"><?php echo $user['email']; ?></p>
                    <small class="text-muted">Member sejak <?php echo date('M Y', strtotime($user['created_at'])); ?></small>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistik</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 mb-3">
                            <div class="border-bottom pb-2">
                                <h4 class="text-success mb-0"><?php echo $stats['total_pesanan']; ?></h4>
                                <small class="text-muted">Total Pesanan</small>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="border-bottom pb-2">
                                <h4 class="text-primary mb-0"><?php echo format_rupiah($stats['total_belanja']); ?></h4>
                                <small class="text-muted">Total Belanja</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <h4 class="text-warning mb-0"><?php echo $stats['pesanan_pending']; ?></h4>
                            <small class="text-muted">Pesanan Pending</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Tabs -->
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" 
                            data-bs-target="#profile" type="button" role="tab">
                        <i class="fas fa-user me-2"></i>Edit Profile
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="orders-tab" data-bs-toggle="tab" 
                            data-bs-target="#orders" type="button" role="tab">
                        <i class="fas fa-shopping-bag me-2"></i>Riwayat Pesanan
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabsContent">
                <!-- Edit Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="card border-top-0">
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Lengkap *</label>
                                        <input type="text" class="form-control" name="nama" 
                                               value="<?php echo $user['nama']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo $user['email']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nomor Telepon</label>
                                        <input type="tel" class="form-control" name="telepon" 
                                               value="<?php echo $user['telepon']; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea class="form-control" name="alamat" rows="3"><?php echo $user['alamat']; ?></textarea>
                                </div>
                                
                                <hr>
                                <h6 class="fw-bold">Ubah Password (Opsional)</h6>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Password Lama</label>
                                        <input type="password" class="form-control" name="password_lama">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" name="password_baru">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Konfirmasi Password</label>
                                        <input type="password" class="form-control" name="konfirmasi_password">
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">* Field wajib diisi</small>
                                    <button type="submit" name="update_profile" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders" role="tabpanel">
                    <div class="card border-top-0">
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                    <h5>Belum Ada Pesanan</h5>
                                    <p class="text-muted">Anda belum pernah melakukan pemesanan</p>
                                    <a href="produk.php" class="btn btn-success">
                                        <i class="fas fa-shopping-cart me-2"></i>Mulai Belanja
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No. Pesanan</th>
                                                <th>Tanggal</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $order['order_number']; ?></strong>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                                    <td class="fw-bold text-success"><?php echo format_rupiah($order['total']); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'pending' => 'warning',
                                                            'confirmed' => 'info',
                                                            'processing' => 'primary',
                                                            'shipped' => 'secondary',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $class = $status_class[$order['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $class; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="order_detail.php?order=<?php echo $order['order_number']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </a>
                                                        <?php if ($order['status'] == 'pending'): ?>
                                                            <button class="btn btn-sm btn-outline-success" 
                                                                    onclick="contactWhatsApp('Halo, saya ingin konfirmasi pembayaran pesanan <?php echo $order['order_number']; ?>')">
                                                                <i class="fab fa-whatsapp"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="order_history.php" class="btn btn-outline-primary">
                                        <i class="fas fa-history me-2"></i>Lihat Semua Riwayat
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
