<?php
// student/dashboard.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('student');

// Lấy thông tin user đang đăng nhập
$user = currentUser() ?: ($_SESSION['user'] ?? []);
$student_id = (int)($user['id'] ?? $_SESSION['user_id'] ?? 0);

// =========================================================================
// TỰ ĐỘNG XÁC ĐỊNH BẢNG LƯU KẾT QUẢ VÀ TÊN CỘT TRONG CSDL (ADAPTIVE SCHEMA)
// =========================================================================
$attemptTable = 'results';
if (!$pdo->query("SHOW TABLES LIKE 'results'")->fetch()) {
    if ($pdo->query("SHOW TABLES LIKE 'exam_attempts'")->fetch()) {
        $attemptTable = 'exam_attempts';
    } elseif ($pdo->query("SHOW TABLES LIKE 'exam_submissions'")->fetch()) {
        $attemptTable = 'exam_submissions';
    }
}

// Lấy danh sách cột của bảng làm bài
$cols = $pdo->query("SHOW COLUMNS FROM {$attemptTable}")->fetchAll(PDO::FETCH_COLUMN);

$userCol = in_array('student_id', $cols) ? 'student_id' : (in_array('user_id', $cols) ? 'user_id' : 'student_id');
$dateCol = in_array('submitted_at', $cols) ? 'submitted_at' : (in_array('created_at', $cols) ? 'created_at' : 'created_at');
$statusWhere = in_array('status', $cols) ? " AND status = 'completed'" : "";

// 1. THỐNG KÊ BÀI THI ĐÃ LÀM & ĐIỂM TRUNG BÌNH
$total_completed = (int)db_fetch_var("SELECT COUNT(*) FROM {$attemptTable} WHERE {$userCol} = ? {$statusWhere}", [$student_id]);
$avg_score = (float)db_fetch_var("SELECT AVG(score) FROM {$attemptTable} WHERE {$userCol} = ? {$statusWhere}", [$student_id]);

// Thống kê câu sai (Fallback an toàn nếu bảng student_wrong_questions chưa tạo)
$total_wrong = 0;
try {
    if ($pdo->query("SHOW TABLES LIKE 'student_wrong_questions'")->fetch()) {
        $total_wrong = (int)db_fetch_var("SELECT COUNT(*) FROM student_wrong_questions WHERE student_id = ?", [$student_id]);
    }
} catch (Exception $e) {
    $total_wrong = 0;
}

