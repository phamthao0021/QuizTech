<?php
// teacher/exam_questions.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

$teacherId = (int)((function_exists('currentUser') ? (currentUser()['id'] ?? null) : null) ?? ($_SESSION['user_id'] ?? null) ?? ($_SESSION['user']['id'] ?? 0));
$exam_id = (int)($_GET['id'] ?? $_POST['exam_id'] ?? 0);

if ($exam_id <= 0) {
    setFlash('danger', 'Đề thi không tồn tại!');
    redirect('exams.php');
}

// Kiểm tra quyền sở hữu đề thi
$stmtExam = $pdo->prepare("SELECT e.*, s.name as subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id WHERE e.id = ? AND e.teacher_id = ?");
$stmtExam->execute([$exam_id, $teacherId]);
$exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    setFlash('danger', 'Đề thi không tồn tại hoặc bạn không có quyền truy cập!');
    redirect('exams.php');
}

// Xử lý thêm câu hỏi vào đề thi
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    verify_csrf();
    $question_id = (int)($_POST['question_id'] ?? 0);
    
    if ($question_id > 0) {
        // Kiểm tra xem đã đủ câu hỏi chưa
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
        $stmtCount->execute([$exam_id]);
        $current_count = $stmtCount->fetchColumn();

        if ($current_count >= $exam['total_questions']) {
            setFlash('danger', 'Đề thi đã đủ số lượng câu hỏi (' . $exam['total_questions'] . ')!');
        } else {
            // Kiểm tra câu hỏi có tồn tại và chưa được thêm
            $stmtChk = $pdo->prepare("SELECT id FROM exam_questions WHERE exam_id = ? AND question_id = ? AND question_type = 'single'");
            $stmtChk->execute([$exam_id, $question_id]);
            if ($stmtChk->fetch()) {
                setFlash('danger', 'Câu hỏi này đã có trong đề thi!');
            } else {
                try {
                    $order = $current_count + 1;
                    $stmtAdd = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, question_type, question_order) VALUES (?, ?, 'single', ?)");
                    $stmtAdd->execute([$exam_id, $question_id, $order]);
                    setFlash('success', 'Đã thêm câu hỏi vào đề thi!');
                } catch (Exception $e) {
                    setFlash('danger', 'Lỗi: Không thể thêm câu hỏi.');
                }
            }
        }
    }
    redirect("exam_questions.php?id=$exam_id");
}

// Xử lý xóa câu hỏi khỏi đề thi
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_question') {
    verify_csrf();
    $eq_id = (int)($_POST['eq_id'] ?? 0);
    
    if ($eq_id > 0) {
        try {
            $stmtRm = $pdo->prepare("DELETE FROM exam_questions WHERE id = ? AND exam_id = ?");
            $stmtRm->execute([$eq_id, $exam_id]);
            setFlash('success', 'Đã xóa câu hỏi khỏi đề thi!');
        } catch (Exception $e) {
            setFlash('danger', 'Lỗi: Không thể xóa câu hỏi.');
        }
    }
    redirect("exam_questions.php?id=$exam_id");
}

// Lấy danh sách câu hỏi đã được gán vào đề thi này
$stmtEq = $pdo->prepare("
    SELECT eq.id as eq_id, q.id as question_id, q.content, q.difficulty 
    FROM exam_questions eq 
    JOIN questions q ON eq.question_id = q.id 
    WHERE eq.exam_id = ? AND eq.question_type = 'single'
    ORDER BY eq.question_order ASC, eq.id ASC
");
$stmtEq->execute([$exam_id]);
$exam_questions = $stmtEq->fetchAll(PDO::FETCH_ASSOC);

// Danh sách các ID câu hỏi đã có trong đề để loại trừ
$assigned_ids = array_column($exam_questions, 'question_id');

// Lấy các câu hỏi thuộc môn học của đề thi để chọn
// Chỉ lấy những câu chưa có trong đề thi
$placeholders = empty($assigned_ids) ? '0' : implode(',', array_fill(0, count($assigned_ids), '?'));
$queryAvail = "
    SELECT id, content, difficulty 
    FROM questions 
    WHERE subject_id = ? AND id NOT IN ($placeholders)
    ORDER BY id DESC
";
$paramsAvail = array_merge([$exam['subject_id']], $assigned_ids);
$stmtAvail = $pdo->prepare($queryAvail);
$stmtAvail->execute($paramsAvail);
$available_questions = $stmtAvail->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gán câu hỏi cho Đề thi';
include '../includes/header_teacher.php';
?>

<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-exam-questions">
<section class="qt-t-hero" aria-label="Giới thiệu trang"><div class="qt-t-hero-icon"><i class="bi bi-list-check"></i></div><div class="qt-t-hero-copy"><div class="qt-t-eyebrow">QUIZTECH · TEACHER</div><h1>Câu hỏi trong đề</h1><p>Sắp xếp và quản lý câu hỏi của đề thi</p></div><a class="qt-t-hero-home" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></section>
    <div class="topbar d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">
                <a href="exams.php" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
                Gán câu hỏi
            </h4>
            <p class="text-muted mb-0">Đề thi: <strong><?= e($exam['title']) ?></strong> (Mã: <?= e($exam['exam_code']) ?>) | Môn: <?= e($exam['subject_name']) ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary fs-6 p-2 shadow-sm">
                Đã thêm: <?= count($exam_questions) ?> / <?= $exam['total_questions'] ?> câu
            </span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Danh sách câu hỏi CÓ TRONG đề thi -->
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-list-check text-success me-2"></i>Câu hỏi trong đề thi</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($exam_questions)): ?>
                            <li class="list-group-item py-4 text-center text-muted">Chưa có câu hỏi nào trong đề thi.</li>
                        <?php else: ?>
                            <?php foreach ($exam_questions as $index => $q): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold text-dark mb-1">
                                            Câu <?= $index + 1 ?>: 
                                            <span class="fw-normal"><?= e(mb_strimwidth($q['content'], 0, 100, '...')) ?></span>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center mt-2">
                                            <span class="badge bg-secondary">ID: <?= $q['question_id'] ?></span>
                                            <?= difficulty_badge($q['difficulty']) ?>
                                        </div>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Loại bỏ câu hỏi này khỏi đề thi?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="remove_question">
                                        <input type="hidden" name="eq_id" value="<?= $q['eq_id'] ?>">
                                        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger mt-1" title="Xóa khỏi đề thi">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Ngân hàng câu hỏi của Môn học này -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bank text-primary me-2"></i>Ngân hàng câu hỏi khả dụng</h5>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($available_questions)): ?>
                            <li class="list-group-item py-4 text-center text-muted">Không còn câu hỏi khả dụng. Hãy tạo thêm trong Ngân hàng câu hỏi.</li>
                        <?php else: ?>
                            <?php foreach ($available_questions as $q): ?>
                                <li class="list-group-item py-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="fw-semibold text-dark">
                                            <?= e(mb_strimwidth($q['content'], 0, 80, '...')) ?>
                                        </div>
                                        <form method="POST">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="add_question">
                                            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                                            <button type="submit" class="btn btn-sm btn-primary" <?= count($exam_questions) >= $exam['total_questions'] ? 'disabled' : '' ?>>
                                                <i class="bi bi-plus-lg"></i> Thêm
                                            </button>
                                        </form>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-secondary">ID: <?= $q['id'] ?></span>
                                        <?= difficulty_badge($q['difficulty']) ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php 
echo '</div></div>';
include '../includes/footer.php'; 
?>
