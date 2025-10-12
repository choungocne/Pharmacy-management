<?php
// ==========================================
// INDEX – TRANG CHÍNH CỦA WEBSITE
// ==========================================

// Đường dẫn gốc
$base_url = '/Pharmacy-management';

// Trang mặc định là "home"
$pages = $_GET['pages'] ?? 'home';

// Xác định đường dẫn file của trang
$path = "pages/$pages.php";

// ==========================================
// NẠP NỘI DUNG TRANG
// ==========================================
if (file_exists($path)) {
    ob_start();
    include $path; // nạp file trang con (home, about, products, ...)
    $page_content = ob_get_clean();
} else {
    // Nếu không tồn tại file
    $page_title = "404 - Không tìm thấy trang";
    $page_content = "<div class='container py-5 text-center'>
                        <h2>Trang không tồn tại</h2>
                        <p>Vui lòng quay lại <a href='{$base_url}/index.php'>Trang chủ</a></p>
                    </div>";
}

// ==========================================
// HIỂN THỊ GIAO DIỆN (BASE TEMPLATE)
// ==========================================
include 'base.php';
