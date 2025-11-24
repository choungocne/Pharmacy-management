<?php
/** admin/taikhoan.php
 *  Quản lý tài khoản: liệt kê / thêm / sửa / xóa
 *  Yêu cầu: đã đăng nhập với quyền admin; header.php dựng layout chung.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== KẾT NỐI PDO: tái dùng logic như admin/index.php ===== */
$pdo = null;
$dbFile = __DIR__ . '/../db.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
    if (function_exists('pdo'))       { $pdo = pdo(); }
    elseif (function_exists('get_pdo')) { $pdo = get_pdo(); }
}
if (!$pdo instanceof PDO) {
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Throwable $e) {
        die('Could not connect to database: ' . $e->getMessage());
    }
}

/* ===== BẢO VỆ TRUY CẬP ===== */
$me = $_SESSION['auth'] ?? [];
$meRoles = $me['roles'] ?? [];
if (is_string($meRoles)) {
    $tmp = json_decode($meRoles, true);
    if (is_array($tmp)) $meRoles = $tmp;
}
$isAdmin = in_array('admin', (array)$meRoles, true);
if (!$isAdmin) {
    http_response_code(403);
    echo 'Chỉ quản trị viên mới được phép truy cập trang này.';
    exit;
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];
$check_csrf = function () {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
};

/* ===== HÀM TRỢ GIÚP ===== */
function first_role_label($rolesCol) {
    if (is_array($rolesCol)) $arr = $rolesCol;
    else {
        $arr = json_decode((string)$rolesCol, true);
        if (!is_array($arr)) $arr = [];
    }
    $code = $arr[0] ?? 'user';
    $map = ['admin' => 'Quản trị viên', 'staff' => 'Nhân viên', 'user' => 'Người dùng'];
    return [$code, $map[$code] ?? $code];
}
function only_first_role_json($code) {
    $code = in_array($code, ['admin','staff','user'], true) ? $code : 'user';
    return json_encode([$code], JSON_UNESCAPED_UNICODE);
}

/* ==== TÌM KIẾM (nếu đã có thì giữ nguyên) ==== */
$kw = isset($_GET['q']) ? trim($_GET['q']) : '';

/* ==== SẮP XẾP THEO ID: asc|desc ==== */
$sort = strtolower($_GET['sort'] ?? 'desc');   // mặc định: id mới nhất trước
$sort = $sort === 'asc' ? 'asc' : 'desc';      // chống giá trị lạ
$dir  = $sort === 'asc' ? 'ASC' : 'DESC';      // dùng cho ORDER BY
$nextSort = $sort === 'asc' ? 'desc' : 'asc';  // trạng thái khi bấm nút

/* ===== XỬ LÝ POST (CREATE/UPDATE/DELETE) ===== */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $check_csrf()) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = trim($_POST['role'] ?? 'user');

            if ($username === '' || $password === '' || $email === '') {
                throw new RuntimeException('Vui lòng nhập đủ Username, Password, Email.');
            }

            // Username/Email duy nhất (ở mức ứng dụng)
            $st = $pdo->prepare("SELECT COUNT(*) FROM auth WHERE username=? OR email=?");
            $st->execute([$username, $email]);
            if ((int)$st->fetchColumn() > 0) {
                throw new RuntimeException('Username hoặc Email đã tồn tại.');
            }

            // Mặc định băm mật khẩu; nếu login của bạn dùng so sánh thuần, hãy đổi tại đây.
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $rolesJson = only_first_role_json($role);

            $st = $pdo->prepare("INSERT INTO auth (username, password_hash, email, roles, status) VALUES (?,?,?,?,1)");
            $st->execute([$username, $hash, $email, $rolesJson]);

            $flash = ['ok' => true, 'msg' => 'Đã tạo tài khoản mới.'];
        }

        if ($action === 'update') {
            $id       = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = trim($_POST['role'] ?? 'user');

            if ($id <= 0 || $username === '' || $email === '') {
                throw new RuntimeException('Thiếu dữ liệu bắt buộc.');
            }

            // Kiểm tra tồn tại
            $st = $pdo->prepare("SELECT * FROM auth WHERE id=?");
            $st->execute([$id]);
            $row = $st->fetch();
            if (!$row) throw new RuntimeException('Không tìm thấy tài khoản.');

            // Duy nhất
            $st = $pdo->prepare("SELECT COUNT(*) FROM auth WHERE (username=? OR email=?) AND id<>?");
            $st->execute([$username, $email, $id]);
            if ((int)$st->fetchColumn() > 0) {
                throw new RuntimeException('Username hoặc Email đã được dùng bởi tài khoản khác.');
            }

            $rolesJson = only_first_role_json($role);

            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE auth SET username=?, email=?, roles=?, password_hash=? WHERE id=?";
                $pdo->prepare($sql)->execute([$username, $email, $rolesJson, $hash, $id]);
            } else {
                $sql = "UPDATE auth SET username=?, email=?, roles=? WHERE id=?";
                $pdo->prepare($sql)->execute([$username, $email, $rolesJson, $id]);
            }

            // Nếu sửa chính mình, cập nhật session roles/username
            if (!empty($_SESSION['auth']) && (int)($_SESSION['auth']['id'] ?? 0) === $id) {
                $_SESSION['auth']['username'] = $username;
                $_SESSION['auth']['roles']    = json_decode($rolesJson, true);
            }

            $flash = ['ok' => true, 'msg' => 'Đã cập nhật tài khoản.'];
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Thiếu ID để xóa.');

            // Không cho tự xóa chính mình
            if (!empty($_SESSION['auth']) && (int)($_SESSION['auth']['id'] ?? 0) === $id) {
                throw new RuntimeException('Không thể tự xóa tài khoản đang đăng nhập.');
            }

            $pdo->prepare("DELETE FROM auth WHERE id=?")->execute([$id]);
            $flash = ['ok' => true, 'msg' => 'Đã xóa tài khoản.'];
        }

    } catch (Throwable $e) {
        $flash = ['ok' => false, 'msg' => $e->getMessage()];
    }

    // PRG: tránh submit lại khi refresh
    $_SESSION['flash_taikhoan'] = $flash;
    $redirectQS = http_build_query(['q' => $kw, 'sort' => $sort]);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . ($redirectQS ? '?' . $redirectQS : ''));
    exit;
}

