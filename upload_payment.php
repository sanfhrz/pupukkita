<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    header('Location: my_orders.php');
    exit();
}

// Get order details
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE order_number = ? AND user_id = ? AND payment_method = 'bank_transfer'
");
$stmt->execute([$order_number, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Check if order can accept payment upload
if (!in_array($order['status'], ['pending', 'payment_uploaded'])) {
    $_SESSION['error'] = 'Pesanan ini tidak dapat menerima upload bukti pembayaran';
    header('Location: order_detail.php?order=' . $order_number);
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_name = trim($_POST['bank_name']);
    $account_name = trim($_POST['account_name']);
    $transfer_amount = (float)$_POST['transfer_amount'];
    $transfer_date = $_POST['transfer_date'];
    $notes = trim($_POST['notes']);
    
    // Validate amount
    if ($transfer_amount != $order['total']) {
        $message = 'Jumlah transfer harus sama dengan total pesanan: ' . format_rupiah($order['total']);
        $message_type = 'danger';
    } else {
        // Handle file upload
        $upload_dir = 'uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['payment_proof'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_types)) {
                $message = 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF';
                $message_type = 'danger';
            } elseif ($file['size'] > $max_size) {
                $message = 'Ukuran file terlalu besar. Maksimal 5MB';
                $message_type = 'danger';
            } else {
                $filename = 'payment_' . $order['id'] . '_' . time() . '.' . $file_ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    try {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Update order
                        $update_stmt = $pdo->prepare("
                            UPDATE orders 
                            SET status = 'payment_uploaded',
                                payment_proof = ?,
                                payment_bank = ?,
                                payment_account_name = ?,
                                payment_amount = ?,
                                payment_date = ?,
                                payment_notes = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $update_stmt->execute([
                            $filename, $bank_name, $account_name, 
                            $transfer_amount, $transfer_date, $notes, $order['id']
                        ]);
                        
                        // Add tracking record
                        $tracking_stmt = $pdo->prepare("
                            INSERT INTO order_tracking (order_id, status, title, description, created_by) 
                            VALUES (?, 'payment_uploaded', 'Bukti Pembayaran Diunggah', 'Pelanggan telah mengunggah bukti pembayaran', ?)
                        ");
                        $tracking_stmt->execute([$order['id'], $user_id]);
                        
                        // Create notification for admin
                        $notif_stmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, order_id, type, title, message) 
                            SELECT id, ?, 'payment', 'Bukti Pembayaran Baru', CONCAT('Pesanan #', ?, ' - Bukti pembayaran telah diunggah') 
                            FROM users WHERE role = 'admin'
                        ");
                        $notif_stmt->execute([$order['id'], $order_number]);
                        
                        $pdo->commit();
                        
                        $message = 'Bukti pembayaran berhasil diunggah! Admin akan memverifikasi dalam 1x24 jam.';
                        $message_type = 'success';
                        
                        // Redirect after success
                        $_SESSION['success'] = $message;
                        header('Location: order_detail.php?order=' . $order_number);
                        exit();
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        unlink($filepath); // Delete uploaded file
                        $message = 'Gagal menyimpan data: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Gagal mengunggah file';
                    $message_type = 'danger';
                }
            }
        } else {
            $message = 'Pilih file bukti pembayaran';
            $message_type = 'danger';
        }
    }
}

$page_title = 'Upload Bukti Pembayaran';
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="my_orders.php">Pesanan Saya</a></li>
            <li class="breadcrumb-item"><a href="order_detail.php?order=<?php echo $order_number; ?>">Detail Pesanan</a></li>
            <li class="breadcrumb-item active">Upload Bukti Pembayaran</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-success">
                <i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran
            </h2>
            <p class="text-muted">Unggah bukti transfer untuk pesanan #<?php echo $order_number; ?></p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column - Form -->
        <div class="col-lg-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Informasi Pembayaran</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="paymentForm">
                        <!-- Bank Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Bank Tujuan *</label>
                                <select class="form-select" name="bank_name" required>
                                    <option value="">Pilih Bank</option>
                                    <option value="BCA">BCA - 1234567890</option>
                                    <option value="Mandiri">Mandiri - 0987654321</option>
                                    <option value="BRI">BRI - 5678901234</option>
                                    <option value="BNI">BNI - 4321098765</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Pengirim *</label>
                                <input type="text" class="form-control" name="account_name" 
                                       placeholder="Nama sesuai rekening pengirim" required>
                            </div>
                        </div>

                        <!-- Transfer Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Transfer *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="transfer_amount" 
                                           value="<?php echo $order['total']; ?>" readonly required>
                                </div>
                                <small class="text-muted">Jumlah harus sama persis dengan total pesanan</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Transfer *</label>
                                <input type="date" class="form-control" name="transfer_date" 
                                       max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label class="form-label">Bukti Pembayaran *</label>
                            <input type="file" class="form-control" name="payment_proof" 
                                   accept=".jpg,.jpeg,.png,.pdf" required>
                            <div class="form-text">
                                Format: JPG, PNG, atau PDF. Maksimal 5MB.
                                <br>Pastikan foto/scan jelas dan dapat dibaca.
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="Catatan tambahan jika ada..."></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="order_detail.php?order=<?php echo $order_number; ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Summary & Instructions -->
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card border-info mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Ringkasan Pesanan</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td>Nomor Pesanan:</td>
                            <td class="fw-bold">#<?php echo $order_number; ?></td>
                        </tr>
                        <tr>
                            <td>Tanggal Pesanan:</td>
                            <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <td>Subtotal:</td>
                            <td><?php echo format_rupiah($order['subtotal']); ?></td>
                        </tr>
                        <tr>
                            <td>Ongkos Kirim:</td>
                            <td><?php echo format_rupiah($order['shipping_cost']); ?></td>
                        </tr>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <tr>
                            <td>Diskon:</td>
                            <td class="text-danger">-<?php echo format_rupiah($order['discount_amount']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="border-top">
                            <td><strong>Total:</strong></td>
                                                        <td><strong class="text-success"><?php echo format_rupiah($order['total']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Bank Account Info -->
            <div class="card border-warning mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-university me-2"></i>Rekening Tujuan</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="p-2 border rounded">
                                <img src="assets/images/banks/bca.png" alt="BCA" class="mb-2" style="height: 30px;">
                                <div class="fw-bold">1234567890</div>
                                <small class="text-muted">BCA</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-2 border rounded">
                                <img src="assets/images/banks/mandiri.png" alt="Mandiri" class="mb-2" style="height: 30px;">
                                <div class="fw-bold">0987654321</div>
                                <small class="text-muted">Mandiri</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-2 border rounded">
                                <img src="assets/images/banks/bri.png" alt="BRI" class="mb-2" style="height: 30px;">
                                <div class="fw-bold">5678901234</div>
                                <small class="text-muted">BRI</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-2 border rounded">
                                <img src="assets/images/banks/bni.png" alt="BNI" class="mb-2" style="height: 30px;">
                                <div class="fw-bold">4321098765</div>
                                <small class="text-muted">BNI</small>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <strong>A.n: Sahabat Tani</strong>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Petunjuk Upload</h6>
                </div>
                <div class="card-body">
                    <ol class="small mb-0">
                        <li class="mb-2">Transfer sesuai <strong>jumlah exact</strong> ke salah satu rekening di atas</li>
                        <li class="mb-2">Ambil foto/screenshot bukti transfer yang <strong>jelas</strong></li>
                        <li class="mb-2">Pastikan terlihat:
                            <ul class="mt-1">
                                <li>Nama bank tujuan</li>
                                <li>Nomor rekening tujuan</li>
                                <li>Jumlah transfer</li>
                                <li>Tanggal & waktu</li>
                                <li>Status berhasil</li>
                            </ul>
                        </li>
                        <li class="mb-2">Upload file melalui form ini</li>
                        <li class="mb-0">Tunggu konfirmasi admin (1x24 jam)</li>
                    </ol>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="card border-success mt-4">
                <div class="card-body text-center">
                    <h6 class="fw-bold mb-3">Butuh Bantuan?</h6>
                    <div class="d-grid gap-2">
                        <a href="https://wa.me/6281234567890" class="btn btn-success btn-sm" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp Support
                        </a>
                        <a href="mailto:support@sahabattani.com" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-2"></i>Email Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 10px;
}

.form-control:focus,
.form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.btn {
    border-radius: 8px;
}

.bank-account {
    transition: all 0.3s ease;
}

.bank-account:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>

<script>
// Form validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const fileInput = document.querySelector('input[type="file"]');
    const transferAmount = document.querySelector('input[name="transfer_amount"]').value;
    const expectedAmount = <?php echo $order['total']; ?>;
    
    // Validate file
    if (fileInput.files.length === 0) {
        e.preventDefault();
        alert('Pilih file bukti pembayaran');
        return;
    }
    
    const file = fileInput.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    
    if (file.size > maxSize) {
        e.preventDefault();
        alert('Ukuran file terlalu besar. Maksimal 5MB');
        return;
    }
    
    if (!allowedTypes.includes(file.type)) {
        e.preventDefault();
        alert('Format file tidak didukung. Gunakan JPG, PNG, atau PDF');
        return;
    }
    
    // Validate amount
    if (parseFloat(transferAmount) !== expectedAmount) {
        e.preventDefault();
        alert('Jumlah transfer harus sama dengan total pesanan');
        return;
    }
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengunggah...';
    submitBtn.disabled = true;
    
    // If validation passes, form will submit normally
    // Reset button if there's an error (though this won't execute if form submits)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 10000);
});

