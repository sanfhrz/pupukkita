<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // Get order details with user info
    $stmt = $pdo->prepare("SELECT o.*, u.nama, u.email, u.no_hp, u.alamat, 
                       CASE WHEN o.total_amount > 0 THEN o.total_amount ELSE o.total END as display_total 
                       FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Get order items
    // Get order items
    $stmt = $pdo->prepare("
    SELECT oi.*, p.nama_produk as product_name, p.gambar 
    FROM order_items oi 
    JOIN produk p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
    ");

    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    // Generate HTML
    ob_start();
?>

    <div class="row">
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Informasi Pesanan</h6>
            <table class="table table-borderless">
                <tr>
                    <td width="40%">ID Pesanan:</td>
                    <td><strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                </tr>
                <tr>
                    <td>Tanggal:</td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        switch ($order['status']) {
                            case 'pending':
                                $status_class = 'bg-warning';
                                $status_text = 'Pending';
                                break;
                            case 'confirmed':
                                $status_class = 'bg-info';
                                $status_text = 'Dikonfirmasi';
                                break;
                            case 'shipped':
                                $status_class = 'bg-primary';
                                $status_text = 'Dikirim';
                                break;
                            case 'delivered':
                                $status_class = 'bg-success';
                                $status_text = 'Selesai';
                                break;
                            case 'cancelled':
                                $status_class = 'bg-danger';
                                $status_text = 'Dibatalkan';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </td>
                </tr>
                <tr>
                    <td>Total:</td>
                    <td><strong class="text-success">Rp <?php echo number_format($order['display_total'], 0, ',', '.'); ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Informasi Pelanggan</h6>
            <table class="table table-borderless">
                <tr>
                    <td width="40%">Nama:</td>
                    <td><?php echo htmlspecialchars($order['nama']); ?></td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                </tr>
                <tr>
                    <td>No. HP:</td>
                    <td><?php echo htmlspecialchars($order['no_hp'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>Alamat:</td>
                    <td><?php echo htmlspecialchars($order['alamat'] ?? '-'); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <hr>

    <h6 class="fw-bold mb-3"></h6>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($item['gambar']): ?>
                                    <img src="assets/img/pupuk/?php echo htmlspecialchars($item['gambar']); ?>"
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                        class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                </div>
                            </div>
                        </td>
                        <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><strong>Rp <?php echo number_format($item['harga'] * $item['quantity'], 0, ',', '.'); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">Total:</th>
                    <th class="text-success">Rp <?php echo number_format($order['total_amount'] > 0 ? $order['total_amount'] : $order['total'], 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if (isset($order['admin_notes']) && $order['admin_notes']): ?>
        <hr>
        <h6 class="fw-bold mb-3">Catatan Admin</h6>
        <div class="alert alert-info">
            <i class="fas fa-sticky-note me-2"></i>
            <?php echo nl2br(htmlspecialchars($order['admin_notes'])); ?>
        </div>
    <?php endif; ?>

<?php
    $html = ob_get_clean();

    // Update total_amount if it's 0
if ($order['total_amount'] == 0 && $order['total'] > 0) {
    $update_stmt = $pdo->prepare("UPDATE orders SET total_amount = total WHERE id = ?");
    $update_stmt->execute([$order_id]);
    $order['total_amount'] = $order['total'];
}

echo json_encode([
    'success' => true,
    'html' => $html
]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>