// Lấy flash sau redirect
if (isset($_SESSION['flash_taikhoan'])) {
    $flash = $_SESSION['flash_taikhoan'];
    unset($_SESSION['flash_taikhoan']);
}

/* ===== TRUY VẤN DANH SÁCH ===== */
if ($kw !== '') {
    $sql = "SELECT id, username, email, roles, password_hash
            FROM auth
            WHERE username LIKE :k OR email LIKE :k
            ORDER BY id {$dir}";
    $st  = $pdo->prepare($sql);
    $st->execute([':k' => "%{$kw}%"]);
    $rows = $st->fetchAll();
} else {
    $rows = $pdo->query(
        "SELECT id, username, email, roles, password_hash
         FROM auth
         ORDER BY id {$dir}"
    )->fetchAll();
}

/* ===== TIÊU ĐỀ & HEADER LAYOUT ===== */
$page_title = 'Quản Lý Tài Khoản - Quản Trị Nhà Thuốc';
$active = 'accounts';
require __DIR__ . '/partials/header.php';  // dựng sidebar/topbar/stylesheet

$userName = $_SESSION['auth']['username'] ?? 'Admin';
[$roleCode, $roleLabel] = first_role_label($_SESSION['auth']['roles'] ?? []);
?>
<!-- =========================== NỘI DUNG TRANG =========================== -->
<main class="flex-1 p-8 overflow-y-auto">
  <div class="max-w-7xl mx-auto">
    <header class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-3xl font-bold text-gray-800">Quản lý tài khoản</h2>
        <p class="text-gray-500 mt-1">Tạo, chỉnh sửa và xóa tài khoản người dùng.</p>
      </div>
      <div class="flex items-center gap-3">
        <form method="get" class="relative">
          <input name="q" value="<?=htmlspecialchars($kw)?>" type="search" placeholder="Tìm theo username/email..."
                 class="pl-10 pr-4 py-2 w-72 border border-gray-300 rounded-full bg-white shadow-sm focus:ring-2 focus:outline-none transition"
                 style="--tw-ring-color: var(--primary-color)">
          <input type="hidden" name="sort" value="<?=htmlspecialchars($sort)?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
               class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
        </form>
        <a href="?<?= http_build_query(['q' => $kw, 'sort' => $nextSort]) ?>"
           class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">
           Sắp xếp ID: <?= $sort === 'asc' ? '↑ thấp→cao' : '↓ cao→thấp' ?>
        </a>
        <button id="btn-open-create"
                class="px-4 py-2 rounded-lg text-white font-medium shadow"
                style="background: var(--primary-color);">Thêm tài khoản</button>
      </div>
    </header>

    <?php if ($flash): ?>
      <div class="mb-4 p-3 rounded-lg border <?= $flash['ok'] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="bg-white/80 backdrop-blur-lg p-6 rounded-2xl shadow-md border border-gray-200">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left">
          <thead>
            <tr class="text-gray-500 border-b">
              <th class="py-3 pr-4">
                <a href="?<?= http_build_query(['q' => $kw, 'sort' => $nextSort]) ?>"
                   class="flex items-center gap-1 hover:underline">
                   ID <?= $sort === 'asc' ? '▲' : '▼' ?>
                </a>
              </th>
              <th class="py-3 pr-4">Username</th>
              <th class="py-3 pr-4">Email</th>
              <th class="py-3 pr-4">Vai trò</th>
              <th class="py-3 pr-4 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="text-gray-800">
          <?php foreach ($rows as $r): ?>
            <?php [$rc, $rl] = first_role_label($r['roles']); ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-3 pr-4"><?= (int)$r['id'] ?></td>
              <td class="py-3 pr-4 font-medium"><?= htmlspecialchars($r['username']) ?></td>
              <td class="py-3 pr-4"><?= htmlspecialchars($r['email']) ?></td>
              <td class="py-3 pr-4">
                <span class="px-2 py-1 rounded text-sm border <?= $rc==='admin'?'border-rose-300 bg-rose-50':'border-slate-200 bg-slate-50'?>"><?= htmlspecialchars($rl) ?></span>
              </td>
              <td class="py-3 pr-0">
                <div class="flex items-center gap-2 justify-end">
                  <button
                    class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-100"
                    data-edit
                    data-id="<?= (int)$r['id'] ?>"
                    data-username="<?= htmlspecialchars($r['username']) ?>"
                    data-email="<?= htmlspecialchars($r['email']) ?>"
                    data-role="<?= htmlspecialchars($rc) ?>"
                  >Sửa</button>

                  <form method="post" onsubmit="return confirm('Xóa tài khoản này?');">
                    <input type="hidden" name="csrf" value="<?=$CSRF?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="px-3 py-2 rounded-lg border border-red-300 text-red-600 hover:bg-red-50">Xóa</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td class="py-6 text-center text-gray-500" colspan="5">Chưa có tài khoản.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- ============== MODAL: TẠO/SỬA TÀI KHOẢN ============== -->
