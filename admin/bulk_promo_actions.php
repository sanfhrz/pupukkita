<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'bulk_activate':
            if (isset($_POST['selected_promos']) && is_array($_POST['selected_promos'])) {
                $promo_ids = array_map('intval', $_POST['selected_promos']);
                $placeholders = str_repeat('?,', count($promo_ids) - 1) . '?';

                try {
                    $stmt = $pdo->prepare("UPDATE promo_codes SET is_active = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($promo_ids);

                    $message = count($promo_ids) . ' promo berhasil diaktifkan!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
            break;

        case 'bulk_deactivate':
            if (isset($_POST['selected_promos']) && is_array($_POST['selected_promos'])) {
                $promo_ids = array_map('intval', $_POST['selected_promos']);
                $placeholders = str_repeat('?,', count($promo_ids) - 1) . '?';

                try {
                    $stmt = $pdo->prepare("UPDATE promo_codes SET is_active = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($promo_ids);

                    $message = count($promo_ids) . ' promo berhasil dinonaktifkan!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
            break;
        case 'bulk_delete':
            if (isset($_POST['selected_promos']) && is_array($_POST['selected_promos'])) {
                $promo_ids = array_map('intval', $_POST['selected_promos']);
                $placeholders = str_repeat('?,', count($promo_ids) - 1) . '?';

                try {
                    $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE id IN ($placeholders)");
                    $stmt->execute($promo_ids);

                    $message = count($promo_ids) . ' promo berhasil dihapus!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
            break;

        case 'bulk_reset':
            if (isset($_POST['selected_promos']) && is_array($_POST['selected_promos'])) {
                $promo_ids = array_map('intval', $_POST['selected_promos']);
                $placeholders = str_repeat('?,', count($promo_ids) - 1) . '?';

                try {
                    $stmt = $pdo->prepare("UPDATE promo_codes SET used_count = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($promo_ids);

                    $message = 'Usage count ' . count($promo_ids) . ' promo berhasil direset!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
            break;

        case 'clean_expired':
            try {
                $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE end_date < CURDATE() AND end_date IS NOT NULL");
                $stmt->execute();

                $deleted_count = $stmt->rowCount();
                $message = $deleted_count . ' promo expired berhasil dihapus!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
            break;
    }
}

// Redirect back to promo codes page with message
$_SESSION['promo_message'] = $message;
$_SESSION['promo_message_type'] = $message_type;
header('Location: promo_codes.php');
exit();
