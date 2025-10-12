
<body>
    <!-- Header Bar -->
    <div class="header-bar">
        <div class="header-bar-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                <path d="M10.5 3A7.5 7.5 0 003 10.5c0 4.143 3.357 7.5 7.5 7.5 1.47 0 2.844-.423 3.993-1.145l4.826 4.827 1.414-1.414-4.827-4.826A7.457 7.457 0 0018 10.5 7.5 7.5 0 0010.5 3zm0 2A5.5 5.5 0 1110.5 15a5.5 5.5 0 010-11z"/>
            </svg>
            <a href="#">
                Trung tâm tiêm chủng An Tâm <strong>Tìm hiểu ngay</strong>
            </a>
        </div>

        <div class="header-bar-right">
            <a href="#" class="app-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.5 2A1.5 1.5 0 005 3.5v13A1.5 1.5 0 006.5 18h7a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0013.5 2h-7zM9 14h2a.5.5 0 010 1H9a.5.5 0 010-1z"/>
                </svg>
                <span>Tải ứng dụng</span>
            </a>

            <div class="call-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6.62 10.79a15.466 15.466 0 006.59 6.59l2.2-2.2a1 1 0 011.11-.21c1.21.49 2.53.76 3.88.76a1 1 0 011 1V20a1 1 0 01-1 1C10.29 21 3 13.71 3 5a1 1 0 011-1h3.5a1 1 0 011 1c0 1.35.27 2.67.76 3.88a1 1 0 01-.21 1.11l-2.43 2.8z"/>
                </svg>
                <span>Tư vấn ngay: <a href="tel:18006928">1800 6928</a></span>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="/Pharmacy-management/" style="text-decoration: none; color: #004aad;">
                    <img src="<?= $base_url ?>/static/img/logo.png" alt="Logo" style="width: 45px; height: auto; vertical-align: middle;">
                        <span style="font-family: 'Poppins', sans-serif; font-size: 40px; font-weight: 600; margin-left: 5px;">An Tâm</span>
                     </a>
                </div>

                <div class="search-bar">
                    <input type="text" placeholder="Tìm kiếm sản phẩm, thuốc, bệnh...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="user-actions">
                    <a href="/login.php"><i class="fas fa-user"></i> Đăng nhập</a>
                    <a href="#"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a>
                    <a href="#"><i class="fas fa-headset"></i> Hỗ trợ</a>
                </div>
            </div>
        </div>

        <!-- Main Navigation -->
        <nav class="main-nav">
            <div class="nav-container">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="base.php?page=thucpham">Thực phẩm chức năng <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item"><img src="static/img/Vitamin_and_Khoang_chat.webp" alt=""> Vitamin & Khoáng chất</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Sinh_li_Noi_tiet_to.webp" alt=""> Sinh lý - Nội tiết tố</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Cai_thien_tang_cuong_chuc_nang.webp" alt=""> Cải thiện tăng cường chức năng</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Ho_tro_dieu_tri.webp" alt=""> Hỗ trợ điều trị</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Ho_tro_tieu_hoa.webp" alt=""> Hỗ trợ tiêu hóa</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Than_kinh_nao.webp" alt=""> Thần kinh não</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Ho_tro_lam_dep.webp" alt=""> Hỗ trợ làm đẹp</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Suc_khoe_tim_mach_e413362a48.webp" alt=""> Sức khoẻ tim mạch</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Dinh_duong_c16bba60b5.webp" alt=""> Dinh dưỡng</a>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a href="base.php?page=search">Thuốc <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item"><img src="static/img/Tra_cuu_thuoc_3c3dcc9179.webp" alt=""> Tra cứu thuôc</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Tra_cuu_duoc_lieu_ed08035f86.webp" alt=""> Tra cứu dược liệu</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Tra_cuu_duoc_chat_3d726d05f6.webp" alt=""> Tra cứu dược chất</a>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a href="base.php?page=thietbi">Thiết bị y tế <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item"><img src="static/img/Dung_cu_y_te_8ae0da0bb4.webp" alt=""> Dụng cụ y tế</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Dung_cu_so_cuu_277b6974b1.webp" alt=""> Dụng cụ sơ cứu</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Dung_cu_theo_doi_db6cafb0fa.webp" alt=""> Dụng cụ theo dõi</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Khau_trang_1fedf5b9be.webp" alt=""> Khẩu trang</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="base.php?page=search">Tra cứu bệnh </a>
                    </li>
                    <li class="nav-item">
                        <a href="base.php?page=suckhoe">Bệnh & Góc sức khỏe <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item"><img src="static/img/Goc_suc_khoe.webp" alt=""> Góc sức khỏe</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Benh_thuong_gap.webp" alt=""> Bệnh thường gặp</a>
                            <a href="#" class="dropdown-item"><img src="static/img/Tin_khuyen_mai_87bca39cdb.webp" alt=""> Tin khuyến mãi</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="base.php?page=about">Hệ thống nhà thuốc</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>