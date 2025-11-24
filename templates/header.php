<?php
declare(strict_types=1);
// ==========================================
// Tập: header.php
// ==========================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$base_url = $base_url ?? '/Pharmacy-management';
$isLogged = !empty($_SESSION['auth']);
$username = $isLogged ? ($_SESSION['auth']['username'] ?? 'Tài khoản') : null;
$roles = $isLogged ? (array)($_SESSION['auth']['roles'] ?? []) : [];
$isAdmin = $isLogged && (in_array('admin', $roles, true) || !empty($_SESSION['auth']['manv']));
$auth = $_SESSION['auth'] ?? [];
$makh = $auth['makh'] ?? ($_SESSION['makh'] ?? null);

// --- YASU Cấu Kết Nối CSDL Và Hàm Hỗ Trợ ---
if (!function_exists('pdo')) {
    require_once __DIR__ . '/../db.php';
}

require_once __DIR__ . '/effect.php';

// --- THIẾT LẬP BASE URL ---
$base_url = $base_url ?: '/Pharmacy-management';

// Detect "big cart" page (pages/giohang.php or base.php?page=giohang)
if (!isset($IS_CART_PAGE)) {
    $curScript = $_SERVER['SCRIPT_NAME'] ?? '';
    $isByPath  = (strpos($curScript, '/pages/giohang.php') !== false);
    $isByParam = (($_GET['page'] ?? '') === 'giohang');
    $IS_CART_PAGE = $isByPath || $isByParam;
}

// --- Lấy danh mục cấp 1 ---
$pdo = pdo();
$sql_lv1 = "SELECT * FROM danhmuc WHERE cap = 1 ORDER BY madm";
$stmt1 = $pdo->query($sql_lv1);
$menu_lv1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// Map tên danh mục cấp 1 tới trang PHP đích
$href_map = [
    'Thực phẩm chức năng' => 'base.php?page=thucpham',
    'Thuốc'               => 'base.php?page=search',
    'Thiết bị y tế'       => 'base.php?page=thietbi',
    'Tra cứu bệnh'        => 'base.php?page=search_disease',
    'Bệnh & Góc sức khỏe'=> 'base.php?page=suckhoe',
    'Hệ thống nhà thuốc'  => 'base.php?page=about',
];

// --- Lấy giỏ hàng từ database ---
$session_id = session_id();

$sql_cart = "
    SELECT 
        g.id,
        g.masp,
        g.soluong AS cart_quantity,
        sp.tensp,
        sp.giaban,
        sp.hinhsp,
        sp.makm,
        dv.tendv,
        km.phantram_giam,
        km.gia_giam_co_dinh,
        COALESCE(SUM(tk.soluong), 0) AS tonkho_soluong
    FROM giohang g
    INNER JOIN sanpham sp ON g.masp = sp.masp
    LEFT JOIN khuyenmai km ON sp.makm = km.makm 
        AND km.trangthai_deal = 'dang_dien_ra'
        AND km.ngay_batdau <= NOW() 
        AND km.ngay_ketthuc >= NOW()
    LEFT JOIN donvitinh dv ON sp.madv = dv.madv
    LEFT JOIN tonkho tk ON tk.masp = sp.masp
    WHERE " . ($makh ? "g.makh = ?" : "g.session_id = ?") . "
    GROUP BY 
        g.id,
        g.masp,
        cart_quantity,
        sp.tensp,
        sp.giaban,
        sp.hinhsp,
        sp.makm,
        dv.tendv,
        km.phantram_giam,
        km.gia_giam_co_dinh
    ORDER BY g.created_at DESC
";

$stmt_cart = $pdo->prepare($sql_cart);
$stmt_cart->execute($makh ? [$makh] : [$session_id]);
$cart_rows = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);
$cart_items = [];
$cart_total = 0;

