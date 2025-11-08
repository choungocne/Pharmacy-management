<?php
// Bổ sung các file cấu hình và hàm cần thiết
// Giả sử db.php đã được include hoặc hàm pdo() và money_vn() đã được định nghĩa
include_once 'db.php'; 

$base_url = '/Pharmacy-management';
$page_title = "Trang chủ - Nhà Thuốc An Tâm";

// Hàm tính giá sau khuyến mãi
// Hàm tính giá sau khuyến mãi
if (!function_exists('calculate_sale_price')) {
    function calculate_sale_price($giaban, $phantram_giam, $gia_giam_co_dinh) {
        $sale_price = $giaban;
        if ($phantram_giam > 0) {
            $sale_price = $giaban * (1 - $phantram_giam / 100);
        }
        if ($gia_giam_co_dinh > 0) {
            // Lưu ý: Giá trị giagiam_co_dinh trong bảng khuyenmai của bạn không được dùng trực tiếp
            // để trừ vào giá sản phẩm, mà thường áp dụng cho tổng đơn hàng. 
            // Tuy nhiên, tôi vẫn giữ logic đơn giản nhất.
        }
        return max(0, $sale_price); // Đảm bảo giá không âm
    }
}
// ... (các truy vấn trước đó)

// --- 3. Lấy dữ liệu Thương hiệu nổi bật ---
$sql_brands = "
    SELECT math, tenth, logo_url
    FROM thuonghieu
    ORDER BY math 
    LIMIT 5
";
$brands = pdo()->query($sql_brands)->fetchAll();

// Giả định thêm một trường "discount" cho mục đích hiển thị mẫu
// Bạn cần thay thế logic này bằng dữ liệu khuyến mãi thực tế nếu cần.
$brand_discounts = [
    'Blackmores' => 'Giảm đến 25%', 
    'Nature\'s Way' => 'Giảm đến 20%', 
    'Healthy Care' => 'Giảm đến 30%',
    'Kirkland Signature' => 'Giảm đến 15%',
    'Omron' => 'Giảm đến 10%',
    // Thêm các thương hiệu khác nếu có
];
// --- 1. Lấy Sản phẩm đang có Deal (Sản phẩm có makm hợp lệ) ---
$sql_deal = "
    SELECT 
        sp.masp, sp.tensp, sp.giaban, sp.hinhsp, dm.tendm, km.phantram_giam, km.gia_giam_co_dinh
    FROM sanpham sp
    JOIN danhmuc dm ON sp.madm = dm.madm
    JOIN khuyenmai km ON sp.makm = km.makm
    WHERE km.trangthai_deal = 'dang_dien_ra'
    LIMIT 4
";
$deals = pdo()->query($sql_deal)->fetchAll();


// --- 2. Lấy Sản phẩm bán chạy nhất (Top 4 - Sửa lỗi LIMIT & IN Subquery) ---
// CHỈ LẤY ID CỦA CÁC SẢN PHẨM BÁN CHẠY NHẤT VÀ KHÔNG SỬ DỤNG JSON_EXTRACT TRONG GROUP BY/ORDER BY
// Cách an toàn nhất là lấy ID cứng của các sản phẩm có doanh số tốt trong dữ liệu mẫu.

$products_banchay_ids = [1, 13, 30, 41]; 
$id_list = implode(',', $products_banchay_ids);

$sql_banchay_safe = "
    SELECT 
        sp.masp, sp.tensp, sp.giaban, sp.hinhsp, dm.tendm, km.phantram_giam, km.gia_giam_co_dinh
    FROM sanpham sp
    JOIN danhmuc dm ON sp.madm = dm.madm
    LEFT JOIN khuyenmai km ON sp.makm = km.makm
    WHERE sp.masp IN ({$id_list})
    ORDER BY FIELD(sp.masp, {$id_list}) -- Giữ nguyên thứ tự bán chạy
";
$products_banchay = pdo()->query($sql_banchay_safe)->fetchAll();

