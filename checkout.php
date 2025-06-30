<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT k.*, p.nama_produk, p.harga, p.gambar, p.stok, p.berat,
           k.jumlah as quantity, k.produk_id as product_id
    FROM keranjang k
    JOIN produk p ON k.produk_id = p.id
    WHERE k.user_id = ? AND p.status = 'active'
    ORDER BY k.created_at DESC
");

$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header('Location: keranjang.php');
    exit();
}

// Get user data
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Calculate totals
$subtotal = 0;
$total_weight = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['harga'] * $item['quantity'];
    
    // Extract numeric weight (e.g., "25kg" becomes 25)
    $berat_numeric = (float)preg_replace('/[^0-9.]/', '', $item['berat'] ?? '1');
    $total_weight += $berat_numeric * $item['quantity'];
}

// Simple shipping cost calculation based on weight
$shipping_cost = 0;
if ($total_weight <= 1) {
    $shipping_cost = 15000; // <= 1kg
} elseif ($total_weight <= 5) {
    $shipping_cost = 25000; // 1-5kg
} elseif ($total_weight <= 10) {
    $shipping_cost = 35000; // 5-10kg
} elseif ($total_weight <= 25) {
    $shipping_cost = 50000; // 10-25kg
} else {
    $shipping_cost = 75000; // >25kg
}

$total = $subtotal + $shipping_cost;

