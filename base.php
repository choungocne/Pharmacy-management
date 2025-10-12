<?php
// ==========================================
// BASE TEMPLATE CHO WEBSITE NHÀ THUỐC
// ==========================================

// Đường dẫn gốc của website (đặt đúng tên thư mục bạn trong htdocs)
$base_url = '/Pharmacy-management';

// Tránh lỗi biến chưa khai báo
$page_title = $page_title ?? 'Nhà Thuốc An Tâm';
$page_css   = $page_css   ?? '';
$page_js    = $page_js    ?? '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>

  <!-- CSS CHUNG -->
  <link rel="stylesheet" href="<?= $base_url ?>/static/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <!-- CSS RIÊNG (từng trang tự truyền vào) -->
  <?php if (!empty($page_css)) echo $page_css; ?>
</head>

<body>
  <!-- HEADER CHUNG -->
  <?php if (file_exists('templates/header.php')) include 'templates/header.php'; ?>

  <main>
        <?php
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
                $path = "pages/$page.php";

                if (file_exists($path)) {
                    include $path;
                } else {
                    echo "<p>Trang không tồn tại!</p>";
                }
            } else {
                include "pages/home.php"; // Trang mặc định
            }
        ?>
    </main>

  <!-- FOOTER CHUNG -->
  <?php if (file_exists('templates/footer.php')) include 'templates/footer.php'; ?>

  <!-- JS CHUNG -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $base_url ?>/static/js/main.js"></script>

  <!-- JS RIÊNG (từng trang tự truyền vào) -->
  <?php if (!empty($page_js)) echo $page_js; ?>
</body>
</html>
