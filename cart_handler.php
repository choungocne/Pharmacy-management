<?php
// ==========================================
// TỆP: cart_handler.php
// Xử lý các thao tác với giỏ hàng (Database + Session)
// ==========================================

session_start();
require_once __DIR__ . '/db.php';

$pdo = pdo();
$response = ['success' => false, 'message' => ''];

// Lấy thông tin user (nếu đã đăng nhập)
$makh = $_SESSION['makh'] ?? null;
$session_id = session_id();

// Lấy action từ POST
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
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
            if ($makh) {
                // User đã đăng nhập
                $stmt_check = $pdo->prepare("SELECT id, soluong FROM giohang WHERE makh = ? AND masp = ?");
                $stmt_check->execute([$makh, $masp]);
                $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // Cập nhật số lượng
                    $new_quantity = $existing['soluong'] + $quantity;
                    $stmt_update = $pdo->prepare("UPDATE giohang SET soluong = ?, updated_at = NOW() WHERE id = ?");
                    $stmt_update->execute([$new_quantity, $existing['id']]);
                } else {
                    // Thêm mới
                    $stmt_insert = $pdo->prepare("INSERT INTO giohang (makh, masp, soluong) VALUES (?, ?, ?)");
                    $stmt_insert->execute([$makh, $masp, $quantity]);
                }
            } else {
                // Khách vãng lai
                $stmt_check = $pdo->prepare("SELECT id, soluong FROM giohang WHERE session_id = ? AND masp = ?");
                $stmt_check->execute([$session_id, $masp]);
                $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    $new_quantity = $existing['soluong'] + $quantity;
                    $stmt_update = $pdo->prepare("UPDATE giohang SET soluong = ?, updated_at = NOW() WHERE id = ?");
                    $stmt_update->execute([$new_quantity, $existing['id']]);
                } else {
                    $stmt_insert = $pdo->prepare("INSERT INTO giohang (session_id, masp, soluong) VALUES (?, ?, ?)");
                    $stmt_insert->execute([$session_id, $masp, $quantity]);
                }
            }
            
            // Đếm số lượng item trong giỏ
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM giohang WHERE " . 
                ($makh ? "makh = ?" : "session_id = ?"));
            $stmt_count->execute($makh ? [$makh] : [$session_id]);
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
            
            $stmt = $pdo->prepare("DELETE FROM giohang WHERE id = ? AND " . 
                ($makh ? "makh = ?" : "session_id = ?"));
            $stmt->execute($makh ? [$id, $makh] : [$id, $session_id]);
            
            // Đếm lại
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM giohang WHERE " . 
                ($makh ? "makh = ?" : "session_id = ?"));
            $stmt_count->execute($makh ? [$makh] : [$session_id]);
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
                $stmt = $pdo->prepare("DELETE FROM giohang WHERE id = ? AND " . 
                    ($makh ? "makh = ?" : "session_id = ?"));
                $stmt->execute($makh ? [$id, $makh] : [$id, $session_id]);
                $response['message'] = 'Đã xóa sản phẩm';
            } else {
                // Cập nhật số lượng
                $stmt = $pdo->prepare("UPDATE giohang SET soluong = ?, updated_at = NOW() WHERE id = ? AND " . 
                    ($makh ? "makh = ?" : "session_id = ?"));
                $stmt->execute($makh ? [$quantity, $id, $makh] : [$quantity, $id, $session_id]);
                $response['message'] = 'Đã cập nhật số lượng';
            }
            
            // Đếm lại
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM giohang WHERE " . 
                ($makh ? "makh = ?" : "session_id = ?"));
            $stmt_count->execute($makh ? [$makh] : [$session_id]);
            $cart_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
            
            $response['success'] = true;
            $response['cart_count'] = $cart_count;
            break;
            
        case 'clear':
            // Xóa toàn bộ giỏ hàng
            $stmt = $pdo->prepare("DELETE FROM giohang WHERE " . 
                ($makh ? "makh = ?" : "session_id = ?"));
            $stmt->execute($makh ? [$makh] : [$session_id]);
            
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
}

// Trả về JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;