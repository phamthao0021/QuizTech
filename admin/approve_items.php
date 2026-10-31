<?php
// admin/approve_items.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireAdmin();

// V39: Bảng practice_feedback phải được tạo bởi migration/installation, không tạo khi mở trang.

// Thao tác hàng loạt: duyệt hoặc xóa, tối đa 500 mục.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['bulk_action'])) {
    verify_csrf();
    $action = (string)$_POST['bulk_action'];
    $ids = $_POST['feedback_ids'] ?? [];
    if (!is_array($ids)) $ids = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
    try {
        if (!in_array($action, ['approve','delete'], true) || !$ids || count($ids) > 500) throw new InvalidArgumentException('Lựa chọn không hợp lệ.');
        $pdo->beginTransaction(); $count = 0;
        foreach ($ids as $id) {
            $q = $pdo->prepare('SELECT * FROM practice_feedback WHERE id=? FOR UPDATE'); $q->execute([$id]); $fb=$q->fetch(PDO::FETCH_ASSOC);
            if (!$fb) continue;
            if ($action === 'approve') {
                if (($fb['type'] ?? '') === 'subject_request' && !empty($fb['title'])) {
                    $q=$pdo->prepare('SELECT id FROM subjects WHERE name=? LIMIT 1'); $q->execute([$fb['title']]);
                    if (!$q->fetchColumn()) {
                        $prefix=strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/','',$fb['title']),0,4)) ?: 'SUB';
                        $code=$prefix.strtoupper(bin2hex(random_bytes(4)));
                        $q=$pdo->prepare("INSERT INTO subjects(name,code,description,status,created_by,created_at) VALUES(?,?,?,'active',?,NOW())");
                        $q->execute([$fb['title'],$code,(string)($fb['content']??''),(int)($_SESSION['user_id']??0)]);
                    }
                }
                $q=$pdo->prepare("UPDATE practice_feedback SET status='approved' WHERE id=?");
            } else $q=$pdo->prepare('DELETE FROM practice_feedback WHERE id=?');
            $q->execute([$id]); $count++;
        }
        $pdo->commit(); setFlash('success',($action==='delete'?'Đã xóa ':'Đã duyệt ').$count.' yêu cầu.');
    } catch(Throwable $e) {
        if($pdo->inTransaction())$pdo->rollBack(); error_log('approve_items bulk: '.$e->getMessage());
        setFlash('danger','Không thể xử lý hàng loạt. Không có thay đổi nào được lưu.');
    }
    header('Location: approve_items.php');exit;
}

