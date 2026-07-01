<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$page_title = 'Dashboard';
$pdo = db();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ============================================
// Thống kê cá nhân
// ============================================
$totalExams = $pdo->prepare('SELECT COUNT(*) FROM results WHERE user_id = ?');
$totalExams->execute([$user_id]);
$totalExams = $totalExams->fetchColumn();

$avgScore = $pdo->prepare('SELECT AVG(score) FROM results WHERE user_id = ?');
$avgScore->execute([$user_id]);
$avgScore = round($avgScore->fetchColumn(), 1) ?? 0;

$bestScore = $pdo->prepare('SELECT MAX(score) FROM results WHERE user_id = ?');
$bestScore->execute([$user_id]);
$bestScore = $bestScore->fetchColumn() ?? 0;

$totalCorrect = $pdo->prepare('SELECT SUM(correct_answers) FROM results WHERE user_id = ?');
$totalCorrect->execute([$user_id]);
$totalCorrect = $totalCorrect->fetchColumn() ?? 0;

$totalQuestions = $pdo->prepare('SELECT SUM(total_questions) FROM results WHERE user_id = ?');
$totalQuestions->execute([$user_id]);
$totalQuestions = $totalQuestions->fetchColumn() ?? 0;

// ============================================
// Học theo môn
// ============================================
$subjectStats = $pdo->prepare("
    SELECT s.name, s.slug, s.color, COUNT(r.id) as exam_count, AVG(r.score) as avg_score
    FROM subjects s
    JOIN exams e ON e.subject_id = s.id
    JOIN results r ON r.exam_id = e.id
    WHERE r.user_id = ?
    GROUP BY s.id
    ORDER BY avg_score DESC
");
$subjectStats->execute([$user_id]);
$subjectStats = $subjectStats->fetchAll();

// ============================================
// Lịch sử thi gần đây
// ============================================
$recentResults = $pdo->prepare("
    SELECT r.*, e.title as exam_title, s.name as subject_name, s.slug as subject_slug
    FROM results r
    JOIN exams e ON e.id = r.exam_id
    JOIN subjects s ON s.id = e.subject_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$recentResults->execute([$user_id]);
$recentResults = $recentResults->fetchAll();

// ============================================
// Đề thi gợi ý
// ============================================
$recommended = $pdo->prepare("
    SELECT e.*, s.name as subject_name, s.slug as subject_slug, s.color
    FROM exams e
    JOIN subjects s ON s.id = e.subject_id
    WHERE e.is_active = 1 
    AND e.id NOT IN (SELECT DISTINCT exam_id FROM results WHERE user_id = ?)
    ORDER BY RAND()
    LIMIT 6
");
$recommended->execute([$user_id]);
$recommended = $recommended->fetchAll();

// ============================================
// AI Analysis (nếu có)
// ============================================
$aiAnalysis = null;
if (AI_ENABLED && $totalExams > 0) {
    $stmt = $pdo->prepare("
        SELECT subject_name, avg_score, exam_count
        FROM (
            SELECT s.name as subject_name, AVG(r.score) as avg_score, COUNT(r.id) as exam_count
            FROM subjects s
            JOIN exams e ON e.subject_id = s.id
            JOIN results r ON r.exam_id = e.id
            WHERE r.user_id = ?
            GROUP BY s.id
        ) t
        ORDER BY avg_score ASC
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $weakSubjects = $stmt->fetchAll();
    
    if (!empty($weakSubjects)) {
        $aiAnalysis = "📊 Bạn nên tập trung cải thiện các môn: ";
        $suggestions = [];
        foreach ($weakSubjects as $ws) {
            $suggestions[] = $ws['subject_name'] . " (" . number_format($ws['avg_score'], 1) . "/10)";
        }
        $aiAnalysis .= implode(', ', $suggestions);
        $aiAnalysis .= ". Hãy luyện tập thêm để nâng cao điểm số! 💪";
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- ============================================
    USER DASHBOARD
============================================ -->
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Dashboard</h1>
            <p class="text-muted">Xin chào, <?= e($_SESSION['fullname']) ?>! 👋</p>
        </div>
        <?php if (is_teacher()): ?>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-primary">
                <i class="fas fa-user-shield"></i> Admin Panel
            </a>
        <?php endif; ?>
    </div>

    <!-- ==========================================
    STATS CARDS
    ========================================== -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Bài thi đã làm</p>
                            <h3 class="stat-number"><?= number_format($totalExams) ?></h3>
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
                            <p class="text-muted mb-1">Điểm trung bình</p>
                            <h3 class="stat-number"><?= number_format($avgScore, 1) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-star text-warning"></i>
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
                            <p class="text-muted mb-1">Điểm cao nhất</p>
                            <h3 class="stat-number"><?= number_format($bestScore, 1) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-trophy text-success"></i>
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
                            <p class="text-muted mb-1">Tỉ lệ đúng</p>
                            <h3 class="stat-number">
                                <?= $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100) : 0 ?>%
                            </h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
    AI ANALYSIS (if enabled)
    ========================================== -->
    <?php if ($aiAnalysis): ?>
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-robot fa-2x me-3"></i>
                <div>
                    <h6 class="mb-1">🤖 AI Phân tích kết quả học tập</h6>
                    <p class="mb-0"><?= e($aiAnalysis) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ==========================================
    SUBJECT STATS
    ========================================== -->
    <?php if (!empty($subjectStats)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-chart-bar text-primary"></i> Kết quả theo môn học</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($subjectStats as $stat): ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="p-3 rounded" style="background: <?= e($stat['color'] ?? '#f8f9fa') ?>20; border-left: 4px solid <?= e($stat['color'] ?? '#0d6efd') ?>">
                                <h6><?= e($stat['name']) ?></h6>
                                <div class="d-flex justify-content-between">
                                    <span>Điểm TB: <strong><?= number_format($stat['avg_score'], 1) ?></strong></span>
                                    <span class="text-muted"><?= number_format($stat['exam_count']) ?> bài</span>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= ($stat['avg_score'] / 10) * 100 ?>%; background: <?= e($stat['color'] ?? '#0d6efd') ?>"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ==========================================
    RECOMMENDED EXAMS
    ========================================== -->
    <?php if (!empty($recommended)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-lightbulb text-warning"></i> Đề thi gợi ý</h5>
                <a href="<?= base_url('exams.php') ?>" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($recommended as $exam): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <span class="badge bg-secondary"><?= e($exam['subject_name']) ?></span>
                                    <h6 class="mt-2"><?= e($exam['title']) ?></h6>
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span><i class="far fa-clock"></i> <?= $exam['duration'] ?> phút</span>
                                        <span><i class="fas fa-question-circle"></i> <?= $exam['total_questions'] ?> câu</span>
                                    </div>
                                    <?= get_difficulty_badge($exam['difficulty']) ?>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="<?= base_url('exam-take.php?id=' . $exam['id']) ?>" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-play"></i> Làm bài
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ==========================================
    RECENT RESULTS
    ========================================== -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history text-info"></i> Lịch sử thi gần đây</h5>
            <a href="<?= base_url('leaderboard.php') ?>" class="btn btn-sm btn-outline-primary">Xem bảng xếp hạng</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentResults)): ?>
                <div class="text-center py-4">
                    <p class="text-muted">Chưa có bài thi nào. Hãy bắt đầu làm bài thi đầu tiên! 🚀</p>
                    <a href="<?= base_url('exams.php') ?>" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Xem đề thi
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Đề thi</th>
                                <th>Môn</th>
                                <th>Điểm</th>
                                <th>Đúng/Tổng</th>
                                <th>Thời gian</th>
                                <th>Ngày làm</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentResults as $result): ?>
                                <tr>
                                    <td><?= e($result['exam_title']) ?></td>
                                    <td>
                                        <span class="badge" style="background: <?= e($result['subject_color'] ?? '#0d6efd') ?>">
                                            <?= e($result['subject_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="<?= $result['score'] >= 8 ? 'text-success' : ($result['score'] >= 5 ? 'text-warning' : 'text-danger') ?>">
                                            <?= number_format($result['score'], 1) ?>
                                        </strong>
                                    </td>
                                    <td><?= $result['correct_answers'] ?>/<?= $result['total_questions'] ?></td>
                                    <td><?= gmdate('i:s', $result['time_taken']) ?></td>
                                    <td><?= format_date($result['created_at']) ?></td>
                                    <td>
                                        <a href="<?= base_url('exam-result.php?id=' . $result['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>