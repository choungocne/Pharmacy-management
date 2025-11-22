<?php
// KẾT NỐI DATABASE
require_once __DIR__ . '/../db.php'; // Điều chỉnh đường dẫn tùy cấu trúc thư mục của bạn
$pdo = pdo();

// 1. Lấy danh sách bệnh theo Mùa (Lấy ngẫu nhiên hoặc theo mùa hiện tại)
$sql_season = "SELECT * FROM benh WHERE mua IS NOT NULL ORDER BY RAND() LIMIT 4";
$list_season = $pdo->query($sql_season)->fetchAll();

// 2. Lấy danh sách bệnh theo Nhóm tuổi
$sql_kids = "SELECT * FROM benh WHERE nhom_tuoi = 'tre_em' LIMIT 8";
$list_kids = $pdo->query($sql_kids)->fetchAll();

$sql_adults = "SELECT * FROM benh WHERE nhom_tuoi = 'nguoi_lon' LIMIT 8";
$list_adults = $pdo->query($sql_adults)->fetchAll();

$sql_elderly = "SELECT * FROM benh WHERE nhom_tuoi = 'nguoi_gia' LIMIT 8";
$list_elderly = $pdo->query($sql_elderly)->fetchAll();

// Hàm helper để map tên mùa sang tiếng Việt đẹp
function getSeasonName($key) {
    $map = [
        'mua_dong' => 'Mùa Đông - Xuân',
        'mua_he' => 'Mùa Hè',
        'mua_xuan_thu' => 'Giao Mùa'
    ];
    return $map[$key] ?? 'Bệnh theo mùa';
}
?>

