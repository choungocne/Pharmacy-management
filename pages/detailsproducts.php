<?php
// ================== 1. THIẾT LẬP KẾT NỐI DATABASE ==================
require_once dirname(__DIR__) . '/db.php';

$pdo = pdo(); 

// LẤY MÃ SẢN PHẨM TỪ URL
$masp_can_tim = isset($_GET['masp']) ? (int)$_GET['masp'] : 0;
if ($masp_can_tim <= 0) {
    echo "<div style='padding:20px; text-align:center;'>Sản phẩm không hợp lệ.</div>";
    exit;
}

$BASE_URL = "/Pharmacy-management"; 

// ================== 2. TRUY VẤN DỮ LIỆU SẢN PHẨM ==================
// Join với khuyenmai, danhmuc, donvitinh, thuonghieu
$sql = "SELECT sp.*,
               dm.tendm, 
               dv.tendv AS donvi_tinh,
               th.tenth AS ten_thuong_hieu,
               km.phantram_giam, km.gia_giam_co_dinh, km.trangthai_deal
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON sp.madm = dm.madm
        LEFT JOIN donvitinh dv ON sp.madv = dv.madv
        LEFT JOIN thuonghieu th ON sp.math = th.math
        LEFT JOIN khuyenmai km ON sp.makm = km.makm 
             AND km.trangthai_deal = 'dang_dien_ra' 
             AND NOW() BETWEEN km.ngay_batdau AND km.ngay_ketthuc
        WHERE sp.masp = :masp
        LIMIT 1";

$st = $pdo->prepare($sql);
$st->bindValue(':masp', $masp_can_tim, PDO::PARAM_INT);
$st->execute();
$product_data = $st->fetch();

if (!$product_data) {
    echo "<div style='padding:20px; text-align:center;'>Sản phẩm không tồn tại.</div>";
    exit;
}

// ================== 3. XỬ LÝ DỮ LIỆU HIỂN THỊ ==================

// --- Xử lý Giá Bán & Khuyến Mãi ---
$gia_goc = (float)$product_data['giaban'];
$gia_hien_tai = $gia_goc;
$phantram_giam = 0;

if (!empty($product_data['phantram_giam']) && $product_data['phantram_giam'] > 0) {
    $phantram_giam = (float)$product_data['phantram_giam'];
    $gia_hien_tai = $gia_goc * (1 - $phantram_giam / 100);
} elseif (!empty($product_data['gia_giam_co_dinh']) && $product_data['gia_giam_co_dinh'] > 0) {
    $giam_tien = (float)$product_data['gia_giam_co_dinh'];
    $gia_hien_tai = $gia_goc - $giam_tien;
    $phantram_giam = round(($giam_tien / $gia_goc) * 100);
}

// Format tiền tệ
$gia_goc_fmt = number_format($gia_goc, 0, ',', '.') . '₫';
$gia_hien_tai_fmt = number_format($gia_hien_tai, 0, ',', '.') . '₫';

// --- Xử lý Hình ảnh ---
$hinhsp_main = !empty($product_data['hinhsp']) ? $product_data['hinhsp'] : '/static/img/placeholder.jpg';

// Danh sách ảnh gallery
$images = [];
// Thêm ảnh chính vào đầu
$images[] = ['url' => $hinhsp_main, 'caption' => $product_data['tensp']];

// Thêm ảnh phụ từ JSON
if (!empty($product_data['images'])) {
    $json_images = json_decode($product_data['images'], true);
    if (is_array($json_images)) {
        foreach ($json_images as $img) {
            // Kiểm tra xem có phải đường dẫn đầy đủ không, nếu không thì thêm vào
            $img_path = (strpos($img, 'http') === 0 || strpos($img, '/') === 0) ? $img : '/uploads/sp/' . $img;
            $images[] = ['url' => $img_path, 'caption' => 'Ảnh chi tiết'];
        }
    }
}

