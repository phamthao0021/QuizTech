<?php
// teacher/exams.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

$teacherId = (int)((function_exists('currentUser') ? (currentUser()['id'] ?? null) : null) ?? ($_SESSION['user_id'] ?? null) ?? ($_SESSION['user']['id'] ?? 0));

// Lấy danh sách môn học của giáo viên (do giáo viên tạo hoặc được phân công)
$stmtSubj = $pdo->prepare("
    SELECT id, name 
    FROM subjects 
    WHERE created_by = ? OR id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?)
    ORDER BY name ASC
");
$stmtSubj->execute([$teacherId, $teacherId]);
$subjects = $stmtSubj->fetchAll(PDO::FETCH_ASSOC);

$allowed_subject_ids = array_column($subjects, 'id');

// -------------------------------------------------------------
// XỬ LÝ THÊM ĐỀ THI
// -------------------------------------------------------------
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $subject_id      = (int)($_POST['subject_id'] ?? 0);
    $title           = trim($_POST['title'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $duration        = (int)($_POST['duration'] ?? 20); // changed from time_limit
    $total_questions = (int)($_POST['total_questions'] ?? 10); // changed from question_count

    // Validate Dữ liệu
    if (!in_array($subject_id, $allowed_subject_ids)) {
        setFlash('danger', 'Môn học không hợp lệ hoặc bạn không có quyền thao tác!');
    } elseif ($subject_id <= 0 || empty($title)) {
        setFlash('danger', 'Vui lòng chọn môn học và nhập tiêu đề đề thi!');
    } elseif ($duration < 1 || $total_questions < 1) {
        setFlash('danger', 'Thời gian làm bài và số lượng câu hỏi phải lớn hơn 0!');
    } else {
        try {
            // Sinh mã tự động cho Đề thi (Ví dụ: EXAM-8F32A)
            $exam_code = 'EXAM-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));

            $stmt = $pdo->prepare("INSERT INTO exams (subject_id, teacher_id, exam_code, title, description, duration, total_questions, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$subject_id, $teacherId, $exam_code, $title, $description, $duration, $total_questions]);
            
            setFlash('success', 'Đã thêm đề thi thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi CSDL: Không thể thêm đề thi mới!');
        }
        redirect('exams.php');
    }
}

// -------------------------------------------------------------
// XỬ LÝ XÓA ĐỀ THI
// -------------------------------------------------------------
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        // Check ownership
        $stmtChk = $pdo->prepare("SELECT teacher_id FROM exams WHERE id = ?");
        $stmtChk->execute([$id]);
        $ex = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if (!$ex || $ex['teacher_id'] != $teacherId) {
            setFlash('danger', 'Bạn không có quyền xóa đề thi này!');
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
                $stmt->execute([$id]);

                resetAutoIncrement($pdo, 'exams');
                setFlash('success', 'Đã xóa đề thi!');
            } catch (PDOException $e) {
                setFlash('danger', 'Lỗi: Không thể xóa đề thi này (có thể đã có học sinh làm bài)!');
            }
        }
    }
    redirect('exams.php');
}

