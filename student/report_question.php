<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('student');

$user = currentUser();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_id = intval($_POST['question_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($question_id > 0 && !empty($reason)) {
        db_insert("INSERT INTO question_reports (student_id, question_id, reason, created_at) VALUES (?, ?, ?, NOW())", [
            $user['id'], $question_id, $reason
        ]);
        $success = 'Đã gửi báo cáo thành công!';
    }
}

$page_title = 'Báo cáo câu hỏi lỗi';
require_once dirname(__DIR__) . '/includes/header_student.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold mb-3">Báo cáo câu hỏi bị lỗi</h4>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">ID câu hỏi</label>
                        <input type="number" name="question_id" class="form-control" placeholder="Nhập ID câu hỏi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý do báo cáo</label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="Mô tả sai sót của câu hỏi..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100"><i class="bi bi-flag"></i> Gửi báo cáo</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>