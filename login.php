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

function is_hash(?string $hash): bool
{
    if ($hash === null) {
        return false;
    }

    return (bool)preg_match('/^\$2y\$|^\$argon2/i', $hash);
}

function rehash_if_needed(PDO $pdo, int $userId, string $plain, string $storedHash, bool $isHashed): string
{
    $shouldRehash = !$isHashed || password_needs_rehash($storedHash, PASSWORD_DEFAULT);
    if (!$shouldRehash) {
        return $storedHash;
    }

    $newHash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE auth SET password_hash = ? WHERE id = ?');
    $stmt->execute([$newHash, $userId]);

    return $newHash;
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

$errorMessage = '';
$successMessage = '';
$csrfToken = csrf_token();

if (isset($_SESSION['register_success'])) {
    $successMessage = (string)$_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        $errorMessage = 'Phiên đăng nhập không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = isset($_POST['remember-me']);
        $ip = client_ip();

        if (check_rate_limit($username, $ip)) {
            $errorMessage = 'Tài khoản tạm khoá do đăng nhập sai nhiều lần. Vui lòng thử lại sau.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id, username, password_hash, email, manv, makh, status, roles, permissions, tokens FROM auth WHERE username = ? LIMIT 1');
                $stmt->execute([$username]);
                $user = $stmt->fetch();
            } catch (Throwable $e) {
                $user = null;
            }

            if (!$user) {
                register_failed_attempt($username, $ip);
                $errorMessage = 'Tên đăng nhập hoặc mật khẩu không đúng.';
            } else {
                $hash = (string)($user['password_hash'] ?? '');
                $isHashed = is_hash($hash);
                $isValid = false;

                if ($isHashed) {
                    $isValid = password_verify($password, $hash);
                    if ($isValid) {
                        $hash = rehash_if_needed($pdo, (int)$user['id'], $password, $hash, true);
                    }
                } else {
                    $isValid = hash_equals($hash, $password);
                    if ($isValid) {
                        $hash = rehash_if_needed($pdo, (int)$user['id'], $password, $hash, false);
                    }
                }

                if (!$isValid || (ENFORCE_STATUS === true && (int)$user['status'] !== 1)) {
                    register_failed_attempt($username, $ip);
                    $errorMessage = 'Tên đăng nhập hoặc mật khẩu không đúng.';
                } else {
                    reset_rate_limit($username, $ip);
                    session_regenerate_id(true);

                    $roles = json_decode($user['roles'] ?? '[]', true);
                    $roles = is_array($roles) ? $roles : [];
                    $permissions = json_decode($user['permissions'] ?? '[]', true);
                    $permissions = is_array($permissions) ? $permissions : [];

                    $_SESSION['auth'] = [
                        'id' => (int)$user['id'],
                        'username' => (string)$user['username'],
                        'email' => $user['email'] ?? null,
                        'manv' => $user['manv'] !== null ? (int)$user['manv'] : null,
                        'makh' => $user['makh'] !== null ? (int)$user['makh'] : null,
                        'roles' => $roles,
                        'permissions' => $permissions,
                        'logged_in_at' => gmdate('c'),
                    ];

                    unset($_SESSION['csrf']);
                    $csrfToken = csrf_token();

                    if ($remember) {
                        $selector = bin2hex(random_bytes(9));
                        $validator = bin2hex(random_bytes(32));
                        $expiresAt = date('Y-m-d H:i:s', time() + 30 * 86400);

                        $tokens = load_tokens($user['tokens'] ?? null);
                        $tokens['remember'] = [
                            'selector' => $selector,
                            'validator_hash' => hash('sha256', $validator),
                            'expires_at' => $expiresAt,
                            'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 150),
                            'ip' => $ip,
                        ];

                        $storedTokens = save_tokens($tokens);
                        $updateTokens = $pdo->prepare('UPDATE auth SET tokens = ? WHERE id = ?');
                        $updateTokens->execute([$storedTokens, (int)$user['id']]);

                        setcookie('remember_selector', $selector, [
                            'expires' => time() + 30 * 86400,
                            'httponly' => true,
                            'samesite' => 'Lax',
                            'secure' => $secureCookie,
                            'path' => '/',
                        ]);
                        setcookie('remember_validator', $validator, [
                            'expires' => time() + 30 * 86400,
                            'httponly' => true,
                            'samesite' => 'Lax',
                            'secure' => $secureCookie,
                            'path' => '/',
                        ]);
                    }

                    $isAdmin = in_array('admin', $roles, true);
                    $isStaff = in_array('staff', $roles, true);

                    if ($isAdmin || $isStaff) {
                        header('Location: /Pharmacy-management/admin/');
                        exit;
                    }

                    header('Location: /Pharmacy-management/');
                    exit;
                }
            }
        }
    }
}

