<?php
// api/products.php
// Include db.php để sử dụng hàm pdo()
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  exit(0);
}

$pdo = pdo();

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$id = $request[0] ?? null;

// Get parameters for GET requests
$q = trim($_GET['q'] ?? '');
$dm = (int)($_GET['dm'] ?? 0);
$perPage = max(1, (int)($_GET['per'] ?? 9));
$page = max(1, (int)($_GET['page'] ?? 1));

switch ($method) {
  case 'GET':
    if ($id) {
      // Get single product
      $stmt = $pdo->prepare("
        SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam, sp.hinhsp, sp.congdung, sp.xuatxu, sp.cachdung, sp.requires_rx,
               sp.madv, sp.math, sp.mancc, sp.mamv, sp.madm, sp.trangthai, sp.created_at, sp.updated_at, sp.thanhphan, sp.hamluong,
               sp.chidinh, sp.chongchidinh, sp.tacdungphu, sp.tuongtac, sp.luuy, sp.baoche, sp.donggoi, sp.soluong, sp.giatri, 
               dm.tendm AS danhmuc, dv.tendv AS donvitinh, th.tenth AS thuonghieu, ncc.tenncc AS nhacungcap, mv.tenmv AS muivi,
               tk.soluong AS tonkho, tk.hsd
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON dm.madm = sp.madm
        LEFT JOIN donvitinh dv ON dv.madv = sp.madv
        LEFT JOIN thuonghieu th ON th.math = sp.math
        LEFT JOIN nhacungcap ncc ON ncc.mancc = sp.mancc
        LEFT JOIN muivi mv ON mv.mamv = sp.mamv
        LEFT JOIN tonkho tk ON tk.masp = sp.masp
        WHERE sp.masp = ?
      ");
      $stmt->execute([$id]);
      $product = $stmt->fetch();
      echo json_encode($product ? $product : ['error' => 'Product not found']);
    } else {
      // Get all products with filtering and pagination
      $countSql = "
        SELECT COUNT(*) FROM sanpham sp
        WHERE (:q='' OR sp.tensp LIKE CONCAT('%',:q,'%'))
          AND (:dm=0 OR sp.madm=:dm)";
      $cst = $pdo->prepare($countSql);
      $cst->execute([':q' => $q, ':dm' => $dm]);
      $totalFiltered = (int)$cst->fetchColumn();

      $pages = max(1, (int)ceil($totalFiltered / $perPage));
      $offset = ($page - 1) * $perPage;

      $sql = "
        SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam, sp.hinhsp, sp.congdung, sp.xuatxu, sp.cachdung, sp.requires_rx,
               sp.madv, sp.math, sp.mancc, sp.mamv, sp.madm, sp.trangthai, sp.created_at, sp.updated_at, sp.thanhphan, sp.hamluong,
               sp.chidinh, sp.chongchidinh, sp.tacdungphu, sp.tuongtac, sp.luuy, sp.baoche, sp.donggoi, sp.soluong, sp.giatri, 
               dm.tendm AS danhmuc, dv.tendv AS donvitinh, th.tenth AS thuonghieu, ncc.tenncc AS nhacungcap, mv.tenmv AS muivi,
               tk.soluong AS tonkho, tk.hsd
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON dm.madm = sp.madm
        LEFT JOIN donvitinh dv ON dv.madv = sp.madv
        LEFT JOIN thuonghieu th ON th.math = sp.math
        LEFT JOIN nhacungcap ncc ON ncc.mancc = sp.mancc
        LEFT JOIN muivi mv ON mv.mamv = sp.mamv
        LEFT JOIN tonkho tk ON tk.masp = sp.masp
        WHERE (:q='' OR sp.tensp LIKE CONCAT('%',:q,'%'))
          AND (:dm=0 OR sp.madm=:dm)
        ORDER BY sp.tensp
        LIMIT :lim OFFSET :off
      ";
      $st = $pdo->prepare($sql);
      $st->bindValue(':q', $q);
      $st->bindValue(':dm', $dm, PDO::PARAM_INT);
      $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
      $st->bindValue(':off', $offset, PDO::PARAM_INT);
      $st->execute();
      $products = $st->fetchAll();

      echo json_encode([
        'total' => $totalFiltered,
        'pages' => $pages,
        'current_page' => $page,
        'data' => $products
      ]);
    }
    break;

  case 'POST':
    // Create product
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
      echo json_encode(['error' => 'Invalid JSON data']);
      exit;
    }

    $required_fields = ['tensp', 'giaban', 'madm'];
    foreach ($required_fields as $field) {
      if (!isset($data[$field])) {
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
      }
    }

    try {
      $stmt = $pdo->prepare("
        INSERT INTO sanpham (tensp, giaban, giagiam, hinhsp, congdung, xuatxu, cachdung, requires_rx, madv, math, mancc, mamv, madm, trangthai, thanhphan, hamluong, chidinh, chongchidinh, tacdungphu)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $data['tensp'],
        $data['giaban'],
        $data['giagiam'] ?? 0,
        $data['hinhsp'] ?? '',
        $data['congdung'] ?? '',
        $data['xuatxu'] ?? '',
        $data['cachdung'] ?? '',
        $data['requires_rx'] ?? 0,
        $data['madv'] ?? null,
        $data['math'] ?? null,
        $data['mancc'] ?? null,
        $data['mamv'] ?? null,
        $data['madm'],
        $data['thanhphan'] ?? '',
        $data['hamluong'] ?? '',
        $data['chidinh'] ?? '',
        $data['chongchidinh'] ?? '',
        $data['tacdungphu'] ?? ''
      ]);
      $newId = $pdo->lastInsertId();
      echo json_encode(['success' => true, 'id' => $newId]);
    } catch (Exception $e) {
      echo json_encode(['error' => 'Error creating product: ' . $e->getMessage()]);
    }
    break;

  case 'PUT':
    if (!$id) {
      echo json_encode(['error' => 'ID required for update']);
      exit;
    }
    // Update product
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
      echo json_encode(['error' => 'Invalid JSON data']);
      exit;
    }

    $fields = [];
    $params = [];
    foreach (['tensp', 'giaban', 'giagiam', 'hinhsp', 'congdung', 'xuatxu', 'cachdung', 'requires_rx', 'madv', 'math', 'mancc', 'mamv', 'madm', 'thanhphan', 'hamluong', 'chidinh', 'chongchidinh', 'tacdungphu'] as $field) {
      if (isset($data[$field])) {
        $fields[] = "$field = ?";
        $params[] = $data[$field];
      }
    }
    if (empty($fields)) {
      echo json_encode(['error' => 'No fields to update']);
      exit;
    }
    $sql = "UPDATE sanpham SET " . implode(', ', $fields) . " WHERE masp = ?";
    $params[] = $id;
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      echo json_encode(['success' => true]);
    } catch (Exception $e) {
      echo json_encode(['error' => 'Error updating product: ' . $e->getMessage()]);
    }
    break;

  case 'DELETE':
    if (!$id) {
      echo json_encode(['error' => 'ID required for delete']);
      exit;
    }
    // Delete product
    try {
      $stmt = $pdo->prepare("DELETE FROM sanpham WHERE masp = ?");
      $stmt->execute([$id]);
      echo json_encode(['success' => true]);
    } catch (Exception $e) {
      echo json_encode(['error' => 'Error deleting product: ' . $e->getMessage()]);
    }
    break;

  default:
    echo json_encode(['error' => 'Method not supported']);
    break;
}
?>