<?php
// admin/orders.php
$pdo = new PDO(
  'mysql:host=localhost;dbname=nhathuocantam;charset=utf8mb4','root','',
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
);

function df($n){ return number_format((float)$n,0,',','.'); }

/* ====== Filters ====== */
$q   = trim($_GET['q'] ?? '');
$st  = trim($_GET['st'] ?? '');  // '', moi, da_thanh_toan, huy
$per = max(1,(int)($_GET['per'] ?? 10));
$page= max(1,(int)($_GET['page']?? 1));

$today = (new DateTime())->format('Y-m-d');
$first = (new DateTime('first day of this month'))->format('Y-m-d');
$d1 = $_GET['d1'] ?? $first;
$d2 = $_GET['d2'] ?? $today;

/* ====== Stats ====== */
$statToday = $pdo->query("SELECT COUNT(*) so_don, COALESCE(SUM(phai_thu),0) doanh_thu FROM v_order_list WHERE DATE(ngaytao)=CURDATE()")->fetch();
$statMonth = $pdo->query("SELECT COUNT(*) so_don, COALESCE(SUM(phai_thu),0) doanh_thu FROM v_order_list WHERE YEAR(ngaytao)=YEAR(CURDATE()) AND MONTH(ngaytao)=MONTH(CURDATE())")->fetch();
$statBySt  = $pdo->prepare("SELECT trangthai, COUNT(*) cnt FROM v_order_list WHERE DATE(ngaytao) BETWEEN :d1 AND :d2 GROUP BY trangthai");
$statBySt->execute([':d1'=>$d1, ':d2'=>$d2]);
$bySt = $statBySt->fetchAll();

/* ====== Count for pagination ====== */
$count = $pdo->prepare("
  SELECT COUNT(*) FROM v_order_list
  WHERE (:q='' OR ten_kh LIKE CONCAT('%',:q2,'%') OR sdt_kh LIKE CONCAT('%',:q2,'%') OR sodh = :q_exact)
    AND (:st='' OR trangthai=:st)
    AND DATE(ngaytao) BETWEEN :d1 AND :d2
");
$qExact = ctype_digit($q) ? (int)$q : 0;
$count->execute([
  ':q'=>$q, ':q2'=>$q, ':q_exact'=>$qExact,
  ':st'=>$st, ':d1'=>$d1, ':d2'=>$d2
]);
$totalRows = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($totalRows / $per));
if ($page>$pages) $page=$pages;
$offset = ($page-1)*$per;

