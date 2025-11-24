<?php
// file: checkout.php
session_start();
include("../db.php");
// 1. KẾT NỐI DATABASE
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

// 2. LẤY DỮ LIỆU NGÂN HÀNG (Giữ nguyên)
$bankDataForJS = []; 
$banksList = [];     

try {
    $stmt = $conn->query("SELECT * FROM taikhoanhethong WHERE TrangThai = 'HoatDong'");
    $rawBanks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($rawBanks as $b) {
        $logoUrl = $b['Logo'];
        if (!empty($logoUrl) && strpos($logoUrl, 'http') === false) {
            $logoUrl = '/Pharmacy-management/' . ltrim($logoUrl, '/');
        }
        $banksList[] = [
            'code' => $b['NganHang'],
            'name' => $b['NganHang'],
            'logo' => $logoUrl
        ];
        $bankDataForJS[$b['NganHang']] = [
            'real_account' => $b['SoTaiKhoan'],
            'holder_name'  => $b['ChuTaiKhoan']
        ];
    }
} catch (Exception $e) { 
    $banksList = [];
}

// 3. XỬ LÝ LOGIC TÍNH TIỀN VÀ LƯU SESSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? 'Khách lẻ';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'COD';

    // Truy vấn lại giỏ hàng
    $sessionId = session_id();
    
    // SỬA 1: Đồng bộ cách lấy ID khách hàng (giống otp.php)
    $userId = $_SESSION['auth']['makh'] ?? ($_SESSION['makh'] ?? null);
    
    $cartItems = [];
    
    try {
        // SỬA 2: Lấy thêm cột masp và hinhsp để lưu snapshot
        $cols = "gh.masp, gh.soluong, sp.tensp, sp.giaban, sp.hinhsp";
        
        if ($userId) {
            $sql = "SELECT $cols FROM giohang gh JOIN sanpham sp ON gh.masp = sp.masp WHERE gh.makh = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $userId]);
        } else {
            $sql = "SELECT $cols FROM giohang gh JOIN sanpham sp ON gh.masp = sp.masp WHERE gh.session_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $sessionId]);
        }
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $cartItems = []; }

    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += floatval($item['giaban']) * intval($item['soluong']);
    }

    $isPickUp = (strpos($address, 'Nhận tại') !== false);
    $shippingFee = ($subtotal >= 300000 || $isPickUp) ? 0 : 30000;
    $totalAmount = $subtotal + $shippingFee;
    
    if ($subtotal == 0 && empty($cartItems)) {
        header("Location: giohang.php");
        exit;
    }
    
    $orderCode = 'DH' . time() . rand(10, 99);

    // SỬA 3: Lưu cart_items và shipping_fee vào Session để file OTP dùng
    $_SESSION['current_transaction'] = [
        'order_code'     => $orderCode,
        'amount'         => $totalAmount,
        'fullname'       => $fullname,
        'phone'          => $phone,        // Lưu thêm SĐT
        'address'        => $address,      // Lưu thêm địa chỉ
        'payment_method' => $paymentMethod,
        'shipping_fee'   => $shippingFee,  // Lưu phí ship
        'cart_items'     => $cartItems     // QUAN TRỌNG: Lưu danh sách sản phẩm
    ];
} else {
    header("Location: giohang.php");
    exit;
}

