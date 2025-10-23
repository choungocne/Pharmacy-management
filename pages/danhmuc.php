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
$is_cap1 = true; // Giả định ban đầu là cấp 1, cần kiểm tra dựa trên code

// Giả sử code cho cấp 1 là uppercase không có dấu gạch dưới, cấp 2 có
if (strpos($code, '_') === false) {
    $cap_level = 'cap1';
} else {
    $cap_level = 'cap2';
    $is_cap1 = false;
}

try {
    if ($is_cap1) { // Nếu là danh mục cha (cấp 1)
        $category_name = str_replace('_', ' ', $code);
        
        // Lấy sản phẩm cho cấp 1
        $stmt_prod = $pdo->prepare("
            SELECT sp.*, dv.tendv AS donvitinh FROM sanpham sp
            LEFT JOIN donvitinh dv ON sp.madv = dv.madv
            WHERE JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap1')) = :cap1 AND sp.trangthai = 1
            GROUP BY sp.masp
        ");
        $stmt_prod->execute(['cap1' => str_replace('_', ' ', $code)]);
        $products = $stmt_prod->fetchAll();

        // Lấy danh mục con (Cấp 2)
        $stmt_sub = $pdo->prepare("
            SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap2')) AS tendm2, COUNT(DISTINCT sp.masp) as product_count 
            FROM sanpham sp
            WHERE JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap1')) = :cap1 AND sp.trangthai = 1
            GROUP BY tendm2
        ");
        $stmt_sub->execute(['cap1' => str_replace('_', ' ', $code)]);
        $sub_categories = $stmt_sub->fetchAll();
        
        // Lấy thương hiệu cho Cấp 1
        $stmt_brands = $pdo->prepare("
            SELECT DISTINCT th.math, th.tenth 
            FROM thuonghieu th
            JOIN sanpham sp ON sp.math = th.math
            WHERE JSON_UNQUOTE(JSON_EXTRACT(sp.danhmuc, '$[0].cap1')) = :cap1 AND sp.trangthai = 1
            ORDER BY th.tenth
        ");
        $stmt_brands->execute(['cap1' => str_replace('_', ' ', $code)]);
        $brands = $stmt_brands->fetchAll();
        
        // *** MỚI: Lấy Đối tượng sử dụng cho Cấp 1 ***
        $stmt_doituong = $pdo->prepare("
            SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(doituong, '$[*]')) AS doituong
            FROM sanpham sp
            WHERE JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap1')) = :cap1 AND sp.trangthai = 1 AND JSON_LENGTH(doituong) > 0
            ORDER BY doituong
        ");
        $stmt_doituong->execute(['cap1' => str_replace('_', ' ', $code)]);
        $doituong_list = $stmt_doituong->fetchAll(PDO::FETCH_COLUMN);

        // *** MỚI: Lấy Mùi vị cho Cấp 1 ***
        $stmt_muivi = $pdo->prepare("
            SELECT DISTINCT mv.tenmv
            FROM muivi mv
            JOIN sanpham sp ON sp.mamv = mv.mamv
            WHERE JSON_UNQUOTE(JSON_EXTRACT(sp.danhmuc, '$[0].cap1')) = :cap1 AND sp.trangthai = 1 AND mv.tenmv IS NOT NULL
            ORDER BY mv.tenmv
        ");
        $stmt_muivi->execute(['cap1' => str_replace('_', ' ', $code)]);
        $muivi_list = $stmt_muivi->fetchAll(PDO::FETCH_COLUMN);

    } else { // Nếu là danh mục con (cấp 2)
        $category_name = str_replace('_', ' ', $code);
        
        // Lấy sản phẩm cho cấp 2
        $stmt_prod = $pdo->prepare("
            SELECT sp.*, dv.tendv AS donvitinh FROM sanpham sp
            LEFT JOIN donvitinh dv ON sp.madv = dv.madv
            WHERE JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap2')) = :cap2 AND sp.trangthai = 1
            GROUP BY sp.masp
        ");
        $stmt_prod->execute(['cap2' => str_replace('_', ' ', $code)]);
        $products = $stmt_prod->fetchAll();

        // Lấy danh mục con cấp 3 nếu có
        $stmt_sub_lvl3 = $pdo->prepare("
            SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap3')) AS tendm3, COUNT(DISTINCT sp.masp) as product_count 
            FROM sanpham sp
            WHERE JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap2')) = :cap2 AND sp.trangthai = 1 AND JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap3')) IS NOT NULL
            GROUP BY tendm3
        ");
        $stmt_sub_lvl3->execute(['cap2' => str_replace('_', ' ', $code)]);
        $sub_categories_lvl3 = $stmt_sub_lvl3->fetchAll();
        
        // Lấy thương hiệu cho Cấp 2
        $stmt_brands = $pdo->prepare("
            SELECT DISTINCT th.math, th.tenth 
            FROM thuonghieu th
            JOIN sanpham sp ON sp.math = th.math
            WHERE JSON_UNQUOTE(JSON_EXTRACT(sp.danhmuc, '$[0].cap2')) = :cap2 AND sp.trangthai = 1
            ORDER BY th.tenth
        ");
        $stmt_brands->execute(['cap2' => str_replace('_', ' ', $code)]);
        $brands = $stmt_brands->fetchAll();
        
        // *** MỚI: Lấy Đối tượng sử dụng cho Cấp 2 ***
        $stmt_doituong = $pdo->prepare("
            SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(doituong, '$[*]')) AS doituong
            FROM sanpham sp
            WHERE JSON_UNQUOTE(JSON_EXTRACT(danhmuc, '$[0].cap2')) = :cap2 AND sp.trangthai = 1 AND JSON_LENGTH(doituong) > 0
            ORDER BY doituong
        ");
        $stmt_doituong->execute(['cap2' => str_replace('_', ' ', $code)]);
        $doituong_list = $stmt_doituong->fetchAll(PDO::FETCH_COLUMN);

        // *** MỚI: Lấy Mùi vị cho Cấp 2 ***
        $stmt_muivi = $pdo->prepare("
            SELECT DISTINCT mv.tenmv
            FROM muivi mv
            JOIN sanpham sp ON sp.mamv = mv.mamv
            WHERE JSON_UNQUOTE(JSON_EXTRACT(sp.danhmuc, '$[0].cap2')) = :cap2 AND sp.trangthai = 1 AND mv.tenmv IS NOT NULL
            ORDER BY mv.tenmv
        ");
        $stmt_muivi->execute(['cap2' => str_replace('_', ' ', $code)]);
        $muivi_list = $stmt_muivi->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>

<div class="container">
    <section class="product-section">
        <div class="product-layout">
            <aside class="filter-panel">
                <h3><i class="fas fa-filter"></i> Bộ lọc nâng cao</h3>
                
                <?php if ($is_cap1 && !empty($sub_categories)): ?>
                <div class="sub-categories">
                    <h3><?php echo htmlspecialchars($category_name); ?></h3>
                    <div class="sub-grid">
                        <?php foreach ($sub_categories as $sub): ?>
                            <?php $sub_code = strtoupper(preg_replace('/\s+/', '_', $sub['tendm2'])); ?>
                            <a href="<?= $base_url ?>/base.php?page=danhmuc&code=<?= urlencode($sub_code) ?>" class="sub-item">
                                <div class="sub-icon">
                                    <img src="<?= $base_url ?>/static/img/default_icon.png" alt=""> <!-- Thay bằng icon nếu có -->
                                </div>
                                <div class="sub-info">
                                    <h4><?= htmlspecialchars($sub['tendm2']) ?></h4>
                                    <p><?= $sub['product_count'] ?> sản phẩm</p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$is_cap1 && !empty($sub_categories_lvl3)): ?>
                <div class="sub-categories-lvl3">
                    <h3>Danh mục con cấp 3</h3>
                    <ul>
                        <?php foreach ($sub_categories_lvl3 as $lvl3): ?>
                            <li><?= htmlspecialchars($lvl3['tendm3']) ?> (<?= $lvl3['product_count'] ?> sản phẩm)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="filter-group">
                    <h4>Giá bán</h4>
                    <div class="filter-options">
                        <label><input type="radio" name="price_range" value="all" checked> Tất cả</label>
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
