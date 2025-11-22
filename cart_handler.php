<?php
// ==========================================
// TEP: cart_handler.php
// Xu ly cac thao tac voi gio hang (Database + Session)
// ==========================================

session_start();
require_once __DIR__ . '/db.php';

$pdo = pdo();
header('Content-Type: application/json; charset=utf-8');

function cart_owner(): array {
    $makh = $_SESSION['makh'] ?? null;
    $session_id = session_id();
    $ownerField = $makh ? 'makh' : 'session_id';
    $ownerValue = $makh ?: $session_id;
    return [$ownerField, $ownerValue];
}

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
        g.id,
        g.masp,
        cart_quantity,
        sp.tensp,
        sp.giaban,
        sp.hinhsp,
        sp.makm,
        dv.tendv,
        km.phantram_giam,
        km.gia_giam_co_dinh
    ORDER BY g.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$val]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

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

    // Voucher trong session (tùy dự án, dùng các key sau nếu có)
    $voucher_value   = (int)($_SESSION['voucher_value']   ?? 0); // giảm cố định
    $voucher_percent = (int)($_SESSION['voucher_percent'] ?? 0); // %
    $voucher_max     = (int)($_SESSION['voucher_max']     ?? 0); // trần giảm

    $discount_voucher = 0;
    if ($voucher_percent > 0) {
        $discount_voucher = (int)floor($after_direct * $voucher_percent / 100);
        if ($voucher_max > 0) {
            $discount_voucher = min($discount_voucher, $voucher_max);
        }
    } elseif ($voucher_value > 0) {
        $discount_voucher = $voucher_value;
    }
    $discount_voucher = min($discount_voucher, $after_direct);

    $shipping = 30000;
    if ($after_direct - $discount_voucher >= 300000) {
        $shipping = 0;
    }

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

$response = ['success' => false, 'message' => ''];

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : (int)($_GET['id'] ?? 0);
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : (int)($_GET['quantity'] ?? 0);

[$ownerField, $ownerValue] = cart_owner();
$makh = $_SESSION['makh'] ?? null;
$session_id = session_id();

try {
    switch ($action) {
        case 'view':
            $rows = cart_load_rows($pdo);
            $sum = cart_summary($rows);
            echo json_encode(['success' => true, 'items' => $rows] + $sum);
            exit;

        case 'add':
            // Thêm sản phẩm vào giỏ
            $masp = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($masp <= 0) {
                throw new Exception('Thiếu thông tin sản phẩm');
            }
            
            // Lấy thông tin sản phẩm từ DB
            $stmt = $pdo->prepare("SELECT masp, tensp, giaban, hinhsp FROM sanpham WHERE masp = ? AND trangthai = 1");
            $stmt->execute([$masp]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception('Sản phẩm không tồn tại');
            }
            
            // Kiểm tra tồn kho
            $stmt_stock = $pdo->prepare("SELECT soluong FROM tonkho WHERE masp = ?");
            $stmt_stock->execute([$masp]);
            $stock = $stmt_stock->fetch(PDO::FETCH_ASSOC);
            
            if (!$stock || $stock['soluong'] < $quantity) {
                throw new Exception('Sản phẩm không đủ số lượng trong kho');
            }
            
            // Thêm/Cập nhật vào database
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
            
            // Đếm số lượng item trong giỏ
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM giohang WHERE {$ownerField} = ?");
            $stmt_count->execute([$ownerValue]);
            $cart_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
            
            $response['success'] = true;
            $response['message'] = 'Đã thêm vào giỏ hàng';
            $response['cart_count'] = $cart_count;
            break;
            
        case 'remove':
            // Xóa sản phẩm khỏi giỏ
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID không hợp lệ');
            }
            
            $stmt = $pdo->prepare("DELETE FROM giohang WHERE id = ? AND {$ownerField} = ?");
            $stmt->execute([$id, $ownerValue]);
            
            // Đếm lại
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM giohang WHERE {$ownerField} = ?");
            $stmt_count->execute([$ownerValue]);
            $cart_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
            
            $response['success'] = true;
            $response['message'] = 'Đã xóa sản phẩm';
            $response['cart_count'] = $cart_count;
            break;
            
        case 'update':
            // Cập nhật số lượng
            $id = intval($_POST['id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($id <= 0) {
                throw new Exception('ID không hợp lệ');
            }
            
            if ($quantity <= 0) {
                // Xóa nếu số lượng = 0
                $stmt = $pdo->prepare("DELETE FROM giohang WHERE id = ? AND {$ownerField} = ?");
                $stmt->execute([$id, $ownerValue]);
                $response['message'] = 'Đã xóa sản phẩm';
            } else {
                // Cập nhật số lượng
                $quantity = max(1, min(100, $quantity));
                $stmt = $pdo->prepare("UPDATE giohang SET soluong = ?, updated_at = NOW() WHERE id = ? AND {$ownerField} = ?");
                $stmt->execute([$quantity, $id, $ownerValue]);
                $response['message'] = 'Đã cập nhật số lượng';
            }
            
            // Đếm lại
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM giohang WHERE {$ownerField} = ?");
            $stmt_count->execute([$ownerValue]);
            $cart_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
            
            $response['success'] = true;
            $response['cart_count'] = $cart_count;
            break;

        case 'update_quantity':
            if ($id <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $quantity = max(1, min(100, $quantity));

            $stmt = $pdo->prepare("UPDATE giohang SET soluong = :qty, updated_at = NOW() WHERE id = :id AND {$ownerField} = :owner");
            $stmt->execute([
                ':qty' => $quantity,
                ':id' => $id,
                ':owner' => $ownerValue,
            ]);

            if ($stmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $rows = cart_load_rows($pdo);
            $sum = cart_summary($rows);
            echo json_encode(['success' => true] + $sum, JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'clear':
            // Xóa toàn bộ giỏ hàng
            $stmt = $pdo->prepare("DELETE FROM giohang WHERE {$ownerField} = ?");
            $stmt->execute([$ownerValue]);
            
            $response['success'] = true;
            $response['message'] = 'Đã xóa toàn bộ giỏ hàng';
            $response['cart_count'] = 0;
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống, vui lòng thử lại'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Trả về JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
