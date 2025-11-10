<?php
// =================================================================
// TỆP: search_results_duocchat.php
// CHỨC NĂNG: Xử lý tìm kiếm và hiển thị kết quả DUỢC CHẤT
// =================================================================

// Đảm bảo hàm pdo() có sẵn
require_once 'db.php'; 

// Lấy base URL
if (!isset($base_url)) $base_url = '/Pharmacy-management'; 

// 1. Lấy từ khóa tìm kiếm
$search_query = $_GET['q'] ?? '';
$search_query = trim($search_query);

// 2. Thực hiện truy vấn Dược chất
$results = []; // Khởi tạo biến $results là mảng rỗng
if (!empty($search_query)) {
    $sql = "
    SELECT 
        madc, tendc, tenkhac, tacdung, phan_loai, anh_cau_truc
    FROM duocchat 
    WHERE (
        tendc LIKE :query OR 
        tenkhac LIKE :query 
        
    ) 
    LIMIT 20
";

    try {
        $pdo = pdo();
        $stmt = $pdo->prepare($sql);
        $search_term = '%' . $search_query . '%';
        $stmt->bindParam(':query', $search_term);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $results = []; 
    }
} 

$total_found = count($results);
?>
<div class="duocchat-search-content">
    <div class="search-info">
        <h2 class="search-title">
            Kết quả tra cứu Dược Chất cho: 
            <span class="query-term">"<?= htmlspecialchars($search_query) ?>"</span> 
            (Tìm thấy **<?= $total_found ?>** dược chất)
        </h2>
    </div>
    
    <?php if ($total_found > 0): ?>
        <div class="product-list duocchat-list">
            <?php foreach ($results as $item): ?>
                <?php 
                    $img_url = htmlspecialchars($item['anh_cau_truc'] ?? $base_url . '/static/img/placeholder.jpg'); 
                    $phan_loai = htmlspecialchars($item['phan_loai'] ?? 'N/A');
                ?>
                <div class="product-item duocchat-item"> 
                    <div class="product-image">
                        <a href="<?= $base_url ?>/base.php?page=duocchat_detail&madc=<?= $item['madc'] ?>">
                            <img src="<?= $img_url ?>" 
                                 alt="<?= htmlspecialchars($item['tendc']) ?>"
                                 onerror="this.onerror=null;this.src='<?= $base_url ?>/static/img/placeholder.jpg'">
                        </a>
                        <span class="rx-badge" style="background-color: #007bff; color: white;">Dược Chất</span>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="<?= $base_url ?>/base.php?page=duocchat_detail&madc=<?= $item['madc'] ?>">
                                <?= htmlspecialchars($item['tendc']) ?>
                            </a>
                        </h3>
                        <p class="product-category" style="color: #007bff; font-weight: 600;">Phân loại: <?= $phan_loai ?></p>
                        
                        <div class="product-price" style="flex-direction: column; align-items: flex-start;">
                            <span class="current-price" style="font-size: 15px; color: #555;">Tác dụng chính:</span>
                            <span class="old-price" style="text-decoration: none; font-size: 14px; color: #777; height: 36px; overflow: hidden; line-height: 1.3;">
                                <?= htmlspecialchars(substr($item['tacdung'] ?? 'Đang cập nhật...', 0, 70)) ?>...
                            </span>
                        </div>
                        
                        <a href="<?= $base_url ?>/base.php?page=duocchat_detail&madc=<?= $item['madc'] ?>" class="add-to-cart-btn" style="text-align: center; background-color: #2ecc71;">
                            <i class="fas fa-eye"></i> Xem Chi Tiết
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results" style="padding: 50px; text-align: center; background: white; border-radius: 8px;">
            <i class="fas fa-microscope" style="font-size: 40px; color: #007bff; margin-bottom: 15px;"></i>
            <p>Xin lỗi, không tìm thấy Dược chất nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
        </div>
    <?php endif; ?>
</div>