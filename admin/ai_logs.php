<?php
// admin/ai_logs.php hoặc admin/activity_logs.php
session_start();
require_once __DIR__ . '/../includes/data.php'; // Điều chỉnh lại đường dẫn file db chuẩn của bạn nếu cần
require_once __DIR__ . '/../includes/functions.php';

// Kiểm tra quyền truy cập (Chỉ Admin và Teacher)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: ../login.php');
    exit;
}

// KHỞI TẠO BIẾN TÌM KIẾM & PHÂN TRANG
$search_keyword = trim($_GET['search'] ?? '');
$page           = max(1, intval($_GET['page'] ?? 1));
$limit          = 10;
$offset         = ($page - 1) * $limit;

// XÂY DỰNG CÂU QUERY
$whereClauses = ["1=1"];
$params = [];

// Nếu là Giáo viên, chỉ xem nhật ký do chính mình thực hiện
if ($_SESSION['role'] === 'teacher') {
    $whereClauses[] = "l.user_id = ?";
    $params[] = $_SESSION['user_id'];
}

if (!empty($search_keyword)) {
    $whereClauses[] = "(l.action LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
}

$whereSql = implode(" AND ", $whereClauses);

// Đếm tổng bản ghi
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs l WHERE $whereSql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Lấy danh sách Logs từ activity_logs
$stmt = $pdo->prepare("
    SELECT l.*, 
           COALESCE(u.id, 0) as user_exists,
           u.role
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE $whereSql
    ORDER BY l.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nạp Header giao diện sẵn có của dự án
include_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper d-flex">
    <!-- Nạp Sidebar sẵn có -->
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4">
        
        <!-- Header trang -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="m-0 fw-bold">
                <i class="bi bi-clock-history text-primary me-2"></i>Nhật ký hoạt động & AI
            </h4>
            <a href="ai.php" class="btn btn-primary btn-sm">
                <i class="bi bi-magic me-1"></i>Tạo câu hỏi AI
            </a>
        </div>

        <!-- BỘ LỌC TÌM KIẾM -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm theo Hành động (Action) hoặc Mô tả (Description)..." value="<?= htmlspecialchars($search_keyword) ?>">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Lọc</button>
                        <a href="ai_logs.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- BẢNG DANH SÁCH LOGS -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 70px;">ID</th>
                                <th>User ID</th>
                                <th>Hành động (Action)</th>
                                <th>Mô tả chi tiết (Description)</th>
                                <th>IP / Browser</th>
                                <th class="pe-4">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Chưa có nhật ký hoạt động nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-muted">#<?= $log['id'] ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                User #<?= htmlspecialchars($log['user_id'] ?? 'N/A') ?>
                                            </span>
                                            <?php if (!empty($log['role'])): ?>
                                                <small class="text-muted d-block"><?= ucfirst($log['role']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <?= htmlspecialchars($log['action'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($log['description'] ?? '') ?>">
                                                <?= htmlspecialchars($log['description'] ?? '-') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                            </small>
                                        </td>
                                        <td class="pe-4">
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i><?= date('H:i:s d/m/Y', strtotime($log['created_at'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PHÂN TRANG -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-0 py-3">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center m-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_keyword) ?>">Trước</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search_keyword) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_keyword) ?>">Sau</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Nạp Footer nếu dự án có file footer.php
if (file_exists(__DIR__ . '/../includes/footer.php')) {
    include_once __DIR__ . '/../includes/footer.php';
} else {
    echo '</body></html>';
}
?>