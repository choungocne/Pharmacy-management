<?php
// File: pages/danhmuc.php

// 1. KẾT NỐI CSDL VÀ LẤY DỮ LIỆU
// ===================================
include_once __DIR__ . '/../db.php'; // Sử dụng file kết nối chung
$pdo = pdo();
$base_url = '/Pharmacy-management';

// Lấy mã danh mục từ URL (ví dụ: ?code=TP_CHUC_NANG)
$code = $_GET['code'] ?? '';
if (empty($code)) {
    die("Không tìm thấy mã danh mục.");
}

// 2. XÁC ĐỊNH DANH MỤC VÀ TRUY VẤN SẢN PHẨM
// ===========================================
$category_name = "Danh mục không tồn tại";
$products = [];
$sub_categories = []; // Dùng khi ở Cấp 1
$sub_categories_lvl3 = []; // Dùng khi ở Cấp 2
$brands = []; // Bộ lọc thương hiệu
$doituong_list = []; // *** MỚI: Bộ lọc đối tượng ***
$muivi_list = []; // *** MỚI: Bộ lọc mùi vị ***

// Kiểm tra xem đây là danh mục cấp 1 hay cấp 2
$stmt_cat1 = $pdo->prepare("SELECT madm1, tendm1 FROM danhmuc_cap1 WHERE code = :code");
$stmt_cat1->execute(['code' => $code]);
$category1 = $stmt_cat1->fetch();

$stmt_cat2 = $pdo->prepare("SELECT madm2, tendm2 FROM danhmuc_cap2 WHERE code = :code");
$stmt_cat2->execute(['code' => $code]);
$category2 = $stmt_cat2->fetch();

