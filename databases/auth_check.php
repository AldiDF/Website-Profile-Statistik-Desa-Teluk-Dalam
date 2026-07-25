<?php
// auth_check.php
require_once __DIR__ . '/session_config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Auto-logout setelah 30 menit tidak aktif
$timeout = 1800;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
$_SESSION['login_time'] = time();
?>