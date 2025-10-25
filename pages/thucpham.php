<?php
require_once 'db.php';

$sql = "SELECT sp.*, dm.tendm 
        FROM sanpham sp
        JOIN danhmuc dm ON sp.madm = dm.madm
        WHERE sp.madm BETWEEN 26 AND 63
        ORDER BY sp.masp DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <?php foreach ($products as $sp): ?>
    <div class='product-card'>
        <img src='../uploads/<?= htmlspecialchars($sp['hinhanh']) ?>' alt='<?= htmlspecialchars($sp['tensp']) ?>'>
        <h3><?= htmlspecialchars($sp['tensp']) ?></h3>
        <p><?= htmlspecialchars($sp['tendm']) ?></p>
        <p class='price'><?= number_format($sp['giaban'], 0, ',', '.') ?> ₫</p>
        <a href='chitiet.php?masp=<?= $sp['masp'] ?>' class='btn'>Xem chi tiết</a>
    </div>
<?php endforeach; ?>
</div>

       

      </div>
    </div>
  </div>
</section>
        </div>
    </div>