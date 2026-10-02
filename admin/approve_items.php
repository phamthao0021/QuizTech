<?php
// admin/approve_items.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireAdmin();

// ----------------------------------------------------
// TỰ ĐỘNG KHỞI TẠO BẢNG practice_feedback
// ----------------------------------------------------
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `practice_feedback` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'feedback',
        `exam_id` INT NULL,
        `question_id` INT NULL,
        `title` VARCHAR(255) NULL,
        `content` TEXT NOT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `admin_note` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    // Ignored
}

// ----------------------------------------------------
// XỬ LÝ SỬA NHANH CÂU HỎI TRỰC TIẾP TỪ ADMIN
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'quick_update_question') {
    $q_id        = (int)($_POST['question_id'] ?? 0);
    $feedback_id = (int)($_POST['feedback_id'] ?? 0);
    $q_text      = trim($_POST['question_text'] ?? '');
    $opt_a       = trim($_POST['option_a'] ?? '');
    $opt_b       = trim($_POST['option_b'] ?? '');
    $opt_c       = trim($_POST['option_c'] ?? '');
    $opt_d       = trim($_POST['option_d'] ?? '');
    $correct     = trim($_POST['correct_option'] ?? 'A');
    $admin_note  = trim($_POST['admin_note'] ?? 'Đã cập nhật câu hỏi theo phản hồi.');

    if ($q_id > 0 && !empty($q_text)) {
        try {
            $column_names = ['content', 'question_text', 'question', 'title'];

            foreach ($column_names as $col) {
                try {
                    $sql = "UPDATE questions SET `{$col}` = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE id = ?";
                    $stmtQ = $pdo->prepare($sql);
                    $stmtQ->execute([$q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $q_id]);
                    break;
                } catch (Exception $ex) {
                    continue; 
                }
            }

            if ($feedback_id > 0) {
                $stmtF = $pdo->prepare("UPDATE practice_feedback SET status = 'approved', admin_note = ? WHERE id = ?");
                $stmtF->execute([$admin_note, $feedback_id]);
            }

            setFlash('success', 'Đã cập nhật câu hỏi & duyệt báo cáo thành công!');
        } catch (Exception $e) {
            setFlash('danger', 'Lỗi khi cập nhật câu hỏi: ' . $e->getMessage());
        }
    }
    header('Location: approve_items.php');
    exit;
}

// ----------------------------------------------------
// XỬ LÝ DUYỆT / TỪ CHỐI THÔNG THƯỜNG
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !isset($_POST['action_type'])) {
    $item_id = (int)($_POST['id'] ?? 0);
    $action  = $_POST['action'];
    $note    = trim($_POST['admin_note'] ?? '');

    if ($item_id > 0) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $fb = db_fetch_one("SELECT * FROM practice_feedback WHERE id = ?", [$item_id]);

        if ($fb) {
            $stmt = $pdo->prepare("UPDATE practice_feedback SET status = ?, admin_note = ? WHERE id = ?");
            $stmt->execute([$status, $note, $item_id]);

            if ($status === 'approved' && $fb['type'] === 'subject_request' && !empty($fb['title'])) {
                try {
                    $checkName = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE name = ?");
                    $checkName->execute([$fb['title']]);

                    if ($checkName->fetchColumn() == 0) {
                        $codePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $fb['title']), 0, 4));
                        if (empty($codePrefix)) { $codePrefix = 'SUB'; }

                        do {
                            $code = $codePrefix . rand(100, 999);
                            $checkCode = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE code = ?");
                            $checkCode->execute([$code]);
                        } while ($checkCode->fetchColumn() > 0);

                        $adminId = $_SESSION['user']['id'] ?? 1;
                        $description = !empty($fb['content']) ? $fb['content'] : 'Môn học được duyệt từ đề xuất của sinh viên';

                        $stmtSub = $pdo->prepare("INSERT INTO subjects (name, code, description, status, created_by, created_at) VALUES (?, ?, ?, 'active', ?, NOW())");
                        $stmtSub->execute([$fb['title'], $code, $description, $adminId]);
                    }
                } catch (Exception $e) {}
            }

            setFlash('success', ($action === 'approve' ? 'Đã duyệt yêu cầu thành công!' : 'Đã từ chối phản hồi thành công!'));
        }
        header('Location: approve_items.php');
        exit;
    }
}

