<?php
require_once 'includes/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && md5($password) === $user['password']) {
                // Login berhasil
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                $_SESSION['success'] = 'Login berhasil! Selamat datang, ' . $user['nama'];
                
                // Redirect berdasarkan role
                if ($user['role'] == 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Email atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem!';
        }
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-sign-in-alt fa-3x text-success mb-3"></i>
                        <h3 class="card-title">Login</h3>
                        <p class="text-muted">Masuk ke akun Anda</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-2">Belum punya akun? 
                            <a href="register.php" class="text-success text-decoration-none">Daftar di sini</a>
                        </p>
                        <a href="forgot_password.php" class="text-muted text-decoration-none small">Lupa password?</a>
                    </div>
                    
                    <!-- Demo Accounts -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <strong>Demo Account:</strong><br>
                            Admin: admin@pupukstore.com / admin123<br>
                            Customer: budi@gmail.com / 123456
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>