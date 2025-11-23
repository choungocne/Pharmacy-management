<?php
session_start();
$status = $_GET['status'] ?? 'error';
$sessionData = $_SESSION['current_transaction'] ?? null;

if ($status === 'success' && $sessionData) {
    // Lấy mã đơn hàng thật từ DB (đã lưu ở bước OTP)
    $finalCode = $sessionData['real_order_code'] ?? $sessionData['order_code'];
    $amount = $sessionData['amount'];
    $date = date('H:i - d/m/Y');
} else {
    // Truy cập trái phép
    $finalCode = "N/A";
    $amount = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán thành công</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .receipt-card { width: 400px; background: #fff; border-radius: 20px; padding: 30px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .icon { font-size: 60px; color: #28a745; margin-bottom: 20px; }
        .amount { font-size: 30px; color: #005b9f; font-weight: bold; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: #555; }
        .btn { display: block; background: #333; color: #fff; padding: 15px; border-radius: 10px; text-decoration: none; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <h2>Thanh toán thành công!</h2>
        <p>Cảm ơn bạn đã mua hàng tại Nhà Thuốc An Tâm.</p>
        
        <div class="amount"><?= number_format($amount) ?>đ</div>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 10px;">
            <div class="info-row"><span>Mã đơn hàng</span> <strong>#<?= $finalCode ?></strong></div>
            <div class="info-row"><span>Thời gian</span> <span><?= $date ?></span></div>
            <div class="info-row"><span>Khách hàng</span> <span><?= htmlspecialchars($sessionData['fullname'] ?? 'Khách lẻ') ?></span></div>
        </div>

        <a href="giohang.php" class="btn">Tiếp tục mua sắm</a>
    </div>
    
    <?php 
    // Xóa session sau khi hiển thị xong
    if($status === 'success') unset($_SESSION['current_transaction']); 
    ?>
</body>
</html>