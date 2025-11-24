<?php
// admin/product_detail.php

// 1. KẾT NỐI DB VÀ LẤY DỮ LIỆU
$dbFile = __DIR__ . '/../db.php'; // Đường dẫn có thể thay đổi tùy cấu trúc folder của bạn
// Fallback nếu file db ở chỗ khác (dựa trên products.php)
if (!file_exists($dbFile)) {
    foreach ([__DIR__ . '/../db.php', __DIR__ . '/db.php'] as $try) {
        if (file_exists($try)) { $dbFile = $try; break; }
    }
}
require_once $dbFile;

$pdo = isset($pdo) ? $pdo : pdo(); // Đảm bảo biến $pdo tồn tại

$masp = (int)($_GET['masp'] ?? 0);
if ($masp <= 0) {
    header('Location: products.php'); // Redirect nếu không có ID
    exit;
}

// Lấy thông tin sản phẩm
$productStmt = $pdo->prepare("
    SELECT sp.*, dm.tendm, dv.tendv
    FROM sanpham sp
    LEFT JOIN danhmuc dm ON dm.madm = sp.madm
    LEFT JOIN donvitinh dv ON dv.madv = sp.madv
    WHERE sp.masp = :id
");
$productStmt->execute([':id' => $masp]);
$product = $productStmt->fetch();

if (!$product) {
    echo "Sản phẩm không tồn tại.";
    exit;
}

// Lấy thông tin tồn kho
$stockStmt = $pdo->prepare("
    SELECT solo, soluong, hsd, chinhanh
    FROM tonkho
    WHERE masp = :id AND soluong > 0
    ORDER BY hsd ASC
");
$stockStmt->execute([':id' => $masp]);
$lots = $stockStmt->fetchAll();

// Xử lý hình ảnh
$image = $product['hinhsp'] ?: '/pharmacy-management/uploads/sp/placeholder.jpg';
$image = str_replace('/Pharmacy-management/', '/pharmacy-management/', $image);

// Helper format tiền
if (!function_exists('money_vn')) {
    function money_vn($n) { return number_format((float)$n, 0, ',', '.'); }
}
?>

<?php 
// Set active tab cho sidebar trong header.php
$active = 'products'; 
// Include header (đường dẫn dựa theo file products.php bạn cung cấp)
include __DIR__.'/partials/header.php'; 
?>

<style>
    .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: saturate(180%) blur(10px); }
    .fade-in { animation: fade .5s ease both; }
    @keyframes fade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .card-shadow { box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05); }
    /* Dark mode override nếu hệ thống có support */
    .dark .glass { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.05); }
</style>

