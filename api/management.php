// File: api_management.php
<?php
require __DIR__ . '/db.php'; // Kết nối PDO từ db.php

$pdo = pdo(); // Lấy kết nối PDO

// Xử lý API đơn giản (CRUD qua POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];

    try {
        if ($action === 'add_danhmuc') {
            $tendm = $_POST['tendm'];
            $cap = (int)$_POST['cap'];
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $img_url = $_POST['img_url'] ?? null;

            $stmt = $pdo->prepare("INSERT INTO danhmuc (tendm, cap, parent_id, img_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tendm, $cap, $parent_id, $img_url]);
            $response['success'] = true;
            $response['message'] = 'Thêm danh mục thành công';
        } elseif ($action === 'edit_danhmuc') {
            $madm = (int)$_POST['madm'];
            $tendm = $_POST['tendm'];
            $cap = (int)$_POST['cap'];
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $img_url = $_POST['img_url'] ?? null;

            $stmt = $pdo->prepare("UPDATE danhmuc SET tendm = ?, cap = ?, parent_id = ?, img_url = ? WHERE madm = ?");
            $stmt->execute([$tendm, $cap, $parent_id, $img_url, $madm]);
            $response['success'] = true;
            $response['message'] = 'Sửa danh mục thành công';
        } elseif ($action === 'delete_danhmuc') {
            $madm = (int)$_POST['madm'];

            $stmt = $pdo->prepare("DELETE FROM danhmuc WHERE madm = ?");
            $stmt->execute([$madm]);
            $response['success'] = true;
            $response['message'] = 'Xóa danh mục thành công';
        } elseif ($action === 'add_thuonghieu') {
            $tenth = $_POST['tenth'];
            $logo_url = $_POST['logo_url'] ?? null;

            $stmt = $pdo->prepare("INSERT INTO thuonghieu (tenth, logo_url) VALUES (?, ?)");
            $stmt->execute([$tenth, $logo_url]);
            $response['success'] = true;
            $response['message'] = 'Thêm thương hiệu thành công';
        } elseif ($action === 'edit_thuonghieu') {
            $math = (int)$_POST['math'];
            $tenth = $_POST['tenth'];
            $logo_url = $_POST['logo_url'] ?? null;

            $stmt = $pdo->prepare("UPDATE thuonghieu SET tenth = ?, logo_url = ? WHERE math = ?");
            $stmt->execute([$tenth, $logo_url, $math]);
            $response['success'] = true;
            $response['message'] = 'Sửa thương hiệu thành công';
        } elseif ($action === 'delete_thuonghieu') {
            $math = (int)$_POST['math'];

            $stmt = $pdo->prepare("DELETE FROM thuonghieu WHERE math = ?");
            $stmt->execute([$math]);
            $response['success'] = true;
            $response['message'] = 'Xóa thương hiệu thành công';
        } elseif ($action === 'add_donvitinh') {
            $tendv = $_POST['tendv'];

            $stmt = $pdo->prepare("INSERT INTO donvitinh (tendv) VALUES (?)");
            $stmt->execute([$tendv]);
            $response['success'] = true;
            $response['message'] = 'Thêm đơn vị tính thành công';
        } elseif ($action === 'edit_donvitinh') {
            $madv = (int)$_POST['madv'];
            $tendv = $_POST['tendv'];

            $stmt = $pdo->prepare("UPDATE donvitinh SET tendv = ? WHERE madv = ?");
            $stmt->execute([$tendv, $madv]);
            $response['success'] = true;
            $response['message'] = 'Sửa đơn vị tính thành công';
        } elseif ($action === 'delete_donvitinh') {
            $madv = (int)$_POST['madv'];

            $stmt = $pdo->prepare("DELETE FROM donvitinh WHERE madv = ?");
            $stmt->execute([$madv]);
            $response['success'] = true;
            $response['message'] = 'Xóa đơn vị tính thành công';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Lỗi: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}