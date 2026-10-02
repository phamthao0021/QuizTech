<?php
// admin/feedback_manage.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bắt buộc quyền Admin
requireAdmin();

// Xử lý cập nhật trạng thái & gửi phản hồi cho Sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_feedback') {
    $feedback_id = (int)$_POST['feedback_id'];
    $status      = $_POST['status'] ?? 'pending';
    $admin_note  = trim($_POST['admin_note'] ?? '');

    try {
        $stmt = $pdo->prepare("
            UPDATE practice_feedback 
            SET status = ?, admin_note = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $admin_note, $feedback_id]);
        
        setFlash('success', 'Cập nhật trạng thái phản hồi thành công!');
        header('Location: feedback_manage.php');
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi hệ thống: ' . $e->getMessage());
    }
}

// Lọc dữ liệu theo trạng thái
$filter_status = $_GET['status'] ?? 'all';
$where_clause = "";
$params = [];

if ($filter_status !== 'all') {
    $where_clause = "WHERE f.status = ?";
    $params[] = $filter_status;
}

// Lấy danh sách tất cả các góp ý / báo lỗi từ sinh viên
$feedbacks = db_fetch_all("
    SELECT f.*, 
           u.fullname as student_name, 
           u.username as student_code,
           e.title as exam_title,
           q.question_text
    FROM practice_feedback f
    LEFT JOIN users u ON f.user_id = u.id
    LEFT JOIN exams e ON f.exam_id = e.id
    LEFT JOIN questions q ON f.question_id = q.id
    $where_clause
    ORDER BY f.created_at DESC
", $params);

$page_title = 'Quản lý Góp ý & Báo lỗi';
require_once dirname(__DIR__) . '/includes/header_admin.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-chat-square-text text-primary me-2"></i>Quản Lý Góp Ý & Báo Lỗi</h4>
            <p class="text-muted small mb-0">Xem và xử lý các đề xuất môn học, góp ý bài thi từ Sinh viên</p>
        </div>
        <div class="btn-group">
            <a href="?status=all" class="btn btn-sm btn-outline-secondary <?= $filter_status === 'all' ? 'active' : '' ?>">Tất cả</a>
            <a href="?status=pending" class="btn btn-sm btn-outline-warning <?= $filter_status === 'pending' ? 'active' : '' ?>">Chờ xử lý</a>
            <a href="?status=approved" class="btn btn-sm btn-outline-success <?= $filter_status === 'approved' ? 'active' : '' ?>">Đã tiếp thu</a>
            <a href="?status=rejected" class="btn btn-sm btn-outline-danger <?= $filter_status === 'rejected' ? 'active' : '' ?>">Từ chối</a>
        </div>
    </div>

    <!-- Thông báo Flash -->
    <?php if (function_exists('getFlash') && $flash = getFlash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show border-0 rounded-3 small mb-4" role="alert">
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4">Sinh viên</th>
                            <th>Loại yêu cầu</th>
                            <th>Tiêu đề / Môn học</th>
                            <th>Nội dung</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedbacks)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Chưa có góp ý nào trong danh sách.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($feedbacks as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= e($item['student_name'] ?: 'N/A') ?></div>
                                        <small class="text-muted"><?= e($item['student_code']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($item['type'] === 'subject_request'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">📚 Đề xuất môn</span>
                                        <?php elseif ($item['type'] === 'question_report'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">🚩 Báo lỗi câu hỏi</span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">💬 Góp ý bài thi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($item['title'] ?: 'Không có tiêu đề') ?></div>
                                        <?php if ($item['exam_title']): ?>
                                            <small class="text-muted d-block">Bài: <?= e($item['exam_title']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 280px;">
                                        <div class="text-truncate small"><?= e($item['content']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($item['status'] === 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">Đã tiếp thu</span>
                                        <?php elseif ($item['status'] === 'rejected'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">Từ chối</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1">Chờ duyệt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('H:i d/m/Y', strtotime($item['created_at'])) ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#replyModal<?= $item['id'] ?>">
                                            <i class="bi bi-pencil-square me-1"></i>Xử lý
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Xử lý & Phản hồi -->
                                <div class="modal fade" id="replyModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update_feedback">
                                                <input type="hidden" name="feedback_id" value="<?= $item['id'] ?>">
                                                
                                                <div class="modal-header border-0 pb-0">
                                                    <h6 class="modal-header-title fw-bold">Xử Lý Phản Hồi #<?= $item['id'] ?></h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body py-3">
                                                    <div class="mb-3 p-3 bg-light rounded-3">
                                                        <small class="text-muted d-block mb-1">Nội dung sinh viên phản hồi:</small>
                                                        <div class="small fw-semibold text-dark"><?= nl2br(e($item['content'])) ?></div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Trạng thái xử lý</label>
                                                        <select name="status" class="form-select rounded-3">
                                                            <option value="pending" <?= $item['status'] === 'pending' ? 'selected' : '' ?>>⏳ Đang chờ xem xét</option>
                                                            <option value="approved" <?= $item['status'] === 'approved' ? 'selected' : '' ?>>✅ Đã tiếp thu / Đã cập nhật</option>
                                                            <option value="rejected" <?= $item['status'] === 'rejected' ? 'selected' : '' ?>>❌ Từ chối / Giữ nguyên</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Ghi chú / Lời nhắn phản hồi cho sinh viên</label>
                                                        <textarea name="admin_note" class="form-control rounded-3" rows="3" placeholder="Nhập câu trả lời hoặc hướng giải quyết..."><?= e($item['admin_note'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light rounded-pill btn-sm" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-primary rounded-pill btn-sm px-4">Lưu cập nhật</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>