$normalizeImage = function ($path) use ($base_url) {
    $path = trim($path ?? '');
    if ($path === '') {
        return $base_url . '/static/img/placeholder.jpg';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $normalized = '/' . ltrim($path, '/');
    $normalized = preg_replace('#^(/Pharmacy-management)+/#', '/Pharmacy-management/', $normalized);
    if (strpos($normalized, '/Pharmacy-management/') === 0) {
        return $normalized;
    }
    return rtrim($base_url, '/') . $normalized;
};

foreach ($cart_rows as $row) {
    $quantity = (int)($row['cart_quantity'] ?? 0);
    $price = (float)($row['giaban'] ?? 0);
    $final_price = $price;

    if (!empty($row['makm'])) {
        if (!empty($row['phantram_giam'])) {
            $final_price = $price * (1 - ($row['phantram_giam'] / 100));
        } elseif (!empty($row['gia_giam_co_dinh'])) {
            $final_price = max(0, $price - $row['gia_giam_co_dinh']);
        }
    }

    $cart_items[] = [
        'id' => (int)$row['id'],
        'masp' => (int)$row['masp'],
        'name' => $row['tensp'] ?? 'Sản phẩm',
        'quantity' => $quantity,
        'unit' => $row['tendv'] ?? 'Hộp',
        'giaban' => $final_price,
        'image' => $normalizeImage($row['hinhsp'] ?? ''),
        'stock' => (int)($row['tonkho_soluong'] ?? 0),
    ];

    $cart_total += $final_price * $quantity;
}

$cart_count = count($cart_items);

render_pills_effect_assets();
?>

<?php if (!empty($IS_CART_PAGE)): ?>
<style>
/* Hard kill mini-cart on big cart page */
#mini-cart, .mini-cart, .header-cart-popover { display:none !important; }
/* BẮT BUỘC XÓA DẤU CHẤM */
ul.user-menu-list, 
.user-dropdown-menu ul {
    list-style-type: none !important; /* Xóa dấu chấm */
    padding-left: 0 !important;       /* Xóa lùi đầu dòng mặc định */
    margin: 0 !important;
}

/* Đảm bảo thẻ li cũng không hiện dấu */
ul.user-menu-list li,
.user-dropdown-menu li {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Style cho thẻ A bên trong */
ul.user-menu-list li a {
    display: flex !important;
    align-items: center;
    padding: 10px 15px !important;
    text-decoration: none !important;
    color: #333;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

ul.user-menu-list li a:hover {
    background-color: #f5f5f5 !important;
    color: #004aad !important;
}
</style>
<?php endif; ?>

<div class="header-bar">
    <div class="header-bar-content">
        <div class="header-bar-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M10.5 3A7.5 7.5 0 003 10.5c0 4.143 3.357 7.5 7.5 7.5 1.47 0 2.844-.423 3.993-1.145l4.826 4.827 1.414-1.414-4.827-4.826A7.457 7.457 0 0018 10.5 7.5 7.5 0 0010.5 3zm0 2A5.5 5.5 0 1110.5 15a5.5 5.5 0 010-11z"/></svg>
            <a href="#">Trung tâm tiêm chủng An Tâm <strong>Tìm hiểu ngay</strong></a>
        </div>
        <div class="header-bar-right">
            <a href="#" class="app-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path d="M6.5 2A1.5 1.5 0 005 3.5v13A1.5 1.5 0 006.5 18h7a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0013.5 2h-7zM9 14h2a.5.5 0 010 1H9a.5.5 0 010-1z"/></svg>
                <span>Tải ứng dụng</span>
            </a>
            <div class="call-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M6.62 10.79a15.466 15.466 0 006.59 6.59l2.2-2.2a1 1 0 011.11-.21c1.21.49 2.53.76 3.88.76a1 1 0 011 1V20a1 1 0 01-1 1C10.29 21 3 13.71 3 5a1 1 0 011-1h3.5a1 1 0 011 1c0 1.35.27 2.67.76 3.88a1 1 0 01-.21 1.11l-2.43 2.8z"/></svg>
                <span>Tư vấn ngay: <a href="tel:18006928">1800 6928</a></span>
            </div>
        </div>
    </div>
</div>

<header class="main-header">
    <div class="header-top">
        <div class="logo">
            <a href="<?= $base_url ?>/base.php?page=home"><img src="<?= $base_url ?>/static/img/logo.png" alt="Logo"><span>An Tâm</span></a>
        </div>
        <form action="<?= $base_url ?>/base.php" method="GET" class="search-bar">
            <input type="hidden" name="page" value="search_results_drug"> 
            <input type="text" name="q" placeholder="Tìm kiếm sản phẩm, thuốc, bệnh..." required>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        <div class="user-actions">
            <?php if (!$isLogged): ?>
                <a href="<?= $base_url ?>/login.php"><i class="fas fa-user"></i> Đăng nhập</a>
                <a href="<?= $base_url ?>/register.php"><i class="fas fa-user-plus"></i> Đăng ký</a>
            
   <?php else: ?>
    <style>
        .user-dropdown-menu {
            display: none; /* Ẩn mặc định */
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 9999;
            padding-top: 10px;
        }

        /* Khi có class 'active' thì mới hiện */
        .user-dropdown-wrapper.active .user-dropdown-menu {
            display: block;
            animation: fadeIn 0.2s ease-out;
        }

        /* Mũi tên xoay khi mở menu */
        .user-dropdown-wrapper.active .arrow-icon {
            transform: rotate(180deg);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="user-dropdown-wrapper" style="position: relative; display: inline-block;">
        
        <div class="user-trigger" onclick="toggleUserMenu(event)" style="display: flex; align-items: center; gap: 8px; color: #333; font-weight: 500; cursor: pointer; padding: 10px 0;">
            <i class="fas fa-user-circle" style="font-size: 20px; color: #555;"></i>
            <span style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($username ?? 'Khách hàng') ?>
            </span>
            <i class="fas fa-chevron-down arrow-icon" style="font-size: 12px; color: #777; transition: transform 0.2s;"></i>
        </div>

        <div class="user-dropdown-menu">
            <div style="background: #fff; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); border: 1px solid #eee; overflow: hidden; min-width: 240px;">
                
                <div style="padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 14px; color: #333;">
                    Xin chào, <strong style="color: #004aad;"><?= htmlspecialchars($username) ?></strong>
                </div>

                <ul style="list-style: none !important; padding: 0 !important; margin: 0 !important;">
                    <li style="list-style: none !important; margin: 0;">
                        <a href="<?= $base_url ?>/base.php?page=profile" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 14px; color: #444; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#f0f8ff'; this.style.color='#004aad'" onmouseout="this.style.background='transparent'; this.style.color='#444'">
                            <i class="fas fa-id-card" style="width: 20px; text-align: center;"></i> Thông tin tài khoản
                        </a>
                    </li>

                    <li style="list-style: none !important; margin: 0;">
                        <a href="<?= $base_url ?>/base.php?page=profile&action=edit" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 14px; color: #444; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#f0f8ff'; this.style.color='#004aad'" onmouseout="this.style.background='transparent'; this.style.color='#444'">
                            <i class="fas fa-user-edit" style="width: 20px; text-align: center;"></i> Cập nhật thông tin
                        </a>
                    </li>

                    <li style="list-style: none !important; margin: 0;">
                        <a href="<?= $base_url ?>/base.php?page=my_orders" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 14px; color: #444; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#f0f8ff'; this.style.color='#004aad'" onmouseout="this.style.background='transparent'; this.style.color='#444'">
                            <i class="fas fa-clipboard-list" style="width: 20px; text-align: center;"></i> Đơn hàng của tôi
                        </a>
                    </li>

                    <?php if ($isAdmin): ?>
                    <li style="list-style: none !important; margin: 0;">
                        <a href="<?= $base_url ?>/admin/" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 14px; color: #d9534f; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='transparent'">
                            <i class="fas fa-gauge-high" style="width: 20px; text-align: center;"></i> Quản trị hệ thống
                        </a>
                    </li>
                    <?php endif; ?>

                    <li style="height: 1px; background: #eee; margin: 4px 0; list-style: none !important;"></li>

                    <li style="list-style: none !important; margin: 0;">
                        <a href="#" data-logout-btn style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 14px; color: #d9534f; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='transparent'">
                            <i class="fas fa-sign-out-alt" style="width: 20px; text-align: center;"></i> Đăng xuất
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <form id="logout-form" action="<?= $base_url ?>/logout.php" method="POST" style="display:none;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
        <input type="hidden" name="r" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
    </form>

    <script>
        function toggleUserMenu(e) {
            // Ngăn không cho sự kiện click lan ra ngoài ngay lập tức
            e.stopPropagation();
            
            // Tìm element cha và toggle class 'active'
            const wrapper = document.querySelector('.user-dropdown-wrapper');
            if (wrapper) {
                wrapper.classList.toggle('active');
            }
        }

        // Lắng nghe sự kiện click bất kỳ đâu trên trang web
        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.user-dropdown-wrapper');
            // Nếu click ra ngoài vùng menu thì đóng lại
            if (wrapper && !wrapper.contains(e.target)) {
                wrapper.classList.remove('active');
            }
        });
    </script>
<?php endif; ?>
            
            <!-- GIỎ HÀNG MINI -->
            <div class="cart-wrapper">
                <a href="<?= $base_url ?>/base.php?page=cart"
                   <?php if (!empty($IS_CART_PAGE)): ?>
                    aria-disabled="true" style="pointer-events:none;cursor:default;"
                   <?php endif; ?>
                   class="cart-trigger">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>
                
                <?php if (empty($IS_CART_PAGE)): ?>
                <!-- DROPDOWN GIỎ HÀNG -->
                <div class="cart-dropdown">
                    <div class="cart-dropdown-surface effect-pills-container" data-effect-fallback-width="380" data-effect-fallback-height="320">
                        <div class="effect-pills-content">
                            <div class="cart-dropdown-header">
                                <span class="cart-title">Giỏ hàng</span>
                            </div>
                            
                            <div class="cart-dropdown-body">
                                <?php if (empty($cart_items)): ?>
                                    <div class="cart-empty">
                                        <i class="fas fa-shopping-cart"></i>
                                        <p>Giỏ hàng của bạn đang trống</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($cart_items as $item): ?>
                                        <div class="cart-item" data-id="<?= (int)$item['id'] ?>">
                                            <div class="cart-item-img">
                                                <a href="<?= $base_url ?>/base.php?page=product&id=<?= $item['masp'] ?>">
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                                </a>
                                            </div>
                                            <div class="cart-item-info">
                                                <a href="<?= $base_url ?>/base.php?page=product&id=<?= $item['masp'] ?>" class="cart-item-name">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </a>
                                                <div class="cart-item-bottom">
                                                    <div class="cart-item-price">
                                                        <span class="price"><?= number_format($item['giaban'] ?? 0, 0, ',', '.') ?>đ</span>
                                                    </div>
                                                    <span class="cart-item-quantity">x<?= (int)($item['quantity'] ?? 0) ?> <?= htmlspecialchars($item['unit'] ?? 'Hộp') ?></span>
                                                </div>
                                            </div>
                                            <button class="cart-item-delete" onclick="removeFromCart(<?= (int)$item['id'] ?>)">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($cart_items)): ?>
                                <div class="cart-dropdown-footer">
                                    <div class="cart-summary">
                                        <span class="cart-total-items"><?= $cart_count ?> sản phẩm</span>
                                        <a href="<?= $base_url ?>/pages/giohang.php#" class="btn-view-cart">Xem giỏ hàng</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <a href="#"><i class="fas fa-headset"></i> Hỗ trợ</a>
        </div>
    </div>
</header>

<?php if (!empty($_GET['logged_out'])): ?>
<div class="logout-alert" role="status">Bạn đã đăng xuất.</div>
<?php endif; ?>

<nav class="main-nav">
    <div class="nav-container">
        <ul class="nav-menu">
            <?php foreach ($menu_lv1 as $lv1): ?>
                <?php
                $stmt2 = $pdo->prepare("SELECT * FROM danhmuc WHERE parent_id = ? ORDER BY madm");
                $stmt2->execute([$lv1['madm']]);
                $menu_lv2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                $link_lv1 = htmlspecialchars($href_map[$lv1['tendm']] ?? '#');
                ?>

                <li class="nav-item">
                    <a href="<?= $link_lv1 ?>">
                        <?= htmlspecialchars($lv1['tendm']) ?>
                        <?php if ($menu_lv2): ?><i class="fas fa-chevron-down"></i><?php endif; ?>
                    </a>

                    <?php if ($menu_lv2): ?>
                        <div class="dropdown-menu">
                            <?php foreach ($menu_lv2 as $lv2): ?>
                                <?php
                                $lv2_tendm = $lv2['tendm'];
                                $lv2_link = $base_url . '/base.php?page=danhmuc&madm=' . $lv2['madm'];
                                
                                if ($lv1['tendm'] == 'Thuốc') {
                                    if (strpos($lv2_tendm, 'Tra cứu thuốc') !== false) {
                                        $lv2_link = $base_url . '/base.php?page=search&filter=thuoc';
                                    } elseif (strpos($lv2_tendm, 'Tra cứu dược chất') !== false) {
                                        $lv2_link = $base_url . '/base.php?page=search&filter=duocchat';
                                    } elseif (strpos($lv2_tendm, 'Tra cứu dược liệu') !== false) {
                                        $lv2_link = $base_url . '/base.php?page=search&filter=duoclieu';
                                    } else {
                                        $lv2_link = $base_url . '/base.php?page=danhmuc&madm=' . $lv2['madm'];
                                    }
                                }
                                ?>
                                <a href="<?= $lv2_link ?>" class="dropdown-item">
                                    <?php if (!empty($lv2['img_url'])): ?>
                                        <img src="<?= $base_url ?>/<?= htmlspecialchars($lv2['img_url']) ?>" alt="<?= htmlspecialchars($lv2['tendm']) ?>">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($lv2['tendm']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
    body { font-family: 'Poppins', sans-serif; margin: 0; }
    a { text-decoration: none; color: inherit; }
    
    .header-bar { background-color: #f2f2f2; padding: 8px 0; font-size: 13px; color: #555; }
    .header-bar-content { width: 90%; max-width: 1400px; margin: auto; display: flex; justify-content: space-between; align-items: center; }
    .header-bar-left, .header-bar-right, .app-btn, .call-btn { display: flex; align-items: center; gap: 8px; }
    .header-bar-left a { font-weight: 500; }
    .header-bar-left strong { color: #004aad; margin-left: 4px; }
    .call-btn a { font-weight: 600; color: #d9534f; }
    
    .main-header { padding: 20px 0; border-bottom: 1px solid #e0e0e0; }
    .header-top { width: 90%; max-width: 1400px; margin: auto; display: flex; justify-content: space-between; align-items: center; gap: 30px; }
    .logo a { display: flex; align-items: center; gap: 8px; font-size: 32px; font-weight: 600; color: #004aad; }
    .logo img { width: 45px; height: auto; }
    
    .search-bar { flex-grow: 1; display: flex; border: 2px solid #004aad; border-radius: 30px; overflow: hidden; }
    .search-bar input { flex-grow: 1; border: none; outline: none; padding: 12px 20px; font-size: 14px; }
    .search-bar button { background-color: #004aad; border: none; color: white; padding: 0 20px; cursor: pointer; }
    
    .user-actions { display: flex; gap: 16px; align-items: center; font-size: 14px; font-weight: 500; color: #333; flex-wrap: wrap; justify-content: flex-end; }
    .user-actions > a, .user-actions > span { display: flex; align-items: center; gap: 8px; transition: color 0.3s; }
    .user-actions > a:hover { color: #004aad; }
    .user-name { color: #004aad; font-weight: 600; }
    
    /* === GIỎ HÀNG MINI STYLES === */
    .cart-wrapper { position: relative; }
    .cart-trigger { 
        display: flex; align-items: center; gap: 8px; 
        position: relative; cursor: pointer; 
        transition: color 0.3s;
    }
    .cart-trigger:hover { color: #004aad; }
    
    .cart-badge {
        position: absolute; top: -8px; right: -12px;
        background-color: #d9534f; color: white;
        border-radius: 50%; width: 20px; height: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 600;
    }
    
    .cart-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 15px);
        right: 0;
        width: 420px;
        padding-top: 14px;
        background: transparent;
        z-index: 1000;
    }
    
    .cart-dropdown-surface {
        position: relative;
        border-radius: 24px;
    }
    
    .cart-wrapper:hover .cart-dropdown,
    .cart-wrapper:focus-within .cart-dropdown,
    .cart-wrapper.open .cart-dropdown { display: block; }
    
    .cart-dropdown::before {
        content: '';
        position: absolute;
        top: 6px;
        right: 36px;
        width: 18px;
        height: 18px;
        background: linear-gradient(145deg, rgba(224, 247, 250, 0.95), rgba(179, 229, 252, 0.95));
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 12px 30px rgba(2, 132, 199, 0.25);
        transform: rotate(45deg);
        z-index: 0;
    }
    
    .cart-dropdown .effect-pills-content {
        border-radius: 20px;
    }
    
    .cart-dropdown-header {
        padding: 16px 20px; border-bottom: 1px solid #e0e0e0;
    }
    
    .cart-title {
        font-size: 14px; font-weight: 600; color: #666;
    }
    
    .cart-dropdown-body {
        max-height: 420px; overflow-y: auto; padding: 12px 0;
    }
    
    .cart-empty {
        padding: 40px 20px; text-align: center; color: #999;
    }
    
    .cart-empty i { font-size: 48px; margin-bottom: 12px; opacity: 0.3; }
    .cart-empty p { font-size: 14px; }
    
    .cart-item {
        display: flex; padding: 12px 20px; gap: 12px;
        align-items: flex-start;
        transition: background-color 0.2s;
    }
    
    .cart-item:hover { background-color: #f9f9f9; }
    
    .cart-item-img {
        flex-shrink: 0; 
        width: 56px;
        height: 56px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        overflow: hidden; 
        display: flex; 
        align-items: center; 
        justify-content: center;
    }
    
    .cart-item-img img {
        margin: 0; 
        max-width: 90%;
        max-height: 100%;
        object-fit: contain; 
        transform: translateX(-4px);
    }
    
    .cart-item-info { flex-grow: 1; min-width: 0; }
    
    .cart-item-name {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; font-size: 14px; font-weight: 500;
        color: #333; line-height: 1.4; margin-bottom: 8px;
    }
    
    .cart-item-name:hover { color: #004aad; }
    
    .cart-item-bottom {
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .cart-item-price .price {
        font-size: 14px; font-weight: 600; color: #004aad;
    }
    
    .cart-item-quantity {
        font-size: 13px; color: #666;
    }
    
    .cart-item-delete {
        flex-shrink: 0; background: none; border: none;
        color: #999; cursor: pointer; padding: 8px;
        transition: color 0.2s;
    }
    
    .cart-item-delete:hover { color: #d9534f; }
    
    .cart-dropdown-footer {
        padding: 16px 20px; border-top: 1px solid #e0e0e0;
    }
    
    .cart-summary {
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .cart-total-items {
        font-size: 12px; font-weight: 600; color: #666;
    }
    
    .btn-view-cart {
        background-color: #88afe1ff; color: white;
        padding: 10px 24px; border-radius: 6px;
        font-size: 14px; font-weight: 600;
        transition: background-color 0.3s;
    }
    
    .btn-view-cart:hover { background-color: #003580; }
    
    /* === MAIN NAV === */
    .main-nav { background-color: #004aad; color: white; }
    .nav-container { width: 90%; max-width: 1400px; margin: auto; }
    .nav-menu { list-style: none; margin: 0; padding: 0; display: flex; gap: 30px; }
    .nav-item { position: relative; }
    .nav-item > a {
        display: flex; align-items: center; gap: 6px;
        padding: 15px 0; font-weight: 500; font-size: 15px;
        transition: color 0.3s;
    }
    .nav-item > a i { font-size: 12px; }
    .nav-item:hover > a { color: #ffdd00; }
    
    .dropdown-menu {
        display: none; position: absolute; top: 100%; left: 0;
        background-color: white; color: #333;
        border-radius: 0 0 8px 8px; box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        padding: 10px 0; min-width: 280px; z-index: 1000;
    }
    .nav-item:hover .dropdown-menu { display: block; }
    
    .dropdown-item {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 20px; font-size: 14px; white-space: nowrap;
        transition: background-color 0.3s;
    }
    .dropdown-item:hover { background-color: #f5f5f5; color: #004aad; }
    .dropdown-item img { width: 24px; height: 24px; object-fit: contain; }

    .logout-alert {
        width: 90%;
        max-width: 1400px;
        margin: 10px auto;
        padding: 10px 14px;
        background: #ecfdf3;
        color: #166534;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        font-size: 14px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartWrapper = document.querySelector('.cart-wrapper');
    if (!cartWrapper) return;
    const cartTrigger = cartWrapper.querySelector('.cart-trigger');
    let autoCloseTimer = null;

    const openCartDropdown = (autoClose = false) => {
        cartWrapper.classList.add('open');
        if (autoClose) {
            clearTimeout(autoCloseTimer);
            autoCloseTimer = setTimeout(() => cartWrapper.classList.remove('open'), 4000);
        }
    };

    cartTrigger.addEventListener('click', function(e) {
        e.preventDefault();
        if (cartWrapper.classList.contains('open')) {
            cartWrapper.classList.remove('open');
        } else {
            openCartDropdown();
        }
    });

    document.addEventListener('click', function(e) {
        if (!cartWrapper.contains(e.target)) {
            cartWrapper.classList.remove('open');
        }
    });

    document.addEventListener('header-cart:open', function() {
        openCartDropdown(true);
    });

    window.showHeaderMiniCart = openCartDropdown;
});

function removeFromCart(id) {
    if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
        fetch('<?= $base_url ?>/cart_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove&id=' + encodeURIComponent(id)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>

<script>
  document.addEventListener('click', e => {
    const t = e.target.closest('[data-logout-btn]');
    if (t) {
      e.preventDefault();
      document.getElementById('logout-form')?.submit();
    }
  });
</script>

<?php if (!empty($IS_CART_PAGE)): ?>
<script>
  window.IS_BIG_CART_PAGE = true;
  document.documentElement.classList.add('is-cart-page');
</script>
<?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
