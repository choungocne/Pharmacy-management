<?php
require __DIR__.'/db.php';
$pdo=pdo();

// input
$q = trim($_GET['q'] ?? '');
$madm = (int)($_GET['madm'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12; $offset = ($page-1)*$limit;

// danh mục cho combobox
$cats = $pdo->query("SELECT madm,tendm FROM danhmuc ORDER BY tendm")->fetchAll();

// where
$where = "1"; $p=[];
if($q!==''){ $where.=" AND sp.tensp LIKE :q"; $p[':q']="%$q%"; }
if($madm>0){ $where.=" AND sp.madm=:madm"; $p[':madm']=$madm; }

// count
$st=$pdo->prepare("SELECT COUNT(*) FROM sanpham sp WHERE $where"); $st->execute($p);
$total=(int)$st->fetchColumn(); $pages=max(1,ceil($total/$limit));

// data
$sql="SELECT sp.masp, sp.tensp, sp.giaban, sp.giagiam,
             dm.tendm, dv.tendv, th.tenth,
             tk.soluong AS ton,
             COALESCE(sp.hinhsp, v.main_image) AS image
      FROM sanpham sp
      LEFT JOIN danhmuc dm ON sp.madm=dm.madm
      LEFT JOIN donvitinh dv ON sp.madv=dv.madv
      LEFT JOIN thuonghieu th ON sp.math=th.math
      LEFT JOIN tonkho tk ON tk.masp=sp.masp
      LEFT JOIN v_sanpham_mainimage v ON v.masp=sp.masp
      WHERE $where
      ORDER BY sp.tensp ASC
      LIMIT :lim OFFSET :off";
$st=$pdo->prepare($sql);
foreach($p as $k=>$v) $st->bindValue($k,$v);
$st->bindValue(':lim',$limit,PDO::PARAM_INT);
$st->bindValue(':off',$offset,PDO::PARAM_INT);
$st->execute();
$items=$st->fetchAll();
?>
<!doctype html>
<html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Danh sách sản phẩm</title>
<style>
body{margin:0;background:#0f172a;color:#e2e8f0;font:15px/1.6 ui-sans-serif,system-ui}
.wrap{max-width:1100px;margin:24px auto;padding:0 16px}
h1{margin:0 0 12px;font-size:24px}
.toolbar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
input,select,button{background:#0b1226;border:1px solid rgba(148,163,184,.25);color:#e2e8f0;border-radius:10px;padding:10px 12px}
button{cursor:pointer;font-weight:700}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
@media (max-width:1024px){.grid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:640px){.grid{grid-template-columns:1fr}}
.card{background:linear-gradient(180deg,rgba(255,255,255,.02),transparent),#0b1226;border:1px solid rgba(148,163,184,.15);border-radius:14px;overflow:hidden}
.pic{aspect-ratio:1/1;background:#0a1021;display:flex;align-items:center;justify-content:center}
.pic img{max-width:100%;max-height:100%}
.box{padding:10px}
.name{font-weight:700;min-height:40px}
.meta{color:#94a3b8;font-size:12px}
.price{display:flex;gap:8px;align-items:baseline;margin-top:6px}
.now{font-weight:900}
.old{text-decoration:line-through;color:#94a3b8}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;background:rgba(16,185,129,.15);color:#bbf7d0;font-weight:700;font-size:12px}
.pager{display:flex;gap:6px;justify-content:center;margin:14px 0}
.pager a{padding:8px 12px;border:1px solid rgba(148,163,184,.25);border-radius:10px;color:#e2e8f0;text-decoration:none}
</style>
</head><body>
<div class="wrap">
  <h1>Sản phẩm</h1>
  <form class="toolbar" method="get" action="">
    <input type="text" name="q" placeholder="Tìm tên…" value="<?=htmlspecialchars($q)?>">
    <select name="madm">
      <option value="0">Tất cả danh mục</option>
      <?php foreach($cats as $c): ?>
        <option value="<?=$c['madm']?>" <?=$madm==$c['madm']?'selected':''?>><?=$c['tendm']?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Lọc</button>
    <div class="badge"><?=$total?> kết quả</div>
  </form>

  <div class="grid">
    <?php foreach($items as $r): 
      $img = $r['image'] ?: '/uploads/placeholder.png';
      $gia = (float)$r['giaban']; $giam=(float)$r['giagiam'];
    ?>
    <a class="card" href="product_detail.php?masp=<?=$r['masp']?>" style="text-decoration:none;color:inherit">
      <div class="pic"><img src="<?=htmlspecialchars($img)?>" alt="<?=htmlspecialchars($r['tensp'])?>"></div>
      <div class="box">
        <div class="name"><?=htmlspecialchars($r['tensp'])?></div>
        <div class="meta"><?=$r['tendm']?> • <?=$r['tendv']?> • tồn: <?=$r['ton']?:0?></div>
        <div class="price">
          <div class="now"><?=money_vn($gia - $giam)?> ₫</div>
          <?php if($giam>0): ?><div class="old"><?=money_vn($gia)?> ₫</div><?php endif; ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="pager">
    <?php for($i=1;$i<=$pages;$i++): 
      $qs=http_build_query(['q'=>$q,'madm'=>$madm,'page'=>$i]);
    ?>
      <a href="?<?=$qs?>" <?=$i==$page?'style="border-color:#10b981"':''?>><?=$i?></a>
    <?php endfor; ?>
  </div>
</div>
</body></html>
