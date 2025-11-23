<?php
// admin/management.php - Quản lý Danh mục (Tái cấu trúc theo mẫu products.php)

// Tự định nghĩa kết nối PDO, giống như products.php
$pdo = new PDO(
  'mysql:host=localhost;dbname=nhathuocantam;charset=utf8mb4','root','',
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
);

$q  = trim($_GET['q'] ?? '');
$cap = (int)($_GET['cap'] ?? 0); // Lọc theo cấp danh mục
$perPage      = max(1,(int)($_GET['per'] ?? 9)); // Số lượng danh mục trên mỗi trang, giống products
$page         = max(1,(int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

// Hàm gọi API, giống products.php
function callAPI($endpoint, $params = []) {
    $baseURL = 'http://localhost/pharmacy-management/api/api_management.php';
    $url = $baseURL . $endpoint;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    
    return null;
}

/* ===== STATS (Thống kê) ===== */
$totalCats = (int)$pdo->query("SELECT COUNT(*) FROM danhmuc")->fetchColumn();
$cap1Cats  = (int)$pdo->query("SELECT COUNT(*) FROM danhmuc WHERE cap=1")->fetchColumn();
$cap2Cats  = (int)$pdo->query("SELECT COUNT(*) FROM danhmuc WHERE cap=2")->fetchColumn();
$cap3Cats  = (int)$pdo->query("SELECT COUNT(*) FROM danhmuc WHERE cap=3")->fetchColumn();

/* ===== LẤY DỮ LIỆU TỪ API ===== */
$apiParams = [
    'q' => $q,
    'cap' => $cap,
    'per' => $perPage,
    'page' => $page
];

$apiResponse = callAPI('', $apiParams);

if ($apiResponse && isset($apiResponse['success']) && $apiResponse['success'] && isset($apiResponse['data'])) {
    $totalFiltered = $apiResponse['total'];
    $pages = $apiResponse['pages'];
    $page = $apiResponse['current_page'];
    $offset = ($page - 1) * $perPage;
    $rows = $apiResponse['data'];
    
    // Xử lý dữ liệu để tương thích
    foreach ($rows as &$r) {
        // Giả định API trả về madm, tendm, cap, parent_id, img_url, parent_name
        // Xử lý hình ảnh: Thêm prefix nếu cần
        if (!empty($r['img_url'])) {
            if (!str_starts_with($r['img_url'], '/pharmacy-management/')) {
                $r['img_url'] = '/pharmacy-management/' . $r['img_url'];
            }
        } else {
            $r['img_url'] = '/pharmacy-management/uploads/sp/placeholder.jpg';
        }
    }
    unset($r);
} else {
    // Fallback về truy vấn trực tiếp
    $whereClauses = [];
    $execParams = [];

    if ($q !== '') {
        $whereClauses[] = "dm.tendm LIKE CONCAT('%',:q,'%')";
        $execParams[':q'] = $q;
    }

    if ($cap > 0) {
        $whereClauses[] = "dm.cap = :cap";
        $execParams[':cap'] = $cap;
    }

    $whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

    // Đếm tổng số danh mục đã lọc
    $countSql = "SELECT COUNT(*) FROM danhmuc dm $whereSql";
    $cst = $pdo->prepare($countSql);
    $cst->execute($execParams);
    $totalFiltered = (int)$cst->fetchColumn();

    $pages = max(1, (int)ceil($totalFiltered / $perPage));
    if ($page > $pages) $page = $pages;
    $offset = ($page - 1) * $perPage;

    // Lấy dữ liệu danh mục
    $sql = "
    SELECT dm.madm, dm.tendm, dm.cap, dm.parent_id, dm.img_url, p.tendm AS parent_name
    FROM danhmuc dm
    LEFT JOIN danhmuc p ON p.madm = dm.parent_id
    $whereSql
    ORDER BY dm.cap, dm.tendm
    LIMIT :lim OFFSET :off";

    $st=$pdo->prepare($sql);
    $st->bindValue(':lim',$perPage,PDO::PARAM_INT);
    $st->bindValue(':off',$offset,PDO::PARAM_INT);
    foreach ($execParams as $key => $val) {
        $st->bindValue($key, $val, str_starts_with($key, ':cap') ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    $rows=$st->fetchAll();
    
    // Xử lý hình ảnh trong fallback
    foreach ($rows as &$r) {
        if (!empty($r['img_url'])) {
            if (!str_starts_with($r['img_url'], '/pharmacy-management/')) {
                $r['img_url'] = '/pharmacy-management/' . $r['img_url'];
            }
        } else {
            $r['img_url'] = '/pharmacy-management/uploads/sp/placeholder.jpg';
        }
    }
    unset($r);
}

// Lấy danh sách danh mục cha (cấp 1 và 2) cho modal
$parentCats = $pdo->query("SELECT madm, tendm, cap FROM danhmuc WHERE cap IN (1, 2) ORDER BY cap, tendm")->fetchAll();

/* helper build url (giống products.php) */
function build_url($q,$cap,$page,$per){ return htmlspecialchars($_SERVER['PHP_SELF']).'?'.http_build_query(['q'=>$q,'cap'=>$cap,'page'=>$page,'per'=>$per]); }
?>
<?php
// Tab đang hiển thị: 'product' (Quản lý Sản phẩm) hoặc 'meta' (DVT, DM, TH)
$ADMIN_TAB = $_GET['tab'] ?? 'product';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Quản lý danh mục</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .glass{background:rgba(255,255,255,.85);backdrop-filter:saturate(180%) blur(10px)}
    .fade-in{animation:fade .5s ease both}
    @keyframes fade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
    .card{transition:transform .15s ease, box-shadow .15s ease}
    .card:hover{transform:translateY(-3px); box-shadow:0 16px 30px rgba(2,6,23,.10)}
    .pill{box-shadow: inset 0 0 0 1px rgba(2,6,23,.08)}
    .stat{box-shadow:0 12px 30px rgba(59,130,246,.08)}
    .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:50;align-items:center;justify-content:center}
    .modal.active{display:flex}
    .modal-content{background:white;border-radius:1rem;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;padding:2rem}
  </style>
</head>
<body class="bg-slate-50 text-slate-800">
<div class="flex h-screen">
  <?php $active='products'; include __DIR__.'/partials/header.php'; ?>

  <main class="flex-1 overflow-y-auto relative z-10">
    <header class="sticky top-0 z-20 glass border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <h1 class="text-3xl font-extrabold tracking-tight">
          <?= ($ADMIN_TAB === 'meta')
                ? 'Qu?n l? danh m?c, th??ng hi?u v? ??n v? t?nh'
                : 'Qu?n l? S?n ph?m' ?>
        </h1>
        <div class="flex gap-2 items-center">
        <button onclick="openAddModal()" class="px-4 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 transition flex items-center gap-2">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m7-7H5"/></svg>
          Thêm danh mục
        </button>
        <form method="get" class="flex gap-2 items-center">
          <div class="relative">
            <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Tìm kiếm danh mục…"
                   class="w-80 pl-10 pr-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
            <svg class="absolute left-3 top-2.5 text-slate-400" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="9" r="7"/><path d="m21 21-6-6"/></svg>
          </div>
          <select name="cap" class="px-3 py-2 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-blue-400">
            <option value="0">Tất cả cấp</option>
            <option value="1" <?=$cap==1?'selected':''?>>Cấp 1</option>
            <option value="2" <?=$cap==2?'selected':''?>>Cấp 2</option>
            <option value="3" <?=$cap==3?'selected':''?>>Cấp 3</option>
          </select>
          <input type="hidden" name="per" value="<?=$perPage?>">
          <button class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition">Lọc</button>
        </form>
        </div>
      </div>
    </header>

    <section class="max-w-7xl mx-auto px-6 pt-6 pb-2">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Tổng số danh mục</div>
          <div class="text-3xl font-extrabold mt-1" data-count="<?=$totalCats?>">0</div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Danh mục Cấp 1</div>
          <div class="text-3xl font-extrabold mt-1 text-green-600" data-count="<?=$cap1Cats?>">0</div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Danh mục Cấp 2</div>
          <div class="text-3xl font-extrabold mt-1 text-amber-600" data-count="<?=$cap2Cats?>">0</div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Danh mục Cấp 3</div>
          <div class="text-3xl font-extrabold mt-1 text-violet-600" data-count="<?=$cap3Cats?>">0</div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 mb-4">
        <a href="<?=build_url('',0,1,$perPage)?>" class="pill px-3 py-1.5 rounded-full bg-white hover:bg-slate-50">Tất cả</a>
        <a href="<?=build_url($q, 0, 1, $perPage)?>" class="pill px-3 py-1.5 rounded-full bg-white hover:bg-slate-50">Xoá lọc</a>
        <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 pill">Kết quả: <?=$totalFiltered?></span>
        <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 pill">Trang <?=$page?>/<?=$pages?></span>
      </div>

      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach($rows as $r):
          $badgeColor = $r['cap'] == 1 ? 'bg-green-100 text-green-700' : ($r['cap'] == 2 ? 'bg-amber-100 text-amber-700' : 'bg-violet-100 text-violet-700');
        ?>
        <div class="glass rounded-2xl p-4 card fade-in border border-slate-200/70">
          <div class="flex gap-4">
            <img src="<?=htmlspecialchars($r['img_url'])?>" referrerpolicy="no-referrer"
                 class="w-24 h-24 object-cover rounded-xl border border-slate-200 bg-white" alt="">
            <div class="flex-1">
              <div class="font-semibold leading-snug">
                <?=htmlspecialchars($r['tendm'])?> <span class="ml-2 px-2 py-0.5 text-xs rounded <?=$badgeColor?>">Cấp <?=$r['cap']?></span>
              </div>
              <div class="text-sm text-slate-500 mt-0.5">
                Cha: <?=htmlspecialchars($r['parent_name']??'N/A')?>
              </div>
              <div class="mt-3 flex gap-2">
                <button onclick="openEditModal(<?=$r['madm']?>)"
                   class="px-3 py-1.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 text-sm transition">Sửa</button>
                <button onclick="deleteCategory(<?=$r['madm']?>)"
                   class="px-3 py-1.5 rounded-xl bg-red-600 text-white hover:bg-red-700 text-sm transition">Xóa</button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-8 flex items-center justify-between">
        <div class="text-sm text-slate-600">
          Hiển thị
          <b><?=min($totalFiltered, $offset+1)?></b>—
          <b><?=min($totalFiltered, $offset + count($rows))?></b>
          / <b><?=$totalFiltered?></b> danh mục
        </div>
        <nav class="flex items-center gap-2">
          <?php if($page>1): ?>
            <a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50"
               href="<?=build_url($q,$cap,$page-1,$perPage)?>">« Trước</a>
          <?php endif; ?>

          <?php
            $win = 2;
            $start = max(1, $page-$win);
            $end   = min($pages, $page+$win);
            if ($start>1) {
              echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($q,$cap,1,$perPage).'">1</a>';
              if ($start>2) echo '<span class="px-2">…</span>';
            }
            for($p=$start;$p<=$end;$p++){
              $cls = $p==$page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-slate-50';
              echo '<a class="px-3 py-1.5 rounded-lg border '.$cls.'" href="'.build_url($q,$cap,$p,$perPage).'">'.$p.'</a>';
            }
            if ($end<$pages) {
              if ($end<$pages-1) echo '<span class="px-2">…</span>';
              echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($q,$cap,$pages,$perPage).'">'.$pages.'</a>';
            }
          ?>

          <?php if($page<$pages): ?>
            <a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50"
               href="<?=build_url($q,$cap,$page+1,$perPage)?>">Sau »</a>
          <?php endif; ?>
        </nav>
      </div>
    </section>
  </main>
</div>

<div id="categoryModal" class="modal">
  <div class="modal-content">
    <div class="flex justify-between items-center mb-6">
      <h2 id="modalTitle" class="text-2xl font-bold">Thêm Danh mục</h2>
      <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 6-12 12m0-12 12 12"/></svg>
      </button>
    </div>
    
    <form id="categoryForm" class="space-y-4">
      <input type="hidden" id="categoryId">
      
      <div>
        <label class="block text-sm font-medium mb-1">Tên danh mục <span class="text-red-500">*</span></label>
        <input type="text" id="tendm" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-400">
      </div>
      
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Cấp danh mục <span class="text-red-500">*</span></label>
          <select id="cap" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-400">
            <option value="">-- Chọn cấp --</option>
            <option value="1">Cấp 1</option>
            <option value="2">Cấp 2</option>
            <option value="3">Cấp 3</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Danh mục cha</label>
          <select id="parent_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-400">
            <option value="">-- Không có --</option>
            <?php foreach($parentCats as $c): ?>
              <option value="<?=$c['madm']?>"><?=$c['tendm']?> (Cấp <?=$c['cap']?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      
      <div>
        <label class="block text-sm font-medium mb-1">Tên file hình ảnh (sẽ tự thêm /pharmacy-management/ ở đầu)</label>
        <input type="text" id="img_url" placeholder="static/img/icon/example.webp" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-400">
        <p class="text-xs text-slate-500 mt-1">Ví dụ: Nếu nhập "static/img/icon/example.webp", sẽ lưu thành "/pharmacy-management/static/img/icon/example.webp"</p>
      </div>
      
      <div id="parentWarning" class="text-sm text-red-500 hidden mt-2">
        ⚠️ Danh mục Cấp 2 và Cấp 3 bắt buộc phải chọn Danh mục cha.
      </div>
      
      <div class="flex gap-3 pt-4">
        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
          Lưu
        </button>
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition">
          Hủy
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const API_URL = 'http://localhost/pharmacy-management/api/api_management.php';
const IMAGE_PREFIX = '/pharmacy-management/';

// Xử lý sự kiện thay đổi Cấp để hiển thị cảnh báo
document.getElementById('cap').addEventListener('change', (e) => {
    const cap = parseInt(e.target.value);
    const parentWarning = document.getElementById('parentWarning');
    if (cap === 2 || cap === 3) {
        parentWarning.classList.remove('hidden');
    } else {
        parentWarning.classList.add('hidden');
    }
});

// Mở modal thêm
function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Thêm Danh mục';
  document.getElementById('categoryForm').reset();
  document.getElementById('categoryId').value = '';
  document.getElementById('parentWarning').classList.add('hidden');
  document.getElementById('categoryModal').classList.add('active');
}

// Mở modal sửa
async function openEditModal(id) {
  try {
    const response = await fetch(`${API_URL}?madm=${id}`);
    const result = await response.json();
    
    if (!result.success) {
      alert(result.error || 'Không tìm thấy danh mục!');
      return;
    }
    
    const category = result.data;
    
    document.getElementById('modalTitle').textContent = 'Sửa Danh mục';
    document.getElementById('categoryId').value = category.madm;
    document.getElementById('tendm').value = category.tendm || '';
    document.getElementById('cap').value = category.cap || '';
    document.getElementById('parent_id').value = category.parent_id || '';
    // Chỉ hiển thị phần sau prefix nếu có
    document.getElementById('img_url').value = (category.img_url || '').replace(IMAGE_PREFIX, '');
    
    const cap = parseInt(category.cap);
    const parentWarning = document.getElementById('parentWarning');
    if (cap === 2 || cap === 3) {
        parentWarning.classList.remove('hidden');
    } else {
        parentWarning.classList.add('hidden');
    }
    
    document.getElementById('categoryModal').classList.add('active');
  } catch (error) {
    alert('Lỗi khi tải dữ liệu: ' + error.message);
  }
}

// Đóng modal
function closeModal() {
  document.getElementById('categoryModal').classList.remove('active');
}

// Xử lý form submit
document.getElementById('categoryForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const id = document.getElementById('categoryId').value;
  const cap = parseInt(document.getElementById('cap').value);
  let img_url = document.getElementById('img_url').value.trim();
  
  // Tự động thêm prefix nếu có giá trị img_url
  if (img_url) {
    img_url = IMAGE_PREFIX + img_url;
  }
  
  const parent_id = document.getElementById('parent_id').value ? parseInt(document.getElementById('parent_id').value) : null;
  
  if ((cap === 2 || cap === 3) && !parent_id) {
      alert('Danh mục Cấp 2 và Cấp 3 bắt buộc phải chọn Danh mục cha.');
      return;
  }
  
  const data = {
    tendm: document.getElementById('tendm').value,
    cap: cap,
    parent_id: parent_id,
    img_url: img_url
  };
  
  try {
    let url = API_URL;
    let method = 'POST';
    if (id) {
      url = `${API_URL}?madm=${id}`;
      method = 'PUT';
    }
    const response = await fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(id ? 'Cập nhật danh mục thành công!' : 'Thêm danh mục thành công!');
      closeModal();
      location.reload();
    } else {
      alert('Lỗi: ' + (result.error || 'Không xác định'));
    }
  } catch (error) {
    alert('Lỗi kết nối: ' + error.message);
  }
});

// Xóa danh mục
async function deleteCategory(id) {
  if (!confirm('Bạn có chắc muốn xóa danh mục này? Việc này có thể ảnh hưởng đến các sản phẩm và danh mục con liên quan.')) return;
  
  try {
    const response = await fetch(`${API_URL}?madm=${id}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    
    if (result.success) {
      alert('Xóa thành công!');
      location.reload();
    } else {
      alert('Lỗi: ' + (result.error || 'Không xác định'));
    }
  } catch (error) {
    alert('Lỗi kết nối: ' + error.message);
  }
}

// Đóng modal khi click bên ngoài
document.getElementById('categoryModal').addEventListener('click', (e) => {
  if (e.target.id === 'categoryModal') closeModal();
});

// Script cho hiệu ứng đếm số và fade-in
document.querySelectorAll('[data-count]').forEach(el=>{
  const target=+el.dataset.count; let v=0, step=Math.max(1, Math.round(target/30));
  const tick=()=>{ v+=step; if(v>target) v=target; el.textContent=new Intl.NumberFormat('vi-VN').format(v); if(v<target) requestAnimationFrame(tick); };
  tick();
});
const io=new IntersectionObserver(es=>es.forEach(e=>e.isIntersecting&&e.target.classList.add('fade-in')), {threshold:.12});
document.querySelectorAll('.stat, .card').forEach(c=>io.observe(c));
</script>
</body>
</html>
