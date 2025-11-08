<?php
// TỆP NỘI DUNG: search_results_duocchat_content.php
// Dùng để nhúng (include) vào search_tool.php

$total_found = count($results);
?>
<div class="duocchat-search-content">
    <div class="search-info">
        <p>Tìm thấy **<?= $total_found ?>** dược chất.</p>
    </div>
    
    <?php if ($total_found > 0): ?>
        <div class="result-list duocchat-list">
            <?php foreach ($results as $item): ?>
                <div class="duocchat-item">
                    </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <i class="fas fa-microscope"></i>
            <p>Xin lỗi, không tìm thấy Dược chất nào phù hợp với từ khóa **"<?= htmlspecialchars($search_query) ?>"**.</p>
        </div>
    <?php endif; ?>
</div>