$isCod = ($paymentMethod === 'COD');
$isMomo = ($paymentMethod === 'MOMO');
$themeColor = $isMomo ? '#d82d8b' : '#005b9f';
$actionUrl = "otp.php";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán - Nhà Thuốc An Tâm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; }
        #pills-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; background: linear-gradient(to bottom, #e0f7fa, #b3e5fc); }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .bank-item {
            transition: all 0.2s; cursor: pointer; 
            border: 2px solid transparent;
        }
        .bank-item:hover { border-color: <?= $themeColor ?>; transform: translateY(-2px); }
        .bank-item.selected {
            border-color: <?= $themeColor ?>;
            background-color: <?= $isMomo ? '#fdf2f8' : '#f0f9ff' ?>;
            box-shadow: 0 0 0 2px <?= $themeColor ?> inset;
        }
        
        .theme-input:focus {
            border-color: <?= $themeColor ?>;
            ring: 2px solid <?= $themeColor ?>;
            outline: none;
        }
        
        .napas-card {
            background: linear-gradient(135deg, #ff0080, #ff5c00);
            color: white; border-radius: 12px;
        }
        
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #999; }
    </style>
</head>
<body class="text-gray-800">
    <canvas id="pills-canvas"></canvas>

    <div class="min-h-screen flex items-center justify-center p-4">
        
        <?php if ($isCod): ?>
        <div class="w-full max-w-md glass-panel rounded-2xl p-8 text-center">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-box-open text-3xl text-orange-600"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">Thanh toán khi nhận hàng</h2>
            <p class="text-gray-600 mb-6">Đơn hàng <strong><?= $orderCode ?></strong> trị giá <strong><?= number_format($totalAmount) ?>đ</strong> sẽ được thanh toán bằng tiền mặt khi giao.</p>
            
            <form action="<?= $actionUrl ?>" method="POST">
                <input type="hidden" name="is_cod_confirm" value="1">
                <button class="w-full py-3 bg-sky-600 text-white rounded-xl font-bold hover:bg-sky-700 shadow-lg transition">Xác nhận đặt hàng</button>
            </form>
            <a href="giohang.php" class="block mt-3 text-gray-500 hover:text-gray-800">Quay lại</a>
        </div>

        <?php else: ?>
        <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-1 glass-panel p-6 rounded-2xl h-fit">
                <h3 class="font-bold text-lg mb-4 text-gray-700 border-b pb-2">Chi tiết đơn hàng</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span>Mã đơn:</span> <span class="font-bold"><?= $orderCode ?></span></div>
                    <div class="flex justify-between"><span>Khách hàng:</span> <span><?= htmlspecialchars($fullname) ?></span></div>
                    <div class="flex justify-between"><span>SĐT:</span> <span><?= htmlspecialchars($phone) ?></span></div>
                    <div class="pt-3 border-t border-gray-300 mt-3">
                        <div class="flex justify-between items-end">
                            <span>Tổng tiền:</span>
                            <span class="text-2xl font-bold" style="color: <?= $themeColor ?>"><?= number_format($totalAmount) ?>đ</span>
                        </div>
                    </div>
                </div>
                <a href="giohang.php" class="block text-center mt-6 text-xs text-gray-500 hover:underline">Quay lại giỏ hàng</a>
            </div>

            <div class="lg:col-span-2 glass-panel p-8 rounded-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold" style="color: <?= $themeColor ?>">
                        <?= $isMomo ? 'Cổng thanh toán MoMo' : 'Cổng thanh toán VNPAY' ?>
                    </h2>
                    <img src="<?= $isMomo ? 'https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png' : 'https://sandbox.vnpayment.vn/paymentv2/images/bank/vnpay-logo.png' ?>" class="h-8">
                </div>

                <form method="POST" action="<?= $actionUrl ?>" id="payment-form">
                    <input type="hidden" name="bank_code" id="selected-bank-code">
                    <input type="hidden" name="card_holder" id="final-card-holder">

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Chọn ngân hàng:</label>
                        <div class="grid grid-cols-4 md:grid-cols-5 gap-3 max-h-64 overflow-y-auto custom-scrollbar pr-2">
                            <?php foreach ($banksList as $bank): ?>
                                <div class="bank-item bg-white p-2 rounded-lg border h-14 flex items-center justify-center relative group"
                                     onclick="selectBank(this, '<?= $bank['code'] ?>')">
                                    <?php if(!empty($bank['logo'])): ?>
                                        <img src="<?= $bank['logo'] ?>" class="max-h-full max-w-full object-contain" alt="<?= $bank['name'] ?>">
                                    <?php else: ?>
                                        <span class="text-[10px] font-bold text-center"><?= $bank['name'] ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="bank-error" class="text-xs text-red-500 mt-1 h-4"></div>
                    </div>

                    <?php if ($isMomo): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="napas-card p-5 relative shadow-lg">
                                <div class="flex justify-between mb-6">
                                    <div class="w-10 h-7 bg-yellow-200 rounded-md border border-yellow-400"></div>
                                    <i class="fas fa-wifi rotate-90 opacity-70"></i>
                                </div>
                                <div class="mb-4">
                                    <label class="text-[10px] uppercase opacity-80">Số thẻ</label>
                                    <div class="text-lg font-mono tracking-widest" id="card-display">**** **** **** ****</div>
                                </div>
                                <div class="flex justify-between">
                                    <div>
                                        <label class="text-[10px] uppercase opacity-80">Chủ thẻ</label>
                                        <div class="text-sm font-bold uppercase truncate w-32" id="name-display">YOUR NAME</div>
                                    </div>
                                </div>
                                <div class="absolute bottom-4 right-4 font-black italic opacity-80 text-lg">NAPAS</div>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">SỐ THẺ ATM (Ghi trên thẻ)</label>
                                    <input type="text" id="input-acc-number" class="w-full p-3 border rounded-lg theme-input bg-white/50 font-mono" 
                                           placeholder="Nhập số thẻ..." oninput="checkAccountInfo()">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">TÊN CHỦ THẺ</label>
                                    <input type="text" id="input-holder-name" class="w-full p-3 border rounded-lg bg-gray-100 font-bold text-gray-600 uppercase" readonly placeholder="Tự động hiện...">
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2 text-sm text-gray-600">
                                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                Vui lòng nhập thông tin tài khoản Internet Banking.
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">SỐ TÀI KHOẢN</label>
                                <input type="text" id="input-acc-number" class="w-full p-3 border rounded-lg theme-input bg-white" 
                                       placeholder="Nhập số tài khoản" oninput="checkAccountInfo()">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">TÊN CHỦ TÀI KHOẢN</label>
                                <input type="text" id="input-holder-name" class="w-full p-3 border rounded-lg bg-gray-100 font-bold text-gray-600 uppercase" readonly placeholder="Tự động hiện...">
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="btn-submit" class="w-full mt-6 py-4 rounded-xl font-bold text-white shadow-lg flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed" 
                            style="background-color: <?= $themeColor ?>" disabled>
                        <span>THANH TOÁN NGAY</span> <i class="fas fa-check-circle"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const DB_DATA = <?= json_encode($bankDataForJS) ?>;
        
        const bankCodeInput = document.getElementById('selected-bank-code');
        const accInput = document.getElementById('input-acc-number');
        const holderInput = document.getElementById('input-holder-name');
        const finalHolderInput = document.getElementById('final-card-holder');
        const btnSubmit = document.getElementById('btn-submit');
        
        const cardDisplay = document.getElementById('card-display');
        const nameDisplay = document.getElementById('name-display');
        const bankError = document.getElementById('bank-error');

        function selectBank(el, code) {
            document.querySelectorAll('.bank-item').forEach(item => item.classList.remove('selected'));
            el.classList.add('selected');
            
            bankCodeInput.value = code;
            if(bankError) bankError.innerText = '';
            
            if(accInput) {
                accInput.value = '';
                accInput.focus();
            }
            if(holderInput) holderInput.value = '';
            if(cardDisplay) cardDisplay.innerText = '**** **** **** ****';
            if(nameDisplay) nameDisplay.innerText = 'YOUR NAME';
            
            checkAccountInfo();
        }

        function checkAccountInfo() {
            if(!accInput) return;
            
            const code = bankCodeInput.value;
            const inputVal = accInput.value.trim().replace(/\s/g, '');
            
            if (!code) {
                if(inputVal.length > 0 && bankError) bankError.innerText = 'Vui lòng chọn ngân hàng trước!';
                btnSubmit.disabled = true;
                return;
            } else {
                if(bankError) bankError.innerText = '';
            }

            if (cardDisplay) {
                cardDisplay.innerText = inputVal.replace(/(\d{4})(?=\d)/g, '$1 ') || '**** **** **** ****';
            }

            const bankInfo = DB_DATA[code];
            let isValid = false;
            let foundName = '';

            if (bankInfo) {
                if (inputVal === bankInfo.real_account) {
                    isValid = true;
                    foundName = bankInfo.holder_name;
                }
            }

            if (isValid) {
                holderInput.value = foundName;
                finalHolderInput.value = foundName;
                
                if (nameDisplay) nameDisplay.innerText = foundName;
                
                holderInput.classList.remove('text-gray-600');
                holderInput.classList.add('text-green-600');
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<span>XÁC NHẬN THANH TOÁN</span> <i class="fas fa-check"></i>`;
            } else {
                holderInput.value = '';
                if (nameDisplay) nameDisplay.innerText = 'YOUR NAME';
                
                holderInput.classList.remove('text-green-600');
                holderInput.classList.add('text-gray-600');
                
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = `<span>THANH TOÁN NGAY</span>`;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('pills-canvas');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                canvas.width = window.innerWidth; canvas.height = window.innerHeight;
                let pills = Array.from({length: 50}, () => ({
                    x: Math.random()*canvas.width, y: Math.random()*canvas.height,
                    size: Math.random()*5+5, speed: Math.random()+0.2
                }));
                function animate() {
                    ctx.clearRect(0,0,canvas.width,canvas.height);
                    ctx.fillStyle = 'rgba(255,255,255,0.3)';
                    pills.forEach(p => {
                        p.y -= p.speed;
                        if(p.y < -10) p.y = canvas.height + 10;
                        ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI*2); ctx.fill();
                    });
                    requestAnimationFrame(animate);
                }
                animate();
            }
        });
    </script>
</body>
</html>