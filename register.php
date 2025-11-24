<?php
declare(strict_types=1);

$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
    'path' => '/',
]);
session_start();

const ENFORCE_STATUS = false;

function client_ip(): string
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $parts = explode(',', (string)$_SERVER[$key]);
            $ip = trim($parts[0]);
            if ($ip !== '') {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf'];
}

function check_rate_limit(string $username, string $ip): bool
{
    $maxAttempts = 5;
    $windowSeconds = 600;
    $userKey = $username !== '' ? $username : '_blank';

    if (!isset($_SESSION['login_attempts'][$userKey][$ip])) {
        $_SESSION['login_attempts'][$userKey][$ip] = [
            'count' => 0,
            'first_at' => time(),
        ];
        return false;
    }

    $entry = &$_SESSION['login_attempts'][$userKey][$ip];
    if ((time() - (int)$entry['first_at']) > $windowSeconds) {
        $entry = ['count' => 0, 'first_at' => time()];
        return false;
    }

    return (int)$entry['count'] >= $maxAttempts;
}

function register_failed_attempt(string $username, string $ip): void
{
    $userKey = $username !== '' ? $username : '_blank';
    if (!isset($_SESSION['login_attempts'][$userKey][$ip])) {
        $_SESSION['login_attempts'][$userKey][$ip] = ['count' => 0, 'first_at' => time()];
    }
    $_SESSION['login_attempts'][$userKey][$ip]['count'] = (int)$_SESSION['login_attempts'][$userKey][$ip]['count'] + 1;
}

function reset_rate_limit(string $username, string $ip): void
{
    $userKey = $username !== '' ? $username : '_blank';
    if (isset($_SESSION['login_attempts'][$userKey][$ip])) {
        unset($_SESSION['login_attempts'][$userKey][$ip]);
    }
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
        ]
    );
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}