<style>
    :root {
        --primary-health: #0ea5e9; /* Màu xanh y tế */
        --bg-health: #f0f9ff;
    }
    
    /* Header & Search */
    .health-hero {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        padding: 60px 20px;
        text-align: center;
        border-radius: 0 0 30px 30px;
        margin-bottom: 40px;
    }
    .health-hero h1 { color: #0369a1; font-weight: 800; margin-bottom: 15px; font-size: 2.5rem; }
    .health-hero p { color: #546e7a; font-size: 1.1rem; margin-bottom: 30px; }
    
    .search-box-wrapper {
        max-width: 600px; margin: 0 auto; position: relative;
    }
    .search-input {
        width: 100%; padding: 15px 25px; border-radius: 50px; border: 2px solid #fff;
        box-shadow: 0 10px 25px rgba(14, 165, 233, 0.2); font-size: 16px; outline: none;
        transition: all 0.3s;
    }
    .search-input:focus { border-color: var(--primary-health); box-shadow: 0 10px 30px rgba(14, 165, 233, 0.3); }
    
    /* Section Styles */
    .section-health { margin-bottom: 60px; }
    .section-title-health {
        font-size: 24px; font-weight: 700; color: #334155; margin-bottom: 25px;
        padding-left: 15px; border-left: 5px solid var(--primary-health);
        display: flex; justify-content: space-between; align-items: center;
    }

    /* Disease Card Grid */
    .disease-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px;
    }
    .disease-card {
        background: #fff; border-radius: 16px; overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; border: 1px solid #e2e8f0;
        cursor: pointer; position: relative;
    }
    .disease-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(14, 165, 233, 0.15); border-color: var(--primary-health); }
    
    .disease-img { height: 160px; width: 100%; object-fit: cover; background: #f1f5f9; }
    .disease-content { padding: 20px; }
    .disease-badge {
        font-size: 11px; text-transform: uppercase; font-weight: 700; padding: 4px 10px;
        border-radius: 20px; display: inline-block; margin-bottom: 10px;
    }
    .badge-season { background: #ffedd5; color: #c2410c; }
    .badge-age { background: #dcfce7; color: #15803d; }
    
    .disease-name { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;}
    .disease-desc { font-size: 13px; color: #64748b; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    /* Tabs */
    .health-tabs { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
    .tab-btn {
        padding: 10px 25px; border-radius: 30px; border: none; font-weight: 600;
        background: #f1f5f9; color: #64748b; cursor: pointer; transition: 0.3s;
    }
    .tab-btn.active, .tab-btn:hover { background: var(--primary-health); color: #fff; box-shadow: 0 5px 15px rgba(14, 165, 233, 0.3); }
    .tab-content { display: none; animation: fadeIn 0.5s; }
    .tab-content.active { display: block; }

    /* Modal Chi tiết */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.6); z-index: 999; display: none;
        align-items: center; justify-content: center; backdrop-filter: blur(3px);
    }
    .modal-health {
        background: #fff; width: 90%; max-width: 700px; border-radius: 20px;
        max-height: 85vh; overflow-y: auto; position: relative; animation: zoomIn 0.3s;
    }
    .modal-header { position: relative; height: 200px; overflow: hidden; }
    .modal-header img { width: 100%; height: 100%; object-fit: cover; }
    .btn-close-modal {
        position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.8);
        border: none; width: 35px; height: 35px; border-radius: 50%; font-size: 20px; cursor: pointer;
    }
    .modal-body { padding: 30px; }
    .modal-title { font-size: 28px; font-weight: 800; color: #0369a1; margin-bottom: 15px; }
    .info-block { margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 12px; border-left: 4px solid var(--primary-health); }
    .info-label { font-weight: 700; color: #334155; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
    .info-text { color: #475569; line-height: 1.6; font-size: 15px; }

    @keyframes fadeIn { from{opacity:0} to{opacity:1} }
    @keyframes zoomIn { from{transform:scale(0.9); opacity:0} to{transform:scale(1); opacity:1} }
</style>

<div class="container">
    
    <div class="health-hero">
        <h1>Cẩm Nang Sức Khỏe An Tâm</h1>
        <p>Tra cứu thông tin bệnh lý, triệu chứng và cách phòng ngừa chính xác từ chuyên gia.</p>
        <div class="search-box-wrapper">
            <input type="text" id="healthSearch" class="search-input" placeholder="Nhập tên bệnh hoặc triệu chứng cần tìm...">
        </div>
    </div>

    <div class="section-health">
        <div class="section-title-health">
            <span><i class="fas fa-cloud-sun-rain text-sky-500"></i> Bệnh Lý Theo Mùa Đang Phổ Biến</span>
        </div>
        <div class="disease-grid">
            <?php foreach($list_season as $benh): ?>
                <div class="disease-card" onclick='openHealthModal(<?= json_encode($benh) ?>)'>
                    <img src="<?= !empty($benh['img_url']) ? $benh['img_url'] : 'static/img/placeholder.jpg' ?>" class="disease-img">
                    <div class="disease-content">
                        <span class="disease-badge badge-season"><?= getSeasonName($benh['mua']) ?></span>
                        <h3 class="disease-name"><?= htmlspecialchars($benh['tenbenh']) ?></h3>
                        <p class="disease-desc"><?= htmlspecialchars($benh['mota']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section-health">
        <div class="section-title-health">
            <span><i class="fas fa-user-friends text-sky-500"></i> Tra Cứu Theo Đối Tượng</span>
        </div>
        
        <div class="health-tabs">
            <button class="tab-btn active" onclick="openTab('kids', this)">Trẻ Em</button>
            <button class="tab-btn" onclick="openTab('adults', this)">Người Lớn</button>
            <button class="tab-btn" onclick="openTab('elderly', this)">Người Cao Tuổi</button>
        </div>

        <div id="kids" class="tab-content active">
            <div class="disease-grid">
                <?php foreach($list_kids as $benh): ?>
                    <div class="disease-card" onclick='openHealthModal(<?= json_encode($benh) ?>)'>
                        <img src="<?= !empty($benh['img_url']) ? $benh['img_url'] : 'static/img/placeholder.jpg' ?>" class="disease-img">
                        <div class="disease-content">
                            <span class="disease-badge badge-age">Nhi Khoa</span>
                            <h3 class="disease-name"><?= htmlspecialchars($benh['tenbenh']) ?></h3>
                            <p class="disease-desc"><?= htmlspecialchars($benh['mota']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="adults" class="tab-content">
            <div class="disease-grid">
                <?php foreach($list_adults as $benh): ?>
                    <div class="disease-card" onclick='openHealthModal(<?= json_encode($benh) ?>)'>
                        <img src="<?= !empty($benh['img_url']) ? $benh['img_url'] : 'static/img/placeholder.jpg' ?>" class="disease-img">
                        <div class="disease-content">
                            <span class="disease-badge badge-age" style="background:#e0f2fe; color:#0284c7;">Đa Khoa</span>
                            <h3 class="disease-name"><?= htmlspecialchars($benh['tenbenh']) ?></h3>
                            <p class="disease-desc"><?= htmlspecialchars($benh['mota']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="elderly" class="tab-content">
            <div class="disease-grid">
                <?php foreach($list_elderly as $benh): ?>
                    <div class="disease-card" onclick='openHealthModal(<?= json_encode($benh) ?>)'>
                        <img src="<?= !empty($benh['img_url']) ? $benh['img_url'] : 'static/img/placeholder.jpg' ?>" class="disease-img">
                        <div class="disease-content">
                            <span class="disease-badge badge-age" style="background:#fef9c3; color:#ca8a04;">Lão Khoa</span>
                            <h3 class="disease-name"><?= htmlspecialchars($benh['tenbenh']) ?></h3>
                            <p class="disease-desc"><?= htmlspecialchars($benh['mota']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<div id="healthModal" class="modal-overlay">
    <div class="modal-health">
        <div class="modal-header">
            <img id="m_img" src="" alt="Disease Image">
            <button class="btn-close-modal" onclick="closeHealthModal()">&times;</button>
        </div>
        <div class="modal-body">
            <h2 id="m_title" class="modal-title">Tên Bệnh</h2>
            
            <div class="info-block">
                <div class="info-label"><i class="fas fa-info-circle text-sky-500"></i> Tổng quan</div>
                <p id="m_desc" class="info-text"></p>
            </div>

            <div class="info-block" style="border-color: #f59e0b; background: #fffbeb;">
                <div class="info-label"><i class="fas fa-exclamation-triangle text-amber-500"></i> Triệu chứng & Nguyên nhân</div>
                <p class="info-text"><strong>Triệu chứng:</strong> <span id="m_symptom"></span></p>
                <p class="info-text" style="margin-top:10px;"><strong>Nguyên nhân:</strong> <span id="m_cause"></span></p>
            </div>

            <div class="info-block" style="border-color: #22c55e; background: #f0fdf4;">
                <div class="info-label"><i class="fas fa-user-md text-green-500"></i> Điều trị & Phòng ngừa</div>
                <p id="m_treat" class="info-text"></p>
            </div>
            
            <div style="text-align:center; margin-top:20px;">
                <a href="#" class="btn btn-primary" style="background:var(--primary-health); color:white; padding:10px 30px; border-radius:50px; text-decoration:none;">Đặt lịch tư vấn ngay</a>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Tab Switching
    function openTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    // 2. Modal Logic
    const modal = document.getElementById('healthModal');
    
    function openHealthModal(data) {
        document.getElementById('m_title').textContent = data.tenbenh;
        document.getElementById('m_desc').textContent = data.mota || 'Đang cập nhật...';
        document.getElementById('m_symptom').textContent = data.trieuchung || 'Đang cập nhật...';
        document.getElementById('m_cause').textContent = data.nguyennhan || 'Đang cập nhật...';
        document.getElementById('m_treat').textContent = data.cachdieutri || 'Vui lòng tham khảo ý kiến bác sĩ.';
        document.getElementById('m_img').src = data.img_url || 'static/img/placeholder.jpg';
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling background
    }

    function closeHealthModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close on click outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeHealthModal();
    });

    // 3. Simple Search Logic (Client-side filter for better UX)
    document.getElementById('healthSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let cards = document.querySelectorAll('.disease-card');
        
        cards.forEach(card => {
            let name = card.querySelector('.disease-name').textContent.toLowerCase();
            let desc = card.querySelector('.disease-desc').textContent.toLowerCase();
            if (name.includes(filter) || desc.includes(filter)) {
                card.style.display = "";
            } else {
                card.style.display = "none";
            }
        });
    });
</script>