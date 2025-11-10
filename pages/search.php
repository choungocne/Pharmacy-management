<?php
// =================================================================
// TỆP: search.php (Tra cứu Thuốc, Dược chất, Dược liệu)
// =================================================================

// Yêu cầu kết nối CSDL (chứa hàm pdo()) và hàm money_vn()
require_once 'db.php'; 

// Lấy base URL từ base.php (Cần cho các liên kết)
global $base_url; // Giả định base.php đã include biến này
if (!isset($base_url)) $base_url = '/Pharmacy-management'; 

// =================================================================
// PHẦN 1: HÀM XỬ LÝ DỮ LIỆU
// =================================================================

/**
 * Lấy danh sách các danh mục cấp 3 thuộc Thuốc (Nhóm trị liệu)
 * @return array Danh sách danh mục
 */
function get_drug_therapy_categories(): array {
    $pdo = pdo(); 
    // Lấy các danh mục cấp 3 liên kết trực tiếp với Thuốc (madm=2)
    $sql = "SELECT dm.madm, dm.tendm, dm.img_url, 
            (SELECT COUNT(masp) FROM sanpham sp WHERE sp.madm = dm.madm AND sp.trangthai = 1) AS product_count
            FROM danhmuc dm
            WHERE dm.cap = 3 AND dm.parent_id = 2
            LIMIT 8"; // Giới hạn 8 nhóm trị liệu chính

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy danh sách Dược liệu nổi bật (Top 8)
 * @return array Danh sách Dược liệu
 */
function get_featured_duoclieu(): array {
    $pdo = pdo();
    $sql = "SELECT madl, tendl, hinh_anh, cong_dung FROM duoclieu WHERE trangthai = 1 LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy danh sách Dược chất nổi bật (Top 8)
 * @return array Danh sách Dược chất
 */
function get_featured_duocchat(): array {
    $pdo = pdo();
    $sql = "SELECT madc, tendc, phan_loai, anh_cau_truc FROM duocchat WHERE trangthai = 1 LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm chuyển tên danh mục thành value cho radio button
function get_filter_value($tendm) {
    $tendm = strtolower(str_replace('Tra cứu ', '', $tendm));
    if ($tendm == 'dược chất') return 'duocchat';
    if ($tendm == 'dược liệu') return 'duoclieu';
    return 'drug'; // Mặc định cho Tra cứu thuốc
}

/**
 * Lấy các danh mục cấp 2 liên quan đến 'Tra cứu' (Tra cứu thuốc, Tra cứu dược chất, Tra cứu dược liệu)
 * @return array Danh sách danh mục
 */
function get_drug_search_categories() {
    $pdo = pdo(); 
    // Lấy TPCN, Thuốc (có madm 1 và 2) và Thiết bị Y tế (có madm 3)
    $sql = "SELECT madm, tendm FROM danhmuc WHERE parent_id IN (1, 2, 3) AND cap = 2 ORDER BY madm";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// =================================================================
// PHẦN 2: XỬ LÝ YÊU CẦU & THIẾT LẬP BIẾN
// =================================================================
$therapy_categories = get_drug_therapy_categories();
$featured_duoclieu = get_featured_duoclieu();
$featured_duocchat = get_featured_duocchat();

$search_categories = [
    ['tendm' => 'Thuốc/Sản phẩm', 'value' => 'drug'],
    ['tendm' => 'Dược chất', 'value' => 'duocchat'],
    ['tendm' => 'Dược liệu', 'value' => 'duoclieu'],
];

$page_css = '<link rel="stylesheet" href="static/css/search.css">'; 
?>

    <?= $page_css ?>

    <div class="breadcrumb">
      <a href="<?= $base_url ?>/base.php?page=home">Trang chủ</a> / <span>Tra cứu</span>
    </div>
<hr>
  <section class="search-section">
    <div class="search-box">
      <div class="search-left">
        <h1>Tra cứu Thuốc, Dược Chất & Dược Liệu</h1>
        <p class="intro-text">Tìm kiếm thông tin chi tiết về sản phẩm, thành phần hóa học và các loại thảo dược y tế.</p>
        
       <form id="searchForm" action="<?= $base_url ?>/base.php" method="GET">
    <input type="hidden" name="page" id="pageTarget" value="search_results_drug"> 
    
    <div class="search-bar">
        <input type="text" 
               placeholder="Nhập tên thuốc, dược chất, dược liệu..." 
               id="searchInput" 
               name="q" 
               required>
        <button type="submit" id="searchBtn">
            <i class="fas fa-search"></i> Tra cứu
        </button>
    </div>
    
    <div class="filters">
        <p class="filter-label">Bạn muốn tra cứu:</p>
        <?php foreach ($search_categories as $cat): ?>
            <label>
                <input type="radio" 
                       name="type" 
                       value="<?= $cat['value'] ?>" 
                       data-target="search_results_<?= $cat['value'] ?>"
                       <?= ($cat['value'] == 'drug' ? 'checked' : '') ?>> 
                <?= htmlspecialchars($cat['tendm']) ?>
            </label>
        <?php endforeach; ?>
    </div>
</form>
      </div>
      <div class="search-img">
        <img src="<?= $base_url ?>/static/img/pngtree-herbal-supplements-and-ingredients-illustration-png-image_18813782.png" alt="Pharmacist illustration">
      </div>
    </div>
  </section>

<hr>
  <section class="featured-section">
    <div class="container">
      <h2>Dược liệu nổi bật</h2>
      <div class="grid featured-grid duoclieu-grid">
        <?php if (!empty($featured_duoclieu)): ?>
            <?php foreach ($featured_duoclieu as $item): ?>
                <a href="<?= $base_url ?>/base.php?page=duoclieu_detail&madl=<?= $item['madl'] ?>" class="card small-card duoclieu-card">
                    <img src="<?= htmlspecialchars($item['hinh_anh'] ?? $base_url . '/static/img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['tendl']) ?>">
                    <div class="text">
                        <h3><?= htmlspecialchars($item['tendl']) ?></h3>
                        <p title="<?= htmlspecialchars($item['cong_dung']) ?>">Công dụng chính: <?= htmlspecialchars(substr($item['cong_dung'], 0, 50)) ?>...</p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1 / -1;">Hiện không có Dược liệu nổi bật nào.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>
<hr>
  <section class="featured-section">
    <div class="container">
      <h2>Dược chất nổi bật</h2>
      <div class="grid featured-grid duocchat-grid">
        <?php if (!empty($featured_duocchat)): ?>
            <?php foreach ($featured_duocchat as $item): ?>
                <a href="<?= $base_url ?>/base.php?page=duocchat_detail&madc=<?= $item['madc'] ?>" class="card small-card duocchat-card">
                    <img src="<?= htmlspecialchars($item['anh_cau_truc'] ?? $base_url . '/static/img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['tendc']) ?>" onerror="this.onerror=null;this.src='<?= $base_url ?>/static/img/placeholder.jpg'">
                    <div class="text">
                        <h3><?= htmlspecialchars($item['tendc']) ?></h3>
                        <span class="phan-loai"><?= htmlspecialchars($item['phan_loai'] ?? 'N/A') ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1 / -1;">Hiện không có Dược chất nổi bật nào.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>


<style>
/* CSS Đã Cải Thiện Cho Trang Tra Cứu */
.search-section {
    padding: 40px 0;
    background: linear-gradient(135deg, #f0f8ff, #e6f7ff);
}
.search-box {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    gap: 50px;
    align-items: center;
    padding: 0 20px;
}
.search-left {
    flex: 2;
}
.search-left h1 {
    font-size: 36px;
    font-weight: 700;
    color: #004aad;
    margin-bottom: 10px;
}
.intro-text {
    color: #555;
    margin-bottom: 25px;
}
.search-img {
    flex: 1;
    text-align: center;
    max-width: 300px;
}
.search-img img {
    width: 100%;
    height: auto;
    opacity: 0.8;
}
.search-bar {
    display: flex;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 15px;
}
.search-bar input[type="text"] {
    flex-grow: 1;
    padding: 15px 20px;
    border: none;
    font-size: 16px;
}
.search-bar button {
    background-color: #004aad;
    color: white;
    padding: 15px 20px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s;
}
.search-bar button:hover {
    background-color: #003a80;
}
.search-bar button svg {
    stroke: white !important;
}

.filters {
    display: flex;
    gap: 25px;
    align-items: center;
    color: #333;
}
.filter-label {
    font-weight: 600;
    margin: 0;
}
.filters input[type="radio"] {
    margin-right: 5px;
}
.filters label {
    font-size: 15px;
    cursor: pointer;
}

/* Category & Featured Sections */
.category-section, .featured-section {
    padding: 30px 0;
}
.category-section h2, .featured-section h2 {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 25px;
    border-bottom: 2px solid #eee;
    padding-bottom: 5px;
}
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}
.grid {
    display: grid;
    gap: 20px;
}
.therapy-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}
.featured-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}

.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    text-align: center;
    padding: 15px;
    display: block;
    text-decoration: none;
    color: inherit;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}
.card img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    margin: 0 auto 10px;
}
.card h3 {
    font-size: 16px;
    font-weight: 600;
    color: #34495e;
    margin-bottom: 5px;
}
.card p {
    font-size: 14px;
    color: #7f8c8d;
    height: 36px;
    overflow: hidden;
}
.duoclieu-card { border-bottom: 3px solid #28a745; }
.duocchat-card { border-bottom: 3px solid #007bff; }
.phan-loai { font-size: 13px; color: #7f8c8d; display: block; margin-top: 5px; }

</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageTarget = document.getElementById('pageTarget');
    const filterRadios = document.querySelectorAll('input[name="type"]');
    
    // Hàm cập nhật trường ẩn 'pageTarget' dựa trên radio button được chọn
    function updatePageTarget() {
        const selectedFilter = document.querySelector('input[name="type"]:checked');
        if (selectedFilter) {
            // Lấy giá trị data-target (ví dụ: search_results_drug, search_results_duocchat)
            const targetPage = selectedFilter.getAttribute('data-target');
            pageTarget.value = targetPage; 
        }
    }

    // Lắng nghe sự kiện thay đổi radio button
    filterRadios.forEach(radio => {
        radio.addEventListener('change', updatePageTarget);
    });
    
    // Khởi tạo trạng thái ban đầu khi tải trang (Mặc định là 'drug')
    // Nếu giá trị khởi tạo bị sai, ta sửa lại:
    pageTarget.value = 'search_results_drug'; 
    updatePageTarget(); // Chạy lần đầu để đảm bảo giá trị đúng
});
</script>