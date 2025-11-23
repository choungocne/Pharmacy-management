<?php
require_once __DIR__ . '/../db.php'; 

try {
    $pdo = pdo();
} catch (Exception $e) {
    echo json_encode([]);
    exit;
}

$sessionId = session_id();
$userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null; // Hoặc $_SESSION['user_id'] tùy code login của bạn

// 3. TRUY VẤN DỮ LIỆU TỪ SQL
$cart_rows = [];

try {
    if ($userId) {
        // Nếu đã đăng nhập: Lấy theo makh
        $sql = "SELECT 
                    gh.masp, 
                    gh.soluong AS cart_quantity, 
                    sp.tensp, 
                    sp.giaban, 
                    sp.hinhsp 
                FROM giohang gh
                JOIN sanpham sp ON gh.masp = sp.masp
                WHERE gh.makh = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $userId]);
    } else {
        // Nếu chưa đăng nhập: Lấy theo session_id
        $sql = "SELECT 
                    gh.masp, 
                    gh.soluong AS cart_quantity, 
                    sp.tensp, 
                    sp.giaban, 
                    sp.hinhsp 
                FROM giohang gh
                JOIN sanpham sp ON gh.masp = sp.masp
                WHERE gh.session_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $sessionId]);
    }
    
    $cart_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ép kiểu dữ liệu để tránh lỗi JS
    foreach ($cart_rows as &$row) {
        $row['giaban'] = (float)$row['giaban'];
        $row['cart_quantity'] = (int)$row['cart_quantity'];
        // Xử lý đường dẫn ảnh nếu cần (thêm base_url nếu trong DB chỉ lưu đường dẫn tương đối)
        if (!empty($row['hinhsp']) && strpos($row['hinhsp'], 'http') === false) {
            // Giả sử ảnh lưu dạng 'uploads/...' thì thêm '/' vào trước
            $row['hinhsp'] = '/Pharmacy-management/' . ltrim($row['hinhsp'], '/');
        }
    }
    unset($row); // Hủy tham chiếu

} catch (Exception $e) {
    // Nếu lỗi thì giỏ hàng rỗng
    $cart_rows = [];
}
// 1. Lấy danh sách Tỉnh/Thành phố từ SQL
$sql_provinces = "SELECT id, full_name FROM provinces ORDER BY id ASC";
$stmt_p = pdo()->query($sql_provinces);
$provinces = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy toàn bộ Phường/Xã và gom nhóm theo Tỉnh (để dùng cho JS)
$sql_wards = "SELECT id, name_with_type, province_id FROM wards";
$stmt_w = pdo()->query($sql_wards);
$all_wards = $stmt_w->fetchAll(PDO::FETCH_ASSOC);

// Gom nhóm: [province_id => [danh sách phường]]
$wards_by_province = [];
foreach ($all_wards as $w) {
    $wards_by_province[$w['province_id']][] = $w;
}

// Chuyển sang JSON để JavaScript đọc được
$json_wards = json_encode($wards_by_province);

?>
<!-- Flag để tắt mini-cart trên trang giỏ hàng lớn -->
<script>
  window.IS_BIG_CART_PAGE = true;
  // Thêm class để có thể ẩn mini-cart bằng CSS dự phòng
  document.documentElement.classList.add('is-cart-page');
