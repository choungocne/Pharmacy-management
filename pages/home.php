<?php
// ================== 1. CẤU HÌNH & PHP LOGIC (GIỮ NGUYÊN) ==================
if (file_exists('db.php')) { include_once 'db.php'; } elseif (file_exists('../db.php')) { include_once '../db.php'; }
$base_url = '/Pharmacy-management'; 

if (!function_exists('calculate_sale_price')) {
    function calculate_sale_price($giaban, $phantram_giam, $gia_giam_co_dinh) {
        $sale_price = $giaban;
        if ($phantram_giam > 0) $sale_price = $giaban * (1 - $phantram_giam / 100);
        if ($gia_giam_co_dinh > 0) $sale_price = $sale_price - $gia_giam_co_dinh;
        return max(0, $sale_price);
    }
}
if (!function_exists('money_vn')) {
    function money_vn($number) { return number_format($number, 0, ',', '.') . '₫'; }
}

// --- Truy vấn dữ liệu ---
$brands = [];
try { $brands = pdo()->query("SELECT math, tenth, logo_url FROM thuonghieu ORDER BY math LIMIT 5")->fetchAll(); } catch (Exception $e) { }
$brand_discounts = ['Blackmores'=>'Giảm 25%','Nature\'s Way'=>'Giảm 20%','Healthy Care'=>'Giảm 30%','Kirkland Signature'=>'Giảm 15%','Omron'=>'Giảm 10%'];

$deals = [];
try {
    $sql_deal = "SELECT sp.masp, sp.tensp, sp.giaban, sp.hinhsp, dm.tendm, km.phantram_giam, km.gia_giam_co_dinh, km.ngay_ketthuc FROM sanpham sp JOIN danhmuc dm ON sp.madm=dm.madm JOIN khuyenmai km ON sp.makm=km.makm WHERE km.trangthai_deal='dang_dien_ra' AND NOW() BETWEEN km.ngay_batdau AND km.ngay_ketthuc ORDER BY km.phantram_giam DESC LIMIT 4";
    $deals = pdo()->query($sql_deal)->fetchAll();
} catch (Exception $e) { }

$products_banchay = [];
$products_banchay_ids = [1, 13, 30, 41]; 
$id_list = implode(',', $products_banchay_ids);
try {
    $sql_banchay = "SELECT sp.masp, sp.tensp, sp.giaban, sp.hinhsp, dm.tendm, km.phantram_giam, km.gia_giam_co_dinh FROM sanpham sp JOIN danhmuc dm ON sp.madm=dm.madm LEFT JOIN khuyenmai km ON sp.makm=km.makm WHERE sp.masp IN ({$id_list}) ORDER BY FIELD(sp.masp, {$id_list})";
    $products_banchay = pdo()->query($sql_banchay)->fetchAll();
} catch (Exception $e) { }
?>

