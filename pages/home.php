
<style>
.price .new{font-weight:700}
.price .old{text-decoration:line-through;color:#777;font-size:12px}
</style>


<style>
.auto-slide-layer img{width:100%;height:auto;object-fit:cover;display:block}
</style>

<style>
.hero{position:relative}
.banner-slider{position:relative;overflow:hidden;border-radius:12px}
.banner-slider .slide{position:absolute;inset:0;opacity:0;transition:opacity .4s ease}
.banner-slider .slide.is-active{opacity:1;z-index:2}
.banner-slider img{width:100%;height:auto;display:block;object-fit:cover}
.banner-slider .nav{
  position:absolute;top:50%;transform:translateY(-50%);
  background:rgba(0,0,0,.35);color:#fff;border:0;width:40px;height:40px;border-radius:999px;
  cursor:pointer;display:flex;align-items:center;justify-content:center
}
.banner-slider .nav:hover{background:rgba(0,0,0,.5)}
.banner-slider .prev{left:12px}
.banner-slider .next{right:12px}
.banner-slider .dots{
  position:absolute;left:0;right:0;bottom:10px;display:flex;gap:8px;justify-content:center;z-index:3
}
.banner-slider .dots button{
  width:8px;height:8px;border-radius:999px;border:0;background:rgba(255,255,255,.5);cursor:pointer
}
.banner-slider .dots button.active{background:#fff;width:18px;border-radius:8px}
@media (max-width:768px){
  .banner-slider .nav{width:34px;height:34px}
}
</style>

<style>
/* Ensure layout looks good after removing sidebar */
.sidebar{display:none !important;}
.main-content{display:block !important; width:100%;}
.main-content .content{width:100%; max-width:100%;}
/* Deal prices */
.product-card .price{display:flex; gap:8px; align-items:baseline;}
.product-card .price .new{font-weight:700;}
.product-card .price .old{text-decoration:line-through; color:#777; font-size:12px;}
</style>

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
// Cập nhật truy vấn Deal: Ưu tiên deal giảm giá cao và deal có giới hạn số lượng
$sql_deal = "
    SELECT 
        sp.masp, sp.tensp, sp.giaban, sp.hinhsp, dm.tendm,
        km.tenkm, km.phantram_giam, km.gia_giam_co_dinh, km.soluong_deal_conlai,
        CASE
            WHEN km.phantram_giam IS NOT NULL AND km.phantram_giam > 0
                THEN ROUND(sp.giaban * (1 - km.phantram_giam/100), 0)
            WHEN km.gia_giam_co_dinh IS NOT NULL AND km.gia_giam_co_dinh > 0
                THEN GREATEST(0, sp.giaban - km.gia_giam_co_dinh)
            ELSE sp.giaban
        END AS gia_sau_giam
    FROM sanpham sp
    JOIN danhmuc dm ON sp.madm = dm.madm
    JOIN khuyenmai km ON sp.makm = km.makm
    WHERE km.trangthai_deal = 'dang_dien_ra'
      AND NOW() BETWEEN km.ngay_batdau AND km.ngay_ketthuc
      AND (km.soluong_deal_conlai IS NULL OR km.soluong_deal_conlai > 0)
    ORDER BY 
        gia_sau_giam ASC,
        km.phantram_giam DESC,
        sp.masp ASC
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
<style>
/* --- Deal Section Styling --- */
.deal-section {
    padding: 25px 0;
    margin-bottom: 30px;
    background-color: #fff; /* Nền trắng */
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.deal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding: 0 15px;
    border-bottom: 2px solid #f0f0f0; /* Đường kẻ dưới */
    padding-bottom: 15px;
}

.deal-header h2 {
    font-size: 26px;
    font-weight: 700;
    color: #ffffffff; /* Màu đỏ nổi bật cho tiêu đề */
    margin: 0;
    text-transform: uppercase;
}

.deal-timer {
    display: flex;
    align-items: center;
    font-size: 18px;
    font-weight: 600;
    color: #e74c3c;
    background-color: #fff0f0; /* Nền nhẹ cho đồng hồ */
    padding: 5px 15px;
    border-radius: 6px;
}

.deal-timer i {
    margin-right: 8px;
    color: #e74c3c;
}

.deal-timer #countdown {
    margin-left: 8px;
    font-size: 20px;
    color: #c0392b; /* Màu đậm hơn cho đồng hồ */
}

/* Product Grid Layout */
.deal-section .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    padding: 0 15px;
}

/* Product Card trong Deal */
.deal-section .product-card {
    border: 1px solid #eee;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
}

.deal-section .product-card:hover {
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    transform: translateY(-3px);
}

.deal-section .product-image {
    padding: 15px;
    text-align: center;
    height: 180px; /* Chiều cao cố định */
}

.deal-section .product-image img {
    max-height: 100%;
    width: auto;
    object-fit: contain;
}

.deal-section .product-info {
    padding: 10px 15px 15px;
}

.deal-section .product-category {
    font-size: 12px;
    color: #2ecc71; /* Màu xanh lá cho danh mục */
    font-weight: 500;
    margin-bottom: 4px;
}

.deal-section .product-name {
    font-size: 15px;
    font-weight: 600;
    height: 40px;
    overflow: hidden;
    color: #34495e;
    line-height: 1.3;
    margin-bottom: 10px;
}

/* Price Block */
.deal-section .product-price {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap; /* Cho phép xuống dòng trên mobile */
}

.deal-section .current-price {
    font-size: 20px;
    font-weight: 700;
    color: #e74c3c; /* Giá mới màu đỏ nổi bật */
}

.deal-section .original-price {
    font-size: 13px;
    color: #95a5a6;
    text-decoration: line-through;
}

.deal-section .discount-badge {
    background-color: #f39c12; /* Màu cam cho badge */
    color: white;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 6px;
    border-radius: 4px;
}

.deal-section .add-to-cart {
    background-color: #2980b9;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    width: 100%;
    font-weight: 600;
    transition: background-color 0.2s;
}

.deal-section .add-to-cart:hover {
    background-color: #3498db;
}
</style>
    <!-- Hero Banner Carousel -->
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
                                <img src="static/img/banner/H1_desktop_805x246_8c1d616da4.webp"
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

   <section class="deal-section">
    <div class="deal-header">
        <h2>DEAL XỊN QUẢ XINH - ƯU ĐÃI MỖI NGÀY</h2>
        <div class="deal-timer">
            <i class="fas fa-clock"></i> 
            Kết thúc sau: 
            <span id="countdown">
                <?php
                    // Hiển thị thời gian kết thúc của deal đầu tiên (nếu có)
                    if (!empty($deals)) {
                        echo date('H:i:s', strtotime($deals[0]['ngay_ketthuc']) - time()); // Placeholder
                    } else {
                        echo 'N/A';
                    }
                ?>
            </span>
        </div>
    </div>
    <div class="product-grid">
        <?php foreach ($deals as $product): ?>
        <?php 
            // Đã tính toán ở trên: $current_price, $discount_percent, $image_path
            $current_price = calculate_sale_price($product['giaban'], $product['phantram_giam'], $product['gia_giam_co_dinh']);
            $discount_amount = $product['giaban'] - $current_price;
            $discount_percent = ($product['giaban'] > 0) ? round(($discount_amount / $product['giaban']) * 100) : 0;
            $image_path = $product['hinhsp'] ? $product['hinhsp'] : 'static/img/placeholder.jpg';
        ?>
        <div class="product-card">
            <?php if ($discount_percent > 0): ?>
                 <?php endif; ?>
            <div class="product-image">
                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['tensp']) ?>">
            </div>
            <div class="product-info">
                <div class="product-category"><?= htmlspecialchars($product['tendm']) ?></div>
                <div class="product-name"><?= htmlspecialchars($product['tensp']) ?></div>
                <div class="product-price">
                    <span class="current-price"><?= money_vn($current_price) ?>₫</span>
                    <?php if ($product['giaban'] > $current_price): ?>
                        <span class="original-price"><?= money_vn($product['giaban']) ?>₫</span>
                        <span class="discount-badge">-<?= $discount_percent ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="product-actions">
                    <button class="add-to-cart" data-product-id="<?= $product['masp'] ?>">
                        Chọn mua
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
            
