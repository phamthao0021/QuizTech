<?php
// admin/exams.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (function_exists('requireAdmin')) {
    requireAdmin();
}

$page_title = 'Quản lý Đề thi';
$msg = '';
$msg_type = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $msg = "Tạo đề thi mới thành công!";
        $msg_type = "success";
    } elseif ($_GET['msg'] === 'updated') {
        $msg = "Cập nhật đề thi thành công!";
        $msg_type = "success";
    } elseif ($_GET['msg'] === 'deleted') {
        $msg = "Xóa đề thi thành công!";
        $msg_type = "success";
    }
}

// ==========================================
// HÀM HỖ TRỢ ÉP KIỂU STATUS PHÙ HỢP VỚI CSDL
// ==========================================
function getDbCompatibleStatus($pdo, $statusInput) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'status'");
        $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($columnInfo) {
            $type = strtolower($columnInfo['Type']);
            // Nếu kiểu dữ liệu trong DB là TINYINT, INT hoặc BIT (dùng số 1/0)
            if (strpos($type, 'int') !== false || strpos($type, 'bit') !== false) {
                return ($statusInput === 'active' || $statusInput == 1) ? 1 : 0;
            }
            // Nếu kiểu dữ liệu là ENUM chứa '0','1'
            if (strpos($type, 'enum') !== false && strpos($type, "'1'") !== false) {
                return ($statusInput === 'active' || $statusInput == 1) ? '1' : '0';
            }
        }
    } catch (Exception $e) {
        // Mặc định giữ giá trị gốc nếu không check được
    }
    return ($statusInput === 'active' || $statusInput == 1) ? 'active' : 'draft';
}

