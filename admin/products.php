<?php
// admin/products.php
$pdo = new PDO(
  'mysql:host=localhost;dbname=nhathuocantam;charset=utf8mb4','root','',
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
);

$q  = trim($_GET['q'] ?? '');
$dm = (int)($_GET['dm'] ?? 0);
$soonDays     = 60;   // HSD sắp hết hạn
$lowThreshold = 10;   // tồn thấp
$perPage      = max(1,(int)($_GET['per'] ?? 9));  // 3 cột x 3 hàng
$page         = max(1,(int)($_GET['page'] ?? 1));

/* ===== STATS (giữ như index, nhưng điều chỉnh cho tonkho) ===== */
$totalProducts   = (int)$pdo->query("SELECT COUNT(*) FROM sanpham")->fetchColumn();
$expiredProducts = (int)$pdo->query("SELECT COUNT(*) FROM tonkho WHERE hsd < CURDATE() AND soluong>0")->fetchColumn();
$soonProducts    = (int)$pdo->query("
  SELECT COUNT(*) FROM tonkho
  WHERE hsd >= CURDATE() AND hsd <= DATE_ADD(CURDATE(), INTERVAL $soonDays DAY) AND soluong>0
")->fetchColumn();
$lowStock        = (int)$pdo->query("
  SELECT COUNT(*) FROM tonkho
  WHERE soluong>0 AND soluong <= $lowThreshold
")->fetchColumn();

/* ===== FILTER LIST (danh mục - chỉ lấy cap=3 để khớp schema) ===== */
$cats = $pdo->query("SELECT madm,tendm FROM danhmuc WHERE cap=3 ORDER BY tendm")->fetchAll();

/* ===== TOTAL FILTERED (để phân trang - loại bỏ group vì tonkho là 1-1) ===== */
$countSql = "
SELECT COUNT(*) FROM sanpham sp
WHERE (:q='' OR sp.tensp LIKE CONCAT('%',:q,'%'))
  AND (:dm=0 OR sp.madm=:dm)";
$cst = $pdo->prepare($countSql);
$cst->execute([':q'=>$q, ':dm'=>$dm]);
$totalFiltered = (int)$cst->fetchColumn();

$pages = max(1, (int)ceil($totalFiltered / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;

/* ===== DATA PAGE (điều chỉnh cho tonkho) ===== */
$sql = "
SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam,
       REPLACE(
           COALESCE(sp.hinhsp, '/Pharmacy-management/uploads/sp/placeholder.jpg'),
           '/Pharmacy-management/',
           '/pharmacy-management/'
       ) AS image,
       dm.tendm, dv.tendv,
       CASE WHEN tk.hsd < CURDATE()  AND tk.soluong>0 THEN tk.soluong ELSE 0 END AS sl_het_han,
       CASE WHEN tk.soluong>0 THEN tk.hsd END                           AS hsd_gan_nhat,
       tk.soluong AS ton
FROM sanpham sp
LEFT JOIN danhmuc  dm ON dm.madm=sp.madm
LEFT JOIN donvitinh dv ON dv.madv=sp.madv
LEFT JOIN tonkho   tk ON tk.masp=sp.masp
WHERE (:q='' OR sp.tensp LIKE CONCAT('%',:q,'%'))
  AND (:dm=0 OR sp.madm=:dm)
ORDER BY sp.tensp
LIMIT :lim OFFSET :off";
$st=$pdo->prepare($sql);
$st->bindValue(':q',$q);
$st->bindValue(':dm',$dm,PDO::PARAM_INT);
$st->bindValue(':lim',$perPage,PDO::PARAM_INT);
$st->bindValue(':off',$offset,PDO::PARAM_INT);
$st->execute();
$rows=$st->fetchAll();

/* helper build url */
function build_url($q,$dm,$page,$per){ return htmlspecialchars($_SERVER['PHP_SELF']).'?'.http_build_query(['q'=>$q,'dm'=>$dm,'page'=>$page,'per'=>$per]); }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Quản lý Sản phẩm</title>
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
  <?php $active='products'; include __DIR__.'/partials/header.php'; ?>

  <main class="flex-1 overflow-y-auto relative z-10">
    <header class="sticky top-0 z-20 glass border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <h1 class="text-3xl font-extrabold tracking-tight">Quản lý Sản phẩm</h1>
        <form method="get" class="flex gap-2 items-center">
          <div class="relative">
            <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Tìm kiếm sản phẩm…"
                   class="w-80 pl-10 pr-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
            <svg class="absolute left-3 top-2.5 text-slate-400" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="9" r="7"/><path d="m21 21-6-6"/></svg>
          </div>
          <select name="dm" class="px-3 py-2 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-blue-400">
            <option value="0">Tất cả danh mục</option>
            <?php foreach($cats as $c): ?>
              <option value="<?=$c['madm']?>" <?=$dm==$c['madm']?'selected':''?>><?=$c['tendm']?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="per" value="<?=$perPage?>">
          <button class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition">Lọc</button>
        </form>
      </div>
    </header>

    <section class="max-w-7xl mx-auto px-6 pt-6 pb-2">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Tổng số sản phẩm</div>
          <div class="text-3xl font-extrabold mt-1" data-count="<?=$totalProducts?>">0</div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">HẾT HẠN (còn tồn)</div>
          <div class="text-3xl font-extrabold mt-1 text-red-600" data-count="<?=$expiredProducts?>">0</div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Sắp hết hạn (≤ <?=$soonDays?> ngày)</div>
          <div class="text-3xl font-extrabold mt-1 text-amber-600" data-count="<?=$soonProducts?>">0</div>
        </div>
        <div class="glass rounded-2xl p-5 stat fade-in">
          <div class="text-slate-500">Tồn thấp (≤ <?=$lowThreshold?>)</div>
          <div class="text-3xl font-extrabold mt-1 text-violet-600" data-count="<?=$lowStock?>">0</div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 mb-4">
        <a href="<?=build_url('',0,1,$perPage)?>" class="pill px-3 py-1.5 rounded-full bg-white hover:bg-slate-50">Tất cả</a>
        <a href="<?=build_url('', $dm, 1, $perPage)?>" class="pill px-3 py-1.5 rounded-full bg-white hover:bg-slate-50">Xoá tìm</a>
        <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 pill">Kết quả: <?=$totalFiltered?></span>
        <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 pill">Trang <?=$page?>/<?=$pages?></span>
      </div>

      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach($rows as $r):
          $badge='';
          if ((int)$r['sl_het_han']>0) {
            $badge='<span class="ml-2 px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">Hết hạn</span>';
          } elseif (!empty($r['hsd_gan_nhat'])) {
            $d=new DateTime($r['hsd_gan_nhat']);
            $days=(new DateTime())->diff($d)->days * ($d > new DateTime() ? 1 : -1);
            if ($days>=0 && $days <= $soonDays) {
              $badge='<span class="ml-2 px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-700">Sắp hết hạn</span>';
            }
          }
          $low = ($r['ton']!==null && (int)$r['ton']>0 && (int)$r['ton'] <= $lowThreshold)
                  ? '<span class="ml-2 px-2 py-0.5 text-xs rounded bg-violet-100 text-violet-700">Tồn thấp</span>' : '';
          $price=(float)$r['giaban']; $sale=(float)$r['giagiam'];
        ?>
        <div class="glass rounded-2xl p-4 card fade-in border border-slate-200/70">
          <div class="flex gap-4">
            <img src="<?=htmlspecialchars($r['image'])?>" referrerpolicy="no-referrer"
                 class="w-24 h-24 object-cover rounded-xl border border-slate-200 bg-white" alt="">
            <div class="flex-1">
              <div class="font-semibold leading-snug">
                <?=htmlspecialchars($r['tensp'])?> <?=$badge?> <?=$low?>
              </div>
              <div class="text-sm text-slate-500 mt-0.5">
                <?=htmlspecialchars($r['tendm']??'Khác')?> • <?=htmlspecialchars($r['tendv']??'')?>
                <?php if($r['hsd_gan_nhat']): ?>
                  • HSD gần nhất: <?=date('d/m/Y', strtotime($r['hsd_gan_nhat']))?>
                <?php endif; ?>
              </div>
              <div class="mt-2">
                <?php if($sale>0): ?>
                  <span class="text-slate-400 line-through mr-2"><?=number_format($price)?>đ</span>
                  <span class="text-blue-600 font-bold"><?=number_format($price-$sale)?>đ</span>
                <?php else: ?>
                  <span class="text-blue-600 font-bold"><?=number_format($price)?>đ</span>
                <?php endif; ?>
                <?php if($r['ton']!==null): ?>
                  <span class="ml-2 text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-700">Tồn: <?= (int)$r['ton']?></span>
                <?php endif; ?>
              </div>
              <div class="mt-3 flex gap-2">
                <a href="/pharmacy-management/product_detail.php?masp=<?=$r['masp']?>"
                   class="px-3 py-1.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-sm transition">Xem</a>
                <a href="#"
                   class="px-3 py-1.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 text-sm transition">Sửa</a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-8 flex items-center justify-between">
        <div class="text-sm text-slate-600">
          Hiển thị
          <b><?=min($totalFiltered, $offset+1)?></b>–
          <b><?=min($totalFiltered, $offset + count($rows))?></b>
          / <b><?=$totalFiltered?></b> sản phẩm
        </div>
        <nav class="flex items-center gap-2">
          <?php if($page>1): ?>
            <a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50"
               href="<?=build_url($q,$dm,$page-1,$perPage)?>">« Trước</a>
          <?php endif; ?>

          <?php
            $win = 2; // số trang hai bên
            $start = max(1, $page-$win);
            $end   = min($pages, $page+$win);
            if ($start>1) {
              echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($q,$dm,1,$perPage).'">1</a>';
              if ($start>2) echo '<span class="px-2">…</span>';
            }
            for($p=$start;$p<=$end;$p++){
              $cls = $p==$page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-slate-50';
              echo '<a class="px-3 py-1.5 rounded-lg border '.$cls.'" href="'.build_url($q,$dm,$p,$perPage).'">'.$p.'</a>';
            }
            if ($end<$pages) {
              if ($end<$pages-1) echo '<span class="px-2">…</span>';
              echo '<a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50" href="'.build_url($q,$dm,$pages,$perPage).'">'.$pages.'</a>';
            }
          ?>

          <?php if($page<$pages): ?>
            <a class="px-3 py-1.5 rounded-lg border bg-white hover:bg-slate-50"
               href="<?=build_url($q,$dm,$page+1,$perPage)?>">Sau »</a>
          <?php endif; ?>
        </nav>
      </div>
    </section>
  </main>
</div>

<script>
document.querySelectorAll('[data-count]').forEach(el=>{
  const target=+el.dataset.count; let v=0, step=Math.max(1, Math.round(target/30));
  const tick=()=>{ v+=step; if(v>target) v=target; el.textContent=new Intl.NumberFormat('vi-VN').format(v); if(v<target) requestAnimationFrame(tick); };
  tick();
});
const io=new IntersectionObserver(es=>es.forEach(e=>e.isIntersecting&&e.target.classList.add('fade-in')), {threshold:.12});
document.querySelectorAll('.card').forEach(c=>io.observe(c));
</script>
</body>
</html>