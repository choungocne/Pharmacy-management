<?php
// =================================================================
// TỆP: search_results_duoclieu.php
// CHỨC NĂNG: Xử lý tìm kiếm và hiển thị kết quả DƯỢC LIỆU
// Đã sửa: Logic tìm kiếm AND/OR, phân trang, giao diện và Fix lỗi đường dẫn ảnh.
// =================================================================

// Đường dẫn tương đối từ tệp này đến db.php. Bạn có thể cần điều chỉnh
require_once 'db.php'; 

// --- KHAI BÁO CÁC HÀM CƠ BẢN VÀ BIẾN CẤU HÌNH ---
// Hàm escape HTML
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// HÀM QUAN TRỌNG: Xử lý đường dẫn ảnh.
// Các đường dẫn trong DB bắt đầu bằng '/Pharmacy-management', vì vậy $base = '' để tránh lặp lại.
$base = ''; 
// Nếu bạn muốn dùng base cho các tệp tĩnh khác (ví dụ: placeholder), hãy đặt đúng
$static_base = '/Pharmacy-management';

// Hàm xử lý ảnh placeholder nếu không tìm thấy ảnh gốc
function resolve_image_url($url, $static_base) { 
    if (empty($url) || !file_exists($_SERVER['DOCUMENT_ROOT'] . $url)) {
        return $static_base . '/static/img/placeholder-duoclieu.jpg'; 
    }
    return $url;
}


// --- THIẾT LẬP THAM SỐ PHÂN TRANG VÀ TÌM KIẾM ---
$pdo = pdo();
$tbl = 'duoclieu';

$q = trim($_GET['q'] ?? '');
$per = max(1, (int)($_GET['per_page'] ?? 20));
$per_page = $per; 
$page = max(1, (int)($_GET['page'] ?? 1));
$off = ($page - 1) * $per;

// Các trường để tìm kiếm trong Dược liệu
$fields = ['tendl', 'tenkhoahoc', 'tenkhac', 'mo_ta', 'bo_phan_dung', 'cong_dung'];
$rows = [];
$total = 0;
$total_pages = 1;