$page_title = 'Đăng Nhập - Quản Trị Nhà Thuốc';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- Tải Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tải Google Font (Inter) giống như file header -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- 
    TOÀN BỘ CSS CỦA TRANG ĐĂNG NHẬP 
    Bao gồm tất cả hiệu ứng đã cải tiến
    -->
    <style>
        :root {
            /* Sao chép các biến màu từ file header.php của bạn */
            --primary-color: #0284c7; /* sky-600 */
            --primary-light: #e0f2fe; /* sky-100 */
            --primary-dark: #0369a1;  /* sky-700 */
        }
        body { 
            font-family: 'Inter', sans-serif; 
            /* overflow: hidden để canvas không tạo thanh cuộn */
            overflow: hidden; 
        }

        /* Sao chép style cho canvas từ file header.php 
        */
        #pills-canvas {
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            z-index: -1; 
            /* Gradient nền cho canvas */
            background: linear-gradient(to bottom, #e0f7fa, #b3e5fc);
        }

        /* * HIỆU ỨNG MỚI CHO FORM ĐĂNG NHẬP (ĐÃ CẢI TIẾN)
        */

        /* Keyframes cho form bay lên, phóng to, và mờ dần (cải tiến) */
        @keyframes fadeInUpAndGrow {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95); /* Bắt đầu nhỏ hơn và thấp hơn */
                filter: blur(5px); /* Bắt đầu mờ */
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1); /* Phóng to về kích thước gốc */
                filter: blur(0); /* Rõ nét */
            }
        }

        /* Keyframes cho hiệu ứng "treo lơ lửng" */
        @keyframes float {
            0% { transform: translateY(0px) rotateZ(0deg); }
            50% { transform: translateY(-5px) rotateZ(0.5deg); } /* Dịch lên nhẹ và nghiêng nhẹ */
            100% { transform: translateY(0px) rotateZ(0deg); }
        }

        /* Áp dụng animation cho card đăng nhập */
        .login-card-animation {
            /* 1. Áp dụng animation xuất hiện */
            animation: fadeInUpAndGrow 0.9s ease-out forwards;
            
            /* 2. Áp dụng animation treo lơ lửng sau khi animation xuất hiện kết thúc */
            /* Chúng ta kết hợp 2 animation, cách nhau bằng dấu phẩy */
            animation-name: fadeInUpAndGrow, float;
            animation-duration: 0.9s, 3s; /* Thời lượng cho mỗi animation */
            animation-timing-function: ease-out, ease-in-out;
            animation-delay: 0s, 0.9s; /* float sẽ delay 0.9s để chờ cái kia chạy xong */
            animation-iteration-count: 1, infinite; /* fadeInUpAndGrow chạy 1 lần, float lặp vô hạn */
            animation-fill-mode: forwards, none; /* Giữ trạng thái cuối của fadeInUpAndGrow */

            /* 3. Thêm transition cho hiệu ứng hover (di chuyển nghiêng) */
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        /* 4. Hiệu ứng di chuyển nghiêng nhẹ khi di chuột vào form */
        .login-card-animation:hover {
            transform: translateY(-8px) rotateZ(-1.5deg) scale(1.01); /* Dịch lên, nghiêng trái, phóng to nhẹ */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); /* Tăng bóng đổ */
            animation-play-state: running, paused; /* Dừng hiệu ứng float khi hover */
            /* (running là để đảm bảo cái đầu tiên vẫn chạy nếu hover vào sớm) */
        }
        
        /* * HIỆU ỨNG CŨ CHO NÚT BẤM (GIỮ NGUYÊN) 
        */
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(2, 132, 199, 0.3);
            }
            50% {
                /* Tăng cường độ glow ở giữa animation */
                box-shadow: 0 0 35px rgba(2, 132, 199, 0.6);
            }
        }

        .login-button {
            /* Thêm transition mượt mà cho tất cả thuộc tính */
            transition: all 0.3s ease-in-out;
            
            /* Áp dụng animation pulse-glow */
            animation: pulse-glow 2.5s infinite ease-in-out;
            
            /* Thêm một chút bóng đổ mặc định */
            box-shadow: 0 4px 14px 0 rgba(2, 132, 199, 0.25);
        }

        /* Hiệu ứng khi di chuột vào nút */
        .login-button:hover {
            /* Nâng nút lên một chút */
            transform: translateY(-4px);
            
            /* Tăng bóng đổ khi hover */
            box-shadow: 0 10px 25px 0 rgba(2, 132, 199, 0.35);
        }

        /* Hiệu ứng khi nhấn nút */
        .login-button:active {
            /* Thu nhỏ nút lại một chút khi nhấn */
            transform: scale(0.98);
            box-shadow: 0 2px 10px 0 rgba(2, 132, 199, 0.2);
        }
        
        /* * HIỆU ỨNG CŨ CHO Ô INPUT (GIỮ NGUYÊN)
        */
        .login-input {
            transition: all 0.2s ease-in-out;
        }

        /* Hiệu ứng khi focus vào ô input */
        .login-input:focus {
            /* Thêm viền màu primary và hiệu ứng "ring" (vòng sáng) */
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
            outline: none; /* Bỏ viền focus mặc định */
        }
    </style>
