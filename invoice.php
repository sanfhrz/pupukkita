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
    SELECT o.*, u.nama as customer_name, u.email as customer_email, u.no_hp as customer_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Get order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.nama_produk, p.kategori
    FROM order_items oi
    JOIN produk p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$items_stmt->execute([$order['id']]);
$order_items = $items_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_number; ?> - Sahabat Tani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .container { max-width: none; }
        }
        
        .invoice-header {
            border-bottom: 3px solid #28a745;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        
        .company-info {
            text-align: right;
        }
        
        .invoice-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .table th {
            background: #28a745;
            color: white;
            border: none;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Print Button -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Cetak Invoice
            </button>
            <a href="order_detail.php?order=<?php echo $order_number; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h1 class="text-success fw-bold">SAHABAT TANI</h1>
                    <p class="mb-1">Jl. Raya Pertanian No. 123</p>
                    <p class="mb-1">Jakarta Selatan, 12345</p>
                    <p class="mb-1">Telp: (021) 1234-5678</p>
                    <p class="mb-0">Email: info@sahabattani.com</p>
                </div>
                <div class="col-md-6 company-info">
                    <h2 class="text-success">INVOICE</h2>
                    <h4>#<?php echo $order_number; ?></h4>
                    <p class="mb-1">Tanggal: <?php echo date('d F Y', strtotime($order['created_at'])); ?></p>
                    <p class="mb-0">Jatuh Tempo: <?php echo date('d F Y', strtotime($order['created_at'] . ' +7 days')); ?></p>
                </div>
            </div>
        </div>

        <!-- Customer & Order Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Tagihan Kepada:</h5>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Kirim Ke:</h5>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong></p>
                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="45%">Produk</th>
                        <th width="15%">Harga</th>
                        <th width="10%">Qty</th>
                        <th width="25%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                                                        <strong><?php echo htmlspecialchars($item['nama_produk']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($item['kategori']); ?></small>
                        </td>
                        <td><?php echo format_rupiah($item['price']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo format_rupiah($item['price'] * $item['quantity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Total Section -->
        <div class="row">
            <div class="col-md-6">
                <!-- Payment Info -->
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Informasi Pembayaran:</h5>
                    <p class="mb-1"><strong>Metode:</strong> 
                        <?php echo $order['payment_method'] === 'bank_transfer' ? 'Transfer Bank' : 'COD'; ?>
                    </p>
                    <p class="mb-1"><strong>Status:</strong> 
                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                            <?php echo $order['payment_status'] === 'paid' ? 'Lunas' : 'Belum Lunas'; ?>
                        </span>
                    </p>
                    <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                    <hr>
                    <small class="text-muted">
                        <strong>Rekening Tujuan:</strong><br>
                        BCA: 1234567890<br>
                        Mandiri: 0987654321<br>
                        A.n: Sahabat Tani
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="total-section">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end"><strong><?php echo format_rupiah($order['subtotal']); ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Ongkos Kirim:</strong></td>
                            <td class="text-end"><strong><?php echo format_rupiah($order['shipping_cost']); ?></strong></td>
                        </tr>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <tr>
                            <td><strong>Diskon:</strong></td>
                            <td class="text-end text-danger"><strong>-<?php echo format_rupiah($order['discount_amount']); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="border-top">
                            <td><h5><strong>TOTAL:</strong></h5></td>
                            <td class="text-end"><h5 class="text-success"><strong><?php echo format_rupiah($order['total']); ?></strong></h5></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shipping Info -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Informasi Pengiriman:</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Kurir:</strong><br>
                            <?php echo strtoupper($order['shipping_courier']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Layanan:</strong><br>
                            <?php echo htmlspecialchars($order['shipping_service']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Estimasi:</strong><br>
                            <?php echo $order['shipping_etd']; ?> hari
                        </div>
                        <div class="col-md-3">
                            <strong>No. Resi:</strong><br>
                            <?php echo $order['tracking_number'] ?: 'Belum tersedia'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($order['customer_notes']): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="invoice-details">
                    <h5 class="fw-bold mb-3">Catatan:</h5>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Terms & Conditions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="invoice-details">
                    <h6 class="fw-bold mb-2">Syarat & Ketentuan:</h6>
                    <ul class="small mb-0">
                        <li>Pembayaran dilakukan maksimal 3x24 jam setelah pemesanan</li>
                        <li>Barang yang sudah dibeli tidak dapat dikembalikan kecuali ada kerusakan</li>
                        <li>Komplain dapat diajukan maksimal 3 hari setelah barang diterima</li>
                        <li>Harga sudah termasuk PPN</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="row">
                <div class="col-md-6">
                    <div class="text-center">
                        <p class="mb-5">Penerima</p>
                        <div style="border-bottom: 1px solid #000; width: 200px; margin: 0 auto;"></div>
                        <p class="mt-2 mb-0">Tanggal: ___________</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-center">
                        <p class="mb-5">Hormat Kami</p>
                        <div style="border-bottom: 1px solid #000; width: 200px; margin: 0 auto;"></div>
                        <p class="mt-2 mb-0"><strong>Sahabat Tani</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted small mb-0">
                Terima kasih atas kepercayaan Anda berbelanja di Sahabat Tani<br>
                Website: www.sahabattani.com | Email: info@sahabattani.com | Telp: (021) 1234-5678
            </p>
        </div>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
        
        // Print function
        function printInvoice() {
            window.print();
        }
    </script>
</body>
</html>