$page_title = 'Đăng Ký - Quản Trị Nhà Thuốc';
$errorMessage = '';
$csrfToken = csrf_token();
$oldFullname = '';
$oldUsername = '';
$oldEmail = '';
$oldPhone = ''; // Thêm biến lưu SĐT cũ

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $oldFullname = trim((string)($_POST['fullname'] ?? ''));
    $oldUsername = strtolower(trim((string)($_POST['username'] ?? '')));
    $oldEmail = trim((string)($_POST['email'] ?? ''));
    $oldPhone = trim((string)($_POST['phone'] ?? '')); // Lấy SĐT từ form

    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        $errorMessage = 'Phiên không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $fullname = $oldFullname;
        $username = $oldUsername;
        $email = $oldEmail;
        $phone = $oldPhone;
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm-password'] ?? '');
        $ip = client_ip();

        $isLimited = check_rate_limit($username, $ip) || check_rate_limit('_ip_', $ip);
        if ($isLimited) {
            $errorMessage = 'Tài khoản tạm khóa do đăng ký nhiều lần. Vui lòng thử lại sau.';
        } else {
            $invalid = false;
            // Validate Họ tên
            $nameLength = function_exists('mb_strlen') ? mb_strlen($fullname, 'UTF-8') : strlen($fullname);
            if ($fullname === '' || $nameLength > 100) {
                $invalid = true;
            }
            // Validate Email
            if ($email !== '') {
                $emailLength = function_exists('mb_strlen') ? mb_strlen($email, 'UTF-8') : strlen($email);
                if ($emailLength > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalid = true;
                }
            } else {
                $email = null;
            }
            // Validate Phone (SĐT là bắt buộc để tạo khách hàng)
            if ($phone === '' || !preg_match('/^[0-9]{9,11}$/', $phone)) {
                $invalid = true;
                $errorMessage = 'Số điện thoại không hợp lệ.';
            }

            // Validate Username
            $usernameLength = strlen($username);
            if ($username === '' || $usernameLength < 3 || $usernameLength > 60 || !preg_match('/^[a-z0-9._]+$/', $username)) {
                $invalid = true;
            }
            // Validate Password
            if (strlen($password) < 6 || $password !== $confirmPassword) {
                $invalid = true;
            }

            if ($invalid && empty($errorMessage)) {
                $errorMessage = 'Thông tin không hợp lệ. Vui lòng kiểm tra lại.';
            }

            if ($invalid) {
                register_failed_attempt($username, $ip);
                register_failed_attempt('_ip_', $ip);
            } else {
                try {
                    // Kiểm tra username đã tồn tại chưa
                    $stmt = $pdo->prepare('SELECT id FROM auth WHERE username = ? LIMIT 1');
                    $stmt->execute([$username]);
                    $existing = $stmt->fetch();
                } catch (Throwable $e) {
                    $existing = null;
                }

                if ($existing) {
                    register_failed_attempt($username, $ip);
                    register_failed_attempt('_ip_', $ip);
                    $errorMessage = 'Tên đăng nhập đã tồn tại.';
                } else {
                    // --- BẮT ĐẦU INSERT DỮ LIỆU (TRANSACTION) ---
                    try {
                        $pdo->beginTransaction(); // Bắt đầu giao dịch

                        // 1. Thêm vào bảng khachhang trước
                        // (Cột hoten, sdt, email, diachi để trống)
                        $insertKH = $pdo->prepare("INSERT INTO khachhang (hoten, sdt, email, diachi) VALUES (?, ?, ?, '')");
                        $insertKH->execute([$fullname, $phone, $email]);
                        $newMakh = $pdo->lastInsertId(); // Lấy ID khách hàng vừa tạo

                        // 2. Thêm vào bảng auth với makh vừa lấy được
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $roles = ['customer'];
                        $permissions = [];
                        $tokens = [];

                        $tokensJson = $tokens === [] ? '{}' : save_tokens($tokens);
                        $rolesJson = json_encode($roles, JSON_UNESCAPED_UNICODE);
                        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE);

                        // Lưu ý: Cột makh được điền $newMakh
                        $insertAuth = $pdo->prepare('INSERT INTO auth (username, password_hash, email, manv, makh, status, roles, permissions, tokens) VALUES (?, ?, ?, NULL, ?, 1, ?, ?, ?)');
                        $insertAuth->execute([$username, $passwordHash, $email, $newMakh, $rolesJson, $permissionsJson, $tokensJson]);

                        $pdo->commit(); // Xác nhận giao dịch thành công

                        reset_rate_limit($username, $ip);
                        reset_rate_limit('_ip_', $ip);
                        unset($_SESSION['csrf']);
                        
                        $_SESSION['register_success'] = 'Đăng ký thành công. Vui lòng đăng nhập.';
                        header('Location: login.php');
                        exit;

                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack(); // Hoàn tác nếu có lỗi
                        }
                        register_failed_attempt($username, $ip);
                        register_failed_attempt('_ip_', $ip);
                        $errorMessage = 'Lỗi hệ thống: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0284c7;
            --primary-light: #e0f2fe;
            --primary-dark: #0369a1;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            overflow: hidden; 
        }
        #pills-canvas {
            position: fixed; 
            top: 0; left: 0; width: 100%; height: 100%; z-index: -1; 
            background: linear-gradient(to bottom, #e0f7fa, #b3e5fc);
        }
        @keyframes fadeInUpAndGrow {
            from { opacity: 0; transform: translateY(30px) scale(0.95); filter: blur(5px); }
            to { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }
        @keyframes float {
            0% { transform: translateY(0px) rotateZ(0deg); }
            50% { transform: translateY(-5px) rotateZ(0.5deg); }
            100% { transform: translateY(0px) rotateZ(0deg); }
        }
        .login-card-animation {
            animation: fadeInUpAndGrow 0.9s ease-out forwards, float 3s ease-in-out 0.9s infinite;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .login-card-animation:hover {
            transform: translateY(-8px) rotateZ(-1.5deg) scale(1.01);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            animation-play-state: running, paused;
        }
        .login-button {
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 14px 0 rgba(2, 132, 199, 0.25);
        }
        .login-button:hover { transform: translateY(-4px); box-shadow: 0 10px 25px 0 rgba(2, 132, 199, 0.35); }
        .login-button:active { transform: scale(0.98); box-shadow: 0 2px 10px 0 rgba(2, 132, 199, 0.2); }
        .login-input { transition: all 0.2s ease-in-out; }
        .login-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2); outline: none; }
        /* Custom scrollbar cho form dài */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.2); border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 text-gray-800">

