    <link rel="stylesheet" href="static/css/product.css">

    <div class="container">
        <!-- Sidebar - Bộ lọc -->
<section class="featured-categories-section">
    <div class="container">
        <div class="section-header">
            <h2>Thiết bị y tế</h2>
        </div>
        <div class="categories-grid">
            <!-- Category 1 -->
            <div class="category-item" data-category="than-kinh-nao">
                <div class="category-icon">
                  <i class="fa-solid fa-syringe"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Dụng cụ y tế</h3>
                    <span class="category-count">55 sản phẩm</span>
                </div>
            </div>

            <!-- Category 2 -->
            <div class="category-item" data-category="vitamin-khoang-chat">
                <div class="category-icon">
                  <i class="fa-solid fa-stethoscope"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Dụng cụ theo dõi</h3>
                    <span class="category-count">110 sản phẩm</span>
                </div>
            </div>

            <!-- Category 3 -->
            <div class="category-item" data-category="suc-khoe-tim-mach">
                <div class="category-icon">
                    <i class="fa-solid fa-kit-medical"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name">Dụng cụ sơ cứu</h3>
                    <span class="category-count">23 sản phẩm</span>
                </div>
            </div>


            <div class="category-item" data-category="ho-tro-tieu-hoa">
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

    <!-- ========== BỘ LỌC BÊN TRÁI ========== -->
    <aside class="filter-panel">
      <h3><i class="fas fa-filter"></i> Bộ lọc nâng cao</h3>

      <div class="filter-group">
        <h4>Đối tượng sử dụng</h4>
        <div class="filter-options">
          <label><input type="checkbox" checked> Tất cả</label>
          <label><input type="checkbox"> Trẻ em</label>
          <label><input type="checkbox"> Người trưởng thành</label>
          <label><input type="checkbox"> Người lớn</label>
          <label><input type="checkbox"> Người cao tuổi</label>
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
          <label><input type="checkbox"> Vani</label>
          <label><input type="checkbox"> Dâu</label>
          <label><input type="checkbox"> Cam</label>
        </div>
      </div>
    </aside>

    <!-- ========== DANH SÁCH SẢN PHẨM BÊN PHẢI ========== -->
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
        <div class="product-card">
          <span class="discount-badge">-20%</span>
          <img src="<?= $base_url ?>/assets/img/product1.jpg" alt="Sản phẩm 1">
          <h3>Cốm vi sinh bổ sung lợi khuẩn đường ruột Lacto Biomin Gold+</h3>
          <p class="price">149.000đ <span class="old-price">200.000đ</span></p>
          <button class="btn-buy">Chọn mua</button>
        </div>

        <div class="product-card">
          <img src="<?= $base_url ?>/assets/img/product2.jpg" alt="Sản phẩm 2">
          <h3>Dung dịch D3 Drops 10ml Dao Nordic Health</h3>
          <p class="price">270.000đ</p>
          <button class="btn-buy">Chọn mua</button>
        </div>

        <div class="product-card">
          <span class="discount-badge">-20%</span>
          <img src="<?= $base_url ?>/assets/img/product3.jpg" alt="Sản phẩm 3">
          <h3>Siro Bổ Phế Lábebé 120ml hỗ trợ bổ phế, giảm ho</h3>
          <p class="price">60.000đ <span class="old-price">75.000đ</span></p>
          <button class="btn-buy">Chọn mua</button>
        </div>

        <div class="product-card">
          <img src="<?= $base_url ?>/assets/img/product4.jpg" alt="Sản phẩm 4">
          <h3>Viên uống NutriGrow bổ sung canxi, vitamin D3, K2</h3>
          <p class="price">480.000đ</p>
          <button class="btn-buy">Chọn mua</button>
        </div>
      </div>
    </div>
  </div>
</section>
        </div>
    </div>

