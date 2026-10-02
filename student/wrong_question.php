<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('student');

$user = currentUser();
$wrong_questions = db_fetch_all("
    SELECT w.*, q.content, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option 
    FROM student_wrong_questions w 
    JOIN questions q ON w.question_id = q.id 
    WHERE w.student_id = ?
", [$user['id']]);

$page_title = 'Sổ tay câu hỏi làm sai';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <h3 class="fw-bold mb-4">Sổ tay câu hỏi làm sai</h3>

    <?php if (empty($wrong_questions)): ?>
        <div class="alert alert-success">Tuyệt vời! Bạn không có câu hỏi làm sai nào trong sổ tay.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($wrong_questions as $q): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <h6 class="fw-bold text-danger">Nội dung câu hỏi: <?= e($q['content']) ?></h6>
                            <div class="row mt-3 small">
                                <div class="col-md-6 text-muted">Lựa chọn của bạn: <strong><?= e($q['selected_option']) ?></strong></div>
                                <div class="col-md-6 text-success">Đáp án đúng: <strong><?= e($q['correct_option']) ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>