// 2. LẤY DANH SÁCH BÀI THI KHẢ DỤNG (ACTIVE)
$available_exams = db_fetch_all("
    SELECT e.*, 
           (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as question_count
    FROM exams e
    WHERE e.status = 'active' OR e.status = '1'
    ORDER BY e.id DESC
");

// 3. LẤY LỊCH SỬ LÀM BÀI GẦN ĐÂY CỦA HỌC SINH
$recent_submissions = db_fetch_all("
    SELECT a.*, 
           a.{$dateCol} AS date_submitted,
           e.title AS exam_title
    FROM {$attemptTable} a 
    LEFT JOIN exams e ON a.exam_id = e.id 
    WHERE a.{$userCol} = ? {$statusWhere}
    ORDER BY a.{$dateCol} DESC 
    LIMIT 10
", [$student_id]);

// Dữ liệu biểu đồ cá nhân từ cùng bảng kết quả đã được dashboard nhận diện
$chart_attempts = db_fetch_all("SELECT score, {$dateCol} AS chart_date FROM {$attemptTable} WHERE {$userCol} = ? {$statusWhere} ORDER BY {$dateCol} ASC", [$student_id]);
$score_distribution = [0,0,0,0];
$trend_labels=[]; $trend_scores=[];
foreach($chart_attempts as $attempt){
    $score=(float)($attempt['score']??0);
    if($score<5)$score_distribution[0]++; elseif($score<7)$score_distribution[1]++; elseif($score<8.5)$score_distribution[2]++; else $score_distribution[3]++;
    if(!empty($attempt['chart_date'])){$trend_labels[]=date('d/m',strtotime($attempt['chart_date']));$trend_scores[]=round($score,2);}
}
$trend_labels=array_slice($trend_labels,-12); $trend_scores=array_slice($trend_scores,-12);

$page_title = 'Bảng điều khiển học sinh';

// Nạp Header dùng chung của học sinh
require_once dirname(__DIR__) . '/includes/header_student.php';
?>

<style>
    /* Styling Dashboard Học Sinh - Modern Admin Purple Theme */
    :root {
        --dash-purple-main: #7c3aed;
        --dash-purple-dark: #5b21b6;
        --dash-purple-light: #f3e8ff;
        --dash-card-border: #e2e8f0;
    }

    /* Banner Hero Lời Chào */
    .dashboard-hero-card {
        background: linear-gradient(135deg, #312e81 0%, #5b21b6 52%, #7c3aed 100%) !important;
        border: none;
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px -8px rgba(109, 40, 217, 0.35);
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .dashboard-hero-card::before {
        content: "";
        position: absolute;
        top: -40%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(168, 85, 247, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
        pointer-events: none;
    }

    /* Modern Stat Cards */
    .stat-card-v2 {
        background: #ffffff;
        border: 1px solid var(--dash-card-border);
        border-radius: 1rem;
        padding: 1.25rem;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    }

    .stat-card-v2:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -6px rgba(124, 58, 237, 0.12);
        border-color: #c084fc;
    }

    .stat-icon-wrapper {
        width: 52px;
        height: 52px;
        border-radius: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .stat-icon-purple { background: rgba(124, 58, 237, 0.1); color: #7c3aed; }
    .stat-icon-emerald { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .stat-icon-rose    { background: rgba(244, 63, 94, 0.1); color: #f43f5e; }

    /* Exam Cards (Thẻ bài thi) */
    .exam-card-v2 {
        background: #ffffff;
        border: 1px solid var(--dash-card-border);
        border-radius: 1rem;
        transition: all 0.25s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .exam-card-v2:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 30px -5px rgba(15, 23, 42, 0.08);
        border-color: #a855f7;
    }

    /* Cắt tỉa văn bản chống tràn/đè chữ */
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    /* History Timeline Item */
    .history-item-v2 {
        transition: background-color 0.15s ease;
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .history-item-v2:last-child {
        border-bottom: none;
    }

    .history-item-v2:hover {
        background-color: #f8fafc;
    }

    /* Custom Scrollbar for Recent Submissions if scrollable */
    .custom-history-list {
        max-height: 480px;
        overflow-y: auto;
    }

    .custom-history-list::-webkit-scrollbar {
        width: 5px;
    }
    .custom-history-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>

<div class="container-xl py-4 px-3 px-md-4">

    <!-- 1. HERO BANNER LỜI CHÀO -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-hero-card p-4 p-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 position-relative z-1">
                    <div>
                        <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-1 rounded-pill small mb-2 text-warning border border-white border-opacity-25">
                            <i class="bi bi-stars text-warning"></i>
                            <span>Hệ thống Ôn luyện & Thi Trực tuyến</span>
                        </div>
                        <h2 class="fw-bold text-white mb-1">
                            Xin chào, <?= e($user['full_name'] ?? $user['name'] ?? $user['username'] ?? 'Học sinh') ?> ^^
                        </h2>
                        <p class="text-white-50 mb-0 small fs-6">
                            Chào mừng bạn quay trở lại. Hãy chọn bài thi bên dưới hoặc luyện tập để duy trì phong độ!
                        </p>
                    </div>
                    <div>
                        <a href="practice.php" class="btn btn-light text-primary fw-bold rounded-pill px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2 hover-scale">
                            <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                            <span>Luyện tập ngay</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. STATS CARDS (Thẻ Thống Kê Tổng Quan) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Hoàn thành -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card-v2 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Bài thi hoàn thành</div>
                    <div class="fs-2 fw-bold text-dark mb-0"><?= $total_completed ?></div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-check2-circle text-success me-1"></i>Đã nộp bài thành công
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-purple">
                    <i class="bi bi-journal-check"></i>
                </div>
            </div>
        </div>

        <!-- Card 2: Điểm trung bình -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card-v2 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Điểm trung bình</div>
                    <div class="fs-2 fw-bold text-dark mb-0"><?= number_format($avg_score, 2) ?></div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-graph-up-arrow text-emerald me-1"></i>Thang điểm 10.0
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="bi bi-trophy"></i>
                </div>
            </div>
        </div>

        <!-- Card 3: Câu làm sai -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card-v2 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wider mb-1">Câu hỏi làm sai</div>
                    <div class="fs-2 fw-bold text-dark mb-0"><?= $total_wrong ?></div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-exclamation-circle text-danger me-1"></i>Cần ôn lại trong kho sai
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-rose">
                    <i class="bi bi-x-octagon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ THỐNG KÊ CÁ NHÂN -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-8"><div class="student-chart-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h5 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Tiến độ điểm số</h5><div class="text-muted small">12 lần làm bài gần nhất</div></div><a href="history.php" class="btn btn-sm btn-light text-primary fw-semibold">Lịch sử</a></div>
        <div class="student-chart-wrap"><canvas id="studentScoreTrend"></canvas></div>
      </div></div>
      <div class="col-12 col-xl-4"><div class="student-chart-card">
        <h5 class="fw-bold mb-1"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Phân bố kết quả</h5><div class="text-muted small mb-3">Theo toàn bộ bài đã hoàn thành</div>
        <div class="student-chart-wrap"><canvas id="studentScoreDistribution"></canvas></div>
      </div></div>
    </div>

    <!-- 3. KHỐI NỘI DUNG CHÍNH (MAIN GRID) -->
    <div class="row g-4">
        
        <!-- Khối 1: Danh sách bài thi mới khả dụng (Col 8) -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                            <i class="bi bi-collection-play-fill fs-5"></i>
                        </div>
                        <h5 class="card-title fw-bold text-dark mb-0">Bài Thi Mới Khả Dụng</h5>
                    </div>
                    <a href="exams.php" class="btn btn-sm btn-light text-primary fw-semibold rounded-pill px-3">
                        Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="card-body p-4 pt-0">
                    <?php if (empty($available_exams)): ?>
                        <div class="text-center py-5">
                            <div class="bg-light d-inline-flex p-3 rounded-circle mb-3">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                            </div>
                            <h6 class="fw-bold text-dark">Hiện chưa có bài thi nào!</h6>
                            <p class="text-muted small mb-0">Hãy quay lại sau hoặc tham gia Luyện tập tự do để ôn bài nhé.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($available_exams as $exam): ?>
                                <div class="col-12 col-md-6">
                                    <div class="exam-card-v2 p-3">
                                        <div class="mb-2">
                                            <span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill px-2 py-1 small fw-semibold" style="background-color: #f3e8ff; color: #7c3aed;">
                                                <i class="bi bi-patch-check me-1"></i>Sẵn sàng
                                            </span>
                                        </div>
                                        
                                        <h6 class="fw-bold text-dark mb-2 line-clamp-1" title="<?= e($exam['title'] ?? $exam['name'] ?? 'Bài thi') ?>">
                                            <?= e($exam['title'] ?? $exam['name'] ?? 'Bài thi') ?>
                                        </h6>

                                        <p class="text-muted small line-clamp-2 mb-3 flex-grow-1" style="min-height: 38px;">
                                            <?= e($exam['description'] ?? 'Không có mô tả chi tiết cho bài thi này.') ?>
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center text-muted small bg-light p-2 rounded-3 mb-3">
                                            <span class="d-flex align-items-center gap-1">
                                                <i class="bi bi-clock text-primary"></i> 
                                                <strong><?= (int)($exam['duration_minutes'] ?? $exam['duration'] ?? 45) ?></strong> phút
                                            </span>
                                            <span class="d-flex align-items-center gap-1">
                                                <i class="bi bi-question-circle text-primary"></i> 
                                                <strong><?= (int)($exam['question_count'] ?? 0) ?></strong> câu
                                            </span>
                                        </div>

                                        <a href="exam.php?id=<?= $exam['id'] ?>" class="btn btn-primary btn-sm w-100 fw-bold rounded-3 py-2 text-white shadow-sm" style="background-color: #7c3aed; border-color: #7c3aed;">
                                            <i class="bi bi-pencil-square me-1"></i>Bắt đầu làm bài
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Khối 2: Lịch sử làm bài gần đây (Col 4) -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                        <h5 class="card-title fw-bold text-dark mb-0">Lịch Sử Gần Đây</h5>
                    </div>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($recent_submissions)): ?>
                        <div class="text-center py-5 px-3">
                            <i class="bi bi-journal-x fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted small mb-0">Bạn chưa hoàn thành bài thi nào gần đây.</p>
                        </div>
                    <?php else: ?>
                        <div class="custom-history-list">
                            <?php foreach ($recent_submissions as $sub): 
                                $examTitle = !empty($sub['exam_title']) ? $sub['exam_title'] : ($sub['exam_id'] == 0 ? 'Luyện tập tự do' : 'Bài thi #' . $sub['exam_id']);
                                $scoreVal = (float)($sub['score'] ?? 0);
                                $subTime = !empty($sub['date_submitted']) ? date('H:i - d/m/Y', strtotime($sub['date_submitted'])) : '--:--';
                                
                                // Phân loại màu badge điểm số
                                $scoreBadgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                                if ($scoreVal >= 8.0) {
                                    $scoreBadgeClass = 'bg-success-subtle text-success border-success-subtle';
                                } elseif ($scoreVal >= 5.0) {
                                    $scoreBadgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                                }
                            ?>
                                <div class="history-item-v2">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                        <div class="fw-semibold text-dark line-clamp-1 flex-grow-1" title="<?= e($examTitle) ?>">
                                            <?= e($examTitle) ?>
                                        </div>
                                        <span class="badge rounded-pill border px-2 py-1 fs-7 fw-bold <?= $scoreBadgeClass ?>">
                                            <?= number_format($scoreVal, 1) ?> đ
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="text-muted small" style="font-size: 0.78rem;">
                                            <i class="bi bi-calendar3 me-1"></i><?= $subTime ?>
                                        </span>
                                        <a href="exam_result.php?id=<?= $sub['id'] ?>" class="text-decoration-none fw-bold small text-primary d-inline-flex align-items-center gap-1 hover-link">
                                            Chi tiết <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
 if(typeof Chart==='undefined')return;
 const labels=<?= json_encode($trend_labels, JSON_UNESCAPED_UNICODE) ?>,scores=<?= json_encode($trend_scores, JSON_UNESCAPED_UNICODE) ?>,dist=<?= json_encode($score_distribution) ?>;
 const t=document.getElementById('studentScoreTrend'); if(t)new Chart(t,{type:'line',data:{labels:labels,datasets:[{data:scores,borderColor:'#6d28d9',backgroundColor:'rgba(109,40,217,.10)',fill:true,tension:.35,pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:10,ticks:{stepSize:2}}},plugins:{legend:{display:false}}}});
 const d=document.getElementById('studentScoreDistribution'); if(d)new Chart(d,{type:'doughnut',data:{labels:['Dưới 5','5 - dưới 7','7 - dưới 8.5','8.5 - 10'],datasets:[{data:dist,backgroundColor:['#ef4444','#f59e0b','#6366f1','#22c55e'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8}}}}});
});
</script>

<?php 
// Nạp Footer dùng chung
require_once dirname(__DIR__) . '/includes/footer.php'; 
?>