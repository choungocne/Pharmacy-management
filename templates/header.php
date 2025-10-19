<?php
// File: templates/header.php

// 1. Kết nối CSDL và lấy dữ liệu menu (logic giữ nguyên)
include_once __DIR__ . '/../db.php';

$menu_items = [];
try {
    $pdo = pdo();
    $stmt1 = $pdo->query("SELECT madm1, code, tendm1 FROM danhmuc_cap1 ORDER BY madm1 ASC");
    $danhmuc_cap1 = $stmt1->fetchAll();
    $stmt2 = $pdo->query("SELECT code, tendm2, icon_url, madm1 FROM danhmuc_cap2 ORDER BY tendm2 ASC");
    $danhmuc_cap2 = $stmt2->fetchAll();

    foreach ($danhmuc_cap1 as $dm1) {
        $children = [];
        foreach ($danhmuc_cap2 as $dm2) {
            if ($dm2['madm1'] == $dm1['madm1']) {
                $children[] = ['code' => $dm2['code'], 'name' => $dm2['tendm2'], 'icon' => $dm2['icon_url']];
            }
        }
        $menu_items[] = ['code' => $dm1['code'], 'name' => $dm1['tendm1'], 'children' => $children];
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn menu: " . $e->getMessage());
}
$base_url = '/Pharmacy-management';
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
            <?php foreach ($menu_items as $item): ?>
                <li class="nav-item">
                    <?php
                        // Link mặc định sẽ trỏ đến trang danh mục chung
                        $link = "base.php?page=danhmuc&code=" . urlencode($item['code']);

                        // Gán link tùy chỉnh cho các trang đặc biệt
                        if ($item['code'] === 'TP_CHUC_NANG') $link = "base.php?page=thucpham";
                        elseif ($item['code'] === 'HE_THONG_NT') $link = "base.php?page=about";
                        elseif ($item['code'] === 'THUOC') $link = "base.php?page=search";
                        elseif ($item['code'] === 'THIET_BI_Y_TE') $link = "base.php?page=thietbi";
                        elseif ($item['code'] === 'BENH_GOC_SK') $link = "base.php?page=suckhoe";
                        
                        // Thêm các trang khác nếu có, ví dụ:
                        // elseif ($item['code'] === 'THUOC') $link = "base.php?page=thuoc";
                    ?>
                    <a href="<?= $base_url ?>/<?= $link ?>">
                        <?= htmlspecialchars($item['name']) ?>
                        <?php if (!empty($item['children'])): ?><i class="fas fa-chevron-down"></i><?php endif; ?>
                    </a>
                    <?php if (!empty($item['children'])): ?>
                        <div class="dropdown-menu">
                            <?php foreach ($item['children'] as $child): ?>
                                <a href="<?= $base_url ?>/base.php?page=danhmuc&code=<?= urlencode($child['code']) ?>" class="dropdown-item">
                                    <?php if (!empty($child['icon'])): ?><img src="<?= $base_url ?>/<?= $child['icon'] ?>" alt=""><?php endif; ?>
                                    <span><?= htmlspecialchars($child['name']) ?></span>
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