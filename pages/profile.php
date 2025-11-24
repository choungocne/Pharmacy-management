<?php
// pages/profile.php

// 1. Kiểm tra đăng nhập
if (empty($_SESSION['auth']) || empty($_SESSION['auth']['makh'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$makh = $_SESSION['auth']['makh'];
$action = $_GET['action'] ?? 'view';
$msg = '';
$pdo = pdo();

// 2. Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $hoten = trim($_POST['hoten']);
    $sdt = trim($_POST['sdt']);
    $email = trim($_POST['email']);
    $diachi = trim($_POST['diachi']);

    if (empty($hoten) || empty($sdt)) {
        $msg = '<div class="alert alert-danger rounded-3 shadow-sm mb-4"><i class="fas fa-exclamation-triangle me-2"></i>Họ tên và SĐT là bắt buộc.</div>';
    } else {
        try {
            $sql = "UPDATE khachhang SET hoten = ?, sdt = ?, email = ?, diachi = ? WHERE makh = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hoten, $sdt, $email, $diachi, $makh]);

            $pdo->prepare("UPDATE auth SET email = ? WHERE makh = ?")->execute([$email, $makh]);
            $_SESSION['auth']['username'] = $hoten;

            $msg = '<div class="alert alert-success rounded-3 shadow-sm mb-4"><i class="fas fa-check-circle me-2"></i>Cập nhật hồ sơ thành công!</div>';
            echo "<script>setTimeout(()=>{window.location.href='base.php?page=profile';}, 1200);</script>";
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger rounded-3 shadow-sm mb-4">Lỗi: ' . $e->getMessage() . '</div>';
        }
    }
}

// 3. Lấy dữ liệu
$stmt = $pdo->prepare("SELECT * FROM khachhang WHERE makh = ?");
$stmt->execute([$makh]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
    /* --- CSS CĂN GIỮA TUYỆT ĐỐI --- */
    .profile-full-screen {
        width: 100%;
        min-height: 85vh; /* Chiều cao tối thiểu gần bằng màn hình */
        display: flex;
        justify-content: center; /* Căn giữa ngang */
        align-items: center;     /* Căn giữa dọc */
        padding: 40px 15px;
        background-color: #f8f9fa; /* Màu nền nhẹ */
    }

    .profile-card-centered {
        width: 100%;
        max-width: 1000px; /* Giới hạn độ rộng tối đa để không bị bè */
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.1); /* Đổ bóng sâu cho nổi */
        overflow: hidden;
        position: relative;
        margin: 0 auto; /* Đảm bảo căn giữa */
    }

    .profile-header-cover {
        height: 200px;
        background: linear-gradient(135deg, #005b9f, #00d2ff);
        position: relative;
    }

    .profile-content-wrapper {
        padding: 0 50px 50px 50px;
        position: relative;
    }

    .profile-avatar-float {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        border: 6px solid #fff;
        background: #fff;
        overflow: hidden;
        margin-top: -80px; /* Kéo avatar lên đè vào cover */
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-avatar-float img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .info-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .info-display-box {
        background: #f4f6f8;
        padding: 15px;
        border-radius: 12px;
        font-weight: 600;
        color: #333;
        border: 1px solid #e0e0e0;
        font-size: 1.05rem;
    }

    .form-control-lg {
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 12px;
        padding: 12px 15px;
        font-size: 1.05rem;
    }
    .form-control-lg:focus {
        box-shadow: 0 0 0 4px rgba(0, 91, 159, 0.15);
        border-color: #005b9f;
    }

    @media (max-width: 768px) {
        .profile-content-wrapper { padding: 0 20px 30px 20px; }
        .profile-avatar-float { width: 120px; height: 120px; margin-top: -60px; }
    }
</style>

<div class="profile-full-screen">
    
    <div class="profile-card-centered">
        
        <div class="profile-header-cover"></div>

        <div class="profile-content-wrapper">
            
            <div class="d-flex flex-column align-items-center text-center">
                <div class="profile-avatar-float">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User">
                </div>
                <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['hoten']) ?></h2>
                <p class="text-primary fw-bold mb-4"><i class="fas fa-check-circle"></i> Tài khoản thành viên</p>
            </div>

            <?= $msg ?>

            <hr class="mb-5 opacity-10">

            <form method="POST" action="base.php?page=profile&action=edit">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label class="info-label">Họ và tên</label>
                            <?php if ($action == 'edit'): ?>
                                <input type="text" name="hoten" class="form-control form-control-lg" value="<?= htmlspecialchars($user['hoten']) ?>" required>
                            <?php else: ?>
                                <div class="info-display-box"><?= htmlspecialchars($user['hoten']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="info-label">Số điện thoại</label>
                            <?php if ($action == 'edit'): ?>
                                <input type="text" name="sdt" class="form-control form-control-lg" value="<?= htmlspecialchars($user['sdt']) ?>" required>
                            <?php else: ?>
                                <div class="info-display-box"><?= htmlspecialchars($user['sdt']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-4">
                            <label class="info-label">Email liên hệ</label>
                            <?php if ($action == 'edit'): ?>
                                <input type="email" name="email" class="form-control form-control-lg" value="<?= htmlspecialchars($user['email']) ?>">
                            <?php else: ?>
                                <div class="info-display-box text-muted"><?= htmlspecialchars($user['email'] ?? 'Chưa cập nhật') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="info-label">Địa chỉ giao hàng</label>
                            <?php if ($action == 'edit'): ?>
                                <input type="text" name="diachi" class="form-control form-control-lg" value="<?= htmlspecialchars($user['diachi']) ?>">
                            <?php else: ?>
                                <div class="info-display-box text-muted">
                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                    <?= htmlspecialchars($user['diachi'] ?? 'Chưa cập nhật') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <?php if ($action == 'edit'): ?>
                        <button type="submit" name="update_profile" class="btn btn-primary btn-lg px-5 rounded-pill shadow fw-bold">
                            <i class="fas fa-save me-2"></i> LƯU THAY ĐỔI
                        </button>
                        <a href="base.php?page=profile" class="btn btn-outline-secondary btn-lg px-4 rounded-pill ms-2 fw-bold">Hủy</a>
                    <?php else: ?>
                        <a href="base.php?page=profile&action=edit" class="btn btn-outline-primary btn-lg px-5 rounded-pill border-2 fw-bold">
                            <i class="fas fa-user-edit me-2"></i> CHỈNH SỬA
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>