<style>
    /* --- CẤU TRÚC CHUNG --- */
    body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; overflow-x: hidden; }
    .sidebar { display: none !important; }
    .main-content { width: 100%; display: block; }
    .container-custom { max-width: 1240px; margin: 0 auto; padding: 0 15px; }
    a { text-decoration: none !important; transition: 0.3s; }
    img { max-width: 100%; }

    /* --- BANNER (GIỮ NGUYÊN) --- */
    .hero-banner { margin-bottom: 20px; }
    .auto-slide-layer img { width: 100%; height: auto; object-fit: cover; }
    .banner-slider { position: relative; overflow: hidden; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .carousel-slide { display: none; opacity: 0; transition: opacity 0.5s ease; }
    .carousel-slide.active { display: block; opacity: 1; }
    .carousel-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; z-index: 10; transition: 0.3s; color: #333; }
    .carousel-prev { left: 20px; } .carousel-next { right: 20px; }

    /* --- SECTION TITLES --- */
    .section-header { text-align: center; margin-bottom: 35px; position: relative; }
    .section-title { 
        font-size: 26px; font-weight: 800; color: #1e293b; text-transform: uppercase; 
        display: inline-block; position: relative; z-index: 1; padding: 0 15px;
    }
    .section-title::before {
        content: ''; position: absolute; width: 40px; height: 3px; background: #0284c7; 
        bottom: -10px; left: 50%; transform: translateX(-50%);
    }

    /* --- 1. DEAL SECTION (HỒNG NHẸ) --- */
    .deal-section { background: linear-gradient(180deg, #fff0f5 0%, #fff 100%); padding: 40px 20px; border-radius: 20px; margin-bottom: 40px; }
    .deal-timer-badge { background: #e11d48; color: white; padding: 5px 15px; border-radius: 20px; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; }
    
    .product-card {
        background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        transition: 0.3s; display: flex; flex-direction: column; overflow: hidden; position: relative;
    }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); border-color: #cbd5e1; }
    .product-img-wrap { height: 200px; padding: 15px; display: flex; align-items: center; justify-content: center; background: #fff; }
    .product-img-wrap img { max-height: 100%; object-fit: contain; transition: 0.3s; }
    .product-card:hover .product-img-wrap img { transform: scale(1.05); }
    .product-info { padding: 15px; text-align: center; flex-grow: 1; display: flex; flex-direction: column; }
    .product-name { font-size: 15px; font-weight: 600; color: #334155; margin-bottom: 10px; height: 44px; overflow: hidden; line-height: 1.4; }
    .product-name a { color: inherit; }
    .product-name a:hover { color: #0284c7; }
    .price-wrap { display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 15px; }
    .price-new { font-size: 18px; font-weight: 700; color: #e11d48; }
    .price-old { font-size: 13px; color: #94a3b8; text-decoration: line-through; }
    .btn-buy {
        background: #e11d48; color: white; border: none; padding: 10px; border-radius: 8px; width: 100%;
        font-weight: 600; cursor: pointer; transition: 0.2s;
    }
    .btn-buy:hover { background: #be123c; }

    /* --- 2. SẢN PHẨM BÁN CHẠY (TOP RANKING STYLE) --- */
    .banchay-section {
        background: #ffffff;
        padding: 40px 20px;
        border-radius: 20px;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05);
        margin-bottom: 50px;
        position: relative;
        overflow: hidden;
    }
    /* Trang trí nền cho bán chạy */
    .banchay-section::before {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 6px;
        background: linear-gradient(90deg, #f59e0b, #fbbf24, #f59e0b);
    }

    .ranking-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    @media (max-width: 992px) { .ranking-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .ranking-grid { grid-template-columns: 1fr; } }

    .rank-card {
        background: #fff;
        border: 1px solid #f3f4f6;
        border-radius: 12px;
        padding: 15px;
        position: relative;
        transition: all 0.3s ease;
        text-align: center;
    }
    .rank-card:hover {
        border-color: #fbbf24;
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(251, 191, 36, 0.15);
    }
    /* Badge Top 1, 2, 3 */
    .rank-badge {
        position: absolute; top: 10px; left: 10px; z-index: 2;
        width: 32px; height: 32px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        font-weight: 800; font-size: 14px; color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .rank-1 { background: linear-gradient(135deg, #FFD700, #FDB931); border: 2px solid #fff; width: 40px; height: 40px; font-size: 16px; top: -5px; left: -5px; }
    .rank-2 { background: #C0C0C0; }
    .rank-3 { background: #CD7F32; }
    .rank-other { background: #64748b; font-size: 12px; width: 28px; height: 28px; }

    .rank-img { height: 160px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .rank-img img { max-height: 100%; max-width: 100%; object-fit: contain; }
    
    .rank-btn {
        margin-top: 10px;
        background: #fff;
        color: #d97706;
        border: 1px solid #d97706;
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: 0.2s;
    }
    .rank-btn:hover { background: #fffbeb; }

/* --- BRAND LIST (ĐÃ SỬA TO ĐẸP) --- */
    .brand-list { 
        display: flex; 
        flex-wrap: wrap; 
        justify-content: center; 
        gap: 25px; /* Tăng khoảng cách */
        margin-bottom: 50px; 
    }
    .brand-item { 
        background: white; 
        border-radius: 16px; 
        width: 220px;       /* TĂNG CHIỀU RỘNG */
        padding: 30px 15px; /* TĂNG PADDING */
        text-align: center; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        transition: 0.3s; 
        border: 1px solid #f1f5f9;
    }
    .brand-item:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 15px 30px rgba(0,0,0,0.1); 
        border-color: #38bdf8;
    }
    .brand-item img { 
        height: 70px;        /* TĂNG KÍCH THƯỚC LOGO */
        width: 100%;
        object-fit: contain; 
        margin-bottom: 15px; 
    }
    .brand-name-text {
        font-weight: 700; color: #334155; font-size: 16px; margin-bottom: 8px;
    }
    .discount-tag { 
        font-size: 13px;     /* CHỮ TO HƠN */
        background: #e0f2fe; 
        color: #0284c7; 
        padding: 5px 12px;   /* TAG TO HƠN */
        border-radius: 6px; 
        font-weight: 600; 
        display: inline-block;
    }
     /* --- 4. GÓC SỨC KHỎE (MEDICAL DASHBOARD STYLE) --- */
    .health-section { margin-bottom: 60px; }
    
    /* Tabs đẹp như nút chọn */
    .health-tabs { display: flex; justify-content: center; gap: 15px; margin-bottom: 40px; }
    .h-tab-btn {
        background: #fff; border: none; padding: 12px 30px; border-radius: 12px;
        font-weight: 600; color: #64748b; cursor: pointer; transition: 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 8px;
    }
    .h-tab-btn i { font-size: 18px; }
    .h-tab-btn:hover, .h-tab-btn.active { 
        background: #0ea5e9; color: white; transform: translateY(-2px); 
        box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
    }

    /* Grid nội dung */
    .health-content { display: none; grid-template-columns: repeat(4, 1fr); gap: 25px; }
    .health-content.active { display: grid; }
    @media (max-width: 992px) { .health-content { grid-template-columns: repeat(2, 1fr); } }

    /* Card bài viết đẹp */
    .health-card {
        background: #fff; border-radius: 16px; overflow: hidden; text-align: center;
        border: 1px solid #e2e8f0; transition: 0.3s;
        display: flex; flex-direction: column; align-items: center; padding: 30px 20px;
    }
    .health-card:hover {
        border-color: #38bdf8; transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.05);
    }
    
    .health-icon-box {
        width: 70px; height: 70px; border-radius: 50%; margin-bottom: 20px;
        background: #f0f9ff; display: flex; align-items: center; justify-content: center;
        border: 1px solid #bae6fd;
    }
    .health-icon-box img { width: 40px; height: 40px; object-fit: contain; }
    
    .health-title { font-size: 18px; font-weight: 700; color: #334155; margin-bottom: 10px; }
    .health-list { list-style: none; padding: 0; margin: 0 0 20px 0; font-size: 14px; color: #64748b; line-height: 1.6; }
    .health-list li { margin-bottom: 5px; }
    
    .health-link {
        color: #0284c7; font-weight: 600; font-size: 14px; 
        display: inline-flex; align-items: center; gap: 5px;
    }
    .health-link:hover { text-decoration: underline !important; gap: 8px; }

    /* Toast */
    #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    .toast-msg { background: #fff; border-left: 5px solid #22c55e; padding: 15px 25px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; margin-bottom: 10px; animation: slideIn 0.3s ease; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

</style>

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

<section class="container-custom" style="margin-top: 30px; margin-bottom: 50px;">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <div style="border-radius: 12px; overflow: hidden;"><img src="static/img/banner/D_H1_Desktop_1200x367_0a2663616c.webp" style="width:100%; height:100%; object-fit:cover;"></div>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="border-radius: 12px; overflow: hidden;"><img src="static/img/banner/Banner_Ung_Thu_1_185705d391.webp" style="width:100%;"></div>
            <div style="border-radius: 12px; overflow: hidden;"><img src="static/img/banner/Banner_H2_2893cabcaa.webp" style="width:100%;"></div>
        </div>
    </div>
</section>

<div class="container-custom">
    <section class="deal-section">
        <div class="section-header">
            <h2 class="section-title" style="color: #be123c;">🔥 Deal Xịn Giá Sốc</h2>
            <br>
            <div class="deal-timer-badge">
                <i class="fas fa-clock"></i> Kết thúc sau: 
                <span id="countdown"><?php echo !empty($deals) ? date('H:i:s', strtotime($deals[0]['ngay_ketthuc']) - time()) : '05:00:00'; ?></span>
            </div>
        </div>

        <div class="product-grid">
            <?php foreach ($deals as $sp): 
                $price = calculate_sale_price($sp['giaban'], $sp['phantram_giam'], $sp['gia_giam_co_dinh']);
                $img = $sp['hinhsp'] ?: 'static/img/placeholder.jpg';
                $link = "base.php?page=detailsproducts&masp=" . $sp['masp'];
            ?>
            <div class="product-card">
                <div class="product-img-wrap">
                    <a href="<?= $link ?>"><img src="<?= $img ?>" alt="<?= htmlspecialchars($sp['tensp']) ?>"></a>
                </div>
                <div class="product-info">
                    <div class="product-cate"><?= htmlspecialchars($sp['tendm']) ?></div>
                    <div class="product-name"><a href="<?= $link ?>"><?= htmlspecialchars($sp['tensp']) ?></a></div>
                    <div class="price-wrap">
                        <span class="price-new"><?= money_vn($price) ?></span>
                        <?php if($sp['giaban'] > $price): ?><span class="price-old"><?= money_vn($sp['giaban']) ?></span><?php endif; ?>
                    </div>
                    <button class="btn-buy" onclick="addToCart(<?= $sp['masp'] ?>)">Chọn Mua</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="container-custom">
    <div class="banchay-section">
        <div class="section-header">
            <h2 class="section-title" style="color: #d97706;">🏆 Top Bán Chạy Nhất</h2>
        </div>

        <div class="ranking-grid">
            <?php 
            $rank = 0;
            foreach ($products_banchay as $sp): 
                $rank++;
                $rank_class = ($rank == 1) ? 'rank-1' : (($rank == 2) ? 'rank-2' : (($rank == 3) ? 'rank-3' : 'rank-other'));
                
                $price = calculate_sale_price($sp['giaban'], $sp['phantram_giam'], $sp['gia_giam_co_dinh']);
                $img = $sp['hinhsp'] ?: 'static/img/placeholder.jpg';
                $link = "base.php?page=detailsproducts&masp=" . $sp['masp'];
            ?>
            <div class="rank-card">
                <div class="rank-badge <?= $rank_class ?>"><?= $rank ?></div>
                
                <div class="rank-img">
                    <a href="<?= $link ?>"><img src="<?= $img ?>" alt="<?= htmlspecialchars($sp['tensp']) ?>"></a>
                </div>
                <div class="product-name" style="margin-bottom: 5px;">
                    <a href="<?= $link ?>"><?= htmlspecialchars($sp['tensp']) ?></a>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #d97706; margin-bottom: 10px;">
                    <?= money_vn($price) ?>
                </div>
                <button class="rank-btn" onclick="addToCart(<?= $sp['masp'] ?>)">
                    <i class="fas fa-cart-plus"></i> Thêm ngay
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div class="container-custom">
    <div class="section-header"><h2 class="section-title">Thương Hiệu Nổi Bật</h2></div>
    <div class="brand-list">
        <?php foreach ($brands as $brand): 
            $img = $brand['logo_url'] ?: 'static/img/placeholder.jpg';
            $promo = $brand_discounts[$brand['tenth']] ?? 'Ưu đãi tốt';
        ?>
        <div class="brand-item">
            <img src="<?= $img ?>" onerror="this.src='static/img/placeholder.jpg';">
            <div class="brand-name-text"><?= htmlspecialchars($brand['tenth']) ?></div>
            <div class="discount-tag"><?= $promo ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="container-custom health-section">
    <div class="section-header">
        <h2 class="section-title" style="color: #0284c7;">💙 Góc Sức Khỏe</h2>
    </div>

    <div class="health-tabs">
        <button class="h-tab-btn active" onclick="switchHealthTab('target', this)">
            <i class="fas fa-users"></i> Theo Đối Tượng
        </button>
        <button class="h-tab-btn" onclick="switchHealthTab('season', this)">
            <i class="fas fa-cloud-sun"></i> Bệnh Theo Mùa
        </button>
    </div>

    <div id="target" class="health-content active">
        <div class="health-card">
            <div class="health-icon-box"><img src="static/img/nam_gioi.webp"></div>
            <h3 class="health-title">Nam Giới</h3>
            <ul class="health-list">
                <li>Tăng cường sinh lý</li>
                <li>Xương khớp, vận động</li>
            </ul>
            <a href="base.php?page=search_disease" class="health-link">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="health-card">
            <div class="health-icon-box"><img src="static/img/nu_gioi.jpg"></div>
            <h3 class="health-title">Nữ Giới</h3>
            <ul class="health-list">
                <li>Sắc đẹp, nội tiết</li>
                <li>Chăm sóc mẹ & bé</li>
            </ul>
            <a href="base.php?page=search_disease" class="health-link">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="health-card">
            <div class="health-icon-box"><img src="static/img/nguoi_gia.png"></div>
            <h3 class="health-title">Người Cao Tuổi</h3>
            <ul class="health-list">
                <li>Tim mạch, huyết áp</li>
                <li>Bổ não, trí nhớ</li>
            </ul>
            <a href="base.php?page=search_disease" class="health-link">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="health-card">
            <div class="health-icon-box"><img src="static/img/tre_em.webp"></div>
            <h3 class="health-title">Trẻ Em</h3>
            <ul class="health-list">
                <li>Tăng đề kháng</li>
                <li>Phát triển chiều cao</li>
            </ul>
            <a href="base.php?page=search_disease" class="health-link">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <div id="season" class="health-content">
        <div class="health-card">
            <div class="health-icon-box"><img src="static/img/sot-xuat-huyet.webp"></div>
            <h3 class="health-title">Sốt Xuất Huyết</h3>
            <p style="font-size:13px; color:#666;">Phòng ngừa muỗi đốt và các biện pháp xử lý...</p>
            <a href="base.php?page=search_disease" class="health-link">Tìm hiểu ngay</a>
        </div>
        <div class="health-card">
            <div class="health-icon-box"><img src="static/img/cum.png"></div>
            <h3 class="health-title">Cúm Mùa</h3>
            <p style="font-size:13px; color:#666;">Tăng đề kháng, tiêm vắc xin phòng cúm...</p>
            <a href="base.php?page=search_disease" class="health-link">Tìm hiểu ngay</a>
        </div>
        <div class="health-card">
            <div class="health-icon-box"><img src="static/img/tcm.jpeg"></div>
            <h3 class="health-title">Tay Chân Miệng</h3>
            <p style="font-size:13px; color:#666;">Dấu hiệu nhận biết sớm ở trẻ nhỏ...</p>
            <a href="base.php?page=search_disease" class="health-link">Tìm hiểu ngay</a>
        </div>
    </div>
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

<div id="toast-container"></div>

<script>
    const BASE_URL = '<?= $base_url ?>';

    // 1. Switch Tab Health
    function switchHealthTab(id, btn) {
        document.querySelectorAll('.health-content').forEach(el => el.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        document.querySelectorAll('.h-tab-btn').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
    }

    // 2. Banner Auto Slide
    (function(){
        const slides = document.querySelectorAll('.carousel-slide');
        let idx = 0;
        const show = (i) => slides.forEach((s, n) => s.classList.toggle('active', n === i));
        document.querySelector('.carousel-next').onclick = () => { idx = (idx+1)%slides.length; show(idx); }
        document.querySelector('.carousel-prev').onclick = () => { idx = (idx-1+slides.length)%slides.length; show(idx); }
        setInterval(() => { idx = (idx+1)%slides.length; show(idx); }, 5000);
    })();

    // 3. Add to Cart
    function addToCart(pid) {
        const f = new FormData();
        f.append('action','add'); f.append('product_id',pid); f.append('quantity',1);
        fetch(BASE_URL+'/cart_handler.php', { method:'POST', body:f })
        .then(r=>r.json())
        .then(d => {
            if (d.success) {
                showToast('Đã thêm vào giỏ hàng thành công!');
                const cnt = (typeof d.cart_count !== 'undefined') ? d.cart_count : (d.count ?? d.total_items);
                if (typeof updateHeaderCartCount === 'function' && typeof cnt !== 'undefined') {
                    updateHeaderCartCount(cnt);
                }
                if (typeof refreshHeaderCart === 'function') {
                    refreshHeaderCart();
                } else if (typeof viewCart === 'function') {
                    viewCart();
                }
                if (typeof showHeaderMiniCart === 'function') {
                    showHeaderMiniCart();
                }
            } else {
                showToast(d.message || 'Lỗi', 'error');
            }
        })
        .catch(() => showToast('Lỗi kết nối server!', 'error'));
    }

    // 4. Toast
    function showToast(msg, type='success') {
        const t = document.createElement('div');
        t.className = 'toast-msg';
        if(type==='error') t.style.borderLeftColor = '#ef4444';
        t.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}" style="color:${type==='success'?'#22c55e':'#ef4444'}"></i> <span style="font-weight:600; font-size:14px; color:#333;">${msg}</span>`;
        document.getElementById('toast-container').appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }
</script>