// ----------------------------------------------------
// XỬ LÝ DUYỆT / TỪ CHỐI THÔNG THƯỜNG
// ----------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && !isset($_POST['action_type'])) {
    verify_csrf();
    $id=(int)($_POST['id']??0);$action=(string)($_POST['action']??'');$note=trim((string)($_POST['admin_note']??''));
    try {
        if($id<1 || !in_array($action,['approve','reject','delete'],true))throw new InvalidArgumentException('Hành động không hợp lệ.');
        $pdo->beginTransaction();
        $s=$pdo->prepare('SELECT * FROM practice_feedback WHERE id=? FOR UPDATE');$s->execute([$id]);$fb=$s->fetch(PDO::FETCH_ASSOC);
        if(!$fb)throw new RuntimeException('Phản hồi không tồn tại.');
        if($action==='delete') {
            $q=$pdo->prepare('DELETE FROM practice_feedback WHERE id=?');$q->execute([$id]);
            $pdo->commit();setFlash('success','Đã xóa yêu cầu.');
            header('Location: approve_items.php');exit;
        }
        if($action==='approve' && ($fb['type']??'')==='subject_request' && !empty($fb['title'])){
            $s=$pdo->prepare('SELECT id FROM subjects WHERE name=? LIMIT 1');$s->execute([$fb['title']]);
            if(!$s->fetchColumn()){
                $prefix=strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/','',$fb['title']),0,4))?:'SUB';
                $code=$prefix.strtoupper(bin2hex(random_bytes(4)));
                $s=$pdo->prepare("INSERT INTO subjects(name,code,description,status,created_by,created_at) VALUES(?,?,?,'active',?,NOW())");
                $s->execute([$fb['title'],$code,(string)($fb['content']??''),(int)($_SESSION['user_id']??0)]);
            }
        }
        $status=$action==='approve'?'approved':'rejected';
        $s=$pdo->prepare('UPDATE practice_feedback SET status=?,admin_note=? WHERE id=?');$s->execute([$status,$note,$id]);
        $pdo->commit();setFlash('success','Đã cập nhật phản hồi.');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('approve_items action: '.$e->getMessage());setFlash('danger','Không thể xử lý yêu cầu; dữ liệu không thay đổi.');}
    header('Location: approve_items.php');exit;
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
        font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif; 
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
  #requestsTable tr.qt-feedback-highlight td{background:#fef3c7!important;box-shadow:inset 0 1px #f59e0b,inset 0 -1px #f59e0b}
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
                        <i class="bi bi-flag-fill me-1 text-danger"></i>Báo lỗi đề / câu hỏi
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-nowrap" onclick="filterType('subject_request', this)">
                        <i class="bi bi-journal-plus me-1 text-primary"></i>Đề xuất môn
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-nowrap" onclick="filterType('feedback', this)">
                        <i class="bi bi-chat-left-text me-1 text-info"></i>Góp ý chức năng / giao diện
                    </button>
                </li>
            </ul>

            <div class="position-relative" style="min-width: 260px;">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="searchInput" onkeyup="applyFilters()" class="form-control ps-5 rounded-pill form-control-sm py-2" placeholder="Tìm kiếm theo tên, MSSV, tiêu đề...">
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="Bộ lọc phản hồi">
            <label class="small fw-semibold" for="qtFeedbackStatus">Trạng thái</label>
            <select class="form-select form-select-sm" id="qtFeedbackStatus" style="width:auto;min-width:150px" aria-label="Lọc trạng thái">
                <option value="all">Tất cả trạng thái</option><option value="pending">Chờ xử lý</option><option value="approved">Đã duyệt</option><option value="rejected">Từ chối</option>
            </select>
            <label class="small fw-semibold ms-sm-2" for="qtFeedbackPageSize">Số dòng</label>
            <select class="form-select form-select-sm" id="qtFeedbackPageSize" style="width:auto" aria-label="Số dòng mỗi trang">
                <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-sm-auto" id="qtFeedbackClear"><i class="bi bi-x-circle me-1"></i>Xóa bộ lọc</button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="qtFeedbackRefresh"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</button>
        </div>
        <div id="qtFeedbackFilterInfo" class="small text-muted mb-2" aria-live="polite"></div>
        <form id="qtBulkForm" method="POST" class="d-flex align-items-center flex-wrap gap-2 mb-3">
            <?php csrf_field(); ?>
            <input type="hidden" name="bulk_action" id="qtBulkAction" value="approve">
            <button type="submit" class="btn btn-sm btn-success" onclick="return qtBulkConfirm('approve')"><i class="bi bi-check2-all me-1"></i>Duyệt đã chọn</button>
            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return qtBulkConfirm('delete')"><i class="bi bi-trash me-1"></i>Xóa đã chọn</button>
            <span id="qtBulkCount" class="small text-muted">Đã chọn 0 yêu cầu</span>
        </form>
        <!-- Bảng Danh Sách -->
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" id="requestsTable">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" class="form-check-input" id="qtSelectAll" aria-label="Chọn tất cả"></th>
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
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Chưa có dữ liệu báo lỗi hoặc đề xuất nào.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($feedbacks as $item): ?>
                        <tr id="feedback-<?= (int)($item['id'] ?? 0) ?>" data-feedback-id="<?= (int)($item['id'] ?? 0) ?>" data-type="<?= e($item['type'] ?? '') ?>" data-status="<?= e($item['status'] ?? '') ?>" data-search="<?= strtolower(e(($item['student_name']??'').' '.($item['title']??'').' '.($item['student_code']??''))) ?>">
                            <td><input type="checkbox" form="qtBulkForm" class="form-check-input qt-row-check" name="feedback_ids[]" value="<?= (int)$item['id'] ?>" aria-label="Chọn yêu cầu <?= (int)$item['id'] ?>"></td>
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
                                    <?php if (preg_match('~\[Ảnh góp ý: (uploads/feedback/[a-zA-Z0-9_.-]+\.(?:jpg|png|webp))\]~u', (string)($item['content'] ?? ''), $attachment)): ?>
                                      <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="<?= e('../' . $attachment[1]) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-image me-1"></i> Xem ảnh góp ý / prototype</a></div>
                                    <?php endif; ?>

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
                                    <?php if (($item['status'] ?? '') !== 'approved'): ?>
                                        <form method="POST" class="d-inline">
