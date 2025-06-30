<?php
require_once 'includes/config.php';

// Cek login customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    $_SESSION['error'] = 'Silakan login sebagai customer terlebih dahulu!';
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        // Cek stok produk
        $stmt = $pdo->prepare("SELECT p.stok FROM keranjang k 
                               JOIN produk p ON k.produk_id = p.id 
                               WHERE k.id = ? AND k.user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        $stok = $stmt->fetch()['stok'] ?? 0;
        
        if ($quantity <= $stok) {
            $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
            $_SESSION['success'] = 'Keranjang berhasil diupdate!';
        } else {
            $_SESSION['error'] = 'Jumlah melebihi stok tersedia!';
        }
    }
    
    header('Location: keranjang.php');
    exit();
}

// Handle remove item
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    
    $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    
    $_SESSION['success'] = 'Item berhasil dihapus dari keranjang!';
    header('Location: keranjang.php');
    exit();
}

// Ambil data keranjang
$stmt = $pdo->prepare("SELECT k.*, p.nama_produk, p.harga, p.gambar, p.stok, p.berat 
                       FROM keranjang k 
                       JOIN produk p ON k.produk_id = p.id 
                       WHERE k.user_id = ? AND p.status = 'active' 
                       ORDER BY k.created_at DESC");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Hitung total
$total_harga = 0;
$total_berat = 0;
foreach ($cart_items as $item) {
    $total_harga += $item['harga'] * $item['jumlah'];
    $total_berat += (float)str_replace(['kg', 'gram', 'liter'], '', $item['berat']) * $item['jumlah'];
}

$page_title = 'Keranjang Belanja';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Keranjang</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Keranjang Belanja</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($cart_items)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5>Keranjang Anda Kosong</h5>
                            <p class="text-muted">Belum ada produk yang ditambahkan ke keranjang</p>
                            <a href="produk.php" class="btn btn-success">
                                <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th>Harga</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="assets/img/pupuk/<?php echo $item['gambar'] ?: 'default.jpg'; ?>" 
                                                         alt="<?php echo $item['nama_produk']; ?>" 
                                                         class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                                                                                <h6 class="mb-1"><?php echo $item['nama_produk']; ?></h6>
                                                        <small class="text-muted">Berat: <?php echo $item['berat']; ?></small>
                                                        <br><small class="text-muted">Stok: <?php echo $item['stok']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <span class="fw-bold"><?php echo format_rupiah($item['harga']); ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <div class="input-group" style="width: 120px;">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                onclick="decreaseQty(<?php echo $item['id']; ?>)">-</button>
                                                        <input type="number" class="form-control form-control-sm text-center" 
                                                               id="qty_<?php echo $item['id']; ?>" name="quantity" 
                                                               value="<?php echo $item['jumlah']; ?>" 
                                                               min="1" max="<?php echo $item['stok']; ?>"
                                                               onchange="updateCart(<?php echo $item['id']; ?>)">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                onclick="increaseQty(<?php echo $item['id']; ?>, <?php echo $item['stok']; ?>)">+</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="align-middle">
                                                <span class="fw-bold text-success">
                                                    <?php echo format_rupiah($item['harga'] * $item['jumlah']); ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <a href="?remove=<?php echo $item['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   onclick="return confirm('Yakin ingin menghapus item ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Cart Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="produk.php" class="btn btn-outline-success">
                                <i class="fas fa-arrow-left me-2"></i>Lanjut Belanja
                            </a>
                            <button class="btn btn-outline-secondary" onclick="clearCart()">
                                <i class="fas fa-trash me-2"></i>Kosongkan Keranjang
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($cart_items)): ?>
        <div class="col-lg-4">
            <!-- Ringkasan Pesanan -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Ringkasan Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (<?php echo count($cart_items); ?> item)</span>
                        <span class="fw-bold"><?php echo format_rupiah($total_harga); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Estimasi Berat</span>
                        <span><?php echo number_format($total_berat, 1); ?> kg</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold text-success fs-5"><?php echo format_rupiah($total_harga); ?></span>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="checkout.php" class="btn btn-success btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Checkout
                        </a>
                        <button class="btn btn-outline-success" onclick="contactWhatsApp('Halo, saya ingin melakukan pemesanan dengan total <?php echo format_rupiah($total_harga); ?>')">
                            <i class="fab fa-whatsapp me-2"></i>Pesan via WhatsApp
                        </button>
                    </div>
                    
                    <!-- Info Pengiriman -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Info:</strong> Ongkos kirim akan dihitung pada halaman checkout berdasarkan alamat tujuan.
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Promo/Kupon -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Kode Promo</h6>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Masukkan kode promo" id="promo_code">
                        <button class="btn btn-outline-success" onclick="applyPromo()">Gunakan</button>
                    </div>
                    <small class="text-muted">Dapatkan diskon dengan kode promo khusus</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function increaseQty(cartId, maxStock) {
    const input = document.getElementById('qty_' + cartId);
    const current = parseInt(input.value);
    if (current < maxStock) {
        input.value = current + 1;
        updateCart(cartId);
    }
}

function decreaseQty(cartId) {
    const input = document.getElementById('qty_' + cartId);
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
        updateCart(cartId);
    }
}

function updateCart(cartId) {
    const quantity = document.getElementById('qty_' + cartId).value;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="cart_id" value="${cartId}">
        <input type="hidden" name="quantity" value="${quantity}">
        <input type="hidden" name="update_cart" value="1">
    `;
    document.body.appendChild(form);
    form.submit();
}

function clearCart() {
    if (confirm('Yakin ingin mengosongkan keranjang?')) {
        window.location.href = 'ajax/clear_cart.php';
    }
}

function applyPromo() {
    const promoCode = document.getElementById('promo_code').value;
    if (promoCode.trim() === '') {
        alert('Masukkan kode promo terlebih dahulu');
        return;
    }
    
    // Ajax call untuk apply promo
    
    fetch('ajax/apply_promo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `promo_code=${encodeURIComponent(promoCode)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Kode promo berhasil diterapkan!');
            location.reload();
        } else {
            alert(data.message || 'Kode promo tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan sistem');
    });
}
</script>

    <!-- Footer -->
<?php include 'includes/footer.php'; ?>