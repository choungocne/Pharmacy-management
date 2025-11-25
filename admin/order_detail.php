<?php
// admin/order_detail.php
$page_title = 'Chi tiết đơn hàng';
$active = 'orders';
require_once __DIR__ . '/partials/header.php';
include("../db.php");
// Kết nối DB (nếu header chưa có)
if (!isset($pdo)) {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

$sodh = $_GET['sodh'] ?? 0;
$msg = '';

// --- XỬ LÝ CẬP NHẬT TRẠNG THÁI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['trangthai'];
    $allowedStatus = ['Chờ xác nhận', 'Đang vận chuyển', 'Đã giao hàng', 'Đã hủy'];
    
    if (in_array($newStatus, $allowedStatus)) {
        $stmt = $pdo->prepare("UPDATE donhang SET TrangThai = ? WHERE sodh = ?");
        $stmt->execute([$newStatus, $sodh]);
        $msg = '<div class="bg-green-100 text-green-700 p-3 rounded mb-4">Cập nhật trạng thái thành công!</div>';
    }
}

// --- LẤY DỮ LIỆU ĐƠN HÀNG ---
$stmt = $pdo->prepare("
    SELECT d.*, k.hoten as ten_kh, k.sdt as sdt_kh, k.email 
    FROM donhang d 
    LEFT JOIN khachhang k ON d.makh = k.makh 
    WHERE d.sodh = ?
");
$stmt->execute([$sodh]);
$order = $stmt->fetch();

if (!$order) {
    echo "<div class='p-10'>Không tìm thấy đơn hàng! <a href='orders.php' class='text-blue-500'>Quay lại</a></div>";
    exit;
}

// Giải mã JSON
$items = json_decode($order['chitiet'] ?? '[]', true) ?: [];
$shipment = json_decode($order['shipment'] ?? '{}', true);
$payment = json_decode($order['payment'] ?? '{}', true);

// Tính toán
$tongTienHang = 0;
foreach ($items as $it) $tongTienHang += ($it['gia'] * $it['sl']);
$tongCong = $tongTienHang + $order['phiship'] - $order['giagiam'];

// Màu trạng thái
$sttColors = [
    'Chờ xác nhận' => 'bg-yellow-100 text-yellow-800',
    'Đang vận chuyển' => 'bg-blue-100 text-blue-800',
    'Đã giao hàng' => 'bg-green-100 text-green-800',
    'Đã hủy' => 'bg-red-100 text-red-800'
];
?>

<style>
    .glass { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); }
</style>

<main class="flex-1 overflow-y-auto p-6 bg-slate-50">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <a href="orders.php" class="p-2 rounded-full bg-white shadow hover:bg-gray-50 text-gray-600">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-800">Chi tiết đơn hàng #<?= $order['sodh'] ?></h1>
            </div>
            <span class="px-4 py-2 rounded-full font-bold <?= $sttColors[$order['TrangThai']] ?? 'bg-gray-100' ?>">
                <?= $order['TrangThai'] ?>
            </span>
        </div>

        <?= $msg ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="glass rounded-2xl p-6 shadow-sm border border-slate-200">
                    <h3 class="font-bold text-lg mb-4 text-slate-700 border-b pb-2">Sản phẩm</h3>
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-slate-500 border-b">
                                <th class="pb-2">Sản phẩm</th>
                                <th class="pb-2 text-center">SL</th>
                                <th class="pb-2 text-right">Đơn giá</th>
                                <th class="pb-2 text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($items as $item): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-3 flex items-center gap-3">
                                    <img src="<?= !empty($item['hinh']) ? '/' . ltrim($item['hinh'], '/') : '/Pharmacy-management/static/img/no-image.jpg' ?>" 
                                         class="w-12 h-12 object-cover rounded border">
                                    <span class="font-medium text-slate-700 line-clamp-2"><?= $item['tensp'] ?></span>
                                </td>
                                <td class="text-center py-3">x<?= $item['sl'] ?></td>
                                <td class="text-right py-3"><?= number_format($item['gia'], 0, ',', '.') ?>đ</td>
                                <td class="text-right py-3 font-bold"><?= number_format($item['gia'] * $item['sl'], 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="mt-4 pt-4 border-t space-y-2 text-right">
                        <div class="text-sm text-slate-500">Tạm tính: <span class="font-medium text-slate-800"><?= number_format($tongTienHang, 0, ',', '.') ?>đ</span></div>
                        <div class="text-sm text-slate-500">Phí vận chuyển: <span class="font-medium text-slate-800"><?= number_format($order['phiship'], 0, ',', '.') ?>đ</span></div>
                        <?php if ($order['giagiam'] > 0): ?>
                            <div class="text-sm text-green-600">Giảm giá: -<?= number_format($order['giagiam'], 0, ',', '.') ?>đ</div>
                        <?php endif; ?>
                        <div class="text-xl font-bold text-blue-600 pt-2 border-t">
                            Tổng cộng: <?= number_format($tongCong, 0, ',', '.') ?>đ
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="glass rounded-2xl p-6 shadow-sm border border-slate-200">
                    <h3 class="font-bold text-lg mb-4 text-slate-700">Cập nhật trạng thái</h3>
                    <form method="POST">
                        <select name="trangthai" class="w-full p-3 border rounded-xl bg-white mb-4 focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php foreach (['Chờ xác nhận', 'Đang vận chuyển', 'Đã giao hàng', 'Đã hủy'] as $stt): ?>
                                <option value="<?= $stt ?>" <?= $order['TrangThai'] === $stt ? 'selected' : '' ?>>
                                    <?= $stt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition">
                            Lưu thay đổi
                        </button>
                    </form>
                </div>

                <div class="glass rounded-2xl p-6 shadow-sm border border-slate-200">
                    <h3 class="font-bold text-lg mb-4 text-slate-700">Thông tin giao hàng</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="block text-slate-500 text-xs uppercase">Người nhận</span>
                            <span class="font-medium"><?= htmlspecialchars($shipment['name'] ?? $order['ten_kh']) ?></span>
                        </div>
                        <div>
                            <span class="block text-slate-500 text-xs uppercase">Số điện thoại</span>
                            <span class="font-medium"><?= htmlspecialchars($shipment['phone'] ?? $order['sdt_kh']) ?></span>
                        </div>
                        <div>
                            <span class="block text-slate-500 text-xs uppercase">Địa chỉ</span>
                            <span class="font-medium"><?= htmlspecialchars($shipment['address'] ?? 'Tại cửa hàng') ?></span>
                        </div>
                        <div class="pt-3 border-t">
                            <span class="block text-slate-500 text-xs uppercase">Thanh toán</span>
                            <span class="badge bg-gray-100 px-2 py-1 rounded text-xs border">
                                <?= htmlspecialchars($payment['method'] ?? 'Tiền mặt') ?>
                            </span>
                            <span class="ml-2 text-xs <?= ($payment['status'] ?? '') == 'Paid' ? 'text-green-600 font-bold' : 'text-yellow-600' ?>">
                                <?= ($payment['status'] ?? '') == 'Paid' ? '(Đã thanh toán)' : '(Chưa thanh toán)' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>