/* ====== Page data ====== */
$list = $pdo->prepare("
  SELECT * FROM v_order_list
  WHERE (:q='' OR ten_kh LIKE CONCAT('%',:q2,'%') OR sdt_kh LIKE CONCAT('%',:q2,'%') OR sodh = :q_exact)
    AND (:st='' OR trangthai=:st)
    AND DATE(ngaytao) BETWEEN :d1 AND :d2
  ORDER BY ngaytao DESC
  LIMIT :lim OFFSET :off
");
$list->bindValue(':q',$q);
$list->bindValue(':q2',$q);
$list->bindValue(':q_exact',$qExact,PDO::PARAM_INT);
$list->bindValue(':st',$st);
$list->bindValue(':d1',$d1);
$list->bindValue(':d2',$d2);
$list->bindValue(':lim',$per,PDO::PARAM_INT);
$list->bindValue(':off',$offset,PDO::PARAM_INT);
$list->execute();
$rows = $list->fetchAll();

function build_url($arr){
  return htmlspecialchars($_SERVER['PHP_SELF']).'?'.http_build_query($arr);
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Danh sách Đơn hàng</title>
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
  </style>
</head>
<body class="bg-slate-50 text-slate-800">
<div class="flex h-screen">
  <?php $active='orders'; include __DIR__.'/partials/header.php'; ?>

  <main class="flex-1 overflow-y-auto relative z-10">
    <!-- Top bar -->
    <header class="sticky top-0 z-20 glass border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <h1 class="text-3xl font-extrabold tracking-tight">Danh sách </h1>
        <form method="get" class="flex gap-2 items-center">
          <div class="relative">
            <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Tìm mã đơn / tên KH / SĐT…"
                   class="w-80 pl-10 pr-3 py-2 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-blue-400">
            <svg class="absolute left-3 top-2.5 text-slate-400" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="9" r="7"/><path d="m21 21-6-6"/></svg>
          </div>
          <input type="date" name="d1" value="<?=htmlspecialchars($d1)?>" class="px-3 py-2 rounded-xl border border-slate-300 bg-white">
          <input type="date" name="d2" value="<?=htmlspecialchars($d2)?>" class="px-3 py-2 rounded-xl border border-slate-300 bg-white">
          <select name="st" class="px-3 py-2 rounded-xl border border-slate-300 bg-white">
            <option value="">Tất cả trạng thái</option>
            <?php foreach(['moi'=>'Mới','da_thanh_toan'=>'Đã thanh toán','huy'=>'Huỷ'] as $k=>$v): ?>
              <option value="<?=$k?>" <?=$st===$k?'selected':''?>><?=$v?></option>
            <?php endforeach; ?>
          </select>
          <select name="per" class="px-3 py-2 rounded-xl border border-slate-300 bg-white">
            <?php foreach([10,15,20,30] as $n): ?><option <?=$per==$n?'selected':''?>><?=$n?></option><?php endforeach; ?>
          </select>
          <button class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition">Lọc</button>
        </form>
      </div>
    </header>

    <section class="max-w-7xl mx-auto px-6 pt-6 pb-2">
      <!-- Stat cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Đơn hôm nay</div>
          <div class="text-3xl font-extrabold mt-1" data-count="<?=$statToday['so_don']?>">0</div>
          <div class="text-sm text-slate-500 mt-1">Doanh thu: <b><?=df($statToday['doanh_thu'])?>đ</b></div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Đơn tháng này</div>
          <div class="text-3xl font-extrabold mt-1" data-count="<?=$statMonth['so_don']?>">0</div>
          <div class="text-sm text-slate-500 mt-1">Doanh thu: <b><?=df($statMonth['doanh_thu'])?>đ</b></div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Theo trạng thái</div>
          <div class="mt-2 flex gap-2 flex-wrap">
            <?php foreach($bySt as $r): 
              $name = $r['trangthai']=='moi'?'Mới':($r['trangthai']=='da_thanh_toan'?'Đã TT':'Huỷ'); ?>
              <span class="pill px-3 py-1.5 rounded-full bg-white"><?=$name?>: <b><?=$r['cnt']?></b></span>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Kết quả lọc</div>
          <div class="text-3xl font-extrabold mt-1" data-count="<?=$totalRows?>">0</div>
          <div class="text-sm text-slate-500 mt-1">Trang <?=$page?>/<?=$pages?></div>
        </div>
      </div>

      <!-- List -->
      <div class="grid gap-4">
        <?php foreach($rows as $o):
          $color = $o['trangthai']=='da_thanh_toan' ? 'bg-green-100 text-green-700'
                 : ($o['trangthai']=='huy' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
          $label = $o['trangthai']=='da_thanh_toan' ? 'Đã thanh toán' : ($o['trangthai']=='huy'?'Huỷ':'Mới');
        ?>
        <div class="glass rounded-2xl p-4 card border border-slate-200/70">
          <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100">
                <span class="font-bold text-blue-600">#</span>
              </div>
              <div>
                <div class="font-semibold text-lg">Đơn #<?=$o['sodh']?>
                  <span class="ml-2 px-2 py-0.5 text-xs rounded <?=$color?>"><?=$label?></span>
                </div>
                <div class="text-sm text-slate-500">
                  Ngày: <?=date('d/m/Y H:i', strtotime($o['ngaytao']))?> •
                  KH: <?=htmlspecialchars($o['ten_kh']??'N/A')?> (<?=htmlspecialchars($o['sdt_kh']??'')?>) •
                  NV: <?=htmlspecialchars($o['ten_nv']??'')?>
                </div>
                <div class="mt-1 text-sm text-slate-600">
                  Dòng: <b><?=$o['line_count']?></b> • SL: <b><?=$o['qty_sum']?></b> •
                  Tiền hàng: <b><?=df($o['tien_hang'])?>đ</b> •
                  Giảm: <b><?=df($o['giagiam'])?>đ</b> •
                  Phải thu: <b class="text-blue-600"><?=df($o['phai_thu'])?>đ</b>
                </div>
              </div>
            </div>
            <div class="flex gap-2">
              <a class="px-3 py-1.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-sm" href="/Pharmacy-management/order_detail.php?sodh=<?=$o['sodh']?>">Xem</a>
              <a class="px-3 py-1.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 text-sm" href="#">In</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-slate-600">
          Hiển thị <b><?=min($totalRows, $offset+1)?></b>–<b><?=min($totalRows, $offset+count($rows))?></b> / <b><?=$totalRows?></b> đơn
        </div>
        <nav class="flex items-center gap-2">
          <?php
            $base = ['q'=>$q,'st'=>$st,'d1'=>$d1,'d2'=>$d2,'per'=>$per];
            if ($page>1) echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($base+['page'=>$page-1]).'">« Trước</a>';
            $win=2; $start=max(1,$page-$win); $end=min($pages,$page+$win);
            if($start>1){ echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($base+['page'=>1]).'">1</a>'; if($start>2) echo '<span class="px-2">…</span>'; }
            for($p=$start;$p<=$end;$p++){ $cls=$p==$page?'bg-blue-600 text-white border-blue-600':'bg-white hover:bg-slate-50';
              echo '<a class="px-3 py-1.5 rounded-lg border '.$cls.'" href="'.build_url($base+['page'=>$p]).'">'.$p.'</a>'; }
            if($end<$pages){ if($end<$pages-1) echo '<span class="px-2">…</span>'; echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($base+['page'=>$pages]).'">'.$pages.'</a>'; }
            if ($page<$pages) echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($base+['page'=>$page+1]).'">Sau »</a>';
          ?>
        </nav>
      </div>
    </section>
  </main>
</div>

<!-- Count-up -->
<script>
document.querySelectorAll('[data-count]').forEach(el=>{
  const t=+el.dataset.count; let v=0, step=Math.max(1,Math.round(t/30));
  const tick=()=>{ v+=step; if(v>t) v=t; el.textContent=new Intl.NumberFormat('vi-VN').format(v); if(v<t) requestAnimationFrame(tick); };
  tick();
});
</script>
</body>
</html>
