<?php
// pages/my_orders.php
// Khởi động session và kết nối DB
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'path' => '/',
    ]);
    session_start();
}
require_once __DIR__ . '/../db.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (empty($_SESSION['auth']) || empty($_SESSION['auth']['makh'])) {
    echo "<script>
        alert('Vui lòng đăng nhập để xem đơn hàng!');
        window.location.href='/Pharmacy-management/login.php';
    </script>";
    exit;
}

$makh = $_SESSION['auth']['makh'];
$pdo = pdo();

// 2. XỬ LÝ: KHÁCH XÁC NHẬN ĐÃ NHẬN HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_received'])) {
    $sodh_confirm = $_POST['sodh'];
    
    // Chỉ cập nhật nếu đơn hàng của đúng khách và đang 'Đang vận chuyển'
    $sqlCheck = "UPDATE donhang SET TrangThai = 'Đã giao hàng' 
                 WHERE sodh = ? AND makh = ? AND TrangThai = 'Đang vận chuyển'";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$sodh_confirm, $makh]);
    
    if ($stmtCheck->rowCount() > 0) {
        echo "<script>
            alert('Cảm ơn bạn! Đơn hàng đã được cập nhật trạng thái thành công.');
            window.location.href='base.php?page=my_orders';
        </script>";
    } else {
        echo "<script>alert('Không thể cập nhật trạng thái đơn hàng này.');</script>";
    }
}

// 3. LẤY DỮ LIỆU ĐƠN HÀNG
$sql = "SELECT * FROM donhang WHERE makh = ? ORDER BY ngaytao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$makh]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hàm lấy ảnh sản phẩm
function getProductImage($pdo, $masp) {
    $stmt = $pdo->prepare("SELECT hinhsp FROM sanpham WHERE masp = ?");
    $stmt->execute([$masp]);
    return $stmt->fetchColumn() ?: 'static/img/no-image.jpg';
}
?>

