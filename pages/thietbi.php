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

$sql = "SELECT sp.*, dm.tendm, dv.tendv
        FROM sanpham sp
        JOIN danhmuc dm ON sp.madm = dm.madm
        LEFT JOIN donvitinh dv ON sp.madv = dv.madv
        WHERE sp.madm BETWEEN 64 AND 75
        ORDER BY sp.masp DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$product_count = count($products);
$error_message = empty($products) ? "Không tìm thấy sản phẩm nào trong danh mục (ID: 64-75)." : '';

// --- Logic trích xuất Đối tượng và Mùi vị (Giữ nguyên cho bộ lọc) ---

// *Vì bạn không cung cấp logic trích xuất Đối tượng/Mùi vị trong file hiện tại, 
// tôi tạm thời hardcode các biến này để tránh lỗi Parse Error trong phần HTML
// Bạn có thể thay thế bằng logic truy vấn database nếu cần.*
$doituong_options = ['Trẻ em', 'Người lớn', 'Phụ nữ có thai']; 
$muivi_options = [['mamv'=>1, 'tenmv'=>'Không mùi'], ['mamv'=>2, 'tenmv'=>'Vị Cam']];


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

     <section class="product-wrapper">
  <div class="product-layout">

    <aside class="filter-panel">
      <h3><i class="fas fa-filter"></i> Bộ lọc nâng cao</h3>

      <div class="filter-group">
        <h4>Đối tượng sử dụng</h4>
        <div class="filter-options">
          <label><input type="checkbox" checked> Tất cả</label>
          <?php foreach ($doituong_options as $dt): ?>
            <label><input type="checkbox" name="doituong" value="<?php echo htmlspecialchars($dt); ?>"> <?php echo htmlspecialchars($dt); ?></label>
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
          <h2>Danh sách sản phẩm (Tổng: <?php echo $product_count; ?>)</h2>
          <p class="note">Lưu ý: Một số sản phẩm cần tư vấn từ dược sĩ.</p>
        </div>
        <div class="sort-options">
          <span>Sắp xếp theo:</span>
          <button class="active">Bán chạy</button>
          <button>Giá thấp</button>
          <button>Giá cao</button>
        </div>
      </div>
      
      <div class='product-grid'>
        <?php if (!empty($error_message)): ?>
            <p style="color: red; padding: 20px;"><?php echo $error_message; ?></p>
        <?php else: ?>
            <?php foreach ($products as $sp): 
                // XỬ LÝ DỮ LIỆU ĐỂ HIỂN THỊ ĐẸP
                $is_discount = (float)($sp['giagiam'] ?? 0) > 0;
                $display_price = (float)($sp['giaban'] ?? 0) - (float)($sp['giagiam'] ?? 0);
                $old_price = (float)($sp['giaban'] ?? 0);
                $discount_percent = $is_discount ? round((($sp['giagiam'] ?? 0) / ($sp['giaban'] ?? 1)) * 100) : 0;
                
                $detail_link = $base_url . "/detailsproducts.php?masp=" . ($sp['masp'] ?? '');
                // Dùng cột 'hinhsp' theo schema, thêm $base_url
                $image_src = ($sp['hinhsp'] ?? '') ? $base_url . $sp['hinhsp'] : ($base_url . '/static/img/placeholder.jpg');
            ?>
                <a href='<?= htmlspecialchars($detail_link) ?>' class='product-card'>
                    <?php if ($is_discount): ?>
                        <span class="discount-badge">-<?php echo $discount_percent; ?>%</span>
                    <?php endif; ?>
                    <img src='<?= htmlspecialchars($image_src) ?>' alt='<?= htmlspecialchars($sp['tensp'] ?? '') ?>'>
                    <h3><?= htmlspecialchars($sp['tensp'] ?? '') ?></h3>
                    <p class="unit">Đơn vị: <?= htmlspecialchars($sp['tendv'] ?? 'Chưa rõ') ?></p>
                    <p class="category">DM: <?= htmlspecialchars($sp['tendm'] ?? 'Chưa rõ') ?></p>
                    <p class='price'>
                        <?php echo format_price_vn($display_price); ?> ₫
                        <?php if ($is_discount): ?>
                            <span class="old-price"><?php echo format_price_vn($old_price); ?> ₫</span>
                        <?php endif; ?>
                    </p>
                    <button class='btn-buy' onclick="window.location.href='<?= htmlspecialchars($detail_link) ?>'; return false;">
                        Chọn mua
                    </button>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>
        </div>
    </div>