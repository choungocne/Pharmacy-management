<!-- Disease Section -->
<div class="disease-section">
    <div class="tabs">
        <button class="tab-btn active" data-tab="target">Bệnh theo đối tượng</button>
        <button class="tab-btn" data-tab="season">Bệnh theo mùa</button>
    </div>

    <!-- Bệnh theo đối tượng -->
    <div class="tab-content active" id="target">
        <?php
        // Truy vấn bệnh theo nhóm tuổi từ SQL
        $groups = ['tre_em' => 'Trẻ em', 'nguoi_lon' => 'Người lớn', 'nguoi_gia' => 'Người cao tuổi'];
        foreach ($groups as $key => $title) {
            $stmt = $pdo->prepare("SELECT * FROM benh WHERE nhom_tuoi = :nhom_tuoi LIMIT 4"); // Giới hạn 4 bệnh mỗi nhóm
            $stmt->execute([':nhom_tuoi' => $key]);
            $diseases = $stmt->fetchAll();
            
            echo '<h3>' . $title . '</h3>';
            echo '<div class="disease-grid">';
            foreach ($diseases as $disease) {
                echo '<div class="card">';
                echo '<img src="' . htmlspecialchars($disease['img_url']) . '" alt="' . htmlspecialchars($disease['tenbenh']) . '">';
                echo '<h4>' . htmlspecialchars($disease['tenbenh']) . '</h4>';
                echo '<p>' . substr(htmlspecialchars($disease['mota']), 0, 100) . '...</p>'; // Mô tả ngắn
                echo '<a href="chi-tiet-benh.php?mabenh=' . $disease['mabenh'] . '">Tìm hiểu thêm →</a>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>

    <!-- Bệnh theo mùa -->
    <div class="tab-content" id="season">
        <?php
        // Truy vấn bệnh theo mùa từ SQL
        $seasons = ['mua_dong' => 'Mùa đông', 'mua_he' => 'Mùa hè', 'mua_xuan_thu' => 'Mùa xuân/thu'];
        foreach ($seasons as $key => $title) {
            $stmt = $pdo->prepare("SELECT * FROM benh WHERE mua = :mua LIMIT 4");
            $stmt->execute([':mua' => $key]);
            $diseases = $stmt->fetchAll();
            
            echo '<h3>' . $title . '</h3>';
            echo '<div class="disease-grid">';
            foreach ($diseases as $disease) {
                echo '<div class="card">';
                echo '<img src="' . htmlspecialchars($disease['img_url']) . '" alt="' . htmlspecialchars($disease['tenbenh']) . '">';
                echo '<h4>' . htmlspecialchars($disease['tenbenh']) . '</h4>';
                echo '<p>' . substr(htmlspecialchars($disease['mota']), 0, 100) . '...</p>';
                echo '<a href="chi-tiet-benh.php?mabenh=' . $disease['mabenh'] . '">Tìm hiểu thêm →</a>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<style>
.disease-section {
    background: #f5f7ff;
    padding: 30px;
    border-radius: 15px;
    margin: 20px 0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.tabs {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
}

.tab-btn {
    padding: 10px 20px;
    background: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    color: #34495e;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.tab-btn.active {
    background: #007bff;
    color: white;
    box-shadow: 0 4px 10px rgba(0,123,255,0.3);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.disease-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    padding: 15px;
    text-align: center;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 15px;
}

.card h4 {
    color: #2c3e50;
    margin: 10px 0;
    font-size: 18px;
}

.card p {
    color: #7f8c8d;
    font-size: 14px;
    margin-bottom: 15px;
}

.card a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
}

.card a:hover {
    color: #0056b3;
}
</style>

<script>
// JavaScript cho tabs (giữ nguyên hoặc cải tiến)
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});
</script>