$page_title = 'Checkout';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="keranjang.php">Keranjang</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-success">
                <i class="fas fa-credit-card me-2"></i>Checkout
            </h2>
            <p class="text-muted">Lengkapi informasi untuk menyelesaikan pesanan Anda</p>
        </div>
    </div>

    <form method="POST" action="process_checkout.php" id="checkoutForm">
        <div class="row">
            <!-- Left Column - Forms -->
            <div class="col-lg-8">
                <!-- Shipping Information -->
                <div class="card border-success mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Informasi Pengiriman</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" name="nama_lengkap"
                                    value="<?php echo htmlspecialchars($user['nama'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Telepon *</label>
                                <input type="tel" class="form-control" name="telepon"
                                    value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap *</label>
                            <textarea class="form-control" name="alamat" rows="3" required
                                placeholder="Masukkan alamat lengkap termasuk RT/RW, Kelurahan, Kecamatan"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Provinsi *</label>
                                <select class="form-select" name="provinsi" required>
                                    <option value="">Pilih Provinsi</option>
                                    <option value="Aceh">Aceh</option>
                                    <option value="Sumatera Utara">Sumatera Utara</option>
                                    <option value="Sumatera Barat">Sumatera Barat</option>
                                    <option value="Riau">Riau</option>
                                    <option value="Kepulauan Riau">Kepulauan Riau</option>
                                    <option value="Jambi">Jambi</option>
                                    <option value="Sumatera Selatan">Sumatera Selatan</option>
                                    <option value="Bangka Belitung">Bangka Belitung</option>
                                    <option value="Bengkulu">Bengkulu</option>
                                    <option value="Lampung">Lampung</option>
                                    <option value="DKI Jakarta">DKI Jakarta</option>
                                    <option value="Jawa Barat">Jawa Barat</option>
                                    <option value="Jawa Tengah">Jawa Tengah</option>
                                    <option value="DI Yogyakarta">DI Yogyakarta</option>
                                    <option value="Jawa Timur">Jawa Timur</option>
                                    <option value="Banten">Banten</option>
                                    <option value="Bali">Bali</option>
                                    <option value="Nusa Tenggara Barat">Nusa Tenggara Barat</option>
                                    <option value="Nusa Tenggara Timur">Nusa Tenggara Timur</option>
                                    <option value="Kalimantan Barat">Kalimantan Barat</option>
                                    <option value="Kalimantan Tengah">Kalimantan Tengah</option>
                                    <option value="Kalimantan Selatan">Kalimantan Selatan</option>
                                    <option value="Kalimantan Timur">Kalimantan Timur</option>
                                    <option value="Kalimantan Utara">Kalimantan Utara</option>
                                    <option value="Sulawesi Utara">Sulawesi Utara</option>
                                    <option value="Sulawesi Tengah">Sulawesi Tengah</option>
                                    <option value="Sulawesi Selatan">Sulawesi Selatan</option>
                                    <option value="Sulawesi Tenggara">Sulawesi Tenggara</option>
                                    <option value="Gorontalo">Gorontalo</option>
                                    <option value="Sulawesi Barat">Sulawesi Barat</option>
                                    <option value="Maluku">Maluku</option>
                                    <option value="Maluku Utara">Maluku Utara</option>
                                    <option value="Papua">Papua</option>
                                    <option value="Papua Barat">Papua Barat</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kota/Kabupaten *</label>
                                <input type="text" class="form-control" name="kota" required
                                    placeholder="Masukkan nama kota/kabupaten">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kode Pos *</label>
                                <input type="text" class="form-control" name="kode_pos"
                                    pattern="[0-9]{5}" maxlength="5" required
                                    placeholder="12345">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card border-success mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Metode Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check border rounded p-3">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                        id="bank_transfer" value="bank_transfer" checked>
                                    <label class="form-check-label w-100" for="bank_transfer">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university fa-2x text-primary me-3"></i>
                                            <div>
                                                <strong>Transfer Bank</strong>
                                                <small class="d-block text-muted">BCA, Mandiri, BRI, BNI</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check border rounded p-3">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                        id="cod" value="cod">
                                    <label class="form-check-label w-100" for="cod">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-money-bill-wave fa-2x text-success me-3"></i>
                                            <div>
                                                <strong>Bayar di Tempat (COD)</strong>
                                                <small class="d-block text-muted">Bayar saat barang diterima</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Transfer Details -->
                        <div id="bank_details" class="mt-3">
                            <div class="alert alert-info">
                                <h6 class="fw-bold mb-2">Informasi Rekening:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>BCA:</strong> 1234567890</p>
                                        <p class="mb-1"><strong>Mandiri:</strong> 0987654321</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>BRI:</strong> 5678901234</p>
                                        <p class="mb-1"><strong>BNI:</strong> 4321098765</p>
                                    </div>
                                </div>
                                <p class="mb-0"><strong>A.n:</strong> Sahabat Tani</p>
                            </div>
                        </div>

                        <!-- COD Details -->
                        <div id="cod_details" class="mt-3" style="display: none;">
                            <div class="alert alert-warning">
                                <h6 class="fw-bold mb-2">Ketentuan COD:</h6>
                                <ul class="mb-0">
                                    <li>Tersedia untuk wilayah Jabodetabek</li>
                                    <li>Biaya tambahan Rp 5.000 untuk COD</li>
                                    <li>Pembayaran dengan uang pas</li>
                                    <li>Barang akan dikirim setelah konfirmasi telepon</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="card border-success mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Catatan Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="catatan" rows="3"
                            placeholder="Catatan khusus untuk pesanan Anda (opsional)"></textarea>
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Summary -->
            <div class="col-lg-4">
                <div class="card border-success sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Ringkasan Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <!-- Cart Items -->
                        <div class="mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <img src="<?php echo $item['gambar'] ? 'assets/img/pupuk/' . $item['gambar'] : 'assets/images/no-image.jpg'; ?>"
                                        alt="<?php echo htmlspecialchars($item['nama_produk']); ?>"
                                        class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo strlen($item['nama_produk']) > 30 ? substr($item['nama_produk'], 0, 30) . '...' : $item['nama_produk']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo $item['quantity']; ?> × Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?>
                                        </small>
                                        <div class="fw-bold text-success">
                                            Rp <?php echo number_format($item['harga'] * $item['quantity'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Ongkos Kirim:</span>
                                <span id="shipping_cost_display">Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" id="cod_fee" style="display: none;">
                                <span>Biaya COD:</span>
                                <span>Rp 5.000</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold h5">
                                <span>Total:</span>
                                <span class="text-success" id="total_display">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <!-- Weight Info -->
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">
                                <i class="fas fa-weight me-1"></i>
                                Total Berat: <?php echo $total_weight; ?> kg
                            </small>
                        </div>

                        <!-- Shipping Info -->
                        <div class="mt-2 p-2 bg-info bg-opacity-10 rounded">
                            <small class="text-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Estimasi pengiriman: 2-5 hari kerja
                            </small>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check me-2"></i>Buat Pesanan
                            </button>
                        </div>

                        <!-- Security Info -->
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Transaksi Anda aman dan terlindungi
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Promo Code -->
                <div class="card border-success mt-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-tag me-2"></i>Kode Promo
                        </h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="promo_code"
                                placeholder="Masukkan kode promo">
                            <button class="btn btn-outline-success" type="button" onclick="applyPromo()">
                                Gunakan
                            </button>
                        </div>
                        <div id="promo_message" class="mt-2"></div>
                    </div>
                </div>

                <!-- Shipping Calculator -->
                <div class="card border-success mt-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-calculator me-2"></i>Kalkulator Ongkir
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Berat</th>
                                        <th>Ongkir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="<?php echo $total_weight <= 1 ? 'table-success' : ''; ?>">
                                        <td>≤ 1 kg</td>
                                        <td>Rp 15.000</td>
                                    </tr>
                                    <tr class="<?php echo $total_weight > 1 && $total_weight <= 5 ? 'table-success' : ''; ?>">
                                        <td>1-5 kg</td>
                                        <td>Rp 25.000</td>
                                    </tr>
                                    <tr class="<?php echo $total_weight > 5 && $total_weight <= 10 ? 'table-success' : ''; ?>">
                                        <td>5-10 kg</td>
                                        <td>Rp 35.000</td>
                                    </tr>
                                    <tr class="<?php echo $total_weight > 10 && $total_weight <= 25 ? 'table-success' : ''; ?>">
                                        <td>10-25 kg</td>
                                        <td>Rp 50.000</td>
                                    </tr>
                                    <tr class="<?php echo $total_weight > 25 ? 'table-success' : ''; ?>">
                                        <td>> 25 kg</td>
                                        <td>Rp 75.000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-truck me-1"></i>
                            Ongkir dihitung berdasarkan total berat pesanan
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .form-check:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .form-check-input:checked + .form-check-label {
        color: #198754;
    }

    .sticky-top {
        z-index: 1020;
    }

    .table-success {
        background-color: rgba(25, 135, 84, 0.1);
        font-weight: 600;
    }

    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        transition: all 0.3s ease;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .alert {
        border: none;
        border-radius: 10px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .border-success {
        border-color: #28a745 !important;
    }

    .bg-success {
        background-color: #28a745 !important;
    }

    .text-success {
        color: #28a745 !important;
    }

    .btn-outline-success {
        color: #28a745;
        border-color: #28a745;
    }

    .btn-outline-success:hover {
        background-color: #28a745;
        border-color: #28a745;
    }
</style>

<script>
    // Payment method change handler
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const bankDetails = document.getElementById('bank_details');
            const codDetails = document.getElementById('cod_details');
            const codFee = document.getElementById('cod_fee');
            const totalDisplay = document.getElementById('total_display');

            if (this.value === 'bank_transfer') {
                bankDetails.style.display = 'block';
                codDetails.style.display = 'none';
                codFee.style.display = 'none';

                // Update total without COD fee
                const newTotal = <?php echo $total; ?>;
                totalDisplay.textContent = 'Rp ' + newTotal.toLocaleString('id-ID');
            } else if (this.value === 'cod') {
                bankDetails.style.display = 'none';
                codDetails.style.display = 'block';
                codFee.style.display = 'flex';

                // Update total with COD fee
                const newTotal = <?php echo $total; ?> + 5000;
                totalDisplay.textContent = 'Rp ' + newTotal.toLocaleString('id-ID');
            }
        });
    });

    // Form validation
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        // Basic validation
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            showToast('Mohon lengkapi semua field yang wajib diisi', 'error');
            return;
        }

        // Phone validation
        const phone = document.querySelector('input[name="telepon"]').value;
        if (!/^[0-9+\-\s]{10,15}$/.test(phone)) {
            e.preventDefault();
            showToast('Format nomor telepon tidak valid', 'error');
            return;
        }

        // Postal code validation
        const postalCode = document.querySelector('input[name="kode_pos"]').value;
        if (!/^[0-9]{5}$/.test(postalCode)) {
            e.preventDefault();
            showToast('Kode pos harus 5 digit angka', 'error');
            return;
        }

        // Show loading
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
        submitBtn.disabled = true;

        // Allow form to submit normally
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    });

    // Promo code function
    function applyPromo() {
        const promoCode = document.getElementById('promo_code').value.trim();
        const messageDiv = document.getElementById('promo_message');

        if (!promoCode) {
            messageDiv.innerHTML = '<small class="text-danger">Masukkan kode promo</small>';
            return;
        }

        // Show loading
        messageDiv.innerHTML = '<small class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Memproses...</small>';

        fetch('ajax/apply_promo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `promo_code=${promoCode}&total=<?php echo $total; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = `<small class="text-success">✓ ${data.message}</small>`;
                    // Update total display
                    document.getElementById('total_display').textContent = 'Rp ' + data.new_total.toLocaleString('id-ID');
                } else {
                    messageDiv.innerHTML = `<small class="text-danger">✗ ${data.message}</small>`;
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<small class="text-danger">Gagal memproses kode promo</small>';
            });
    }

    // Auto-save form data to localStorage
    function saveFormData() {
        const formData = {};
        const form = document.getElementById('checkoutForm');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            if (input.type !== 'radio' || input.checked) {
                formData[input.name] = input.value;
            }
        });

        localStorage.setItem('checkoutFormData', JSON.stringify(formData));
    }

    // Load saved form data
    function loadFormData() {
        const savedData = localStorage.getItem('checkoutFormData');
        if (savedData) {
            const formData = JSON.parse(savedData);

            Object.keys(formData).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'radio') {
                        if (input.value === formData[key]) {
                            input.checked = true;
                            input.dispatchEvent(new Event('change'));
                        }
                    } else {
                        input.value = formData[key];
                    }
                }
            });
        }
    }

    // Auto-save on input change
    document.addEventListener('DOMContentLoaded', function() {
        loadFormData();

        const form = document.getElementById('checkoutForm');
        form.addEventListener('input', saveFormData);
        form.addEventListener('change', saveFormData);
    });

    // Toast notification function
    function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Add to body
        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

    // Clear saved data on successful order
    window.addEventListener('beforeunload', function() {
        // Only clear if we're navigating to success page
        if (window.location.href.includes('order_success.php')) {
            localStorage.removeItem('checkoutFormData');
        }
    });

    // Add smooth scrolling for better UX
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Add input formatting
    document.querySelector('input[name="telepon"]').addEventListener('input', function(e) {
        // Remove non-numeric characters except + and -
        this.value = this.value.replace(/[^0-9+\-]/g, '');
    });

    document.querySelector('input[name="kode_pos"]').addEventListener('input', function(e) {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Add real-time validation feedback
    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
        field.addEventListener('blur', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Add loading animation for better UX
    function showLoading() {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.innerHTML = `
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="text-center">
                    <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="text-success">Memproses pesanan...</h5>
                    <p class="text-muted">Mohon tunggu sebentar</p>
                </div>
            </div>
        `;
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: flex;
        `;
        document.body.appendChild(overlay);
    }

    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // Enhanced form submission
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        showLoading();
        
        // Hide loading after 3 seconds if still visible (fallback)
        setTimeout(() => {
            hideLoading();
        }, 10000);
    });

    // Add cart item hover effects
    document.querySelectorAll('.d-flex.align-items-center.mb-3').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
            this.style.borderRadius = '8px';
            this.style.padding = '8px';
            this.style.transition = 'all 0.3s ease';
        });

        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
            this.style.padding = '0';
        });
    });

    // Add province change handler for better UX
    document.querySelector('select[name="provinsi"]').addEventListener('change', function() {
        const kotaInput = document.querySelector('input[name="kota"]');
        
        // Clear kota field when province changes
        kotaInput.value = '';
        
        // Add placeholder based on province
        if (this.value) {
            kotaInput.placeholder = `Masukkan nama kota di ${this.value}`;
        } else {
            kotaInput.placeholder = 'Masukkan nama kota/kabupaten';
        }
    });

    // Add copy bank account functionality
    function copyBankAccount(bank, account) {
        navigator.clipboard.writeText(account).then(function() {
            showToast(`Nomor rekening ${bank} berhasil disalin!`, 'success');
        }).catch(function() {
            showToast('Gagal menyalin nomor rekening', 'error');
        });
    }

    // Make bank account numbers clickable
    document.addEventListener('DOMContentLoaded', function() {
        const bankDetails = document.getElementById('bank_details');
        const bankAccounts = bankDetails.querySelectorAll('p');
        
        bankAccounts.forEach(p => {
            if (p.innerHTML.includes(':')) {
                const parts = p.innerHTML.split(':');
                if (parts.length === 2) {
                    const bank = parts[0].replace('<strong>', '').replace('</strong>', '');
                    const account = parts[1].trim();
                    
                    p.style.cursor = 'pointer';
                    p.title = 'Klik untuk menyalin nomor rekening';
                    
                    p.addEventListener('click', function() {
                        copyBankAccount(bank, account);
                    });
                }
            }
        });
    });

    // Add form progress indicator
    function updateFormProgress() {
        const requiredFields = document.querySelectorAll('[required]');
        const filledFields = Array.from(requiredFields).filter(field => field.value.trim() !== '');
        const progress = (filledFields.length / requiredFields.length) * 100;
        
        // Update progress bar if exists
        const progressBar = document.getElementById('formProgress');
        if (progressBar) {
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
        }
    }

    // Monitor form completion
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('checkoutForm');
        form.addEventListener('input', updateFormProgress);
        form.addEventListener('change', updateFormProgress);
        updateFormProgress(); // Initial check
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + Enter to submit form
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('checkoutForm').dispatchEvent(new Event('submit'));
        }
        
        // Escape to clear promo code
        if (e.key === 'Escape') {
            const promoInput = document.getElementById('promo_code');
            if (document.activeElement === promoInput) {
                promoInput.value = '';
                document.getElementById('promo_message').innerHTML = '';
            }
        }
    });

    console.log('🛒 Checkout page loaded successfully!');
    console.log('📦 Total items:', <?php echo count($cart_items); ?>);
    console.log('⚖️ Total weight:', <?php echo $total_weight; ?>, 'kg');
    console.log('💰 Total amount:', 'Rp', <?php echo $total; ?>);
</script>

<?php include 'includes/footer.php'; ?>
