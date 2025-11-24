<?php
declare(strict_types=1);

$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
]);
session_start();

$baseUrl = '/Pharmacy-management';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isLogged = !empty($_SESSION['auth']);
$forceLogout = $method === 'GET' && ($_GET['force'] ?? '') === '1' && $isLogged;

$csrfValid = $method === 'POST' && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '');
if (!$csrfValid && !$forceLogout) {
    header('Location: ' . $baseUrl . '/login.php');
    exit;
}

function load_tokens(?string $tokens): array
{
    if ($tokens === null || trim($tokens) === '') {
        return [];
    }
    $decoded = json_decode($tokens, true);
    return is_array($decoded) ? $decoded : [];
}

function save_tokens(array $tokens): string
{
    $json = json_encode($tokens, JSON_UNESCAPED_UNICODE);
    return $json === false ? '[]' : $json;
}

$pdo = null;
if (is_file(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
    if (function_exists('pdo')) {
        $pdo = pdo();
    }
}

if (!$pdo) {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

if ($isLogged) {
    $userId = (int)($_SESSION['auth']['id'] ?? 0);
    if ($userId > 0) {
        $stmt = $pdo->prepare('SELECT tokens FROM auth WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $tokens = load_tokens($row['tokens'] ?? null);
        if (isset($tokens['remember'])) {
            unset($tokens['remember']);
            $update = $pdo->prepare('UPDATE auth SET tokens = ? WHERE id = ?');
            $update->execute([save_tokens($tokens), $userId]);
        }
    }
}

setcookie('remember_selector', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
]);
setcookie('remember_validator', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
]);

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
]);
session_start();
$_SESSION['csrf'] = bin2hex(random_bytes(32));

$redirect = $_POST['r'] ?? $_GET['r'] ?? '';
if (!is_string($redirect) || !str_starts_with($redirect, '/')) {
    $redirect = $baseUrl . '/login.php?logged_out=1';
}

header('Location: ' . $redirect);
exit;