<?php csrf_field(); ?>
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
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn yêu cầu này?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-0" style="width:31px;height:31px" title="Xóa"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <nav class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3" aria-label="Phân trang phản hồi">
            <span class="small text-muted" id="qtFeedbackPageSummary"></span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Điều hướng trang">
                <button type="button" class="btn btn-outline-primary" id="qtFeedbackPrev">‹ Trước</button>
                <span class="btn btn-light disabled" id="qtFeedbackPageLabel" aria-live="polite">1 / 1</span>
                <button type="button" class="btn btn-outline-primary" id="qtFeedbackNext">Sau ›</button>
            </div>
        </nav>
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
<?php csrf_field(); ?>
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
let qtFeedbackPage = 1;
let qtFeedbackSize = 10;
let qtTargetFeedbackId = null;

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
    const selector = document.getElementById('qtFeedbackStatus'); if (selector) selector.value = currentStatus;
    applyFilters();
}

function applyFilters(resetPage = true) {
    if (resetPage) qtFeedbackPage = 1;
    const input = document.getElementById('searchInput');
    const term = (input?.value || '').trim().toLocaleLowerCase('vi');
    const rows = [...document.querySelectorAll('#requestsTable tbody tr[data-feedback-id]')];
    const matched = rows.filter(row =>
        (currentType === 'all' || row.dataset.type === currentType) &&
        (currentStatus === 'all' || row.dataset.status === currentStatus) &&
        (row.dataset.search || '').toLocaleLowerCase('vi').includes(term)
    );
    if (qtTargetFeedbackId !== null) {
        const index = matched.findIndex(row => Number(row.dataset.feedbackId) === qtTargetFeedbackId);
        if (index >= 0) qtFeedbackPage = Math.floor(index / qtFeedbackSize) + 1;
        qtTargetFeedbackId = null;
    }
    const pages = Math.max(1, Math.ceil(matched.length / qtFeedbackSize));
    qtFeedbackPage = Math.min(Math.max(qtFeedbackPage, 1), pages);
    const begin = (qtFeedbackPage - 1) * qtFeedbackSize;
    const visible = new Set(matched.slice(begin, begin + qtFeedbackSize));
    rows.forEach(row => { row.hidden = !visible.has(row); row.style.display = visible.has(row) ? '' : 'none'; });
    document.getElementById('qtFeedbackFilterInfo').textContent = `${matched.length} kết quả phù hợp / ${rows.length} yêu cầu`;
    document.getElementById('qtFeedbackPageSummary').textContent = matched.length ? `Hiển thị ${begin + 1}–${Math.min(begin + qtFeedbackSize, matched.length)} trên ${matched.length} kết quả` : 'Không có kết quả phù hợp';
    document.getElementById('qtFeedbackPageLabel').textContent = `${qtFeedbackPage} / ${pages}`;
    document.getElementById('qtFeedbackPrev').disabled = qtFeedbackPage <= 1;
    document.getElementById('qtFeedbackNext').disabled = qtFeedbackPage >= pages;
}

