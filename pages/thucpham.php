<?php
// ================== THIẾT LẬP KẾT NỐI DATABASE ==================
$servername = "localhost";
$username = "root";
$password = "";            
$dbname = "nhathuocantam"; 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Giả định: Nếu không có biến $base_url, sử dụng đường dẫn gốc cho ảnh
$base_url_for_images = isset($base_url) ? $base_url : ''; 

// Hàm định dạng giá
function format_price($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// ================== 1. TRUY VẤN DỮ LIỆU CHO SẢN PHẨM ==================
// Lấy ảnh chính (hinhsp) từ bảng sanpham
$sql_products = "
    SELECT 
        sp.masp, sp.tensp, sp.giaban, sp.giagiam, COALESCE(sp.hinhsp, '') AS hinhsp, 
        dv.tendv, dm.tendm
    FROM sanpham sp
    JOIN danhmuc dm ON sp.madm = dm.madm
    JOIN donvitinh dv ON sp.madv = dv.madv
    WHERE sp.madm IN (3, 4) -- Lọc theo ID danh mục TPCN và Vitamin
    LIMIT 20
";

$result_products = $conn->query($sql_products);
$products = [];

if ($result_products && $result_products->num_rows > 0) {
    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $error_message = "Không tìm thấy sản phẩm TPCN hoặc Vitamin nào trong database.";
}

// ================== 2. TRUY VẤN DỮ LIỆU CHO BỘ LỌC ĐỐI TƯỢNG ==================
$sql_doituong = "SELECT madt, tendt FROM doituong ORDER BY tendt";
$result_doituong = $conn->query($sql_doituong);
$doituong_options = [];
if ($result_doituong) {
    while ($row = $result_doituong->fetch_assoc()) {
        $doituong_options[] = $row;
    }
}

// ================== 3. TRUY VẤN DỮ LIỆU CHO BỘ LỌC MÙI VỊ ==================
$sql_muivi = "SELECT mamv, tenmv FROM muivi WHERE tenmv != 'Tất cả' ORDER BY tenmv";
$result_muivi = $conn->query($sql_muivi);
$muivi_options = [];
if ($result_muivi) {
    while ($row = $result_muivi->fetch_assoc()) {
        $muivi_options[] = $row;
    }
}

$conn->close();
?>

    <link rel="stylesheet" href="static/css/product.css">

    <div class="container">
        <section class="featured-categories-section">
    <div class="container">
        <div class="section-header">
            <h2>Thực phẩm chức năng</h2>
        </div>
        <div class="categories-grid">
            <div class="category-item" data-category="than-kinh-nao">
                <div class="category-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Thần kinh não</h3>
                    <span class="category-count">55 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="vitamin-khoang-chat">
                <div class="category-icon">
                    <i class="fas fa-capsules"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Vitamin & Khoáng chất</h3>
                    <span class="category-count">110 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="suc-khoe-tim-mach">
                <div class="category-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Sức khoẻ tim mạch</h3>
                    <span class="category-count">23 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="mien-dich">
                <div class="category-icon">
                    <i class="fas fa-shield-virus"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Tăng sức đề kháng, miễn dịch</h3>
                    <span class="category-count">40 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="ho-tro-tieu-hoa">
                <div class="category-icon">
                    <i class="fas fa-stomach"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Hỗ trợ tiêu hóa</h3>
                    <span class="category-count">65 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="noi-tiet-to">
                <div class="category-icon">
                    <i class="fas fa-hormone"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Sinh lý - Nội tiết tố</h3>
                    <span class="category-count">39 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="dinh-duong">
                <div class="category-icon">
                    <i class="fas fa-apple-alt"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Dinh dưỡng</h3>
                    <span class="category-count">36 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="ho-tro-dieu-tri">
                <div class="category-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Hỗ trợ điều trị</h3>
                    <span class="category-count">119 sản phẩm</span>
                </div>
            </div>
            <div class="category-item" data-category="ho-tro-lam-dep">
                <div class="category-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Hỗ trợ làm đẹp</h3>
                    <span class="category-count">22 sản phẩm</span>
                </div>
            </div>
        </div>
    </div>
</section>

     <section class="product-wrapper">
  <div class="product-layout">

    <aside class="filter-panel">
      <h3><i class="fas fa-filter"></i> Bộ lọc nâng cao</h3>

      <div class="filter-group">
        <h4>Đối tượng sử dụng</h4>
        <div class="filter-options">
          <label><input type="checkbox" checked> Tất cả</label>
          <?php foreach ($doituong_options as $dt): ?>
            <label><input type="checkbox" name="doituong" value="<?php echo $dt['madt']; ?>"> <?php echo htmlspecialchars($dt['tendt']); ?></label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="filter-group">
        <h4>Giá bán</h4>
        <div class="filter-options">
          <label><input type="checkbox"> Dưới 100.000đ</label>
          <label><input type="checkbox"> 100.000đ - 300.000đ</label>
          <label><input type="checkbox"> 300.000đ - 500.000đ</label>
          <label><input type="checkbox"> Trên 500.000đ</label>
        </div>
      </div>

      <div class="filter-group">
        <h4>Mùi vị / Mùi hương</h4>
        <div class="filter-options">
          <label><input type="checkbox" checked> Tất cả</label>
          <?php foreach ($muivi_options as $mv): ?>
            <label><input type="checkbox" name="muivi" value="<?php echo $mv['mamv']; ?>"> <?php echo htmlspecialchars($mv['tenmv']); ?></label>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>

    <div class="product-content">
      <div class="product-header">
        <div class="title">
          <h2>Danh sách sản phẩm</h2>
          <p class="note">Lưu ý: Một số sản phẩm cần tư vấn từ dược sĩ.</p>
        </div>
        <div class="sort-options">
          <span>Sắp xếp theo:</span>
          <button class="active">Bán chạy</button>
          <button>Giá thấp</button>
          <button>Giá cao</button>
        </div>
      </div>

      <div class="product-grid">
        <?php if (isset($error_message)): ?>
            <p style="color: red; padding: 20px;"><?php echo $error_message; ?></p>
        <?php elseif (!empty($products)): ?>
            <?php foreach ($products as $product): 
                $is_discount = $product['giagiam'] > 0;
                $display_price = $is_discount ? $product['giaban'] - $product['giagiam'] : $product['giaban'];
                $old_price = $product['giaban'];
                $discount_percent = $is_discount ? round(($product['giagiam'] / $product['giaban']) * 100) : 0;
                // Tạo liên kết đến trang chi tiết sản phẩm
                $detail_link = "detailsproducts.php?masp=" . $product['masp'];
                
                // Xử lý đường dẫn ảnh, sử dụng đường dẫn tuyệt đối từ database nếu có, ngược lại dùng placeholder
                $image_src = $product['hinhsp'] ? $base_url_for_images . $product['hinhsp'] : ($base_url_for_images . '/assets/img/placeholder.jpg');
            ?>
            <a href="<?php echo $detail_link; ?>" class="product-card">
              <?php if ($is_discount): ?>
                <span class="discount-badge">-<?php echo $discount_percent; ?>%</span>
              <?php endif; ?>
              <img src="<?php echo htmlspecialchars($image_src); ?>" 
                   alt="<?php echo htmlspecialchars($product['tensp']); ?>">
              <h3><?php echo htmlspecialchars($product['tensp']); ?></h3>
              <p class="price">
                  <?php echo format_price($display_price); ?> 
                  <?php if ($is_discount): ?>
                    <span class="old-price"><?php echo format_price($old_price); ?></span>
                  <?php endif; ?>
              </p>
              <button class="btn-buy" onclick="window.location.href='<?php echo $detail_link; ?>'; return false;">
                Chọn mua
              </button>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Hiện chưa có sản phẩm nào được hiển thị.</p>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>
        </div>
    </div>