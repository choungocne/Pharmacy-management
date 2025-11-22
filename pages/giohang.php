<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mẫu Giỏ Hàng - Nhà Thuốc An Tâm</title>
    
    <!-- Tải Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tải Google Font (Inter) - Giống login.php -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tải Font Awesome (cho icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!--
    TOÀN BỘ CSS HIỆU ỨNG TỪ LOGIN.PHP CỦA BẠN
    -->
    <style>
        :root {
            /* Sao chép các biến màu từ file login.php */
            --primary-color: #0284c7; /* sky-600 */
            --primary-light: #e0f2fe; /* sky-100 */
            --primary-dark: #0369a1;  /* sky-700 */
        }
        body { 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden; 
        }

        /* Nền Canvas (từ login.php) */
        #pills-canvas {
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            z-index: -1; 
            /* * CẬP NHẬT: Thêm một lớp gradient màu đen mờ (0.3 opacity)
             * lên trên nền gradient màu xanh ban đầu để làm tối ~30%
             */
            background: 
                linear-gradient(to bottom, rgba(44, 93, 114, 0.6), rgba(4, 255, 0, 0.3)),
                linear-gradient(to bottom, #e0f7fa, #b3e5fc);
        }

        /* Keyframes cho card bay lên (từ login.php) */
        @keyframes fadeInUpAndGrow {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
                filter: blur(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }
        }

        /* Áp dụng animation cho card (từ login.php) */
        .login-card-animation {
            animation-name: fadeInUpAndGrow;
            animation-duration: 0.9s;
            animation-timing-function: ease-out;
            animation-delay: 0s;
            animation-iteration-count: 1;
            animation-fill-mode: forwards;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .login-card-animation:hover {
            transform: translateY(-8px) rotateZ(-1.5deg) scale(1.01);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        /* Hiệu ứng nút bấm (từ login.php) */
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(2, 132, 199, 0.3); }
            50% { box-shadow: 0 0 35px rgba(2, 132, 199, 0.6); }
        }
        .login-button {
            transition: all 0.3s ease-in-out;
            animation: pulse-glow 2.5s infinite ease-in-out;
            box-shadow: 0 4px 14px 0 rgba(2, 132, 199, 0.25);
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 0.5rem; /* rounded-lg */
        }
        .login-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px 0 rgba(2, 132, 199, 0.35);
        }
        .login-button:active {
            transform: scale(0.98);
            box-shadow: 0 2px 10px 0 rgba(2, 132, 199, 0.2);
        }
        
        /* Hiệu ứng ô input (từ login.php) */
        .login-input {
            transition: all 0.2s ease-in-out;
            border: 1px solid #D1D5DB; /* border-gray-300 */
            width: 100%;
            border-radius: 0.5rem; /* rounded-lg */
            padding: 0.75rem 1rem; /* py-3 px-4 */
            background-color: rgba(255, 255, 255, 0.7);
        }
        .login-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
            outline: none;
            background-color: white;
        }
        .login-input:disabled {
            background-color: #f3f4f6; /* bg-gray-100 */
            cursor: not-allowed;
        }

        /* Tùy chỉnh cho Toggle Switch (Hóa đơn) */
        .toggle-checkbox:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: var(--primary-color);
        }
        
        /* Tùy chỉnh cho Radio Button (Thanh toán) */
        .form-radio {
            color: var(--primary-color);
        }
        .form-radio:checked {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
        }

        /* === CSS MỚI ĐỂ CHUYỂN TAB === */
        /* Class cho nút tab không active */
        .tab-inactive {
            background-color: transparent;
            color: #4B5563; /* text-gray-600 */
        }
        .tab-inactive:hover {
            background-color: rgba(255, 255, 255, 0.8);
        }
        /* Class cho nút tab active */
        .tab-active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(2, 132, 199, 0.25);
        }

        /* === CSS MỚI CHO MODAL CHỌN GIỜ === */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5); /* Lớp phủ mờ */
            z-index: 40;
            opacity: 0;
            transition: opacity 0.3s ease-out;
            pointer-events: none;
        }
        .modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-panel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            background-color: white;
            border-radius: 1rem; /* rounded-2xl */
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            z-index: 50;
            width: 90%;
            max-width: 640px; /* max-w-2xl */
            opacity: 0;
            transition: all 0.3s ease-out;
            pointer-events: none;
        }
        .modal-panel.active {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
            pointer-events: auto;
        }

        /* Nút chọn ngày/giờ */
        .slot-button {
            border: 1px solid #D1D5DB; /* border-gray-300 */
            background-color: white;
            color: #1F2937; /* text-gray-800 */
            border-radius: 0.5rem; /* rounded-lg */
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem; /* text-sm */
            font-weight: 500;
            transition: all 0.2s;
        }
        .slot-button:hover {
            background-color: #F9FAFB; /* bg-gray-50 */
        }
        .slot-button.selected {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
        }
        .slot-button:disabled {
            background-color: #F3F4F6; /* bg-gray-100 */
            color: #9CA3AF; /* text-gray-400 */
            cursor: not-allowed;
        }

    </style>