</head>
<body class="bg-slate-50 text-gray-800">

<!-- 
SAO CHÉP TOÀN BỘ CANVAS VÀ SCRIPT TỪ HEADER.PHP CỦA BẠN
Đây là phần tạo ra hiệu ứng nền "viên thuốc" bay
-->
<canvas id="pills-canvas"></canvas>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        const canvas = document.getElementById('pills-canvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            let pills = [];
            const numberOfPills = 100; // Giữ nguyên số lượng
            const colors = ['#ffffff', '#bae6fd', '#f0f9ff', '#0284c7']; // Giữ nguyên màu sắc
            const mouse = { x: null, y: null, radius: 120 };

            window.addEventListener('mousemove', (e) => { mouse.x = e.x; mouse.y = e.y; });
            window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });
            window.addEventListener('resize', () => {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                init();
            });

            class Pill {
                constructor() { this.reset(); }
                reset() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 7 + 5;
                    this.speedY = Math.random() * 1 + 0.2;
                    this.color = colors[Math.floor(Math.random() * colors.length)];
                    this.opacity = Math.random() * 0.5 + 0.15;
                    this.density = (Math.random() * 5) + 1;
                    this.angle = Math.random() * Math.PI * 2;
                    this.rotationSpeed = (Math.random() - 0.5) * 0.01;
                }
                update() {
                    let dx = mouse.x - this.x;
                    let dy = mouse.y - this.y;
                    let distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < mouse.radius) {
                        const force = (mouse.radius - distance) / mouse.radius;
                        this.x -= (dx / distance) * force * this.density;
                        this.y -= (dy / distance) * force * this.density;
                    }
                    this.y -= this.speedY;
                    this.angle += this.rotationSpeed;
                    if (this.y < -this.size * 3) {
                        this.y = canvas.height + this.size * 3;
                        this.x = Math.random() * canvas.width;
                    }
                }
                draw() {
                    ctx.save();
                    ctx.translate(this.x, this.y);
                    ctx.rotate(this.angle);
                    ctx.globalAlpha = this.opacity;
                    ctx.shadowBlur = 12;
                    ctx.shadowColor = this.color;
                    const capsuleHeight = this.size;
                    const capsuleWidth = this.size * 2;
                    ctx.fillStyle = this.color;
                    ctx.beginPath();
                    ctx.arc(capsuleWidth / 4, 0, capsuleHeight / 2, -Math.PI / 2, Math.PI / 2, false);
                    ctx.arc(-capsuleWidth / 4, 0, capsuleHeight / 2, Math.PI / 2, -Math.PI / 2, false);
                    ctx.closePath();
                    ctx.fill();
                    ctx.restore();
                }
            }
            function init() { pills = Array.from({ length: numberOfPills }, () => new Pill()); }
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                pills.forEach(p => { p.update(); p.draw(); });
                requestAnimationFrame(animate);
            }
            init();
            animate();
        }
    });
