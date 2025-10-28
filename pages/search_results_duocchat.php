<?php
// =================================================================
// TỆP: search_results_duocchat.php
// CHỨC NĂNG: Hiển thị kết quả tra cứu DƯỢC CHẤT
// =================================================================

require_once 'db.php'; 
global $base_url; 

// 1. Lấy từ khóa tìm kiếm
$search_query = $_GET['q'] ?? '';
$search_query = trim($search_query);

// 2. Chuẩn bị truy vấn CSDL
$results = [];
if (!empty($search_query)) {
    // Tìm kiếm trong bảng duocchat theo tendc, tenkhac và tacdung
    $sql = "SELECT madc, tendc, tenkhac, tacdung, phan_loai, anh_cau_truc 
            FROM duocchat 
            WHERE (tendc LIKE :query OR tenkhac LIKE :query OR tacdung LIKE :query)
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
        // error_log("Lỗi truy vấn dược chất: " . $e->getMessage());
    }
}
?>

<div class="search-results-page">
    <div class="container">
        <h2 class="search-title">
            Kết quả tra cứu Dược chất cho: 
            <span class="query-term">"<?= htmlspecialchars($search_query) ?>"</span> 
            (Tìm thấy **<?= count($results) ?>** dược chất)
        </h2>

        <hr class="divider">

        <?php if (!empty($results)): ?>
            <div class="result-list duocchat-list">
                <?php foreach ($results as $item): ?>
                    <div class="duocchat-item">
                        <div class="item-header">
                            <h3 class="item-name"><?= htmlspecialchars($item['tendc']) ?></h3>
                            <span class="phan-loai"><?= htmlspecialchars($item['phan_loai'] ?? 'Không rõ') ?></span>
                        </div>
                        <div class="item-body">
                            <?php 
                            $img_url = $item['anh_cau_truc'] ? $base_url . '/static/img/duocchat/' . htmlspecialchars($item['anh_cau_truc']) : '';
                            if (!empty($img_url)): ?>
                                <img src="<?= $img_url ?>" 
                                     alt="Cấu trúc <?= htmlspecialchars($item['tendc']) ?>" class="cau-truc-img">
                            <?php endif; ?>
                            <p><strong>Tên khác:</strong> <?= htmlspecialchars($item['tenkhac'] ?? 'N/A') ?></p>
                            <p><strong>Tác dụng:</strong> <?= htmlspecialchars(substr($item['tacdung'], 0, 300)) ?>...</p>
                            
                            <a href="<?= $base_url ?>/base.php?page=duocchat_detail&madc=<?= $item['madc'] ?>" class="btn-detail">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-microscope"></i>
                <p>Xin lỗi, không tìm thấy Dược chất nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* CSS Tùy chỉnh cho Dược Chất (Nên đưa vào file CSS riêng) */
.search-results-page { padding: 30px 0; min-height: 500px; background-color: #f9f9f9; }
.container { width: 90%; max-width: 1400px; margin: auto; }
.search-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 20px; }
.query-term { color: #004aad; }
.divider { border: 0; border-top: 1px solid #ccc; margin: 20px 0 40px; }
.no-results { text-align: center; padding: 50px; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.no-results i { font-size: 60px; color: #004aad; margin-bottom: 20px; }
.no-results p { font-size: 16px; color: #555; }

.duocchat-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.duocchat-item {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    padding: 20px;
    border-left: 5px solid #007bff;
}
.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.item-name {
    font-size: 18px;
    color: #007bff;
    margin: 0;
}
.phan-loai {
    background-color: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
    color: #495057;
}
.cau-truc-img {
    max-width: 100%;
    height: auto;
    margin: 10px 0;
    border: 1px dashed #ddd;
    padding: 10px;
}
.btn-detail {
    display: inline-block;
    margin-top: 15px;
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}
</style>