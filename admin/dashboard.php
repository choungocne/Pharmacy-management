<?php
// admin/dashboard.php — gọi db.php kiểu giống products.php / orders.php

// Thử nhiều đường dẫn để luôn tìm được db.php
$pdo = null;
foreach ([__DIR__ . '/../db.php', __DIR__ . '/db.php'] as $try) {
  if (file_exists($try)) { require $try; break; }
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
  // fallback tuyệt đối nếu require không nạp được $pdo
  $dsn  = 'mysql:host=localhost;dbname=nhathuocantam;charset=utf8mb4';
  $user = 'root'; $pass = '';
  $pdo  = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
  $pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

// ===== KPI =====
$kpi = [
  'revenue_today'  => 0,
  'orders_today'   => 0,
  'low_stock'      => 0,
  'total_products' => 0
];

// Doanh thu hôm nay (đơn đã thanh toán)
$sql = "SELECT COALESCE(SUM(c.sl*c.gia)-SUM(d.giagiam),0) AS rev
        FROM donhang d
        JOIN chitietdh c USING(sodh)
        WHERE DATE(d.ngaytao)=CURDATE()
          AND d.trangthai COLLATE utf8mb4_unicode_ci = 'da_thanh_toan'";
$kpi['revenue_today'] = (float)$pdo->query($sql)->fetchColumn();

// Đơn hàng mới hôm nay
$kpi['orders_today'] = (int)$pdo->query(
  "SELECT COUNT(*) FROM donhang WHERE DATE(ngaytao)=CURDATE()"
)->fetchColumn();

// Sắp hết hàng (tổng tồn theo lô <=10)
$sql = "SELECT COUNT(*) FROM (
          SELECT masp, COALESCE(SUM(sl),0) AS ton
          FROM lohang GROUP BY masp HAVING ton <= 10
        ) x";
$kpi['low_stock'] = (int)$pdo->query($sql)->fetchColumn();

// Tổng số sản phẩm
$kpi['total_products'] = (int)$pdo->query("SELECT COUNT(*) FROM sanpham")->fetchColumn();

// ===== Doanh thu 7 ngày =====
$sql = "SELECT DATE(d.ngaytao) AS ngay,
               ROUND(SUM(c.sl*c.gia)-SUM(d.giagiam),0) AS dt
        FROM donhang d
        JOIN chitietdh c USING(sodh)
        WHERE d.trangthai COLLATE utf8mb4_unicode_ci = 'da_thanh_toan'
          AND d.ngaytao >= CURDATE() - INTERVAL 6 DAY
        GROUP BY DATE(d.ngaytao)
        ORDER BY ngay";
$rows = $pdo->query($sql)->fetchAll();

$rev7 = [];
for ($i=6;$i>=0;$i--) { $d=date('Y-m-d',strtotime("-$i day")); $rev7[$d]=0; }
foreach($rows as $r){ $rev7[$r['ngay']] = (int)$r['dt']; }
$labels7 = array_keys($rev7);
$data7   = array_values($rev7);

// ===== Top bán chạy 30 ngày =====
$sql = "SELECT sp.tensp, SUM(c.sl) AS qty
        FROM chitietdh c
        JOIN donhang d ON d.sodh=c.sodh
        JOIN sanpham sp ON sp.masp=c.masp
        WHERE d.ngaytao >= CURDATE() - INTERVAL 30 DAY
        GROUP BY sp.masp, sp.tensp
        ORDER BY qty DESC
        LIMIT 10";
$topSell = $pdo->query($sql)->fetchAll();

// ===== Hết hạn / Sắp hết hạn (<=60 ngày) =====
// Tính trạng thái ở PHP để né so sánh literal trong SQL
$sql = "SELECT sp.tensp, lh.solo, lh.hsd, lh.sl
        FROM lohang lh
        JOIN sanpham sp ON sp.masp=lh.masp
        WHERE lh.sl>0 AND lh.hsd <= (CURDATE() + INTERVAL 60 DAY)
        ORDER BY lh.hsd ASC
        LIMIT 10";
$expireLots = $pdo->query($sql)->fetchAll();
foreach ($expireLots as &$e) {
  $e['status'] = (strtotime($e['hsd']) < strtotime(date('Y-m-d'))) ? 'expired' : 'soon';
} unset($e);

