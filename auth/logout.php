<?php
// /Pharmacy-management/auth/logout.php
declare(strict_types=1);

session_start();

// Base URL
$base_url = $base_url ?? '/Pharmacy-management';

// Nếu ai đó gọi GET trực tiếp -> chuyển về login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$base_url}/login.php");
    exit;
}

// CSRF check
$sessionCsrf = $_SESSION['csrf'] ?? '';
$postCsrf     = $_POST['csrf'] ?? '';
if (!$sessionCsrf || !$postCsrf || !hash_equals($sessionCsrf, $postCsrf)) {
    http_response_code(419); // Authentication Timeout
    // Vẫn hủy session để an toàn
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header("Location: {$base_url}/login.php?err=csrf");
    exit;
}

// Hủy session đăng nhập
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Chuyển về trang đăng nhập
header("Location: {$base_url}/login.php?msg=logged_out");
exit;
