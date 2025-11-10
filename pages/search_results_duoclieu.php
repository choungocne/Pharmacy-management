<?php
// =================================================================
// TỆP: search_results_duoclieu.php
// CHỨC NĂNG: Xử lý tìm kiếm và hiển thị kết quả DƯỢC LIỆU
// =================================================================

// Đảm bảo hàm pdo() có sẵn
require_once 'db.php'; 

// Lấy base URL
if (!isset($base_url)) $base_url = '/Pharmacy-management'; 

// 1. Lấy từ khóa tìm kiếm
$search_query = $_GET['q'] ?? '';
$search_query = trim($search_query);

// 2. Thực hiện truy vấn Dược liệu
$results = []; // Khởi tạo biến $results là mảng rỗng để tránh lỗi Fatal Error
if (!empty($search_query)) {
    // Sửa truy vấn SQL trong search_results_duocchat.php
// Sửa truy vấn SQL trong search_results_duocchat.php
// Sửa truy vấn SQL trong search_results_duoclieu.php
$sql = "
    SELECT 
        madl, tendl, tenkhac, cong_dung, hinh_anh
    FROM duoclieu 
    WHERE (tendl LIKE :query OR tenkhac LIKE :query) 
    LIMIT 20 -- Đã bỏ AND trangthai = 1
";
// ... (Phần còn lại của code giữ nguyên)

    try {
        $pdo = pdo();
        $stmt = $pdo->prepare($sql);
        $search_term = '%' . $search_query . '%';
        $stmt->bindParam(':query', $search_term);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Xử lý lỗi CSDL nếu cần
        $results = []; 
    }
} 
// Nếu không có từ khóa tìm kiếm, $results vẫn là mảng rỗng (đã khởi tạo ở trên)

$total_found = count($results);
?>
<div class="duoclieu-search-content">
    <div class="search-info">
        <h2 class="search-title">
            Kết quả tra cứu Dược Liệu cho: 
            <span class="query-term">"<?= htmlspecialchars($search_query) ?>"</span> 
            (Tìm thấy **<?= $total_found ?>** dược liệu)
        </h2>
    </div>
    
    <?php if ($total_found > 0): ?>
        <div class="result-list duoclieu-list">
            <?php foreach ($results as $item): ?>
                <?php 
                    // Đường dẫn hình ảnh được chuẩn hóa
                    $img_url = htmlspecialchars($item['hinh_anh'] ?? $base_url . '/static/img/placeholder.jpg'); 
                ?>
                <div class="duoclieu-item result-card">
                    <a href="<?= $base_url ?>/base.php?page=duoclieu_detail&madl=<?= $item['madl'] ?>" class="item-link">
                        <div class="item-img">
                            <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($item['tendl']) ?>">
                        </div>
                        <div class="item-details">
                            <h3 class="item-title"><?= htmlspecialchars($item['tendl']) ?></h3>
                            <p class="item-description" title="<?= htmlspecialchars($item['cong_dung'] ?? 'Chưa có thông tin công dụng.') ?>">
                                **Công dụng chính:** <?= htmlspecialchars(substr($item['cong_dung'] ?? 'Đang cập nhật...', 0, 100)) ?>...
                            </p>
                            <span class="read-more">Xem chi tiết →</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results" style="padding: 50px; text-align: center; background: white; border-radius: 8px;">
            <i class="fas fa-leaf" style="font-size: 40px; color: #28a745; margin-bottom: 15px;"></i>
            <p>Xin lỗi, không tìm thấy Dược liệu nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
        </div>
    <?php endif; ?>
</div>

<style>
/* CSS để đảm bảo hiển thị đúng */
.duoclieu-search-content { padding: 20px 0; }
.result-list { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
    gap: 25px; 
}
.result-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    overflow: hidden;
}
.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}
.item-link {
    display: flex;
    text-decoration: none;
    color: inherit;
    height: 100%;
}
.item-img {
    width: 120px;
    height: 120px;
    padding: 15px;
    flex-shrink: 0;
}
.item-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 8px;
}
.item-details {
    padding: 15px;
    flex-grow: 1;
}
.item-title {
    font-size: 18px;
    font-weight: 700;
    color: #28a745; 
    margin-top: 0;
    margin-bottom: 5px;
}
.item-description {
    font-size: 14px;
    color: #555;
    margin-bottom: 8px;
    line-height: 1.4;
}
.read-more {
    font-size: 14px;
    color: #007bff;
    font-weight: 600;
}
</style>