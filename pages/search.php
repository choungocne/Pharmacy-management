<?php
// =================================================================
// TỆP: search.php (Tra cứu Thuốc, Dược chất, Dược liệu - UI UPDATE)
// =================================================================

require_once 'db.php'; 

global $base_url; 
if (!isset($base_url)) $base_url = '/Pharmacy-management'; 

// =================================================================
// PHẦN 1: HÀM XỬ LÝ DỮ LIỆU
// =================================================================

function get_featured_duoclieu(): array {
    $pdo = pdo();
    // Lấy thêm mô tả ngắn nếu có
    $sql = "SELECT madl, tendl, hinh_anh, cong_dung, tenkhoahoc FROM duoclieu WHERE trangthai = 1 ORDER BY RAND() LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_featured_duocchat(): array {
    $pdo = pdo();
    $sql = "SELECT madc, tendc, phan_loai, anh_cau_truc, tacdung FROM duocchat WHERE trangthai = 1 ORDER BY RAND() LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =================================================================
// PHẦN 2: XỬ LÝ YÊU CẦU & THIẾT LẬP BIẾN
// =================================================================
$featured_duoclieu = get_featured_duoclieu();
$featured_duocchat = get_featured_duocchat();

$search_categories = [
    ['label' => 'Thuốc & Sản phẩm', 'value' => 'drug', 'icon' => 'fa-pills'],
    ['label' => 'Hoạt chất', 'value' => 'duocchat', 'icon' => 'fa-flask'],
    ['label' => 'Dược liệu', 'value' => 'duoclieu', 'icon' => 'fa-leaf'],
];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root {
        --primary-color: #0284c7; /* Sky 600 */
        --primary-dark: #0369a1;
        --accent-green: #10b981; /* Emerald 500 */
        --accent-purple: #8b5cf6; /* Violet 500 */
        --bg-light: #f8fafc;
        --text-main: #334155;
        --text-light: #64748b;
        --white: #ffffff;
    }

    body {
        background-color: var(--bg-light);
        font-family: 'Inter', sans-serif;
    }

    /* 1. HERO SEARCH SECTION */
    .search-hero {
        background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
        padding: 60px 20px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
        position: relative;
        overflow: hidden;
    }
    
    /* Trang trí nền */
    .hero-bg-decoration {
        position: absolute;
        top: -50px; right: -50px;
        width: 300px; height: 300px;
        background: rgba(2, 132, 199, 0.05);
        border-radius: 50%;
        z-index: 0;
    }

    .search-container {
        max-width: 800px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .search-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-dark);
        margin-bottom: 10px;
    }

    .search-subtitle {
        color: var(--text-light);
        font-size: 1.1rem;
        margin-bottom: 40px;
    }

    /* Search Box & Tabs */
    .search-wrapper {
        background: var(--white);
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
    }

    .search-tabs {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    /* Ẩn radio mặc định */
    .search-tabs input[type="radio"] {
        display: none;
    }

    .tab-label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 50px;
        background: #f1f5f9;
        color: var(--text-light);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .tab-label i { font-size: 1.1rem; }

    /* Trạng thái Checked */
    .search-tabs input[type="radio"]:checked + .tab-label {
        background: #e0f2fe;
        color: var(--primary-color);
        border-color: var(--primary-color);
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.2);
    }

    /* Input Bar */
    .search-input-group {
        display: flex;
        position: relative;
    }

    .search-field {
        width: 100%;
        padding: 18px 25px;
        padding-right: 140px; /* Chừa chỗ cho nút */
        border: 2px solid #e2e8f0;
        border-radius: 50px;
        font-size: 16px;
        outline: none;
        transition: 0.3s;
    }

    .search-field:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1);
    }

    .search-btn {
        position: absolute;
        right: 6px;
        top: 6px;
        bottom: 6px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 40px;
        padding: 0 30px;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .search-btn:hover {
        background: var(--primary-dark);
        transform: scale(1.02);
    }

    /* 2. SECTION STYLES */
    .section-wrapper {
        padding: 60px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 30px;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 15px;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .grid-layout {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 25px;
    }

    /* 3. CARDS DESIGN */
    .info-card {
        background: var(--white);
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
    }

    /* Card Dược Liệu (Style Xanh lá) */
    .card-duoclieu .card-img-wrap {
        height: 160px;
        background: #f0fdf4;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 15px;
    }
    .card-duoclieu:hover { border-color: var(--accent-green); }
    .card-duoclieu .card-badge { background: var(--accent-green); }

    /* Card Dược Chất (Style Tím/Xanh) */
    .card-duocchat .card-img-wrap {
        height: 160px;
        background: #f5f3ff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 15px;
        position: relative;
    }
    .card-duocchat:hover { border-color: var(--accent-purple); }
    .card-duocchat .card-badge { background: var(--accent-purple); }

    /* Image Styles */
    .card-img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        transition: transform 0.5s ease;
    }
    .info-card:hover .card-img { transform: scale(1.1); }

    /* Card Body */
    .card-body {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .card-badge {
        align-self: flex-start;
        font-size: 0.75rem;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .card-desc {
        font-size: 0.9rem;
        color: var(--text-light);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .search-title { font-size: 2rem; }
        .search-btn { position: static; width: 100%; margin-top: 10px; justify-content: center; }
        .search-field { padding-right: 25px; }
        .search-input-group { flex-direction: column; }
        .search-wrapper { padding: 20px; }
    }
</style>

<section class="search-hero">
    <div class="hero-bg-decoration"></div>
    
    <div class="search-container">
        <h1 class="search-title">Tra Cứu Dược Điển</h1>
        <p class="search-subtitle">Tìm kiếm thông tin chính xác về Thuốc, Hoạt chất và Dược liệu y học cổ truyền.</p>

        <div class="search-wrapper">
            <form id="searchForm" action="<?= $base_url ?>/base.php" method="GET">
                <input type="hidden" name="page" id="pageTarget" value="search_results_drug">
                
                <div class="search-tabs">
                    <?php foreach ($search_categories as $idx => $cat): ?>
                        <label>
                            <input type="radio" 
                                   name="type" 
                                   value="<?= $cat['value'] ?>" 
                                   data-target="search_results_<?= $cat['value'] ?>"
                                   <?= ($idx === 0) ? 'checked' : '' ?>>
                            <span class="tab-label">
                                <i class="fas <?= $cat['icon'] ?>"></i>
                                <?= $cat['label'] ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="search-input-group">
                    <input type="text" 
                           class="search-field" 
                           id="searchInput" 
                           name="q" 
                           placeholder="Nhập tên thuốc, hoạt chất hoặc dược liệu..." 
                           required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Tra Cứu
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="section-wrapper">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-leaf" style="color: var(--accent-green);"></i>
            Dược Liệu Thiên Nhiên
        </div>
        <a href="#" style="color: var(--primary-color); font-weight: 600; font-size: 0.9rem;">Xem tất cả <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="grid-layout">
        <?php if (!empty($featured_duoclieu)): ?>
            <?php foreach ($featured_duoclieu as $item): ?>
                <a href="<?= $base_url ?>/base.php?page=duoclieu_detail&madl=<?= $item['madl'] ?>" class="info-card card-duoclieu">
                    <div class="card-img-wrap">
                        <img src="<?= htmlspecialchars($item['hinh_anh'] ?? $base_url . '/static/img/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($item['tendl']) ?>" 
                             class="card-img"
                             onerror="this.src='<?= $base_url ?>/static/img/placeholder.jpg'">
                    </div>
                    <div class="card-body">
                        <span class="card-badge">Dược liệu</span>
                        <h3 class="card-title"><?= htmlspecialchars($item['tendl']) ?></h3>
                        <p class="card-desc" title="<?= htmlspecialchars($item['cong_dung']) ?>">
                            <?= !empty($item['tenkhoahoc']) ? '<i>'.htmlspecialchars($item['tenkhoahoc']).'</i><br>' : '' ?>
                            <?= htmlspecialchars($item['cong_dung'] ?? 'Đang cập nhật công dụng...') ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #999;">Chưa có dữ liệu dược liệu.</p>
        <?php endif; ?>
    </div>
</section>

<section class="section-wrapper" style="background: #fff; border-radius: 20px; margin-bottom: 40px;">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-flask" style="color: var(--accent-purple);"></i>
            Hoạt Chất Y Học
        </div>
        <a href="#" style="color: var(--primary-color); font-weight: 600; font-size: 0.9rem;">Xem tất cả <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="grid-layout">
        <?php if (!empty($featured_duocchat)): ?>
            <?php foreach ($featured_duocchat as $item): ?>
                <a href="<?= $base_url ?>/base.php?page=duocchat_detail&madc=<?= $item['madc'] ?>" class="info-card card-duocchat">
                    <div class="card-img-wrap">
                        <?php if(!empty($item['anh_cau_truc'])): ?>
                            <img src="<?= htmlspecialchars($item['anh_cau_truc']) ?>" 
                                 alt="<?= htmlspecialchars($item['tendc']) ?>" 
                                 class="card-img"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <i class="fas fa-prescription-bottle-alt" style="font-size: 40px; color: #cbd5e1; display: none;"></i>
                        <?php else: ?>
                            <i class="fas fa-atom" style="font-size: 50px; color: #ddd;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <span class="card-badge">Hoạt chất</span>
                        <h3 class="card-title"><?= htmlspecialchars($item['tendc']) ?></h3>
                        <p class="card-desc">
                            <strong>Phân loại:</strong> <?= htmlspecialchars($item['phan_loai'] ?? 'N/A') ?><br>
                            <?= htmlspecialchars($item['tacdung'] ?? '') ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #999;">Chưa có dữ liệu dược chất.</p>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageTarget = document.getElementById('pageTarget');
    const filterRadios = document.querySelectorAll('input[name="type"]');
    const searchInput = document.getElementById('searchInput');
    
    // Map placeholder theo từng loại
    const placeholders = {
        'drug': 'Nhập tên thuốc, thực phẩm chức năng...',
        'duocchat': 'Nhập tên hoạt chất (VD: Paracetamol, ...)',
        'duoclieu': 'Nhập tên dược liệu (VD: Cam thảo, ...)'
    };

    function updateSearchConfig() {
        const selectedFilter = document.querySelector('input[name="type"]:checked');
        if (selectedFilter) {
            // 1. Cập nhật page target cho form
            const targetPage = selectedFilter.getAttribute('data-target');
            pageTarget.value = targetPage;
            
            // 2. Cập nhật placeholder
            const val = selectedFilter.value;
            if(placeholders[val]) {
                searchInput.placeholder = placeholders[val];
            }
        }
    }

    // Event Listeners
    filterRadios.forEach(radio => {
        radio.addEventListener('change', updateSearchConfig);
    });
    
    // Init
    updateSearchConfig();
});
</script>