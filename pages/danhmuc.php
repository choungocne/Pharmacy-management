<?php
// File: pages/danhmuc.php
// PHIÊN BẢN AJAX FILTER

// 1. KẾT NỐI CSDL VÀ CÁC HÀM HỖ TRỢ
// ===================================
include_once __DIR__ . '/../db.php'; // Sử dụng file kết nối chung
if (!function_exists('pdo')) {
    function pdo() {
        return new PDO(
          'mysql:host=localhost;dbname=nhathuocantam;charset=utf8mb4','root','',
          [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
        );
    }
}
if (!function_exists('money_vn')) {
    function money_vn($price) {
        return number_format($price, 0, ',', '.');
    }
}

$pdo = pdo();
$base_url = '/Pharmacy-management';
$madm_cap2 = $_GET['madm'] ?? 0;

// 2. LẤY THÔNG TIN DANH MỤC CẤP 2 HIỆN TẠI
// ===================================
$stmt_dm2 = $pdo->prepare("SELECT * FROM danhmuc WHERE madm = ? AND cap = 2");
$stmt_dm2->execute([$madm_cap2]);
$dm2 = $stmt_dm2->fetch(PDO::FETCH_ASSOC);

if (!$dm2) {
    echo "<h3>Danh mục không tồn tại</h3>";
    exit;
}

// 3. LẤY DANH MỤC CẤP 3
// =======================================================
$stmt_lv3 = $pdo->prepare("SELECT * FROM danhmuc WHERE parent_id = ? AND cap = 3 ORDER BY tendm");
$stmt_lv3->execute([$madm_cap2]);
$sub_categories_lvl3 = $stmt_lv3->fetchAll(PDO::FETCH_ASSOC);
$madm_cap3_list = array_column($sub_categories_lvl3, 'madm');

// 4. LẤY SỐ LƯỢNG SẢN PHẨM CHO MỖI DANH MỤC CẤP 3
// =======================================================
$product_counts = []; // Khởi tạo mảng
if (!empty($madm_cap3_list)) {
    $placeholders_count = implode(',', array_fill(0, count($madm_cap3_list), '?'));
    $sql_count = "SELECT madm, COUNT(*) as product_count FROM sanpham WHERE madm IN ($placeholders_count) GROUP BY madm";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($madm_cap3_list);
    $product_counts = $stmt_count->fetchAll(PDO::FETCH_KEY_PAIR);
}

// 5. LẤY TẤT CẢ SẢN PHẨM (CHỈ LẤY LẦN ĐẦU)
// =======================================================
$products = []; // Luôn khởi tạo là mảng rỗng
if (!empty($madm_cap3_list)) {
    $placeholders_prod = implode(',', array_fill(0, count($madm_cap3_list), '?'));
    $sql_prod = "SELECT * FROM sanpham WHERE madm IN ($placeholders_prod)";
    $stmt_prod = $pdo->prepare($sql_prod);
    $stmt_prod->execute($madm_cap3_list);
    $products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);
}

// 6. TẠO BỘ LỌC ĐỘNG
// =======================================================

// --- LỌC THƯƠNG HIỆU ---
$brands = []; // Khởi tạo mảng
$product_math_ids = array_values(array_unique(array_filter(array_column($products, 'math'))));
if (!empty($product_math_ids)) {
    $placeholders_brand = implode(',', array_fill(0, count($product_math_ids), '?'));
    $sql_brand = "SELECT math, tenth FROM thuonghieu WHERE math IN ($placeholders_brand) ORDER BY tenth";
    $stmt_brand = $pdo->prepare($sql_brand);
    $stmt_brand->execute($product_math_ids);
    $brands = $stmt_brand->fetchAll(PDO::FETCH_ASSOC);
}

// --- LỌC ĐỐI TƯỢNG (từ cột JSON) ---
$doituong_list = []; // Khởi tạo mảng
foreach ($products as $product) {
    if (!empty($product['doituong'])) {
        $doituong_data = json_decode($product['doituong'], true);
        if (is_array($doituong_data)) {
            $doituong_list = array_merge($doituong_list, $doituong_data);
        }
    }
}
$doituong_list = array_unique(array_filter($doituong_list));
sort($doituong_list);

// --- LỌC MÙI VỊ (từ bảng muivi) ---
$muivi_list = []; // Khởi tạo mảng
$product_mamv_ids = array_values(array_unique(array_filter(array_column($products, 'mamv'))));
if (!empty($product_mamv_ids)) {
    // *** SỬA ĐỔI: Lấy cả ID (mamv) và TÊN (tenmv) ***
    $placeholders_mv = implode(',', array_fill(0, count($product_mamv_ids), '?'));
    $sql_mv = "SELECT mamv, tenmv FROM muivi WHERE mamv IN ($placeholders_mv) ORDER BY tenmv";
    $stmt_mv = $pdo->prepare($sql_mv);
    $stmt_mv->execute($product_mamv_ids);
    $muivi_list = $stmt_mv->fetchAll(PDO::FETCH_ASSOC); // <<< Sửa từ FETCH_COLUMN
}

