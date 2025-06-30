<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$format = $_GET['format'] ?? 'excel';

// Get promo codes data
$sql = "SELECT * FROM promo_codes ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$promo_codes = $stmt->fetchAll();

if ($format === 'excel') {
    // Export to Excel (CSV format)
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="promo_codes_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'ID',
        'Kode Promo',
        'Deskripsi',
        'Tipe',
        'Nilai',
        'Min. Pembelian',
        'Max. Diskon',
        'Batas Penggunaan',
        'Sudah Digunakan',
        'Tanggal Mulai',
        'Tanggal Berakhir',
        'Status',
        'Dibuat'
    ]);
    
    // CSV data
    foreach ($promo_codes as $promo) {
        fputcsv($output, [
            $promo['id'],
            $promo['code'],
            $promo['description'],
            $promo['type'] === 'percentage' ? 'Persentase' : 'Nominal',
            $promo['type'] === 'percentage' ? $promo['value'] . '%' : 'Rp ' . number_format($promo['value'], 0, ',', '.'),
            $promo['min_purchase'] ? 'Rp ' . number_format($promo['min_purchase'], 0, ',', '.') : 'Tidak ada',
            $promo['max_discount'] ? 'Rp ' . number_format($promo['max_discount'], 0, ',', '.') : 'Tidak ada',
            $promo['usage_limit'] ?: 'Unlimited',
            $promo['used_count'],
            date('d/m/Y', strtotime($promo['start_date'])),
            $promo['end_date'] ? date('d/m/Y', strtotime($promo['end_date'])) : 'Tidak terbatas',
            $promo['is_active'] ? 'Aktif' : 'Nonaktif',
            date('d/m/Y H:i', strtotime($promo['created_at']))
        ]);
    }
    
    fclose($output);
    
} elseif ($format === 'pdf') {
    // Export to PDF (simple HTML to PDF)
    require_once '../vendor/autoload.php'; // If using a PDF library like TCPDF or DOMPDF
    
    // For now, we'll create a simple HTML that can be printed as PDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Laporan Kode Promo</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #28a745; margin: 0; }
            .header p { margin: 5px 0; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; font-weight: bold; }
            .text-center { text-align: center; }
            .badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; }
            .badge-success { background-color: #d4edda; color: #155724; }
            .badge-danger { background-color: #f8d7da; color: #721c24; }
            .badge-secondary { background-color: #e2e3e5; color: #383d41; }
            .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Laporan Kode Promo</h1>
            <p>Sahabat Tani - Admin Dashboard</p>
            <p>Dicetak pada: <?php echo date('d F Y, H:i'); ?> WIB</p>
            <p>Total Promo: <?php echo count($promo_codes); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Deskripsi</th>
                    <th>Tipe</th>
                    <th>Nilai</th>
                    <th>Min. Beli</th>
                    <th>Penggunaan</th>
                    <th>Periode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promo_codes as $index => $promo): ?>
                    <?php
                    $is_expired = $promo['end_date'] && strtotime($promo['end_date']) < time();
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($promo['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($promo['description']); ?></td>
                        <td><?php echo $promo['type'] === 'percentage' ? 'Persentase' : 'Nominal'; ?></td>
                        <td>
                            <?php if ($promo['type'] === 'percentage'): ?>
                                <?php echo $promo['value']; ?>%
                            <?php else: ?>
                                Rp <?php echo number_format($promo['value'], 0, ',', '.'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($promo['min_purchase']): ?>
                                Rp <?php echo number_format($promo['min_purchase'], 0, ',', '.'); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php echo $promo['used_count']; ?>
                            <?php if ($promo['usage_limit']): ?>
                                / <?php echo $promo['usage_limit']; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?>
                            <?php if ($promo['end_date']): ?>
                                <br>s/d <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_expired): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php elseif ($promo['is_active']): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Laporan ini digenerate otomatis oleh sistem Sahabat Tani</p>
            <p>© <?php echo date('Y'); ?> Sahabat Tani. All rights reserved.</p>
        </div>
        
        <script>
            // Auto print when page loads
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
}
?>