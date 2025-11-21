<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

// Base URL (gi? l?i bi?n c? s?n n?u ?? khai b?o n?i kh?c)
$base_url = $base_url ?? '/Pharmacy-management';

// L?y file hi?n t?i v? tab hi?n t?i
$current_file = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$current_tab  = $_GET['tab'] ?? null;

/**
 * Tr? v? class active n?u ??ng file v? (tu? ch?n) ??ng tab.
 * - $file: t?n file PHP, v? d? 'management.php'
 * - $tab:  null n?u kh?ng c?n so tab; ho?c 'product' / 'meta' ...
 */
function admin_active_if(string $file, ?string $tab = null): string {
    global $current_file, $current_tab;
    if ($current_file !== $file) return '';
    if ($tab !== null && $current_tab !== $tab) return '';
    return ' active bg-sky-100 text-sky-700 font-semibold';
}

// ??t c?c gi? tr? m?c ??nh
if (!isset($page_title)) {
    $page_title = 'Qu?n Tr? - Nh? Thu?c An T?m';
}
if (!isset($active)) {
    $active = 'home';
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
    
    <!-- TOÀN BỘ CSS CỦA TRANG WEB -->
    <style>
        :root {
            --primary-color: #0284c7; /* sky-600 */
            --primary-light: #e0f2fe; /* sky-100 */
            --primary-dark: #0369a1;  /* sky-700 */
        }
        body { 
            font-family: 'Inter', sans-serif; 
            overflow: hidden; 
        }
        #pills-canvas {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; background: linear-gradient(to bottom, #e0f7fa, #b3e5fc);
        }
        .sidebar-item { 
            transition: transform 0.2s ease, background-color 0.2s ease; 
        }
        .sidebar-item:hover {
            background-color: var(--primary-light); color: var(--primary-dark);
            transform: translateX(4px);
        }
        .sidebar-item.active {
            background-color: var(--primary-color); color: white;
            box-shadow: 0 4px 14px 0 rgba(2, 132, 199, 0.25);
        }
        .sidebar-item.active svg { color: white; }
        .dashboard-card { 
            transition: all 0.3s ease-in-out; 
        }
    .dashboard-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1);
    }
    </style>
    <style>
      /* Dự phòng: nếu project chưa có style cho .active ở sidebar */
      .active {
        background-color:#e0f2fe;
        color:#075985;
        font-weight:600;
      }
      .sidebar a.active,
      nav a.active {
        background-color: #e0f2fe; /* sky-100 */
        color: #075985;            /* sky-700 */
        font-weight: 600;
      }
    </style>
</head>
<body class="bg-slate-50 text-gray-800">

<!-- Canvas và Script cho hiệu ứng nền -->
<canvas id="pills-canvas"></canvas>
<script>
    // Đảm bảo script chạy sau khi canvas được tải
    document.addEventListener('DOMContentLoaded', (event) => {
        const canvas = document.getElementById('pills-canvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            let pills = [];
            const numberOfPills = 100;
            const colors = ['#ffffff', '#bae6fd', '#f0f9ff', '#0284c7'];
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

<!-- Layout chính của trang -->
<div class="flex h-screen">
    <aside class="w-64 bg-white/80 backdrop-blur-lg shadow-lg flex flex-col p-4 border-r border-gray-200 z-10">
      <a href="<?= $base_url ?>/" class="flex items-center gap-3 px-2 py-4 border-b border-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-color);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9.5 14.5 1.5 1.5 3.5-3.5"/></svg>
        <h1 class="text-2xl font-bold" style="color: var(--primary-dark);">An Tâm</h1>
      </a>
      <nav class="mt-6 flex-1 overflow-y-auto">
        <ul class="space-y-2" id="nav-menu">
            <li><a href="<?= $base_url ?>/admin/index.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700<?= admin_active_if('index.php'); ?>">Trang Chủ</a></li>
            <li><a href="<?= $base_url ?>/admin/orders.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700<?= admin_active_if('orders.php'); ?>">Danh Sách Đơn Hàng</a></li>
            <li><a href="<?= $base_url ?>/admin/dashboard.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700<?= admin_active_if('dashboard.php'); ?>">Dashboard</a></li>
            <li><a href="<?= $base_url ?>/admin/management.php?tab=product" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700<?= admin_active_if('management.php','product'); ?>">Quản Lý Sản Phẩm</a></li>
            <li><a href="<?= $base_url ?>/admin/management.php?tab=meta" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700<?= admin_active_if('management.php','meta'); ?>">Quản lý DVT, DM, TH</a></li>
            <!-- <li><a href="<?= $base_url ?>/admin/create-order.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700<?= admin_active_if('create-order.php'); ?>">T?o Don h?ng</a></li> -->
            <li><a href="<?= $base_url ?>/admin/staff.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700<?= admin_active_if('staff.php'); ?>">Quản Lý Nhân Viên</a></li>
</ul>
      </nav>
      <?php
        // Đảm bảo có session + CSRF để render form
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
        $base_url = $base_url ?? '/Pharmacy-management';
      ?>
      <div class="mt-auto p-2">
        <form method="POST" action="<?php echo htmlspecialchars($base_url . '/auth/logout.php'); ?>"
              onsubmit="return confirm('Bạn có chắc muốn đăng xuất?');">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
          <button type="submit"
            class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg text-red-500 bg-red-100/50 hover:bg-red-100 transition-colors">
            <span>Đăng xuất</span>
          </button>
        </form>
      </div>
    </aside>

