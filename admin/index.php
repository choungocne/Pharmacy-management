<?php
$page_title = 'Trang Chủ - Quản Trị Nhà Thuốc';
$active = 'home'; 
require __DIR__ . '/partials/header.php'; // Gọi toàn bộ layout, CSS và hiệu ứng vào
?>

<!-- =============================================== -->
<!-- BẮT ĐẦU NỘI DUNG RIÊNG CỦA TRANG INDEX          -->
<!-- =============================================== -->
<main class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        <!-- Header của phần nội dung -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Chào mừng trở lại!</h2>
                <p class="text-gray-500 mt-1">Đây là trang quản trị của Nhà thuốc An Tâm.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                   <input type="search" placeholder="Tìm kiếm sản phẩm..." class="pl-10 pr-4 py-2 w-72 border border-gray-300 rounded-full bg-white shadow-sm focus:ring-2 focus:outline-none transition" style="--tw-ring-color: var(--primary-color)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                </div>
                <div class="flex items-center gap-3">
                     <img src="https://placehold.co/40x40/0284c7/FFFFFF?text=A" alt="Avatar" class="rounded-full">
                    <div>
                        <p class="font-semibold">Nguyễn Văn A</p>
                        <p class="text-sm text-gray-500">Quản trị viên</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Phần thẻ thông tin tổng quan -->
        <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-md border border-gray-200">
            <h3 class="text-xl font-semibold mb-4" style="color: var(--primary-dark);">Tổng quan nhanh</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Doanh thu hôm nay</h4>
                    <p class="text-2xl md:text-3xl font-bold mt-2 whitespace-nowrap" style="color: var(--primary-color)">12.500.000₫</p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Đơn hàng mới</h4>
                    <p class="text-3xl font-bold text-sky-600 mt-2">32</p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Sắp hết hàng</h4>
                    <p class="text-3xl font-bold text-amber-600 mt-2">8</p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Tổng số sản phẩm</h4>
                    <p class="text-3xl font-bold text-slate-600 mt-2">1,250</p>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- =============================================== -->
<!-- KẾT THÚC NỘI DUNG RIÊNG CỦA TRANG INDEX         -->
<!-- =============================================== -->

<?php
// Đóng các thẻ HTML đã được mở trong header.php
?>
</div> <!-- Đóng thẻ div.flex.h-screen -->
</body>
</html>

