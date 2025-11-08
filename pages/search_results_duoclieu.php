<?php
// TỆP NỘI DUNG: search_results_duoclieu_content.php
// Dùng để nhúng (include) vào search_tool.php

$total_found = count($results);
?>
<div class="duoclieu-search-content">
    <div class="search-info">
        <p>Tìm thấy **<?= $total_found ?>** dược liệu.</p>
    </div>
    
    <?php if ($total_found > 0): ?>
        <div class="result-list duoclieu-list">
            <?php foreach ($results as $item): ?>
                <div class="duoclieu-item">
                    </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <i class="fas fa-leaf"></i>
            <p>Xin lỗi, không tìm thấy Dược liệu nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
        </div>
    <?php endif; ?>
</div>