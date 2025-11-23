<?php
require_once __DIR__ . '/../db.php';

$pdo = pdo();
$madl = $_GET['madl'] ?? 0;

if ($madl == 0) {
    echo "<h3>Không tìm thấy dược liệu</h3>";
    exit;
}

$sql = "SELECT * FROM duoclieu WHERE madl = :madl";
$st = $pdo->prepare($sql);
$st->execute([':madl' => $madl]);
$r = $st->fetch(PDO::FETCH_ASSOC);

if (!$r) {
    echo "<h3>Dược liệu không tồn tại</h3>";
    exit;
}

function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function resolve_image_url($url) { return $url ?: '/static/img/placeholder-duoclieu.jpg'; }
$base = ''; // Thay bằng base URL nếu cần

$img = resolve_image_url($r['anh_cau_truc'] ?? null);
$img_url = preg_match('~^https?://~i', $img) ? $img : (rtrim($base,'/') . $img);
?>

<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Chi tiết Dược liệu - <?= e($r['tendl']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: system-ui, sans-serif; margin: 20px; background: #f9f9f9; }
    .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { font-size: 24px; margin-bottom: 10px; }
    .meta { color: #555; font-size: 14px; margin-bottom: 20px; }
    .section { margin-bottom: 20px; }
    .section h2 { font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
    .section p { margin: 0 0 10px; line-height: 1.6; }
    img { max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <h1><?= e($r['tendl']) ?></h1>
    <div class="meta">
      Tên khác: <?= e($r['tenkhac'] ?? 'N/A') ?><br>
      Tên thực vật: <?= e($r['tenthucvat'] ?? 'N/A') ?><br>
      Cập nhật: <?= e($r['updated_at'] ?? $r['created_at'] ?? 'N/A') ?>
    </div>
    
    <img src="<?= e($img_url) ?>" alt="<?= e($r['tendl']) ?>" onerror="this.src='/static/img/placeholder-duoclieu.jpg'">
    
    <div class="section">
      <h2>Phân bố</h2>
      <p><?= nl2br(e($r['phanbo'] ?? 'N/A')) ?></p>
    </div>
    
    <div class="section">
      <h2>Bộ phận dùng</h2>
      <p><?= nl2br(e($r['boban'] ?? 'N/A')) ?></p>
    </div>
    
    <div class="section">
      <h2>Thành phần hóa học</h2>
      <p><?= nl2br(e($r['thanhphanhoahoc'] ?? 'N/A')) ?></p>
    </div>
    
    <div class="section">
      <h2>Tác dụng dược lý</h2>
      <p><?= nl2br(e($r['tacdungduocly'] ?? 'N/A')) ?></p>
    </div>
    
    <div class="section">
      <h2>Tính vị</h2>
      <p><?= e($r['tinhvi'] ?? 'N/A') ?></p>
    </div>
    
    <div class="section">
      <h2>Công dụng</h2>
      <p><?= nl2br(e($r['congdung'] ?? 'N/A')) ?></p>
    </div>
    
    <div class="section">
      <h2>Liều lượng & Cách dùng</h2>
      <p><?= nl2br(e($r['lieuluongcachdung'] ?? 'N/A')) ?></p>
    </div>
    
    <div class="section">
      <h2>Lưu ý</h2>
      <p><?= nl2br(e($r['luuy'] ?? 'N/A')) ?></p>
    </div>
  </div>
</body>
</html>
