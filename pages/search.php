
    <link rel="stylesheet" href="static/css/search.css">

  <div class="breadcrumb">
    <a href="#">Trang chủ</a> / <span>Thuốc</span>
  </div>

  <!-- Search Section -->
  <section class="search-section">
    <div class="search-box">
      <div class="search-left">
        <h1>Tra cứu thuốc & biệt dược</h1>
        <div class="search-bar">
          <input type="text" placeholder="Nhập tên thuốc, dược chất, dược liệu..." id="searchInput">
          <button id="searchBtn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="#666" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
        <div class="filters">
          <label><input type="radio" name="filter" value="all" checked> Tất cả</label>
          <label><input type="radio" name="filter" value="thuoc"> Thuốc</label>
          <label><input type="radio" name="filter" value="duocchat"> Dược chất</label>
          <label><input type="radio" name="filter" value="duoclieu"> Dược liệu</label>
        </div>
      </div>
      <div class="search-img">
        <img src="static/img/pngtree-simple-pill-icon-vector-art-illustration-png-image_15500175.png" alt="pharmacist illustration">
      </div>
    </div>
  </section>

  <!-- Medicine Category Section -->
  <section class="category-section">
    <div class="container">
      <h2>Thuốc theo nhóm trị liệu</h2>
      <div class="grid">
        <!-- từng item -->
        <div class="card">
          <img src="https://cdn.longchau.vn/media/catalog/product/t/h/thuoc-di-ung_1660635231.png" alt="">
          <div class="text">
            <h3>Thuốc dị ứng</h3>
            <p>138 sản phẩm</p>
          </div>
        </div>

        <div class="card">
          <img src="https://cdn.longchau.vn/media/catalog/product/t/h/thuoc-bo-va-vitamin_1660635230.png" alt="">
          <div class="text">
            <h3>Thuốc bổ & vitamin</h3>
            <p>281 sản phẩm</p>
          </div>
        </div>

        <div class="card">
          <img src="https://cdn.longchau.vn/media/catalog/product/t/h/thuoc-ho-hap_1660635232.png" alt="">
          <div class="text">
            <h3>Thuốc hô hấp</h3>
            <p>311 sản phẩm</p>
          </div>
        </div>

        <div class="card">
          <img src="https://cdn.longchau.vn/media/catalog/product/t/h/thuoc-he-than-kinh_1660635234.png" alt="">
          <div class="text">
            <h3>Thuốc hệ thần kinh</h3>
            <p>323 sản phẩm</p>
          </div>
        </div>

        <div class="card">
          <img src="https://cdn.longchau.vn/media/catalog/product/t/h/thuoc-tieu-hoa_1660635235.png" alt="">
          <div class="text">
            <h3>Thuốc tiêu hóa & gan mật</h3>
            <p>651 sản phẩm</p>
          </div>
        </div>

        <div class="card">
          <img src="https://cdn.longchau.vn/media/catalog/product/t/h/thuoc-tim-mach_1660635236.png" alt="">
          <div class="text">
            <h3>Thuốc tim mạch & máu</h3>
            <p>856 sản phẩm</p>
          </div>
        </div>
      </div>
    </div>
  </section>


    <script src="static/js/search.js"></script>
