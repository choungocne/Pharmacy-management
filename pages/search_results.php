<?php
// Bao gồm header.php để thiết lập kết nối CSDL ($pdo), $base_url, và hiển thị phần đầu trang
// Lưu ý: Nếu trang của bạn sử dụng cấu trúc base.php, bạn cần điều chỉnh cho phù hợp.
// Ở đây tôi giả định bạn sẽ include header.php trực tiếp.

include 'db.php'; 

// 1. Lấy từ khóa tìm kiếm
$search_query = $_GET['q'] ?? '';
$search_query = trim($search_query); // Loại bỏ khoảng trắng thừa

// 2. Chuẩn bị truy vấn CSDL
$products = [];
if (!empty($search_query)) {
    // Tìm kiếm trong bảng sanpham theo tensp (tên sản phẩm)
    // Sẽ tìm các sản phẩm có tên chứa từ khóa tìm kiếm (dùng LIKE %...%)
    $sql = "SELECT masp, tensp, giaban, giagiam, hinhsp, congdung, requires_rx 
            FROM sanpham 
            WHERE tensp LIKE ? AND trangthai = 1 
            LIMIT 20"; // Giới hạn 20 kết quả

    try {
        $stmt = $pdo->prepare($sql);
        // Sử dụng %...% để tìm kiếm một phần
        $search_term = '%' . $search_query . '%';
        $stmt->execute([$search_term]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Xử lý lỗi CSDL (có thể ghi log thay vì hiển thị trực tiếp)
        // echo "Lỗi truy vấn: " . $e->getMessage();
    }
}
?>

<div class="search-results-page">
    <div class="container">
        <h2 class="search-title">
            Kết quả tìm kiếm cho: 
            <span class="query-term">"<?= htmlspecialchars($search_query) ?>"</span> 
            (Tìm thấy **<?= count($products) ?>** sản phẩm)
        </h2>

        <hr class="divider">

        <?php if (!empty($products)): ?>
            <div class="product-list">
                <?php foreach ($products as $product): ?>
                    <?php
                        // Tính giá hiển thị
                        $display_price = $product['giaban'];
                        $discount_percent = 0;
                        if ($product['giagiam'] > 0 && $product['giagiam'] < $product['giaban']) {
                            $display_price = $product['giagiam'];
                            $discount_percent = round((($product['giaban'] - $product['giagiam']) / $product['giaban']) * 100);
                        }
                    ?>
                    <div class="product-item">
                        <div class="product-image">
                            <?php if ($discount_percent > 0): ?>
                                <span class="discount-badge">-<?= $discount_percent ?>%</span>
                            <?php endif; ?>
                            <a href="<?= $base_url ?>/base.php?page=product_detail&masp=<?= $product['masp'] ?>">
                                <img src="<?= $base_url ?>/static/img/<?= htmlspecialchars($product['hinhsp'] ?? 'default.png') ?>" 
                                     alt="<?= htmlspecialchars($product['tensp']) ?>">
                            </a>
                            <?php if ($product['requires_rx']): ?>
                                <span class="rx-badge">Cần kê đơn</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">
                                <a href="<?= $base_url ?>/base.php?page=product_detail&masp=<?= $product['masp'] ?>">
                                    <?= htmlspecialchars($product['tensp']) ?>
                                </a>
                            </h3>
                            <p class="product-desc"><?= htmlspecialchars(substr($product['congdung'] ?? '', 0, 80)) ?>...</p>
                            <div class="product-price">
                                <span class="current-price"><?= number_format($display_price, 0, ',', '.') ?>đ</span>
                                <?php if ($product['giagiam'] > 0 && $product['giagiam'] < $product['giaban']): ?>
                                    <span class="old-price"><?= number_format($product['giaban'], 0, ',', '.') ?>đ</span>
                                <?php endif; ?>
                            </div>
                            <button class="add-to-cart-btn"><i class="fas fa-shopping-cart"></i> Thêm vào giỏ</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="far fa-frown"></i>
                <p>Xin lỗi, chúng tôi không tìm thấy sản phẩm nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
                <p>Vui lòng thử lại với từ khóa khác.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* CSS cho trang kết quả tìm kiếm */
.search-results-page {
    padding: 30px 0;
    min-height: 500px;
    background-color: #f9f9f9;
}
.container {
    width: 90%; 
    max-width: 1400px; 
    margin: auto;
}
.search-title {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}
.query-term {
    color: #004aad; /* Màu chủ đạo */
}
.divider {
    border: 0;
    border-top: 1px solid #ccc;
    margin: 20px 0 40px;
}
.no-results {
    text-align: center;
    padding: 50px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.no-results i {
    font-size: 60px;
    color: #004aad;
    margin-bottom: 20px;
}
.no-results p {
    font-size: 16px;
    color: #555;
}

/* Danh sách sản phẩm */
.product-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
}
.product-item {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    overflow: hidden;
}
.product-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}
.product-image {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 15px;
}
.product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.product-info {
    padding: 15px;
    text-align: center;
}
.product-name {
    font-size: 16px;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 10px;
    height: 40px; /* Cố định chiều cao */
    overflow: hidden;
}
.product-name a:hover {
    color: #004aad;
}
.product-desc {
    font-size: 13px;
    color: #777;
    margin-bottom: 15px;
    height: 30px; /* Cố định chiều cao */
    overflow: hidden;
}
.product-price {
    margin-bottom: 15px;
    display: flex;
    justify-content: center;
    align-items: baseline;
    gap: 10px;
}
.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #d9534f; /* Màu đỏ nổi bật cho giá */
}
.old-price {
    font-size: 14px;
    color: #999;
    text-decoration: line-through;
}
.add-to-cart-btn {
    background-color: #004aad; /* Màu chủ đạo */
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.3s;
}
.add-to-cart-btn:hover {
    background-color: #003a80;
}

/* Badge cần kê đơn và giảm giá */
.rx-badge {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background-color: #ffdd00; /* Màu vàng */
    color: #333;
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
    font-weight: 600;
}
.discount-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: #d9534f; /* Màu đỏ */
    color: white;
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
    font-weight: 600;
}
</style>

</body>
</html>