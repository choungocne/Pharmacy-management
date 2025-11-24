<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

$base_url = $base_url ?? '/Pharmacy-management';
$current_file = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$current_tab  = $_GET['tab'] ?? null;

// Bảo vệ khu vực admin: chỉ admin/staff được vào
$auth = $_SESSION['auth'] ?? null;
$roles = [];
if (is_array($auth) && isset($auth['roles'])) {
    $roles = is_array($auth['roles']) ? $auth['roles'] : json_decode((string)$auth['roles'], true);
    if (!is_array($roles)) {
        $roles = [];
    }
}
$isAdmin = in_array('admin', $roles, true);
$isStaff = in_array('staff', $roles, true);

if (!$auth || (!$isAdmin && !$isStaff)) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Staff không được vào trang quản lý tài khoản
if (!$isAdmin && $current_file === 'taikhoan.php') {
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

function admin_active_if(string $file, ?string $tab = null): string {
    global $current_file, $current_tab;
    if ($current_file !== $file) return '';
    if ($tab !== null && $current_tab !== $tab) return '';
    return ' active bg-sky-100 text-sky-700 font-semibold';
}

if (!isset($page_title)) {
    $page_title = 'Quản Trị - Nhà Thuốc An Tâm';
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
    <script>
      window.tailwind = window.tailwind || {};
      window.tailwind.config = { darkMode: 'class' };
    </script>
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
            --bg-surface: #ffffff;
            --bg-surface-muted: #ffffffcc;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
        }
        body {
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            background-color: #f8fafc;
            color: var(--text-primary);
        }
        .dark {
            --bg-surface: #0f172a;
            --bg-surface-muted: rgba(15,23,42,0.8);
            --text-primary: #e2e8f0;
            --text-secondary: #cbd5e1;
            background-color: #0b1220;
            color: var(--text-primary);
        }
        .dark .bg-white { background-color: #0f172a !important; color: var(--text-primary) !important; }
        .dark .bg-white\/80 { background-color: rgba(15,23,42,0.8) !important; color: var(--text-primary) !important; }
        .dark .bg-white\/\[0\.8\] { background-color: rgba(15,23,42,0.8) !important; color: var(--text-primary) !important; }
        .dark .bg-slate-50 { background-color: #0b1220 !important; }
        .dark .text-gray-800 { color: var(--text-primary) !important; }
        .dark .text-gray-500 { color: var(--text-secondary) !important; }
        .dark .border-gray-200 { border-color: #334155 !important; }
        .dark .shadow-lg { box-shadow: 0 10px 30px rgba(0,0,0,0.4) !important; }
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
        .dashboard-card { transition: all 0.3s ease-in-out; }
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
<body class="bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100">

<!-- Canvas và Script cho hiệu ứng nền -->
<canvas id="pills-canvas"></canvas>
<script>
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
    <aside class="w-64 bg-white/80 dark:bg-slate-950/30 backdrop-blur-lg shadow-lg flex flex-col p-4 border-r border-gray-200 dark:border-slate-800 z-10">
      <div class="flex items-center justify-between gap-3 px-2 py-4 border-b border-gray-200 dark:border-slate-800">
        <a href="<?= $base_url ?>/" class="flex items-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-color);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9.5 14.5 1.5 1.5 3.5-3.5"/></svg>
          <h1 class="text-2xl font-bold" style="color: var(--primary-dark);">An Tâm</h1>
        </a>
        <button id="themeToggle"
          class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-slate-200/60 bg-white/80 hover:bg-white dark:bg-slate-800/70 dark:border-slate-700"
          aria-label="Đổi giao diện"
          title="Đổi giao diện sáng/tối">
          <!-- Moon (hiện ở chế độ sáng) -->
          <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-700 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
          <!-- Sun (hiện ở chế độ tối) -->
          <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden dark:block text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.93 4.93 6.34 6.34M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07 6.34 17.66M17.66 6.34l1.41-1.41"/>
          </svg>
        </button>
      </div>
      <nav class="mt-6 flex-1 overflow-y-auto">
        <ul class="space-y-2" id="nav-menu">
            <li><a href="<?= $base_url ?>/admin/index.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('index.php'); ?>">Trang Chủ</a></li>
            <li><a href="<?= $base_url ?>/admin/orders.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('orders.php'); ?>">Danh Sách Đơn Hàng</a></li>
            <li><a href="<?= $base_url ?>/admin/dashboard.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('dashboard.php'); ?>">Dashboard</a></li>
            <li><a href="<?= $base_url ?>/admin/products.php?tab=product" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('products.php','product'); ?>">Quản Lý Sản Phẩm</a></li>
            <li><a href="<?= $base_url ?>/admin/management.php?tab=meta" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('management.php','meta'); ?>">Quản lý DVT, DM, TH</a></li>
            <!-- <li><a href="<?= $base_url ?>/admin/create-order.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('create-order.php'); ?>">Tạo Đơn hàng</a></li> -->
            <li><a href="<?= $base_url ?>/admin/staff.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('staff.php'); ?>">Quản Lý Nhân Viên</a></li>
            <li><a href="<?= $base_url ?>/admin/taikhoan.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-slate-200 dark:hover:bg-slate-800/70<?= admin_active_if('taikhoan.php'); ?>">Quản Lý Tài Khoản</a></li>
        </ul>
      </nav>
      <?php
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
    <script>
(function() {
  const KEY = 'theme'; // 'dark' | 'light'
  const root = document.documentElement;

  function applyVars(isDark) {
    if (isDark) {
      root.style.setProperty('--primary-color', '#38bdf8'); // sky-400
      root.style.setProperty('--primary-dark',  '#0ea5e9'); // sky-500
      root.style.setProperty('--primary-light', '#082f49'); // slate-950-ish accent
    } else {
      root.style.setProperty('--primary-color', '#0284c7');
      root.style.setProperty('--primary-dark',  '#0369a1');
      root.style.setProperty('--primary-light', '#e0f2fe');
    }
  }

  function setTheme(mode) {
    const isDark = mode === 'dark';
    root.classList.toggle('dark', isDark);
    try { localStorage.setItem(KEY, isDark ? 'dark' : 'light'); } catch (e) {}
    applyVars(isDark);
  }

  (function init() {
    let saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    setTheme(saved ? saved : (prefersDark ? 'dark' : 'light'));
  })();

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('#themeToggle');
    if (!btn) return;
    const isDark = root.classList.contains('dark');
    setTheme(isDark ? 'light' : 'dark');
  });
})();
</script>
