<?php
// TỆP: search_tool.php (Logic chuyển hướng phải ở đây)
require_once 'db.php'; 
global $base_url;
if (!isset($base_url)) $base_url = '/Pharmacy-management'; 

$search_query = $_GET['q'] ?? '';
$search_type = $_GET['type'] ?? 'drug'; 

$results = [];
$products = []; 
$search_keyword = trim($search_query);
$search_term = '%' . $search_keyword . '%';
$pdo = pdo();
$show_content = true;

// --- LOGIC CHUYỂN HƯỚNG VÀ TRUY VẤN CHECK (Chuyển lên đầu) ---

if (!empty($search_keyword) && ($search_type === 'duoclieu' || $search_type === 'duocchat')) {
    
    $table = ($search_type === 'duoclieu') ? 'duoclieu' : 'duocchat';
    $id_col = ($search_type === 'duoclieu') ? 'madl' : 'madc';
    $name_col = ($search_type === 'duoclieu') ? 'tendl' : 'tendc';
    
    $search_cols = ($search_type === 'duoclieu') ? 
                    "tendl LIKE ? OR tenkhac LIKE ? OR cong_dung LIKE ?" : 
                    "tendc LIKE ? OR tenkhac LIKE ? OR tacdung LIKE ?";

    $params = [$search_term, $search_term, $search_term];

    $sql_check = "SELECT $id_col, $name_col 
                  FROM $table 
                  WHERE ($search_cols) AND trangthai = 1 
                  LIMIT 2"; 
    
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute($params); 
    $results_check = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

    // DÒNG NÀY ĐÃ GÂY LỖI: Chỉ chuyển hướng nếu tìm thấy 1 kết quả DUY NHẤT
    if (count($results_check) === 1) {
        $single_item = $results_check[0];
        $detail_page = ($search_type === 'duoclieu') ? 'duoclieu_detail' : 'duocchat_detail';
        $detail_id = $single_item[$id_col];
        
        // GỌI HEADER() TRƯỚC KHI CÓ BẤT KỲ ĐẦU RA HTML NÀO
        header("Location: {$base_url}/base.php?page={$detail_page}&{$id_col}={$detail_id}");
        exit;
    }
} 

// --- PHẦN 2: TRUY VẤN CHO VIỆC HIỂN THỊ (Sau khi không chuyển hướng) ---

if (!empty($search_keyword) && $show_content) {
    // ... (Thực hiện truy vấn hiển thị cho drug, duoclieu, duocchat) ...
    // Giữ nguyên logic truy vấn Phần 2 như lần sửa trước
}

// ----------------------------------------------------
// KẾT THÚC KHỐI PHP - BẮT ĐẦU KHỐI HIỂN THỊ HTML
// ----------------------------------------------------
?>

<?php if ($show_content): ?>
<div class="breadcrumb">
  <a href="<?= $base_url ?>/base.php?page=home">Trang chủ</a> / <span>Kết quả tra cứu</span>
</div>
<?php endif; ?>