</script>
<?php
// Đặt base_url giống các trang khác
$base_url = '/Pharmacy-management';
?>
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
    
    <canvas id="pills-canvas"></canvas>
    
    <!-- 
    PHẦN NỘI DUNG GIỎ HÀNG
    -->
    <div class="relative min-h-screen w-full flex justify-center p-4 sm:p-6 lg:p-10">
        
        <div class="w-full max-w-7xl mx-auto">
            <!-- Tiêu đề và link quay lại -->
            <div class="mb-4">
                <a href="/Pharmacy-management/" class="font-medium text-lg" style="color: var(--primary-dark);">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Quay lại trang chủ
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
                        
                        <div id="list-cart-item"></div>
                        
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
                                    <input type="text" id="buyer-name" placeholder="Nguyễn Văn A" class="login-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                    <input type="text" id="buyer-phone" placeholder="09xxxxxxxx" class="login-input">
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
        <select id="province-select" class="login-input">
            <option value="">-- Chọn Tỉnh/Thành --</option>
            <?php foreach ($provinces as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Phường/Xã</label>
        <select id="ward-select" class="login-input" disabled>
            <option value="">-- Vui lòng chọn Tỉnh trước --</option>
        </select>
    </div>
</div>


                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhập địa chỉ chi tiết (số nhà, tên đường)</label>
                                    <input type="text" id="ship-address-old" placeholder="Số 2 Võ Oanh" class="login-input">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (không bắt buộc)</label>
                                    <textarea placeholder="Ví dụ: Giao hàng giờ hành chính..." rows="3" class="login-input"></textarea>
                                </div>
                            </div>
                            <!-- === KẾT THÚC FORM "TRƯỚC SÁP NHẬP" === -->

                            <!-- === THÊM MỚI: FORM "SAU SÁP NHẬP" (DỰA TRÊN HÌNH ẢNH) === -->
                          
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
                                <span id="tam-tinh" class="font-medium">207.000đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giảm giá trực tiếp:</span>
                                <span id="giam-truc-tiep" class="font-medium text-green-600">0đ</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giảm giá voucher:</span>
                                <span id="giam-voucher" class="font-medium text-green-600">0đ</span>
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
                        <button id="btn-checkout" type="submit" class="w-full py-3 px-4 mt-6 text-lg bg-sky-600 text-white rounded-lg font-bold hover:bg-sky-700 transition shadow-lg hover:shadow-sky-500/50">
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

<form id="hidden-checkout-form" action="checkout.php" method="POST" style="display: none;">
    <input type="hidden" name="fullname" id="hidden_fullname">
    <input type="hidden" name="phone" id="hidden_phone">
    <input type="hidden" name="address" id="hidden_address">
    <input type="hidden" name="total_amount" id="hidden_total_amount">
    <input type="hidden" name="payment_method" id="hidden_payment_method">
    <input type="hidden" name="cart_items" id="hidden_cart_items">
</form>
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

<form id="hidden-checkout-form" action="checkout.php" method="POST" style="display: none;">
    <input type="hidden" name="fullname" id="hidden_fullname">
    <input type="hidden" name="phone" id="hidden_phone">
    <input type="hidden" name="address" id="hidden_address">
    <input type="hidden" name="total_amount" id="hidden_total_amount">
    <input type="hidden" name="payment_method" id="hidden_payment_method">
    
    <input type="hidden" name="cart_items" id="hidden_cart_items">
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Lấy dữ liệu giỏ hàng từ PHP (Đã bao gồm tensp, giaban)
    const cartRows = <?= json_encode($cart_rows ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const btnCheckout = document.getElementById('btn-checkout');

    btnCheckout.addEventListener('click', (e) => {
        e.preventDefault();

        // 1. Validate dữ liệu (Giữ nguyên logic cũ của bạn)
        const buyerName = document.getElementById('buyer-name').value.trim();
        const buyerPhone = document.getElementById('buyer-phone').value.trim();
        
        if (!buyerName || !buyerPhone) {
            alert("Vui lòng điền tên và số điện thoại người nhận!");
            return;
        }

        // 2. Xử lý địa chỉ (Kết hợp Tỉnh/Phường/Chi tiết)
        let finalAddress = "";
        const isDelivery = document.getElementById('btn-giao-hang').classList.contains('tab-active');
        
        if (isDelivery) {
            const provinceSel = document.getElementById('province-select');
            const wardSel = document.getElementById('ward-select');
            const detailAddr = document.getElementById('ship-address-old').value.trim(); // ID input địa chỉ

            const provinceText = provinceSel.options[provinceSel.selectedIndex]?.text || "";
            const wardText = wardSel.options[wardSel.selectedIndex]?.text || "";

            if(provinceSel.value === "" || wardSel.value === "" || detailAddr === "") {
                alert("Vui lòng chọn đầy đủ địa chỉ giao hàng!");
                return;
            }
            finalAddress = `${detailAddr}, ${wardText}, ${provinceText}`;
        } else {
            finalAddress = "Nhận tại nhà thuốc (123 Nguyễn Huệ, Q1)";
        }

        // 3. Lấy tổng tiền
        const totalText = document.getElementById('thanh-tien').textContent;
        const totalAmount = parseInt(totalText.replace(/\./g, '').replace('đ', '').replace(/,/g, ''));

        // 4. Xác định phương thức thanh toán
        let paymentMethod = 'COD';
        const paymentChecked = document.querySelector('input[name="payment-method"]:checked');
        if (paymentChecked) {
            paymentMethod = paymentChecked.value; 
        }

        // 5. Đổ dữ liệu vào Form ẩn
        document.getElementById('hidden_fullname').value = buyerName;
        document.getElementById('hidden_phone').value = buyerPhone;
        document.getElementById('hidden_address').value = finalAddress;
        document.getElementById('hidden_total_amount').value = totalAmount;
        document.getElementById('hidden_payment_method').value = paymentMethod;
        
        // --- QUAN TRỌNG: Gửi JSON chứa toàn bộ thông tin sản phẩm (để lưu tên + giá) ---
        document.getElementById('hidden_cart_items').value = JSON.stringify(cartRows);

        // 6. Submit
        btnCheckout.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        btnCheckout.disabled = true;
        document.getElementById('hidden-checkout-form').submit();
    });
});
</script>

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
   <script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dữ liệu Phường/Xã lấy từ PHP (Không cần gọi API)
    const WARDS_DATA = <?php echo $json_wards; ?>; 

    const provinceSelect = document.getElementById('province-select');
    const wardSelect = document.getElementById('ward-select');

    // 2. Bắt sự kiện khi chọn Tỉnh
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            const provinceId = this.value;
            
            // Reset ô Phường
            wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            wardSelect.disabled = true;

            // Nếu có dữ liệu phường của tỉnh này
            if (provinceId && WARDS_DATA[provinceId]) {
                const listWards = WARDS_DATA[provinceId];

                // Sắp xếp theo tên (a-z)
                listWards.sort((a, b) => a.name_with_type.localeCompare(b.name_with_type));

                // Tạo danh sách option
                let html = '<option value="">-- Chọn Phường/Xã --</option>';
                listWards.forEach(w => {
                    html += `<option value="${w.id}">${w.name_with_type}</option>`;
                });

                wardSelect.innerHTML = html;
                wardSelect.disabled = false;
            } else {
                // Trường hợp tỉnh không có phường (hiếm gặp)
                if(provinceId) {
                     wardSelect.innerHTML = '<option value="">-- Không có dữ liệu --</option>';
                } else {
                     wardSelect.innerHTML = '<option value="">-- Vui lòng chọn Tỉnh trước --</option>';
                }
            }
        });
    }
    
});
</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const BASE_URL = '<?= $base_url ?>';
            const listCart = document.getElementById('list-cart-item');
            const cartRows = <?= json_encode($cart_rows ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

            // Helpers
            const vn = n => new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n) + 'đ';
            const $ = s => document.querySelector(s);

            function renderCartItems() {
                if (!listCart || !Array.isArray(cartRows)) return;
                listCart.innerHTML = '';

                cartRows.forEach((sp, index) => {
                    const cartId = typeof sp?.id !== 'undefined' ? sp.id : '';
                    const rawQuantity = typeof sp?.cart_quantity !== 'undefined' ? parseInt(sp.cart_quantity, 10) : 1;
                    const safeQuantity = Math.max(1, Math.min(100, isNaN(rawQuantity) ? 1 : rawQuantity));
                    const priceText = vn(sp && typeof sp.giaban !== 'undefined' ? sp.giaban : 0);
                    const imageSrc = sp && sp.hinhsp ? sp.hinhsp : '';
                    const productName = sp && sp.tensp ? sp.tensp : '';

                    const itemHTML = `
                        <div class="flex items-start gap-4 py-4 border-b border-gray-200 cart-item cart-item${index + 1}" data-cart-id="${cartId}">
                            <img src="${imageSrc}" alt="${productName}" class="w-24 h-24 rounded-lg border border-gray-200 object-cover">
                            <div class="flex-grow space-y-2">
                                <h3 class="font-medium text-gray-800">${productName}</h3>
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-semibold text-lg" style="color: var(--primary-color);">${priceText}</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:text-gray-800 btn-change-qty" data-delta="-1">-</button>
                                        <input type="number" class="login-input w-20 text-center py-1 px-2 cart-qty-input" value="${safeQuantity}" min="1" max="100">
                                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:text-gray-800 btn-change-qty" data-delta="1">+</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="text-gray-400 hover:text-red-500 transition-colors" onclick="removeFromCart(${cartId})">
                                <i class="fas fa-trash-alt fa-lg"></i>
                            </button>
                        </div>
                    `;
                    listCart.insertAdjacentHTML('beforeend', itemHTML);
                });
            }

            // Cập nhật phần tóm tắt đơn hàng từ JSON backend
            function renderSummary(sum) {
                if (!sum) return;
                const sub = $('#tam-tinh');
                const gtr = $('#giam-truc-tiep');
                const gvc = $('#giam-voucher');
                const ship = $('#phi-van-chuyen');
                const ttl = $('#thanh-tien');

                if (sub)  sub.textContent  = vn(sum.subtotal || 0);
                if (gtr)  gtr.textContent  = sum.discount_direct  ? ('-' + vn(sum.discount_direct))  : '0đ';
                if (gvc)  gvc.textContent  = sum.discount_voucher ? ('-' + vn(sum.discount_voucher)) : '0đ';
                if (ship) ship.textContent = (sum.shipping === 0) ? 'Miễn phí' : vn(sum.shipping || 0);
                if (ttl)  ttl.textContent  = vn(sum.grand_total || 0);
            }

            // Gọi API update quantity
            function postUpdateQuantity(id, quantity) {
                const body = new URLSearchParams({ action: 'update_quantity', id: String(id), quantity: String(quantity) });
                return fetch(`${BASE_URL}/cart_handler.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                }).then(r => r.json());
            }

            renderCartItems();
            if (!listCart) return;

            // Khi click nút +/- (đã có đoạn lắng nghe trước đó, nếu chưa thì thêm)
            listCart.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-change-qty');
                if (!btn) return;

                const wrapper = btn.closest('.cart-item');
                const input   = wrapper?.querySelector('.cart-qty-input');
                const id      = wrapper?.getAttribute('data-cart-id'); // id dòng giỏ

                if (!input || !id) return;

                const delta = parseInt(btn.dataset.delta || '0', 10);
                let val = parseInt(input.value || '1', 10) + delta;
                val = Math.max(1, Math.min(100, val));
                input.value = val;

                postUpdateQuantity(id, val).then(json => {
                    if (json && json.success) {
                        renderSummary(json);
                        if (typeof viewCart === 'function') viewCart(); // refresh mini cart nếu có
                    }
                }).catch(console.error);
            });

            // Khi user gõ số trực tiếp
            listCart.addEventListener('change', (e) => {
                const input = e.target.closest('.cart-qty-input');
                if (!input) return;

                const wrapper = input.closest('.cart-item');
                const id = wrapper?.getAttribute('data-cart-id');
                if (!id) return;

                let val = parseInt(input.value || '1', 10);
                val = Math.max(1, Math.min(100, val));
                input.value = val;

                postUpdateQuantity(id, val).then(json => {
                    if (json && json.success) {
                        renderSummary(json);
                        if (typeof viewCart === 'function') viewCart();
                    }
                }).catch(console.error);
            });

            // Load tổng tiền lần đầu (nếu cần)
            fetch(`${BASE_URL}/cart_handler.php?action=view`)
              .then(r => r.json())
              .then(json => { if (json && json.success) renderSummary(json); })
              .catch(() => {});
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', () => {
    const btnCheckout = document.getElementById('btn-checkout');

    // Hàm hiển thị lỗi (đổi màu viền input thành đỏ)
    function showError(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.classList.add('border-red-500', 'bg-red-50');
            // Xóa lỗi khi người dùng bắt đầu nhập
            el.addEventListener('input', () => {
                el.classList.remove('border-red-500', 'bg-red-50');
            }, { once: true });
        }
    }

    // Hàm kiểm tra số điện thoại đơn giản (VN)
    function isValidPhone(phone) {
        const regex = /^(0|\+84)\d{9,10}$/;
        return regex.test(phone);
    }

    btnCheckout.addEventListener('click', (e) => {
        e.preventDefault(); // Chặn hành động mặc định
        
        let isValid = true;
        let firstErrorId = null; // Để scroll tới lỗi đầu tiên

        // 1. Validate thông tin người đặt (Luôn bắt buộc)
        const buyerName = document.getElementById('buyer-name');
        const buyerPhone = document.getElementById('buyer-phone');

        if (!buyerName.value.trim()) {
            showError('buyer-name');
            isValid = false;
            if (!firstErrorId) firstErrorId = 'buyer-name';
        }

        if (!buyerPhone.value.trim() || !isValidPhone(buyerPhone.value.trim())) {
            showError('buyer-phone');
            isValid = false;
            if (!firstErrorId) firstErrorId = 'buyer-phone';
        }

        // 2. Kiểm tra Tab đang chọn (Giao hàng hay Nhận tại shop)
        const isDelivery = document.getElementById('btn-giao-hang').classList.contains('tab-active');

        if (isDelivery) {
            // --- Đang chọn GIAO HÀNG ---
            // Kiểm tra xem đang dùng form "Trước" hay "Sau" sáp nhập
            const isTruocSapNhap = document.getElementById('radio-truoc-sap-nhap').checked;
            const suffix = isTruocSapNhap ? '-old' : '-new';

            const shipNameId = 'ship-name' + suffix;
            const shipPhoneId = 'ship-phone' + suffix;
            const shipAddressId = 'ship-address' + suffix;

            const shipName = document.getElementById(shipNameId);
            const shipPhone = document.getElementById(shipPhoneId);
            const shipAddress = document.getElementById(shipAddressId);

            if (!shipName.value.trim()) {
                showError(shipNameId);
                isValid = false;
                if (!firstErrorId) firstErrorId = shipNameId;
            }
            if (!shipPhone.value.trim() || !isValidPhone(shipPhone.value.trim())) {
                showError(shipPhoneId);
                isValid = false;
                if (!firstErrorId) firstErrorId = shipPhoneId;
            }
            if (!shipAddress.value.trim()) {
                showError(shipAddressId);
                isValid = false;
                if (!firstErrorId) firstErrorId = shipAddressId;
            }

        } else {
            // --- Đang chọn NHẬN TẠI NHÀ THUỐC ---
            // Kiểm tra xem đã chọn nhà thuốc chưa (radio name="pharmacy")
            const pharmacyRadios = document.querySelectorAll('input[name="pharmacy"]:checked');
            if (pharmacyRadios.length === 0) {
                alert("Vui lòng chọn nhà thuốc để nhận hàng.");
                isValid = false;
            }
        }

        // 3. Kiểm tra phương thức thanh toán
        const paymentRadios = document.querySelectorAll('input[name="payment-method"]:checked');
        if (paymentRadios.length === 0) {
            alert("Vui lòng chọn phương thức thanh toán.");
            isValid = false;
        }

        // XỬ LÝ KẾT QUẢ
        if (!isValid) {
        alert("Vui lòng điền đầy đủ thông tin nhận hàng.");
        if (firstErrorId) {
            document.getElementById(firstErrorId).focus();
            document.getElementById(firstErrorId).scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } else {
        // === CẬP NHẬT MỚI: LẤY DỮ LIỆU VÀ SUBMIT FORM ===
        
        // 1. Lấy thông tin cơ bản
        const finalName = document.getElementById('buyer-name').value;
        const finalPhone = document.getElementById('buyer-phone').value;
        
        // 2. Xử lý địa chỉ (Logic phức tạp tùy vào tab đang chọn)
        let finalAddress = "";
        
        if (isDelivery) {
            // Đang chọn Giao hàng tận nơi
            const isTruocSapNhap = document.getElementById('radio-truoc-sap-nhap').checked;
            const suffix = isTruocSapNhap ? '-old' : '-new';
            
            // Lấy các giá trị từ form (giả sử bạn đã đặt ID cho các select tỉnh/huyện/xã là city-old, dist-old...)
            // Ở đây mình lấy ví dụ lấy từ input địa chỉ chi tiết + text của select
            const addressDetail = document.getElementById('ship-address' + suffix).value;
            
            // Lưu ý: Bạn cần thêm ID cho các thẻ select Tỉnh/Huyện/Xã để lấy text chính xác.
            // Ví dụ tạm thời lấy địa chỉ chi tiết:
            finalAddress = addressDetail + " (Giao tận nơi)"; 
        } else {
            // Đang chọn Nhận tại nhà thuốc
            finalAddress = "Nhận tại: Nhà Thuốc An Tâm - 001, P. Thạnh Mỹ Tây";
        }

        // 3. Lấy tổng tiền (loại bỏ chữ 'đ' và dấu chấm)
        const totalText = document.getElementById('thanh-tien').textContent;
        const totalAmount = parseInt(totalText.replace(/\./g, '').replace('đ', ''));

        // 4. Lấy phương thức thanh toán
        let paymentMethod = 'COD';
        const paymentChecked = document.querySelector('input[name="payment-method"]:checked');
        if (paymentChecked) {
            // Kiểm tra src của ảnh hoặc logic để xác định value
            const parentLabel = paymentChecked.closest('label');
            if(parentLabel.innerHTML.includes('MOMO')) paymentMethod = 'MOMO';
            else if(parentLabel.innerHTML.includes('QR')) paymentMethod = 'QR';
            else if(parentLabel.innerHTML.includes('VISA')) paymentMethod = 'VISA';
        }

        // 5. Đổ dữ liệu vào Form ẩn
        document.getElementById('hidden_fullname').value = finalName;
        document.getElementById('hidden_phone').value = finalPhone;
        document.getElementById('hidden_address').value = finalAddress;
        document.getElementById('hidden_total_amount').value = totalAmount;
        document.getElementById('hidden_payment_method').value = paymentMethod;
        
        // Gửi kèm dữ liệu giỏ hàng (biến cartRows lấy từ PHP ở trên)
        // Lưu ý: Đảm bảo biến cartRows đã được khai báo trong script render giỏ hàng
        if (typeof cartRows !== 'undefined') {
            document.getElementById('hidden_cart_items').value = JSON.stringify(cartRows);
        }

        // 6. Submit Form
        btnCheckout.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        btnCheckout.disabled = true;
        
        document.getElementById('hidden-checkout-form').submit();
    }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Lấy dữ liệu gốc từ PHP (đã query từ SQL)
    // Biến này sẽ là "Kho dữ liệu" chính ở Client
    let cartRows = <?= json_encode($cart_rows ?? [], JSON_UNESCAPED_UNICODE) ?>;
    
    const listCart = document.getElementById('list-cart-item');
    const btnCheckout = document.getElementById('btn-checkout');
    const vn = n => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n);

    // 2. Hàm Render Giỏ hàng
    function renderCart() {
        if (!listCart) return;
        listCart.innerHTML = '';
        
        let subtotal = 0;

        cartRows.forEach((item, index) => {
            // Tính toán tiền
            const price = parseFloat(item.giaban);
            const qty = parseInt(item.cart_quantity);
            subtotal += price * qty;

            const html = `
                <div class="flex items-start gap-4 py-4 border-b border-gray-200">
                    <img src="${item.hinhsp}" class="w-20 h-20 rounded-lg border object-cover">
                    <div class="flex-grow">
                        <h3 class="font-medium text-gray-800 text-sm">${item.tensp}</h3>
                        <div class="flex items-center justify-between mt-2">
                            <span class="font-bold text-sky-600">${vn(price)}</span>
                            <div class="flex items-center gap-2">
                                <button type="button" class="w-7 h-7 border rounded flex items-center justify-center hover:bg-gray-100" 
                                    onclick="updateQty(${index}, -1)">-</button>
                                <input type="number" class="w-12 text-center border rounded py-1 text-sm" 
                                    value="${qty}" readonly>
                                <button type="button" class="w-7 h-7 border rounded flex items-center justify-center hover:bg-gray-100" 
                                    onclick="updateQty(${index}, 1)">+</button>
                            </div>
                        </div>
                    </div>
                    <button onclick="removeItem(${index})" class="text-gray-400 hover:text-red-500">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            listCart.insertAdjacentHTML('beforeend', html);
        });

        updateSummary(subtotal);
    }

    // 3. Hàm cập nhật tổng tiền
    function updateSummary(subtotal) {
        // Logic phí ship: > 300k hoặc Nhận tại quán thì miễn phí
        const isPickUp = document.getElementById('btn-nhan-tai-nha-thuoc').classList.contains('tab-active');
        let shipFee = 30000;
        
        if (subtotal >= 300000 || isPickUp) {
            shipFee = 0;
        }

        document.getElementById('tam-tinh').innerText = vn(subtotal);
        document.getElementById('phi-van-chuyen').innerText = (shipFee === 0) ? 'Miễn phí' : vn(shipFee);
        document.getElementById('thanh-tien').innerText = vn(subtotal + shipFee);
    }

    // 4. Hàm xử lý sự kiện (Global scope để gọi từ HTML string)
    window.updateQty = function(index, delta) {
        let newQty = parseInt(cartRows[index].cart_quantity) + delta;
        if (newQty < 1) newQty = 1;
        // Cập nhật vào biến gốc cartRows
        cartRows[index].cart_quantity = newQty;
        renderCart();
    };

    window.removeItem = function(index) {
        if(confirm('Xóa sản phẩm này?')) {
            cartRows.splice(index, 1);
            renderCart();
        }
    };

    // 5. Xử lý nút THANH TOÁN (Gửi dữ liệu sang checkout.php)
    if(btnCheckout){
        btnCheckout.addEventListener('click', (e) => {
            e.preventDefault(); // Chặn submit mặc định

            // Validate cơ bản
            const name = document.getElementById('buyer-name').value;
            const phone = document.getElementById('buyer-phone').value;
            if(!name || !phone) {
                alert("Vui lòng nhập Tên và Số điện thoại!");
                return;
            }

            // Lấy thông tin địa chỉ
            const isDelivery = document.getElementById('btn-giao-hang').classList.contains('tab-active');
            let address = "Nhận tại nhà thuốc";
            if(isDelivery) {
                const detail = document.getElementById('ship-address-old').value;
                // Lấy text từ select box tỉnh (nếu có)
                const prov = document.getElementById('province-select');
                const provText = prov.options[prov.selectedIndex]?.text || '';
                address = `${detail}, ${provText}`;
            }

            // Đổ dữ liệu vào Form ẩn
            document.getElementById('hidden_fullname').value = name;
            document.getElementById('hidden_phone').value = phone;
            document.getElementById('hidden_address').value = address;
            
            // Lấy phương thức thanh toán
            const payment = document.querySelector('input[name="payment-method"]:checked');
            /* Logic map value dựa trên text hoặc ID, ở đây giả sử: */
            let payMethod = 'COD';
            if(payment) {
                const label = payment.closest('label').innerText;
                if(label.includes('MoMo')) payMethod = 'MOMO';
                else if(label.includes('QR')) payMethod = 'QR';
                else if(label.includes('thẻ')) payMethod = 'ATM';
            }
            document.getElementById('hidden_payment_method').value = payMethod;

            // QUAN TRỌNG: Chuyển mảng cartRows thành JSON string để gửi đi
            document.getElementById('hidden_cart_items').value = JSON.stringify(cartRows);

            // Submit form ẩn
            document.getElementById('hidden-checkout-form').submit();
        });
    }

    // Xử lý chuyển tab (để tính lại ship)
    document.getElementById('btn-giao-hang').addEventListener('click', () => {
        renderCart(); // Render lại để tính ship
    });
    document.getElementById('btn-nhan-tai-nha-thuoc').addEventListener('click', () => {
        renderCart(); // Render lại để tính ship
    });

    // Khởi chạy lần đầu
    renderCart();
});
</script>
</body>
</html>
