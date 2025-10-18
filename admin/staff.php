<?php
// admin/staff.php
declare(strict_types=1);
session_start();

/* ========= DB: lấy từ ../db.php, nếu không có thì tự kết nối ========= */
$pdo = null;
$rootDb = __DIR__ . '/../db.php';
if (is_file($rootDb)) {
    require_once $rootDb;                // file này nên tạo $pdo hoặc get_pdo()
}
if (!$pdo instanceof PDO) {
    if (function_exists('get_pdo')) { $pdo = get_pdo(); }
    elseif (function_exists('pdo'))   { $pdo = pdo(); }
}
if (!$pdo instanceof PDO) {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=nhathuocantam;charset=utf8mb4',
            'root', '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='';"
            ]
        );
    } catch (Throwable $e) {
        die('DB connect error: '.$e->getMessage());
    }
}

/* ========= RBAC nhẹ ========= */
function uid(): ?int { return $_SESSION['uid'] ?? 1; }   // demo
function has_perm(PDO $pdo, string $perm): bool {
    try {
        $u = uid(); if (!$u) return false;
        $st = $pdo->prepare("SELECT FIND_IN_SET(?, perms) ok FROM v_user_perms WHERE user_id=?");
        $st->execute([$perm,$u]); $r = $st->fetch();
        return $r ? ((int)$r['ok']===1) : true; // nếu chưa dựng view thì cho phép
    } catch (Throwable $e) { return true; }
}

