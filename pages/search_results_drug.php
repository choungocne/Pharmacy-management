<?php
// =================================================================
// TỆP: search_results_drug.php
// CHỨC NĂNG: Hiển thị kết quả tìm kiếm SẢN PHẨM THUỐC
// =================================================================

// Đảm bảo hàm pdo() và money_vn() có sẵn
require_once 'db.php'; 

// 1. Lấy từ khóa tìm kiếm
$search_query = $_GET['q'] ?? '';
$search_query = trim($search_query);

// 2. Chuẩn bị truy vấn CSDL
$products = [];
if (!empty($search_query)) {
    // Chỉ tìm kiếm các sản phẩm thuộc danh mục cấp 1 là 'Thuốc' (madm=2) 
    // HOẶC 'Thực phẩm chức năng' (madm=1).
    // Ta dùng IN để lấy madm của các sản phẩm thuộc TPCN (1) hoặc Thuốc (2).
    
    $sql = "SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam, sp.hinhsp, sp.congdung, sp.requires_rx, dm.tendm
            FROM sanpham sp
            JOIN danhmuc dm ON sp.madm = dm.madm
            WHERE sp.tensp LIKE :query 
            AND dm.parent_id IN (1, 2) -- Lọc theo TPCN (madm=1) và Thuốc (madm=2)
            AND sp.trangthai = 1 
            LIMIT 20";

    try {
        $pdo = pdo(); // Lấy kết nối PDO
        $stmt = $pdo->prepare($sql);
        $search_term = '%' . $search_query . '%';
        $stmt->bindParam(':query', $search_term);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ghi log lỗi
        // error_log("Lỗi truy vấn tìm kiếm thuốc: " . $e->getMessage());
    }
}
?>

<div class="search-results-page">
    <div class="container">
        <h2 class="search-title">
            Kết quả tìm kiếm sản phẩm cho: 
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
                        $product_image = $product['hinhsp'] ?? 'default.png';
                        // Định dạng lại URL ảnh vì ảnh trong schema là /static/img/product/
                        $img_url = (strpos($product_image, 'static/img') !== false) 
                                   ? $base_url . $product_image 
                                   : $base_url . '/static/img/product/' . $product_image;
                    ?>
                    <div class="product-item">
                        <div class="product-image">
                            <?php if ($discount_percent > 0): ?>
                                <span class="discount-badge">-<?= $discount_percent ?>%</span>
                            <?php endif; ?>
                            <a href="<?= $base_url ?>/base.php?page=product_detail&masp=<?= $product['masp'] ?>">
                                <img src="<?= htmlspecialchars($img_url) ?>" 
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
                            <p class="product-category"><?= htmlspecialchars($product['tendm']) ?></p>
                            <div class="product-price">
                                <span class="current-price"><?= money_vn($display_price) ?>đ</span>
                                <?php if ($product['giagiam'] > 0 && $product['giagiam'] < $product['giaban']): ?>
                                    <span class="old-price"><?= money_vn($product['giaban']) ?>đ</span>
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
                <p>Xin lỗi, chúng tôi không tìm thấy sản phẩm thuốc/thực phẩm chức năng nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
                <p>Vui lòng thử lại với từ khóa khác.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-results-page { padding: 30px 0; min-height: 500px; background-color: #f9f9f9; }
.container { width: 90%; max-width: 1400px; margin: auto; }
.search-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 20px; }
.query-term { color: #004aad; }
.divider { border: 0; border-top: 1px solid #ccc; margin: 20px 0 40px; }
.no-results { text-align: center; padding: 50px; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.no-results i { font-size: 60px; color: #004aad; margin-bottom: 20px; }
.no-results p { font-size: 16px; color: #555; }
.product-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px; }
.product-item { background-color: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; overflow: hidden; }
.product-item:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
.product-image { height: 200px; display: flex; align-items: center; justify-content: center; position: relative; padding: 15px; }
.product-image img { max-width: 100%; max-height: 100%; object-fit: contain; }
.product-info { padding: 15px; text-align: center; }
.product-name { font-size: 16px; font-weight: 600; margin-top: 0; margin-bottom: 5px; height: 40px; overflow: hidden; }
.product-category { font-size: 14px; color: #007bff; margin-bottom: 10px; }
.product-name a:hover { color: #004aad; }
.product-price { margin-bottom: 15px; display: flex; justify-content: center; align-items: baseline; gap: 10px; }
.current-price { font-size: 18px; font-weight: 700; color: #d9534f; }
.old-price { font-size: 14px; color: #999; text-decoration: line-through; }
.add-to-cart-btn { background-color: #004aad; color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer; font-size: 14px; font-weight: 500; transition: background-color 0.3s; }
.add-to-cart-btn:hover { background-color: #003a80; }
.rx-badge { position: absolute; bottom: 10px; right: 10px; background-color: #ffdd00; color: #333; padding: 4px 8px; font-size: 12px; border-radius: 4px; font-weight: 600; }
.discount-badge { position: absolute; top: 10px; left: 10px; background-color: #d9534f; color: white; padding: 4px 8px; font-size: 12px; border-radius: 4px; font-weight: 600; }
</style>