</script>
<!-- KẾT THÚC PHẦN SAO CHÉP HIỆU ỨNG NỀN -->


<!-- 
PHẦN NỘI DUNG FORM ĐĂNG NHẬP
-->
<div class="min-h-screen flex items-center justify-center p-4">
    
    <!-- 
    Card Đăng Nhập
    - Sử dụng style bg-white/80 và backdrop-blur-lg giống sidebar của bạn
    - Thêm shadow-2xl để nổi bật hơn
    - Áp dụng animation 'login-card-animation' đã định nghĩa ở trên
    -->
    <div class="bg-white/80 backdrop-blur-lg p-8 md:p-10 rounded-2xl shadow-2xl border border-gray-200 w-full max-w-md login-card-animation">
        
        <!-- Logo và Tiêu đề -->
        <div class="flex flex-col items-center mb-6">
            <!-- Icon Logo (Giống header) -->
            <div class="p-3 rounded-full" style="background-color: var(--primary-light);">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-color);">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9.5 14.5 1.5 1.5 3.5-3.5"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold mt-4" style="color: var(--primary-dark);">Nhà Thuốc An Tâm</h1>
            <p class="text-gray-600 mt-2">Chào mừng bạn trở lại!</p>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php elseif ($successMessage !== ''): ?>
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Form Đăng Nhập -->
        <form action="#" method="POST" class="space-y-6">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <!-- Ô Tên đăng nhập -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    Tên đăng nhập
                </label>
                <div class="relative">
                    <!-- Icon bên trong input -->
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <!-- 
                    Input với class 'login-input'
                    - Thêm padding trái (pl-10) để chừa chỗ cho icon
                    -->
                    <input 
                        type="text" 
                        id="username" 
                        name="username"
                        placeholder="ten.dang.nhap"
                        required
                        class="login-input w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-white/70 focus:bg-white"
                    >
                </div>
            </div>

            <!-- Ô Mật khẩu -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Mật khẩu
                </label>
                <div class="relative">
                    <!-- Icon bên trong input -->
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        placeholder="••••••••"
                        required
                        class="login-input w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-white/70 focus:bg-white"
                    >
                </div>
            </div>

            <!-- Tùy chọn Ghi nhớ & Quên mật khẩu -->
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 rounded border-gray-300" style="color: var(--primary-color);">
                    <label for="remember-me" class="text-gray-700">Ghi nhớ tôi</label>
                </div>
                <a href="#" class="font-medium" style="color: var(--primary-color); --tw-ring-color: var(--primary-color)">
                    Quên mật khẩu?
                </a>
            </div>

            <!-- 
            *** BỔ SUNG MỚI ***
            Thêm link "Đăng ký ngay"
            -->
            <div class="text-sm text-center">
                <span class="text-gray-700">Chưa có tài khoản? </span>
                <a href="register.php" class="font-medium" style="color: var(--primary-color); --tw-ring-color: var(--primary-color)">
                    Đăng ký ngay
                </a>
            </div>
            <!-- *** KẾT THÚC BỔ SUNG *** -->

            <!-- 
            Nút Đăng Nhập
            - Áp dụng class 'login-button' với nhiều hiệu ứng (hover, active, pulse)
            -->
            <div>
                <button 
                    type="submit" 
                    class="login-button w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white font-semibold"
                    style="background-color: var(--primary-color);"
                >
                    Đăng Nhập
                </button>
            </div>
        </form>

    </div>
</div>

</body>
</html>