// ==========================================
// 1. XỬ LÝ POST REQUEST (CREATE & UPDATE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action     = $_POST['action'];
    $exam_id    = intval($_POST['exam_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $duration   = intval($_POST['duration'] ?? 0);
    $raw_status = trim($_POST['status'] ?? 'active');

    // Chuyển đổi status sang định dạng mà MySQL chấp nhận
    $status_db  = getDbCompatibleStatus($pdo, $raw_status);

    $errors = [];

    if (empty($title)) {
        $errors[] = "Tên đề thi không được để trống.";
    } elseif (mb_strlen($title) < 5 || mb_strlen($title) > 255) {
        $errors[] = "Tên đề thi phải có độ dài từ 5 đến 255 ký tự.";
    }

    if ($subject_id <= 0) {
        $errors[] = "Vui lòng chọn môn học hợp lệ.";
    }

    if ($duration < 1 || $duration > 300) {
        $errors[] = "Thời gian làm bài phải từ 1 đến 300 phút.";
    }

    if (!empty($errors)) {
        $msg = implode('<br>', $errors);
        $msg_type = "danger";
    } else {
        try {
            // A. TẠO ĐỀ THI MỚI (CREATE)
            if ($action === 'create_exam') {
                $exam_code = 'EXAM_' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

                $cols = ['exam_code', 'title', 'subject_id', 'duration', 'status', 'created_at'];
                $placeholders = ['?', '?', '?', '?', '?', 'NOW()'];
                $params = [$exam_code, $title, $subject_id, $duration, $status_db];

                $sql = "INSERT INTO exams (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                header("Location: exams.php?msg=created");
                exit;
            }

            // B. CẬP NHẬT ĐỀ THI (UPDATE)
            if ($action === 'update_exam' && $exam_id > 0) {
                $update_fields = [];
                $params = [];

                $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'title'");
                if ($stmt->fetch()) {
                    $update_fields[] = "title = ?";
                    $params[] = $title;
                } else {
                    $update_fields[] = "name = ?";
                    $params[] = $title;
                }

                $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'subject_id'");
                if ($stmt->fetch()) {
                    $update_fields[] = "subject_id = ?";
                    $params[] = $subject_id;
                }

                $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'duration'");
                if ($stmt->fetch()) {
                    $update_fields[] = "duration = ?";
                    $params[] = $duration;
                } elseif ($pdo->query("SHOW COLUMNS FROM exams LIKE 'time_limit'")->fetch()) {
                    $update_fields[] = "time_limit = ?";
                    $params[] = $duration;
                }

                $stmt = $pdo->query("SHOW COLUMNS FROM exams LIKE 'status'");
                if ($stmt->fetch()) {
                    $update_fields[] = "status = ?";
                    $params[] = $status_db;
                }

                if (!empty($update_fields)) {
                    $params[] = $exam_id;
                    $sql = "UPDATE exams SET " . implode(", ", $update_fields) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    header("Location: exams.php?msg=updated");
                    exit;
                }
            }
        } catch (Exception $e) {
            $msg = "Lỗi xử lý CSDL: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// ==========================================
// 2. XỬ LÝ DELETE
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $del_id = intval($_GET['id'] ?? 0);
    if ($del_id > 0) {
        try {
            $pdo->beginTransaction();

            $stmt1 = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
            $stmt1->execute([$del_id]);

            $stmt2 = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt2->execute([$del_id]);

            $pdo->commit();
            header("Location: exams.php?msg=deleted");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $msg = "Lỗi khi xóa đề thi: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// ==========================================
// 3. LẤY DANH SÁCH MÔN HỌC & ĐỀ THI
// ==========================================
$subjects = [];
if (isset($pdo)) {
    try {
        $sub_stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name ASC");
        $subjects = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $subjects = [];
    }
}

$exams = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM exams ORDER BY id ASC");
        $raw_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subjects_map = [];
        foreach ($subjects as $s) {
            $subjects_map[$s['id']] = $s['name'];
        }

        foreach ($raw_exams as $e) {
            $title = $e['title'] ?? $e['exam_name'] ?? $e['name'] ?? 'Đề thi #' . $e['id'];
            $sub_id = $e['subject_id'] ?? 0;
            $sub_name = $e['subject_name'] ?? $e['subject'] ?? ($subjects_map[$sub_id] ?? 'Chưa phân loại');

            $q_count = 0;
            if (isset($e['total_questions'])) {
                $q_count = $e['total_questions'];
            } else {
                try {
                    $q_stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
                    $q_stmt->execute([$e['id']]);
                    $q_count = $q_stmt->fetchColumn();
                } catch (Exception $ex) {
                    $q_count = 0;
                }
            }

            // Chuẩn hóa trạng thái hiển thị
            $st_raw = $e['status'] ?? 'active';
            $st_clean = ($st_raw === 'active' || $st_raw == 1 || $st_raw === '1') ? 'active' : 'draft';

            $exams[] = array_merge($e, [
                'display_title'   => $title,
                'display_sub_id'  => $sub_id,
                'display_subject' => $sub_name,
                'display_q_count' => $q_count,
                'display_time'    => $e['duration'] ?? $e['time_limit'] ?? $e['exam_time'] ?? 45,
                'display_status'  => $st_clean,
                'display_date'    => $e['created_at'] ?? $e['date_created'] ?? 'now'
            ]);
        }
    } catch (Exception $ex) {
        $exams = [];
    }
}

$exams_reversed = array_reverse($exams);

include '../includes/header_admin.php';
?>

<style>
/* Page-specific UI only.
   Header color/background is intentionally NOT defined here:
   includes/admin_theme.php owns .admin-page-head / .exams-header. */
.exams-page .exam-card{
    background:#fff;border:1px solid #edf0f5;border-radius:18px;
    box-shadow:0 8px 26px rgba(15,23,42,.055);
}
.exams-page .exam-toolbar{
    display:flex;flex-wrap:wrap;gap:10px;align-items:center;
    padding:12px;border:1px solid #edf0f5;border-radius:15px;background:#fff;
}
.exams-page .toolbar-left{display:flex;flex:1 1 650px;gap:10px;flex-wrap:wrap;align-items:center}
.exams-page .toolbar-right{display:flex;gap:8px;align-items:center;margin-left:auto}
.exams-page .search-wrap{flex:1 1 280px;max-width:390px}
.exams-page .filter-control{
    min-height:42px;border:1px solid #e2e8f0!important;border-radius:11px!important;
    background:#f8fafc!important;box-shadow:none!important;
}
.exams-page .filter-control:focus{
    background:#fff!important;border-color:#a78bfa!important;
    box-shadow:0 0 0 3px rgba(124,58,237,.10)!important;
}
.exams-page .search-wrap .input-group-text{border-radius:11px 0 0 11px!important}
.exams-page .search-wrap .form-control{border-radius:0 11px 11px 0!important}
.exams-page .filter-select{min-width:170px;max-width:215px}
.exams-page .page-size{width:92px}
.exams-page .creative-table{border-collapse:separate!important;border-spacing:0 8px!important}
.exams-page .creative-table thead th{
    border:0!important;padding:.8rem 1rem!important;white-space:nowrap;
    color:#697386!important;font-size:.72rem!important;font-weight:800!important;
    letter-spacing:.055em;text-transform:uppercase;
}
.exams-page .creative-table tbody td{
    border:0!important;padding:.9rem 1rem!important;background:#f8fafc!important;vertical-align:middle;
}
.exams-page .creative-table tbody tr:hover td{background:#f3f0ff!important}
.exams-page .creative-table tbody td:first-child{border-radius:12px 0 0 12px}
.exams-page .creative-table tbody td:last-child{border-radius:0 12px 12px 0}
.exams-page .subject-chip{
    display:inline-flex;padding:.32rem .58rem;border-radius:8px;background:#fff;
    border:1px solid #e8ebf1;color:#596274;font-size:.75rem;
}
.exams-page .status-chip{
    display:inline-flex;align-items:center;border-radius:999px;padding:.36rem .65rem;
    font-size:.74rem;font-weight:700;
}
.exams-page .action-btn{
    width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center;
    border-radius:10px!important;border:1px solid #e8ebf1!important;background:#fff!important;
    box-shadow:0 2px 7px rgba(15,23,42,.04);transition:.18s;
}
.exams-page .action-btn:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(15,23,42,.09)}
.exams-page .bulk-bar{
    display:none;align-items:center;justify-content:space-between;gap:12px;
    padding:.72rem .9rem;margin-bottom:12px;border:1px solid #ddd6fe;
    border-radius:14px;background:#faf8ff;
}
.exams-page .bulk-bar.show{display:flex}
.exams-page .form-check-input:checked{background-color:#6d28d9;border-color:#6d28d9}
.exams-page .pagination{gap:.25rem}
.exams-page .pagination .page-link{
    min-width:36px;text-align:center;border:1px solid #e8ebf1!important;
    border-radius:9px!important;color:#596274;background:#fff;
}
.exams-page .pagination .page-item.active .page-link{
    background:#6d28d9!important;border-color:#6d28d9!important;color:#fff!important;
}
.exams-page .table-meta{font-size:.82rem;color:#64748b}
.exams-page .empty-state{padding:4rem 1rem;text-align:center;color:#7b8494}
.exams-page .empty-state .empty-icon{
    width:62px;height:62px;display:grid;place-items:center;margin:0 auto 12px;
    border-radius:18px;background:#f3f0ff;color:#6d28d9;
}

/* CRUD modal */
.exam-modal .modal-content{
    border:0!important;border-radius:22px!important;overflow:hidden;
    box-shadow:0 28px 70px rgba(30,27,75,.22)!important;
}
.exam-modal .modal-header{
    position:relative;border:0!important;padding:1.2rem 1.4rem!important;
    background:linear-gradient(135deg,#4c1d95,#6d28d9)!important;color:#fff!important;
}
.exam-modal .modal-header:after{
    content:"";position:absolute;width:145px;height:145px;border-radius:50%;
    right:-50px;top:-80px;background:rgba(255,255,255,.10);
}
.exam-modal .modal-title{position:relative;z-index:1;color:#fff!important;font-weight:800!important}
.exam-modal .modal-title i{color:#fff!important}
.exam-modal .btn-close{position:relative;z-index:2;filter:brightness(0) invert(1);opacity:.9}
.exam-modal .modal-body{padding:1.35rem!important;background:#fbfcfe}
.exam-modal .modal-footer{border:0!important;padding:1rem 1.4rem 1.3rem!important;background:#fff}
.exam-modal .form-label{font-size:.82rem;font-weight:700!important;color:#4b5563!important}
.exam-modal .form-control,.exam-modal .form-select{
    min-height:44px;border-radius:11px!important;border:1px solid #dde3eb!important;background:#fff!important;
}
.exam-modal .form-control:focus,.exam-modal .form-select:focus{
    border-color:#a78bfa!important;box-shadow:0 0 0 3px rgba(124,58,237,.10)!important;
}
.exam-modal .modal-footer .btn{min-height:42px;border-radius:11px!important;font-weight:700}
.exam-modal .btn-primary{
    background:linear-gradient(135deg,#6d28d9,#7c3aed)!important;border:0!important;
    box-shadow:0 6px 14px rgba(124,58,237,.18);
}
@media(max-width:991.98px){
    .exams-page .toolbar-left,.exams-page .toolbar-right{width:100%;margin-left:0}
    .exams-page .toolbar-right{justify-content:flex-end}
    .exams-page .search-wrap{max-width:none}
    .exams-page .filter-select{flex:1;max-width:none}
}
@media(max-width:767.98px){
    .exams-page .header-actions{display:grid!important;grid-template-columns:1fr;width:100%}
    .exams-page .header-actions .btn,.exams-page .header-actions a{width:100%;justify-content:center}
    .exams-page .toolbar-left{display:grid;grid-template-columns:1fr}
    .exams-page .search-wrap,.exams-page .filter-select{width:100%!important;max-width:none!important}
    .exams-page .toolbar-right{justify-content:space-between}
    .exams-page .bulk-bar{align-items:flex-start;flex-direction:column}
    .exam-modal .modal-dialog{margin:.65rem}
}
</style>

<div class="container-fluid px-0 exams-page">
    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header dùng màu chung từ includes/admin_theme.php -->
    <div class="card border-0 text-white mb-4 shadow-sm admin-page-head exams-header">
        <div class="card-body d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            <div>
                <h3 class="fw-bold mb-1 fs-4">Quản lý đề thi</h3>
                <div class="text-white-50 small"><?= count($exams_reversed) ?> đề thi</div>
            </div>
            <div class="d-flex gap-2 flex-wrap header-actions">
                <a href="create_exam.php" class="btn btn-light text-primary fw-semibold px-3 py-2 rounded-3 shadow-sm">
                    <i class="bi bi-file-earmark-arrow-up-fill me-1"></i>Tạo / Import bộ đề
                </a>
                <button type="button" class="btn btn-outline-light fw-semibold px-3 py-2 rounded-3"
                        data-bs-toggle="modal" data-bs-target="#createExamModal">
                    <i class="bi bi-plus-circle-fill me-1"></i>Tạo đề thủ công
                </button>
            </div>
        </div>
    </div>

    <div class="card exam-card p-3 p-md-4">
        <div class="exam-toolbar mb-3">
            <div class="toolbar-left">
                <div class="search-wrap">
                    <div class="input-group">
                        <span class="input-group-text filter-control border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control filter-control border-start-0"
                               placeholder="Tìm tên đề thi, môn học..." oninput="applyExamFilters()">
                    </div>
                </div>

                <select id="statusFilter" class="form-select filter-control filter-select fw-semibold text-secondary"
                        onchange="applyExamFilters()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Hoạt động</option>
                    <option value="draft">Bản nháp</option>
                </select>

                <select id="subjectFilter" class="form-select filter-control filter-select fw-semibold text-secondary"
                        onchange="applyExamFilters()">
                    <option value="">Tất cả môn học</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= htmlspecialchars(mb_strtolower($sub['name'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($sub['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="toolbar-right">
                <span class="small text-muted">Hiển thị</span>
                <select id="pageSize" class="form-select filter-control page-size" onchange="changePageSize()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="bulk-bar" id="bulkBar">
            <div><b id="selectedCount">0</b> đề thi đã chọn</div>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3" onclick="bulkDeleteSelected()">
                <i class="bi bi-trash3 me-1"></i>Xóa hàng loạt
            </button>
        </div>

        <?php if (empty($exams_reversed)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-journal-x fs-3"></i></div>
                <div class="fw-bold text-dark mb-1">Chưa có đề thi</div>
                <div class="small">Tạo đề thủ công hoặc vào Tạo / Import bộ đề để bắt đầu.</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table creative-table align-middle mb-0" id="examsTable">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width:44px">
                                <input class="form-check-input" type="checkbox" id="checkAll" onchange="toggleAllExams(this)">
                            </th>
                            <th style="width:70px">ID</th>
                            <th>Tên đề thi</th>
                            <th>Môn học</th>
                            <th class="text-center">Câu hỏi</th>
                            <th class="text-center">Thời gian</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stt = count($exams_reversed);
                    foreach ($exams_reversed as $exam):
                        $exam_id = $exam['id'];
                        $virtual_id = $stt--;
                        $exam_title = $exam['display_title'];
                        $subject_id = $exam['display_sub_id'];
                        $subject_name = $exam['display_subject'];
                        $q_count = $exam['display_q_count'];
                        $duration = $exam['display_time'];
                        $status_code = $exam['display_status'];
                        $created_at = $exam['display_date'];
                        $safe_title = htmlspecialchars($exam_title, ENT_QUOTES, 'UTF-8');
                        $safe_subject = htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr class="exam-row"
                            data-title="<?= htmlspecialchars(mb_strtolower($exam_title, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                            data-subject="<?= htmlspecialchars(mb_strtolower($subject_name, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars($status_code, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="ps-3">
                                <input class="form-check-input exam-check" type="checkbox"
                                       value="<?= (int)$exam_id ?>" onchange="updateBulkBar()">
                            </td>
                            <td class="fw-bold text-secondary">#<?= $virtual_id ?></td>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width:280px"
                                     title="<?= $safe_title ?>"><?= $safe_title ?></div>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-clock-history me-1"></i>
                                    <?= strtotime($created_at) ? date('d/m/Y H:i', strtotime($created_at)) : htmlspecialchars($created_at) ?>
                                </small>
                            </td>
                            <td><span class="subject-chip font-monospace"><?= $safe_subject ?></span></td>
                            <td class="text-center">
                                <a href="exam_questions.php?exam_id=<?= $exam_id ?>"
                                   class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fw-semibold">
                                    <i class="bi bi-question-circle-fill me-1"></i><?= $q_count ?> câu
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="status-chip bg-primary bg-opacity-10 text-primary"><?= $duration ?> phút</span>
                            </td>
                            <td class="text-center">
                                <?php if ($status_code === 'active'): ?>
                                    <span class="status-chip bg-success bg-opacity-10 text-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="status-chip bg-secondary bg-opacity-10 text-secondary">Bản nháp</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-1">
                                    <a href="exam_questions.php?exam_id=<?= $exam_id ?>"
                                       class="btn btn-sm text-primary action-btn" title="Quản lý câu hỏi">
                                        <i class="bi bi-list-check"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm text-secondary action-btn" title="Sửa"
                                            onclick="openEditModal(
                                                <?= $exam_id ?>,
                                                '<?= addslashes($safe_title) ?>',
                                                <?= (int)$subject_id ?>,
                                                <?= (int)$duration ?>,
                                                '<?= $status_code ?>'
                                            )">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm text-danger action-btn" title="Xóa"
                                            onclick="confirmDelete(<?= $exam_id ?>, <?= $virtual_id ?>)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
                <div class="table-meta" id="tableMeta"></div>
                <nav aria-label="Phân trang đề thi">
                    <ul class="pagination pagination-sm mb-0" id="examPagination"></ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL THÊM -->
<div class="modal fade exam-modal" id="createExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle text-primary me-2"></i>Thêm Đề Thi Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="exams.php" method="POST" class="needs-validation" id="formCreateExam" novalidate onsubmit="return validateExamForm(this)">
                <input type="hidden" name="action" value="create_exam">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Tên Đề Thi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" name="title" required minlength="5" maxlength="255" placeholder="VD: Kiểm tra giữa kỳ Cơ sở dữ liệu">
                        <div class="invalid-feedback">Tên đề thi phải chứa từ 5 đến 255 ký tự.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-secondary">Môn Học <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="subject_id" required>
                                <option value="" disabled selected>-- Chọn môn --</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn môn học.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-secondary">Thời Gian (Phút) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control rounded-3" name="duration" min="1" max="300" value="45" required>
                            <div class="invalid-feedback">Thời gian phải từ 1 đến 300 phút.</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-secondary">Trạng Thái</label>
                        <select class="form-select rounded-3" name="status">
                            <option value="active" selected>Hoạt động (Công bố cho sinh viên)</option>
                            <option value="draft">Bản nháp / Ẩn</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4 rounded-3 text-secondary fw-semibold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-semibold shadow-sm">Tạo Mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SỬA -->
<div class="modal fade exam-modal" id="editExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Chỉnh Sửa Đề Thi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="exams.php" method="POST" class="needs-validation" id="formEditExam" novalidate onsubmit="return validateExamForm(this)">
                <input type="hidden" name="action" value="update_exam">
                <input type="hidden" name="exam_id" id="edit_exam_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Tên Đề Thi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="edit_title" name="title" required minlength="5" maxlength="255" placeholder="Nhập tên đề thi...">
                        <div class="invalid-feedback">Tên đề thi phải chứa từ 5 đến 255 ký tự.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-secondary">Môn Học <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="edit_subject_id" name="subject_id" required>
                                <option value="" disabled>-- Chọn môn --</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn môn học.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-secondary">Thời Gian (Phút) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control rounded-3" id="edit_duration" name="duration" min="1" max="300" required>
                            <div class="invalid-feedback">Thời gian phải từ 1 đến 300 phút.</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-secondary">Trạng Thái</label>
                        <select class="form-select rounded-3" id="edit_status" name="status">
                            <option value="active">Hoạt động (Công bố cho sinh viên)</option>
                            <option value="draft">Bản nháp / Ẩn</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4 rounded-3 text-secondary fw-semibold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-semibold shadow-sm">Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let examPage = 1;
let examPageSize = 10;
let filteredExamRows = [];

function applyExamFilters(resetPage = true) {
    const q = (document.getElementById('searchInput')?.value || '').toLocaleLowerCase('vi').trim();
    const status = document.getElementById('statusFilter')?.value || '';
    const subject = document.getElementById('subjectFilter')?.value || '';
    const rows = Array.from(document.querySelectorAll('#examsTable tbody .exam-row'));

    filteredExamRows = rows.filter(row => {
        const title = row.dataset.title || '';
        const sub = row.dataset.subject || '';
        const st = row.dataset.status || '';
        return (!q || title.includes(q) || sub.includes(q))
            && (!status || st === status)
            && (!subject || sub === subject);
    });

    if (resetPage) examPage = 1;
    renderExamPage();
}

function changePageSize() {
    examPageSize = parseInt(document.getElementById('pageSize')?.value || '10', 10);
    examPage = 1;
    renderExamPage();
}

function renderExamPage() {
    const allRows = Array.from(document.querySelectorAll('#examsTable tbody .exam-row'));
    allRows.forEach(row => row.style.display = 'none');

    const total = filteredExamRows.length;
    const pages = Math.max(1, Math.ceil(total / examPageSize));
    if (examPage > pages) examPage = pages;

    const start = (examPage - 1) * examPageSize;
    filteredExamRows.slice(start, start + examPageSize).forEach(row => row.style.display = '');

    const meta = document.getElementById('tableMeta');
    if (meta) {
        meta.textContent = total
            ? `Hiển thị ${start + 1}–${Math.min(start + examPageSize, total)} / ${total} đề thi`
            : 'Không có dữ liệu phù hợp';
    }

    const ul = document.getElementById('examPagination');
    if (ul) {
        ul.innerHTML = '';
        const addPage = (label, page, disabled = false, active = false) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            li.innerHTML = `<button type="button" class="page-link">${label}</button>`;
            if (!disabled) {
                li.querySelector('button').onclick = () => {
                    examPage = page;
                    renderExamPage();
                };
            }
            ul.appendChild(li);
        };

        addPage('‹', Math.max(1, examPage - 1), examPage === 1);
        let from = Math.max(1, examPage - 2);
        let to = Math.min(pages, from + 4);
        from = Math.max(1, to - 4);
        for (let p = from; p <= to; p++) addPage(String(p), p, false, p === examPage);
        addPage('›', Math.min(pages, examPage + 1), examPage === pages);
    }
    updateBulkBar();
}

function toggleAllExams(master) {
    document.querySelectorAll('#examsTable tbody .exam-row').forEach(row => {
        if (row.style.display !== 'none') {
            const checkbox = row.querySelector('.exam-check');
            if (checkbox) checkbox.checked = master.checked;
        }
    });
    updateBulkBar();
}

function updateBulkBar() {
    const selected = document.querySelectorAll('.exam-check:checked').length;
    const bar = document.getElementById('bulkBar');
    const count = document.getElementById('selectedCount');
    if (count) count.textContent = selected;
    if (bar) bar.classList.toggle('show', selected > 0);
}

function bulkDeleteSelected() {
    const checked = Array.from(document.querySelectorAll('.exam-check:checked'));
    if (!checked.length) return;

    // Không tạo endpoint bulk-delete mới: giữ nguyên ràng buộc CRUD hiện tại.
    if (checked.length === 1) {
        confirmDelete(parseInt(checked[0].value, 10), checked[0].value);
        return;
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: `${checked.length} đề thi đã chọn`,
            text: 'Trang hiện tại chưa có nghiệp vụ xóa hàng loạt an toàn. Vui lòng xóa từng đề để giữ nguyên ràng buộc dữ liệu.',
            confirmButtonText: 'Đã hiểu'
        });
    } else {
        alert('Vui lòng xóa từng đề để giữ nguyên ràng buộc dữ liệu hiện tại.');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    applyExamFilters(false);
});

    function openEditModal(id, title, subjectId, duration, status) {
        document.getElementById('edit_exam_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_subject_id').value = subjectId || '';
        document.getElementById('edit_duration').value = duration || 45;
        document.getElementById('edit_status').value = status || 'active';

        const form = document.getElementById('formEditExam');
        form.classList.remove('was-validated');

        var editModal = new bootstrap.Modal(document.getElementById('editExamModal'));
        editModal.show();
    }

    function validateExamForm(form) {
        const titleInput = form.querySelector('input[name="title"]');
        const durationInput = form.querySelector('input[name="duration"]');
        const subjectSelect = form.querySelector('select[name="subject_id"]');

        if (titleInput) titleInput.value = titleInput.value.trim();

        let isValid = true;
        if (!titleInput || titleInput.value.length < 5 || titleInput.value.length > 255) isValid = false;
        if (!subjectSelect || !subjectSelect.value || subjectSelect.value === "0") {
            subjectSelect.setCustomValidity("Invalid");
            isValid = false;
        } else {
            subjectSelect.setCustomValidity("");
        }

        const durationVal = parseInt(durationInput ? durationInput.value : 0);
        if (isNaN(durationVal) || durationVal < 1 || durationVal > 300) isValid = false;

        form.classList.add('was-validated');

        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'Dữ liệu không hợp lệ!',
                text: 'Vui lòng kiểm tra lại các trường bắt buộc trước khi lưu.',
                confirmButtonColor: '#6d28d9',
                customClass: { popup: 'rounded-4' }
            });
            return false;
        }
        return true;
    }

    function filterExamsTable() {
        const searchValue = document.getElementById('searchInput').value.toLowerCase().trim();
        const statusValue = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('.exam-row');

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const subject = row.getAttribute('data-subject') || '';
            const status = row.getAttribute('data-status') || '';

            const matchesSearch = title.includes(searchValue) || subject.includes(searchValue);
            const matchesStatus = !statusValue || status === statusValue;

            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    function confirmDelete(realId, virtualId) {
        Swal.fire({
            title: 'Xác nhận xóa đề thi?',
            text: `Bạn có chắc muốn xóa đề thi #${virtualId}? Tất cả câu hỏi liên kết sẽ bị xóa.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy bỏ',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `exams.php?action=delete&id=${realId}`;
            }
        });
    }
</script>

<?php 
if (file_exists('../includes/footer.php')) {
    include '../includes/footer.php'; 
}
?>