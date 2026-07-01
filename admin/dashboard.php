<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Admin Dashboard';
$pdo = db();

// ============================================
// Thống kê tổng quan
// ============================================
$stats = [
    'users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'students' => $pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn(),
    'teachers' => $pdo->query('SELECT COUNT(*) FROM users WHERE role = "teacher"')->fetchColumn(),
    'subjects' => $pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn(),
    'questions' => $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
    'exams' => $pdo->query('SELECT COUNT(*) FROM exams')->fetchColumn(),
    'results' => $pdo->query('SELECT COUNT(*) FROM results')->fetchColumn(),
    'rooms' => $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
];

// ============================================
// Thống kê theo thời gian
// ============================================
// Users đăng ký trong 7 ngày qua
$weeklyUsers = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date
")->fetchAll();

// Results trong 7 ngày qua
$weeklyResults = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count, AVG(score) as avg_score
    FROM results 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date
")->fetchAll();

// ============================================
// Top môn học có nhiều câu hỏi nhất
// ============================================
$topSubjects = $pdo->query("
    SELECT s.name, COUNT(q.id) as question_count
    FROM subjects s
    LEFT JOIN questions q ON q.subject_id = s.id
    GROUP BY s.id
    ORDER BY question_count DESC
    LIMIT 5
")->fetchAll();

// ============================================
// Top sinh viên có điểm cao nhất
// ============================================
$topStudents = $pdo->query("
    SELECT u.fullname, u.student_code, AVG(r.score) as avg_score, COUNT(r.id) as exam_count
    FROM users u
    JOIN results r ON r.user_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY avg_score DESC
    LIMIT 10
")->fetchAll();

// ============================================
// Lịch sử hoạt động gần đây
// ============================================
$recentActivities = $pdo->query("
    SELECT 'exam' as type, u.fullname, e.title as name, r.created_at as time
    FROM results r
    JOIN users u ON u.id = r.user_id
    JOIN exams e ON e.id = r.exam_id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

$recentActivities2 = $pdo->query("
    SELECT 'user' as type, fullname, 'registered' as name, created_at as time
    FROM users
    ORDER BY created_at DESC
    LIMIT 3
")->fetchAll();

$recentActivities = array_merge($recentActivities, $recentActivities2);
usort($recentActivities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$recentActivities = array_slice($recentActivities, 0, 8);

include __DIR__ . '/../includes/header.php';
?>

<!-- ============================================
    ADMIN DASHBOARD
============================================ -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Dashboard</h1>
            <p class="text-muted">Tổng quan hệ thống QuizTech</p>
        </div>
        <div>
            <span class="badge bg-primary">Version <?= APP_VERSION ?></span>
            <span class="badge bg-success ms-2">Online</span>
        </div>
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
                            <p class="text-muted mb-1">Tổng người dùng</p>
                            <h3 class="stat-number"><?= number_format($stats['users']) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-user-graduate"></i> SV: <?= number_format($stats['students']) ?> 
                            | <i class="fas fa-user-tie"></i> GV: <?= number_format($stats['teachers']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stat border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Môn học</p>
                            <h3 class="stat-number"><?= number_format($stats['subjects']) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-book text-success"></i>
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
                            <p class="text-muted mb-1">Ngân hàng câu hỏi</p>
                            <h3 class="stat-number"><?= number_format($stats['questions']) ?></h3>
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
                            <p class="text-muted mb-1">Đề thi</p>
                            <h3 class="stat-number"><?= number_format($stats['exams']) ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt text-danger"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-check-circle text-success"></i> Kết quả: <?= number_format($stats['results']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
    SECOND ROW - CHARTS
    ========================================== -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-chart-line text-primary"></i> Hoạt động 7 ngày qua</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Người dùng mới</h6>
                            <div id="userChart" style="height: 200px;"></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Bài thi & Điểm TB</h6>
                            <div id="resultChart" style="height: 200px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-crown text-warning"></i> Top môn học</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($topSubjects as $index => $subject): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <span class="badge bg-secondary me-2"><?= $index + 1 ?></span>
                                <?= e($subject['name']) ?>
                            </span>
                            <span class="badge bg-primary"><?= number_format($subject['question_count']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
    THIRD ROW - TOP STUDENTS & RECENT ACTIVITIES
    ========================================== -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-trophy text-warning"></i> Top sinh viên</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>MSSV</th>
                                    <th>Họ tên</th>
                                    <th>Điểm TB</th>
                                    <th>Số bài</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topStudents as $index => $student): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index == 0): ?>
                                                <span class="badge bg-warning text-dark">🥇</span>
                                            <?php elseif ($index == 1): ?>
                                                <span class="badge bg-secondary">🥈</span>
                                            <?php elseif ($index == 2): ?>
                                                <span class="badge bg-danger">🥉</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark"><?= $index + 1 ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($student['student_code']) ?></td>
                                        <td><?= e($student['fullname']) ?></td>
                                        <td><strong><?= number_format($student['avg_score'], 1) ?></strong></td>
                                        <td><?= number_format($student['exam_count']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="fas fa-clock text-info"></i> Hoạt động gần đây</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($activity['type'] == 'exam'): ?>
                                        <i class="fas fa-file-alt text-primary me-2"></i>
                                        <span><?= e($activity['fullname']) ?></span>
                                        <span class="text-muted">đã làm</span>
                                        <strong><?= e($activity['name']) ?></strong>
                                    <?php else: ?>
                                        <i class="fas fa-user-plus text-success me-2"></i>
                                        <span><?= e($activity['fullname']) ?></span>
                                        <span class="text-muted">đã đăng ký</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= time_ago($activity['time']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
CHARTS - Using Chart.js
========================================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ==========================================
    // User Chart
    // ==========================================
    const userData = <?= json_encode($weeklyUsers) ?>;
    const userCtx = document.getElementById('userChart').getContext('2d');
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: userData.map(d => d.date),
            datasets: [{
                label: 'Người dùng mới',
                data: userData.map(d => d.count),
                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // ==========================================
    // Result Chart
    // ==========================================
    const resultData = <?= json_encode($weeklyResults) ?>;
    const resultCtx = document.getElementById('resultChart').getContext('2d');
    new Chart(resultCtx, {
        type: 'line',
        data: {
            labels: resultData.map(d => d.date),
            datasets: [
                {
                    label: 'Số bài thi',
                    data: resultData.map(d => d.count),
                    borderColor: 'rgba(25, 135, 84, 1)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Điểm TB',
                    data: resultData.map(d => d.avg_score ? parseFloat(d.avg_score) : 0),
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderDash: [5, 5],
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    ticks: { stepSize: 1 }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    min: 0,
                    max: 10
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>