// Lấy danh sách đề thi của giáo viên này
$stmt = $pdo->prepare("
    SELECT e.*, s.name as subject_name 
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id 
    WHERE e.teacher_id = ?
    ORDER BY e.id DESC
");
$stmt->execute([$teacherId]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Quản lý đề thi';
include '../includes/header_teacher.php';
?>

<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-exams">
  <section class="qt-t-hero qt-exam-hero" aria-label="Quản lý đề thi">
    <div class="qt-t-hero-icon"><i class="bi bi-file-earmark-text"></i></div>
    <div class="qt-t-hero-copy">
      <div class="qt-t-eyebrow">QUIZTECH · TEACHER</div>
      <h1>Quản lý đề thi</h1>
      <p>Tạo đề, quản lý câu hỏi và theo dõi ngân hàng đề thi</p>
    </div>
    <div class="qt-exam-hero-actions">
      <button type="button" class="qt-exam-hero-add" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle"></i> Thêm đề thi
      </button>
      <a class="qt-t-hero-home" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
    </div>
  </section>

  <section class="qt-exam-summary" aria-label="Tổng quan đề thi">
    <div class="qt-exam-summary-card">
      <span class="qt-exam-summary-icon"><i class="bi bi-journal-text"></i></span>
      <div><span>Tổng số đề thi</span><strong><?= count($exams) ?></strong></div>
    </div>
    <div class="qt-exam-summary-card">
      <span class="qt-exam-summary-icon purple"><i class="bi bi-collection"></i></span>
      <div><span>Môn học có đề</span><strong><?= count(array_unique(array_column($exams, 'subject_id'))) ?></strong></div>
    </div>
    <div class="qt-exam-summary-card">
      <span class="qt-exam-summary-icon pink"><i class="bi bi-question-circle"></i></span>
      <div><span>Tổng số câu cấu hình</span><strong><?= array_sum(array_map(static fn($e) => (int)($e['total_questions'] ?? 0), $exams)) ?></strong></div>
    </div>
  </section>

  <section class="qt-exam-panel" aria-label="Danh sách đề thi">
    <div class="qt-exam-panel-heading">
      <div>
        <h2><i class="bi bi-card-list"></i> Danh sách đề thi</h2>
        <p>Tìm kiếm, lọc theo môn học và quản lý các đề thi đã tạo.</p>
      </div>
      <span class="qt-exam-total" id="examVisibleCount"><?= count($exams) ?> đề thi</span>
    </div>

    <div class="qt-exam-toolbar">
      <label class="qt-exam-search">
        <i class="bi bi-search"></i>
        <input id="examSearch" type="search" autocomplete="off" placeholder="Tìm mã đề, tiêu đề, môn học..." aria-label="Tìm kiếm đề thi">
      </label>
      <select id="examSubjectFilter" class="form-select" aria-label="Lọc theo môn học">
        <option value="">Tất cả môn học</option>
        <?php foreach ($subjects as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="examSort" class="form-select" aria-label="Sắp xếp đề thi">
        <option value="newest">Mới nhất</option>
        <option value="oldest">Cũ nhất</option>
        <option value="title">Tiêu đề A–Z</option>
        <option value="duration">Thời gian tăng dần</option>
      </select>
      <button id="examRefresh" type="button" class="btn qt-exam-refresh" title="Làm mới dữ liệu" aria-label="Tải lại danh sách">
        <i class="bi bi-arrow-clockwise"></i> <span>Làm mới</span>
      </button>
    </div>

    <div class="qt-exam-table-wrap">
      <table class="qt-exam-table">
        <thead>
          <tr>
            <th>ID</th><th>Mã đề</th><th>Tiêu đề</th><th>Môn học</th>
            <th>Số câu</th><th>Thời gian</th><th>Thao tác</th>
          </tr>
        </thead>
        <tbody id="examRows">
          <?php foreach ($exams as $ex): ?>
            <tr class="qt-exam-row"
                data-id="<?= (int)$ex['id'] ?>"
                data-subject="<?= (int)$ex['subject_id'] ?>"
                data-title="<?= e(strtolower((string)$ex['title'])) ?>"
                data-search="<?= e(strtolower((string)(($ex['exam_code'] ?? '') . ' ' . ($ex['title'] ?? '') . ' ' . ($ex['subject_name'] ?? '')))) ?>"
                data-duration="<?= (int)($ex['duration'] ?? 20) ?>">
              <td data-label="ID"><span class="qt-exam-id">#<?= (int)$ex['id'] ?></span></td>
              <td data-label="Mã đề"><span class="qt-exam-code"><?= e($ex['exam_code'] ?? '') ?></span></td>
              <td data-label="Tiêu đề"><strong class="qt-exam-title"><?= e($ex['title']) ?></strong></td>
              <td data-label="Môn học"><span class="qt-exam-subject"><?= e($ex['subject_name'] ?? 'Chưa xác định') ?></span></td>
              <td data-label="Số câu"><span class="qt-exam-metric"><i class="bi bi-question-circle"></i> <?= (int)($ex['total_questions'] ?? 0) ?> câu</span></td>
              <td data-label="Thời gian"><span class="qt-exam-metric"><i class="bi bi-clock"></i> <?= (int)($ex['duration'] ?? 20) ?> phút</span></td>
              <td data-label="Thao tác">
                <div class="qt-exam-actions">
                  <a href="exam_questions.php?id=<?= (int)$ex['id'] ?>" class="qt-exam-action edit" title="Quản lý câu hỏi" aria-label="Quản lý câu hỏi đề <?= e($ex['title']) ?>"><i class="bi bi-list-check"></i><span>Câu hỏi</span></a>
                  <form method="POST" class="qt-exam-delete-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đề thi này?')">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
                    <button type="submit" class="qt-exam-action delete" title="Xóa đề thi" aria-label="Xóa đề <?= e($ex['title']) ?>"><i class="bi bi-trash"></i><span>Xóa</span></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div id="examEmpty" class="qt-exam-empty" hidden>
        <i class="bi bi-search"></i><strong>Không tìm thấy đề thi phù hợp</strong>
        <span>Thử thay đổi từ khóa hoặc bộ lọc.</span>
      </div>
    </div>

    <div class="qt-exam-footer">
      <div class="qt-exam-footer-left">
        <span id="examRange">Hiển thị 0 đề thi</span>
        <label>Hiển thị
          <select id="examPageSize" class="form-select form-select-sm" aria-label="Số đề mỗi trang">
            <option value="5">5</option><option value="10" selected>10</option>
            <option value="20">20</option><option value="50">50</option>
          </select>
        </label>
      </div>
      <nav id="examPagination" class="qt-exam-pagination" aria-label="Phân trang đề thi"></nav>
    </div>
  </section>
</main>

<!-- Modal thêm Đề thi -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="exams.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary">Thêm đề thi mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Môn học <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Chọn môn học --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề đề thi <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="VD: Kiểm tra Giữa kỳ SQL" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Ghi chú thêm về bài thi..."></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Số câu hỏi</label>
                            <input type="number" name="total_questions" class="form-control" value="10" min="1" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Thời gian (phút)</label>
                            <input type="number" name="duration" class="form-control" value="20" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu đề thi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
echo '</div></div>';
include '../includes/footer.php'; 
?>
<script src="../assets/js/qt-teacher-exams.js?v=2" defer></script>
