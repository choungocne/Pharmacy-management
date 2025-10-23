<?php
// --- Kết nối CSDL ---
$pdo = new PDO(
  'mysql:host=localhost;dbname=nhathuocantam;charset=utf8mb4','root','',
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
);


// --- Lấy danh mục cấp 1 ---
$sql_lv1 = "SELECT * FROM danhmuc WHERE cap = 1 ORDER BY madm";
$stmt1 = $pdo->query($sql_lv1);
$menu_lv1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
$href_map = [
    'Thực phẩm chức năng' => 'base.php?page=thucpham',
    'Thuốc'               => 'base.php?page=search',
    'Thiết bị y tế'       => 'base.php?page=thietbi',
    'Tra cứu bệnh'        => 'base.php?page=search',
    'Bệnh & Góc sức khỏe'=> 'base.php?page=suckhoe',
    'Hệ thống nhà thuốc'  => 'base.php?page=about'
];
?>




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
            <a href="<?= $base_url ?>/"><img src="<?= $base_url ?>/static/img/logo.png" alt="Logo"><span>An Tâm</span></a>
        </div>
        <div class="search-bar">
            <input type="text" placeholder="Tìm kiếm sản phẩm, thuốc, bệnh..."><button><i class="fas fa-search"></i></button>
        </div>
        <div class="user-actions">
            <a href="/login.php"><i class="fas fa-user"></i> Đăng nhập</a>
            <a href="#"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a>
            <a href="#"><i class="fas fa-headset"></i> Hỗ trợ</a>
        </div>
    </div>
</header>

<nav class="main-nav">
    <div class="nav-container">
        <ul class="nav-menu">
            <?php foreach ($menu_lv1 as $lv1): ?>
                <?php
                // Lấy danh mục cấp 2
                $stmt2 = $pdo->prepare("SELECT * FROM danhmuc WHERE parent_id = ? ORDER BY madm");
                $stmt2->execute([$lv1['madm']]);
                $menu_lv2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <li class="nav-item">
                    <a href="<?= htmlspecialchars($href_map[$lv1['tendm']] ?? '#') ?>">
                        <?= htmlspecialchars($lv1['tendm']) ?>
                        <?php if ($menu_lv2): ?><i class="fas fa-chevron-down"></i><?php endif; ?>
                    </a>

                    <?php if ($menu_lv2): ?>
                        <div class="dropdown-menu">
                            <?php foreach ($menu_lv2 as $lv2): ?>
                               
                                <a href="<?= htmlspecialchars($link_lv2) ?>" class="dropdown-item">
                                    <?php if (!empty($lv2['img_url'])): ?>
                                        <img src="<?= htmlspecialchars($lv2['img_url']) ?>" alt="<?= htmlspecialchars($lv2['tendm']) ?>">
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
    .user-actions { display: flex; gap: 25px; font-size: 14px; font-weight: 500; color: #333; }
    .user-actions a { display: flex; align-items: center; gap: 8px; transition: color 0.3s; }
    .user-actions a:hover { color: #004aad; }
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
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">