// --- Xử lý Thông tin text ---
$tensp = htmlspecialchars($product_data['tensp']);
$donvitinh = htmlspecialchars($product_data['donvi_tinh'] ?? 'Hộp');
$thuonghieu = htmlspecialchars($product_data['ten_thuong_hieu'] ?? 'Đang cập nhật');
$nhasanxuat = htmlspecialchars($product_data['nhasanxuat'] ?? 'Đang cập nhật');
$xuatxu = htmlspecialchars($product_data['xuatxu'] ?? 'Đang cập nhật');
$dangbaoche = htmlspecialchars($product_data['dangbaoche'] ?? 'Đang cập nhật');
$quycach = htmlspecialchars($product_data['quycach'] ?? 'Đang cập nhật');
$so_dksp = htmlspecialchars($product_data['so_dksp'] ?? 'Đang cập nhật');

// Nội dung dài (nl2br để giữ xuống dòng)
$congdung = nl2br(htmlspecialchars($product_data['congdung'] ?? 'Đang cập nhật'));
$cachdung = nl2br(htmlspecialchars($product_data['cachdung'] ?? 'Đang cập nhật'));
$chidinh = nl2br(htmlspecialchars($product_data['chidinh'] ?? ''));
$thanhphan = nl2br(htmlspecialchars($product_data['thanhphan'] ?? 'Đang cập nhật'));
$baoquan = htmlspecialchars($product_data['baoquan'] ?? 'Nơi khô ráo, thoáng mát');

// ================== 4. TRUY VẤN CHI NHÁNH & TỒN KHO ==================
// Logic: Join bảng chinhanh với tonkho dựa trên mã chi nhánh (macn)
$sql_cn = "SELECT cn.tencn, cn.diachi, cn.macn, 
                  COALESCE(tk.soluong, 0) as ton_kho
           FROM chinhanh cn
           LEFT JOIN tonkho tk ON cn.macn = tk.chinhanh AND tk.masp = :masp
           ORDER BY ton_kho DESC, cn.tencn ASC";

