<?php
session_start();

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login dengan pesan
session_start();
$_SESSION['success'] = 'Anda telah berhasil logout!';
header('Location: login.php');
exit();
?>