?>

<style>
    /* --- CÀI ĐẶT CHUNG --- */
    .container {
        width: 90%;
        max-width: 1400px;
        margin: 20px auto;
    }
    
    /* --- BỐ CỤC CHIA ĐÔI --- */
    .product-layout {
        display: grid;
        grid-template-columns: 280px 1fr; 
        gap: 24px;
        margin-top: 20px;
    }

    /* --- CSS CHO CỘT BỘ LỌC (ASIDE) --- */
    .filter-panel {
        background-color: #fff;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        align-self: start; 
    }
    .filter-panel h3 {
        font-size: 16px;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 16px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 10px;
    }
    .filter-group {
        margin-bottom: 20px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 20px;
    }
    .filter-group:last-child {
        border-bottom: none;
        padding-bottom: 0;
        margin-bottom: 0;
    }
    .filter-group h4 {
        font-size: 15px;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 12px;
    }
    .filter-options label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        color: #333;
        cursor: pointer;
    }
    .filter-options input {
        margin-right: 8px;
        transform: translateY(1px);
    }
    .filter-options.scrollable {
        max-height: 200px;
        overflow-y: auto;
    }

    /* --- LƯỚI DANH MỤC CẤP 3 (Ở TRÊN CÙNG) --- */
    .category-grid-container {
        margin-bottom: 24px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 20px;
    }
    .category-grid-container h2 {
        font-size: 20px;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 16px;
        color: #333;
    }
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }
    .category-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid #e0e0f0;
        border-radius: 8px;
        transition: box-shadow 0.3s, border-color 0.3s;
        text-decoration: none;
        color: inherit;
    }
    .category-item:hover {
        border-color: #004aad;
        box-shadow: 0 4px 12px rgba(0, 74, 173, 0.1);
    }
    .category-icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        background-color: #f4f4f4;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .category-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .category-info h4 {
        font-size: 15px;
        font-weight: 500;
        margin: 0 0 4px 0;
        color: #111;
    }
    .category-info p {
        font-size: 13px;
        color: #777;
        margin: 0;
    }

    /* --- CỘT SẢN PHẨM (BÊN PHẢI) --- */
    .product-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        background-color: #fff;
        border-radius: 8px;
        margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .product-header .title h2 {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }
    .product-header .title p {
        font-size: 14px;
        color: #777;
        margin: 4px 0 0 0;
    }
    
    /* --- LƯỚI SẢN PHẨM --- */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        /* Thêm hiệu ứng khi tải lại */
        transition: opacity 0.3s;
    }
    /* Thêm class này khi đang tải */
    .product-grid.loading {
        opacity: 0.5;
    }

    .product-card {
        background-color: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
        text-decoration: none;
        color: #333;
        transition: box-shadow 0.3s;
        position: relative;
        padding: 12px;
        display: flex;
        flex-direction: column;
    }
    .product-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .product-card img {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: contain;
        margin-bottom: 12px;
    }
    .product-card h3 {
        font-size: 14px;
        font-weight: 500;
        margin: 0 0 8px 0;
        height: 40px; /* 2 dòng */
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .product-card .price {
        font-size: 16px;
        font-weight: 600;
        color: #d9534f;
        margin: 0 0 4px 0;
    }
    .product-card .old-price {
        font-size: 13px;
        color: #777;
        text-decoration: line-through;
        margin-left: 8px;
    }
    .product-card .discount-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: #d9534f;
        color: white;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 6px;
        border-radius: 4px;
    }
    .product-card .btn-buy {
        display: block;
        width: 100%;
        background-color: #004aad;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        text-align: center;
        margin-top: auto; /* Đẩy nút xuống dưới cùng */
    }
</style>