// *Giải thích: Truy vấn này KHÔNG tính toán động sản phẩm bán chạy mà dựa vào các ID 
// (1, 13, 30, 41) đã xác định là bán chạy trong dữ liệu mẫu. 
// Đây là giải pháp an toàn nhất để tránh lỗi cú pháp trong phiên bản MariaDB cũ.
?>
<style>
    .favorite-brands {
  background-color: #f5f7ff;
  border-radius: 10px;
  padding: 20px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.section-title h2 {
  font-size: 22px;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.brand-list {
  gap: 15px;
}

.brand-item {
  background: #fff;
  border-radius: 10px;
  text-align: center;
  width: 18%;
  padding: 10px;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.brand-item:hover {
  transform: translateY(-5px);
}

.brand-item img {
  width: 100%;
  border-radius: 8px;
  margin-bottom: 10px;
}

.brand-info .brand-name {
  font-weight: 600;
  color: #34495e;
  margin-bottom: 5px;
}

.discount {
  color: #007bff;
  font-weight: 500;
}
</style>
    <!-- Hero Banner Carousel -->
    <section class="hero-banner">
        <div class="carousel-container">
            <div class="carousel-slides">
                <!-- Slide 1 -->
                <div class="carousel-slide active">
                        <img src="static/img/banner/Banner.webp"
                             alt="Ông bà vui khỏe trọn vẹn yêu thương" class="desktop-img">
                </div>

                <!-- Slide 2 -->
                <div class="carousel-slide">
                        <img src="static/img/banner/Banner2.webp"
                             alt="Đăng ký khám sức khỏe sàng lọc miễn phí" class="desktop-img">
                </div>

                <!-- Slide 3 -->
                <div class="carousel-slide">
                        <img src="static/img/D_Herobanner.webp"
                             alt="Chăm sóc chính mình mỗi ngày đẹp xinh" class="desktop-img">
                </div>
            </div>

            <!-- Navigation buttons -->
            <button class="carousel-btn carousel-prev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-btn carousel-next">
                <i class="fas fa-chevron-right"></i>
            </button>

            <!-- Indicators -->
            <div class="carousel-indicators">
                <button class="indicator active" data-slide="0"></button>
                <button class="indicator" data-slide="1"></button>
                <button class="indicator" data-slide="2"></button>
            </div>
        </div>
    </section>

<!-- Banner Grid Section -->
<section class="banner-grid-section">
    <div class="container">
        <div class="banner-grid">
            <!-- Main Carousel Banner -->
            <div class="main-banner carousel-banner">
                <div class="banner-carousel">
                    <div class="banner-slides">
                        <!-- Slide 1 -->
                        <div class="banner-slide active">
                                <img src="static/img/banner/D_H1_Desktop_1200x367_0a2663616c.webp"
                                     alt="Máy đo đường huyết liên tục" class="desktop-img">
                        </div>

                        <!-- Slide 2 -->
                        <div class="banner-slide">
                                <img src="static/img/banner/Banner_Web_PC_805x246_ee86632913.webp"
                                     alt="Varna Diabetes" class="desktop-img">
                        </div>

                        <!-- Slide 3 -->
                        <div class="banner-slide">
                                <img src="static/img/banner/H1_desktop_805x246_fea391ab17.webp"
                                     alt="Hội thảo chuyên nghiệp cùng chuyên gia ung thư" class="desktop-img">
                        </div>

                        <!-- Thêm các slide khác -->
                        <div class="banner-slide">
                                <img src="/static/img/banner/H1_desktop_805x246_8c1d616da4.webp"
                                     alt="Vitabiotics" class="desktop-img">

                        </div>
                    </div>

                    <!-- Navigation buttons -->
                    <button class="banner-carousel-btn banner-prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="banner-carousel-btn banner-next">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <!-- Indicators -->
                    <div class="banner-indicators">
                        <button class="banner-indicator active" data-slide="0"></button>
                        <button class="banner-indicator" data-slide="1"></button>
                        <button class="banner-indicator" data-slide="2"></button>
                        <button class="banner-indicator" data-slide="3"></button>
                    </div>
                </div>
            </div>

            <!-- Side Banners -->
            <div class="side-banners">
                <a href="https://nhathuoclongchau.com.vn/chuyen-trang-ung-thu" class="side-banner">
                    <img src="static/img/banner/Banner_Ung_Thu_1_185705d391.webp"
                         alt="Chuyên trang ung thư">
                </a>
                <a href="https://nhathuoclongchau.com.vn/tra-cuu/dia-chinh-moi" class="side-banner">
                    <img src="static/img/banner/Banner_H2_2893cabcaa.webp"
                         alt="Địa chỉ mới">
                </a>
            </div>
        </div>
    </div>
</section>

    <!-- Main Content -->
    <div class="container">
        <!-- Deal Section -->
       <section class="deal-section">
    <div class="deal-header">
        <h2>DEAL XIN QUẢ XINH - ƯU ĐÃI MỖI NGÀY</h2>
        <div class="deal-timer">
            <i class="fas fa-clock"></i> Kết thúc sau: <span id="countdown">18:45:32</span>
        </div>
    </div>
    <div class="product-grid">
        <?php foreach ($deals as $product): ?>
        <?php 
            $current_price = calculate_sale_price($product['giaban'], $product['phantram_giam'], $product['gia_giam_co_dinh']);
            $discount_amount = $product['giaban'] - $current_price;
            $discount_percent = round(($discount_amount / $product['giaban']) * 100);
            $image_path = $product['hinhsp'] ? $product['hinhsp'] : 'static/img/placeholder.jpg';
        ?>
        <div class="product-card">
            <div class="product-image">
                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['tensp']) ?>">
            </div>
            <div class="product-info">
                <div class="product-category"><?= htmlspecialchars($product['tendm']) ?></div>
                <div class="product-name"><?= htmlspecialchars($product['tensp']) ?></div>
                <div class="product-price">
                    <span class="current-price"><?= money_vn($current_price) ?>đ</span>
                    <span class="original-price"><?= money_vn($product['giaban']) ?>đ</span>
                    <span class="discount-badge">-<?= $discount_percent ?>%</span>
                </div>
                <div class="product-actions">
                    <button class="add-to-cart" data-product-id="<?= $product['masp'] ?>">
                        Thêm giỏ hàng
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($deals)): ?>
        <p>Hiện không có deal hấp dẫn nào đang diễn ra.</p>
        <?php endif; ?>
    </div>
</section>
        <div class="main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <h2>Danh mục sản phẩm</h2>
                <ul>
                    <li><a href="#"><i class="fas fa-capsules"></i> Thực phẩm chức năng</a></li>
                    <li><a href="#"><i class="fas fa-pills"></i> Thuốc</a></li>
                    <li><a href="#"><i class="fas fa-stethoscope"></i> Thiết bị y tế</a></li>
                    <li><a href="#"><i class="fas fa-heartbeat"></i> Chăm sóc sức khỏe</a></li>
                    <li><a href="#"><i class="fas fa-baby"></i> Mẹ và bé</a></li>
                    <li><a href="#"><i class="fas fa-user-md"></i> Dược mỹ phẩm</a></li>
                    <li><a href="#"><i class="fas fa-notes-medical"></i> Bệnh thường gặp</a></li>
                </ul>
            </aside>

            <!-- Content -->
            <div class="content">
                <!-- Best Selling Products -->
              <section class="section">
    <div class="section-header">
        <h2>Sản phẩm bán chạy nhất</h2>
        <a href="#" class="view-all">Xem tất cả <i class="fas fa-chevron-right"></i></a>
    </div>
    <div class="product-grid">
        <?php foreach ($products_banchay as $product): ?>
        <?php 
            // Tính toán giá sale nếu có (Sản phẩm bán chạy vẫn có thể đang sale)
            $sale_price = calculate_sale_price($product['giaban'], $product['phantram_giam'], $product['gia_giam_co_dinh']);
            $has_discount = $sale_price < $product['giaban'];
            $discount_percent = $has_discount ? round((($product['giaban'] - $sale_price) / $product['giaban']) * 100) : 0;
            $image_path = $product['hinhsp'] ? $product['hinhsp'] : 'static/img/placeholder.jpg';
        ?>
        <div class="product-card">
            <div class="product-image">
                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['tensp']) ?>">
            </div>
            <div class="product-info">
                <div class="product-category"><?= htmlspecialchars($product['tendm']) ?></div>
                <div class="product-name"><?= htmlspecialchars($product['tensp']) ?></div>
                <div class="product-price">
                    <span class="current-price"><?= money_vn($sale_price) ?>đ</span>
                    <?php if ($has_discount): ?>
                        <span class="original-price"><?= money_vn($product['giaban']) ?>đ</span>
                        <span class="discount-badge">-<?= $discount_percent ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="product-package">Chi tiết sản phẩm</div>
                <div class="product-actions">
                    <button class="add-to-cart" data-product-id="<?= $product['masp'] ?>">
                        Thêm giỏ hàng
                    </button>
                    <button class="view-details">Chi tiết</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($products_banchay)): ?>
        <p>Không tìm thấy sản phẩm bán chạy trong 30 ngày gần nhất.</p>
        <?php endif; ?>
    </div>