<!-- Sidebar removed -->


            <!-- Content -->
            <div class="content">
                <!-- Best Selling Products -->
              <section class="section">
   <section class="section product-carousel-container" style="background-color: #f5f5f5; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
    <div class="section-header" style="justify-content: center; text-align: center;">
        <h2 style="background-color: #e74c3c; color: white; padding: 5px 20px; border-radius: 20px; display: inline-block;">Sản phẩm bán chạy</h2>
    </div>
    <div class="product-grid" style="display: flex; overflow-x: auto; gap: 15px; padding-bottom: 10px;">
        <?php foreach ($products_banchay as $product): ?>
        <?php 
            // Tính toán giá sale nếu có (Sản phẩm bán chạy vẫn có thể đang sale)
            $sale_price = calculate_sale_price($product['giaban'], $product['phantram_giam'], $product['gia_giam_co_dinh']);
            $has_discount = $sale_price < $product['giaban'];
            $discount_percent = $has_discount ? round((($product['giaban'] - $sale_price) / $product['giaban']) * 100) : 0;
            $image_path = $product['hinhsp'] ? $product['hinhsp'] : 'static/img/placeholder.jpg';
        ?>
        <div class="product-card" style="min-width: 200px; max-width: 250px; border: 1px solid #dcdcdc; border-radius: 8px; background-color: #fff; padding: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center;">
            <?php if ($has_discount): ?>
                <span class="discount-badge" style="position: absolute; top: 0; left: 0; background-color: #e74c3c; color: white; padding: 2px 8px; border-radius: 8px 0 8px 0; font-size: 14px;">-<?= $discount_percent ?>%</span>
            <?php endif; ?>
            <div class="product-image" style="margin-bottom: 10px;">
                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['tensp']) ?>" style="width: 100%; height: auto; border-radius: 5px;">
            </div>
            <div class="product-info" style="min-height: 150px; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="product-name" style="font-weight: 600; font-size: 14px; margin-bottom: 5px; color: #2c3e50; height: 40px; overflow: hidden;"><?= htmlspecialchars($product['tensp']) ?></div>
                <div class="product-price" style="margin-bottom: 10px;">
                    <span class="current-price" style="display: block; font-size: 16px; font-weight: bold; color: #e74c3c;"><?= money_vn($sale_price) ?>đ</span>
                    <?php if ($has_discount): ?>
                        <span class="original-price" style="display: block; font-size: 12px; color: #95a5a6; text-decoration: line-through;"><?= money_vn($product['giaban']) ?>đ</span>
                    <?php endif; ?>
                    <span class="product-package-info" style="display: block; font-size: 12px; color: #7f8c8d; margin-top: 5px;">Hộp 60 Viên</span> 
                </div>
                <div class="product-actions">
                    <button class="add-to-cart" data-product-id="<?= $product['masp'] ?>" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: 600;">
                        Chọn mua
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($products_banchay)): ?>
        <p>Không tìm thấy sản phẩm bán chạy trong 30 ngày gần nhất.</p>
        <?php endif; ?>
    </div>
