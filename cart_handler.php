<?php
// ==========================================
// TEP: cart_handler.php
// Xu ly cac thao tac voi gio hang (Database + Session)
// ==========================================

// 1. CẤU HÌNH SESSION GIỐNG HỆT REGISTER.PHP ĐỂ KHÔNG BỊ MẤT SESSION
$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
    'path' => '/',
]);
session_start();

require_once __DIR__ . '/db.php';

// Hàm lấy thông tin người sở hữu giỏ hàng (Sửa lại để bắt đúng Session)
function cart_owner(): array {
    // Ưu tiên lấy từ auth[makh], nếu không có thì lấy makh trần, cuối cùng là null
    $makh = $_SESSION['auth']['makh'] ?? ($_SESSION['makh'] ?? null);
    $session_id = session_id();
    
    // Nếu có makh -> Lưu theo makh
    // Nếu không -> Lưu theo session_id
    $ownerField = $makh ? 'makh' : 'session_id';
    $ownerValue = $makh ?: $session_id;
    
    return [$ownerField, $ownerValue];
}

// Hàm đếm tổng số lượng item trong giỏ (Để cập nhật Header)
function count_cart_items($pdo, $ownerField, $ownerValue) {
    $sql = "SELECT COUNT(*) as total FROM giohang WHERE {$ownerField} = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ownerValue]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res['total'] ?? 0;
}

// Hàm lấy danh sách giỏ hàng (Để hiển thị trang giỏ hàng)
function cart_load_rows(PDO $pdo): array {
    [$field, $val] = cart_owner();
    $sql = "
    SELECT 
        g.id,
        g.masp,
        g.soluong AS cart_quantity,
        sp.tensp,
        sp.giaban,
        sp.hinhsp,
        sp.makm,
        dv.tendv,
        km.phantram_giam,
        km.gia_giam_co_dinh,
        COALESCE(SUM(tk.soluong), 0) AS tonkho_soluong
    FROM giohang g
    INNER JOIN sanpham sp ON g.masp = sp.masp
    LEFT JOIN khuyenmai km ON sp.makm = km.makm 
        AND km.trangthai_deal = 'dang_dien_ra'
        AND km.ngay_batdau <= NOW() 
        AND km.ngay_ketthuc >= NOW()
    LEFT JOIN donvitinh dv ON sp.madv = dv.madv
    LEFT JOIN tonkho tk ON tk.masp = sp.masp
    WHERE g.{$field} = ?
    GROUP BY 
        g.id, g.masp, cart_quantity, sp.tensp, sp.giaban, sp.hinhsp, 
        sp.makm, dv.tendv, km.phantram_giam, km.gia_giam_co_dinh
    ORDER BY g.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$val]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Hàm tính toán tổng tiền
function cart_summary(array $rows): array {
    $subtotal = 0;
    $discount_direct = 0;
    $count = 0;

    foreach ($rows as $r) {
        $qty   = (int)($r['cart_quantity'] ?? $r['soluong'] ?? 1);
        $price = (int)($r['giaban'] ?? 0);
        $line_base = $price * $qty;

        $pt  = (int)($r['phantram_giam'] ?? 0);
        $fix = (int)($r['gia_giam_co_dinh'] ?? 0);
        $disc_per_item = $fix > 0 ? $fix : ($pt > 0 ? (int)floor($price * $pt / 100) : 0);
        $line_disc = $disc_per_item * $qty;

        $subtotal += $line_base;
        $discount_direct += $line_disc;
        $count += $qty;
    }

    $after_direct = max(0, $subtotal - $discount_direct);
    
    // Giả lập voucher (nếu có session voucher)
    $voucher_value   = (int)($_SESSION['voucher_value']   ?? 0);
    $voucher_percent = (int)($_SESSION['voucher_percent'] ?? 0);
    $voucher_max     = (int)($_SESSION['voucher_max']     ?? 0);

    $discount_voucher = 0;
    if ($voucher_percent > 0) {
        $discount_voucher = (int)floor($after_direct * $voucher_percent / 100);
        if ($voucher_max > 0) $discount_voucher = min($discount_voucher, $voucher_max);
    } elseif ($voucher_value > 0) {
        $discount_voucher = $voucher_value;
    }
    $discount_voucher = min($discount_voucher, $after_direct);

    $shipping = 30000;
    if ($after_direct - $discount_voucher >= 300000) $shipping = 0;

    $grand_total = max(0, $after_direct - $discount_voucher + $shipping);

    return [
        'subtotal'         => $subtotal,
        'discount_direct'  => $discount_direct,
        'discount_voucher' => $discount_voucher,
        'shipping'         => $shipping,
        'grand_total'      => $grand_total,
        'free_ship'        => ($shipping === 0),
        'count'            => $count,
    ];
}