<main class="flex-1 overflow-y-auto relative z-10 bg-slate-50/50 dark:bg-slate-900">
    <header class="sticky top-0 z-20 glass border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center gap-4">
            <a href="products.php" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition text-slate-500">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Chi tiết sản phẩm</h1>
                <div class="text-sm text-slate-500">Quản lý kho hàng & thông tin</div>
            </div>
            <div class="ml-auto flex gap-2">
                <button class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                    Sửa sản phẩm
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8 space-y-6">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-4 fade-in">
                <div class="glass p-4 rounded-3xl card-shadow border border-slate-200/60 dark:border-slate-700 h-full flex items-center justify-center bg-white dark:bg-slate-800">
                    <img src="<?=htmlspecialchars($image)?>" 
                         alt="<?=htmlspecialchars($product['tensp'])?>" 
                         class="w-full max-h-[400px] object-contain rounded-2xl drop-shadow-xl"
                         onerror="this.src='/pharmacy-management/uploads/sp/placeholder.jpg'">
                </div>
            </div>

            <div class="lg:col-span-8 space-y-6 fade-in" style="animation-delay: 0.1s">
                <div class="glass p-8 rounded-3xl card-shadow border border-slate-200/60 dark:border-slate-700 relative overflow-hidden">
                    
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-400/10 rounded-full blur-3xl"></div>

                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 dark:border-slate-700 pb-6 mb-6">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                                    <?=htmlspecialchars($product['tendm'] ?? 'Chưa phân loại')?>
                                </span>
                                <?php if ($product['requires_rx']): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 border border-rose-200 flex items-center gap-1">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                        Thuốc kê đơn
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h2 class="text-3xl font-extrabold text-slate-800 dark:text-white leading-tight">
                                <?=htmlspecialchars($product['tensp'])?>
                            </h2>
                            <div class="text-slate-500 mt-1 flex items-center gap-2">
                                <span>Mã SP: #<?=htmlspecialchars($product['masp'])?></span>
                                <span>•</span>
                                <span>ĐVT: <?=htmlspecialchars($product['tendv'] ?? 'Lẻ')?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-slate-400 mb-1">Giá bán lẻ</div>
                            <div class="text-4xl font-black text-blue-600 dark:text-blue-400">
                                <?=money_vn($product['giaban'])?><span class="text-xl text-slate-400 font-normal">đ</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-slate-200 flex items-center gap-2">
                                    <svg class="text-blue-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8"/><path d="M5 11V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"/><path d="M12 11v4"/></svg>
                                    Công dụng
                                </h3>
                                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1 leading-relaxed whitespace-pre-line">
                                    <?=htmlspecialchars($product['congdung'] ?: 'Đang cập nhật...')?>
                                </p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-slate-200 flex items-center gap-2">
                                    <svg class="text-emerald-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    Xuất xứ
                                </h3>
                                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
                                    <?=htmlspecialchars($product['xuatxu'] ?: 'Đang cập nhật...')?>
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-slate-200 flex items-center gap-2">
                                    <svg class="text-amber-500" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    Cách dùng & Liều lượng
                                </h3>
                                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1 leading-relaxed whitespace-pre-line">
                                    <?=htmlspecialchars($product['cachdung'] ?: 'Đang cập nhật...')?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fade-in" style="animation-delay: 0.2s">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="text-violet-500" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Thông tin Lô & Tồn kho
                </h3>
                <span class="text-sm font-medium bg-white px-3 py-1 rounded-full border shadow-sm dark:bg-slate-800 dark:border-slate-700">
                    Tổng lô: <?=count($lots)?>
                </span>
            </div>

            <div class="glass rounded-2xl overflow-hidden card-shadow border border-slate-200/60 dark:border-slate-700">
                <?php if ($lots): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-500 text-sm uppercase tracking-wider">
                                <th class="px-6 py-4 font-semibold">Số lô</th>
                                <th class="px-6 py-4 font-semibold">Chi nhánh/Kho</th>
                                <th class="px-6 py-4 font-semibold text-right">Số lượng tồn</th>
                                <th class="px-6 py-4 font-semibold text-right">Hạn sử dụng</th>
                                <th class="px-6 py-4 font-semibold text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <?php foreach ($lots as $lot): 
                                // Check HSD
                                $hsd = $lot['hsd'] ? strtotime($lot['hsd']) : null;
                                $isExpired = $hsd && $hsd < time();
                                $isNear = $hsd && $hsd < strtotime('+60 days') && !$isExpired;
                            ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-200">
                                    <?=htmlspecialchars($lot['solo'] ?? '---')?>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                                    <?=htmlspecialchars($lot['chinhanh'] ?? 'Kho tổng')?>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-700 dark:text-slate-200">
                                    <?=number_format((int)$lot['soluong'])?>
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums text-slate-600 dark:text-slate-400">
                                    <?=$lot['hsd'] ? date('d/m/Y', $hsd) : '---'?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($isExpired): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Hết hạn
                                        </span>
                                    <?php elseif ($isNear): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            Sắp hết hạn
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Còn hạn
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="p-12 text-center text-slate-400 flex flex-col items-center">
                        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 12v10H4V12M22 7H2v5h20V7zM12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                        <p>Hiện chưa có lô hàng nào trong kho cho sản phẩm này.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

</div> 
</body>
</html>