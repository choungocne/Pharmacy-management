<?php
$page_title = 'Quản Lý Dữ Liệu - Quản Trị Nhà Thuốc';
$active = 'management'; 
require_once dirname(__DIR__) . '/db.php';


$pdo = pdo(); // Lấy kết nối PDO
?>

<!-- =============================================== -->
<!-- BẮT ĐẦU NỘI DUNG RIÊNG CỦA TRANG MANAGEMENT     -->
<!-- =============================================== -->
<main class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        <!-- Header của phần nội dung -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Quản Lý Dữ Liệu</h2>
                <p class="text-gray-500 mt-1">Quản lý danh mục, thương hiệu và đơn vị tính cho Nhà thuốc An Tâm.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input type="search" placeholder="Tìm kiếm..." class="pl-10 pr-4 py-2 w-72 border border-gray-300 rounded-full bg-white shadow-sm focus:ring-2 focus:outline-none transition" style="--tw-ring-color: var(--primary-color)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                </div>
                <div class="flex items-center gap-3">
                    <img src="https://placehold.co/40x40/0284c7/FFFFFF?text=A" alt="Avatar" class="rounded-full">
                    <div>
                        <p class="font-semibold">Nguyễn Văn A</p>
                        <p class="text-sm text-gray-500">Quản trị viên</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Phần nội dung chính với Tabs -->
        <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-md border border-gray-200">
            <h3 class="text-xl font-semibold mb-4" style="color: var(--primary-dark);">Danh sách dữ liệu</h3>
            
            <!-- Tabs cho 3 phần -->
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="danhmuc-tab" data-bs-toggle="tab" data-bs-target="#danhmuc" type="button">Danh Mục</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="thuonghieu-tab" data-bs-toggle="tab" data-bs-target="#thuonghieu" type="button">Thương Hiệu</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="donvitinh-tab" data-bs-toggle="tab" data-bs-target="#donvitinh" type="button">Đơn Vị Tính</button>
                </li>
            </ul>
            
            <div class="tab-content" id="myTabContent">
                <!-- Tab Danh Mục -->
                <div class="tab-pane fade show active" id="danhmuc" role="tabpanel">
                    <button class="btn btn-primary mb-3 dashboard-card" data-bs-toggle="modal" data-bs-target="#addDanhMucModal">Thêm Danh Mục</button>
                    <table class="table table-bordered table-hover shadow-sm rounded-lg overflow-hidden" id="danhmucTable">
                        <thead class="bg-gray-100"><tr><th>Mã DM</th><th>Tên DM</th><th>Cấp</th><th>Parent ID</th><th>Img URL</th><th>Hành Động</th></tr></thead>
                        <tbody>
                            <?php
                            $sql_danhmuc = "SELECT madm, tendm, cap, parent_id, img_url FROM danhmuc ORDER BY madm ASC";
                            $stmt_danhmuc = $pdo->query($sql_danhmuc);
                            if ($stmt_danhmuc->rowCount() > 0) {
                                while ($row = $stmt_danhmuc->fetch()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['madm'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tendm']) . "</td>";
                                    echo "<td>" . $row['cap'] . "</td>";
                                    echo "<td>" . ($row['parent_id'] ? $row['parent_id'] : '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['img_url']) . "</td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editDanhMucModal' data-id='" . $row['madm'] . "' data-tendm='" . htmlspecialchars($row['tendm']) . "' data-cap='" . $row['cap'] . "' data-parent_id='" . ($row['parent_id'] ? $row['parent_id'] : '') . "' data-img_url='" . htmlspecialchars($row['img_url']) . "'>Sửa</button> ";
                                    echo "<button class='btn btn-sm btn-danger' onclick='deleteDanhMuc(" . $row['madm'] . ")'>Xóa</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>Không có dữ liệu danh mục</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Tab Thương Hiệu -->
                <div class="tab-pane fade" id="thuonghieu" role="tabpanel">
                    <button class="btn btn-primary mb-3 dashboard-card" data-bs-toggle="modal" data-bs-target="#addThuongHieuModal">Thêm Thương Hiệu</button>
                    <table class="table table-bordered table-hover shadow-sm rounded-lg overflow-hidden" id="thuonghieuTable">
                        <thead class="bg-gray-100"><tr><th>Mã TH</th><th>Tên TH</th><th>Logo URL</th><th>Hành Động</th></tr></thead>
                        <tbody>
                            <?php
                            $sql_thuonghieu = "SELECT math, tenth, logo_url FROM thuonghieu ORDER BY math ASC";
                            $stmt_thuonghieu = $pdo->query($sql_thuonghieu);
                            if ($stmt_thuonghieu->rowCount() > 0) {
                                while ($row = $stmt_thuonghieu->fetch()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['math'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tenth']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['logo_url']) . "</td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editThuongHieuModal' data-id='" . $row['math'] . "' data-tenth='" . htmlspecialchars($row['tenth']) . "' data-logo_url='" . htmlspecialchars($row['logo_url']) . "'>Sửa</button> ";
                                    echo "<button class='btn btn-sm btn-danger' onclick='deleteThuongHieu(" . $row['math'] . ")'>Xóa</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Không có dữ liệu thương hiệu</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Tab Đơn Vị Tính -->
                <div class="tab-pane fade" id="donvitinh" role="tabpanel">
                    <button class="btn btn-primary mb-3 dashboard-card" data-bs-toggle="modal" data-bs-target="#addDonViTinhModal">Thêm Đơn Vị Tính</button>
                    <table class="table table-bordered table-hover shadow-sm rounded-lg overflow-hidden" id="donvitinhTable">
                        <thead class="bg-gray-100"><tr><th>Mã DV</th><th>Tên DV</th><th>Hành Động</th></tr></thead>
                        <tbody>
                            <?php
                            $sql_donvitinh = "SELECT madv, tendv FROM donvitinh ORDER BY madv ASC";
                            $stmt_donvitinh = $pdo->query($sql_donvitinh);
                            if ($stmt_donvitinh->rowCount() > 0) {
                                while ($row = $stmt_donvitinh->fetch()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['madv'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tendv']) . "</td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editDonViTinhModal' data-id='" . $row['madv'] . "' data-tendv='" . htmlspecialchars($row['tendv']) . "'>Sửa</button> ";
                                    echo "<button class='btn btn-sm btn-danger' onclick='deleteDonViTinh(" . $row['madv'] . ")'>Xóa</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center'>Không có dữ liệu đơn vị tính</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- =============================================== -->
<!-- KẾT THÚC NỘI DUNG RIÊNG CỦA TRANG MANAGEMENT    -->
<!-- =============================================== -->

<!-- Modal cho Thêm Danh Mục (giữ nguyên) -->
<div class="modal fade" id="addDanhMucModal" tabindex="-1" aria-labelledby="addDanhMucModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDanhMucModalLabel">Thêm Danh Mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form thêm danh mục -->
                <form id="addDanhMucForm">
                    <div class="mb-3">
                        <label for="tendm" class="form-label">Tên Danh Mục</label>
                        <input type="text" class="form-control" id="tendm" required>
                    </div>
                    <div class="mb-3">
                        <label for="cap" class="form-label">Cấp</label>
                        <input type="number" class="form-control" id="cap" required>
                    </div>
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent ID</label>
                        <input type="number" class="form-control" id="parent_id">
                    </div>
                    <div class="mb-3">
                        <label for="img_url" class="form-label">Img URL</label>
                        <input type="text" class="form-control" id="img_url">
                    </div>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal cho Sửa Danh Mục (thêm mới) -->
<div class="modal fade" id="editDanhMucModal" tabindex="-1" aria-labelledby="editDanhMucModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDanhMucModalLabel">Sửa Danh Mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form sửa danh mục -->
                <form id="editDanhMucForm">
                    <input type="hidden" id="edit_madm">
                    <div class="mb-3">
                        <label for="edit_tendm" class="form-label">Tên Danh Mục</label>
                        <input type="text" class="form-control" id="edit_tendm" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cap" class="form-label">Cấp</label>
                        <input type="number" class="form-control" id="edit_cap" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_parent_id" class="form-label">Parent ID</label>
                        <input type="number" class="form-control" id="edit_parent_id">
                    </div>
                    <div class="mb-3">
                        <label for="edit_img_url" class="form-label">Img URL</label>
                        <input type="text" class="form-control" id="edit_img_url">
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tương tự cho các modal khác của Thương Hiệu và Đơn Vị Tính -->
<!-- Modal Thêm Thương Hiệu -->
<div class="modal fade" id="addThuongHieuModal" tabindex="-1" aria-labelledby="addThuongHieuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addThuongHieuModalLabel">Thêm Thương Hiệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addThuongHieuForm">
                    <div class="mb-3">
                        <label for="tenth" class="form-label">Tên Thương Hiệu</label>
                        <input type="text" class="form-control" id="tenth" required>
                    </div>
                    <div class="mb-3">
                        <label for="logo_url" class="form-label">Logo URL</label>
                        <input type="text" class="form-control" id="logo_url">
                    </div>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sửa Thương Hiệu -->
<div class="modal fade" id="editThuongHieuModal" tabindex="-1" aria-labelledby="editThuongHieuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editThuongHieuModalLabel">Sửa Thương Hiệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editThuongHieuForm">
                    <input type="hidden" id="edit_math">
                    <div class="mb-3">
                        <label for="edit_tenth" class="form-label">Tên Thương Hiệu</label>
                        <input type="text" class="form-control" id="edit_tenth" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_logo_url" class="form-label">Logo URL</label>
                        <input type="text" class="form-control" id="edit_logo_url">
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Đơn Vị Tính -->
<div class="modal fade" id="addDonViTinhModal" tabindex="-1" aria-labelledby="addDonViTinhModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDonViTinhModalLabel">Thêm Đơn Vị Tính</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDonViTinhForm">
                    <div class="mb-3">
                        <label for="tendv" class="form-label">Tên Đơn Vị Tính</label>
                        <input type="text" class="form-control" id="tendv" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sửa Đơn Vị Tính -->
<div class="modal fade" id="editDonViTinhModal" tabindex="-1" aria-labelledby="editDonViTinhModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDonViTinhModalLabel">Sửa Đơn Vị Tính</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editDonViTinhForm">
                    <input type="hidden" id="edit_madv">
                    <div class="mb-3">
                        <label for="edit_tendv" class="form-label">Tên Đơn Vị Tính</label>
                        <input type="text" class="form-control" id="edit_tendv" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script để handle modal edit và submit form qua AJAX -->
<script>
    // Handle edit Danh Mục
    var editDanhMucModal = document.getElementById('editDanhMucModal');
    editDanhMucModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var madm = button.getAttribute('data-id');
        var tendm = button.getAttribute('data-tendm');
        var cap = button.getAttribute('data-cap');
        var parent_id = button.getAttribute('data-parent_id');
        var img_url = button.getAttribute('data-img_url');

        var modalMadm = editDanhMucModal.querySelector('#edit_madm');
        var modalTendm = editDanhMucModal.querySelector('#edit_tendm');
        var modalCap = editDanhMucModal.querySelector('#edit_cap');
        var modalParentId = editDanhMucModal.querySelector('#edit_parent_id');
        var modalImgUrl = editDanhMucModal.querySelector('#edit_img_url');

        modalMadm.value = madm;
        modalTendm.value = tendm;
        modalCap.value = cap;
        modalParentId.value = parent_id;
        modalImgUrl.value = img_url;
    });

    // Tương tự cho Thương Hiệu
    var editThuongHieuModal = document.getElementById('editThuongHieuModal');
    editThuongHieuModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var math = button.getAttribute('data-id');
        var tenth = button.getAttribute('data-tenth');
        var logo_url = button.getAttribute('data-logo_url');

        var modalMath = editThuongHieuModal.querySelector('#edit_math');
        var modalTenth = editThuongHieuModal.querySelector('#edit_tenth');
        var modalLogoUrl = editThuongHieuModal.querySelector('#edit_logo_url');

        modalMath.value = math;
        modalTenth.value = tenth;
        modalLogoUrl.value = logo_url;
    });

    // Tương tự cho Đơn Vị Tính
    var editDonViTinhModal = document.getElementById('editDonViTinhModal');
    editDonViTinhModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var madv = button.getAttribute('data-id');
        var tendv = button.getAttribute('data-tendv');

        var modalMadv = editDonViTinhModal.querySelector('#edit_madv');
        var modalTendv = editDonViTinhModal.querySelector('#edit_tendv');

        modalMadv.value = madv;
        modalTendv.value = tendv;
    });

    // Submit form thêm/sửa/xóa qua AJAX
    document.getElementById('addDanhMucForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm('addDanhMucForm', 'add_danhmuc', '#addDanhMucModal');
    });

    document.getElementById('editDanhMucForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm('editDanhMucForm', 'edit_danhmuc', '#editDanhMucModal');
    });

    document.getElementById('addThuongHieuForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm('addThuongHieuForm', 'add_thuonghieu', '#addThuongHieuModal');
    });

    document.getElementById('editThuongHieuForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm('editThuongHieuForm', 'edit_thuonghieu', '#editThuongHieuModal');
    });

    document.getElementById('addDonViTinhForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm('addDonViTinhForm', 'add_donvitinh', '#addDonViTinhModal');
    });

    document.getElementById('editDonViTinhForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm('editDonViTinhForm', 'edit_donvitinh', '#editDonViTinhModal');
    });

    function submitForm(formId, action, modalId) {
        var form = document.getElementById(formId);
        var formData = new FormData(form);
        formData.append('action', action);

        fetch('api_management.php', { // Gửi đến file api_management.php
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Reload để cập nhật table
            } else {
                alert(data.message);
            }
            var modal = bootstrap.Modal.getInstance(document.querySelector(modalId));
            modal.hide();
        })
        .catch(error => console.error('Error:', error));
    }

    function deleteDanhMuc(id) {
        if (confirm('Bạn có chắc muốn xóa danh mục này?')) {
            var formData = new FormData();
            formData.append('action', 'delete_danhmuc');
            formData.append('madm', id);

            fetch('api_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    function deleteThuongHieu(id) {
        if (confirm('Bạn có chắc muốn xóa thương hiệu này?')) {
            var formData = new FormData();
            formData.append('action', 'delete_thuonghieu');
            formData.append('math', id);

            fetch('api_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    function deleteDonViTinh(id) {
        if (confirm('Bạn có chắc muốn xóa đơn vị tính này?')) {
            var formData = new FormData();
            formData.append('action', 'delete_donvitinh');
            formData.append('madv', id);

            fetch('api_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }
</script>

<?php
// Đóng các thẻ HTML đã được mở trong header.php
?>
</div> <!-- Đóng thẻ div.flex.h-screen -->
</body>
</html>