// --- BẮT ĐẦU XỬ LÝ REQUEST ---
header('Content-Type: application/json; charset=utf-8');
$pdo = pdo();
$response = ['success' => false, 'message' => ''];

$action = $_POST['action'] ?? $_GET['action'] ?? '';
[$ownerField, $ownerValue] = cart_owner();

try {
    switch ($action) {
        case 'view':
            $rows = cart_load_rows($pdo);
            $sum = cart_summary($rows);
            echo json_encode(['success' => true, 'items' => $rows] + $sum);
            exit;

        case 'add':
            // Lấy dữ liệu
            $masp = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($masp <= 0) throw new Exception('Sản phẩm không hợp lệ');
            
            // 1. Kiểm tra sản phẩm tồn tại
            $stmt = $pdo->prepare("SELECT masp FROM sanpham WHERE masp = ? AND trangthai = 1");
            $stmt->execute([$masp]);
            if (!$stmt->fetch()) throw new Exception('Sản phẩm không tồn tại hoặc ngừng kinh doanh');
            
            // 2. Kiểm tra tồn kho
            $stmt_stock = $pdo->prepare("SELECT soluong FROM tonkho WHERE masp = ?");
            $stmt_stock->execute([$masp]);
            $stock = $stmt_stock->fetch(PDO::FETCH_ASSOC);
            if (!$stock || $stock['soluong'] < $quantity) throw new Exception('Sản phẩm tạm hết hàng');
            
            // 3. Insert hoặc Update (Upsert)
            // Kiểm tra xem sản phẩm đã có trong giỏ của người này chưa
            $stmt_check = $pdo->prepare("SELECT id, soluong FROM giohang WHERE {$ownerField} = ? AND masp = ?");
            $stmt_check->execute([$ownerValue, $masp]);
            $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $new_quantity = $existing['soluong'] + $quantity;
                $stmt_update = $pdo->prepare("UPDATE giohang SET soluong = ?, updated_at = NOW() WHERE id = ?");
                $stmt_update->execute([$new_quantity, $existing['id']]);
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO giohang ({$ownerField}, masp, soluong) VALUES (?, ?, ?)");
                $stmt_insert->execute([$ownerValue, $masp, $quantity]);
            }
            
            // 4. Đếm lại tổng số item để cập nhật Header
            $total_items = count_cart_items($pdo, $ownerField, $ownerValue);
            
            $response['success'] = true;
            $response['message'] = 'Đã thêm vào giỏ hàng';
            // QUAN TRỌNG: Trả về đúng key mà JS ở home.php đang đợi (total_items)
            $response['total_items'] = $total_items; 
            break;
            
        case 'remove':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID không hợp lệ');
            
            $stmt = $pdo->prepare("DELETE FROM giohang WHERE id = ? AND {$ownerField} = ?");
            $stmt->execute([$id, $ownerValue]);
            
            $total_items = count_cart_items($pdo, $ownerField, $ownerValue);
            
            $response['success'] = true;
            $response['message'] = 'Đã xóa sản phẩm';
            $response['total_items'] = $total_items;
            break;
            
        case 'update':
        case 'update_quantity': // Gộp chung logic update
            $id = intval($_POST['id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($id <= 0) throw new Exception('ID không hợp lệ');
            
            if ($quantity <= 0) {
                // Xóa nếu số lượng <= 0
                $stmt = $pdo->prepare("DELETE FROM giohang WHERE id = ? AND {$ownerField} = ?");
                $stmt->execute([$id, $ownerValue]);
                $response['message'] = 'Đã xóa sản phẩm khỏi giỏ';
            } else {
                // Cập nhật
                $quantity = max(1, min(100, $quantity)); // Giới hạn 1-100
                $stmt = $pdo->prepare("UPDATE giohang SET soluong = ?, updated_at = NOW() WHERE id = ? AND {$ownerField} = ?");
                $stmt->execute([$quantity, $id, $ownerValue]);
                $response['message'] = 'Đã cập nhật số lượng';
            }
            
            // Nếu gọi từ trang giỏ hàng (update_quantity), trả về full data để render lại
            if ($action === 'update_quantity') {
                $rows = cart_load_rows($pdo);
                $sum = cart_summary($rows);
                echo json_encode(['success' => true] + $sum, JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $total_items = count_cart_items($pdo, $ownerField, $ownerValue);
            $response['success'] = true;
            $response['total_items'] = $total_items;
            break;
            
        case 'clear':
            $stmt = $pdo->prepare("DELETE FROM giohang WHERE {$ownerField} = ?");
            $stmt->execute([$ownerValue]);
            
            $response['success'] = true;
            $response['message'] = 'Đã xóa toàn bộ giỏ hàng';
            $response['total_items'] = 0;
            break;
            
        default:
            throw new Exception('Hành động không hợp lệ');
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} catch (Throwable $e) {
    $response['success'] = false;
    $response['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;