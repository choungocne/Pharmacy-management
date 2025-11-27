<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$pdo = new PDO(
    'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method == 'OPTIONS') {
    exit(0);
}

function sendResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data);
    exit;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['madm'])) {
            // Lấy một danh mục cụ thể
            $madm = (int)$_GET['madm'];
            $stmt = $pdo->prepare("
                SELECT dm.madm, dm.tendm, dm.cap, dm.parent_id, dm.img_url, p.tendm AS parent_name
                FROM danhmuc dm
                LEFT JOIN danhmuc p ON p.madm = dm.parent_id
                WHERE dm.madm = :madm
            ");
            $stmt->execute([':madm' => $madm]);
            $category = $stmt->fetch();
            if ($category) {
                sendResponse(['success' => true, 'data' => $category]);
            } else {
                sendResponse(['success' => false, 'error' => 'Không tìm thấy danh mục'], 404);
            }
        } else {
            // Lấy danh sách danh mục với phân trang và lọc
            $q = trim($_GET['q'] ?? '');
            $cap = (int)($_GET['cap'] ?? 0);
            $perPage = max(1, (int)($_GET['per'] ?? 9));
            $page = max(1, (int)($_GET['page'] ?? 1));

            $whereClauses = [];
            $execParams = [];

            if ($q !== '') {
                $whereClauses[] = "dm.tendm LIKE CONCAT('%', :q, '%')";
                $execParams[':q'] = $q;
            }

            if ($cap > 0) {
                $whereClauses[] = "dm.cap = :cap";
                $execParams[':cap'] = $cap;
            }

            $whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

            // Đếm tổng số
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM danhmuc dm $whereSql");
            $countStmt->execute($execParams);
            $total = (int)$countStmt->fetchColumn();

            $pages = max(1, ceil($total / $perPage));
            if ($page > $pages) $page = $pages;
            $offset = ($page - 1) * $perPage;

            // Lấy dữ liệu
            $sql = "
                SELECT dm.madm, dm.tendm, dm.cap, dm.parent_id, dm.img_url, p.tendm AS parent_name
                FROM danhmuc dm
                LEFT JOIN danhmuc p ON p.madm = dm.parent_id
                $whereSql
                ORDER BY dm.cap, dm.tendm
                LIMIT :lim OFFSET :off
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            foreach ($execParams as $key => $val) {
                $stmt->bindValue($key, $val, str_starts_with($key, ':cap') ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $data = $stmt->fetchAll();

            sendResponse([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'pages' => $pages,
                'current_page' => $page
            ]);
        }
        break;

    case 'POST':
        // Thêm danh mục mới
        if (!isset($input['tendm']) || !isset($input['cap'])) {
            sendResponse(['success' => false, 'error' => 'Thiếu dữ liệu bắt buộc'], 400);
        }

        $parent_id = isset($input['parent_id']) && $input['parent_id'] ? (int)$input['parent_id'] : null;
        $img_url = $input['img_url'] ?? null;

        if (($input['cap'] == 2 || $input['cap'] == 3) && !$parent_id) {
            sendResponse(['success' => false, 'error' => 'Cấp 2 và 3 cần danh mục cha'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO danhmuc (tendm, cap, parent_id, img_url)
            VALUES (:tendm, :cap, :parent_id, :img_url)
        ");
        $stmt->execute([
            ':tendm' => $input['tendm'],
            ':cap' => (int)$input['cap'],
            ':parent_id' => $parent_id,
            ':img_url' => $img_url
        ]);

        sendResponse(['success' => true, 'madm' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        // Cập nhật danh mục
        if (!isset($_GET['madm'])) {
            sendResponse(['success' => false, 'error' => 'Cần madm'], 400);
        }
        $madm = (int)$_GET['madm'];

        $parent_id = isset($input['parent_id']) && $input['parent_id'] ? (int)$input['parent_id'] : null;
        $img_url = $input['img_url'] ?? null;

        if (isset($input['cap']) && ($input['cap'] == 2 || $input['cap'] == 3) && !$parent_id) {
            sendResponse(['success' => false, 'error' => 'Cấp 2 và 3 cần danh mục cha'], 400);
        }

        $updates = [];
        $params = [':madm' => $madm];

        if (isset($input['tendm'])) {
            $updates[] = 'tendm = :tendm';
            $params[':tendm'] = $input['tendm'];
        }
        if (isset($input['cap'])) {
            $updates[] = 'cap = :cap';
            $params[':cap'] = (int)$input['cap'];
        }
        if (array_key_exists('parent_id', $input)) {
            $updates[] = 'parent_id = :parent_id';
            $params[':parent_id'] = $parent_id;
        }
        if (array_key_exists('img_url', $input)) {
            $updates[] = 'img_url = :img_url';
            $params[':img_url'] = $img_url;
        }

        if (empty($updates)) {
            sendResponse(['success' => false, 'error' => 'Không có dữ liệu để cập nhật'], 400);
        }

        $sql = "UPDATE danhmuc SET " . implode(', ', $updates) . " WHERE madm = :madm";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            sendResponse(['success' => true]);
        } else {
            sendResponse(['success' => false, 'error' => 'Không tìm thấy danh mục hoặc không có thay đổi'], 404);
        }
        break;

    case 'DELETE':
        // Xóa danh mục
        if (!isset($_GET['madm'])) {
            sendResponse(['success' => false, 'error' => 'Cần madm'], 400);
        }
        $madm = (int)$_GET['madm'];

        $stmt = $pdo->prepare("DELETE FROM danhmuc WHERE madm = :madm");
        $stmt->execute([':madm' => $madm]);

        if ($stmt->rowCount() > 0) {
            sendResponse(['success' => true]);
        } else {
            sendResponse(['success' => false, 'error' => 'Không tìm thấy danh mục'], 404);
        }
        break;

    default:
        sendResponse(['success' => false, 'error' => 'Phương thức không được hỗ trợ'], 405);
}
?>