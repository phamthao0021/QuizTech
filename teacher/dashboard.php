<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_teacher();

$page_title = 'Teacher Dashboard';
$pdo = db();
$user_id = $_SESSION['user_id'];

// ============================================
// Thống kê giảng viên
// ============================================
$stats = [
    'my_exams' => $pdo->prepare('SELECT COUNT(*) FROM exams WHERE created_by = ?')->execute([$user_id]),
    'my_questions' => $pdo->prepare('
        SELECT COUNT(*) FROM questions 
        WHERE subject_id IN (SELECT subject_id FROM exams WHERE created_by = ?)
    ')->execute([$user_id]),
    'total_students' => $pdo->prepare('
        SELECT COUNT(DISTINCT user_id) FROM results 
        WHERE exam_id IN (SELECT id FROM exams WHERE created_by = ?)
    ')->execute([$user_id]),
    'total_results' => $pdo->prepare('
        SELECT COUNT(*) FROM results 
        WHERE exam_id IN (SELECT id FROM exams WHERE created_by = ?)
    ')->execute([$user_id]),
];

// Lấy dữ liệu
$myExams = $pdo->prepare('SELECT COUNT(*) FROM exams WHERE created_by = ?');
$myExams->execute([$user_id]);
$myExams = $myExams->fetchColumn();

$myQuestions = $pdo->prepare('
    SELECT COUNT(*) FROM questions 
    WHERE subject_id IN (SELECT subject_id FROM exams WHERE created_by = ?)
');
$myQuestions->execute([$user_id]);
$myQuestions = $myQuestions->fetchColumn();

$totalStudents = $pdo->prepare('
    SELECT COUNT(DISTINCT user_id) FROM results 
    WHERE exam_id IN (SELECT id FROM exams WHERE created_by = ?)
');
$totalStudents->execute([$user_id]);
$totalStudents = $totalStudents->fetchColumn();

$totalResults = $pdo->prepare('
    SELECT COUNT(*) FROM results 
    WHERE exam_id IN (SELECT id FROM exams WHERE created_by = ?)
');
$totalResults->execute([$user_id]);
$totalResults = $totalResults->fetchColumn();

// ============================================
// Danh sách đề thi của tôi
// ============================================
$myExamsList = $pdo->prepare("
    SELECT e.*, s.name as subject_name, 
           (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count,
           (SELECT COUNT(*) FROM results WHERE exam_id = e.id) as result_count
    FROM exams e
    JOIN subjects s ON s.id = e.subject_id
    WHERE e.created_by = ?
    ORDER BY e.created_at DESC
");
$myExamsList->execute([$user_id]);
$myExamsList = $myExamsList->fetchAll();

// ============================================
// Thống kê điểm theo môn
// ============================================
$subjectAvg = $pdo->prepare("
    SELECT s.name, AVG(r.score) as avg_score, COUNT(r.id) as result_count
    FROM subjects s
    JOIN exams e ON e.subject_id = s.id
    JOIN results r ON r.exam_id = e.id
    WHERE e.created_by = ?
    GROUP BY s.id
    ORDER BY avg_score DESC
");
$subjectAvg->execute([$user_id]);
$subjectAvg = $subjectAvg->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Teacher Dashboard</h1>
            <p class="text-muted">Quản lý đề thi và theo dõi kết quả học sinh</p>
        </div>
        <div>
            <a href="<?= base_url('exams.php?create=1') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tạo đề thi mới
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Đề thi của tôi</p>
                            <h3 class="stat-number"><?= number_format($myExams) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Câu hỏi đã tạo</p>
                            <h3 class="stat-number"><?= number_format($myQuestions) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-question-circle text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Học sinh đã thi</p>
                            <h3 class="stat-number"><?= number_format($totalStudents) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tổng kết quả</p>
                            <h3 class="stat-number"><?= number_format($totalResults) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-chart-line text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- My Exams -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt text-primary"></i> Đề thi của tôi</h5>
                    <a href="<?= base_url('exams.php') ?>" class="btn btn-sm btn-outline-primary">Quản lý</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Đề thi</th>
                                    <th>Môn</th>
                                    <th>Câu hỏi</th>
                                    <th>Lượt thi</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myExamsList as $exam): ?>
                                    <tr>
                                        <td><?= e($exam['title']) ?></td>
                                        <td><?= e($exam['subject_name']) ?></td>
                                        <td><?= number_format($exam['question_count']) ?></td>
                                        <td><?= number_format($exam['result_count']) ?></td>
                                        <td>
                                            <?php if ($exam['is_active']): ?>
                                                <span class="badge bg-success">Hoạt động</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Đã tắt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('exam-take.php?id=' . $exam['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Stats -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-chart-bar text-success"></i> Thống kê theo môn</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($subjectAvg as $stat): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?= e($stat['name']) ?></span>
                            <div>
                                <span class="badge bg-primary"><?= number_format($stat['result_count']) ?></span>
                                <span class="badge bg-<?= $stat['avg_score'] >= 7 ? 'success' : ($stat['avg_score'] >= 5 ? 'warning' : 'danger') ?>">
                                    <?= number_format($stat['avg_score'], 1) ?>
                                </span>
                            </div>
                        </div>
                        <div class="progress mb-3" style="height: 4px;">
                            <div class="progress-bar bg-<?= $stat['avg_score'] >= 7 ? 'success' : ($stat['avg_score'] >= 5 ? 'warning' : 'danger') ?>" 
                                 style="width: <?= ($stat['avg_score'] / 10) * 100 ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-bolt text-warning"></i> Hành động nhanh</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('questions.php') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-plus-circle"></i> Thêm câu hỏi
                        </a>
                        <a href="<?= base_url('exams.php') ?>" class="btn btn-outline-success">
                            <i class="fas fa-file-alt"></i> Tạo đề thi
                        </a>
                        <a href="<?= base_url('rooms.php') ?>" class="btn btn-outline-warning">
                            <i class="fas fa-users"></i> Tạo phòng thi
                        </a>
                        <a href="<?= base_url('ai-generate.php') ?>" class="btn btn-outline-info">
                            <i class="fas fa-robot"></i> AI sinh câu hỏi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>