<?php



$pdo = null;

$dbFile = __DIR__ . '/../db.php';

if (file_exists($dbFile)) {

    require_once $dbFile;

    if (function_exists('pdo')) {

        $pdo = pdo();

    } elseif (function_exists('get_pdo')) {

        $pdo = get_pdo();

    }

}

if (!$pdo instanceof PDO) {

    try {

        $pdo = new PDO(

            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,

            [

                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            ]

        );

        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    } catch (Throwable $e) {

        die('Could not connect to database: ' . $e->getMessage());

    }

}

if (!function_exists('money_vn')) {

    function money_vn($n) { return number_format((float)$n, 0, ',', '.'); }

}



date_default_timezone_set('Asia/Ho_Chi_Minh');



if (!function_exists('admin_index_col_exists')) {

    function admin_index_col_exists(PDO $pdo, string $table, string $column): bool {

        $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS

                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";

        $st = $pdo->prepare($sql);

        $st->execute([$table, $column]);

        return (int)$st->fetchColumn() > 0;

    }

}



if (!function_exists('admin_index_enrich_money')) {

    function admin_index_enrich_money(array &$rows, PDO $pdo): void {

        if (!$rows) { return; }

        $itemsCache = [];

        $ids = [];

        foreach ($rows as $idx => $row) {

            $items = json_decode($row['chitiet'] ?? '[]', true);

            if (!is_array($items)) { $items = []; }

            $itemsCache[$idx] = $items;

            foreach ($items as $it) {

                if (isset($it['masp'])) {

                    $ids[(int)$it['masp']] = true;

                }

            }

        }

        $price = [];

        if ($ids) {

            $in  = implode(',', array_map('intval', array_keys($ids)));

            $sql = admin_index_col_exists($pdo, 'sanpham', 'giagiam')

                ? "SELECT masp, CASE WHEN giagiam>0 THEN giagiam ELSE giaban END AS gia FROM sanpham WHERE masp IN ($in)"

                : "SELECT masp, giaban AS gia FROM sanpham WHERE masp IN ($in)";

            foreach ($pdo->query($sql) as $p) {

                $price[(int)$p['masp']] = (float)$p['gia'];

            }

        }

        foreach ($rows as $idx => &$row) {

            $items = $itemsCache[$idx] ?? [];

            $sum = 0;

            foreach ($items as $it) {

                $qty = (int)($it['sl'] ?? 0);

                $masp = (int)($it['masp'] ?? 0);

                $gia = $price[$masp] ?? 0;

                $sum += $qty * $gia;

            }

            $row['phai_thu'] = max(0, $sum - (float)($row['giagiam'] ?? 0));

        }

        unset($row);

    }

}



if (!function_exists('admin_index_sum_phai_thu')) {

    function admin_index_sum_phai_thu(PDO $pdo, string $whereSql, array $params = []): float {

        $col = admin_index_col_exists($pdo, 'donhang', 'giagiam') ? 'giagiam' : '0';

        $stmt = $pdo->prepare("SELECT chitiet, $col AS giagiam FROM donhang WHERE $whereSql");

        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        admin_index_enrich_money($rows, $pdo);

        $total = 0;

        foreach ($rows as $row) {

            $total += (float)($row['phai_thu'] ?? 0);

        }

        return $total;

    }

}



$quickStats = [

    'revenue_today'  => 0,

    'orders_today'   => 0,

    'low_stock'      => 0,

    'total_products' => 0,

];



try {

    $quickStats['revenue_today'] = admin_index_sum_phai_thu(

        $pdo,

        "DATE(ngaytao)=CURDATE() AND trangthai IN ('da_thanh_toan','da_giao')"

    );

} catch (Throwable $e) {

    $quickStats['revenue_today'] = 0;

}

try {

    $quickStats['orders_today'] = (int)$pdo->query(

        "SELECT COUNT(*) FROM donhang WHERE DATE(ngaytao)=CURDATE() AND trangthai='moi'"

    )->fetchColumn();

} catch (Throwable $e) {

    $quickStats['orders_today'] = 0;

}

try {

    $quickStats['low_stock'] = (int)$pdo->query(

        "SELECT COUNT(*) FROM tonkho WHERE soluong>0 AND soluong <= 10"

    )->fetchColumn();

} catch (Throwable $e) {

    $quickStats['low_stock'] = 0;

}

try {

    $baseProductSql = "SELECT COUNT(*) FROM sanpham";

    if (admin_index_col_exists($pdo, 'sanpham', 'trangthai')) {

        $baseProductSql .= " WHERE trangthai = 1";

    }

    $quickStats['total_products'] = (int)$pdo->query($baseProductSql)->fetchColumn();

} catch (Throwable $e) {

    $quickStats['total_products'] = 0;

}