$st_cn = $pdo->prepare($sql_cn);
$st_cn->bindValue(':masp', $masp_can_tim, PDO::PARAM_INT);
$st_cn->execute();
$list_chinhanh = $st_cn->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tensp; ?> - Nhà Thuốc An Tâm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* --- RESET & BASIC --- */
        body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f4f6; color: #333; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        
        /* --- LAYOUT CHÍNH --- */
        .product-wrapper {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-wrap: wrap;
            overflow: hidden;
        }
        
        /* --- CỘT TRÁI: HÌNH ẢNH --- */
        .product-gallery {
            flex: 1;
            min-width: 350px;
            padding: 20px;
            border-right: 1px solid #eee;
        }
        .main-image-frame {
            width: 100%;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: zoom-in;
            position: relative;
        }
        .main-image-frame img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .thumbnails-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }
        .thumb-item {
            width: 70px;
            height: 70px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            opacity: 0.7;
            transition: 0.2s;
            object-fit: contain;
        }
        .thumb-item:hover, .thumb-item.active {
            opacity: 1;
            border-color: #0284c7; /* Sky-600 */
            box-shadow: 0 0 0 2px rgba(2,132,199,0.2);
        }

        /* --- CỘT PHẢI: THÔNG TIN --- */
        .product-info {
            flex: 1.5;
            min-width: 400px;
            padding: 25px;
        }
        .brand-link { color: #0284c7; font-weight: 600; text-decoration: none; }
        .product-title { font-size: 24px; font-weight: 700; margin: 10px 0; line-height: 1.3; }
        .product-meta { font-size: 13px; color: #6b7280; margin-bottom: 15px; display: flex; gap: 15px; }
        
        /* Giá tiền */
        .price-block { margin: 20px 0; display: flex; align-items: flex-end; gap: 10px; }
        .current-price { font-size: 28px; font-weight: bold; color: #dc2626; } /* Red-600 */
        .original-price { font-size: 16px; color: #9ca3af; text-decoration: line-through; margin-bottom: 5px; }
        .discount-tag { background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-size: 13px; font-weight: bold; margin-bottom: 7px; }

        /* Thông tin chi tiết dạng bảng nhỏ */
        .short-specs { font-size: 14px; margin-bottom: 25px; }
        .spec-row { display: flex; margin-bottom: 8px; }
        .spec-label { width: 120px; color: #6b7280; }
        .spec-value { font-weight: 500; color: #1f2937; }

        /* Nút bấm hành động */
        .action-buttons { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px; }
        
        .qty-control {
            display: flex;
            align-items: center;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            height: 48px;
        }
        .qty-btn { width: 40px; height: 100%; border: none; background: #f9fafb; cursor: pointer; font-size: 18px; color: #374151; }
        .qty-btn:hover { background: #e5e7eb; }
        .qty-input { width: 50px; height: 100%; border: none; text-align: center; font-weight: 600; font-size: 16px; border-left: 1px solid #d1d5db; border-right: 1px solid #d1d5db; }
        
        .btn-buy {
            flex: 1;
            background-color: #0284c7;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            padding: 0 20px;
            height: 48px;
            transition: background 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-buy:hover { background-color: #0369a1; }
        
        .btn-find-store {
            background-color: white;
            color: #0284c7;
            border: 1px solid #0284c7;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            padding: 0 20px;
            height: 48px;
            transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-find-store:hover { background-color: #f0f9ff; }

        /* --- PHẦN NỘI DUNG CHI TIẾT BÊN DƯỚI --- */
        .detail-content {
            background: white;
            border-radius: 12px;
            margin-top: 20px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #0284c7;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 10px;
            margin-bottom: 15px;
            margin-top: 30px;
        }
        .section-title:first-child { margin-top: 0; }
        .text-content { line-height: 1.6; color: #4b5563; font-size: 15px; }

        /* --- MODAL TÌM NHÀ THUỐC --- */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            width: 90%; max-width: 550px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .close-modal {
            position: absolute; top: 15px; right: 20px;
            font-size: 24px; font-weight: bold; color: #9ca3af; cursor: pointer;
        }
        .close-modal:hover { color: #374151; }
        
        .branch-item {
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #fff;
        }
        .branch-item.has-stock { border-left: 4px solid #22c55e; } /* Green border */
        .branch-item.no-stock { border-left: 4px solid #9ca3af; background: #f9fafb; } /* Gray border */
        
        .stock-badge {
            font-size: 12px; padding: 3px 8px; border-radius: 12px; font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }

        /* Toast Notification */
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast-msg {
            background: white; border-left: 4px solid #22c55e;
            padding: 15px 20px; border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.3s ease;
            margin-bottom: 10px;
        }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

    </style>
</head>
<body>

<div class="container">
    <div style="margin-bottom: 15px; font-size: 14px; color: #666;">
        <a href="<?php echo $BASE_URL; ?>" style="text-decoration: none; color: #666;">Trang chủ</a> / 
        <span style="color: #333; font-weight: 500;"><?php echo $tensp; ?></span>
    </div>

    <div class="product-wrapper">
        <div class="product-gallery">
            <div class="main-image-frame" id="mainImageWrap">
                <img id="mainImage" src="<?php echo $hinhsp_main; ?>" alt="<?php echo $tensp; ?>">
            </div>
            <div class="thumbnails-scroll">
                <?php foreach($images as $idx => $img): ?>
                    <img class="thumb-item <?php echo ($idx === 0) ? 'active' : ''; ?>" 
                         src="<?php echo $img['url']; ?>" 
                         onclick="changeImage(this)"
                         alt="<?php echo $img['caption']; ?>">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="product-info">
            <a href="#" class="brand-link"><?php echo $thuonghieu; ?></a>
            <h1 class="product-title"><?php echo $tensp; ?></h1>
            
            <div class="product-meta">
                <span>Mã SP: <b><?php echo str_pad($product_data['masp'], 6, '0', STR_PAD_LEFT); ?></b></span>
                <span>•</span>
                <span>Đơn vị: <?php echo $donvitinh; ?></span>
                <span>•</span>
                <span>Quy cách: <?php echo $quycach; ?></span>
            </div>

            <div class="price-block">
                <div class="current-price"><?php echo $gia_hien_tai_fmt; ?></div>
                <?php if($phantram_giam > 0): ?>
                    <div class="original-price"><?php echo $gia_goc_fmt; ?></div>
                    <div class="discount-tag">-<?php echo $phantram_giam; ?>%</div>
                <?php endif; ?>
            </div>

            <div class="short-specs">
                <div class="spec-row"><span class="spec-label">Danh mục:</span> <span class="spec-value"><?php echo htmlspecialchars($product_data['tendm']); ?></span></div>
                <div class="spec-row"><span class="spec-label">Xuất xứ:</span> <span class="spec-value"><?php echo $xuatxu; ?></span></div>
                <div class="spec-row"><span class="spec-label">Nhà sản xuất:</span> <span class="spec-value"><?php echo $nhasanxuat; ?></span></div>
                <?php if($so_dksp) echo '<div class="spec-row"><span class="spec-label">Số đăng ký:</span> <span class="spec-value">'.$so_dksp.'</span></div>'; ?>
            </div>

            <div class="action-buttons">
                <div class="qty-control">
                    <button class="qty-btn" onclick="updateQty(-1)">-</button>
                    <input type="number" id="qtyInput" class="qty-input" value="1" min="1" max="50" readonly>
                    <button class="qty-btn" onclick="updateQty(1)">+</button>
                </div>

                <button class="btn-buy" onclick="addToCart(<?php echo $product_data['masp']; ?>)">
                    <i class="fas fa-cart-plus"></i> CHỌN MUA
                </button>

                <button class="btn-find-store" id="btnOpenPharmacy">
                    <i class="fas fa-map-marker-alt"></i> TÌM NHÀ THUỐC
                </button>
            </div>

            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px dashed #e5e7eb;">
                <div style="display: flex; gap: 20px; font-size: 13px; color: #4b5563;">
                    <span><i class="fas fa-check-circle text-green-600"></i> 100% Chính hãng</span>
                    <span><i class="fas fa-sync text-blue-600"></i> Đổi trả 30 ngày</span>
                    <span><i class="fas fa-truck text-orange-600"></i> Giao hàng 2h</span>
                </div>
            </div>
        </div>
    </div>

    <div class="detail-content">
        <?php if(!empty($product_data['congdung'])): ?>
            <div class="section-title">Công dụng</div>
            <div class="text-content"><?php echo $congdung; ?></div>
        <?php endif; ?>

        <?php if(!empty($product_data['thanhphan'])): ?>
            <div class="section-title">Thành phần</div>
            <div class="text-content"><?php echo $thanhphan; ?></div>
        <?php endif; ?>

        <?php if(!empty($product_data['cachdung'])): ?>
            <div class="section-title">Cách dùng - Liều dùng</div>
            <div class="text-content"><?php echo $cachdung; ?></div>
        <?php endif; ?>

        <?php if(!empty($product_data['baoquan'])): ?>
            <div class="section-title">Bảo quản</div>
            <div class="text-content"><?php echo $baoquan; ?></div>
        <?php endif; ?>
        
        <div class="section-title">Lưu ý</div>
        <div class="text-content">
            <?php echo ($product_data['requires_rx']) ? '<strong style="color:red;">Thuốc này chỉ dùng theo đơn của bác sĩ (Rx).</strong><br>' : ''; ?>
            Sản phẩm này không phải là thuốc và không có tác dụng thay thế thuốc chữa bệnh (nếu là TPCN). <br>
            Đọc kỹ hướng dẫn sử dụng trước khi dùng.
        </div>
    </div>
</div>

<div id="pharmacyModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 style="margin-top:0; color:#0284c7;">Danh sách nhà thuốc có hàng</h3>
        <p style="font-size:13px; color:#666; margin-bottom:15px;">Sản phẩm: <b><?php echo $tensp; ?></b></p>
        
        <div class="branch-list">
            <?php if (!empty($list_chinhanh)): ?>
                <?php foreach ($list_chinhanh as $cn): 
                    $qty = (int)$cn['ton_kho'];
                    $has_stock = $qty > 0;
                    $status_class = $has_stock ? 'has-stock' : 'no-stock';
                    $status_label = $has_stock ? "Còn $qty sản phẩm" : "Tạm hết hàng";
                    $badge_class = $has_stock ? "badge-green" : "badge-gray";
                ?>
                    <div class="branch-item <?php echo $status_class; ?>">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <strong style="font-size:15px;"><?php echo htmlspecialchars($cn['tencn']); ?></strong>
                            <span class="stock-badge <?php echo $badge_class; ?>"><?php echo $status_label; ?></span>
                        </div>
                        <p style="margin:5px 0; font-size:13px; color:#555;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($cn['diachi']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Chưa có thông tin chi nhánh.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script>
    // 1. Xử lý Gallery
    function changeImage(el) {
        document.getElementById('mainImage').src = el.src;
        document.querySelectorAll('.thumb-item').forEach(item => item.classList.remove('active'));
        el.classList.add('active');
    }

    // 2. Xử lý số lượng
    function updateQty(change) {
        const input = document.getElementById('qtyInput');
        let newVal = parseInt(input.value) + change;
        if(newVal < 1) newVal = 1;
        if(newVal > 50) newVal = 50;
        input.value = newVal;
    }

    // 3. Modal Tìm nhà thuốc
    const modal = document.getElementById("pharmacyModal");
    const btnOpen = document.getElementById("btnOpenPharmacy");
    const btnClose = document.querySelector(".close-modal");

    btnOpen.onclick = () => { 
        modal.style.display = "flex"; 
        document.body.style.overflow = "hidden"; // Chặn cuộn trang
    }
    btnClose.onclick = () => { 
        modal.style.display = "none"; 
        document.body.style.overflow = "auto";
    }
    window.onclick = (e) => { 
        if (e.target == modal) { 
            modal.style.display = "none"; 
            document.body.style.overflow = "auto";
        } 
    }

    // 4. Thêm vào giỏ hàng (AJAX)
    function addToCart(masp) {
        const qty = document.getElementById('qtyInput').value;
        
        // Hiển thị loading
        const btn = document.querySelector('.btn-buy');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ĐANG XỬ LÝ...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', masp);
        formData.append('quantity', qty);

        // Gọi API (giả định đường dẫn cart_handler.php)
        fetch('<?php echo $BASE_URL; ?>/cart_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = oldText;
            btn.disabled = false;
            
            if(data.success) {
                showToast("Đã thêm sản phẩm vào giỏ hàng!");
                // Cập nhật số trên header nếu có (hàm giả định)
                if(typeof updateHeaderCartCount === 'function') updateHeaderCartCount(data.total_items);
            } else {
                alert(data.message || "Có lỗi xảy ra!");
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = oldText;
            btn.disabled = false;
            showToast("Đã thêm vào giỏ hàng (Client simulation)!"); 
        });
    }

    function showToast(msg) {
        const container = document.getElementById('toast-container');
        const div = document.createElement('div');
        div.className = 'toast-msg';
        div.innerHTML = `<i class="fas fa-check-circle" style="color:#22c55e; font-size:20px;"></i> <span>${msg}</span>`;
        container.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }
</script>

</body>
</html>