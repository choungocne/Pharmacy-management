<?php
require_once __DIR__ . '/../db.php'; // Đổi nếu đường dẫn sai

// Định nghĩa tạm nếu chưa có ở base.php
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function resolve_image_url($url) { return $url ?: '/static/img/placeholder-duocchat.jpg'; }
$base = ''; // Thay bằng base URL nếu cần, ví dụ '/Pharmacy-management/'

$pdo = pdo();

// Tự xác định tên bảng
$tbl = 'duocchat';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($tbl, $tables) && in_array('duoc_chat', $tables)) $tbl = 'duoc_chat';

$q = trim($_GET['q'] ?? '');
$per = max(1, (int)($_GET['per_page'] ?? 20));
$per_page = $per; // Fix undefined
$page = max(1, (int)($_GET['page'] ?? 1));
$off = ($page - 1) * $per;

$fields = ['tendc', 'tenkhac', 'congthuchoa', 'tacdung', 'co_che_tac_dung', 'phan_loai'];

try {
    if ($q !== '') {
        // Split q thành keywords
        $keywords = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
        $where_clauses = [];
        $params = [];

        foreach ($keywords as $idx => $kw) {
            $kw_like = "%$kw%";
            $sub_clauses = [];
            foreach ($fields as $field) {
                $param_name = ":kw_{$idx}_{$field}";
                $sub_clauses[] = "$field COLLATE utf8mb4_unicode_ci LIKE $param_name";
                $params[$param_name] = $kw_like;
            }
            $where_clauses[] = '(' . implode(' OR ', $sub_clauses) . ')';
        }

        $where_sql = 'WHERE trangthai=1 AND ' . implode(' AND ', $where_clauses);
        $order_sql = 'ORDER BY updated_at DESC, tendc ASC';
        $limit_sql = 'LIMIT :lim OFFSET :off';

        // Query chính
        $sql = "
            SELECT madc, tendc, tenkhac, congthuchoa, tacdung, co_che_tac_dung, phan_loai, anh_cau_truc, trangthai, created_at, updated_at
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

        // Query đếm
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
        $st = $pdo->prepare("
            SELECT madc, tendc, tenkhac, congthuchoa, tacdung, co_che_tac_dung, phan_loai, anh_cau_truc, trangthai, created_at, updated_at
            FROM `$tbl` WHERE trangthai=1
            ORDER BY updated_at DESC, tendc ASC
            LIMIT :lim OFFSET :off
        ");
        $st->bindValue(':lim', $per, PDO::PARAM_INT);
        $st->bindValue(':off', $off, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();
        $total = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl` WHERE trangthai=1")->fetchColumn();
    }

    $total_pages = (int) ceil($total / $per_page); // Fix undefined
} catch (PDOException $ex) {
    echo "Lỗi DB: " . $ex->getMessage(); // Để debug
    $rows = [];
    $total = 0;
    $total_pages = 1;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Kết quả Dược chất<?= $q !== '' ? ' - ' . e($q) : '' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* Giữ nguyên style */
  </style>
</head>
<body>
  <h1>Kết quả Dược chất</h1>

  <div class="count">
    Tìm thấy <strong><?= (int)$total ?></strong> bản ghi<?= $q!=='' ? ' cho từ khóa <code class="k">'.e($q).'</code>' : '' ?>.
    Trang <?= (int)$page ?>/<?= (int)$total_pages ?> • Mỗi trang <?= (int)$per_page ?> mục.
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty">Không có kết quả phù hợp. Thử từ khóa khác (ví dụ: <code class="k">aspirin</code>, <code class="k">ibuprofen</code>, <code class="k">amoxicillin</code>...).</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($rows as $r): 
        $img = resolve_image_url($r['anh_cau_truc'] ?? null);
        $img_url = preg_match('~^https?://~i', $img) ? $img : (rtrim($base,'/') . $img);
      ?>
      <article class="card">
        <img class="thumb"
             src="<?= e($img_url) ?>"
             alt="<?= e($r['tendc'] ?? '') ?>"
             onerror="this.onerror=null;this.src='<?= e(rtrim($base,'/')) ?>/static/img/placeholder-duocchat.jpg'">
        <div class="content">
          <div class="title"><?= e($r['tendc'] ?? '') ?></div>
          <?php if (!empty($r['tenkhac'])): ?>
            <div class="meta">Tên khác: <?= e($r['tenkhac']) ?></div>
          <?php endif; ?>
          <?php if (!empty($r['congthuchoa'])): ?>
            <div class="meta">CTHH: <?= e($r['congthuchoa']) ?></div>
          <?php endif; ?>
          <?php if (!empty($r['phan_loai'])): ?>
            <div class="meta">Phân loại: <?= e($r['phan_loai']) ?></div>
          <?php endif; ?>
          <?php if (!empty($r['tacdung'])): ?>
            <div class="meta">Tác dụng: <?= e($r['tacdung']) ?></div>
          <?php endif; ?>
          <?php if (!empty($r['co_che_tac_dung'])): ?>
            <div class="meta">Cơ chế: <?= e($r['co_che_tac_dung']) ?></div>
          <?php endif; ?>
          <div class="meta">Cập nhật: <?= e($r['updated_at'] ?? $r['created_at'] ?? '') ?></div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <?php
      // Pager query string
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
<style>
    /* RESET và CƠ BẢN */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f7f6;
        color: #333;
        margin: 0;
        padding: 20px;
    }
    h1 {
        color: #007bff;
        text-align: center;
        margin-bottom: 30px;
        font-weight: 600;
        font-size: 28px;
    }

    /* 1. FORM TÌM KIẾM ĐẸP */
    .search {
        display: flex;
        max-width: 600px;
        margin: 0px auto 40px auto; /* Thêm margin trên và dưới */
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
        box-shadow: inset 0 0 5px rgba(0, 123, 255, 0.2);
    }
    .search button {
        background-color: #007bff;
        color: white;
        padding: 15px 25px;
        border: none;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: background-color 0.3s ease, transform 0.1s ease;
    }
    .search button:hover {
        background-color: #0056b3;
    }
    .search button:active {
        transform: scale(0.98);
    }

    /* 2. KHU VỰC THÔNG TIN (COUNT) */
    .count {
        max-width: 1200px;
        margin: 20px auto;
        padding: 15px;
        background-color: #e9f5ff;
        border-left: 5px solid #007bff;
        border-radius: 4px;
        font-size: 15px;
        color: #0056b3;
    }
    .count strong {
        font-weight: 700;
        color: #007bff;
    }
    .count code.k {
        background-color: #d1e7ff;
        padding: 2px 6px;
        border-radius: 3px;
        color: #004085;
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
        object-fit: cover;
        background-color: #f8f8f8;
        border-bottom: 1px solid #eee;
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
        border-left: 3px solid #ced4da;
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
        color: #007bff;
        border: 1px solid #007bff;
    }
    .pager a:hover {
        background-color: #e9f5ff;
    }
    .pager span.active {
        background-color: #007bff;
        color: white;
        border: 1px solid #007bff;
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