<div id="account-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
  <div class="absolute inset-0 bg-black/40"></div>
  <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-xl border border-gray-200 p-6">
    <h3 id="modal-title" class="text-xl font-semibold mb-4">Thêm tài khoản</h3>
    <form id="account-form" method="post" class="space-y-4">
      <input type="hidden" name="csrf" value="<?=$CSRF?>">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="id" value="">

      <div>
        <label class="block text-sm text-gray-600 mb-1">Username</label>
        <input name="username" type="text" required
               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
               style="--tw-ring-color: var(--primary-color)">
      </div>

      <div>
        <label class="block text-sm text-gray-600 mb-1">Email</label>
        <input name="email" type="email" required
               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
               style="--tw-ring-color: var(--primary-color)">
      </div>

      <div>
        <label class="block text-sm text-gray-600 mb-1">Mật khẩu <span class="text-gray-400 text-xs">(để trống khi sửa nếu không đổi)</span></label>
        <input name="password" type="text"
               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
               style="--tw-ring-color: var(--primary-color)" placeholder="Ví dụ: 123456">
      </div>

      <div>
        <label class="block text-sm text-gray-600 mb-1">Vai trò</label>
        <select name="role" class="w-full px-3 py-2 border rounded-lg">
          <option value="admin">Quản trị viên</option>
          <option value="staff">Nhân viên</option>
          <option value="user">Người dùng</option>
        </select>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" id="btn-cancel" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">Hủy</button>
        <button class="px-4 py-2 rounded-lg text-white font-medium shadow" style="background: var(--primary-color);">Lưu</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));
  const modal = $("#account-modal");
  const form  = $("#account-form");
  const title = $("#modal-title");
  const openCreate = $("#btn-open-create");
  const cancelBtn  = $("#btn-cancel");

  function open() { modal.classList.remove("hidden"); modal.classList.add("flex"); }
  function close() { modal.classList.add("hidden"); modal.classList.remove("flex"); }

  function setCreate() {
    title.textContent = "Thêm tài khoản";
    form.action.value = "create";
    form.id.value = "";
    form.username.value = "";
    form.email.value = "";
    form.password.value = "";
    form.role.value = "user";
  }

  function setEdit(row) {
    title.textContent = "Sửa tài khoản";
    form.action.value = "update";
    form.id.value = row.dataset.id;
    form.username.value = row.dataset.username || "";
    form.email.value = row.dataset.email || "";
    form.password.value = ""; // để trống nếu không đổi
    form.role.value = row.dataset.role || "user";
  }

  openCreate?.addEventListener("click", () => { setCreate(); open(); });
  cancelBtn?.addEventListener("click", close);
  modal.addEventListener("click", (e) => { if (e.target === modal) close(); });

  $$("[data-edit]").forEach(btn => {
    btn.addEventListener("click", () => { setEdit(btn); open(); });
  });
})();
</script>

</div><!-- đóng .flex.h-screen mở ở header.php -->
</body>
</html>