<canvas id="pills-canvas"></canvas>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('pills-canvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth; canvas.height = window.innerHeight;
            let pills = []; const numberOfPills = 50;
            const colors = ['#ffffff', '#bae6fd', '#f0f9ff', '#0284c7'];
            const mouse = { x: null, y: null, radius: 120 };
            window.addEventListener('mousemove', (e) => { mouse.x = e.x; mouse.y = e.y; });
            class Pill {
                constructor() { this.reset(); }
                reset() {
                    this.x = Math.random() * canvas.width; this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 7 + 5; this.speedY = Math.random() * 1 + 0.2;
                    this.color = colors[Math.floor(Math.random() * colors.length)];
                    this.opacity = Math.random() * 0.5 + 0.15; this.angle = Math.random() * Math.PI * 2;
                }
                update() { this.y -= this.speedY; if (this.y < -20) this.reset(); }
                draw() {
                    ctx.save(); ctx.translate(this.x, this.y); ctx.rotate(this.angle);
                    ctx.globalAlpha = this.opacity; ctx.fillStyle = this.color;
                    ctx.beginPath(); ctx.arc(0, 0, this.size, 0, Math.PI * 2); ctx.fill(); ctx.restore();
                }
            }
            function init() { pills = Array.from({ length: numberOfPills }, () => new Pill()); }
            function animate() { ctx.clearRect(0, 0, canvas.width, canvas.height); pills.forEach(p => { p.update(); p.draw(); }); requestAnimationFrame(animate); }
            init(); animate();
        }
    });
</script>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white/90 backdrop-blur-lg p-8 rounded-2xl shadow-2xl border border-gray-200 w-full max-w-md login-card-animation max-h-[95vh] overflow-y-auto custom-scrollbar">
        
        <div class="flex flex-col items-center mb-6">
            <div class="p-3 rounded-full" style="background-color: var(--primary-light);">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-color);">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h1 class="text-2xl font-bold mt-3" style="color: var(--primary-dark);">Đăng Ký Thành Viên</h1>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form action="#" method="POST" class="space-y-4">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Họ và Tên</label>
                <input type="text" name="fullname" required value="<?php echo htmlspecialchars($oldFullname); ?>" class="login-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:bg-white" placeholder="Nguyễn Văn A">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                <input type="tel" name="phone" required value="<?php echo htmlspecialchars($oldPhone); ?>" pattern="[0-9]{9,11}" class="login-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:bg-white" placeholder="0901234567">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($oldEmail); ?>" class="login-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:bg-white" placeholder="email@example.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tên đăng nhập</label>
                <input type="text" name="username" required value="<?php echo htmlspecialchars($oldUsername); ?>" class="login-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:bg-white" placeholder="username123">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <input type="password" name="password" required class="login-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:bg-white" placeholder="******">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhập lại</label>
                    <input type="password" name="confirm-password" required class="login-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:bg-white" placeholder="******">
                </div>
            </div>

            <div class="text-sm text-center mt-2">
                <span class="text-gray-700">Đã có tài khoản? </span>
                <a href="login.php" class="font-medium hover:underline" style="color: var(--primary-color);">Đăng nhập ngay</a>
            </div>

            <button type="submit" class="login-button w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white font-semibold mt-4" style="background-color: var(--primary-color);">
                Đăng Ký
            </button>
        </form>
    </div>
</div>

</body>
</html>