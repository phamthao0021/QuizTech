<?php
// teacher/results.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireTeacher();

$teacherId = (int)((function_exists('currentUser') ? (currentUser()['id'] ?? null) : null) ?? ($_SESSION['user_id'] ?? null) ?? ($_SESSION['user']['id'] ?? 0));

// Lấy kết quả bài làm của học sinh thuộc đề thi của giáo viên này
// Sử dụng exam_attempts (kết quả chi tiết) join với exams (lọc theo teacher_id)
$stmt = $pdo->prepare("
    SELECT 
        a.id as attempt_id,
        a.exam_id,
        a.started_at,
        a.submitted_at,
        a.score,
        a.total_questions,
        a.correct_answers,
        a.percentage,
        a.status,
        a.duration_seconds,
        e.title as exam_title,
        e.exam_code,
        s.name as subject_name,
        u.name as student_name,
        u.username as student_username,
        u.email as student_email
    FROM exam_attempts a
    JOIN exams e ON a.exam_id = e.id
    JOIN users u ON a.student_id = u.id
    LEFT JOIN subjects s ON e.subject_id = s.id
    WHERE e.teacher_id = ?
    ORDER BY a.submitted_at DESC, a.started_at DESC
    LIMIT 200
");
$stmt->execute([$teacherId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê nhanh
$totalAttempts = count($results);
$submittedCount = count(array_filter($results, fn($r) => $r['status'] === 'submitted' || $r['status'] === 'timeout'));
$completedResults = array_values(array_filter($results, static fn(array $r): bool => in_array(($r['status'] ?? ''), ['submitted', 'timeout'], true)));
$avgScore = count($completedResults) > 0
    ? round(array_sum(array_map(static fn(array $r): float => (float)($r['score'] ?? 0), $completedResults)) / count($completedResults), 2)
    : 0;

$page_title = 'Kết quả thi';
include '../includes/header_teacher.php';
?>

<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-results qt-t-redesign">
  <section class="qt-t-hero"><div class="qt-t-hero-icon"><i class="bi bi-bar-chart-line"></i></div><div class="qt-t-hero-copy"><div class="qt-t-eyebrow">QUIZTECH · TEACHER</div><h1>Kết quả và thống kê</h1><p>Theo dõi kết quả học tập và phân tích hiệu suất làm bài</p></div><a class="qt-t-hero-home" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></section>
  <section class="qt-r-metrics" aria-label="Tổng quan kết quả">
    <article class="qt-r-metric"><span class="qt-r-icon blue"><i class="bi bi-clipboard-data"></i></span><div><small>Tổng lượt làm bài</small><strong><?= number_format($totalAttempts) ?></strong><span>Trong <?= count($results) ?> kết quả đã tải</span></div></article>
    <article class="qt-r-metric"><span class="qt-r-icon green"><i class="bi bi-check-circle"></i></span><div><small>Đã hoàn thành</small><strong><?= number_format($submittedCount) ?></strong><span><?= $totalAttempts ? round($submittedCount / $totalAttempts * 100, 1) : 0 ?>% lượt làm bài</span></div></article>
    <article class="qt-r-metric"><span class="qt-r-icon purple"><i class="bi bi-graph-up-arrow"></i></span><div><small>Điểm trung bình</small><strong><?= number_format($avgScore, 2) ?></strong><span>Trên các kết quả đã tải</span></div></article>
  </section>
  <section class="qt-r-panel">
    <div class="qt-r-panel-heading"><div><h2><i class="bi bi-list-check"></i> Danh sách kết quả thi</h2><p>Tra cứu theo sinh viên, đề thi, môn học và trạng thái</p></div><span class="qt-r-count"><?= count($results) ?> kết quả</span></div>
    <div class="qt-shared-toolbar" data-qt-table-toolbar><label class="qt-shared-search"><i class="bi bi-search"></i><input type="search" placeholder="Tìm sinh viên, mã đề, môn học..." aria-label="Tìm kiếm kết quả"></label><select class="qt-shared-filter" data-filter="status" aria-label="Lọc trạng thái"><option value="">Tất cả trạng thái</option><option value="submitted">Đã nộp</option><option value="timeout">Hết giờ</option><option value="doing">Đang làm</option></select><select class="qt-shared-sort" aria-label="Sắp xếp"><option value="original">Mới nhất</option><option value="az">Tên A–Z</option><option value="za">Tên Z–A</option></select><button type="button" class="qt-shared-refresh"><i class="bi bi-arrow-clockwise"></i> Làm mới</button></div>
    <div class="qt-r-table-scroll"><table class="qt-enhanced-table qt-r-table"><thead><tr><th>Sinh viên</th><th>Đề thi</th><th>Môn học</th><th>Đúng / Tổng</th><th>Điểm</th><th>Tỷ lệ</th><th>Trạng thái</th><th>Thời gian nộp</th></tr></thead><tbody>
    <?php if (!$results): ?><tr><td colspan="8" class="qt-r-empty"><i class="bi bi-inbox"></i><p>Chưa có kết quả thi nào của các đề do bạn quản lý.</p></td></tr><?php else: foreach ($results as $r):
      $pct = max(0, min(100, (float)($r['percentage'] ?? 0)));
      $status = strtolower((string)($r['status'] ?? ''));
      $statusLabel = ['submitted'=>'Đã nộp','timeout'=>'Hết giờ','doing'=>'Đang làm'][$status] ?? ($status ?: 'Không rõ');
      $score = (float)($r['score'] ?? 0);
    ?>
      <tr data-qt-status="<?= e($status) ?>">
        <td data-label="Sinh viên"><strong><?= e($r['student_name'] ?? 'Chưa xác định') ?></strong><small>@<?= e($r['student_username'] ?? '') ?></small></td>
        <td data-label="Đề thi"><strong><?= e($r['exam_title'] ?? '') ?></strong><small class="qt-r-code"><?= e($r['exam_code'] ?? '') ?></small></td>
        <td data-label="Môn học"><span class="qt-r-subject"><?= e($r['subject_name'] ?? 'Chưa xác định') ?></span></td>
        <td data-label="Đúng / Tổng"><strong><?= (int)($r['correct_answers'] ?? 0) ?> / <?= (int)($r['total_questions'] ?? 0) ?></strong></td>
        <td data-label="Điểm"><strong class="qt-r-score <?= $score >= 5 ? 'pass' : 'fail' ?>"><?= number_format($score, 2) ?></strong></td>
        <td data-label="Tỷ lệ"><div class="qt-r-progress"><div style="width: <?= $pct ?>%"></div></div><small><?= number_format($pct, 1) ?>%</small></td>
        <td data-label="Trạng thái"><span class="qt-r-status <?= e($status) ?>"><?= e($statusLabel) ?></span></td>
        <td data-label="Thời gian nộp"><span><?= !empty($r['submitted_at']) ? e(date('d/m/Y H:i', strtotime($r['submitted_at']))) : 'Chưa nộp' ?></span></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody></table></div>
    <div class="qt-shared-pagination"><span class="qt-shared-count"></span><label>Hiển thị <select class="qt-shared-pagesize"><option>5</option><option selected>10</option><option>20</option><option>50</option></select></label><nav class="qt-shared-pages" aria-label="Phân trang"></nav></div>
  </section>
</main>

<?php 
echo '</div></div>';
include '../includes/footer.php'; 
?>

<script src="../assets/js/qt-teacher-pages.js?v=20261025" defer></script>
