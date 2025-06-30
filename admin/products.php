<?php
    session_start();
    require_once '../includes/config.php';

    // Check if admin is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        session_destroy();
        header('Location: ../login.php?error=unauthorized');
        exit();
    }

    $message = '';
    $message_type = '';

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_product':
                    $nama_produk = trim($_POST['nama_produk']);
                    $brand = trim($_POST['brand']);
                    $kategori_id = !empty($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : null;
                    $jenis_pupuk = $_POST['jenis_pupuk'];
                    $berat = trim($_POST['berat']);
                    $harga = (float)$_POST['harga'];
                    $stok = (int)$_POST['stok'];
                    $status = $_POST['status'];
                    $deskripsi = trim($_POST['deskripsi']);
                    $manfaat = trim($_POST['manfaat']);
                    $cara_pakai = trim($_POST['cara_pakai']);
                    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

                    // Handle image upload
                    $gambar = null;
                    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                        $upload_dir = '../assets/img/pupuk/';

                        // Create directory if not exists
                        if (!file_exists($upload_dir)) {
                            if (!mkdir($upload_dir, 0755, true)) {
                                $message = 'Gagal membuat direktori upload!';
                                $message_type = 'danger';
                                break;
                            }
                        }

                        // Validate file size (5MB max)
                        if ($_FILES['gambar']['size'] > 5000000) {
                            $message = 'Ukuran file terlalu besar! Maksimal 5MB';
                            $message_type = 'danger';
                            break;
                        }

                        // Validate file type
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!in_array($_FILES['gambar']['type'], $allowed_types)) {
                            $message = 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF';
                            $message_type = 'danger';
                            break;
                        }

                        $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                        $gambar = time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $gambar;

                        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                            $message = 'Gagal mengupload gambar!';
                            $message_type = 'danger';
                            break;
                        }
                    }

                    try {
                        $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, brand, kategori_id, jenis_pupuk, berat, harga, stok, status, gambar, deskripsi, manfaat, cara_pakai, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nama_produk, $brand, $kategori_id, $jenis_pupuk, $berat, $harga, $stok, $status, $gambar, $deskripsi, $manfaat, $cara_pakai, $is_featured]);

                        $message = 'Produk berhasil ditambahkan!';
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;

                case 'update_product':
                    $id = (int)$_POST['id'];
                    $nama_produk = trim($_POST['nama_produk']);
                    $brand = trim($_POST['brand']);
                    $kategori_id = !empty($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : null;
                    $jenis_pupuk = $_POST['jenis_pupuk'];
                    $berat = trim($_POST['berat']);
                    $harga = (float)$_POST['harga'];
                    $stok = (int)$_POST['stok'];
                    $status = $_POST['status'];
                    $deskripsi = trim($_POST['deskripsi']);
                    $manfaat = trim($_POST['manfaat']);
                    $cara_pakai = trim($_POST['cara_pakai']);
                    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

                    // Handle image upload
                    $gambar = null;
                    $update_image = false;

                    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                        $upload_dir = '../assets/img/pupuk/';

                        // Validate file size (5MB max)
                        if ($_FILES['gambar']['size'] > 5000000) {
                            $message = 'Ukuran file terlalu besar! Maksimal 5MB';
                            $message_type = 'danger';
                            break;
                        }

                        // Validate file type
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!in_array($_FILES['gambar']['type'], $allowed_types)) {
                            $message = 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF';
                            $message_type = 'danger';
                            break;
                        }

                        // Get old image to delete
                        $old_stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id = ?");
                        $old_stmt->execute([$id]);
                        $old_product = $old_stmt->fetch();

                        $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                        $gambar = time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $gambar;

                        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                            $update_image = true;

                            // Delete old image
                            if ($old_product && $old_product['gambar'] && file_exists($upload_dir . $old_product['gambar'])) {
                                unlink($upload_dir . $old_product['gambar']);
                            }
                        } else {
                            $message = 'Gagal mengupload gambar!';
                            $message_type = 'danger';
                            break;
                        }
                    }

                    try {
                        if ($update_image) {
                            $stmt = $pdo->prepare("UPDATE produk SET nama_produk = ?, brand = ?, kategori_id = ?, jenis_pupuk = ?, berat = ?, harga = ?, stok = ?, status = ?, gambar = ?, deskripsi = ?, manfaat = ?, cara_pakai = ?, is_featured = ? WHERE id = ?");
                            $stmt->execute([$nama_produk, $brand, $kategori_id, $jenis_pupuk, $berat, $harga, $stok, $status, $gambar, $deskripsi, $manfaat, $cara_pakai, $is_featured, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE produk SET nama_produk = ?, brand = ?, kategori_id = ?, jenis_pupuk = ?, berat = ?, harga = ?, stok = ?, status = ?, deskripsi = ?, manfaat = ?, cara_pakai = ?, is_featured = ? WHERE id = ?");
                            $stmt->execute([$nama_produk, $brand, $kategori_id, $jenis_pupuk, $berat, $harga, $stok, $status, $deskripsi, $manfaat, $cara_pakai, $is_featured, $id]);
                        }

                        $message = 'Produk berhasil diupdate!';
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;

                case 'delete_product':
                    $id = (int)$_POST['id'];

                    try {
                        // Get image to delete
                        $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id = ?");
                        $stmt->execute([$id]);
                        $product = $stmt->fetch();

                        // Delete product
                        $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
                        $stmt->execute([$id]);

                        // Delete image file
                        if ($product && $product['gambar']) {
                            $image_path = '../assets/img/pupuk/' . $product['gambar'];
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }

                        $message = 'Produk berhasil dihapus!';
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;

                case 'toggle_status':
                    $id = (int)$_POST['id'];
                    $current_status = $_POST['current_status'];
                    $new_status = $current_status === 'active' ? 'inactive' : 'active';

                    try {
                        $stmt = $pdo->prepare("UPDATE produk SET status = ? WHERE id = ?");
                        $stmt->execute([$new_status, $id]);

                        $message = "Status produk berhasil diubah menjadi $new_status!";
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                    break;

                case 'bulk_action':
                    $action_type = $_POST['bulk_action_type'];
                    $selected_products = $_POST['selected_products'] ?? [];

                    if (empty($selected_products)) {
                        $message = 'Pilih minimal satu produk!';
                        $message_type = 'warning';
                    } else {
                        try {
                            $placeholders = str_repeat('?,', count($selected_products) - 1) . '?';

                            switch ($action_type) {
                                case 'activate':
                                    $stmt = $pdo->prepare("UPDATE produk SET status = 'active' WHERE id IN ($placeholders)");
                                    $stmt->execute($selected_products);
                                    $message = count($selected_products) . ' produk berhasil diaktifkan!';
                                    break;

                                case 'deactivate':
                                    $stmt = $pdo->prepare("UPDATE produk SET status = 'inactive' WHERE id IN ($placeholders)");
                                    $stmt->execute($selected_products);
                                    $message = count($selected_products) . ' produk berhasil dinonaktifkan!';
                                    break;

                                case 'feature':
                                    $stmt = $pdo->prepare("UPDATE produk SET is_featured = 1 WHERE id IN ($placeholders)");
                                    $stmt->execute($selected_products);
                                    $message = count($selected_products) . ' produk berhasil dijadikan featured!';
                                    break;

                                case 'unfeature':
                                    $stmt = $pdo->prepare("UPDATE produk SET is_featured = 0 WHERE id IN ($placeholders)");
                                    $stmt->execute($selected_products);
                                    $message = count($selected_products) . ' produk berhasil dihapus dari featured!';
                                    break;

                                case 'delete':
                                    // Get images to delete
                                    $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id IN ($placeholders)");
                                    $stmt->execute($selected_products);
                                    $images = $stmt->fetchAll();

                                    // Delete products
                                    $stmt = $pdo->prepare("DELETE FROM produk WHERE id IN ($placeholders)");
                                    $stmt->execute($selected_products);

                                    // Delete image files
                                    foreach ($images as $img) {
                                        if ($img['gambar']) {
                                            $image_path = '../assets/img/pupuk/' . $img['gambar'];
                                            if (file_exists($image_path)) {
                                                unlink($image_path);
                                            }
                                        }
                                    }

                                    $message = count($selected_products) . ' produk berhasil dihapus!';
                                    break;
                            }

                            $message_type = 'success';
                        } catch (PDOException $e) {
                            $message = 'Error: ' . $e->getMessage();
                            $message_type = 'danger';
                        }
                    }
                    break;
            }
        }
    }

    // Get filters
    $search = $_GET['search'] ?? '';
    $kategori_filter = $_GET['kategori'] ?? '';
    $jenis_filter = $_GET['jenis'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $featured_filter = $_GET['featured'] ?? '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';
    $page = (int)($_GET['page'] ?? 1);
    $stok_filter = $_GET['stok_filter'] ?? '';
    $limit = 12;
    $offset = ($page - 1) * $limit;

    // Build query
    $sql = "SELECT p.*, COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori 
            FROM produk p 
            LEFT JOIN kategori k ON p.kategori_id = k.id 
            WHERE 1=1";
    $count_sql = "SELECT COUNT(*) as total FROM produk p WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (p.nama_produk LIKE ? OR p.brand LIKE ? OR p.deskripsi LIKE ?)";
        $count_sql .= " AND (p.nama_produk LIKE ? OR p.brand LIKE ? OR p.deskripsi LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if ($kategori_filter) {
        $sql .= " AND p.kategori_id = ?";
        $count_sql .= " AND p.kategori_id = ?";
        $params[] = $kategori_filter;
    }

    if ($jenis_filter) {
        $sql .= " AND p.jenis_pupuk = ?";
        $count_sql .= " AND p.jenis_pupuk = ?";
        $params[] = $jenis_filter;
    }

    if ($status_filter) {
        $sql .= " AND p.status = ?";
        $count_sql .= " AND p.status = ?";
        $params[] = $status_filter;
    }

    if ($stok_filter) {
        switch ($stok_filter) {
            case 'available':
                $sql .= " AND stok > 0";
                $count_sql .= " AND stok > 0";
                break;
            case 'low':
                $sql .= " AND stok > 0 AND stok < 10";
                $count_sql .= " AND stok > 0 AND stok < 10";
                break;
            case 'empty':
                $sql .= " AND stok = 0";
                $count_sql .= " AND stok = 0";
                break;
        }
    }

    if ($featured_filter !== '') {
        $sql .= " AND p.is_featured = ?";
        $count_sql .= " AND p.is_featured = ?";
        $params[] = $featured_filter;
    }

    // Get total count
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_products / $limit);

    // Get products with pagination
    $sql .= " ORDER BY p.$sort $order LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Get categories for filter and form
    $categories = [];
    try {
        $stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {
        $categories = [];
    }

    // Get product statistics
    $stats = [
        'total_products' => 0,
        'active_products' => 0,
        'inactive_products' => 0,
        'featured_products' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0,
        'total_value' => 0,
        'avg_price' => 0
    ];

    try {
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM produk");
        $stats['total_products'] = $stmt->fetch()['count'];

        // Active/Inactive products
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM produk GROUP BY status");
        $status_data = $stmt->fetchAll();
        foreach ($status_data as $status) {
            if ($status['status'] === 'active') {
                $stats['active_products'] = $status['count'];
            } else {
                $stats['inactive_products'] = $status['count'];
            }
        }

        // Featured products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM produk WHERE is_featured = 1");
        $stats['featured_products'] = $stmt->fetch()['count'];

        // Low stock (< 10)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM produk WHERE stok < 10 AND stok > 0");
        $stats['low_stock'] = $stmt->fetch()['count'];

        // Out of stock
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM produk WHERE stok = 0");
        $stats['out_of_stock'] = $stmt->fetch()['count'];

        // Total inventory value
        $stmt = $pdo->query("SELECT SUM(harga * stok) as total FROM produk WHERE status = 'active'");
        $result = $stmt->fetch();
        $stats['total_value'] = $result['total'] ?? 0;

        // Average price
        $stmt = $pdo->query("SELECT AVG(harga) as avg FROM produk WHERE status = 'active'");
        $result = $stmt->fetch();
        $stats['avg_price'] = $result['avg'] ?? 0;
    } catch (PDOException $e) {
        // Keep default values
    }

    // Get top selling products (if orders table exists)
    $top_products = [];
    try {
        $stmt = $pdo->query("
            SELECT 
                p.id,
                p.nama_produk,
                p.gambar,
                p.harga,
                COUNT(oi.id) as total_sold,
                SUM(oi.quantity * oi.harga) as total_revenue
            FROM produk p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed'
            GROUP BY p.id, p.nama_produk, p.gambar, p.harga
            ORDER BY total_sold DESC, total_revenue DESC
            LIMIT 5
        ");
        $top_products = $stmt->fetchAll();
    } catch (PDOException $e) {
        $top_products = [];
    }
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Sahabat Tani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8f9fa;
            color: #2c3e50;
        }

        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
            border-radius: 8px;
            margin: 0 10px;
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        .top-bar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stats-change {
            font-size: 0.8rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .product-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }

        .product-img-placeholder {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            border-radius: 8px;
            margin: 0 2px;
            border: none;
            color: #28a745;
        }

        .page-link:hover,
        .page-item.active .page-link {
            background: #28a745;
            color: white;
        }

        .bulk-actions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }

        .bulk-actions.show {
            display: block;
        }

        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-number {
                font-size: 2rem;
            }

            .top-bar {
                flex-direction: column;
                text-align: center;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #28a745;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #20c997;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .price-tag {
            font-size: 1.25rem;
            font-weight: 700;
            color: #28a745;
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }

        .featured-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }

        .action-buttons {
            opacity: 0;
            transition: all 0.3s ease;
        }

        .product-card:hover .action-buttons {
            opacity: 1;
        }

        .top-products-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .top-products-item:last-child {
            border-bottom: none;
        }

        .top-products-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
        }

        .top-products-content {
            flex: 1;
            min-width: 0;
        }

        .drag-drop-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .drag-drop-area:hover,
        .drag-drop-area.dragover {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-leaf me-2"></i>
                Sahabat Tani
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Kelola Pesanan
                </a>
            </div>
            <div class="nav-item">
                <a href="products.php" class="nav-link active">
                    <i class="fas fa-box"></i>
                    Kelola Produk
                </a>
            </div>
            <div class="nav-item">
                <a href="promo_codes.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    Kode Promo
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Laporan
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    Data User
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar animate-fade-in">
            <div>
                <h4 class="mb-0">📦 Kelola Produk</h4>
                <small class="text-muted">Kelola produk pupuk dan inventori</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm" onclick="exportProducts()">
                    <i class="fas fa-download me-1"></i> Export
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-1"></i> Tambah Produk
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show animate-fade-in" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in">
                    <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_products']); ?></div>
                    <div class="stats-label">Total Produk</div>
                    <div class="stats-change">
                        <span class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            <?php echo number_format($stats['active_products']); ?> aktif
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['featured_products']); ?></div>
                    <div class="stats-label">Produk Unggulan</div>
                    <div class="stats-change">
                        <span class="text-warning">
                            <i class="fas fa-percentage me-1"></i>
                            <?php echo $stats['total_products'] > 0 ? round(($stats['featured_products'] / $stats['total_products']) * 100, 1) : 0; ?>% dari total
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['low_stock'] + $stats['out_of_stock']); ?></div>
                    <div class="stats-label">Stok Menipis</div>
                    <div class="stats-change">
                        <span class="text-danger">
                            <i class="fas fa-times-circle me-1"></i>
                            <?php echo number_format($stats['out_of_stock']); ?> habis
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stats-card animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stats-number">Rp <?php echo number_format($stats['total_value'] / 1000000, 1); ?>M</div>
                    <div class="stats-label">Nilai Inventori</div>
                    <div class="stats-change">
                        <span class="text-info">
                            <i class="fas fa-chart-line me-1"></i>
                            Avg: Rp <?php echo number_format($stats['avg_price'], 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products & Quick Stats -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="card animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <i class="fas fa-trophy me-2 text-warning"></i>
                        Top Produk Terlaris
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($top_products)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Belum ada data penjualan</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_products as $index => $product): ?>
                                <div class="top-products-item">
                                    <div class="badge bg-primary me-3">#<?php echo $index + 1; ?></div>
                                    <?php if ($product['gambar'] && file_exists('../assets/img/pupuk/' . $product['gambar'])): ?>
                                        <img src="../assets/img/pupuk/<?php echo $product['gambar']; ?>"
                                            alt="<?php echo htmlspecialchars($product['nama_produk']); ?>"
                                            class="top-products-img">
                                    <?php else: ?>
                                        <div class="top-products-img bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="top-products-content">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($product['nama_produk']); ?></div>
                                        <small class="text-muted">
                                            <?php echo $product['total_sold']; ?> terjual •
                                            Rp <?php echo number_format($product['total_revenue'], 0, ',', '.'); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card animate-fade-in" style="animation-delay: 0.5s;">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2 text-info"></i>
                        Ringkasan Stok
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success">Stok Aman</span>
                                <span class="fw-bold"><?php echo $stats['total_products'] - $stats['low_stock'] - $stats['out_of_stock']; ?></span>
                            </div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $stats['total_products'] > 0 ? (($stats['total_products'] - $stats['low_stock'] - $stats['out_of_stock']) / $stats['total_products']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-warning">Stok Menipis</span>
                                <span class="fw-bold"><?php echo $stats['low_stock']; ?></span>
                            </div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: <?php echo $stats['total_products'] > 0 ? ($stats['low_stock'] / $stats['total_products']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-danger">Stok Habis</span>
                                <span class="fw-bold"><?php echo $stats['out_of_stock']; ?></span>
                            </div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo $stats['total_products'] > 0 ? ($stats['out_of_stock'] / $stats['total_products']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <form method="POST" onsubmit="return confirmBulkAction()">
                <input type="hidden" name="action" value="bulk_action">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Pilih Aksi:</label>
                        <select class="form-select" name="bulk_action_type" required>
                            <option value="">Pilih aksi...</option>
                            <option value="activate">Aktifkan</option>
                            <option value="deactivate">Nonaktifkan</option>
                            <option value="feature">Jadikan Unggulan</option>
                            <option value="unfeature">Hapus dari Unggulan</option>
                            <option value="delete">Hapus</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAllProducts()">
                            <label class="form-check-label" for="selectAll">
                                Pilih semua produk yang ditampilkan
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        ="submit" class="btn btn-warning w-100">
                        <i class="fas fa-bolt me-1"></i> Jalankan Aksi
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="card animate-fade-in" style="animation-delay: 0.6s;">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <i class="fas fa-filter me-2"></i>
                        Filter & Pencarian
                        <small class="text-muted ms-2">
                            (<?php echo count($products); ?> dari <?php echo $total_products; ?> produk)
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="toggleBulkActions()">
                            <i class="fas fa-tasks me-1"></i> Bulk Actions
                        </button>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-sort me-1"></i> Tanggal
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'nama_produk', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-sort-alpha-down me-1"></i> Nama
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'harga', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-dollar-sign me-1"></i> Harga
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filter Form -->
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">🔍</span>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nama produk, brand...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="kategori">
                            <option value="">📂 Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $kategori_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="jenis">
                            <option value="">🌱 Semua Jenis</option>
                            <option value="organik" <?php echo $jenis_filter === 'organik' ? 'selected' : ''; ?>>Organik</option>
                            <option value="anorganik" <?php echo $jenis_filter === 'anorganik' ? 'selected' : ''; ?>>Anorganik</option>
                            <option value="hayati" <?php echo $jenis_filter === 'hayati' ? 'selected' : ''; ?>>Hayati</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">📊 Semua Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="stok_filter">
                            <option value="">📦 Semua Stok</option>
                            <option value="available" <?php echo ($_GET['stok_filter'] ?? '') === 'available' ? 'selected' : ''; ?>>Tersedia (>0)</option>
                            <option value="low" <?php echo ($_GET['stok_filter'] ?? '') === 'low' ? 'selected' : ''; ?>>Stok Rendah (<10)< /option>
                            <option value="empty" <?php echo ($_GET['stok_filter'] ?? '') === 'empty' ? 'selected' : ''; ?>>Stok Habis (0)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="?" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-refresh"></i>
                            </a>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="mt-4">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada produk ditemukan</h5>
                    <p class="text-muted">Coba ubah filter pencarian atau tambah produk baru</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus me-1"></i> Tambah Produk Pertama
                    </button>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="card product-card animate-fade-in" style="animation-delay: <?php echo rand(1, 10) * 0.1; ?>s;">
                            <div class="position-relative">
                                <!-- Featured Badge -->
                                <?php if ($product['is_featured']): ?>
                                    <span class="badge bg-warning featured-badge">
                                        <i class="fas fa-star me-1"></i> Unggulan
                                    </span>
                                <?php endif; ?>

                                <!-- Stock Badge -->
                                <?php if ($product['stok'] == 0): ?>
                                    <span class="badge bg-danger stock-badge">Habis</span>
                                <?php elseif ($product['stok'] < 10): ?>
                                    <span class="badge bg-warning stock-badge">Stok: <?php echo $product['stok']; ?></span>
                                <?php endif; ?>

                                <!-- Product Image -->
                                <?php if ($product['gambar'] && file_exists('../assets/img/pupuk/' . $product['gambar'])): ?>
                                    <img src="../assets/img/pupuk/<?php echo $product['gambar']; ?>"
                                        alt="<?php echo htmlspecialchars($product['nama_produk']); ?>"
                                        class="product-img"
                                        onclick="viewProductImage('<?php echo $product['gambar']; ?>', '<?php echo htmlspecialchars($product['nama_produk']); ?>')">
                                <?php else: ?>
                                    <div class="product-img-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Checkbox for bulk actions -->
                                <div class="form-check position-absolute" style="top: 10px; right: 40px;">
                                    <input class="form-check-input product-checkbox" type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>">
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0 fw-bold"><?php echo htmlspecialchars($product['nama_produk']); ?></h6>
                                    <span class="badge <?php echo $product['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </div>

                                <p class="text-muted small mb-2">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['brand']); ?> •
                                    <i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($product['nama_kategori']); ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-info">
                                        <?php echo ucfirst($product['jenis_pupuk']); ?>
                                    </span>
                                    <small class="text-muted"><?php echo htmlspecialchars($product['berat']); ?></small>
                                </div>

                                <div class="price-tag mb-3">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Stok: <span class="fw-bold <?php echo $product['stok'] == 0 ? 'text-danger' : ($product['stok'] < 10 ? 'text-warning' : 'text-success'); ?>">
                                            <?php echo $product['stok']; ?>
                                        </span>
                                    </small>

                                    <div class="btn-group btn-group-sm" role="group">
                                        <!-- View -->
                                        <button class="btn btn-outline-primary" onclick="viewProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <!-- Edit -->
                                        <button class="btn btn-outline-warning" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Toggle Status -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $product['status']; ?>">
                                            <button type="submit" class="btn btn-outline-<?php echo $product['status'] === 'active' ? 'success' : 'secondary'; ?>"
                                                onclick="return confirm('Yakin ingin mengubah status produk ini?')" title="Toggle Status">
                                                <i class="fas fa-<?php echo $product['status'] === 'active' ? 'check' : 'times'; ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus produk ini? Data tidak dapat dikembalikan!')">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Product pagination" class="mt-4">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Tambah Produk Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="addProductForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_product">

                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Produk *</label>
                                            <input type="text" class="form-control" name="nama_produk" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Brand *</label>
                                            <input type="text" class="form-control" name="brand" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select class="form-select" name="kategori_id">
                                                <option value="">Pilih Kategori</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jenis Pupuk *</label>
                                            <select class="form-select" name="jenis_pupuk" required>
                                                <option value="">Pilih Jenis</option>
                                                <option value="organik">Organik</option>
                                                <option value="anorganik">Anorganik</option>
                                                <option value="hayati">Hayati</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Berat *</label>
                                            <input type="text" class="form-control" name="berat" placeholder="contoh: 1kg, 500gr" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Harga *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" name="harga" min="0" step="100" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Stok *</label>
                                            <input type="number" class="form-control" name="stok" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status *</label>
                                            <select class="form-select" name="status" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" name="is_featured" id="addFeatured">
                                                <label class="form-check-label" for="addFeatured">
                                                    <i class="fas fa-star text-warning me-1"></i>
                                                    Jadikan Produk Unggulan
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="deskripsi" rows="3" placeholder="Deskripsi produk..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Manfaat</label>
                                    <textarea class="form-control" name="manfaat" rows="3" placeholder="Manfaat produk..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Cara Pakai</label>
                                    <textarea class="form-control" name="cara_pakai" rows="3" placeholder="Cara penggunaan..."></textarea>
                                </div>
                            </div>

                            <!-- Right Column - Image Upload -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gambar Produk</label>
                                    <div class="drag-drop-area" onclick="document.getElementById('addGambar').click()">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Klik atau drag & drop gambar</p>
                                        <small class="text-muted">JPG, PNG, GIF (Max: 5MB)</small>
                                    </div>
                                    <input type="file" class="form-control d-none" name="gambar" id="addGambar" accept="image/*" onchange="previewImage(this, 'addPreview')">
                                    <img id="addPreview" class="preview-image d-none mt-2" alt="Preview">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Produk
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editProductForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="id" id="editId">

                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Produk *</label>
                                            <input type="text" class="form-control" name="nama_produk" id="editNamaProduk" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Brand *</label>
                                            <input type="text" class="form-control" name="brand" id="editBrand" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select class="form-select" name="kategori_id" id="editKategoriId">
                                                <option value="">Pilih Kategori</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jenis Pupuk *</label>
                                            <select class="form-select" name="jenis_pupuk" id="editJenisPupuk" required>
                                                <option value="">Pilih Jenis</option>
                                                <option value="organik">Organik</option>
                                                <option value="anorganik">Anorganik</option>
                                                <option value="hayati">Hayati</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Berat *</label>
                                            <input type="text" class="form-control" name="berat" id="editBerat" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Harga *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" name="harga" id="editHarga" min="0" step="100" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Stok *</label>
                                            <input type="number" class="form-control" name="stok" id="editStok" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status *</label>
                                            <select class="form-select" name="status" id="editStatus" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" name="is_featured" id="editFeatured">
                                                <label class="form-check-label" for="editFeatured">
                                                    <i class="fas fa-star text-warning me-1"></i>
                                                    Jadikan Produk Unggulan
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Manfaat</label>
                                    <textarea class="form-control" name="manfaat" id="editManfaat" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Cara Pakai</label>
                                    <textarea class="form-control" name="cara_pakai" id="editCaraPakai" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Right Column - Image Upload -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gambar Produk</label>
                                    <div class="drag-drop-area" onclick="document.getElementById('editGambar').click()">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Klik untuk ganti gambar</p>
                                        <small class="text-muted">JPG, PNG, GIF (Max: 5MB)</small>
                                    </div>
                                    <input type="file" class="form-control d-none" name="gambar" id="editGambar" accept="image/*" onchange="previewImage(this, 'editPreview')">
                                    <img id="editPreview" class="preview-image mt-2" alt="Preview">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <div class="modal fade" id="viewProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Detail Produk
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <img id="viewProductImage" class="img-fluid rounded" alt="Product Image">
                        </div>
                        <div class="col-md-8">
                            <h4 id="viewProductName" class="mb-3"></h4>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Brand:</strong>
                                    <span id="viewProductBrand"></span>
                                </div>
                                <div class="col-6">
                                    <strong>Kategori:</strong>
                                    <span id="viewProductCategory"></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Jenis:</strong>
                                    <span id="viewProductType"></span>
                                </div>
                                <div class="col-6">
                                    <strong>Berat:</strong>
                                    <span id="viewProductWeight"></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Harga:</strong>
                                    <span id="viewProductPrice" class="text-success fw-bold"></span>
                                </div>
                                <div class="col-6">
                                    <strong>Stok:</strong>
                                    <span id="viewProductStock"></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Status:</strong>
                                    <span id="viewProductStatus"></span>
                                </div>
                                <div class="col-6">
                                    <strong>Unggulan:</strong>
                                    <span id="viewProductFeatured"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Deskripsi:</h6>
                            <p id="viewProductDescription" class="text-muted"></p>
                        </div>
                        <div class="col-md-4">
                            <h6>Manfaat:</h6>
                            <p id="viewProductBenefits" class="text-muted"></p>
                        </div>
                        <div class="col-md-4">
                            <h6>Cara Pakai:</h6>
                            <p id="viewProductUsage" class="text-muted"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-warning" onclick="editProductFromView()">
                        <i class="fas fa-edit me-1"></i> Edit Produk
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageViewTitle">Gambar Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="imageViewSrc" class="img-fluid" alt="Product Image">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentProduct = null;

        // Toggle bulk actions
        function toggleBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            bulkActions.classList.toggle('show');
        }

        // Toggle all products checkbox
        function toggleAllProducts() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.product-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Confirm bulk action
        function confirmBulkAction() {
            const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
            const actionType = document.querySelector('select[name="bulk_action_type"]').value;

            if (selectedProducts.length === 0) {
                alert('Pilih minimal satu produk!');
                return false;
            }

            let message = '';
            switch (actionType) {
                case 'activate':
                    message = `Aktifkan ${selectedProducts.length} produk?`;
                    break;
                case 'deactivate':
                    message = `Nonaktifkan ${selectedProducts.length} produk?`;
                    break;
                case 'feature':
                    message = `Jadikan ${selectedProducts.length} produk sebagai unggulan?`;
                    break;
                case 'unfeature':
                    message = `Hapus ${selectedProducts.length} produk dari unggulan?`;
                    break;
                case 'delete':
                    message = `Hapus ${selectedProducts.length} produk? Data tidak dapat dikembalikan!`;
                    break;
                default:
                    message = `Jalankan aksi pada ${selectedProducts.length} produk?`;
            }

            return confirm(message);
        }

        // Preview image
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        // View product
        function viewProduct(product) {
            currentProduct = product;

            // Set product details
            document.getElementById('viewProductName').textContent = product.nama_produk;
            document.getElementById('viewProductBrand').textContent = product.brand;
            document.getElementById('viewProductCategory').textContent = product.nama_kategori || 'Tidak ada';
            document.getElementById('viewProductType').textContent = product.jenis_pupuk;
            document.getElementById('viewProductWeight').textContent = product.berat;
            document.getElementById('viewProductPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(product.harga);
            document.getElementById('viewProductStock').textContent = product.stok;

            // Status badge
            const statusBadge = product.status === 'active' ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-secondary">Inactive</span>';
            document.getElementById('viewProductStatus').innerHTML = statusBadge;

            // Featured badge
            const featuredBadge = product.is_featured == 1 ?
                '<span class="badge bg-warning"><i class="fas fa-star me-1"></i>Ya</span>' :
                '<span class="badge bg-light text-dark">Tidak</span>';
            document.getElementById('viewProductFeatured').innerHTML = featuredBadge;

            // Descriptions
            document.getElementById('viewProductDescription').textContent = product.deskripsi || 'Tidak ada deskripsi';
            document.getElementById('viewProductBenefits').textContent = product.manfaat || 'Tidak ada manfaat';
            document.getElementById('viewProductUsage').textContent = product.cara_pakai || 'Tidak ada cara pakai';

            // Product image
            const imageElement = document.getElementById('viewProductImage');
            if (product.gambar) {
                imageElement.src = '../assets/img/pupuk/' + product.gambar;
                imageElement.alt = product.nama_produk;
            } else {
                imageElement.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
                imageElement.alt = 'No Image';
            }

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
            modal.show();
        }

        // Edit product
        function editProduct(product) {
            currentProduct = product;

            // Fill form fields
            document.getElementById('editId').value = product.id;
            document.getElementById('editNamaProduk').value = product.nama_produk;
            document.getElementById('editBrand').value = product.brand;
            document.getElementById('editKategoriId').value = product.kategori_id || '';
            document.getElementById('editJenisPupuk').value = product.jenis_pupuk;
            document.getElementById('editBerat').value = product.berat;
            document.getElementById('editHarga').value = product.harga;
            document.getElementById('editStok').value = product.stok;
            document.getElementById('editStatus').value = product.status;
            document.getElementById('editFeatured').checked = product.is_featured == 1;
            document.getElementById('editDeskripsi').value = product.deskripsi || '';
            document.getElementById('editManfaat').value = product.manfaat || '';
            document.getElementById('editCaraPakai').value = product.cara_pakai || '';

            // Set current image
            const previewElement = document.getElementById('editPreview');
            if (product.gambar) {
                previewElement.src = '../assets/img/pupuk/' + product.gambar;
                previewElement.classList.remove('d-none');
            } else {
                previewElement.classList.add('d-none');
            }

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }

        // Edit product from view modal
        function editProductFromView() {
            if (currentProduct) {
                // Close view modal
                const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewProductModal'));
                viewModal.hide();

                // Open edit modal
                setTimeout(() => {
                    editProduct(currentProduct);
                }, 300);
            }
        }

        // View product image
        function viewProductImage(imageName, productName) {
            document.getElementById('imageViewTitle').textContent = productName;
            document.getElementById('imageViewSrc').src = '../assets/img/pupuk/' + imageName;

            const modal = new bootstrap.Modal(document.getElementById('imageViewModal'));
            modal.show();
        }

        // Export products
        function exportProducts() {
            // Create export URL with current filters
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');

            // Create temporary link and click it
            const link = document.createElement('a');
            link.href = '?' + params.toString();
            link.download = 'products_export.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Drag and drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dragDropAreas = document.querySelectorAll('.drag-drop-area');

            dragDropAreas.forEach(area => {
                area.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });

                area.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                });

                area.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const fileInput = this.parentElement.querySelector('input[type="file"]');
                        fileInput.files = files;

                        // Trigger preview
                        const previewId = fileInput.id.replace('Gambar', 'Preview');
                        previewImage(fileInput, previewId);
                    }
                });
            });

            // Auto-hide alerts
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });

        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
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
                alert('Mohon lengkapi semua field yang wajib diisi!');
            }
        });

        document.getElementById('editProductForm').addEventListener('submit', function(e) {
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
                alert('Mohon lengkapi semua field yang wajib diisi!');
            }
        });

        // Format currency input
        document.querySelectorAll('input[name="harga"]').forEach(input => {
            input.addEventListener('input', function() {
                // Remove non-numeric characters except decimal point
                let value = this.value.replace(/[^\d]/g, '');

                // Format with thousand separators
                if (value) {
                    this.value = parseInt(value);
                }
            });
        });

        // Auto-suggest for brand field
        const brandSuggestions = [
            'Pupuk Kaltim', 'Petrokimia Gresik', 'Pupuk Kujang', 'Pupuk Sriwidjaja',
            'Mahkota', 'Mutiara', 'Phonska', 'NPK', 'Urea', 'TSP', 'KCL',
            'Organik Cair', 'Kompos', 'Pupuk Kandang'
        ];

        document.querySelectorAll('input[name="brand"]').forEach(input => {
            input.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                const suggestions = brandSuggestions.filter(brand =>
                    brand.toLowerCase().includes(value)
                );

                // You can implement autocomplete dropdown here
                console.log('Brand suggestions:', suggestions);
            });
        });

        // Stock warning
        document.querySelectorAll('input[name="stok"]').forEach(input => {
            input.addEventListener('change', function() {
                const stock = parseInt(this.value);
                const warning = this.parentElement.querySelector('.stock-warning');

                if (warning) warning.remove();

                if (stock < 10) {
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'stock-warning text-warning small mt-1';
                    warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Stok rendah!';
                    this.parentElement.appendChild(warningDiv);
                }
            });
        });

        // Real-time search
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Auto-submit form after 500ms of no typing
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + N = New Product
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
                modal.show();
            }

            // Escape = Close modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                });
            }
        });

        // Product card hover effects
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Lazy loading for product images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Print product list
        function printProducts() {
            const printWindow = window.open('', '_blank');
            const products = <?php echo json_encode($products); ?>;

            let printContent = `
                <html>
                <head>
                    <title>Daftar Produk - Sahabat Tani</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .date { text-align: right; margin-bottom: 10px; }
                    </style>
                </head>
                <body>
                    <div class="date">Tanggal: ${new Date().toLocaleDateString('id-ID')}</div>
                    <div class="header">
                        <h2>Daftar Produk Pupuk</h2>
                        <p>Sahabat Tani</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Produk</th>
                                <th>Brand</th>
                                <th>Jenis</th>
                                <th>Berat</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            products.forEach((product, index) => {
                printContent += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${product.nama_produk}</td>
                        <td>${product.brand}</td>
                        <td>${product.jenis_pupuk}</td>
                        <td>${product.berat}</td>
                        <td>Rp ${new Intl.NumberFormat('id-ID').format(product.harga)}</td>
                        <td>${product.stok}</td>
                        <td>${product.status}</td>
                    </tr>
                `;
            });

            printContent += `
                        </tbody>
                    </table>
                </body>
                </html>
            `;

            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        // Add print button to export options
        document.querySelector('.btn-outline-success').addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = document.createElement('div');
            dropdown.className = 'dropdown-menu show';
            dropdown.style.position = 'absolute';
            dropdown.style.top = this.offsetTop + this.offsetHeight + 'px';
            dropdown.style.left = this.offsetLeft + 'px';
            dropdown.innerHTML = `
                <a class="dropdown-item" href="#" onclick="exportProducts()">
                    <i class="fas fa-file-csv me-2"></i>Export CSV
                </a>
                <a class="dropdown-item" href="#" onclick="printProducts()">
                    <i class="fas fa-print me-2"></i>Print List
                </a>
            `;

            document.body.appendChild(dropdown);

            // Remove dropdown when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function removeDropdown() {
                    dropdown.remove();
                    document.removeEventListener('click', removeDropdown);
                });
            }, 100);
        });

        // Advanced filtering
        function applyAdvancedFilter() {
            const filters = {
                price_min: document.getElementById('priceMin')?.value || '',
                price_max: document.getElementById('priceMax')?.value || '',
                stock_min: document.getElementById('stockMin')?.value || '',
                stock_max: document.getElementById('stockMax')?.value || '',
                featured_only: document.getElementById('featuredOnly')?.checked || false
            };

            const params = new URLSearchParams(window.location.search);
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    params.set(key, filters[key]);
                } else {
                    params.delete(key);
                }
            });

            window.location.search = params.toString();
        }

        // Quick actions
        function quickToggleStatus(productId, currentStatus) {
            if (confirm('Yakin ingin mengubah status produk ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="${productId}">
                    <input type="hidden" name="current_status" value="${currentStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function quickToggleFeatured(productId, currentFeatured) {
            if (confirm('Yakin ingin mengubah status unggulan produk ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_featured">
                    <input type="hidden" name="id" value="${productId}">
                    <input type="hidden" name="current_featured" value="${currentFeatured}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`Page loaded in ${loadTime.toFixed(2)}ms`);

            // Log slow loading images
            document.querySelectorAll('img').forEach(img => {
                img.addEventListener('load', function() {
                    const imgLoadTime = performance.now();
                    if (imgLoadTime > 2000) {
                        console.warn(`Slow loading image: ${this.src}`);
                    }
                });
            });
        });
    </script>
</body>

</html>