<style>
    body {
        background-color: #f0f2f5;
    }
    .order-page-header {
        background: linear-gradient(135deg, #0061f2 0%, #6900f2 100%);
        padding: 40px 0;
        margin-bottom: -40px;
        color: white;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .order-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 15px;
    }
    
    /* Card Đơn hàng */
    .order-card {
        background: #fff;
        border-radius: 16px;
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        overflow: hidden;
        transition: transform 0.2s;
    }
    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }

    /* Header Card */
    .order-header {
        padding: 15px 25px;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #fff;
    }
    .order-id {
        font-weight: 700;
        color: #333;
        font-size: 1.1rem;
    }
    .order-date {
        color: #888;
        font-size: 0.9rem;
        margin-left: 10px;
    }

    /* Body Card */
    .order-body {
        padding: 20px 25px;
    }

    /* Sản phẩm */
    .product-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #eee;
    }
    .product-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .product-img {
        width: 70px;
        height: 70px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #eee;
    }
    .product-info {
        flex: 1;
        padding-left: 15px;
    }
    .product-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .product-meta {
        font-size: 0.85rem;
        color: #666;
    }
    .product-price {
        font-weight: 700;
        color: #0061f2;
    }

    /* Info Box (Ship & Pay) */
    .info-section {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 15px;
        margin-top: 20px;
        font-size: 0.9rem;
    }
    .info-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        display: block;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    /* Footer Card */
    .order-footer {
        padding: 15px 25px;
        background-color: #fff;
        border-top: 1px solid #f1f1f1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .total-label {
        font-size: 0.9rem;
        color: #666;
    }
    .total-amount {
        font-size: 1.3rem;
        font-weight: 800;
        color: #dc3545;
    }

    /* Status Badges */
    .badge-status {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .stt-pending { background: #fff3cd; color: #856404; }
    .stt-shipping { background: #cce5ff; color: #004085; }
    .stt-success { background: #d4edda; color: #155724; }
    .stt-cancel { background: #f8d7da; color: #721c24; }

    /* Button */
    .btn-received {
        background: linear-gradient(45deg, #11998e, #38ef7d);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 4px 10px rgba(56, 239, 125, 0.3);
        transition: 0.3s;
    }
    .btn-received:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(56, 239, 125, 0.4);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
</style>

<div class="order-page-header">
    <div class="container text-center">
        <h2 class="fw-bold mb-1">Lịch Sử Đơn Hàng</h2>
        <p class="opacity-75 mb-0">Theo dõi và quản lý các đơn hàng của bạn</p>
    </div>
</div>

<div class="container order-container py-5">
    <div class="row">
        <div class="col-12">
            
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <a href="base.php?page=home" class="text-decoration-none fw-bold text-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm
                </a>
                <span class="badge bg-white text-dark shadow-sm px-3 py-2 rounded-pill">
                    Tổng: <?= count($orders) ?> đơn hàng
                </span>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <img src="https://cdn-icons-png.flaticon.com/512/11329/11329060.png" alt="Empty" style="width: 120px; opacity: 0.5; margin-bottom: 20px;">
                    <h4 class="text-muted fw-bold">Bạn chưa có đơn hàng nào</h4>
                    <p class="text-secondary mb-4">Hãy khám phá các sản phẩm chăm sóc sức khỏe tốt nhất tại An Tâm.</p>
                    <a href="base.php?page=home" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow">MUA SẮM NGAY</a>
                </div>
            <?php else: ?>

                <?php foreach ($orders as $order): ?>
                    <?php 
                        // Giải mã JSON
                        $items = json_decode($order['chitiet'], true) ?: [];
                        $ship = json_decode($order['shipment'], true) ?: [];
                        $pay = json_decode($order['payment'], true) ?: [];
                        
                        $tong_hang = 0;
                        foreach($items as $it) $tong_hang += (($it['gia'] ?? 0) * ($it['sl'] ?? 1));
                        
                        $shipFee = $order['phiship'] ?? 0;
                        $giamGia = $order['giagiam'] ?? 0;
                        $tongCong = $tong_hang + $shipFee - $giamGia;

                        // Xử lý trạng thái
                        $stt = $order['TrangThai'];
                        $sttBadge = '';
                        $canConfirm = false;

                        if($stt == 'Chờ xác nhận') {
                            $sttBadge = '<span class="badge-status stt-pending"><i class="fas fa-clock"></i> Chờ xác nhận</span>';
                        } elseif($stt == 'Đang vận chuyển') {
                            $sttBadge = '<span class="badge-status stt-shipping"><i class="fas fa-shipping-fast"></i> Đang vận chuyển</span>';
                            $canConfirm = true;
                        } elseif($stt == 'Đã giao hàng') {
                            $sttBadge = '<span class="badge-status stt-success"><i class="fas fa-check-circle"></i> Giao thành công</span>';
                        } else {
                            $sttBadge = '<span class="badge-status stt-cancel"><i class="fas fa-times-circle"></i> Đã hủy</span>';
                        }
                    ?>

                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">#<?= $order['sodh'] ?></span>
                                <span class="order-date"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($order['ngaytao'])) ?></span>
                            </div>
                            <div><?= $sttBadge ?></div>
                        </div>

                        <div class="order-body">
                            <div class="row">
                                <div class="col-lg-8 border-end-lg">
                                    <?php foreach ($items as $item): 
                                        $img = !empty($item['hinh']) ? $item['hinh'] : getProductImage($pdo, $item['masp']);
                                        if(strpos($img, 'http')===false) $img = '/'.ltrim($img, '/');
                                    ?>
                                    <div class="product-item">
                                        <img src="<?= htmlspecialchars($img) ?>" class="product-img" onerror="this.src='static/img/no-image.jpg'">
                                        <div class="product-info">
                                            <div class="product-name"><?= htmlspecialchars($item['tensp'] ?? 'Sản phẩm') ?></div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span class="product-meta">SL: <strong><?= $item['sl'] ?></strong></span>
                                                <span class="product-price"><?= number_format($item['gia'] ?? 0, 0, ',', '.') ?>đ</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="col-lg-4 ps-lg-4 mt-3 mt-lg-0">
                                    <div class="info-section">
                                        <div class="mb-3">
                                            <span class="info-label text-primary"><i class="fas fa-map-marker-alt me-1"></i> Giao đến</span>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ship['name'] ?? 'N/A') ?></div>
                                            <div class="small text-secondary"><?= htmlspecialchars($ship['phone'] ?? '') ?></div>
                                            <div class="small text-secondary text-truncate"><?= htmlspecialchars($ship['address'] ?? '') ?></div>
                                        </div>
                                        <div>
                                            <span class="info-label text-success"><i class="fas fa-wallet me-1"></i> Thanh toán</span>
                                            <div class="d-flex justify-content-between small">
                                                <span>Phương thức:</span>
                                                <span class="fw-bold"><?= ($pay['method'] == 'MOMO' || $pay['method'] == 'VNPAY') ? 'Ví điện tử' : 'Tiền mặt (COD)' ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between small mt-1">
                                                <span>Trạng thái:</span>
                                                <?php 
                                                    $pStatus = $pay['status'] ?? '';
                                                    echo ($pStatus == 'Paid') 
                                                        ? '<span class="text-success fw-bold"><i class="fas fa-check"></i> Đã thanh toán</span>' 
                                                        : '<span class="text-warning fw-bold">Chưa thanh toán</span>';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-end">
                                    <div class="total-label">Tổng thanh toán</div>
                                    <div class="total-amount"><?= number_format($tongCong, 0, ',', '.') ?>đ</div>
                                </div>
                            </div>

                            <div>
                                <?php if ($canConfirm): ?>
                                    <form method="POST" onsubmit="return confirm('Bạn xác nhận đã nhận được hàng đầy đủ?');">
                                        <input type="hidden" name="sodh" value="<?= $order['sodh'] ?>">
                                        <button type="submit" name="confirm_received" class="btn-received">
                                            <i class="fas fa-box-open me-2"></i> Đã nhận hàng
                                        </button>
                                    </form>
                                <?php elseif ($stt == 'Đã giao hàng'): ?>
                                    <button class="btn btn-light border rounded-pill disabled" disabled>
                                        <i class="fas fa-star text-warning me-1"></i> Đánh giá
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-light border rounded-pill text-muted" disabled>
                                        Đang xử lý...
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
