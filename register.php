<?php
require_once 'includes/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $no_hp = clean_input($_POST['no_hp']);
    $alamat = clean_input($_POST['alamat']);
    
    // Validasi input
    if (empty($nama) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        try {
            // Cek email sudah ada atau belum
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Insert user baru
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, no_hp, alamat) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nama, $email, md5($password), $no_hp, $alamat]);
                
                $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem!';
        }
    }
}

$page_title = 'Daftar';
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-success mb-3"></i>
                        <h3 class="card-title">Daftar Akun</h3>
                        <p class="text-muted">Buat akun baru untuk berbelanja</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <br><a href="login.php" class="alert-link">Klik di sini untuk login</a>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama" class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" id="nama" name="nama" 
                                       value="<?php echo isset($_POST['nama']) ? $_POST['nama'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required>
                                <div class="form-text">Minimal 6 karakter</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="no_hp" class="form-label">No. HP/WhatsApp</label>
                            <input type="tel" class="form-control" id="no_hp" name="no_hp" 
                                   value="<?php echo isset($_POST['no_hp']) ? $_POST['no_hp'] : ''; ?>" 
                                   placeholder="08xxxxxxxxxx">
                        </div>
                                                <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" 
                                      placeholder="Jalan, Kelurahan, Kecamatan, Kota"><?php echo isset($_POST['alamat']) ? $_POST['alamat'] : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agree" required>
                            <label class="form-check-label" for="agree">
                                Saya setuju dengan <a href="#" class="text-success">syarat dan ketentuan</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Sudah punya akun? 
                            <a href="login.php" class="text-success text-decoration-none">Login di sini</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