$staffStats = [

    'total'  => 0,

    'active' => 0,

    'pause'  => 0,

    'left'   => 0,

];

try {

    $staffStats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM nhanvien")->fetchColumn();

    $staffStats['active'] = (int)$pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='dang_lam'")->fetchColumn();

    $staffStats['pause'] = (int)$pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='tam_nghi'")->fetchColumn();

    $staffStats['left'] = (int)$pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='da_nghi'")->fetchColumn();

} catch (Throwable $e) {

    $staffStats = ['total' => 0, 'active' => 0, 'pause' => 0, 'left' => 0];

}



$page_title = 'Trang Chủ - Quản Trị Nhà Thuốc';
$active = 'home'; 
require __DIR__ . '/partials/header.php'; // Gọi toàn bộ layout, CSS và hiệu ứng vào

$userName = $_SESSION['auth']['username'] ?? 'Nguyễn Văn A';
$roleCodes = $_SESSION['auth']['roles'] ?? [];
$roleCode = is_array($roleCodes) ? ($roleCodes[0] ?? null) : null;
$roleMap = [
    'admin' => 'Quản trị viên',
    'staff' => 'Nhân viên',
    'user'  => 'Người dùng',
];
$roleLabel = $roleMap[$roleCode] ?? ($roleCode ?: 'Quản trị viên');
?>

<!-- =============================================== -->
<!-- BẮT ĐẦU NỘI DUNG RIÊNG CỦA TRANG INDEX          -->
<!-- =============================================== -->
<main class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        <!-- Header của phần nội dung -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Chào mừng trở lại!</h2>
                <p class="text-gray-500 mt-1">Đây là trang quản trị của Nhà thuốc An Tâm.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                   <input type="search" placeholder="Tìm kiếm sản phẩm..." class="pl-10 pr-4 py-2 w-72 border border-gray-300 rounded-full bg-white shadow-sm focus:ring-2 focus:outline-none transition" style="--tw-ring-color: var(--primary-color)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                </div>
                <div class="flex items-center gap-3">
                     <img src="https://placehold.co/40x40/0284c7/FFFFFF?text=AD" alt="Avatar" class="rounded-full">
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars($userName) ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($roleLabel) ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Phần thẻ thông tin tổng quan -->
        <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-md border border-gray-200">
            <h3 class="text-xl font-semibold mb-4" style="color: var(--primary-dark);">Tổng quan nhanh</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Doanh thu hôm nay</h4>
                    <p class="text-2xl md:text-3xl font-bold mt-2 whitespace-nowrap" style="color: var(--primary-color)">
                        <?=money_vn($quickStats['revenue_today'])?>đ
                    </p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Đơn hàng mới</h4>
                    <p class="text-3xl font-bold text-sky-600 mt-2">
                        <?=number_format($quickStats['orders_today'], 0, ',', '.')?>
                    </p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Sắp hết hàng</h4>
                    <p class="text-3xl font-bold text-amber-600 mt-2">
                        <?=number_format($quickStats['low_stock'], 0, ',', '.')?>
                    </p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Tổng số sản phẩm</h4>
                    <p class="text-3xl font-bold text-slate-600 mt-2">
                        <?=number_format($quickStats['total_products'], 0, ',', '.')?>
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-md border border-gray-200 mt-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold" style="color: var(--primary-dark);">Tổng quan nhân viên</h3>
                <p class="text-sm text-gray-500">Dữ liệu thống kê từ bảng nhân viên</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Tổng nhân viên</h4>
                    <p class="text-3xl font-bold text-sky-700 mt-2">
                        <?=number_format($staffStats['total'], 0, ',', '.')?>
                    </p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Đang làm</h4>
                    <p class="text-3xl font-bold text-emerald-600 mt-2">
                        <?=number_format($staffStats['active'], 0, ',', '.')?>
                    </p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Tạm nghỉ</h4>
                    <p class="text-3xl font-bold text-amber-600 mt-2">
                        <?=number_format($staffStats['pause'], 0, ',', '.')?>
                    </p>
                </div>
                <div class="dashboard-card bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="text-gray-500 font-medium">Đã nghỉ</h4>
                    <p class="text-3xl font-bold text-slate-700 mt-2">
                        <?=number_format($staffStats['left'], 0, ',', '.')?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- =============================================== -->
<!-- KẾT THÚC NỘI DUNG RIÊNG CỦA TRANG INDEX         -->
<!-- =============================================== -->

<?php
// Đóng các thẻ HTML đã được mở trong header.php
?>
</div> <!-- Đóng thẻ div.flex.h-screen -->
</body>
</html>