// Deep links from Dashboard and the existing notification bell.
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const type = params.get('type');
    const status = params.get('status');
    const id = Number(params.get('feedback_id'));
    if (['subject_request','question_report','feedback'].includes(type)) {
        const tab = [...document.querySelectorAll('#filterTabs .nav-link')].find(el => (el.getAttribute('onclick') || '').includes("'" + type + "'"));
        filterType(type, tab || null);
    }
    if (['pending','approved','rejected'].includes(status)) filterStatus(status);
    if (Number.isSafeInteger(id) && id > 0) {
        const row = document.getElementById('feedback-' + id);
        if (row) {
            // Always reveal the targeted row, even when other filters were active.
            currentType = 'all'; currentStatus = 'all';
            const input = document.getElementById('searchInput'); if (input) input.value = '';
            qtTargetFeedbackId = id;
            applyFilters();
            row.classList.add('qt-feedback-highlight');
            row.scrollIntoView({behavior:'smooth',block:'center'});
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const statusSelect = document.getElementById('qtFeedbackStatus');
    const sizeSelect = document.getElementById('qtFeedbackPageSize');
    statusSelect.addEventListener('change', () => {
        currentStatus = statusSelect.value;
        document.querySelectorAll('.stat-card').forEach(el => el.classList.remove('active-filter'));
        if (currentStatus !== 'all') document.getElementById('card-' + currentStatus)?.classList.add('active-filter');
        applyFilters();
    });
    sizeSelect.addEventListener('change', () => { qtFeedbackSize = Number(sizeSelect.value) || 10; applyFilters(); });
    document.getElementById('qtFeedbackPrev').addEventListener('click', () => { qtFeedbackPage--; applyFilters(false); });
    document.getElementById('qtFeedbackNext').addEventListener('click', () => { qtFeedbackPage++; applyFilters(false); });
    document.getElementById('qtFeedbackClear').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        currentType = 'all'; currentStatus = 'all'; statusSelect.value = 'all';
        document.querySelectorAll('#filterTabs .nav-link').forEach((b, i) => b.classList.toggle('active', i === 0));
        document.querySelectorAll('.stat-card').forEach(el => el.classList.remove('active-filter'));
        applyFilters();
    });
    document.getElementById('qtFeedbackRefresh').addEventListener('click', () => location.reload());
    applyFilters(false);
});

function qtBulkConfirm(action) {
    const count=document.querySelectorAll('.qt-row-check:checked').length;
    if (!count) {alert('Vui lòng chọn ít nhất một yêu cầu.');return false;}
    if (!confirm(action==='delete'?`Xóa vĩnh viễn ${count} yêu cầu đã chọn?`:`Duyệt ${count} yêu cầu đã chọn?`))return false;
    document.getElementById('qtBulkAction').value=action;return true;
}
document.addEventListener('DOMContentLoaded',function(){
    const all=document.getElementById('qtSelectAll'),checks=[...document.querySelectorAll('.qt-row-check')];
    const update=()=>{const n=checks.filter(x=>x.checked).length;document.getElementById('qtBulkCount').textContent=`Đã chọn ${n} yêu cầu`;
        if(all){all.checked=checks.length>0&&n===checks.length;all.indeterminate=n>0&&n<checks.length;}};
    if(all)all.addEventListener('change',()=>{checks.forEach(x=>x.checked=all.checked);update();});
    checks.forEach(x=>x.addEventListener('change',update));
});
function openRejectModal(id, title, note) {
    document.getElementById('reject_item_id').value = id;
    document.getElementById('reject_item_title').innerText = title || 'Yêu cầu này';
    document.getElementById('reject_admin_note').value = note || '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>