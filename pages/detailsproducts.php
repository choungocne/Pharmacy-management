<?php
// ================== 1. THIẾT LẬP KẾT NỐI DATABASE ==================
require_once dirname(__DIR__) . '/db.php';

$pdo = pdo(); // Sử dụng hàm pdo() từ db.php

// LẤY MÃ SẢN PHẨM TỪ THAM SỐ URL (?masp=X)
$masp_can_tim = isset($_GET['masp']) ? (int)$_GET['masp'] : 1;

// Giả định $base_url được định nghĩa để xử lý ảnh
$BASE_URL_FOR_IMAGES = "/pharmacy-management"; // Đường dẫn gốc chứa ảnh, khớp với products.php

// ================== 2. TRUY VẤN DỮ LIỆU SẢN PHẨM ==================
$sql = "SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam, sp.hinhsp,
               sp.congdung, sp.xuatxu, sp.cachdung, sp.requires_rx,
               sp.quycach, sp.dangbaoche,
               dm.tendm, dm.madm,
               dv.tendv AS donvi_tinh
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON sp.madm = dm.madm
        LEFT JOIN donvitinh dv ON sp.madv = dv.madv
        WHERE sp.masp = :masp
        LIMIT 1";


$st = $pdo->prepare($sql);
$st->bindValue(':masp', $masp_can_tim, PDO::PARAM_INT);
$st->execute();
$product_data = $st->fetch();

if (!$product_data) {
    die("Sản phẩm không tồn tại.");
}

// Lấy danh sách ảnh phụ từ cột images (JSON)
$images = [];
if (!empty($product_data['hinhsp'])) {
    $images[] = ['url' => $product_data['hinhsp'], 'caption' => $product_data['tensp']];
}
if (!empty($product_data['images'])) {
    $json_images = json_decode($product_data['images'], true);
    if (is_array($json_images)) {
        foreach ($json_images as $img) {
            $images[] = ['url' => $img, 'caption' => $product_data['tensp']];
        }
    }
}

// ================== 3. GÁN DỮ LIỆU & XỬ LÝ FORMAT ==================
$tensp = htmlspecialchars($product_data['tensp']);
$giaban = number_format((float)$product_data['giaban'], 0, ',', '.') . '₫';
$giagiam = number_format((float)$product_data['giagiam'], 0, ',', '.') . '₫';

// Xử lý đường dẫn ảnh chính: Thêm BASE_URL
$hinhsp_main = $BASE_URL_FOR_IMAGES . htmlspecialchars($product_data['hinhsp'] ?? '/uploads/sp/placeholder.jpg');
$donvitinh=htmlspecialchars($product_data['donvitinh']??'Đang cập nhật');
$thuonghieu = htmlspecialchars($product_data['thuonghieu'] ?? 'Đang cập nhật');
$nhasanxuat = htmlspecialchars($product_data['nhasanxuat'] ?? 'Đang cập nhật');
$xuatxu = htmlspecialchars($product_data['xuatxu'] ?? 'Đang cập nhật');
$dangbaoche = htmlspecialchars($product_data['dangbaoche'] ?? 'Đang cập nhật');
$quycach = htmlspecialchars($product_data['quycach'] ?? 'Đang cập nhật');
$so_dksp = htmlspecialchars($product_data['so_dksp'] ?? 'Đang cập nhật');

