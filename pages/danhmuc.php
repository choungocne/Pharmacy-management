<?php
// File: pages/danhmuc.php
// PHIÊN BẢN AJAX FILTER + CART

// 1. KẾT NỐI CSDL VÀ CÁC HÀM HỖ TRỢ
include_once __DIR__ . '/../db.php';
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
$stmt_dm2 = $pdo->prepare("SELECT * FROM danhmuc WHERE madm = ? AND cap = 2");
$stmt_dm2->execute([$madm_cap2]);
$dm2 = $stmt_dm2->fetch(PDO::FETCH_ASSOC);

if (!$dm2) {
    echo "<h3>Danh mục không tồn tại</h3>";
    exit;
}

// 3. LẤY DANH MỤC CẤP 3
$stmt_lv3 = $pdo->prepare("SELECT * FROM danhmuc WHERE parent_id = ? AND cap = 3 ORDER BY tendm");
$stmt_lv3->execute([$madm_cap2]);
$sub_categories_lvl3 = $stmt_lv3->fetchAll(PDO::FETCH_ASSOC);
$madm_cap3_list = array_column($sub_categories_lvl3, 'madm');

// 4. LẤY SỐ LƯỢNG SẢN PHẨM CHO MỖI DANH MỤC CẤP 3
$product_counts = [];
if (!empty($madm_cap3_list)) {
    $placeholders_count = implode(',', array_fill(0, count($madm_cap3_list), '?'));
    $sql_count = "SELECT madm, COUNT(*) as product_count FROM sanpham WHERE madm IN ($placeholders_count) GROUP BY madm";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($madm_cap3_list);
    $product_counts = $stmt_count->fetchAll(PDO::FETCH_KEY_PAIR);
}

// 5. LẤY TẤT CẢ SẢN PHẨM (CHỈ LẤY LẦN ĐẦU)
$products = [];
if (!empty($madm_cap3_list)) {
    $placeholders_prod = implode(',', array_fill(0, count($madm_cap3_list), '?'));
    $sql_prod = "
        SELECT sp.*, 
               km.makm, km.phantram_giam, km.gia_giam_co_dinh, km.trangthai_deal
        FROM sanpham sp
        LEFT JOIN khuyenmai km ON sp.makm = km.makm 
            AND km.trangthai_deal = 'dang_dien_ra'
            AND km.ngay_batdau <= NOW() 
            AND km.ngay_ketthuc >= NOW()
        WHERE sp.madm IN ($placeholders_prod) AND sp.trangthai = 1
    ";
    $stmt_prod = $pdo->prepare($sql_prod);
    $stmt_prod->execute($madm_cap3_list);
    $products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);
}

// 6. TẠO BỘ LỌC ĐỘNG
$brands = [];
$product_math_ids = array_values(array_unique(array_filter(array_column($products, 'math'))));
if (!empty($product_math_ids)) {
    $placeholders_brand = implode(',', array_fill(0, count($product_math_ids), '?'));
    $sql_brand = "SELECT math, tenth FROM thuonghieu WHERE math IN ($placeholders_brand) ORDER BY tenth";
    $stmt_brand = $pdo->prepare($sql_brand);
    $stmt_brand->execute($product_math_ids);
    $brands = $stmt_brand->fetchAll(PDO::FETCH_ASSOC);
}

$doituong_list = [];
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