</head>
<body class="text-gray-800">

    <!-- 
    HIỆU ỨNG NỀN CANVAS (SAO CHÉP TỪ LOGIN.PHP)
    -->
    <canvas id="pills-canvas"></canvas>
    
    <!-- 
    PHẦN NỘI DUNG GIỎ HÀNG
    -->
    <div class="relative min-h-screen w-full flex justify-center p-4 sm:p-6 lg:p-10">
        
        <div class="w-full max-w-7xl mx-auto">
            <!-- Tiêu đề và link quay lại -->
            <div class="mb-4">
                <a href="#" class="font-medium text-lg" style="color: var(--primary-dark);">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Quay lại giỏ hàng
                </a>
            </div>

            <!-- Grid 2 cột chính -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- CỘT BÊN TRÁI (NỘI DUNG CHÍNH) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Card 1: Danh sách sản phẩm -->
                    <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-xl login-card-animation" style="animation-delay: 0.1s;">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--primary-dark);">
                            <i class="fas fa-capsules mr-2"></i>
                            Danh sách sản phẩm
                        </h2>

                        <!-- Banner miễn phí vận chuyển -->
                        <div class="bg-sky-50/70 border border-sky-200 rounded-lg p-3 text-center mb-4">
                            <p class="font-medium" style="color: var(--primary-color);">
                                <i class="fas fa-truck-fast mr-2"></i>
                                Miễn phí vận chuyển đối với đơn hàng trên 300.000đ
                            </p>
                        </div>
                        
                        <!-- Dữ liệu mẫu 1 -->
                        <div class="flex items-start gap-4 py-4 border-b border-gray-200">
                            <img src="https://placehold.co/100x100/e0f2fe/0284c7?text=An+Tam" alt="Sản phẩm 1" class="w-24 h-24 rounded-lg border border-gray-200 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-medium text-gray-800">Dung dịch Feginic bổ sung sắt cho người thiếu máu (4 vỉ x 5 ống x 5ml)</h3>
                                <p class="text-sm text-gray-500">Hàng chính hãng</p>
                                <div class="flex items-center justify-between mt-2">
                                    <div>
                                        <span class="font-semibold text-lg" style="color: var(--primary-color);">108.000đ</span>
                                        <span class="text-gray-400 line-through ml-2">120.000đ</span>
                                    </div>
                                    <input type="number" value="1" min="1" max="10" class="login-input w-20 text-center py-1 px-2">
                                </div>
                            </div>
                            <button class="text-gray-400 hover:text-red-500 transition-colors">
                                <i class="fas fa-trash-alt fa-lg"></i>
                            </button>
                        </div>
                        
                        <!-- Dữ liệu mẫu 2 -->
                        <div class="flex items-start gap-4 py-4">
                            <img src="https://placehold.co/100x100/e0f2fe/0284c7?text=An+Tam" alt="Sản phẩm 2" class="w-24 h-24 rounded-lg border border-gray-200 object-cover">
                            <div class="flex-grow">
                                <h3 class="font-medium text-gray-800">Thuốc Alpha-Chymotrypsin Euvipharm điều trị phù nề (2 vỉ x 10 viên)</h3>
                                <p class="text-sm text-gray-500">Hàng chính hãng</p>
                                <div class="flex items-center justify-between mt-2">
                                    <div>
                                        <span class="font-semibold text-lg" style="color: var(--primary-color);">99.000đ</span>
                                    </div>
                                    <input type="number" value="2" min="1" max="10" class="login-input w-20 text-center py-1 px-2">
                                </div>
                            </div>
                            <button class="text-gray-400 hover:text-red-500 transition-colors">
                                <i class="fas fa-trash-alt fa-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Card 2: Thông tin nhận hàng -->
                    <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-xl login-card-animation" style="animation-delay: 0.2s;">
                        
                        <!-- === CÁC NÚT TAB ĐỂ NHẤN === -->
                        <div class="mb-5">
                            <label class="block text-base font-medium text-gray-700 mb-3">Chọn hình thức nhận hàng</label>
                            <div class="flex rounded-lg border border-gray-300 p-1 bg-gray-100/60 w-full md:w-auto">
                                <button 
                                    id="btn-giao-hang"
                                    type="button"
                                    class="flex-1 py-2 px-5 rounded-md text-sm font-semibold transition-all duration-200 tab-active"
                                >
                                    <i class="fas fa-truck mr-2"></i>
                                    Giao hàng tận nơi
                                </button>
                                <button 
                                    id="btn-nhan-tai-nha-thuoc"
                                    type="button"
                                    class="flex-1 py-2 px-5 rounded-md text-sm font-semibold transition-all duration-200 tab-inactive"
                                >
                                    <i class="fas fa-store mr-2"></i>
                                    Nhận tại nhà thuốc
                                </button>
                            </div>
                        </div>
                        <!-- === KẾT THÚC CÁC NÚT TAB === -->

                        <!-- === PHẦN THÔNG TIN NGƯỜI ĐẶT (CHUNG CHO CẢ 2) === -->
                        <div class="mb-5">
                            <h3 class="text-lg font-semibold mb-3 flex items-center" style="color: var(--primary-dark);">
                                <i class="fas fa-user mr-2"></i>
                                Thông tin người đặt
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên người đặt</label>
                                    <input type="text" placeholder="Nguyễn Văn A" class="login-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                    <input type="text" placeholder="09xxxxxxxx" class="login-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (không bắt buộc)</label>
                                    <input type="email" placeholder="email@example.com" class="login-input">
                                </div>
                            </div>
                        </div>
                        <!-- === KẾT THÚC PHẦN NGƯỜI ĐẶT === -->


                        <!-- === KHỐI 1: NỘI DUNG GIAO HÀNG TẬN NƠI === -->
                        <div id="content-giao-hang" class="border-t border-gray-200 pt-5">
                            <h2 class="text-xl font-semibold mb-4" style="color: var(--primary-dark);">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                Địa chỉ nhận hàng
                            </h2>

                            <!-- "Trước sáp nhập" / "Sau sáp nhập" -->
                            <div class="flex items-center gap-6 mb-4">
                                <label class="flex items-center cursor-pointer">
                                    <!-- THÊM ID CHO RADIO -->
                                    <input type="radio" name="sap-nhap" id="radio-truoc-sap-nhap" class="form-radio h-5 w-5" checked>
                                    <span class="ml-2 text-gray-700">Trước sáp nhập</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <!-- THÊM ID CHO RADIO -->
                                    <input type="radio" name="sap-nhap" id="radio-sau-sap-nhap" class="form-radio h-5 w-5">
                                    <span class="ml-2 text-gray-700">Sau sáp nhập</span>
                                </label>
                            </div>

                            <!-- Form địa chỉ - BỌC LẠI BẰNG ID "form-truoc-sap-nhap" -->
                            <div class="space-y-4" id="form-truoc-sap-nhap">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên người nhận</label>
                                        <input type="text" placeholder="Nguyễn Văn B" class="login-input">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                        <input type="text" placeholder="09xxxxxxxx" class="login-input">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố</label>
                                        <select class="login-input">
                                            <option>Chọn Tỉnh/Thành phố</option>
                                            <option selected>TP. Hồ Chí Minh</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quận/Huyện</label>
                                        <select class="login-input">
                                            <option>Chọn Quận/Huyện</option>
                                            <option selected>Quận Bình Thạnh</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phường/Xã</label>
                                    <select class="login-input">
                                        <option>Chọn Phường/Xã</option>
                                        <!-- CẬP NHẬT: THÊM NHIỀU PHƯỜNG -->
                                        <option selected>Phường 25</option>
                                        <option>Phường 1</option>
                                        <option>Phường 2</option>
                                        <option>Phường 3</option>
                                        <option>Phường 5</option>
                                        <option>Phường 6</option>
                                        <option>Phường 7</option>
                                        <option>Phường 11</option>
                                        <option>Phường 12</option>
                                        <option>Phường 13</option>
                                        <option>Phường 14</option>
                                        <option>Phường 15</option>
                                        <option>Phường 17</option>
                                        <option>Phường 19</option>
                                        <option>Phường 21</option>
                                        <option>Phường 22</option>
                                        <option>Phường 24</option>
                                        <option>Phường 26</option>
                                        <option>Phường 27</option>
                                        <option>Phường 28</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhập địa chỉ chi tiết (số nhà, tên đường)</label>
                                    <input type="text" placeholder="Số 2 Võ Oanh" class="login-input">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (không bắt buộc)</label>
                                    <textarea placeholder="Ví dụ: Giao hàng giờ hành chính..." rows="3" class="login-input"></textarea>
                                </div>
                            </div>
                            <!-- === KẾT THÚC FORM "TRƯỚC SÁP NHẬP" === -->

                            <!-- === THÊM MỚI: FORM "SAU SÁP NHẬP" (DỰA TRÊN HÌNH ẢNH) === -->
                            <div class="space-y-4" id="form-sau-sap-nhap" style="display: none;">
                                <!-- Thông báo (mô phỏng) -->
                                <div class="bg-sky-50/70 border border-sky-200 rounded-lg p-3 text-center">
                                    <p class="text-sm" style="color: var(--primary-color);">
                                        Đơn vị hành chính đã thay đổi theo quy định. 
                                        <a href="#" class="font-semibold underline">Tra cứu địa chỉ trước và sau sáp nhập.</a>
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên người nhận</label>
                                        <input type="text" placeholder="Nguyễn Văn B" class="login-input">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                        <input type="text" placeholder="09xxxxxxxx" class="login-input">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố</label>
                                        <select class="login-input" disabled>
                                            <option selected>TP. Hồ Chí Minh</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phường/Xã</label>
                                        <select class="login-input">
                                            <option>Chọn Phường/Xã</option>
                                            <!-- CẬP NHẬT: Thay thế bằng danh sách đầy đủ từ Long Châu -->
                                            <option>Phường An Hội Đông</option>
                                            <option>Phường Hòa Bình</option>
                                            <option>Phường Hòa Lợi</option>
                                            <option>Xã Hiệp Phước</option>
                                            <option>Phường Tân Hiệp</option>
                                            <option>Xã Bàu Bàng</option>
                                            <option>Xã Bà Điểm</option>
                                            <option>Phường Long Hương</option>
                                            <option>Phường Phước Thắng</option>
                                            <option>Phường Cầu Ông Lãnh</option>
                                            <option>Phường An Lạc</option>
                                            <option selected>Phường Bình Thạnh</option> <!-- Đã chọn -->
                                            <option>Phường Xuân Hòa</option>
                                            <option>Xã Bình Hưng</option>
                                            <option>Xã Phước Hải</option>
                                            <option>Phường Linh Xuân</option>
                                            <option>Phường Bến Cát</option>
                                            <option>Phường Vĩnh Hội</option>
                                            <option>Phường Tân Thành</option>
                                            <option>Xã Đất Đỏ</option>
                                            <option>Phường Minh Phụng</option>
                                            <option>Xã Xuân Sơn</option>
                                            <option>Phường Tân Phước</option>
                                            <option>Phường Lái Thiêu</option>
                                            <option>Phường Tân Thuận</option>
                                            <option>Xã Thái Mỹ</option>
                                            <option>Phường Chánh Phú Hòa</option>
                                            <option>Phường Tân Bình</option>
                                            <option>Phường Bình Hưng Hòa</option>
                                            <option>Phường Tam Thắng</option>
                                            <option>Xã Bắc Tân Uyên</option>
                                            <option>Phường Thủ Dầu Một</option>
                                            <option>Phường Tam Bình</option>
                                            <option>Phường Thới An</option>
                                            <option>Phường Phú Thọ</option>
                                            <option>Xã Cần Giờ</option>
                                            <option>Xã Thạnh An</option>
                                            <option>Phường Chợ Lớn</option>
                                            <option>Xã Bình Lợi</option>
                                            <option>Phường Phú Thọ Hòa</option>
                                            <option>Xã Bàu Lâm</option>
                                            <option>Xã Châu Pha</option>
                                            <option>Phường Tân Hòa</option>
                                            <option>Phường Tân Định</option>
                                            <option>Xã Bình Khánh</option>
                                            <option>Phường Nhiêu Lộc</option>
                                            <option>Phường Thủ Đức</option>
                                            <option>Phường Phú Lợi</option>
                                            <option>Phường Diên Hồng</option>
                                            <option>Phường An Nhơn</option>
                                            <option>Xã Long Hòa</option>
                                            <option>Phường Phú Lâm</option>
                                            <option>Phường Chánh Hưng</option>
                                            <option>Phường Thông Tây Hội</option>
                                            <option>Phường Hạnh Thông</option>
                                            <option>Phường Tây Nam</option>
                                            <option>Phường Bến Thành</option>
                                            <option>Phường Bình Quới</option>
                                            <option>Xã Nhà Bè</option>
                                            <option>Phường Cát Lái</option>
                                            <option>Phường Cầu Kiệu</option>
                                            <option>Xã Xuyên Mộc</option>
                                            <option>Xã Hòa Hiệp</option>
                                            <option>Phường Sài Gòn</option>
                                            <option>Xã Phú Giáo</option>
                                            <option>Phường Gò Vấp</option>
                                            <option>Phường Phú An</option>
                                            <option>Phường Đông Hưng Thuận</option>
                                            <option>Xã Long Hải</option>
                                            <option>Phường An Phú Đông</option>
                                            <option>Xã Phước Thành</option>
                                            <option>Phường Chợ Quán</option>
                                            <option>Xã Bình Chánh</option>
                                            <option>Phường Tân Sơn Nhất</option>
                                            <option>Xã Thanh An</option>
                                            <option>Phường Phú Thuận</option>
                                            <option>Xã Long Điền</option>
                                            <option>Phường Trung Mỹ Tây</option>
                                            <option>Xã Châu Đức</option>
                                            <option>Phường Phú Mỹ</option>
                                            <option>Phường Bình Tây</option>
                                            <option>Phường Rạch Dừa</option>
                                            <option>Xã Ngãi Giao</option>
                                            <option>Phường Bảy Hiền</option>
                                            <option>Xã Dầu Tiếng</option>
                                            <option>Xã Đông Thạnh</option>
                                            <option>Phường Bình Trưng</option>
                                            <option>Xã Phước Hòa</option>
                                            <option>Phường Chánh Hiệp</option>
                                            <option>Phường Bình Trị Đông</option>
                                            <option>Phường Long Bình</option>
                                            <option>Phường Phú Định</option>
                                            <option>Xã Tân Nhựt</option>
                                            <option>Phường Bình Dương</option>
                                            <option>Phường Tây Thạnh</option>
                                            <option>Phường Đức Nhuận</option>
                                            <option>Xã Củ Chi</option>
                                            <option>Xã Nghĩa Thành</option>
                                            <option>Xã Nhuận Đức</option>
                                            <option>Phường Bà Rịa</option>
                                            <option>Xã Vĩnh Lộc</option>
                                            <option>Phường Thuận An</option>
                                            <option>Xã Tân An Hội</option>
                                            <option>Phường Tân Hải</option>
                                            <option>Phường Xóm Chiếu</option>
                                            <option>Phường Tam Long</option>
                                            <option>Phường Tân Thới Hiệp</option>
                                            <option>Phường An Đông</option>
                                            <option>Phường Long Trường</option>
                                            <option>Phường Phước Long</option>
                                            <option>Phường Bàn Cờ</option>
                                            <option>Phường Bình Đông</option>
                                            <option>Phường Gia Định</option>
                                            <option>Phường Hiệp Bình</option>
                                            <option>Xã Hồ Tràm</option>
                                            <option>Phường Thạnh Mỹ Tây</option>
                                            <option>Phường Tăng Nhơn Phú</option>
                                            <option>Phường Tân Khánh</option>
                                            <option>Xã Long Sơn</option>
                                            <option>Xã Thường Tân</option>
                                            <option>Phường Tân Sơn</option>
                                            <option>Xã Bình Mỹ</option>
                                            <option>Phường Đông Hòa</option>
                                            <option>Phường Tân Đông Hiệp</option>
                                            <option>Xã Bình Giã</option>
                                            <option>Phường An Phú</option>
                                            <option>Xã Kim Long</option>
                                            <option>Xã Trừ Văn Thố</option>
                                            <option>Phường Phú Nhuận</option>
                                            <option>Phường Bình Tiên</option>
                                            <option>Phường An Khánh</option>
                                            <option>Xã Minh Thạnh</option>
                                            <option>Phường Hòa Hưng</option>
                                            <option>Xã Bình Châu</option>
                                            <option>Xã An Nhơn Tây</option>
                                            <option>Phường Long Phước</option>
                                            <option>Phường Long Nguyên</option>
                                            <option>Xã An Long</option>
                                            <option>Phường Thới Hòa</option>
                                            <option>Xã Phú Hòa Đông</option>
                                            <option>Phường Tân Phú</option>
                                            <option>Phường Khánh Hội</option>
                                            <option>Phường An Hội Tây</option>
                                            <option>Xã An Thới Đông</option>
                                            <option>Phường Dĩ An</option>
                                            <option>Phường Bình Phú</option>
                                            <option>Phường Bình Tân</option>
                                            <option>Phường Tân Sơn Nhì</option>
                                            <option>Phường Tân Mỹ</option>
                                            <option>Phường Bình Thới</option>
                                            <option>Phường Tân Hưng</option>
                                            <option>Đặc khu Côn Đảo</option>
                                            <option>Phường Bình Lợi Trung</option>
                                            <option>Phường Thuận Giao</option>
                                            <option>Phường Tân Uyên</option>
                                            <option>Phường Bình Hòa</option>
                                            <option>Phường Tân Sơn Hòa</option>
                                            <option>Xã Hưng Long</option>
                                            <option>Xã Xuân Thới Sơn</option>
                                            <option>Phường Phú Thạnh</option>
                                            <option>Xã Tân Vĩnh Lộc</option>
                                            <option>Phường Vườn Lài</option>
                                            <option>Xã Hòa Hội</option>
                                            <option>Xã Hóc Môn</option>
                                            <option>Phường Bình Cơ</option>
                                            <option>Phường Vũng Tàu</option>
                                            <option>Phường Tân Tạo</option>
                                            <option>Phường Vĩnh Tân</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhập địa chỉ chi tiết (số nhà, tên đường)</label>
                                    <input type="text" placeholder="Số 2 Võ Oanh" class="login-input">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (không bắt buộc)</label>
                                    <textarea placeholder="Ví dụ: Giao hàng giờ hành chính..." rows="3" class="login-input"></textarea>
                                </div>
                            </div>
                            <!-- === KẾT THÚC FORM "SAU SÁP NHẬP" === -->

                        </div>
                        <!-- === KẾT THÚC KHỐI 1 === -->


                        <!-- === KHỐI 2: NỘI DUNG NHẬN TẠI NHÀ THUỐC (ẨN BAN ĐẦU) === -->
                        <div id="content-nhan-tai-nha-thuoc" class="border-t border-gray-200 pt-5" style="display: none;">
                            <h2 class="text-xl font-semibold mb-4" style="color: var(--primary-dark);">
                                <i class="fas fa-store mr-2"></i>
                                Chọn nhà thuốc lấy hàng
                            </h2>

                            <!-- Thông báo (mô phỏng) -->
                            <div class="bg-sky-50/70 border border-sky-200 rounded-lg p-3 text-center mb-4">
                                <p class="text-sm" style="color: var(--primary-color);">
                                    Đơn vị hành chính đã thay đổi theo quy định. 
                                    <a href="#" class="font-semibold underline">Tra cứu địa chỉ.</a>
                                </p>
                            </div>

                            <!-- Lọc Tỉnh/Phường (đã disable) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố</label>
                                    <select class="login-input" disabled>
                                        <option selected>TP. Hồ Chí Minh</option>
                                    </select>
                                </div>
                                <!-- CẬP NHẬT: Đổi Quận/Huyện thành Phường/Xã và đổi giá trị -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phường/Xã</label>
                                    <select class="login-input" disabled>
                                        <option selected>Phường Thạnh Mỹ Tây</option>
                                    </select>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 mb-3">Có 1 nhà thuốc phù hợp:</p>

                            <!-- Danh sách nhà thuốc (chỉ 1) -->
                            <div class="space-y-3">
                                <!-- CẬP NHẬT: Thẻ địa chỉ hiển thị cả địa chỉ mới và cũ -->
                                <label class="flex items-start p-4 border-2 border-sky-600 bg-sky-50 rounded-lg cursor-pointer shadow-md">
                                    <input type="radio" name="pharmacy" class="form-radio h-5 w-5 mt-1" checked>
                                    <div class="ml-4 flex-grow">
                                        <div class="flex justify-between items-center">
                                            <span class="font-semibold text-base" style="color: var(--primary-dark);">Nhà Thuốc An Tâm - 001, P. Thạnh Mỹ Tây</span>
                                            <!-- CẬP NHẬT: Thêm link Google Maps và target="_blank" -->
                                            <a 
                                                href="https://www.google.com/maps/dir/10.8164596,106.6831393/2+%C4%90%C6%B0%E1%BB%9Dng+V%C3%B5+Oanh,+Ph%C6%B0%E1%BB%9Dng+25,+B%C3%ACnh+Th%E1%BA%A1nh,+Th%C3%A0nh+ph%E1%BB%91+H%E1%BB%93+Ch%C3%AD+Minh/@10.812535,106.6873478,15z/data=!3m1!4b1!4m9!4m8!1m1!4e1!1m5!1m1!1s0x317528a3f0b1f849:0x234506e937a8dbef!2m2!1d106.7170736!2d10.8049159?entry=ttu&g_ep=EgoyMDI1MTExMC4wIKXMDSoASAFQAw%3D%3D" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                class="text-sm font-medium" 
                                                style="color: var(--primary-color); white-space: nowrap;"
                                            >
                                                <i class="fas fa-directions"></i> Chỉ đường
                                            </a>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm mt-1">
                                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-medium">
                                                <i class="fas fa-check-circle text-xs"></i> Có hàng
                                            </span>
                                            <span class="text-gray-700">Mở cửa: 06:00 - 22:00</span>
                                        </div>
                                        <p class="text-sm text-gray-700 mt-2">
                                            <i class="fas fa-map-marker-alt text-gray-500 mr-2"></i>
                                            Số 2 Võ Oanh, Phường Thạnh Mỹ Tây, Quận Bình Thạnh, TP. Hồ Chí Minh
                                        </p>
                                        <!-- Thêm địa chỉ cũ -->
                                        <p class="text-xs text-gray-500 mt-1 pl-6">
                                            Địa chỉ cũ: Số 2 Võ Oanh, P. 25, Q. Bình Thạnh, TP. Hồ Chí Minh
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <!-- === THÊM MỚI: THỜI GIAN NHẬN HÀNG DỰ KIẾN === -->
                            <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-200">
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-xl mr-3" style="color: var(--primary-color);"></i>
                                    <div>
                                        <label class="text-base font-medium text-gray-800">Thời gian nhận hàng dự kiến</label>
                                        <!-- Dòng text này sẽ được JS cập nhật -->
                                        <p id="thoi-gian-nhan-hang-text" class="text-sm font-semibold" style="color: var(--primary-dark);">
                                            <!-- CẬP NHẬT GIỜ MẶC ĐỊNH (do giờ hiện tại là 13:24) -->
                                            Từ 13:00 - 14:00 Hôm nay, 13/11/2025
                                        </p>
                                    </div>
                                </div>
                                <button 
                                    id="btn-thay-doi-gio" 
                                    type="button" 
                                    class="font-semibold text-sm" 
                                    style="color: var(--primary-color);"
                                >
                                    Thay đổi
                                </button>
                            </div>
                            <!-- === KẾT THÚC THÊM MỚI === -->

                        </div>
                        <!-- === KẾT THÚC KHỐI 2 === -->
                        

                        <!-- Toggle Hóa đơn (mô phỏng) -->
                        <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-200">
                            <label for="hoa-don" class="text-base font-medium text-gray-800">Yêu cầu xuất hóa đơn điện tử</label>
                            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                                <input type="checkbox" name="hoa-don" id="hoa-don" class="toggle-checkbox absolute block w-7 h-7 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                                <label for="hoa-don" class="toggle-label block overflow-hidden h-7 rounded-full bg-gray-300 cursor-pointer"></label>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Phương thức thanh toán -->
                    <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-xl login-card-animation" style="animation-delay: 0.3s;">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--primary-dark);">
                            <i class="fas fa-credit-card mr-2"></i>
                            Chọn phương thức thanh toán
                        </h2>
                        <div class="space-y-4">
                            <!-- Dữ liệu mẫu (mô phỏng Long Châu) -->
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50 transition-all">
                                <input type="radio" name="payment-method" class="form-radio h-5 w-5" checked>
                                <img src="https://placehold.co/40x40/e0f2fe/0284c7?text=COD" class="mx-3 rounded">
                                <span class="font-medium">Thanh toán tiền mặt khi nhận hàng (COD)</span>
                            </label>
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50 transition-all">
                                <input type="radio" name="payment-method" class="form-radio h-5 w-5">
                                <img src="https://placehold.co/40x40/e0f2fe/0284c7?text=QR" class="mx-3 rounded">
                                <span class="font-medium">Thanh toán bằng chuyển khoản (QR Code)</span>
                            </label>
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50 transition-all">
                                <input type="radio" name="payment-method" class="form-radio h-5 w-5">
                                <img src="https://placehold.co/40x40/e0f2fe/0284c7?text=MOMO" class="mx-3 rounded">
                                <span class="font-medium">Thanh toán bằng ví MoMo</span>
                            </label>
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50 transition-all">
                                <input type="radio" name="payment-method" class="form-radio h-5 w-5">
                                <img src="https://placehold.co/40x40/e0f2fe/0284c7?text=VISA" class="mx-3 rounded">
                                <span class="font-medium">Thanh toán bằng thẻ quốc tế (Visa, Master...)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- CỘT BÊN PHẢI (THANH TOÁN) -->
                <div class="lg:col-span-1 space-y-6 lg:sticky lg:top-10 self-start">
                    
                    <!-- Card 4: Tổng quan đơn hàng -->
                    <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-xl login-card-animation" style="animation-delay: 0.2s;">
                        <h2 class="text-xl font-semibold mb-4 pb-4 border-b border-gray-200" style="color: var(--primary-dark);">
                            Thông tin đơn hàng
                        </h2>
                        
                        <!-- Voucher -->
                        <div class="flex items-center gap-2 mb-4">
                            <input type="text" placeholder="Nhập mã giảm giá" class="login-input py-2">
                            <button class="login-button py-2 px-4 text-sm whitespace-nowrap !shadow-none !animation-none">
                                Áp dụng
                            </button>
                        </div>

                        <!-- Chi tiết giá -->
                        <div class="space-y-2 text-base">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tạm tính:</span>
                                <span class="font-medium">207.000đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giảm giá trực tiếp:</span>
                                <span class="font-medium text-green-600">0đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giảm giá voucher:</span>
                                <span class="font-medium text-green-600">0đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Phí vận chuyển:</span>
                                <span id="phi-van-chuyen" class="font-medium text-green-600">Miễn phí</span>
                            </div>
                        </div>

                        <!-- Tổng tiền -->
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-300">
                            <span class="text-xl font-bold">Thành tiền:</span>
                            <!-- Cập nhật tổng tiền (mô phỏng 207k + 0đ ship) -->
                            <span id="thanh-tien" class="text-2xl font-bold" style="color: var(--primary-color);">207.000đ</span>
                        </div>

                        <!-- Nút Hoàn tất (từ login.php) -->
                        <button type="submit" class="w-full py-3 px-4 mt-6 text-lg bg-sky-600 text-white rounded-lg font-bold hover:bg-sky-700 transition shadow-lg hover:shadow-sky-500/50">
                            TIẾN HÀNH THANH TOÁN
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-3">
                            Bằng việc tiến hành đặt mua hàng, bạn đồng ý với 
                            <a href="#" class="font-medium" style="color: var(--primary-dark);">Điều khoản dịch vụ</a> và 
                            <a href="#" class="font-medium" style="color: var(--primary-dark);">Chính sách</a>
                            của Nhà Thuốc An Tâm.
                        </p>
                    </div>

                    <!-- Card 5: QR Tải App (mô phỏng) -->
                    <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-xl login-card-animation" style="animation-delay: 0.3s;">
                        <div class="flex items-center gap-4">
                            <img src="https://placehold.co/120x120/0284c7/ffffff?text=QR+An+Tam" alt="QR Code" class="w-28 h-28 rounded-lg">
                            <div>
                                <h3 class="font-semibold text-lg" style="color: var(--primary-dark);">Tải ứng dụng An Tâm</h3>
                                <p class="text-gray-600 text-sm mt-1">Quét mã để tải app, nhận ngàn ưu đãi.</p>
                                <button class="login-button py-2 px-4 mt-3 text-sm !shadow-none !animation-none">
                                    Tải ngay
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- 
    SCRIPT HIỆU ỨNG NỀN (SAO CHÉP TỪ LOGIN.PHP)
    -->
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const canvas = document.getElementById('pills-canvas');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;

                let pills = [];
                const numberOfPills = 100;
                const colors = ['#ffffff', '#bae6fd', '#f0f9ff', '#0284c7'];
                const mouse = { x: null, y: null, radius: 120 };

                window.addEventListener('mousemove', (e) => { mouse.x = e.x; mouse.y = e.y; });
                window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });
                window.addEventListener('resize', () => {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                    init();
                });

                class Pill {
                    constructor() { this.reset(); }
                    reset() {
                        this.x = Math.random() * canvas.width;
                        this.y = Math.random() * canvas.height;
                        this.size = Math.random() * 7 + 5;
                        this.speedY = Math.random() * 1 + 0.2;
                        this.color = colors[Math.floor(Math.random() * colors.length)];
                        this.opacity = Math.random() * 0.5 + 0.15;
                        this.density = (Math.random() * 5) + 1;
                        this.angle = Math.random() * Math.PI * 2;
                        this.rotationSpeed = (Math.random() - 0.5) * 0.01;
                    }
                    update() {
                        let dx = mouse.x - this.x;
                        let dy = mouse.y - this.y;
                        let distance = Math.sqrt(dx * dx + dy * dy);

                        if (mouse.x != null && distance < mouse.radius) {
                            const force = (mouse.radius - distance) / mouse.radius;
                            this.x -= (dx / distance) * force * this.density;
                            this.y -= (dy / distance) * force * this.density;
                        }
                        this.y -= this.speedY;
                        this.angle += this.rotationSpeed;
                        if (this.y < -this.size * 3) {
                            this.y = canvas.height + this.size * 3;
                            this.x = Math.random() * canvas.width;
                        } else if (this.x < -this.size * 3) {
                            this.x = canvas.width + this.size * 3;
                        } else if (this.x > canvas.width + this.size * 3) {
                            this.x = -this.size * 3;
                        }
                    }
                    draw() {
                        ctx.save();
                        ctx.translate(this.x, this.y);
                        ctx.rotate(this.angle);
                        ctx.globalAlpha = this.opacity;
                        ctx.shadowBlur = 12;
                        ctx.shadowColor = this.color;
                        const capsuleHeight = this.size;
                        const capsuleWidth = this.size * 2;
                        ctx.fillStyle = this.color;
                        ctx.beginPath();
                        ctx.arc(capsuleWidth / 4, 0, capsuleHeight / 2, -Math.PI / 2, Math.PI / 2, false);
                        ctx.arc(-capsuleWidth / 4, 0, capsuleHeight / 2, Math.PI / 2, -Math.PI / 2, false);
                        ctx.closePath();
                        ctx.fill();
                        ctx.restore();
                    }
                }
                function init() { pills = Array.from({ length: numberOfPills }, () => new Pill()); }
                function animate() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    pills.forEach(p => { p.update(); p.draw(); });
                    requestAnimationFrame(animate);
                }
                init();
                animate();
            }
        });
    </script>
    <!-- KẾT THÚC SCRIPT HIỆU ỨNG NỀN -->

    <!-- === SCRIPT MỚI ĐỂ TOGGLE HÌNH THỨC GIAO HÀNG === -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnGiaoHang = document.getElementById('btn-giao-hang');
            const btnNhanTaiNhaThuoc = document.getElementById('btn-nhan-tai-nha-thuoc');
            const contentGiaoHang = document.getElementById('content-giao-hang');
            const contentNhanTaiNhaThuoc = document.getElementById('content-nhan-tai-nha-thuoc');
            
            // Lấy các ô hiển thị phí
            const phiVanChuyenText = document.getElementById('phi-van-chuyen');
            const thanhTienText = document.getElementById('thanh-tien');
            
            // Giá trị mô phỏng (bạn sẽ thay bằng logic thật)
            const tamTinh = 207000; 
            const phiShipMock = 30000; // Phí ship giả định

            // Hàm format tiền (lấy từ file PHP của bạn)
            function money_vn(price) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price).replace(' ₫', 'đ');
            }

            // Hàm set active cho Giao Hàng
            function setGiaoHangActive() {
                // Hiển thị/ẩn nội dung
                contentGiaoHang.style.display = 'block';
                contentNhanTaiNhaThuoc.style.display = 'none';

                // Đổi class nút
                btnGiaoHang.classList.replace('tab-inactive', 'tab-active');
                btnNhanTaiNhaThuoc.classList.replace('tab-active', 'tab-inactive');
                
                // Cập nhật phí vận chuyển (mô phỏng)
                // Giả sử đơn hàng < 300k nên có phí ship
                phiVanChuyenText.textContent = money_vn(phiShipMock);
                phiVanChuyenText.classList.remove('text-green-600'); // Bỏ màu xanh (miễn phí)
                thanhTienText.textContent = money_vn(tamTinh + phiShipMock);
            }

            // Hàm set active cho Nhận Tại Nhà Thuốc
            function setNhanTaiNhaThuocActive() {
                // Hiển thị/ẩn nội dung
                contentGiaoHang.style.display = 'none';
                contentNhanTaiNhaThuoc.style.display = 'block';

                // Đổi class nút
                btnNhanTaiNhaThuoc.classList.replace('tab-inactive', 'tab-active');
                btnGiaoHang.classList.replace('tab-active', 'tab-inactive');
                
                // Cập nhật phí vận chuyển (Nhận tại shop = 0đ)
                phiVanChuyenText.textContent = "0đ";
                phiVanChuyenText.classList.add('text-green-600'); // Thêm màu xanh
                thanhTienText.textContent = money_vn(tamTinh);
            }

            // Thêm event listeners
            btnGiaoHang.addEventListener('click', setGiaoHangActive);
            btnNhanTaiNhaThuoc.addEventListener('click', setNhanTaiNhaThuocActive);

            // Set trạng thái ban đầu (Giao hàng tận nơi)
            setGiaoHangActive();
            
            // === THÊM MỚI: JAVASCRIPT ĐỂ XỬ LÝ TAB SÁP NHẬP ===
            const radioTruocSapNhap = document.getElementById('radio-truoc-sap-nhap');
            const radioSauSapNhap = document.getElementById('radio-sau-sap-nhap');
            const formTruocSapNhap = document.getElementById('form-truoc-sap-nhap');
            const formSauSapNhap = document.getElementById('form-sau-sap-nhap');

            radioTruocSapNhap.addEventListener('change', () => {
                if (radioTruocSapNhap.checked) {
                    formTruocSapNhap.style.display = 'block';
                    formSauSapNhap.style.display = 'none';
                }
            });

            radioSauSapNhap.addEventListener('change', () => {
                if (radioSauSapNhap.checked) {
                    formTruocSapNhap.style.display = 'none';
                    formSauSapNhap.style.display = 'block';
                }
            });
            // === KẾT THÚC SCRIPT SÁP NHẬP ===

        });
    </script>
    <!-- === KẾT THÚC SCRIPT MỚI === -->


    <!-- === SỬA LỖI: DI CHUYỂN MODAL VÀ SCRIPT VÀO ĐÚNG VỊ TRÍ (TRƯỚC </body>) === -->

    <!-- === THÊM MỚI: MODAL CHỌN GIỜ === -->
    <div 
        id="thoi-gian-modal-backdrop" 
        class="modal-backdrop"
    ></div>
    <div id="thoi-gian-modal-panel" class="modal-panel">
        <div class="p-6">
            <!-- Header Modal -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold" style="color: var(--primary-dark);">Chọn thời gian nhận hàng</h3>
                <button 
                    id="btn-close-modal" 
                    type="button" 
                    class="text-gray-400 hover:text-gray-700 transition-colors"
                >
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            
            <!-- Nội dung Modal -->
            <div class="space-y-4">
                <!-- Chọn ngày -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chọn ngày nhận:</label>
                    <div id="date-slot-container" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <button type="button" class="slot-button selected" data-date-text="Hôm nay, 13/11/2025">Hôm nay, 13/11/2025</button>
                        <button type="button" class="slot-button" data-date-text="Thứ Sáu, 14/11/2025">Thứ Sáu, 14/11/2025</button>
                        <button type="button" class="slot-button" data-date-text="Thứ Bảy, 15/11/2025">Thứ Bảy, 15/11/2025</button>
                        <button type="button" class="slot-button" data-date-text="Chủ Nhật, 16/11/2025">Chủ Nhật, 16/11/2025</button>
                    </div>
                </div>
                
                <!-- Chọn giờ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chọn giờ nhận:</label>
                    <div id="time-slot-container" class="grid grid-cols-3 md:grid-cols-4 gap-3">
                        <!-- 
                            CẬP NHẬT: Giờ hiện tại là 13:24. 
                            Các slot trước 13:00 sẽ bị disabled.
                            Slot 13:00-14:00 được chọn.
                        -->
                        <button type="button" class="slot-button" disabled>10:00 - 11:00</button>
                        <button type="button" class="slot-button" disabled>11:00 - 12:00</button>
                        <button type="button" class="slot-button" disabled data-time-text="Từ 12:00 - 13:00">12:00 - 13:00</button>
                        <button type="button" class="slot-button selected" data-time-text="Từ 13:00 - 14:00">13:00 - 14:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 14:00 - 15:00">14:00 - 15:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 15:00 - 16:00">15:00 - 16:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 16:00 - 17:00">16:00 - 17:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 17:00 - 18:00">17:00 - 18:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 18:00 - 19:00">18:00 - 19:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 19:00 - 20:00">19:00 - 20:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 20:00 - 21:00">20:00 - 21:00</button>
                        <button type="button" class="slot-button" data-time-text="Từ 21:00 - 22:00">21:00 - 22:00</button>
                    </div>
                </div>
            </div>
            
            <!-- Footer Modal -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <button 
                    id="btn-xac-nhan-gio" 
                    type="button" 
                    class="login-button w-full py-3 !animation-none !shadow-md"
                >
                    Xác nhận
                </button>
            </div>
        </div>
    </div>
    <!-- === KẾT THÚC MODAL === -->


    <!-- === SCRIPT MỚI ĐỂ XỬ LÝ MODAL CHỌN GIỜ === -->
    <script>
        // *** FIX: Bọc toàn bộ script trong DOMContentLoaded ***
        document.addEventListener('DOMContentLoaded', () => {
            // Lấy các element của Modal
            const modalBackdrop = document.getElementById('thoi-gian-modal-backdrop');
            const modalPanel = document.getElementById('thoi-gian-modal-panel');
            const btnThayDoiGio = document.getElementById('btn-thay-doi-gio');
            const btnCloseModal = document.getElementById('btn-close-modal');
            const btnXacNhanGio = document.getElementById('btn-xac-nhan-gio');
            
            // Lấy các container chọn
            const dateSlotContainer = document.getElementById('date-slot-container');
            const timeSlotContainer = document.getElementById('time-slot-container');
            
            // Lấy text hiển thị
            const thoiGianNhanHangText = document.getElementById('thoi-gian-nhan-hang-text');

            // Biến tạm để lưu lựa chọn
            // SỬA LỖI: Cập nhật giờ mặc định (Dựa trên giờ hiện tại 13:24)
            let selectedDateText = "Hôm nay, 13/11/2025";
            let selectedTimeText = "Từ 13:00 - 14:00"; // Giờ 13:24 -> slot 13:00-14:00 là slot đầu tiên

            // *** LOGIC MỚI: Hàm cập nhật trạng thái các slot giờ ***
            function updateSlotAvailability(selectedDate) {
                // Giờ hiện tại (mô phỏng là 13:24, nên currentHour = 13)
                const currentHour = 13; 
                const isToday = selectedDate.includes("Hôm nay");

                const timeButtons = timeSlotContainer.querySelectorAll('.slot-button');
                timeButtons.forEach(btn => {
                    // Lấy giờ bắt đầu, ví dụ "10:00" từ "10:00 - 11:00"
                    const timeRange = btn.textContent.split(' - ')[0];
                    const startHour = parseInt(timeRange.split(':')[0]); 

                    if (isToday) {
                        // Nếu là HÔM NAY, vô hiệu hóa các slot đã qua
                        // (startHour < 13)
                        if (startHour < currentHour) {
                            btn.disabled = true;
                            btn.classList.remove('selected');
                        } else {
                            btn.disabled = false;
                        }
                    } else {
                        // Nếu là NGÀY MAI hoặc ngày sau, bật tất cả các slot
                        btn.disabled = false;
                    }
                });

                // Nếu là hôm nay và slot được chọn trước đó đã bị vô hiệu hóa,
                // tự động chọn slot đầu tiên có thể chọn
                const currentSelectedButton = timeSlotContainer.querySelector('.slot-button.selected');
                if (isToday && (!currentSelectedButton || currentSelectedButton.disabled)) {
                    const firstAvailableSlot = timeSlotContainer.querySelector('.slot-button:not(:disabled)');
                    if (firstAvailableSlot) {
                        selectedTimeText = firstAvailableSlot.getAttribute('data-time-text');
                        updateSelection(timeSlotContainer, 'data-time-text', selectedTimeText);
                    }
                }
            }

            // Hàm mở Modal
            function openTimeModal() {
                // Lấy giá trị hiện tại gán vào biến tạm
                const currentText = thoiGianNhanHangText.textContent.trim();
                // Tách chuỗi, ví dụ: "Từ 13:00 - 14:00 Hôm nay, 13/11/2025"
                const parts = currentText.split(' ');
                selectedTimeText = parts.slice(0, 4).join(' '); // "Từ 13:00 - 14:00"
                selectedDateText = parts.slice(4).join(' ');   // "Hôm nay, 13/11/2025"
                
                // *** LOGIC MỚI: Chạy hàm cập nhật slot giờ ***
                updateSlotAvailability(selectedDateText);
                
                // Cập nhật 'selected' class cho các nút trong modal
                // Phải chạy sau updateSlotAvailability
                updateSelection(dateSlotContainer, 'data-date-text', selectedDateText);
                updateSelection(timeSlotContainer, 'data-time-text', selectedTimeText);

                // Hiển thị modal
                modalBackdrop.classList.add('active');
                modalPanel.classList.add('active');
            }

            // Hàm đóng Modal
            function closeTimeModal() {
                modalBackdrop.classList.remove('active');
                modalPanel.classList.remove('active');
            }

            // Hàm helper để cập nhật class 'selected'
            function updateSelection(container, dataAttribute, value) {
                // Kiểm tra container có tồn tại không
                if (!container) return;
                const buttons = container.querySelectorAll('.slot-button');
                buttons.forEach(btn => {
                    if (btn.getAttribute(dataAttribute) === value) {
                        btn.classList.add('selected');
                    } else {
                        btn.classList.remove('selected');
                    }
                });
            }

            // Gán sự kiện
            // Kiểm tra xem các nút có tồn tại không trước khi gán
            if (btnThayDoiGio) {
                btnThayDoiGio.addEventListener('click', openTimeModal);
            }
            if (btnCloseModal) {
                btnCloseModal.addEventListener('click', closeTimeModal);
            }
            
            // Sự kiện click cho các nút ngày
            if (dateSlotContainer) {
                dateSlotContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('slot-button') && !e.target.disabled) {
                        selectedDateText = e.target.getAttribute('data-date-text');
                        updateSelection(dateSlotContainer, 'data-date-text', selectedDateText);
                        
                        // *** LOGIC MỚI: Cập nhật lại slot giờ khi đổi ngày ***
                        updateSlotAvailability(selectedDateText);
                    }
                });
            }
            
            // Sự kiện click cho các nút giờ
            if (timeSlotContainer) {
                timeSlotContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('slot-button') && !e.target.disabled) {
                        selectedTimeText = e.target.getAttribute('data-time-text');
                        updateSelection(timeSlotContainer, 'data-time-text', selectedTimeText);
                    }
                });
            }

            // Sự kiện click cho nút "Xác nhận"
            if (btnXacNhanGio) {
                btnXacNhanGio.addEventListener('click', () => {
                    // Cập nhật text hiển thị chính
                    if (thoiGianNhanHangText) {
                        thoiGianNhanHangText.textContent = `${selectedTimeText} ${selectedDateText}`;
                    }
                    // Đóng modal
                    closeTimeModal();
                });
            }

            // Gán sự kiện click cho backdrop để đóng modal
            if (modalBackdrop) {
                modalBackdrop.addEventListener('click', closeTimeModal);
            }
        }); // *** FIX: Đóng DOMContentLoaded ***
    </script>
    <!-- === KẾT THÚC SCRIPT MODAL === -->

</body>
</html>