// File preview
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Create preview if it's an image
            if (file.type.startsWith('image/')) {
                let preview = document.getElementById('filePreview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.id = 'filePreview';
                    preview.className = 'mt-3';
                    e.target.parentNode.appendChild(preview);
                }
                
                preview.innerHTML = `
                    <div class="card">
                        <div class="card-header">
                            <small class="text-muted">Preview:</small>
                        </div>
                        <div class="card-body text-center">
                            <img src="${e.target.result}" alt="Preview" class="img-fluid" style="max-height: 200px;">
                            <div class="mt-2">
                                <small class="text-muted">${file.name} (${(file.size/1024/1024).toFixed(2)} MB)</small>
                            </div>
                        </div>
                    </div>
                `;
            }
        };
        reader.readAsDataURL(file);
    }
});

// Auto-fill today's date
document.querySelector('input[name="transfer_date"]').value = new Date().toISOString().split('T')[0];

// Copy account number to clipboard
function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(function() {
        const originalText = element.innerHTML;
        element.innerHTML = '<i class="fas fa-check text-success"></i> Disalin!';
        setTimeout(() => {
            element.innerHTML = originalText;
        }, 2000);
    });
}

// Add click handlers to account numbers
document.querySelectorAll('.fw-bold').forEach(element => {
    if (element.textContent.match(/^\d+$/)) {
        element.style.cursor = 'pointer';
        element.title = 'Klik untuk menyalin';
        element.addEventListener('click', function() {
            copyToClipboard(this.textContent, this);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>