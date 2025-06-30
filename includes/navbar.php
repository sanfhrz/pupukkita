<?php
// Hitung jumlah item di keranjang
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM keranjang WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['total'];
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-seedling me-2"></i><?php echo SITE_NAME; ?>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="produk.php">
                        <i class="fas fa-boxes me-1"></i>Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">
                        <i class="fas fa-info-circle me-1"></i>Tentang
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">
                        <i class="fas fa-phone me-1"></i>Kontak
                    </a>
                </li>
            </ul>
            
            <!-- Search Form -->
            <form class="d-flex me-3" method="GET" action="produk.php">
                <div class="input-group">
                    <input class="form-control form-control-sm" type="search" name="search" 
                           placeholder="Cari pupuk..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                    <button class="btn btn-outline-light btn-sm" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- User Menu -->
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Keranjang -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="keranjang.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['nama']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/index.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="my_orders.php">
                                <i class="fas fa-box me-2"></i>Pesanan Saya
                            </a></li>
                            <li><a class="dropdown-item" href="profil.php">
                                <i class="fas fa-user-edit me-2"></i>Edit Profil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>