$muivi_list = [];
$product_mamv_ids = array_values(array_unique(array_filter(array_column($products, 'mamv'))));
if (!empty($product_mamv_ids)) {
    $placeholders_mv = implode(',', array_fill(0, count($product_mamv_ids), '?'));
    $sql_mv = "SELECT mamv, tenmv FROM muivi WHERE mamv IN ($placeholders_mv) ORDER BY tenmv";
    $stmt_mv = $pdo->prepare($sql_mv);
    $stmt_mv->execute($product_mamv_ids);
    $muivi_list = $stmt_mv->fetchAll(PDO::FETCH_ASSOC);
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

    /* --- CỘT SẢN PHẨM (BÊNE PHẢI) --- */
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

    /* Thêm style cho loading để thấy hiệu ứng khi lọc */
    .product-grid.loading {
        opacity: 0.5;
        pointer-events: none;
    }
    
</style>
<link rel="stylesheet" href="static/css/product.css">

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
                            <?php 
                            $giaban = (float)($product['giaban'] ?? 0);
                            $final_price = $giaban;
                            $discount_percent = 0;
                            $imagePath = trim($product['hinhsp'] ?? '');
                            if ($imagePath === '') {
                                $imageSrc = $base_url . '/static/img/placeholder.jpg';
                            } elseif (preg_match('#^https?://#i', $imagePath)) {
                                $imageSrc = $imagePath;
                            } else {
                                $normalized = '/' . ltrim($imagePath, '/');
                                $normalized = preg_replace('#^(/Pharmacy-management)+/#', '/Pharmacy-management/', $normalized);
                                if (strpos($normalized, '/Pharmacy-management/') === 0) {
                                    $imageSrc = $normalized;
                                } else {
                                    $imageSrc = rtrim($base_url, '/') . $normalized;
                                }
                            }
                            
                            // Tính giá sau khuyến mãi
                            if ($product['makm']) {
                                if ($product['phantram_giam'] > 0) {
                                    $final_price = $giaban * (1 - $product['phantram_giam'] / 100);
                                    $discount_percent = (int)$product['phantram_giam'];
                                } else if ($product['gia_giam_co_dinh'] > 0) {
                                    $final_price = max(0, $giaban - $product['gia_giam_co_dinh']);
                                    $discount_percent = (int)round(($product['gia_giam_co_dinh'] / $giaban) * 100);
                                }
                            }
                            ?>

                            <div class="product-card" data-masp="<?= $product['masp'] ?>">
                                <a href="<?= $base_url ?>/base.php?page=detailsproducts&masp=<?= urlencode($product['masp']) ?>">
                                    <?php if ($discount_percent > 0): ?>
                                        <span class="discount-badge">-<?= $discount_percent ?>%</span>
                                    <?php endif; ?>

                                    <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($product['tensp']) ?>">
                                    <h3><?= htmlspecialchars($product['tensp']) ?></h3>
                                    <p class="price">
                                        <?= money_vn($final_price) ?> đ
                                        <?php if ($discount_percent > 0): ?>
                                            <span class="old-price"><?= money_vn($giaban) ?>đ</span>
                                        <?php endif; ?>
                                    </p>
                                </a>
                                <button class="btn-buy" onclick="addToCart(<?= $product['masp'] ?>, event)">
                                    <i class="fas fa-cart-plus"></i> Chọn mua
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    // Định nghĩa allProducts và baseUrl
    const allProducts = <?= json_encode($products) ?>;
    const baseUrl = '<?= $base_url ?>';

    // Hàm thêm vào giỏ hàng
    function addToCart(masp, event) {
        event.preventDefault();
        event.stopPropagation();
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', masp);
        formData.append('quantity', 1);
        
        fetch('<?= $base_url ?>/cart_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Đã thêm vào giỏ hàng!');
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thêm vào giỏ hàng!');
        });
    }

    // Logic lọc sản phẩm (client-side)
    document.addEventListener('DOMContentLoaded', function() {
        const filterPanel = document.getElementById('filter-panel');
        const productGrid = document.getElementById('product-grid');
        const productCountNote = document.getElementById('product-count-note');

        if (!allProducts) {
            console.error('allProducts not defined!');
            return;
        }

        filterPanel.addEventListener('change', function(event) {
            if (event.target.tagName === 'INPUT') {
                applyFilters();
            }
        });

        function applyFilters() {
            productGrid.classList.add('loading');

            const priceRange = document.querySelector('input[name="price_range"]:checked')?.value || 'all';

            const selectedBrands = Array.from(document.querySelectorAll('input[name="brand[]"]:checked')).map(cb => parseInt(cb.value));

            const selectedDoituong = Array.from(document.querySelectorAll('input[name="doituong[]"]:checked')).map(cb => cb.value);

            const selectedMuivi = Array.from(document.querySelectorAll('input[name="muivi[]"]:checked')).map(cb => parseInt(cb.value));

            let filtered = allProducts.filter(product => {
                const finalPrice = calculateFinalPrice(product);
                let priceMatch = true;
                if (priceRange !== 'all') {
                    const [min, max] = priceRange.split('-').map(Number);
                    priceMatch = finalPrice >= min && (isNaN(max) ? true : finalPrice <= max);
                }

                const brandMatch = selectedBrands.length === 0 || selectedBrands.includes(parseInt(product.math || 0));

                let doituongData = [];
                try {
                    doituongData = JSON.parse(product.doituong || '[]');
                } catch(e) {}
                const doituongMatch = selectedDoituong.length === 0 || selectedDoituong.some(dt => doituongData.includes(dt));

                const muiviMatch = selectedMuivi.length === 0 || selectedMuivi.includes(parseInt(product.mamv || 0));

                return priceMatch && brandMatch && doituongMatch && muiviMatch;
            });

            let html = '';
            if (filtered.length === 0) {
                html = '<p>Không tìm thấy sản phẩm phù hợp.</p>';
            } else {
                filtered.forEach(product => {
                    const giaban = parseFloat(product.giaban || 0);
                    let final_price = giaban;
                    let discount_percent = 0;
                    if (product.makm) {
                        if (product.phantram_giam > 0) {
                            final_price = giaban * (1 - product.phantram_giam / 100);
                            discount_percent = parseInt(product.phantram_giam);
                        } else if (product.gia_giam_co_dinh > 0) {
                            final_price = Math.max(0, giaban - product.gia_giam_co_dinh);
                            discount_percent = parseInt(Math.round((product.gia_giam_co_dinh / giaban) * 100));
                        }
                    }
                    const discount_badge = discount_percent > 0 ? `<span class="discount-badge">-${discount_percent}%</span>` : '';
                    const old_price = discount_percent > 0 ? `<span class="old-price">${moneyVn(giaban)}đ</span>` : '';
                    const imageSrc = getImageSrc(product.hinhsp);

                    html += `
                        <div class="product-card" data-masp="${product.masp}">
                            <a href="${baseUrl}/base.php?page=detailsproducts&masp=${encodeURIComponent(product.masp)}">
                                ${discount_badge}
                                <img src="${imageSrc}" alt="${escapeHtml(product.tensp)}">
                                <h3>${escapeHtml(product.tensp)}</h3>
                                <p class="price">
                                    ${moneyVn(final_price)} đ
                                    ${old_price}
                                </p>
                            </a>
                            <button class="btn-buy" onclick="addToCart(${product.masp}, event)">
                                <i class="fas fa-cart-plus"></i> Chọn mua
                            </button>
                        </div>
                    `;
                });
            }

            productGrid.innerHTML = html;
            productCountNote.innerHTML = `Tìm thấy ${filtered.length} sản phẩm.`;
            productGrid.classList.remove('loading');
        }

        function calculateFinalPrice(product) {
            const giaban = parseFloat(product.giaban || 0);
            let final = giaban;
            if (product.makm) {
                if (product.phantram_giam > 0) {
                    final = giaban * (1 - product.phantram_giam / 100);
                } else if (product.gia_giam_co_dinh > 0) {
                    final = Math.max(0, giaban - product.gia_giam_co_dinh);
                }
            }
            return final;
        }

        function moneyVn(price) {
            return new Intl.NumberFormat('vi-VN', {minimumFractionDigits: 0}).format(price).replace(/\./g, '.');
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function getImageSrc(imagePath) {
            imagePath = (imagePath || '').trim();
            if (imagePath === '') {
                return baseUrl + '/static/img/placeholder.jpg';
            }
            if (/^https?:\/\//i.test(imagePath)) {
                return imagePath;
            }
            let normalized = imagePath.replace(/^\/+/, '');
            if (normalized.startsWith('Pharmacy-management/')) {
                normalized = normalized.slice('Pharmacy-management/'.length);
            }
            return baseUrl + '/' + normalized;
        }
    });
</script>