// Xử lý các trường mô tả
$congdung = nl2br(htmlspecialchars($product_data['congdung'] ?? 'Đang cập nhật'));
$cachdung = nl2br(htmlspecialchars($product_data['cachdung'] ?? 'Đang cập nhật'));
$chidinh = nl2br(htmlspecialchars($product_data['chidinh'] ?? 'Đang cập nhật'));
$thanhphan = nl2br(htmlspecialchars($product_data['thanhphan'] ?? 'Đang cập nhật'));

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tensp; ?> - Thông tin Sản phẩm</title>
    <style>
        /* CSS cho bố cục tổng thể */
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .product-container { max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); display: flex; flex-wrap: wrap; }
        .product-gallery { flex: 1; min-width: 300px; padding-right: 20px; }
        .product-details { flex: 2; min-width: 400px; padding-left: 20px; border-left: 1px solid #eee; }
        .product-description { width: 100%; margin-top: 30px; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        .description-section { margin-bottom: 20px; }
        .description-section h3 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-bottom: 10px; font-size: 20px; }
        .description-section p { line-height: 1.6; color: #555; }
        
        /* Bố cục cũ */
        .main-image-wrapper { cursor: pointer; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 10px; display: flex; justify-content: center; align-items: center; }
        .main-image { max-width: 100%; height: auto; display: block; }
        .thumbnail-gallery { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
        .thumbnail { width: 70px; height: 70px; object-fit: contain; cursor: pointer; border: 1px solid #ddd; border-radius: 4px; transition: border-color 0.2s; }
        .thumbnail:hover, .thumbnail.active { border-color: #007bff; }
        .product-header h1 { font-size: 24px; color: #333; margin-top: 0; }
        .price { font-size: 30px; color: #dc3545; font-weight: bold; margin: 15px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table th, .info-table td { padding: 8px 0; text-align: left; border-bottom: 1px dotted #eee; }
        .info-table th { font-weight: normal; color: #666; width: 40%; }
        .info-table td { font-weight: bold; color: #333; }
        .badge-official { display: inline-block; background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-right: 10px; }
        .purchase-options { display: flex; align-items: center; gap: 10px; margin-top: 20px; }
        .quantity-control { display: flex; border: 1px solid #ccc; border-radius: 4px; }
        .quantity-control button { background: #fff; border: none; padding: 5px 10px; cursor: pointer; font-size: 18px; }
        .quantity-control input { width: 40px; text-align: center; border: none; border-left: 1px solid #ccc; border-right: 1px solid #ccc; font-size: 16px; }
        .btn-buy, .btn-pharmacy { padding: 10px 20px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn-buy { background-color: #007bff; color: white; }
        .btn-pharmacy { background-color: #f8f9fa; color: #007bff; border: 1px solid #007bff; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.9); padding-top: 60px; }
        .modal-content { margin: auto; display: block; width: 80%; max-width: 700px; position: relative; }
        .modal-content img { width: 100%; height: auto; border-radius: 8px; }
        .close { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; transition: 0.3s; cursor: pointer; }
        .close:hover, .close:focus { color: #bbb; text-decoration: none; cursor: pointer; }
        .model-switcher { display: flex; gap: 20px; margin-bottom: 20px; }
        .model-label { font-size: 18px; font-weight: bold; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    </style>
</head>
<body>
    <div class="product-container">
        <div class="product-gallery">
            <div class="model-switcher">
                <span class="model-label">Mẫu cũ</span>
                <span class="model-label">Mẫu mới</span>
            </div>

            <div class="main-image-wrapper" id="mainImageWrapper">
                <img id="mainImage" class="main-image" src="<?php echo $hinhsp_main; ?>" alt="<?php echo $tensp; ?>">
            </div>

            <div class="thumbnail-gallery">
                <?php 
                // Hiển thị tất cả ảnh liên quan
                if (empty($images)) {
                    // Mặc định nếu không có ảnh
                    echo '<img class="thumbnail active" src="' . $hinhsp_main . '" alt="Ảnh chính" data-full-src="' . $hinhsp_main . '">';
                    // Giả lập ảnh chi tiết
                    echo '<img class="thumbnail" src="' . $BASE_URL_FOR_IMAGES . '/assets/img/placeholder_70.jpg" alt="Ảnh thông tin" data-full-src="' . $BASE_URL_FOR_IMAGES . '/assets/img/placeholder_700.jpg">'; 
                } else {
                    foreach ($images as $index => $img) {
                        $img_url = $BASE_URL_FOR_IMAGES . htmlspecialchars($img['url']);
                        $isActive = ($img_url == $hinhsp_main) ? 'active' : '';
                        echo '<img class="thumbnail ' . $isActive . '" src="' . $img_url . '" alt="' . htmlspecialchars($img['caption']) . '" data-full-src="' . $img_url . '">';
                    }
                }
                ?>
            </div>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">Mẫu mã sản phẩm có thể thay đổi theo lô hàng</p>
        </div>

        <div class="product-details">
            <div class="product-header">
                <span class="badge-official">CHÍNH HÃNG</span>
                <span>Thương hiệu: <b><?php echo $thuonghieu; ?></b></span>
                <h1><?php echo $tensp; ?></h1>
                <p>000<?php echo $masp_can_tim; ?> • <span style="color: gold;">★</span> 4.9 • 140 đánh giá • 1515 bình luận</p>
            </div>

            <div class="price"><b><?php echo $giaban; ?></b> / Hộp</div>

            <table class="info-table">
                <tr><th>Tên chính hãng</th><td><?php echo $thuonghieu; ?></td></tr>
                <tr><th>Đơn vị tính</th><td><?php echo $donvitinh; ?></td></tr>
                <tr><th>Danh mục</th><td><?php echo htmlspecialchars($product_data['danhmuc'] ?? 'Khác'); ?></td></tr>
                <tr><th>Số đăng ký</th><td><?php echo $so_dksp; ?></td></tr>
                <tr><th>Dạng bào chế</th><td><?php echo $dangbaoche; ?></td></tr>
                <tr><th>Quy cách</th><td><?php echo $quycach; ?></td></tr>
                <tr><th>Xuất xứ thương hiệu</th><td><?php echo $xuatxu; ?></td></tr>
                <tr><th>Nhà sản xuất</th><td><?php echo $nhasanxuat; ?></td></tr>
                <tr><th>Nước sản xuất</th><td><?php echo $xuatxu; ?></td></tr>
                <tr><th>Thành phần chính</th><td><?php echo $thanhphan; ?></td></tr>
            </table>

            <div class="purchase-options">
                <span>Chọn số lượng</span>
                <div class="quantity-control">
                    <button id="qtyDecrease">-</button>
                    <input type="text" id="qtyInput" value="1" min="1" readonly>
                    <button id="qtyIncrease">+</button>
                </div>
                <button class="btn-buy">Chọn mua</button>
                <button class="btn-pharmacy">Tìm nhà thuốc</button>
            </div>
            <p style="font-size: 14px; color: #dc3545; margin-top: 15px;">
                <span style="font-weight: bold;">Sản phẩm đang được chú ý,</span> có 7 người thêm vào giỏ hàng & 18 người đang xem
            </p>
            
            <hr>
            <div style="display: flex; justify-content: space-between; margin-top: 15px; font-size: 14px;">
                <p style="color: #007bff;"><span style="font-size: 18px;">🔄</span> Đổi trả trong 30 ngày kể từ ngày mua hàng</p>
                <p style="color: #007bff;"><span style="font-size: 18px;">✅</span> Miễn phí 100% đổi thuốc</p>
                <p style="color: #007bff;"><span style="font-size: 18px;">🚚</span> Miễn phí vận chuyển theo chính sách giao hàng</p>
            </div>
        </div>

        <div class="product-description">
            <div class="description-section">
                <h3>THÔNG TIN SẢN PHẨM / CÔNG DỤNG</h3>
                <p><?php echo $congdung; ?></p>
            </div>
            
            <?php if (!empty($product_data['chidinh'])): ?>
            <div class="description-section">
                <h3>CHỈ ĐỊNH (Khi nào dùng)</h3>
                <p><?php echo $chidinh; ?></p>
            </div>
            <?php endif; ?>

            <div class="description-section">
                <h3>CÁCH DÙNG - LIỀU DÙNG</h3>
                <p><?php echo $cachdung; ?></p>
            </div>

            <div class="description-section">
                <h3>LƯU Ý</h3>
                <p>
                    Sản phẩm này là <b><?php echo ($product_data['requires_rx'] ? 'Thuốc Kê Đơn (Requires Rx)' : 'Thuốc Không Kê Đơn/Thực Phẩm Chức Năng'); ?></b>. 
                    Đọc kỹ hướng dẫn sử dụng trước khi dùng. Nếu cần thêm thông tin, xin hỏi ý kiến bác sĩ hoặc dược sĩ.
                </p>
            </div>
        </div>
    </div>

    <div id="productModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage" src="<?php echo $hinhsp_main; ?>" alt="<?php echo $tensp; ?> - Chi tiết">

        <div class="thumbnail-gallery" style="width: 80%; max-width: 700px; margin: 10px auto; justify-content: center;">
            <?php 
            // Hiển thị tất cả ảnh liên quan trong Modal
            foreach ($images as $img) {
                $img_url = $BASE_URL_FOR_IMAGES . htmlspecialchars($img['url']);
                echo '<img class="thumbnail" onclick="changeModalImage(this)" src="' . $img_url . '" alt="' . htmlspecialchars($img['caption']) . '" data-modal-src="' . $img_url . '">';
            }
            ?>
        </div>
    </div>

    <script>
        var modal = document.getElementById("productModal");
        var btn = document.getElementById("mainImageWrapper"); 
        var span = document.getElementsByClassName("close")[0];
        var modalImage = document.getElementById("modalImage");
        var mainImage = document.getElementById("mainImage");

        // Khi click vào ảnh chính, mở modal
        btn.onclick = function() {
            modal.style.display = "block";
            modalImage.src = mainImage.src; 
        }

        // Đóng modal khi click vào nút 'x'
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Đóng modal khi click ra ngoài
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Thay đổi ảnh chính khi click vào thumbnail
        document.querySelectorAll('.product-gallery .thumbnail-gallery .thumbnail').forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const newSrc = this.getAttribute('data-full-src');
                if (newSrc) {
                    mainImage.src = newSrc;
                }
                document.querySelectorAll('.product-gallery .thumbnail-gallery .thumbnail').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Thay đổi ảnh trong modal
        function changeModalImage(thumbnail) {
            const newSrc = thumbnail.getAttribute('data-modal-src');
            if (newSrc) {
                modalImage.src = newSrc;
            }
        }

        // Tăng giảm số lượng
        document.getElementById('qtyIncrease').onclick = function() {
            var input = document.getElementById('qtyInput');
            input.value = parseInt(input.value) + 1;
        }

        document.getElementById('qtyDecrease').onclick = function() {
            var input = document.getElementById('qtyInput');
            var currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        }
    </script>
</body>
</html>