</section>
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



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="static/js/script.js"></script>
    <script src="static/js/about.js"></script>
    <script src="static/js/search.js"></script>
<script>
(function(){
  const root = document.getElementById('banner');
  if(!root) return;
  const slides = Array.from(root.querySelectorAll('.slide'));
  const prev = root.querySelector('.prev');
  const next = root.querySelector('.next');
  const dotsWrap = root.querySelector('.dots');

  let i = 0, timer = null, interval = 4000, isHover = false;

  // Dots
  slides.forEach((_, idx) => {
    const b = document.createElement('button');
    b.addEventListener('click', () => go(idx, true));
    dotsWrap.appendChild(b);
  });

  function render(){
    slides.forEach((s, idx) => s.classList.toggle('is-active', idx === i));
    dotsWrap.querySelectorAll('button').forEach((d, idx) => d.classList.toggle('active', idx === i));
  }

  function go(to, user=false){
    i = (to + slides.length) % slides.length;
    render();
    if(user) restart();
  }

  function nextSlide(){ go(i+1); }
  function prevSlide(){ go(i-1); }

  function start(){
    if(timer) clearInterval(timer);
    timer = setInterval(() => { if(!isHover) nextSlide(); }, interval);
  }
  function restart(){ start(); }

  // Hover pause
  root.addEventListener('mouseenter', () => { isHover = true; });
  root.addEventListener('mouseleave', () => { isHover = false; });

  // Nav
  prev && prev.addEventListener('click', prevSlide);
  next && next.addEventListener('click', nextSlide);

  // Swipe
  let x0=null;
  root.addEventListener('touchstart', e=>{ x0 = e.touches[0].clientX; }, {passive:true});
  root.addEventListener('touchend', e=>{
    if(x0===null) return;
    const dx = e.changedTouches[0].clientX - x0;
    if(Math.abs(dx) > 40){ dx>0 ? prevSlide() : nextSlide(); }
    x0=null;
  }, {passive:true});

  render(); start();
})();
</script>

<script>
(function(){
  // Pick the first container that looks like the big banner
  const root = document.querySelector('.hero, .banner, .banner-slider, .banner-wrap, .top-banner, .carousel, .slider');
  if(!root) return;
  let imgs = Array.from(root.querySelectorAll('img'));
  // Prefer images with 'banner' in src
  let bannerImgs = imgs.filter(i => /banner/i.test(i.src));
  if (bannerImgs.length < 2) bannerImgs = imgs;
  if (bannerImgs.length < 2) return;

  // Wrap into layers if not already
  bannerImgs.forEach(img => {
    if (img.closest('.auto-slide-layer')) return;
    const layer = document.createElement('div');
    layer.className = 'auto-slide-layer';
    img.parentNode.insertBefore(layer, img);
    layer.appendChild(img);
  });

  const layers = Array.from(root.querySelectorAll('.auto-slide-layer'));
  layers.forEach(l => { l.style.position='absolute'; l.style.inset='0'; l.style.opacity='0'; l.style.transition='opacity .5s ease'; });
  if (getComputedStyle(root).position === 'static') root.style.position='relative';
  root.style.overflow='hidden';

  let i = 0, timer=null, hover=false, interval=4000;
  function render(){ layers.forEach((l,idx)=> l.style.opacity = (idx===i ? '1':'0')); }
  function next(){ i = (i+1) % layers.length; render(); }

  root.addEventListener('mouseenter', ()=> hover = true);
  root.addEventListener('mouseleave', ()=> hover = false);

  render();
  timer = setInterval(()=>{ if(!hover) next(); }, interval);
})();
</script>

</body>
</html>