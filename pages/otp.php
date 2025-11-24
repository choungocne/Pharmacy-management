<?php
// file: otp.php
session_start();
include("../db.php");
// 1. KẾT NỐI DATABASE (PDO)
try {
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USER;
    $password = DB_PASS;
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

// 2. LẤY DỮ LIỆU TỪ SESSION
$trans = $_SESSION['current_transaction'] ?? null;
$otpCorrect = "123456"; // OTP mặc định để test
$errorMessage = "";

// Chặn truy cập nếu không có giao dịch
if (!$trans) {
    header("Location: giohang.php");
    exit;
}

// 3. XỬ LÝ KHI NGƯỜI DÙNG BẤM XÁC NHẬN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otpInput = trim($_POST['otp_input'] ?? '');

    if ($otpInput === $otpCorrect) {
        try {
            $conn->beginTransaction();

            // --- A. CHUẨN BỊ DỮ LIỆU JSON ĐỂ LƯU VÀO BẢNG `donhang` ---
            
            // 1. Chi tiết sản phẩm (Snapshot giá & tên)
            $orderDetailList = [];
            if (!empty($trans['cart_items'])) {
                foreach ($trans['cart_items'] as $item) {
                    $orderDetailList[] = [
                        'masp'  => $item['masp'] ?? 0,
                        'tensp' => $item['tensp'] ?? 'Sản phẩm',
                        'sl'    => $item['cart_quantity'] ?? 1,
                        'gia'   => $item['giaban'] ?? 0,
                        'hinh'  => $item['hinhsp'] ?? ''
                    ];
                }
            }
            $jsonChiTiet = json_encode($orderDetailList, JSON_UNESCAPED_UNICODE);

            // 2. Thông tin giao hàng (Shipment)
            $jsonShipment = json_encode([
                'name'    => $trans['fullname'],
                'phone'   => $trans['phone'],
                'address' => $trans['address']
            ], JSON_UNESCAPED_UNICODE);

            // 3. Thông tin thanh toán (Payment)
            $jsonPayment = json_encode([
                'method'    => $trans['payment_method'], // MOMO, ATM, COD
                'bank_code' => $_POST['bank_code'] ?? 'GATEWAY',
                'total'     => $trans['amount'],
                'status'    => ($trans['payment_method'] == 'COD') ? 'Pending' : 'Paid',
                'date'      => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);

            // --- B. INSERT VÀO BẢNG `donhang` ---
            $makh = $_SESSION['auth']['makh'] ?? ($_SESSION['makh'] ?? null);

            // Detect schema where `sodh` is not AUTO_INCREMENT to avoid "no default value" errors
            $colInfo = $conn->query("SHOW COLUMNS FROM donhang LIKE 'sodh'")->fetch(PDO::FETCH_ASSOC);
            $needsManualOrderId = !($colInfo && stripos($colInfo['Extra'] ?? '', 'auto_increment') !== false);
            $manualOrderId = null;
            if ($needsManualOrderId) {
                $manualOrderId = (int) $conn->query("SELECT IFNULL(MAX(sodh), 0) + 1 AS next_id FROM donhang")->fetchColumn();
            }

            if ($needsManualOrderId) {
                $sqlInsert = "INSERT INTO donhang (sodh, ngaytao, makh, giagiam, TrangThai, chitiet, payment, shipment, phiship) 
                              VALUES (:sodh, NOW(), :makh, 0, 'Chờ xác nhận', :chitiet, :payment, :shipment, :phiship)";
            } else {
                $sqlInsert = "INSERT INTO donhang (ngaytao, makh, giagiam, TrangThai, chitiet, payment, shipment, phiship) 
                              VALUES (NOW(), :makh, 0, 'Chờ xác nhận', :chitiet, :payment, :shipment, :phiship)";
            }
            
            $params = [
                ':makh'     => $makh,
                ':chitiet'  => $jsonChiTiet,
                ':payment'  => $jsonPayment,
                ':shipment' => $jsonShipment,
                ':phiship'  => $trans['shipping_fee'] ?? 0
            ];
            if ($needsManualOrderId) {
                $params[':sodh'] = $manualOrderId;
            }

            $stmt = $conn->prepare($sqlInsert);
            $stmt->execute($params);

            $newOrderId = $needsManualOrderId ? $manualOrderId : $conn->lastInsertId(); // L?y ID don h?ng v?a t?o


            // --- C. XÓA GIỎ HÀNG CŨ ---
            $sessionId = session_id();
            if ($makh) {
                $stmtDel = $conn->prepare("DELETE FROM giohang WHERE makh = ?");
                $stmtDel->execute([$makh]);
            } else {
                $stmtDel = $conn->prepare("DELETE FROM giohang WHERE session_id = ?");
                $stmtDel->execute([$sessionId]);
            }

            $conn->commit();

            // --- D. CẬP NHẬT SESSION & CHUYỂN HƯỚNG ---
            $_SESSION['current_transaction']['real_order_id'] = $newOrderId; // Lưu ID thật để trang success hiển thị
            
            header("Location: success.php?status=success");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $errorMessage = "Lỗi hệ thống: " . $e->getMessage();
        }

    } else {
        $errorMessage = "Mã OTP không chính xác. Vui lòng nhập 123456.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cổng thanh toán Napas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* RESET CSS */
        body { font-family: 'Arial', sans-serif; background-color: #666; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .napas-wrapper { width: 800px; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.3); display: flex; flex-direction: column; }
        
        /* HEADER */
        .napas-header { height: 80px; border-bottom: 4px solid #005b9f; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; background: #fff; position: relative; overflow: hidden; }
        .napas-logo-img { height: 40px; font-weight: bold; color: #004a8f; font-size: 32px; font-style: italic; }
        .header-decor { position: absolute; top: 0; right: 0; height: 100%; width: 200px; background: linear-gradient(135deg, transparent 20%, #f0f9ff 20%, #f0f9ff 50%, #dcedff 50%, #dcedff 80%, #cfe6ff 80%); opacity: 0.6; }
        .lang-flags { z-index: 2; display: flex; gap: 5px; }

        /* BODY */
        .napas-body { display: flex; min-height: 400px; }
        .napas-sidebar { width: 35%; padding: 30px; border-right: 1px solid #eee; position: relative; background-color: #f9f9f9; }
        .info-group { margin-bottom: 25px; }
        .info-label { font-size: 13px; color: #004a8f; font-weight: bold; display: flex; align-items: center; gap: 8px; margin-bottom: 5px; text-transform: uppercase; }
        .info-value { font-size: 14px; color: #333; padding-left: 0; font-weight: 500; word-break: break-word; }
        .countdown-box { margin-top: 40px; text-align: center; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
        .countdown-timer { color: #d9534f; font-weight: bold; font-size: 20px; margin-top: 5px; }

        .napas-main { width: 65%; padding: 40px; display: flex; flex-direction: column; align-items: center; }

        /* CARD VISUAL */
        .card-visual { width: 320px; height: 200px; background: linear-gradient(110deg, #205099 0%, #052c65 100%); border-radius: 12px; color: #fff; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.2); margin-bottom: 30px; }
        .card-chip { width: 45px; height: 35px; background: linear-gradient(135deg, #ffdb8e 0%, #cea654 100%); border-radius: 6px; position: absolute; top: 40px; left: 30px; }
        .card-dots { position: absolute; top: 90px; left: 30px; font-size: 20px; letter-spacing: 3px; }
        .card-logo { position: absolute; bottom: 20px; right: 20px; font-weight: bold; font-style: italic; font-size: 20px; }
        .card-logo span { color: #9fd423; }

        /* FORM */
        .otp-input-group { width: 320px; margin-bottom: 15px; }
        .form-control { width: 100%; padding: 12px; font-size: 18px; border: 1px solid #ccc; border-radius: 4px; outline: none; box-sizing: border-box; text-align: center; letter-spacing: 3px; }
        .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,0.1); }
        
        .btn-group { display: flex; gap: 10px; width: 320px; margin-top: 10px; }
        .btn { flex: 1; padding: 12px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; border: none; transition: 0.2s; }
        .btn-cancel { background: #f0f0f0; color: #333; }
        .btn-cancel:hover { background: #e0e0e0; }
        .btn-submit { background: #005b9f; color: #fff; }
        .btn-submit:hover { background: #004a8f; }

        .error-msg { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; width: 320px; text-align: center; font-size: 13px; }

        .napas-footer { border-top: 1px solid #eee; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        .footer-left { font-size: 12px; color: #666; }
    </style>
</head>
<body>

    <div class="napas-wrapper">
        <div class="napas-header">
            <div class="napas-logo-img">napas<span style="color:#8bc53f;">></span></div>
            <div class="header-decor"></div>
            <div class="lang-flags">
                <span style="font-size: 12px; font-weight: bold; color: #666; cursor: pointer;">VN / EN</span>
            </div>
        </div>

        <div class="napas-body">
            <div class="napas-sidebar">
                <div class="info-group">
                    <div class="info-label"><i class="fas fa-shopping-cart"></i> Đơn hàng</div>
                    <div class="info-value"><?= htmlspecialchars($trans['order_code']) ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label"><i class="fas fa-money-bill-wave"></i> Số tiền</div>
                    <div class="info-value" style="font-weight: bold; font-size: 18px; color: #005b9f;">
                        <?= number_format($trans['amount'], 0, ',', '.') ?> VND
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label"><i class="fas fa-user"></i> Khách hàng</div>
                    <div class="info-value"><?= htmlspecialchars($trans['fullname']) ?></div>
                </div>

                <div class="countdown-box">
                    <div style="font-size: 13px; color: #666;">Giao dịch hết hạn sau</div>
                    <div class="countdown-timer" id="timer">10:00</div>
                </div>
            </div>

            <div class="napas-main">
                <div class="card-visual">
                    <div class="card-chip"></div>
                    <div class="card-dots">**** **** **** 9704</div>
                    <div style="position: absolute; top: 130px; left: 30px; font-size: 10px; opacity: 0.8;">VALID THRU 12/30</div>
                    <div class="card-logo">napas<span>></span></div>
                </div>

                <form method="POST" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <?php if ($errorMessage): ?>
                        <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMessage) ?></div>
                    <?php endif; ?>

                    <div style="width: 320px; text-align: left; font-size: 13px; margin-bottom: 5px; color: #555;">Nhập mã OTP</div>
                    <div class="otp-input-group">
                        <input type="text" name="otp_input" class="form-control" placeholder="######" maxlength="6" required autofocus autocomplete="off">
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-cancel" onclick="window.location.href='giohang.php'">Hủy bỏ</button>
                        <button type="submit" class="btn btn-submit">XÁC NHẬN</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="napas-footer">
            <div class="footer-left">© 2025 Cổng thanh toán Napas. Bảo mật tuyệt đối.</div>
            <div style="color: #ccc; font-size: 24px;">
                <i class="fab fa-cc-visa"></i> <i class="fab fa-cc-mastercard"></i>
            </div>
        </div>
    </div>

    <script>
        let duration = 600; 
        const timerDisplay = document.getElementById('timer');
        const countdown = setInterval(() => {
            const minutes = Math.floor(duration / 60);
            let seconds = duration % 60;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            timerDisplay.textContent = `${minutes}:${seconds}`;
            if (--duration < 0) {
                clearInterval(countdown);
                timerDisplay.textContent = "Hết hạn";
                document.querySelector('.btn-submit').disabled = true;
                document.querySelector('.btn-submit').style.opacity = 0.5;
                alert("Giao dịch đã hết hạn!");
                window.location.href = 'giohang.php';
            }
        }, 1000);
    </script>
</body>
</html>