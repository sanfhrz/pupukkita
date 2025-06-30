<?php
// Check if admin is logged in
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header('Location: login.php');
        exit();
    }
}

// Check if user is logged in (any role)
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
}
?>