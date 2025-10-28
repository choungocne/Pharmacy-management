<?php
// =================================================================
// TỆP: search_results_duoclieu.php
// CHỨC NĂNG: Hiển thị kết quả tra cứu DƯỢC LIỆU
// =================================================================

require_once 'db.php'; 
global $base_url;

// 1. Lấy từ khóa tìm kiếm
$search_query = $_GET['q'] ?? '';
$search_query = trim($search_query);

// 2. Chuẩn bị truy vấn CSDL
$results = [];
if (!empty($search_query)) {
    // Tìm kiếm trong bảng duoclieu theo tendl, tenkhac và cong_dung
    $sql = "SELECT madl, tendl, tenkhoahoc, tenkhac, bo_phan_dung, cong_dung, hinh_anh 
            FROM duoclieu 
            WHERE (tendl LIKE :query OR tenkhac LIKE :query OR cong_dung LIKE :query)
            AND trangthai = 1 
            LIMIT 20"; 

    try {
        $pdo = pdo(); // Lấy kết nối PDO
        $stmt = $pdo->prepare($sql);
        $search_term = '%' . $search_query . '%';
        $stmt->bindParam(':query', $search_term);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // error_log("Lỗi truy vấn dược liệu: " . $e->getMessage());
    }
}
?>

<div class="search-results-page">
    <div class="container">
        <h2 class="search-title">
            Kết quả tra cứu Dược liệu cho: 
            <span class="query-term">"<?= htmlspecialchars($search_query) ?>"</span> 
            (Tìm thấy **<?= count($results) ?>** dược liệu)
        </h2>

        <hr class="divider">

        <?php if (!empty($results)): ?>
            <div class="result-list duoclieu-list">
                <?php foreach ($results as $item): ?>
                    <div class="duoclieu-item">
                        <div class="item-img">
                            <?php 
                            $img_url = $item['hinh_anh'] ? $base_url . '/static/img/duoclieu/' . htmlspecialchars($item['hinh_anh']) : '';
                            if (!empty($img_url)): ?>
                                <img src="<?= $img_url ?>" 
                                     alt="Hình ảnh <?= htmlspecialchars($item['tendl']) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="item-body">
                            <h3 class="item-name"><?= htmlspecialchars($item['tendl']) ?></h3>
                            <p><strong>Tên khoa học:</strong> <em><?= htmlspecialchars($item['tenkhoahoc'] ?? 'N/A') ?></em></p>
                            <p><strong>Bộ phận dùng:</strong> <?= htmlspecialchars($item['bo_phan_dung'] ?? 'N/A') ?></p>
                            <p><strong>Công dụng chính:</strong> <?= htmlspecialchars(substr($item['cong_dung'], 0, 200)) ?>...</p>
                            
                            <a href="<?= $base_url ?>/base.php?page=duoclieu_detail&madl=<?= $item['madl'] ?>" class="btn-detail">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-leaf"></i>
                <p>Xin lỗi, không tìm thấy Dược liệu nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* CSS Tùy chỉnh cho Dược Liệu (Nên đưa vào file CSS riêng) */
.search-results-page { padding: 30px 0; min-height: 500px; background-color: #f9f9f9; }
.container { width: 90%; max-width: 1400px; margin: auto; }
.search-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 20px; }
.query-term { color: #004aad; }
.divider { border: 0; border-top: 1px solid #ccc; margin: 20px 0 40px; }
.no-results { text-align: center; padding: 50px; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.no-results i { font-size: 60px; color: #28a745; margin-bottom: 20px; } /* Đổi màu icon */
.no-results p { font-size: 16px; color: #555; }

.duoclieu-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}
.duoclieu-item {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    padding: 15px;
    display: flex;
    align-items: flex-start;
    border-left: 5px solid #28a745; 
}
.item-img {
    flex-shrink: 0;
    width: 100px;
    height: 100px;
    margin-right: 15px;
    overflow: hidden;
    border-radius: 4px;
    border: 1px solid #eee;
}
.item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.item-body {
    flex-grow: 1;
}
.item-name {
    font-size: 18px;
    color: #28a745;
    margin-top: 0;
    margin-bottom: 5px;
}
.btn-detail {
    display: inline-block;
    margin-top: 10px;
    color: #28a745;
    text-decoration: none;
    font-weight: 600;
}
</style>