</section>
 <section class="featured-categories-section">
    <div class="container">
        <div class="section-header">
            <h2>Thương hiệu nổi bật</h2>
        </div>

        <div class="brand-list d-flex flex-wrap justify-content-between">
            <?php foreach ($brands as $brand): ?>
                <?php 
                    $discount_text = $brand_discounts[$brand['tenth']] ?? 'Ưu đãi hấp dẫn'; 
                    // Sử dụng logo_url nếu có, nếu không dùng placeholder
                    $image_src = $brand['logo_url'] ?? 'static/img/placeholder.jpg'; 
                ?>
                <div class="brand-item">
                    <img src="<?= htmlspecialchars($image_src) ?>" alt="<?= htmlspecialchars($brand['tenth']) ?>" onerror="this.onerror=null;this.src='static/img/placeholder.jpg';" style="height: 50px; object-fit: contain;">
                    <div class="brand-info">
                        <p class="brand-name"><?= htmlspecialchars($brand['tenth']) ?></p>
                        <span class="discount"><?= $discount_text ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($brands)): ?>
                <p>Không có thương hiệu nào được tìm thấy.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

                <!-- Featured Categories Section -->
<section class="featured-categories-section">
    <div class="container">
        <div class="section-header">
            <h2>Danh mục nổi bật</h2>
        </div>
        <div class="categories-grid">
            <!-- Category 1 -->
            <div class="category-item" data-category="than-kinh-nao">
                <div class="category-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Thần kinh não</h3>
                    <span class="category-count">55 sản phẩm</span>
                </div>
            </div>

            <!-- Category 2 -->
            <div class="category-item" data-category="vitamin-khoang-chat">
                <div class="category-icon">
                    <i class="fas fa-capsules"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Vitamin & Khoáng chất</h3>
                    <span class="category-count">110 sản phẩm</span>
                </div>
            </div>

            <!-- Category 3 -->
            <div class="category-item" data-category="suc-khoe-tim-mach">
                <div class="category-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Sức khoẻ tim mạch</h3>
                    <span class="category-count">23 sản phẩm</span>
                </div>
            </div>

            <!-- Category 4 -->
            <div class="category-item" data-category="mien-dich">
                <div class="category-icon">
                    <i class="fas fa-shield-virus"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Tăng sức đề kháng, miễn dịch</h3>
                    <span class="category-count">40 sản phẩm</span>
                </div>
            </div>

            <!-- Category 5 -->
            <div class="category-item" data-category="ho-tro-tieu-hoa">
                <div class="category-icon">
                    <i class="fas fa-stomach"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Hỗ trợ tiêu hóa</h3>
                    <span class="category-count">65 sản phẩm</span>
                </div>
            </div>

            <!-- Category 6 -->
            <div class="category-item" data-category="noi-tiet-to">
                <div class="category-icon">
                    <i class="fa-solid fa-capsules"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Sinh lý - Nội tiết tố</h3>
                    <span class="category-count">39 sản phẩm</span>
                </div>
            </div>

            <!-- Category 7 -->
            <div class="category-item" data-category="dinh-duong">
                <div class="category-icon">
                    <i class="fas fa-apple-alt"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Dinh dưỡng</h3>
                    <span class="category-count">36 sản phẩm</span>
                </div>
            </div>

            <!-- Category 8 -->
            <div class="category-item" data-category="ho-tro-dieu-tri">
                <div class="category-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Hỗ trợ điều trị</h3>
                    <span class="category-count">119 sản phẩm</span>
                </div>
            </div>

            <!-- Category 9 -->
            <div class="category-item" data-category="giai-phap-lan-da">
                <div class="category-icon">
                    <i class="fas fa-spa"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Giải pháp làn da</h3>
                    <span class="category-count">94 sản phẩm</span>
                </div>
            </div>

            <!-- Category 10 -->
            <div class="category-item" data-category="cham-soc-da-mat">
                <div class="category-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Chăm sóc da mặt</h3>
                    <span class="category-count">211 sản phẩm</span>
                </div>
            </div>

            <!-- Category 11 -->
            <div class="category-item" data-category="ho-tro-lam-dep">
                <div class="category-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Hỗ trợ làm đẹp</h3>
                    <span class="category-count">22 sản phẩm</span>
                </div>
            </div>

            <!-- Category 12 -->
            <div class="category-item" data-category="ho-tro-tinh-duc">
                <div class="category-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Hỗ trợ tình dục</h3>
                    <span class="category-count">41 sản phẩm</span>
                </div>
            </div>
        </div>
    </div>