try {
    if ($category1) { // Nếu là danh mục cha (cấp 1)
        $current_cat_id = $category1['madm1'];
        $category_name = $category1['tendm1'];
        
        // Lấy sản phẩm
        $stmt_prod = $pdo->prepare("
            SELECT sp.*, dv.tendv FROM sanpham sp
            LEFT JOIN donvitinh dv ON sp.madv = dv.madv
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            JOIN danhmuc_cap2 dm2 ON dm3.madm2 = dm2.madm2
            WHERE dm2.madm1 = :id AND sp.trangthai = 1
            GROUP BY sp.masp
        ");
        $stmt_prod->execute(['id' => $current_cat_id]);
        $products = $stmt_prod->fetchAll();

        // Lấy danh mục con (Cấp 2)
        $stmt_sub = $pdo->prepare("
            SELECT dm2.tendm2, dm2.code, di.url AS icon_url, COUNT(DISTINCT sp.masp) as product_count 
            FROM danhmuc_cap2 dm2
            LEFT JOIN dm2_image dmi ON dmi.madm2 = dm2.madm2
            LEFT JOIN media_file di ON dmi.file_id = di.id
            LEFT JOIN danhmuc_cap3 dm3 ON dm3.madm2 = dm2.madm2
            LEFT JOIN sp_dm3 j ON j.madm3 = dm3.madm3
            LEFT JOIN sanpham sp ON sp.masp = j.masp AND sp.trangthai = 1
            WHERE dm2.madm1 = :id
            GROUP BY dm2.madm2, dm2.tendm2, dm2.code, di.url
        ");
        $stmt_sub->execute(['id' => $current_cat_id]);
        $sub_categories = $stmt_sub->fetchAll();
        
        // Lấy thương hiệu cho Cấp 1
        $stmt_brands = $pdo->prepare("
            SELECT DISTINCT th.math, th.tenth 
            FROM thuonghieu th
            JOIN sanpham sp ON sp.math = th.math
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            JOIN danhmuc_cap2 dm2 ON dm3.madm2 = dm2.madm2
            WHERE dm2.madm1 = :id AND sp.trangthai = 1
            ORDER BY th.tenth
        ");
        $stmt_brands->execute(['id' => $current_cat_id]);
        $brands = $stmt_brands->fetchAll();
        
        // *** MỚI: Lấy Đối tượng sử dụng cho Cấp 1 ***
        $stmt_doituong = $pdo->prepare("
            SELECT DISTINCT sp.doituong
            FROM sanpham sp
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            JOIN danhmuc_cap2 dm2 ON dm3.madm2 = dm2.madm2
            WHERE dm2.madm1 = :id AND sp.trangthai = 1 AND sp.doituong IS NOT NULL AND sp.doituong != ''
            ORDER BY sp.doituong
        ");
        $stmt_doituong->execute(['id' => $current_cat_id]);
        $doituong_list = $stmt_doituong->fetchAll(PDO::FETCH_COLUMN);

        // *** MỚI: Lấy Mùi vị cho Cấp 1 ***
        $stmt_muivi = $pdo->prepare("
            SELECT DISTINCT sp.muivi
            FROM sanpham sp
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            JOIN danhmuc_cap2 dm2 ON dm3.madm2 = dm2.madm2
            WHERE dm2.madm1 = :id AND sp.trangthai = 1 AND sp.muivi IS NOT NULL AND sp.muivi != ''
            ORDER BY sp.muivi
        ");
        $stmt_muivi->execute(['id' => $current_cat_id]);
        $muivi_list = $stmt_muivi->fetchAll(PDO::FETCH_COLUMN);

    } elseif ($category2) { // Nếu là danh mục con (cấp 2)
        $current_cat_id = $category2['madm2'];
        $category_name = $category2['tendm2'];
        
        // Lấy sản phẩm
        $stmt_prod = $pdo->prepare("
            SELECT sp.*, dv.tendv FROM sanpham sp
            LEFT JOIN donvitinh dv ON sp.madv = dv.madv
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            WHERE dm3.madm2 = :id AND sp.trangthai = 1
            GROUP BY sp.masp
        ");
        $stmt_prod->execute(['id' => $current_cat_id]);
        $products = $stmt_prod->fetchAll();
        
        // Lấy danh mục Cấp 3 làm bộ lọc
        $stmt_sub3 = $pdo->prepare("
            SELECT madm3, tendm3, code 
            FROM danhmuc_cap3 
            WHERE madm2 = :id 
            ORDER BY tendm3
        ");
        $stmt_sub3->execute(['id' => $current_cat_id]);
        $sub_categories_lvl3 = $stmt_sub3->fetchAll();
        
        // Lấy thương hiệu cho Cấp 2
        $stmt_brands = $pdo->prepare("
            SELECT DISTINCT th.math, th.tenth
            FROM thuonghieu th
            JOIN sanpham sp ON sp.math = th.math
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            WHERE dm3.madm2 = :id AND sp.trangthai = 1
            ORDER BY th.tenth
        ");
        $stmt_brands->execute(['id' => $current_cat_id]);
        $brands = $stmt_brands->fetchAll();
        
        // *** MỚI: Lấy Đối tượng sử dụng cho Cấp 2 ***
        $stmt_doituong = $pdo->prepare("
            SELECT DISTINCT sp.doituong
            FROM sanpham sp
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            WHERE dm3.madm2 = :id AND sp.trangthai = 1 AND sp.doituong IS NOT NULL AND sp.doituong != ''
            ORDER BY sp.doituong
        ");
        $stmt_doituong->execute(['id' => $current_cat_id]);
        $doituong_list = $stmt_doituong->fetchAll(PDO::FETCH_COLUMN);

        // *** MỚI: Lấy Mùi vị cho Cấp 2 ***
        $stmt_muivi = $pdo->prepare("
            SELECT DISTINCT sp.muivi
            FROM sanpham sp
            JOIN sp_dm3 j ON sp.masp = j.masp
            JOIN danhmuc_cap3 dm3 ON j.madm3 = dm3.madm3
            WHERE dm3.madm2 = :id AND sp.trangthai = 1 AND sp.muivi IS NOT NULL AND sp.muivi != ''
            ORDER BY sp.muivi
        ");
        $stmt_muivi->execute(['id' => $current_cat_id]);
        $muivi_list = $stmt_muivi->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn CSDL: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/static/css/product.css">

<div class="container">
    <section class="featured-categories-section">
        <div class="section-header">
            <h2><?= htmlspecialchars($category_name) ?></h2>
        </div>
        
        <?php if (!empty($sub_categories)): // Chỉ hiển thị mục này nếu là danh mục cha (Cấp 1) ?>
        <div class="categories-grid">
            <?php foreach ($sub_categories as $sub_cat): ?>
                <a href="<?= $base_url ?>/base.php?page=danhmuc&code=<?= $sub_cat['code'] ?>" class="category-item">
                    <div class="category-icon">
                        <?php if(!empty($sub_cat['icon_url'])): ?>
                            <img src="<?= $base_url ?><?= htmlspecialchars($sub_cat['icon_url']) ?>" alt="<?= htmlspecialchars($sub_cat['tendm2']) ?>">
                        <?php else: ?>
                            <i class="fas fa-tag"></i> 
                        <?php endif; ?>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name"><?= htmlspecialchars($sub_cat['tendm2']) ?></h3>
                        <span class="category-count"><?= $sub_cat['product_count'] ?> sản phẩm</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="product-wrapper">
        <div class="product-layout">
            <aside class="filter-panel">
                <h3><i class="fas fa-filter"></i> Bộ lọc</h3>

                <?php // Hiển thị bộ lọc Cấp 3 NẾU đang ở trang Cấp 2 ?>
                <?php if (!empty($sub_categories_lvl3)): ?>
                <div class="filter-group">
                    <h4>Danh mục chi tiết</h4>
                    <div class="filter-options">
                        <label><input type="checkbox" name="dm3_all" checked> Tất cả</label>
                        <?php foreach ($sub_categories_lvl3 as $cat3): ?>
                        <label><input type="checkbox" name="dm3[]" value="<?= $cat3['code'] ?>"> <?= htmlspecialchars($cat3['tendm3']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // BỘ LỌC GIÁ BÁN (TĨNH) ?>
                <div class="filter-group">
                    <h4>Khoảng giá</h4>
                    <div class="filter-options">
                        <label><input type="radio" name="price_range" value="all" checked> Tất cả</Ghi>
                        <label><input type="radio" name="price_range" value="0-100000"> Dưới 100.000đ</label>
                        <label><input type="radio" name="price_range" value="100000-300000"> 100.000đ - 300.000đ</label>
                        <label><input type="radio" name="price_range" value="300000-500000"> 300.000đ - 500.000đ</label>
                        <label><input type="radio" name="price_range" value="500000-99999999"> Trên 500.000đ</label>
                    </div>
                </div>

                <?php // BỘ LỌC THƯƠNG HIỆU (ĐỘNG) ?>
                <?php if (!empty($brands)): ?>
                <div class="filter-group">
                    <h4>Thương hiệu</h4>
                    <div class="filter-options">
                        <label><input type="checkbox" name="brand_all" checked> Tất cả</label>
                        <?php foreach ($brands as $brand): ?>
                        <label><input type="checkbox" name="brand[]" value="<?= $brand['math'] ?>"> <?= htmlspecialchars($brand['tenth']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php // *** PHẦN MỚI: BỘ LỌC ĐỐI TƯỢNG (ĐỘNG) *** ?>
                <?php if (!empty($doituong_list)): ?>
                <div class="filter-group">
                    <h4>Đối tượng sử dụng</h4>
                    <div class="filter-options">
                        <label><input type="checkbox" name="doituong_all" checked> Tất cả</label>
                        <?php foreach ($doituong_list as $doituong): ?>
                        <label><input type="checkbox" name="doituong[]" value="<?= htmlspecialchars($doituong) ?>"> <?= htmlspecialchars($doituong) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php // *** PHẦN MỚI: BỘ LỌC MÙI VỊ (ĐỘNG) *** ?>
                <?php if (!empty($muivi_list)): ?>
                <div class="filter-group">
                    <h4>Mùi vị</h4>
                    <div class="filter-options">
                        <label><input type="checkbox" name="muivi_all" checked> Tất cả</label>
                        <?php foreach ($muivi_list as $muivi): ?>
                        <label><input type="checkbox" name="muivi[]" value="<?= htmlspecialchars($muivi) ?>"> <?= htmlspecialchars($muivi) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </aside>
            <div class="product-content">
                <div class="product-header">
                    <div class="title">
                        <h2>Danh sách sản phẩm</h2>
                        <p class="note">Tìm thấy <?= count($products) ?> sản phẩm.</p>
                    </div>
                </div>
                <div class="product-grid">
                    <?php if (empty($products)): ?>
                        <p>Chưa có sản phẩm nào trong danh mục này.</p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <a href="<?= $base_url ?>/base.php?page=product_detail&id=<?= $product['masp'] ?>" class="product-card">
                                <?php if ($product['giagiam'] > 0 && $product['giaban'] > 0): ?>
                                    <span class="discount-badge">-<?= round(($product['giagiam'] / $product['giaban']) * 100) ?>%</span>
                                <?php endif; ?>
                                <img src="<?= $base_url ?><?= htmlspecialchars($product['hinhsp'] ?? '/static/img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($product['tensp']) ?>">
                                <h3><?= htmlspecialchars($product['tensp']) ?></h3>
                                <p class="price">
                                    <?= money_vn($product['giaban'] - $product['giagiam']) ?> đ
                                    <?php if ($product['giagiam'] > 0): ?>
                                        <span class="old-price"><?= money_vn($product['giaban']) ?>đ</span>
                                    <?php endif; ?>
                                </p>
                                <button class="btn-buy">Xem chi tiết</button>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>