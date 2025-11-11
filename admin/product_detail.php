<?php
$dbFile = __DIR__ . '/../db.php';
if (!file_exists($dbFile)) {
    http_response_code(500);
    exit('Missing database config');
}
require $dbFile;
$pdo = pdo();
$masp = (int)($_GET['masp'] ?? 0);
if ($masp <= 0) {
    http_response_code(400);
    exit('masp is required');
}

$productStmt = $pdo->prepare("
    SELECT sp.masp, sp.tensp, sp.giaban, sp.congdung, sp.cachdung, sp.xuatxu, sp.requires_rx,
           sp.hinhsp, dm.tendm, dv.tendv
    FROM sanpham sp
    LEFT JOIN danhmuc dm ON dm.madm = sp.madm
    LEFT JOIN donvitinh dv ON dv.madv = sp.madv
    WHERE sp.masp = :id
");
$productStmt->execute([':id' => $masp]);
$product = $productStmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found');
}

$stockStmt = $pdo->prepare("
    SELECT solo, soluong, hsd, chinhanh
    FROM tonkho
    WHERE masp = :id AND soluong > 0
    ORDER BY hsd IS NULL, hsd
");
$stockStmt->execute([':id' => $masp]);
$lots = $stockStmt->fetchAll();

if (!function_exists('money_vn')) {
    function money_vn($n) { return number_format((float)$n, 0, ',', '.'); }
}

$image = $product['hinhsp'] ?: '/pharmacy-management/uploads/sp/placeholder.jpg';
$image = str_replace('/Pharmacy-management/', '/pharmacy-management/', $image);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Chi tiet san pham #<?=htmlspecialchars($product['masp'])?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.13/dist/tailwind.min.css">
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold">Chi tiet san pham</h1>
      <a href="/pharmacy-management/admin/products.php" class="text-blue-600 hover:underline">&larr; Quay lai danh sach</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white shadow rounded-2xl p-5">
        <img src="<?=htmlspecialchars($image)?>" alt="Hinh san pham" class="w-full h-80 object-contain rounded-xl border border-slate-200 bg-slate-50">
      </div>
      <div class="bg-white shadow rounded-2xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-semibold"><?=htmlspecialchars($product['tensp'])?></h2>
          <?php if ((int)$product['requires_rx'] === 1): ?>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-600">Thuoc ke don</span>
          <?php endif; ?>
        </div>
        <div class="text-sm text-slate-600">
          <?=$product['tendm'] ?? 'Khong ro danh muc'?> &middot;
          <?=$product['tendv'] ?? 'Khong ro don vi'?> &middot;
          Xuat xu: <?=$product['xuatxu'] ?: 'Khong ro'?>
        </div>
        <div class="text-3xl font-bold text-blue-600"><?=money_vn($product['giaban'])?> đ</div>
        <div>
          <div class="font-semibold mb-1">Cong dung</div>
          <p class="text-sm whitespace-pre-wrap"><?=htmlspecialchars($product['congdung'] ?: 'Dang cap nhat')?></p>
        </div>
        <div>
          <div class="font-semibold mb-1">Cach dung</div>
          <p class="text-sm whitespace-pre-wrap"><?=htmlspecialchars($product['cachdung'] ?: 'Dang cap nhat')?></p>
        </div>
      </div>
    </div>

    <div class="bg-white shadow rounded-2xl p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold">Lo ton kho</h3>
        <span class="text-sm text-slate-500"><?=count($lots)?> lo con hang</span>
      </div>
      <?php if ($lots): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-slate-600">
              <tr>
                <th class="text-left px-3 py-2">So lo</th>
                <th class="text-left px-3 py-2">Chi nhanh</th>
                <th class="text-right px-3 py-2">So luong</th>
                <th class="text-left px-3 py-2">HSD</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($lots as $lot): ?>
              <tr class="border-b last:border-none">
                <td class="px-3 py-2"><?=htmlspecialchars($lot['solo'] ?? '-')?></td>
                <td class="px-3 py-2"><?=htmlspecialchars($lot['chinhanh'] ?? '-')?></td>
                <td class="px-3 py-2 text-right"><?=number_format((int)$lot['soluong'])?></td>
                <td class="px-3 py-2">
                  <?=$lot['hsd'] ? date('d/m/Y', strtotime($lot['hsd'])) : '-'?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-sm text-slate-500">Chua co lo ton kho nao con so luong.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