// helper
function vnd($n){ return number_format($n,0,',','.').'đ'; }

$active='dashboard';
include __DIR__ . '/partials/header.php';
?>
<main class="flex-1 p-6 overflow-y-auto">
  <div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-slate-800">Dashboard</h1>
      <span class="text-slate-500 text-sm"><?= date('d/m/Y H:i') ?></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="rounded-2xl bg-white/60 backdrop-blur shadow p-5">
        <div class="text-slate-500 text-sm">Doanh thu hôm nay</div>
        <div class="mt-2 text-3xl font-bold text-blue-600"><?= vnd($kpi['revenue_today']) ?></div>
      </div>
      <div class="rounded-2xl bg-white/60 backdrop-blur shadow p-5">
        <div class="text-slate-500 text-sm">Đơn hàng mới</div>
        <div class="mt-2 text-3xl font-bold text-indigo-600"><?= (int)$kpi['orders_today'] ?></div>
      </div>
      <div class="rounded-2xl bg-white/60 backdrop-blur shadow p-5">
        <div class="text-slate-500 text-sm">Sắp hết hàng (≤10)</div>
        <div class="mt-2 text-3xl font-bold text-amber-600"><?= (int)$kpi['low_stock'] ?></div>
      </div>
      <div class="rounded-2xl bg-white/60 backdrop-blur shadow p-5">
        <div class="text-slate-500 text-sm">Tổng số sản phẩm</div>
        <div class="mt-2 text-3xl font-bold text-slate-800"><?= (int)$kpi['total_products'] ?></div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="rounded-2xl bg-white/60 backdrop-blur shadow p-5 lg:col-span-2">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-lg font-semibold text-slate-800">Doanh thu 7 ngày</h2>
        </div>
        <canvas id="rev7" height="100"></canvas>
      </div>

      <div class="rounded-2xl bg-white/60 backdrop-blur shadow p-5">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-lg font-semibold text-slate-800">Hết hạn / Sắp hết hạn (≤60 ngày)</h2>
        </div>
        <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
          <?php if(empty($expireLots)): ?>
            <div class="text-slate-500 text-sm">Không có lô nào.</div>
          <?php else: foreach($expireLots as $e): ?>
            <div class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
              <div class="w-2/3">
                <div class="font-medium text-slate-800 line-clamp-1"><?= htmlspecialchars($e['tensp']) ?></div>
                <div class="text-xs text-slate-500 mt-0.5">
                  Lô: <b><?= htmlspecialchars($e['solo']) ?></b> • HSD: <?= date('d/m/Y', strtotime($e['hsd'])) ?> • SL: <?= (int)$e['sl'] ?>
                </div>
              </div>
              <span class="text-xs px-2.5 py-1 rounded-full
                <?= $e['status']==='expired' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' ?>">
                <?= $e['status']==='expired' ? 'Hết hạn' : 'Sắp hết hạn' ?>
              </span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="rounded-2xl bg-white/60 backdrop-blur shadow p-5 lg:col-span-3">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-lg font-semibold text-slate-800">Top bán chạy 30 ngày</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          <?php if(empty($topSell)): ?>
            <div class="text-slate-500 text-sm px-2">Chưa có dữ liệu.</div>
          <?php else: foreach($topSell as $i=>$t): ?>
            <div class="rounded-xl border border-slate-200 p-3 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-semibold"><?= $i+1 ?></div>
                <div class="min-w-0">
                  <div class="font-medium text-slate-800 truncate w-56"><?= htmlspecialchars($t['tensp']) ?></div>
                  <div class="text-xs text-slate-500"><?= (int)$t['qty'] ?> sản phẩm</div>
                </div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const labels7 = <?= json_encode(array_map(fn($d)=>date('d/m', strtotime($d)), $labels7), JSON_UNESCAPED_UNICODE) ?>;
const data7   = <?= json_encode($data7, JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('rev7').getContext('2d'), {
  type: 'line',
  data: { labels: labels7, datasets: [{ data: data7, fill: true, tension: 0.35 }] },
  options: { plugins:{ legend:{ display:false }}, scales:{ y:{ ticks:{ callback:v=>v.toLocaleString('vi-VN') }}}}
});
</script>
