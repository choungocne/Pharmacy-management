<?php
// =================================================================
// TỆP: search.php (Tra cứu Thuốc, Dược chất, Dược liệu)
// LƯU Ý: Đổi tên thành search.php trong thư mục pages/
// =================================================================

// Yêu cầu kết nối CSDL (chứa hàm pdo()) và hàm money_vn()
require_once 'db.php'; 

// Lấy base URL từ base.php (Cần cho các liên kết)
global $base_url; // Giả định base.php đã include biến này

// =================================================================
// PHẦN 1: HÀM XỬ LÝ DỮ LIỆU
// =================================================================

/**
 * Lấy danh sách các danh mục cấp 3 thuộc Thuốc (Nhóm trị liệu)
 * @return array Danh sách danh mục
 */
function get_drug_therapy_categories(): array {
    $pdo = pdo(); // Lấy đối tượng PDO
    // Truy vấn tất cả danh mục cấp 3 có parent_id là madm của 'Thuốc' (madm=2)
    // Hoặc cấp 3 thuộc các danh mục con của 'Thuốc'
    
    // Cách 1: Lấy trực tiếp các danh mục cấp 3 có parent_id = 2 (như trong dữ liệu mẫu) [cite: 93]
    // Hoặc madm của các danh mục cấp 2 của Thuốc (vd: Tra cứu thuốc, TPCN,...)

    // Phương án tốt nhất: Lấy các danh mục cấp 3 được tạo mới liên quan đến trị liệu (parent_id = 2) [cite: 93]
    $sql = "SELECT dm.tendm, dm.img_url, 
            (SELECT COUNT(masp) FROM sanpham sp WHERE sp.madm = dm.madm AND sp.trangthai = 1) AS product_count
            FROM danhmuc dm
            WHERE dm.cap = 3 
            AND dm.parent_id = 2"; // Lọc các danh mục cấp 3 được chèn trực tiếp dưới Thuốc (madm=2) [cite: 93]

    // THÊM CÁC DANH MỤC CẤP 3 KHÁC CÓ THỂ LÀ CẤP 2 CỦA TPCN (madm=1) nếu muốn hiển thị chung.
    // Nếu chỉ hiển thị Nhóm Trị Liệu THUỐC (đúng theo tiêu đề), chỉ dùng truy vấn trên.

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm chuyển tên danh mục thành value cho radio button (giữ lại từ phiên bản trước)
function get_filter_value($tendm) {
    $tendm = strtolower(str_replace('Tra cứu ', '', $tendm));
    if ($tendm == 'dược chất') return 'duocchat';
    if ($tendm == 'dược liệu') return 'duoclieu';
    return $tendm;
}

/**
 * Lấy các danh mục cấp 2 liên quan đến 'Tra cứu' (Tra cứu thuốc, Tra cứu dược chất, Tra cứu dược liệu)
 * @return array Danh sách danh mục
 */
function get_drug_search_categories() {
    $pdo = pdo(); // Lấy đối tượng PDO
    $sql = "SELECT tendm FROM danhmuc WHERE parent_id = 2 AND cap = 2 AND tendm LIKE 'Tra cứu%'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// =================================================================
// PHẦN 2: XỬ LÝ YÊU CẦU & THIẾT LẬP BIẾN
// =================================================================
$therapy_categories = get_drug_therapy_categories();
$search_categories = get_drug_search_categories();
$page_css = '<link rel="stylesheet" href="static/css/search.css">'; // Đặt CSS riêng cho trang

?>

    <?= $page_css ?>

    <div class="breadcrumb">
      <a href="<?= $base_url ?>/base.php?page=home">Trang chủ</a> / <span>Thuốc</span>
    </div>

  <section class="search-section">
    <div class="search-box">
      <div class="search-left">
        <h1>Tra cứu thuốc & biệt dược</h1>
        
        <form id="searchForm" action="<?= $base_url ?>/base.php" method="GET">
            <input type="hidden" name="page" id="pageTarget" value="search_results_drug">
            <div class="search-bar">
                <input type="text" 
                       placeholder="Nhập tên thuốc, dược chất, dược liệu..." 
                       id="searchInput" 
                       name="q" 
                       required>
                <button type="submit" id="searchBtn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="#666" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div class="filters">
                <label><input type="radio" name="filter" value="all" checked> Tất cả</label>
                <?php foreach ($search_categories as $cat): 
                    $value = get_filter_value($cat['tendm']); ?>
                    <label><input type="radio" name="filter" value="<?= $value ?>"> <?= htmlspecialchars($cat['tendm']) ?></label>
                <?php endforeach; ?>
                </div>
        </form>
        
      </div>
      <div class="search-img">
        <img src="static/img/pngtree-simple-pill-icon-vector-art-illustration-png-image_15500175.png" alt="pharmacist illustration">
      </div>
    </div>
  </section>

  <section class="category-section">
    <div class="container">
      <h2>Thuốc theo nhóm trị liệu</h2>
      <div class="grid">
        <?php if (!empty($therapy_categories)): ?>
            <?php foreach ($therapy_categories as $cat): ?>
                <div class="card">
                    <img src="<?= $base_url ?>/<?= htmlspecialchars($cat['img_url'] ?? 'static/img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($cat['tendm']) ?>">
                    <div class="text">
                        <h3><?= htmlspecialchars($cat['tendm']) ?></h3>
                        <p><?= htmlspecialchars($cat['product_count']) ?> sản phẩm</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1 / -1;">Hiện chưa có nhóm trị liệu nào được phân loại.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pageTarget = document.getElementById('pageTarget');
        const filterRadios = document.querySelectorAll('input[name="filter"]');

        filterRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Khi chọn radio, thay đổi trang đích (pageTarget)
                const filterValue = this.value;
                if (filterValue === 'thuoc' || filterValue === 'all') {
                    // Nếu tìm kiếm Thuốc hoặc Tất cả (mặc định), chuyển đến trang kết quả sản phẩm
                    pageTarget.value = 'search_results_drug'; 
                } else if (filterValue === 'duocchat') {
                    // Nếu tìm kiếm Dược chất
                    pageTarget.value = 'search_results_duocchat'; 
                } else if (filterValue === 'duoclieu') {
                    // Nếu tìm kiếm Dược liệu
                    pageTarget.value = 'search_results_duoclieu'; 
                } else {
                    pageTarget.value = 'search_results_drug';
                }
            });
        });
    });
    </script>