try {
    if ($q !== '') {
        // Tách từ khóa
        $keywords = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
        $where_clauses = [];
        $params = [];

        foreach ($keywords as $idx => $kw) {
            // Chuẩn hóa từ khóa tìm kiếm (bỏ dấu % trong từ khóa và thêm vào ngoài)
            $kw_clean = trim(mb_strtolower($kw, 'UTF-8'));
            $kw_like = "%$kw_clean%";
            $sub_clauses = [];
            foreach ($fields as $field) {
                // SỬ DỤNG LOWER() để đảm bảo tìm kiếm không phân biệt chữ hoa/thường trên mọi hệ thống
                $param_name = ":kw_{$idx}_{$field}";
                $sub_clauses[] = "LOWER($field) LIKE $param_name";
                $params[$param_name] = $kw_like;
            }
            $where_clauses[] = '(' . implode(' OR ', $sub_clauses) . ')';
        }

        $where_sql = 'WHERE trangthai=1 AND ' . implode(' AND ', $where_clauses);
        $order_sql = 'ORDER BY updated_at DESC, tendl ASC';
        $limit_sql = 'LIMIT :lim OFFSET :off';

        // 1. Query chính
        $sql = "
            SELECT madl, tendl, tenkhoahoc, tenkhac, mo_ta, bo_phan_dung, cong_dung, hinh_anh, trangthai, created_at, updated_at
            FROM `$tbl`
            $where_sql
            $order_sql
            $limit_sql
        ";
        $st = $pdo->prepare($sql);
        foreach ($params as $pname => $pval) {
            $st->bindValue($pname, $pval, PDO::PARAM_STR);
        }
        $st->bindValue(':lim', $per, PDO::PARAM_INT);
        $st->bindValue(':off', $off, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();

        // 2. Query đếm
        $sql_count = "
            SELECT COUNT(*) FROM `$tbl`
            $where_sql
        ";
        $stc = $pdo->prepare($sql_count);
        foreach ($params as $pname => $pval) {
            $stc->bindValue($pname, $pval, PDO::PARAM_STR);
        }
        $stc->execute();
        $total = (int)$stc->fetchColumn();
    } else {
        // Trường hợp không có từ khóa tìm kiếm
        $st = $pdo->prepare("
            SELECT madl, tendl, tenkhoahoc, tenkhac, mo_ta, bo_phan_dung, cong_dung, hinh_anh, trangthai, created_at, updated_at
            FROM `$tbl` WHERE trangthai=1
            ORDER BY updated_at DESC, tendl ASC
            LIMIT :lim OFFSET :off
        ");
        $st->bindValue(':lim', $per, PDO::PARAM_INT);
        $st->bindValue(':off', $off, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();
        $total = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl` WHERE trangthai=1")->fetchColumn();
    }

    $total_pages = (int) ceil($total / $per_page); 
} catch (PDOException $ex) {
    // Để debug nếu có lỗi
    echo "<h1>Lỗi Cơ Sở Dữ Liệu</h1><p>Vui lòng kiểm tra kết nối PDO hoặc cú pháp SQL: " . e($ex->getMessage()) . "</p>"; 
    error_log("DB Error in Duoc Lieu search: " . $ex->getMessage());
    $rows = [];
    $total = 0;
    $total_pages = 1;
}

$total_found_on_page = count($rows);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm Dược Liệu<?= $q !== '' ? ' - ' . e($q) : '' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === STYLE TỪ FILE TRƯỚC (Dược chất) ĐỂ GIAO DIỆN ĐẸP HƠN === */
        
        /* RESET và CƠ BẢN */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #11998e; /* Màu xanh lá đậm cho Dược liệu */
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 28px;
        }

        /* 1. FORM TÌM KIẾM ĐẸP */
        .search {
            display: flex;
            max-width: 600px;
            margin: 0px auto 40px auto; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            background-color: white;
        }
        .search input[type="text"] {
            flex-grow: 1;
            padding: 15px 20px;
            border: none;
            font-size: 16px;
            outline: none;
            transition: box-shadow 0.3s ease;
        }
        .search input[type="text"]:focus {
            box-shadow: inset 0 0 5px rgba(17, 153, 142, 0.2);
        }
        .search button {
            background-color: #11998e;
            color: white;
            padding: 15px 25px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.1s ease;
        }
        .search button:hover {
            background-color: #0c6a61;
        }
        .search button:active {
            transform: scale(0.98);
        }

        /* 2. KHU VỰC THÔNG TIN (COUNT) */
        .count {
            max-width: 1200px;
            margin: 20px auto;
            padding: 15px;
            background-color: #e8f8f5;
            border-left: 5px solid #11998e;
            border-radius: 4px;
            font-size: 15px;
            color: #0c6a61;
        }
        .count strong {
            font-weight: 700;
            color: #11998e;
        }
        .count code.k {
            background-color: #d1f7f0;
            padding: 2px 6px;
            border-radius: 3px;
            color: #074743;
        }

        /* 3. GRID KẾT QUẢ ĐẸP */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 40px auto;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .card .thumb {
            width: 100%;
            height: 200px;
            object-fit: contain; /* Dùng contain để ảnh dược liệu không bị crop */
            background-color: #f8f8f8;
            border-bottom: 1px solid #eee;
            padding: 15px;
        }
        .card .content {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .card .title {
            font-size: 1.1em;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        .card .meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
            border-left: 3px solid #8fe7da;
            padding-left: 8px;
        }

        /* 4. PHÂN TRANG (PAGER) ĐẸP */
        .pager {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 30px auto;
            max-width: 1200px;
        }
        .pager a, .pager span {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .pager a {
            background-color: #fff;
            color: #11998e;
            border: 1px solid #11998e;
        }
        .pager a:hover {
            background-color: #e8f8f5;
        }
        .pager span.active {
            background-color: #11998e;
            color: white;
            border: 1px solid #11998e;
        }
        .empty {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 8px;
            max-width: 600px;
            margin: 40px auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            color: #777;
        }
        .empty code.k {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <h1><i class="fas fa-leaf"></i> Kết quả tra cứu Dược Liệu</h1>


    <div class="count">
        Tìm thấy <strong><?= (int)$total ?></strong> bản ghi<?= $q!=='' ? ' cho từ khóa <code class="k">'.e($q).'</code>' : '' ?>.
        Trang <?= (int)$page ?>/<?= (int)$total_pages ?> • Mỗi trang <?= (int)$per_page ?> mục.
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty">
            Không có kết quả phù hợp. Thử từ khóa khác (ví dụ: 
            <code class="k">Cam Thảo</code>, <code class="k">Lá Sen</code>, 
            <code class="k">Bổ máu</code>...).
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($rows as $r): 
                $img = resolve_image_url($r['hinh_anh'] ?? null, $static_base);
                
                // Gán $base = '' nên $img_url sẽ là giá trị của $img
                $img_url = preg_match('~^https?://~i', $img) ? $img : (rtrim($base,'/') . $img);
            ?>
            <article class="card">
                <img class="thumb"
                     src="<?= e($img_url) ?>"
                     alt="<?= e($r['tendl'] ?? '') ?>"
                     onerror="this.onerror=null;this.src='<?= e($static_base) ?>/static/img/placeholder-duoclieu.jpg'">
                <div class="content">
                    <div class="title"><?= e($r['tendl'] ?? '') ?></div>
                    <?php if (!empty($r['tenkhoahoc'])): ?>
                        <div class="meta">Khoa học: <em><?= e($r['tenkhoahoc']) ?></em></div>
                    <?php endif; ?>
                    <?php if (!empty($r['tenkhac'])): ?>
                        <div class="meta">Tên khác: <?= e($r['tenkhac']) ?></div>
                    <?php endif; ?>
                    <div class="meta">Bộ phận dùng: <strong><?= e($r['bo_phan_dung'] ?? 'N/A') ?></strong></div>
                    <?php if (!empty($r['cong_dung'])): ?>
                        <div class="meta">Công dụng: <?= e(substr($r['cong_dung'], 0, 100)) . (strlen($r['cong_dung']) > 100 ? '...' : '') ?></div>
                    <?php endif; ?>
                    <div class="meta">Cập nhật: <?= e($r['updated_at'] ?? $r['created_at'] ?? '') ?></div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php
          // Tạo Query String cho Phân trang
          $qs = $_GET;
          $qs['per_page'] = $per_page;
        ?>
        <div class="pager">
            <?php if ($page > 1): $qs['page'] = 1; ?>
                <a href="?<?= http_build_query($qs) ?>">« Đầu</a>
            <?php endif; ?>
            <?php if ($page > 1): $qs['page'] = $page - 1; ?>
                <a href="?<?= http_build_query($qs) ?>">‹ Trước</a>
            <?php endif; ?>
            <span class="active"><?= (int)$page ?></span>
            <?php if ($page < $total_pages): $qs['page'] = $page + 1; ?>
                <a href="?<?= http_build_query($qs) ?>">Sau ›</a>
            <?php endif; ?>
            <?php if ($page < $total_pages): $qs['page'] = $total_pages; ?>
                <a href="?<?= http_build_query($qs) ?>">Cuối »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>
</html>