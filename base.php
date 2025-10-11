<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhà Thuốc An Tâm</title>
    <link rel="stylesheet" href="static/css/style.css">

</head>
<body>
    <?php include 'header.php'; ?>

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
                include "pages/index.php"; // Trang mặc định
            }
        ?>
    </main>

    <?php include 'footer.php'; ?>
    
</body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="static/js/script.js"></script>

</html>