// ----------------------------------------------------
// TRUY VẤN DỮ LIỆU
// ----------------------------------------------------
$feedbacks = [];
try {
    $feedbacks = db_fetch_all("
        SELECT f.*, 
               u.name as student_name, 
               u.email, 
               u.student_code, 
               e.title as exam_title
        FROM practice_feedback f
        LEFT JOIN users u ON f.user_id = u.id
        LEFT JOIN exams e ON f.exam_id = e.id
        ORDER BY f.created_at DESC
    ");

    foreach ($feedbacks as &$fb_item) {
        $fb_item['question_text'] = '';
        $fb_item['option_a']       = '';
        $fb_item['option_b']       = '';
        $fb_item['option_c']       = '';
        $fb_item['option_d']       = '';
        $fb_item['correct_option'] = 'A';

        if (!empty($fb_item['question_id'])) {
            try {
                $q_data = db_fetch_one("SELECT * FROM questions WHERE id = ?", [$fb_item['question_id']]);
                if ($q_data) {
                    $fb_item['question_text'] = $q_data['content'] ?? $q_data['question_text'] ?? $q_data['question'] ?? $q_data['title'] ?? '';
                    $fb_item['option_a']       = $q_data['option_a'] ?? '';
                    $fb_item['option_b']       = $q_data['option_b'] ?? '';
                    $fb_item['option_c']       = $q_data['option_c'] ?? '';
                    $fb_item['option_d']       = $q_data['option_d'] ?? '';
                    $fb_item['correct_option'] = $q_data['correct_option'] ?? 'A';
                }
            } catch (Exception $ex) {}
        }
    }
    unset($fb_item);
} catch (Exception $e) {
    $feedbacks = db_fetch_all("SELECT * FROM practice_feedback ORDER BY created_at DESC");
}

$pending_count  = count(array_filter($feedbacks, fn($i) => ($i['status'] ?? '') === 'pending'));
$approved_count = count(array_filter($feedbacks, fn($i) => ($i['status'] ?? '') === 'approved'));
$rejected_count = count(array_filter($feedbacks, fn($i) => ($i['status'] ?? '') === 'rejected'));

$page_title = 'Quản Lý Báo Lỗi & Đề Xuất';
require_once dirname(__DIR__) . '/includes/header_admin.php';
?>

<style>
    :root {
        --primary-color: #6366f1;
        --primary-hover: #4f46e5;
        --bg-body: #f8fafc;
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    body { 
        background-color: var(--bg-body); 
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; 
        color: var(--text-dark); 
    }

    /* Header Title Section */
    .page-header {
        margin-bottom: 1.5rem;
    }

    /* Stat Cards Separated */
    .stat-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        transition: all 0.25s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }

    .stat-card.active-filter {
        border-color: var(--primary-color);
        background-color: #f5f3ff;
    }

    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .stat-pending .stat-icon { background: #fffbe3; color: #d97706; }
    .stat-approved .stat-icon { background: #dc8c8c1f; color: #16a34a; background-color: #f0fdf4; }
    .stat-rejected .stat-icon { background: #fef2f2; color: #dc2626; }

    /* Content Card & Table */
    .content-card { 
        background: #ffffff; 
        border-radius: 20px; 
        border: 1px solid var(--border-color); 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); 
    }

    .nav-pills-custom .nav-link { 
        color: var(--text-muted); 
        font-weight: 600; 
        border-radius: 10px; 
        padding: 0.5rem 1rem; 
        transition: all 0.2s ease; 
        font-size: 0.875rem;
    }

    .nav-pills-custom .nav-link.active { 
        background-color: var(--primary-color); 
        color: #ffffff; 
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25); 
    }

    /* Dynamic Badges */
    .badge-status { 
        padding: 0.35rem 0.75rem; 
        font-weight: 600; 
        border-radius: 20px; 
        font-size: 0.75rem; 
        display: inline-flex; 
        align-items: center; 
        gap: 0.3rem; 
        white-space: nowrap;
    }
    
    .badge-pending { background-color: #fffbeb; color: #b45309; border: 1px solid #fef3c7; }
    .badge-approved { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
    .badge-rejected { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

    .badge-type { font-size: 0.75rem; font-weight: 600; border-radius: 8px; padding: 0.35rem 0.6rem; white-space: nowrap; }
    .badge-type-subject { background-color: #eef2ff; color: #4338ca; }
    .badge-type-feedback { background-color: #f0f9ff; color: #0369a1; }
    .badge-type-report { background-color: #fef2f2; color: #b91c1c; }

    .avatar-circle {
        width: 38px; 
        height: 38px; 
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: #ffffff; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    /* Fixed Table Layout & Text Truncation */
    .table-responsive { overflow-x: auto; }
    .table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-modern thead th { 
        background-color: #f8fafc; 
        color: var(--text-muted); 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em;
        padding: 0.85rem 1rem; 
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    
    .table-modern tbody td { 
        padding: 1rem; 
        border-bottom: 1px solid #f1f5f9; 
        vertical-align: middle;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    .col-min-detail { min-width: 220px; max-width: 280px; }
    .col-min-content { min-width: 260px; max-width: 340px; }
</style>

<div class="container-fluid py-4 px-3 px-md-4">
    <!-- 1. TIÊU ĐỀ TRANG TRẮNG TRỌNG -->
    <div class="page-header admin-page-head d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Duyệt đề xuất & Xử lý báo lỗi</h3>
            <p class="text-muted mb-0 small">Theo dõi phản hồi sinh viên, phê duyệt môn học mới và sửa nhanh câu hỏi bị lỗi.</p>
        </div>
    </div>

    <!-- 2. HÀNG THỐNG KÊ TRẠNG THÁI (Đã tách riêng) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card stat-pending" id="card-pending" onclick="filterStatus('pending')">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $pending_count ?></div>
                    <div class="text-muted small fw-medium">Báo cáo chờ duyệt</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card stat-approved" id="card-approved" onclick="filterStatus('approved')">
                <div class="stat-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $approved_count ?></div>
                    <div class="text-muted small fw-medium">Đã xử lý / Chấp nhận</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card stat-rejected" id="card-rejected" onclick="filterStatus('rejected')">
                <div class="stat-icon">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $rejected_count ?></div>
                    <div class="text-muted small fw-medium">Đã từ chối</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thông báo Flash -->
    <?php if (function_exists('getFlash') && $flash = getFlash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 3. KHU VỰC BẢNG DỮ LIỆU -->
    <div class="content-card p-3 p-md-4">
        <!-- Bộ Lọc Tab & Tìm Kiếm -->
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4 pb-3 border-bottom">
            <ul class="nav nav-pills nav-pills-custom flex-nowrap overflow-auto pb-2 pb-lg-0" id="filterTabs">
                <li class="nav-item">
                    <button class="nav-link active text-nowrap" onclick="filterType('all', this)">Tất cả (<?= count($feedbacks) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-nowrap" onclick="filterType('question_report', this)">
                        <i class="bi bi-flag-fill me-1 text-danger"></i>Báo lỗi câu hỏi
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-nowrap" onclick="filterType('subject_request', this)">
                        <i class="bi bi-journal-plus me-1 text-primary"></i>Đề xuất môn
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-nowrap" onclick="filterType('feedback', this)">
                        <i class="bi bi-chat-left-text me-1 text-info"></i>Góp ý chung
                    </button>
                </li>
            </ul>

            <div class="position-relative" style="min-width: 260px;">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="searchInput" onkeyup="applyFilters()" class="form-control ps-5 rounded-pill form-control-sm py-2" placeholder="Tìm kiếm theo tên, MSSV, tiêu đề...">
            </div>
        </div>

        <!-- Bảng Danh Sách -->
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" id="requestsTable">
                <thead>
                    <tr>
                        <th style="width: 18%;">Sinh viên</th>
                        <th style="width: 12%;">Phân loại</th>
                        <th class="col-min-detail">Chi tiết liên quan</th>
                        <th class="col-min-content">Nội dung báo lỗi / Góp ý</th>
                        <th class="text-center" style="width: 12%;">Trạng thái</th>
                        <th class="text-end" style="width: 14%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbacks)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Chưa có dữ liệu báo lỗi hoặc đề xuất nào.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($feedbacks as $item): ?>
                        <tr data-type="<?= e($item['type'] ?? '') ?>" data-status="<?= e($item['status'] ?? '') ?>" data-search="<?= strtolower(e(($item['student_name']??'').' '.($item['title']??'').' '.($item['student_code']??''))) ?>">
                            <!-- Sinh viên -->
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle"><?= mb_strtoupper(mb_substr($item['student_name']??'U', 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-bold text-dark fs-7"><?= e($item['student_name'] ?? 'Thành viên') ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;"><?= e(($item['student_code'] ?? '') ?: ($item['email'] ?? '')) ?></small>
                                    </div>
                                </div>
                            </td>

                            <!-- Phân loại -->
                            <td class="text-nowrap">
                                <?php if (($item['type'] ?? '') === 'question_report'): ?>
                                    <span class="badge badge-type badge-type-report"><i class="bi bi-flag-fill me-1"></i> Báo lỗi câu</span>
                                <?php elseif (($item['type'] ?? '') === 'subject_request'): ?>
                                    <span class="badge badge-type badge-type-subject"><i class="bi bi-journal-plus me-1"></i> Đề xuất môn</span>
                                <?php else: ?>
                                    <span class="badge badge-type badge-type-feedback"><i class="bi bi-chat-left-text me-1"></i> Góp ý bài thi</span>
                                <?php endif; ?>
                            </td>

                            <!-- Chi tiết liên quan -->
                            <td class="col-min-detail">
                                <div class="fw-bold text-dark text-truncate" title="<?= e($item['title'] ?? '') ?>"><?= e(($item['title'] ?? '') ?: 'Không có tiêu đề') ?></div>
                                <?php if (!empty($item['question_text'])): ?>
                                    <div class="small text-muted text-truncate mt-1" title="<?= e($item['question_text']) ?>">
                                        <b class="text-dark">Đề:</b> <?= e($item['question_text']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($item['exam_title'])): ?>
                                    <div class="small text-muted text-truncate mt-1">
                                        <b>Đề thi:</b> <?= e($item['exam_title']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Nội dung báo lỗi -->
                            <td class="col-min-content">
                                <div class="small text-dark line-clamp-2" title="<?= e($item['content'] ?? '') ?>">
                                    <?= nl2br(e($item['content'] ?? '')) ?>
                                </div>
                                <?php if (!empty($item['admin_note'])): ?>
                                    <div class="mt-1.5 p-1.5 px-2 bg-light rounded border text-primary small line-clamp-2" style="font-size: 0.75rem;">
                                        <strong>Phản hồi:</strong> <?= e($item['admin_note']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Trạng thái -->
                            <td class="text-center text-nowrap">
                                <?php if (($item['status'] ?? '') === 'approved'): ?>
                                    <span class="badge badge-status badge-approved"><i class="bi bi-check-circle-fill"></i> Đã xử lý</span>
                                <?php elseif (($item['status'] ?? '') === 'rejected'): ?>
                                    <span class="badge badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i> Từ chối</span>
                                <?php else: ?>
                                    <span class="badge badge-status badge-pending"><i class="bi bi-hourglass-split"></i> Chờ duyệt</span>
                                <?php endif; ?>
                            </td>

                            <!-- Thao tác -->
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <?php if (!empty($item['question_id'])): ?>
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-2.5" 
                                                onclick='openEditQuestionModal(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                title="Sửa câu hỏi">
                                            <i class="bi bi-pencil-square"></i> <span class="d-none d-xl-inline ms-1">Sửa</span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (($item['status'] ?? '') !== 'approved'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm rounded-circle p-0" style="width: 31px; height: 31px;" title="Duyệt / Chấp nhận">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (($item['status'] ?? '') !== 'rejected'): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-0" style="width: 31px; height: 31px;" 
                                                onclick="openRejectModal(<?= $item['id'] ?>, '<?= e(addslashes($item['title'] ?? '')) ?>', '<?= e(addslashes($item['admin_note'] ?? '')) ?>')" 
                                                title="Từ chối">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL SỬA NHANH -->
<div class="modal fade" id="quickEditQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-primary text-white rounded-top-4 p-3">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-pencil-square me-2"></i>Chỉnh Sửa Câu Hỏi & Duyệt Báo Lỗi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="quick_update_question">
                <input type="hidden" name="question_id" id="edit_q_id">
                <input type="hidden" name="feedback_id" id="edit_fb_id">

                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 rounded-3 mb-3 p-3">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Nội dung báo lỗi từ sinh viên:</div>
                        <div id="edit_fb_content" class="small text-dark"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Nội dung câu hỏi <span class="text-danger">*</span></label>
                        <textarea name="question_text" id="edit_q_text" class="form-control rounded-3" rows="3" required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Đáp án A</label>
                            <input type="text" name="option_a" id="edit_q_opt_a" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Đáp án B</label>
                            <input type="text" name="option_b" id="edit_q_opt_b" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Đáp án C</label>
                            <input type="text" name="option_c" id="edit_q_opt_c" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Đáp án D</label>
                            <input type="text" name="option_d" id="edit_q_opt_d" class="form-control rounded-3">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Đáp án đúng <span class="text-danger">*</span></label>
                            <select name="correct_option" id="edit_q_correct" class="form-select rounded-3 fw-bold text-success" required>
                                <option value="A">Đáp án A</option>
                                <option value="B">Đáp án B</option>
                                <option value="C">Đáp án C</option>
                                <option value="D">Đáp án D</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Phản hồi gửi sinh viên</label>
                            <input type="text" name="admin_note" id="edit_q_note" class="form-control rounded-3" value="Đã kiểm tra và chỉnh sửa câu hỏi. Cảm ơn phản hồi của bạn!">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold"><i class="bi bi-save me-1"></i> Lưu & Phê Duyệt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TỪ CHỐI -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white rounded-top-4 p-3">
                <h5 class="modal-title fw-bold fs-6">Từ Chối Phản Hồi / Đề Xuất</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="reject_item_id">
                    <input type="hidden" name="action" value="reject">
                    <p class="text-secondary small mb-3">Đang xử lý mục: <strong id="reject_item_title" class="text-dark"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Lý do từ chối <span class="text-danger">*</span></label>
                        <textarea name="admin_note" id="reject_admin_note" class="form-control rounded-3" rows="3" placeholder="Nhập lý do từ chối để sinh viên nhận biết..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Xác nhận từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentType = 'all';
let currentStatus = 'all';

function filterType(type, element) {
    if (element) {
        document.querySelectorAll('#filterTabs .nav-link').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
    }
    currentType = type;
    applyFilters();
}

function filterStatus(status) {
    if (currentStatus === status) {
        currentStatus = 'all';
        document.querySelectorAll('.stat-card').forEach(el => el.classList.remove('active-filter'));
    } else {
        currentStatus = status;
        document.querySelectorAll('.stat-card').forEach(el => el.classList.remove('active-filter'));
        const card = document.getElementById(`card-${status}`);
        if (card) card.classList.add('active-filter');
    }
    applyFilters();
}

function applyFilters() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#requestsTable tbody tr');

    rows.forEach(row => {
        const rowType = row.getAttribute('data-type');
        const rowStatus = row.getAttribute('data-status');
        const rowSearch = row.getAttribute('data-search') || '';

        const matchType = (currentType === 'all' || rowType === currentType);
        const matchStatus = (currentStatus === 'all' || rowStatus === currentStatus);
        const matchSearch = rowSearch.includes(searchInput);

        row.style.display = (matchType && matchStatus && matchSearch) ? '' : 'none';
    });
}

function openEditQuestionModal(data) {
    document.getElementById('edit_q_id').value = data.question_id || '';
    document.getElementById('edit_fb_id').value = data.id || '';
    document.getElementById('edit_fb_content').innerHTML = '<strong>' + (data.title || 'Báo lỗi') + ':</strong> ' + (data.content || '');
    document.getElementById('edit_q_text').value = data.question_text || '';
    document.getElementById('edit_q_opt_a').value = data.option_a || '';
    document.getElementById('edit_q_opt_b').value = data.option_b || '';
    document.getElementById('edit_q_opt_c').value = data.option_c || '';
    document.getElementById('edit_q_opt_d').value = data.option_d || '';
    document.getElementById('edit_q_correct').value = data.correct_option || 'A';

    new bootstrap.Modal(document.getElementById('quickEditQuestionModal')).show();
}

function openRejectModal(id, title, note) {
    document.getElementById('reject_item_id').value = id;
    document.getElementById('reject_item_title').innerText = title || 'Yêu cầu này';
    document.getElementById('reject_admin_note').value = note || '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>