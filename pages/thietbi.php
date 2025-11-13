<?php
$rootPath = __DIR__ . '/..'; // Quay về thư mục gốc: Pharmacy-management
// Bỏ include base.php ở đây nếu nó đã được include ở index.php/base.php để tránh lỗi redeclare
include_once $rootPath . '/db.php';

// Kiểm tra kết nối PDO và gán biến cần thiết
if (!isset($pdo)) {
    // Nếu pdo() không tự động gán $pdo, ta phải gọi hàm pdo()
    try {
        $pdo = pdo();
    } catch (PDOException $e) {
        die("Không thể kết nối database: " . $e->getMessage());
    }
}

// Bổ sung hàm định dạng giá từ db.php để sử dụng trong HTML (money_vn đã có trong db.php)
function format_price_vn($n) {
    return money_vn($n); // Sử dụng hàm money_vn() đã được định nghĩa trong db.php
}

// Giả định $base_url được định nghĩa trong base.php (được include ở file gọi thietbi.php)
$base_url = $base_url ?? '/Pharmacy-management'; 

$sql = "SELECT sp.*, dm.tendm, dv.tendv,
               km.makm, km.phantram_giam, km.gia_giam_co_dinh, km.trangthai_deal
        FROM sanpham sp
        JOIN danhmuc dm ON sp.madm = dm.madm
        LEFT JOIN donvitinh dv ON sp.madv = dv.madv
        LEFT JOIN khuyenmai km ON sp.makm = km.makm 
            AND km.trangthai_deal = 'dang_dien_ra'
            AND km.ngay_batdau <= NOW() 
            AND km.ngay_ketthuc >= NOW()
        WHERE sp.madm BETWEEN 64 AND 75
        ORDER BY sp.masp DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$product_count = count($products);
$error_message = empty($products) ? "Không tìm thấy sản phẩm nào trong danh mục (ID: 64-75)." : '';

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

<link rel="stylesheet" href="static/css/product.css">

<div class="container">
    <section class="featured-categories-section">
        <div class="container">
            <div class="section-header">
                <h2>Thiết bị y tế</h2>
            </div>
            <div class="categories-grid">
                <div class="category-item" data-category="dung-cu-y-te">
                    <div class="category-icon">
                      <i class="fa-solid fa-syringe"></i>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name">Dụng cụ y tế</h3>
                        <span class="category-count">55 sản phẩm</span>
                    </div>
                </div>

                <div class="category-item" data-category="dung-cu-theo-doi">
                    <div class="category-icon">
                      <i class="fa-solid fa-stethoscope"></i>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name">Dụng cụ theo dõi</h3>
                        <span class="category-count">110 sản phẩm</span>
                    </div>
                </div>

                <div class="category-item" data-category="dung-cu-so-cuu">
                    <div class="category-icon">
                        <i class="fa-solid fa-kit-medical"></i>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name">Dụng cụ sơ cứu</h3>
                        <span class="category-count">23 sản phẩm</span>
                    </div>
                </div>

                <div class="category-item" data-category="khau-trang">
                    <div class="category-icon">
                        <i class="fa-solid fa-head-side-mask"></i>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name">Khẩu trang</h3>
                        <span class="category-count">65 sản phẩm</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                        <h2>Danh sách sản phẩm (Tổng: <?= $product_count ?>)</h2>
                        <p class="note" id="product-count-note">Lưu ý: Một số sản phẩm cần tư vấn từ dược sĩ.</p>
                    </div>
                </div>
                
                <div class="product-grid" id="product-grid">
                    <?php if (!empty($error_message)): ?>
                        <p style="color: red; padding: 20px;"><?= $error_message ?></p>
                    <?php else: ?>
                        <?php foreach ($products as $sp): 
                            // XỬ LÝ DỮ LIỆU ĐỂ HIỂN THỊ ĐẸP
                            $giaban = (float)($sp['giaban'] ?? 0);
                            $final_price = $giaban;
                            $discount_percent = 0;
                            $imagePath = trim($sp['hinhsp'] ?? '');
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
                            if ($sp['makm']) {
                                if ($sp['phantram_giam'] > 0) {
                                    $final_price = $giaban * (1 - $sp['phantram_giam'] / 100);
                                    $discount_percent = (int)$sp['phantram_giam'];
                                } else if ($sp['gia_giam_co_dinh'] > 0) {
                                    $final_price = max(0, $giaban - $sp['gia_giam_co_dinh']);
                                    $discount_percent = (int)round(($sp['gia_giam_co_dinh'] / $giaban) * 100);
                                }
                            }
                            $detail_link = $base_url . "/base.php?page=detailsproducts&masp=" . ($sp['masp'] ?? '');
                            ?>
                            <div class="product-card" data-masp="<?= $sp['masp'] ?>">
                                <a href="<?= htmlspecialchars($detail_link) ?>">
                                    <?php if ($discount_percent > 0): ?>
                                        <span class="discount-badge">-<?= $discount_percent ?>%</span>
                                    <?php endif; ?>

                                    <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($sp['tensp']) ?>">
                                    <h3><?= htmlspecialchars($sp['tensp']) ?></h3>
                                    <p class="price">
                                        <?= money_vn($final_price) ?> đ
                                        <?php if ($discount_percent > 0): ?>
                                            <span class="old-price"><?= money_vn($giaban) ?>đ</span>
                                        <?php endif; ?>
                                    </p>
                                </a>
                                <button class="btn-buy" onclick="addToCart(<?= $sp['masp'] ?>, event)">
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

    // Hàm thêm vào giỏ hàng (giữ nguyên từ trước)
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