/* ========= Helpers ========= */
function getv(string $k,$d=null){return $_GET[$k]??$d;}
function postv(string $k,$d=null){return $_POST[$k]??$d;}
function h($x){return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
function money($n){return number_format((float)$n,0,',','.');}

$active = 'staff';
$errors=[]; $notes=[];

/* ========= POST actions ========= */
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!has_perm($pdo,'hr.write')) {
        $errors[]='Bạn không có quyền thao tác nhân sự.';
    } else {
        $act=postv('action','');
        try{
            if($act==='create_staff'){
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO nhanvien(hoten,gt,ns,ngayvl,sdt,email,vitri,trangthai)
                               VALUES(?,?,?,?,?,?,?,?)")
                    ->execute([
                        postv('hoten'), postv('gt','Nam'),
                        postv('ns')?:null, postv('ngayvl')?:null,
                        postv('sdt')?:null, postv('email')?:null,
                        postv('vitri')?:null, postv('trangthai','dang_lam')
                    ]);
                $manv=(int)$pdo->lastInsertId();

                if(($cn=(int)postv('chinhanh_id',1))>0){
                    $pdo->prepare("INSERT INTO nv_chinhanh(manv,chinhanh_id,tu_ngay) VALUES(?,?,?)")
                        ->execute([$manv,$cn, postv('ngayvl')?:date('Y-m-d')]);
                }
                $pdo->prepare("INSERT IGNORE INTO nv_quydinh(manv,max_hours_week) VALUES(?,?)")
                    ->execute([$manv,(int)postv('max_hours_week',48)]);
                // tạo user nếu có bảng
                try{
                    $pdo->prepare("INSERT IGNORE INTO auth_user(id,email,full_name,status)
                                   VALUES(?,?,?,?)")
                        ->execute([$manv, postv('email')?:("nv$manv@antam.local"), postv('hoten'), 'active']);
                }catch(Throwable $x){}
                $pdo->commit();
                $notes[]="Đã thêm nhân viên #$manv.";
            }

            if($act==='update_staff'){
                $manv=(int)postv('manv');
                $pdo->prepare("UPDATE nhanvien
                               SET hoten=?,gt=?,ns=?,ngayvl=?,sdt=?,email=?,vitri=?,trangthai=?,ngaynghi=?
                               WHERE manv=?")
                    ->execute([
                        postv('hoten'),postv('gt','Nam'),
                        postv('ns')?:null, postv('ngayvl')?:null,
                        postv('sdt')?:null, postv('email')?:null,
                        postv('vitri')?:null, postv('trangthai','dang_lam'),
                        postv('ngaynghi')?:null, $manv
                    ]);

                if(($newCn=(int)postv('chinhanh_id'))>0){
                    $s=$pdo->prepare("SELECT id,chinhanh_id FROM nv_chinhanh WHERE manv=? ORDER BY id DESC LIMIT 1");
                    $s->execute([$manv]); $cur=$s->fetch();
                    if(!$cur || (int)$cur['chinhanh_id']!==$newCn){
                        if($cur){
                            $pdo->prepare("UPDATE nv_chinhanh SET den_ngay=? WHERE id=?")
                                ->execute([date('Y-m-d'),$cur['id']]);
                        }
                        $pdo->prepare("INSERT INTO nv_chinhanh(manv,chinhanh_id,tu_ngay) VALUES(?,?,?)")
                            ->execute([$manv,$newCn,date('Y-m-d')]);
                        $pdo->prepare("INSERT INTO nhanvien_biendong(manv,loai,tu_cn,den_cn,ghi_chu)
                                       VALUES(?,?,?,?,?)")
                            ->execute([$manv,'chuyen_cn',$cur['chinhanh_id']??null,$newCn,'Chuyển chi nhánh']);
                    }
                }

                if(($max=postv('max_hours_week'))!==null){
                    $pdo->prepare("INSERT INTO nv_quydinh(manv,max_hours_week) VALUES(?,?)
                                   ON DUPLICATE KEY UPDATE max_hours_week=VALUES(max_hours_week)")
                        ->execute([$manv,(int)$max]);
                }
                $notes[]="Đã cập nhật #$manv.";
            }

            if($act==='soft_delete'){
                $manv=(int)postv('manv');
                $pdo->prepare("UPDATE nhanvien SET trangthai='da_nghi',ngaynghi=? WHERE manv=?")
                    ->execute([date('Y-m-d'),$manv]);
                $pdo->prepare("INSERT INTO nhanvien_biendong(manv,loai,ghi_chu) VALUES(?,?,?)")
                    ->execute([$manv,'thoi_viec','Thôi việc']);
                $notes[]="Đã đánh dấu nghỉ việc #$manv.";
            }

            if($act==='add_shift'){
                $manv=(int)postv('manv');
                $maca=postv('maca'); $ngay=postv('ngay')?:date('Y-m-d');
                $c=$pdo->prepare("SELECT id,start_time,end_time FROM ca_lam WHERE maca=?");
                $c->execute([$maca]); $ca=$c->fetch();
                if(!$ca) throw new RuntimeException('Mã ca không hợp lệ');
                $start="$ngay {$ca['start_time']}"; $end="$ngay {$ca['end_time']}";
                // chống trùng
                $q=$pdo->prepare("SELECT COUNT(*) FROM nv_lich WHERE manv=? AND ngay=? AND NOT(end_dt<=? OR start_dt>=?)");
                $q->execute([$manv,$ngay,$start,$end]);
                if((int)$q->fetchColumn()>0) throw new RuntimeException('Trùng ca trong ngày');
                $pdo->prepare("INSERT INTO nv_lich(manv,ngay,start_dt,end_dt,ca_id) VALUES(?,?,?,?,?)")
                    ->execute([$manv,$ngay,$start,$end,$ca['id']]);
                $notes[]="Đã phân ca $maca ngày $ngay cho #$manv.";
            }

            if($act==='add_attendance'){
                $pdo->prepare("INSERT INTO cham_cong(manv,check_in,check_out,source,note)
                               VALUES(?,?,?,?,?)")
                    ->execute([
                        (int)postv('manv'),
                        postv('check_in'),
                        postv('check_out')?:null,
                        'web', postv('note')?:null
                    ]);
                $notes[]="Đã ghi chấm công.";
            }

        }catch(Throwable $e){ $errors[]=$e->getMessage(); if($pdo->inTransaction()) $pdo->rollBack(); }
    }
}

/* ========= Filters & paging ========= */
$q=trim((string)getv('q',''));
$status=getv('status','all');
$cn_id=(int)getv('cn',0);
$page=max(1,(int)getv('page',1));
$limit=max(6,(int)getv('limit',12));
$offset=($page-1)*$limit;

/* ========= KPI ========= */
$kpi=['total'=>0,'dang_lam'=>0,'tam_nghi'=>0,'da_nghi'=>0,'violators'=>0];
$kpi['total']    =(int)$pdo->query("SELECT COUNT(*) FROM nhanvien")->fetchColumn();
$kpi['dang_lam'] =(int)$pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='dang_lam'")->fetchColumn();
$kpi['tam_nghi'] =(int)$pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='tam_nghi'")->fetchColumn();
$kpi['da_nghi']  =(int)$pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='da_nghi'")->fetchColumn();
try{ $kpi['violators']=(int)$pdo->query("SELECT COUNT(DISTINCT manv) FROM v_gio_tuan_vi_pham")->fetchColumn(); }
catch(Throwable $e){ $kpi['violators']=0; }

/* ========= Master data ========= */
try{ $branches=$pdo->query("SELECT id,macn,tencn FROM chinhanh ORDER BY id")->fetchAll(); }
catch(Throwable $e){ $branches=[]; }
try{ $shifts=$pdo->query("SELECT maca,tenca FROM ca_lam ORDER BY id")->fetchAll(); }
catch(Throwable $e){ $shifts=[]; }

/* ========= WHERE ========= */
$where=[]; $args=[];
if($q!==''){ $where[]="(n.hoten LIKE ? OR n.email LIKE ? OR n.sdt LIKE ?)"; $args[]="%$q%";$args[]="%$q%";$args[]="%$q%"; }
if(in_array($status,['dang_lam','tam_nghi','da_nghi'],true)){ $where[]="n.trangthai=?"; $args[]=$status; }
if($cn_id>0){ $where[]="cn.chinhanh_id=?"; $args[]=$cn_id; }
$whereSql=$where?('WHERE '.implode(' AND ',$where)):'';

/* ========= Count ========= */
$csql="SELECT COUNT(*) FROM nhanvien n
       LEFT JOIN (
         SELECT nc.manv,nc.chinhanh_id FROM nv_chinhanh nc
         JOIN (SELECT manv,MAX(id) mid FROM nv_chinhanh GROUP BY manv) t ON t.mid=nc.id
       ) cn ON cn.manv=n.manv
       $whereSql";
$st=$pdo->prepare($csql); $st->execute($args); $totalRows=(int)$st->fetchColumn();
$totalPages=max(1,(int)ceil($totalRows/$limit));

/* ========= List ========= */
$sql="SELECT n.manv,n.hoten,n.gt,n.ns,n.ngayvl,n.sdt,n.email,n.vitri,n.trangthai,
             cn.chinhanh_id, ch.tencn,
             k.so_don, k.doanh_thu, k.ty_le_upsell,
             q.max_hours_week, ROUND(COALESCE(g.hours,0),2) hours_week,
             CASE WHEN g.hours>q.max_hours_week THEN 1 ELSE 0 END over_week
      FROM nhanvien n
      LEFT JOIN (
        SELECT nc.manv,nc.chinhanh_id,ch.tencn
        FROM nv_chinhanh nc
        JOIN (SELECT manv,MAX(id) mid FROM nv_chinhanh GROUP BY manv) t ON t.mid=nc.id
        JOIN chinhanh ch ON ch.id=nc.chinhanh_id
      ) cn ON cn.manv=n.manv
      LEFT JOIN v_kpi_nv_30d k ON k.manv=n.manv
      LEFT JOIN nv_quydinh q ON q.manv=n.manv
      LEFT JOIN (
        SELECT v1.manv,v1.hours FROM v_gio_tuan v1
        JOIN (SELECT manv,MAX(yw) myw FROM v_gio_tuan GROUP BY manv) last
             ON last.manv=v1.manv AND last.myw=v1.yw
      ) g ON g.manv=n.manv
      LEFT JOIN chinhanh ch ON ch.id=cn.chinhanh_id
      $whereSql
      ORDER BY n.manv DESC
      LIMIT $limit OFFSET $offset";
$st=$pdo->prepare($sql); $st->execute($args); $rows=$st->fetchAll();

/* ========= Header ========= */
$header = __DIR__.'/partials/header.php';
include $header;
?>
<main class="flex-1 p-6 overflow-y-auto bg-[url('/Pharmacy-management/static/dots.svg')] bg-cover">
  <div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Quản lý Nhân viên</h1>
      <form method="get" class="flex items-center gap-2">
        <input name="q" value="<?=h($q)?>" placeholder="Tìm tên, email, SĐT..."
               class="px-4 py-2 rounded-xl border border-gray-300 w-72 focus:outline-none focus:ring-2 focus:ring-sky-400">
        <select name="status" class="px-3 py-2 rounded-xl border border-gray-300">
          <option value="all"      <?=$status==='all'?'selected':''?>>Tất cả</option>
          <option value="dang_lam" <?=$status==='dang_lam'?'selected':''?>>Đang làm</option>
          <option value="tam_nghi" <?=$status==='tam_nghi'?'selected':''?>>Tạm nghỉ</option>
          <option value="da_nghi"  <?=$status==='da_nghi'?'selected':''?>>Đã nghỉ</option>
        </select>
        <select name="cn" class="px-3 py-2 rounded-xl border border-gray-300">
          <option value="0">Tất cả CN</option>
          <?php foreach($branches as $b): ?>
            <option value="<?=$b['id']?>" <?=$cn_id===$b['id']?'selected':''?>><?=$b['tencn']?></option>
          <?php endforeach; ?>
        </select>
        <button class="px-4 py-2 bg-sky-600 text-white rounded-xl hover:bg-sky-700">Lọc</button>
      </form>
    </div>

    <?php if($errors): ?>
      <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-3 text-red-700"><?=h(implode(' | ',$errors))?></div>
    <?php endif; ?>
    <?php if($notes): ?>
      <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-emerald-700"><?=h(implode(' | ',$notes))?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <div class="rounded-2xl bg-white shadow p-4"><div class="text-gray-500">Tổng NV</div><div class="text-3xl font-bold text-sky-700"><?=$kpi['total']?></div></div>
      <div class="rounded-2xl bg-white shadow p-4"><div class="text-gray-500">Đang làm</div><div class="text-3xl font-bold text-emerald-600"><?=$kpi['dang_lam']?></div></div>
      <div class="rounded-2xl bg-white shadow p-4"><div class="text-gray-500">Tạm nghỉ</div><div class="text-3xl font-bold text-amber-600"><?=$kpi['tam_nghi']?></div></div>
      <div class="rounded-2xl bg-white shadow p-4"><div class="text-gray-500">Đã nghỉ</div><div class="text-3xl font-bold text-gray-800"><?=$kpi['da_nghi']?></div></div>
      <div class="rounded-2xl bg-white shadow p-4"><div class="text-gray-500">Vi phạm giờ/tuần</div><div class="text-3xl font-bold text-rose-600"><?=$kpi['violators']?></div></div>
    </div>

    <?php if(has_perm($pdo,'hr.write')): ?>
    <details class="mb-6 open:border open:border-gray-200 rounded-2xl">
      <summary class="cursor-pointer select-none px-4 py-3 bg-white rounded-2xl shadow hover:bg-gray-50">+ Thêm nhân viên</summary>
      <form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-3 p-4 bg-white rounded-b-2xl border-t">
        <input type="hidden" name="action" value="create_staff">
        <input name="hoten" required placeholder="Họ tên" class="border rounded-xl px-3 py-2">
        <select name="gt" class="border rounded-xl px-3 py-2"><option>Nam</option><option>Nữ</option><option>Khác</option></select>
        <input type="date" name="ns" class="border rounded-xl px-3 py-2">
        <input type="date" name="ngayvl" class="border rounded-xl px-3 py-2">
        <input name="sdt" placeholder="SĐT" class="border rounded-xl px-3 py-2">
        <input name="email" placeholder="Email" class="border rounded-xl px-3 py-2">
        <input name="vitri" placeholder="Vị trí" class="border rounded-xl px-3 py-2">
        <select name="chinhanh_id" class="border rounded-xl px-3 py-2">
          <?php foreach($branches as $b): ?><option value="<?=$b['id']?>"><?=$b['tencn']?></option><?php endforeach;?>
        </select>
        <input name="max_hours_week" type="number" min="1" value="48" class="border rounded-xl px-3 py-2" placeholder="Giờ/tuần">
        <select name="trangthai" class="border rounded-xl px-3 py-2"><option value="dang_lam">Đang làm</option><option value="tam_nghi">Tạm nghỉ</option><option value="da_nghi">Đã nghỉ</option></select>
        <div class="md:col-span-4"><button class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700">Lưu</button></div>
      </form>
    </details>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <?php foreach($rows as $r): ?>
      <div class="rounded-2xl bg-white shadow p-4">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xl font-semibold text-gray-900">
              <?=h($r['hoten'])?>
              <span class="ml-2 text-xs px-2 py-0.5 rounded-full <?=$r['trangthai']=='dang_lam'?'bg-emerald-100 text-emerald-700':($r['trangthai']=='tam_nghi'?'bg-amber-100 text-amber-700':'bg-gray-200 text-gray-700')?>"><?=h($r['trangthai'])?></span>
            </div>
            <div class="text-sm text-gray-600 mt-1">Mã #<?=$r['manv']?> • GT: <?=h($r['gt'])?> • Vị trí: <?=h($r['vitri']??'')?></div>
            <div class="text-sm text-gray-600">CN: <?=h($r['tencn']??'—')?> • Vào làm: <?=h($r['ngayvl']??'—')?></div>
            <div class="text-sm text-gray-600">Email: <?=h($r['email']??'—')?> • SĐT: <?=h($r['sdt']??'—')?></div>
          </div>
          <span class="text-xs px-2 py-1 rounded-lg <?=$r['over_week']? 'text-rose-700 bg-rose-100' : 'text-sky-700 bg-sky-100'?>">
            <?=$r['hours_week']?>h / <?=$r['max_hours_week']?>h
          </span>
        </div>

        <div class="grid grid-cols-3 gap-3 mt-4">
          <div class="rounded-xl bg-gray-50 p-3"><div class="text-xs text-gray-500">Đơn (30 ngày)</div><div class="text-lg font-semibold"><?=(int)$r['so_don']?></div></div>
          <div class="rounded-xl bg-gray-50 p-3"><div class="text-xs text-gray-500">Doanh thu</div><div class="text-lg font-semibold"><?=money($r['doanh_thu']??0)?>đ</div></div>
          <div class="rounded-xl bg-gray-50 p-3"><div class="text-xs text-gray-500">Upsell</div><div class="text-lg font-semibold"><?=isset($r['ty_le_upsell'])?round((float)$r['ty_le_upsell']*100,1).'%' :'0%'?></div></div>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
          <?php if(has_perm($pdo,'hr.write')): ?>
          <form method="post" class="border rounded-xl p-3">
            <input type="hidden" name="action" value="update_staff">
            <input type="hidden" name="manv" value="<?=$r['manv']?>">
            <div class="grid grid-cols-2 gap-2 text-sm">
              <input name="hoten" value="<?=h($r['hoten'])?>" class="border rounded-lg px-2 py-1" placeholder="Họ tên">
              <select name="gt" class="border rounded-lg px-2 py-1">
                <option <?=$r['gt']=='Nam'?'selected':''?>>Nam</option>
                <option <?=$r['gt']=='Nữ'?'selected':''?>>Nữ</option>
                <option <?=$r['gt']=='Khác'?'selected':''?>>Khác</option>
              </select>
              <input type="date" name="ns" value="<?=h($r['ns'])?>" class="border rounded-lg px-2 py-1">
              <input type="date" name="ngayvl" value="<?=h($r['ngayvl'])?>" class="border rounded-lg px-2 py-1">
              <input name="sdt" value="<?=h($r['sdt'])?>" class="border rounded-lg px-2 py-1" placeholder="SĐT">
              <input name="email" value="<?=h($r['email'])?>" class="border rounded-lg px-2 py-1" placeholder="Email">
              <input name="vitri" value="<?=h($r['vitri'])?>" class="border rounded-lg px-2 py-1" placeholder="Vị trí">
              <select name="chinhanh_id" class="border rounded-lg px-2 py-1">
                <?php foreach($branches as $b): ?>
                  <option value="<?=$b['id']?>" <?=((int)$r['chinhanh_id']===$b['id'])?'selected':''?>><?=$b['tencn']?></option>
                <?php endforeach; ?>
              </select>
              <select name="trangthai" class="border rounded-lg px-2 py-1">
                <option value="dang_lam" <?=$r['trangthai']=='dang_lam'?'selected':''?>>Đang làm</option>
                <option value="tam_nghi" <?=$r['trangthai']=='tam_nghi'?'selected':''?>>Tạm nghỉ</option>
                <option value="da_nghi"  <?=$r['trangthai']=='da_nghi'?'selected':''?>>Đã nghỉ</option>
              </select>
              <input type="date" name="ngaynghi" class="border rounded-lg px-2 py-1">
              <input type="number" min="1" name="max_hours_week" value="<?= (int)$r['max_hours_week'] ?>" class="border rounded-lg px-2 py-1" placeholder="Giờ/tuần">
            </div>
            <div class="mt-2"><button class="px-3 py-1.5 bg-sky-600 text-white rounded-lg text-sm hover:bg-sky-700">Lưu</button></div>
          </form>

          <div class="grid grid-cols-1 gap-3">
            <form method="post" class="border rounded-xl p-3">
              <input type="hidden" name="action" value="add_shift">
              <input type="hidden" name="manv" value="<?=$r['manv']?>">
              <div class="text-sm mb-2 font-semibold">Phân ca</div>
              <div class="flex gap-2">
                <input type="date" name="ngay" value="<?=date('Y-m-d')?>" class="border rounded-lg px-2 py-1 text-sm">
                <select name="maca" class="border rounded-lg px-2 py-1 text-sm">
                  <?php foreach($shifts as $s): ?><option value="<?=$s['maca']?>"><?=$s['maca']?> - <?=$s['tenca']?></option><?php endforeach;?>
                </select>
                <button class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Gán</button>
              </div>
            </form>

            <form method="post" class="border rounded-xl p-3">
              <input type="hidden" name="action" value="add_attendance">
              <input type="hidden" name="manv" value="<?=$r['manv']?>">
              <div class="text-sm mb-2 font-semibold">Chấm công</div>
              <div class="grid grid-cols-2 gap-2">
                <input type="datetime-local" name="check_in" class="border rounded-lg px-2 py-1 text-sm" required>
                <input type="datetime-local" name="check_out" class="border rounded-lg px-2 py-1 text-sm">
              </div>
              <input name="note" placeholder="Ghi chú" class="mt-2 border rounded-lg px-2 py-1 text-sm w-full">
              <div class="mt-2"><button class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">Lưu</button></div>
            </form>
          </div>
          <?php endif; ?>
        </div>

        <?php if(has_perm($pdo,'hr.write')): ?>
        <form method="post" class="mt-3 text-right" onsubmit="return confirm('Đánh dấu nghỉ việc NV #<?=$r['manv']?>?');">
          <input type="hidden" name="action" value="soft_delete">
          <input type="hidden" name="manv" value="<?=$r['manv']?>">
          <button class="px-3 py-1.5 bg-rose-600 text-white rounded-lg text-sm hover:bg-rose-700">Đánh dấu nghỉ việc</button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-6 flex items-center justify-between">
      <div class="text-sm text-gray-600">Kết quả: <?=$totalRows?> • Trang <?=$page?>/<?=$totalPages?></div>
      <div class="flex gap-2">
        <?php if($page>1): ?>
          <a class="px-3 py-2 bg-white border rounded-lg hover:bg-gray-50"
             href="?<?=http_build_query(array_merge($_GET,['page'=>$page-1]))?>">« Trước</a>
        <?php endif; ?>
        <?php if($page<$totalPages): ?>
          <a class="px-3 py-2 bg-white border rounded-lg hover:bg-gray-50"
             href="?<?=http_build_query(array_merge($_GET,['page'=>$page+1]))?>">Sau »</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-white rounded-2xl shadow p-4">
        <div class="font-semibold mb-2">Vi phạm giờ/tuần</div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="py-2">Mã</th><th>Họ tên</th><th>Tuần</th><th>Giờ</th><th>Hạn mức</th></tr></thead>
            <tbody>
            <?php
            try{ $vp=$pdo->query("SELECT manv,hoten,yw,hours,max_hours_week FROM v_gio_tuan_vi_pham ORDER BY yw DESC, hoten")->fetchAll(); }
            catch(Throwable $e){ $vp=[]; }
            if(!$vp) echo '<tr><td colspan="5" class="py-2 text-gray-500">Không có.</td></tr>';
            foreach($vp as $row): ?>
              <tr class="border-t"><td class="py-1">#<?=$row['manv']?></td><td><?=h($row['hoten'])?></td><td><?=$row['yw']?></td><td class="text-rose-600 font-semibold"><?=$row['hours']?></td><td><?=$row['max_hours_week']?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow p-4">
        <div class="font-semibold mb-2">Biến động nhân sự (tháng)</div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="py-2">Tháng</th><th>Tuyển mới</th><th>Thôi việc</th><th>Chuyển CN</th></tr></thead>
            <tbody>
            <?php
            try{ $bd=$pdo->query("SELECT thang,tuyen_moi,thoi_viec,chuyen_cn FROM v_hr_biendong_thang ORDER BY thang DESC LIMIT 12")->fetchAll(); }
            catch(Throwable $e){ $bd=[]; }
            if(!$bd) echo '<tr><td colspan="4" class="py-2 text-gray-500">Chưa có dữ liệu.</td></tr>';
            foreach($bd as $row): ?>
              <tr class="border-t"><td class="py-1"><?=h($row['thang'])?></td><td><?= (int)$row['tuyen_moi'] ?></td><td><?= (int)$row['thoi_viec'] ?></td><td><?= (int)$row['chuyen_cn'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>