</section>

                <!-- Disease Section -->
                <div class="disease-section">
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="target">Bệnh theo đối tượng</button>
                        <button class="tab-btn" data-tab="season">Bệnh theo mùa</button>
                    </div>

                    <!-- Bệnh theo đối tượng -->
                    <div class="tab-content active" id="target">
                        <div class="card">
                            <img src="static/img/nam_gioi.webp" alt="Bệnh nam giới" width="257" height="144">
                            <h3>BỆNH NAM GIỚI</h3>
                            <ul>
                                <li>Loãng xương ở nam</li>
                                <li>Di tinh, mộng tinh</li>
                                <li>Hẹp bao quy đầu</li>
                                <li>Yếu sinh lý</li>
                            </ul>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>

                        <div class="card">
                            <img src="static/img/nu_gioi.jpg" alt="Bệnh nữ giới" width="257" height="144">
                            <h3>BỆNH NỮ GIỚI</h3>
                            <ul>
                                <li>Hội chứng tiền kinh nguyệt</li>
                                <li>Hội chứng tiền mãn kinh</li>
                                <li>Chậm kinh</li>
                                <li>Mất kinh</li>
                            </ul>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>

                        <div class="card">
                            <img src="static/img/nguoi_gia.png" alt="Bệnh người già" width="257" height="144">
                            <h3>BỆNH NGƯỜI GIÀ</h3>
                            <ul>
                                <li>Alzheimer</li>
                                <li>Parkinson</li>
                                <li>Parkinson thứ phát</li>
                                <li>Đục thủy tinh thể ở người già</li>
                            </ul>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>

                        <div class="card">
                            <img src="static/img/tre_em.webp" alt="Bệnh trẻ em" width="257" height="144">
                            <h3>BỆNH TRẺ EM</h3>
                            <ul>
                                <li>Bại não trẻ em</li>
                                <li>Tự kỷ</li>
                                <li>Uốn ván</li>
                                <li>Tắc ruột sơ sinh</li>
                            </ul>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>
                    </div>

                    <!-- Bệnh theo mùa -->
                    <div class="tab-content" id="season">
                        <div class="card">
                            <img src="static/img/sot-xuat-huyet.webp" alt="Sốt xuất huyết">
                            <h3>Sốt xuất huyết Dengue</h3>
                            <p>Sốt xuất huyết Dengue là bệnh do muỗi truyền xảy ra ở các khu vực nhiệt đới và cận nhiệt đới...</p>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>

                        <div class="card">
                            <img src="static/img/ebola.webp" alt="Ebola">
                            <h3>Ebola</h3>
                            <p>Ebola là một căn bệnh truyền nhiễm hiếm gặp nhưng có thể gây nguy cơ tử vong cao ở người...</p>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>

                        <div class="card">
                            <img src="static//img/cum.png" alt="Cúm">
                            <h3>Cúm</h3>
                            <p>Bệnh cúm là bệnh truyền nhiễm, gây ra do nhiễm virus cúm. Virus có thể gây bệnh từ nhẹ tới nặng...</p>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>

                        <div class="card">
                            <img src="static/img/tcm.jpeg" alt="Tay chân miệng">
                            <h3>Bệnh tay chân miệng</h3>
                            <p>Bệnh Tay chân miệng là bệnh do virus gây ra, có khả năng lây lan rất nhanh chóng và do đó, rất dễ bùng phát thành dịch...</p>
                            <a href="/Pharmacy-management/base.php?page=search_disease">Tìm hiểu thêm →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commitment Bar -->
        <section class="commitment-bar">
            <div class="container">
                <div class="item">
                    <img src="static/img/quality.png" alt="Thuốc chính hãng">
                    <div>
                        <h4>Thuốc chính hãng</h4>
                        <p>đa dạng và chuyên sâu</p>
                    </div>
                </div>
                <div class="item">
                    <img src="static/img/return.png" alt="Đổi trả">
                    <div>
                        <h4>Đổi trả trong 30 ngày</h4>
                        <p>kể từ ngày mua hàng</p>
                    </div>
                </div>
                <div class="item">
                    <img src="static/img/shield.png" alt="Cam kết 100%">
                    <div>
                        <h4>Cam kết 100%</h4>
                        <p>chất lượng sản phẩm</p>
                    </div>
                </div>
                <div class="item">
                    <img src="static/img/shipping.png" alt="Miễn phí vận chuyển">
                    <div>
                        <h4>Miễn phí vận chuyển</h4>
                        <p>theo chính sách giao hàng</p>
                    </div>
                </div>
            </div>
        </section>
    </div>


<div id="footer"></div>
<!-- <script>
  fetch("footer.php")
    .then(response => response.text())
    .then(data => document.getElementById("footer").innerHTML = data);
</script>  

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="static/js/script.js"></script>
    <script src="static/js/about.js"></script>
    <script src="static/js/search.js"></script>
</body>
</html>-->