<div class="container">

    <?php if (!empty($sub_categories_lvl3)): ?>
    <div class="category-grid-container">
        <h2><?= htmlspecialchars($dm2['tendm']) ?></h2>
        <div class="category-grid">
            <?php foreach ($sub_categories_lvl3 as $lvl3): ?>
                <?php $count = $product_counts[$lvl3['madm']] ?? 0; ?>
                <a href="#" class="category-item">
                    <div class="category-icon">
                        <img src="<?= htmlspecialchars($lvl3['img_url'] ?? '/static/img/default_icon.png') ?>" alt="<?= htmlspecialchars($lvl3['tendm']) ?>">
                    </div>
                    <div class="category-info">
                        <h4><?= htmlspecialchars($lvl3['tendm']) ?></h4>
                        <p><?= $count ?> sản phẩm</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <section class="product-section">
        <div class="product-layout">
            
            <aside class="filter-panel" id="filter-panel">
                <h3><i class="fas fa-filter"></i> Bộ lọc nâng cao</h3>
                
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

                <?php if (!empty($brands)): ?>
                <div class="filter-group">
                    <h4>Thương hiệu</h4>
                    <div class="filter-options scrollable">
                        <?php foreach ($brands as $brand): ?>
                        <label><input type="checkbox" name="brand[]" value="<?= $brand['math'] ?>"> <?= htmlspecialchars($brand['tenth']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($doituong_list)): ?>
                <div class="filter-group">
                    <h4>Đối tượng sử dụng</h4>
                    <div class="filter-options scrollable">
                        <?php foreach ($doituong_list as $doituong): ?>
                        <label><input type="checkbox" name="doituong[]" value="<?= htmlspecialchars($doituong) ?>"> <?= htmlspecialchars($doituong) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($muivi_list)): ?>
                <div class="filter-group">
                    <h4>Mùi vị</h4>
                    <div class="filter-options scrollable">
                        <?php foreach ($muivi_list as $muivi): ?>
                        <label><input type="checkbox" name="muivi[]" value="<?= $muivi['mamv'] ?>"> <?= htmlspecialchars($muivi['tenmv']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </aside>
            
            <div class="product-content">
                <div class="product-header">
                    <div class="title">
                        <h2><?= htmlspecialchars($dm2['tendm']) ?></h2>
                        <p class="note" id="product-count-note">Tìm thấy <?= count($products) ?> sản phẩm.</p>
                    </div>
                </div>
                
                <div class="product-grid" id="product-grid" data-category-ids="<?= htmlspecialchars(json_encode($madm_cap3_list)) ?>">
                    <?php if (empty($products)): ?>
                        <p>Chưa có sản phẩm nào trong danh mục này.</p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <a href="<?= $base_url ?>/base.php?page=product_detail&id=<?= $product['masp'] ?>" class="product-card">
                                <?php 
                                $discount_percent = 0;
                                $final_price = $product['giaban'];
                                if ($product['giagiam'] > 0 && $product['giaban'] > 0) {
                                    $final_price = $product['giaban'] - $product['giagiam'];
                                    $discount_percent = round(($product['giagiam'] / $product['giaban']) * 100);
                                }
                                ?>
                                <?php if ($discount_percent > 0): ?>
                                    <span class="discount-badge">-<?= $discount_percent ?>%</span>
                                <?php endif; ?>

                                <img src="<?= $base_url ?><?= htmlspecialchars($product['hinhsp'] ?? '/static/img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($product['tensp']) ?>">
                                <h3><?= htmlspecialchars($product['tensp']) ?></h3>
                                <p class="price">
                                    <?= money_vn($final_price) ?> đ
                                    <?php if ($discount_percent > 0): ?>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterPanel = document.getElementById('filter-panel');
        const productGrid = document.getElementById('product-grid');
        const productCountNote = document.getElementById('product-count-note');

        // Lắng nghe sự kiện 'change' trên toàn bộ panel lọc
        filterPanel.addEventListener('change', function(event) {
            // Chỉ chạy khi người dùng thay đổi input (checkbox, radio)
            if (event.target.tagName === 'INPUT') {
                applyFilters();
            }
        });

        function applyFilters() {
            // Thêm class 'loading' để làm mờ grid
            productGrid.classList.add('loading');

            const formData = new FormData();

            // 1. Lấy danh sách ID danh mục cấp 3
            const categoryIds = productGrid.dataset.categoryIds;
            formData.append('category_ids', categoryIds);

            // 2. Lấy giá trị giá
            const priceRange = document.querySelector('input[name="price_range"]:checked');
            if (priceRange) {
                formData.append('price_range', priceRange.value);
            }

            // 3. Lấy các thương hiệu đã chọn
            const checkedBrands = document.querySelectorAll('input[name="brand[]"]:checked');
            checkedBrands.forEach(checkbox => {
                formData.append('brand[]', checkbox.value);
            });

            // 4. Lấy các đối tượng đã chọn
            const checkedDoituong = document.querySelectorAll('input[name="doituong[]"]:checked');
            checkedDoituong.forEach(checkbox => {
                formData.append('doituong[]', checkbox.value);
            });

            // 5. Lấy các mùi vị đã chọn
            const checkedMuivi = document.querySelectorAll('input[name="muivi[]"]:checked');
            checkedMuivi.forEach(checkbox => {
                formData.append('muivi[]', checkbox.value);
            });

            // 6. Gửi yêu cầu AJAX
            // Sửa đường dẫn 'pages/filter_products.php' cho đúng với cấu trúc của bạn
            fetch('pages/filter_products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // 7. Cập nhật lại giao diện
                productGrid.innerHTML = data.html;
                productCountNote.innerHTML = `Tìm thấy ${data.count} sản phẩm.`;
                productGrid.classList.remove('loading');
            })
            .catch(error => {
                console.error('Lỗi khi lọc sản phẩm:', error);
                productGrid.innerHTML = '<p>Đã xảy ra lỗi khi tải sản phẩm.</p>';
                productGrid.classList.remove('loading');
            });
        }
    });
</script>