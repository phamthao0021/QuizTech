<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('student');

$leaderboard = db_fetch_all("
    SELECT u.name, u.avatar, AVG(s.score) as avg_score, COUNT(s.id) as total_exams 
    FROM exam_submissions s 
    JOIN users u ON s.student_id = u.id 
    WHERE s.status = 'completed' 
    GROUP BY u.id 
    ORDER BY avg_score DESC, total_exams DESC 
    LIMIT 20
");

$page_title = 'Bảng xếp hạng';
require_once dirname(__DIR__) . '/includes/header_student.php';

// Tách Top 3 sinh viên xuất sắc nhất nếu có
$top1 = $leaderboard[0] ?? null;
$top2 = $leaderboard[1] ?? null;
$top3 = $leaderboard[2] ?? null;
?>

<style>
    :root {
        --purple-primary: #7c3aed;
        --purple-hover: #6d28d9;
        --purple-soft: #f3e8ff;
        --card-border: #e2e8f0;
    }

    /* Hero Banner Tím Sang Trọng */
    .leaderboard-hero-card {
        background: linear-gradient(135deg, #1e1b4b 0%, #311059 45%, #6d28d9 100%);
        border: none;
        border-radius: 1.25rem;
        color: #ffffff;
        box-shadow: 0 12px 28px -8px rgba(109, 40, 217, 0.35);
        position: relative;
        overflow: hidden;
    }

    .leaderboard-hero-card::before {
        content: "";
        position: absolute;
        top: -40%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(168, 85, 247, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
        pointer-events: none;
    }

    /* Bục Vinh Danh Top 3 (Podium) */
    .podium-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 1.25rem;
        transition: all 0.25s ease;
        position: relative;
    }

    .podium-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px -4px rgba(124, 58, 237, 0.12);
    }

    .podium-card.top-1 {
        border: 2px solid #fbbf24;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
    }

    .podium-card.top-2 {
        border: 2px solid #94a3b8;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }

    .podium-card.top-3 {
        border: 2px solid #f97316;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    }

    /* Avatar styling */
    .avatar-wrapper {
        position: relative;
        display: inline-block;
    }

    .avatar-img {
        object-fit: cover;
        border-radius: 50%;
    }

    .avatar-placeholder {
        background: var(--purple-soft);
        color: var(--purple-primary);
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .crown-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 1.5rem;
    }

    /* Ranking Table Styling */
    .rank-table-card {
        background: #ffffff;
        border-radius: 1.25rem;
        border: 1px solid var(--card-border);
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .table-custom tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-custom tbody tr:hover {
        background-color: #f8fafc;
    }

    .rank-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        margin: 0 auto;
    }

    .rank-1 { background-color: #fef3c7; color: #d97706; }
    .rank-2 { background-color: #f1f5f9; color: #475569; }
    .rank-3 { background-color: #ffedd5; color: #ea580c; }
    .rank-other { background-color: #f1f5f9; color: #64748b; }

    .score-badge {
        background-color: #f3e8ff;
        color: #7c3aed;
        font-weight: 700;
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        border: 1px solid #e9d5ff;
    }
</style>

<div class="container-xl py-4 px-3 px-md-4">

    <!-- 1. HERO BANNER HEADER -->
    <div class="leaderboard-hero-card p-4 p-md-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 position-relative z-1">
            <div>
                <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-1 rounded-pill small mb-2 text-warning border border-white border-opacity-25">
                    <i class="bi bi-trophy-fill text-warning"></i>
                    <span>Bảng Vinh Danh Sinh Viên</span>
                </div>
                <h3 class="fw-bold mb-1 text-white">Bảng Xếp Hạng Xuất Sắc</h3>
                <p class="text-white-50 small mb-0">
                    Tuyên dương những sinh viên có kết quả học tập và luyện tập trắc nghiệm cao nhất
                </p>
            </div>
            
            <div class="bg-white bg-opacity-10 px-3.5 py-2 rounded-3 border border-white border-opacity-20 text-white text-md-end">
                <span class="small opacity-75 d-block">Cập nhật theo</span>
                <span class="fw-bold small"><i class="bi bi-bar-chart-line me-1"></i>Điểm TB & Số bài hoàn thành</span>
            </div>
        </div>
    </div>

    <?php if (empty($leaderboard)): ?>
        <!-- KHÔNG CÓ DỮ LIỆU -->
        <div class="card p-5 text-center border-0 shadow-sm rounded-4 my-4">
            <div class="p-3 d-inline-flex rounded-circle mb-3 mx-auto" style="background-color: #f3e8ff; color: #7c3aed; width: 70px; height: 70px; align-items: center; justify-content: center;">
                <i class="bi bi-trophy fs-1"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Chưa có dữ liệu xếp hạng</h5>
            <p class="text-muted small mb-0">Hãy là sinh viên đầu tiên hoàn thành các bài thi để xuất hiện tại đây!</p>
        </div>
    <?php else: ?>

        <!-- 2. BỤC VINH DANH TOP 3 (PODIUM) -->
        <div class="row g-3 g-md-4 mb-4 align-items-end justify-content-center">
            
            <!-- TOP 2 (SILVER) -->
            <?php if ($top2): ?>
                <div class="col-12 col-md-4 order-2 order-md-1">
                    <div class="podium-card top-2 p-4 text-center">
                        <div class="avatar-wrapper mb-3">
                            <?php if (!empty($top2['avatar'])): ?>
                                <img src="<?= e($top2['avatar']) ?>" alt="<?= e($top2['name']) ?>" class="avatar-img shadow-sm" width="70" height="70">
                            <?php else: ?>
                                <div class="avatar-placeholder shadow-sm fs-4 mx-auto" style="width: 70px; height: 70px;">
                                    <?= mb_substr(e($top2['name']), 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            <span class="badge bg-secondary position-absolute bottom-0 start-50 translate-middle-x rounded-circle p-1.5 border border-2 border-white">
                                🥈
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($top2['name']) ?>"><?= e($top2['name']) ?></h6>
                        <div class="small text-muted mb-2"><i class="bi bi-file-earmark-check me-1"></i><?= $top2['total_exams'] ?> bài thi</div>
                        <div class="d-inline-block">
                            <span class="score-badge fs-6">
                                <?= number_format($top2['avg_score'], 2) ?> <small class="fw-normal">điểm</small>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TOP 1 (GOLD) -->
            <?php if ($top1): ?>
                <div class="col-12 col-md-4 order-1 order-md-2">
                    <div class="podium-card top-1 p-4 text-center shadow-sm">
                        <div class="avatar-wrapper mb-3">
                            <span class="crown-badge">👑</span>
                            <?php if (!empty($top1['avatar'])): ?>
                                <img src="<?= e($top1['avatar']) ?>" alt="<?= e($top1['name']) ?>" class="avatar-img shadow" width="85" height="85">
                            <?php else: ?>
                                <div class="avatar-placeholder shadow fs-3 mx-auto" style="width: 85px; height: 85px; background-color: #fef3c7; color: #d97706;">
                                    <?= mb_substr(e($top1['name']), 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            <span class="badge bg-warning text-dark position-absolute bottom-0 start-50 translate-middle-x rounded-circle p-2 border border-2 border-white fs-6">
                                🥇
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($top1['name']) ?>"><?= e($top1['name']) ?></h5>
                        <div class="small text-muted mb-2"><i class="bi bi-file-earmark-check me-1"></i><?= $top1['total_exams'] ?> bài thi</div>
                        <div class="d-inline-block">
                            <span class="score-badge fs-5 px-3 py-1 text-warning-emphasis bg-warning-subtle border-warning-subtle">
                                <?= number_format($top1['avg_score'], 2) ?> <small class="fw-normal fs-6">điểm</small>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TOP 3 (BRONZE) -->
            <?php if ($top3): ?>
                <div class="col-12 col-md-4 order-3 order-md-3">
                    <div class="podium-card top-3 p-4 text-center">
                        <div class="avatar-wrapper mb-3">
                            <?php if (!empty($top3['avatar'])): ?>
                                <img src="<?= e($top3['avatar']) ?>" alt="<?= e($top3['name']) ?>" class="avatar-img shadow-sm" width="70" height="70">
                            <?php else: ?>
                                <div class="avatar-placeholder shadow-sm fs-4 mx-auto" style="width: 70px; height: 70px;">
                                    <?= mb_substr(e($top3['name']), 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            <span class="badge bg-danger position-absolute bottom-0 start-50 translate-middle-x rounded-circle p-1.5 border border-2 border-white">
                                🥉
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($top3['name']) ?>"><?= e($top3['name']) ?></h6>
                        <div class="small text-muted mb-2"><i class="bi bi-file-earmark-check me-1"></i><?= $top3['total_exams'] ?> bài thi</div>
                        <div class="d-inline-block">
                            <span class="score-badge fs-6">
                                <?= number_format($top3['avg_score'], 2) ?> <small class="fw-normal">điểm</small>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- 3. BẢNG XẾP HẠNG CHI TIẾT (TOP 20) -->
        <div class="rank-table-card">
            <div class="p-3 px-md-4 border-bottom bg-light d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-list-ol text-primary fs-5"></i>
                    <span>Danh Sách Top 20 Sinh Viên</span>
                </h6>
                <span class="badge bg-white text-muted border px-2.5 py-1.5 rounded-pill small">
                    Hiển thị <?= count($leaderboard) ?> sinh viên
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="text-center py-3 px-3" style="width: 90px;">Thứ Hạng</th>
                            <th class="py-3">Sinh Viên</th>
                            <th class="text-center py-3">Số Bài Đã Làm</th>
                            <th class="text-end py-3 px-4">Điểm Trung Bình</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $idx => $row): 
                            $rank = $idx + 1;
                            $rankClass = ($rank === 1) ? 'rank-1' : (($rank === 2) ? 'rank-2' : (($rank === 3) ? 'rank-3' : 'rank-other'));
                        ?>
                            <tr>
                                <!-- Hạng -->
                                <td class="text-center px-3">
                                    <div class="rank-number <?= $rankClass ?>">
                                        <?php if ($rank === 1): ?>
                                            🥇
                                        <?php elseif ($rank === 2): ?>
                                            🥈
                                        <?php elseif ($rank === 3): ?>
                                            🥉
                                        <?php else: ?>
                                            <?= $rank ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Sinh viên (Avatar + Tên) -->
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($row['avatar'])): ?>
                                            <img src="<?= e($row['avatar']) ?>" alt="<?= e($row['name']) ?>" class="avatar-img border" width="42" height="42">
                                        <?php else: ?>
                                            <div class="avatar-placeholder fw-bold fs-6" style="width: 42px; height: 42px;">
                                                <?= mb_substr(e($row['name']), 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= e($row['name']) ?></div>
                                            <?php if ($rank <= 3): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill small" style="font-size: 0.7rem;">
                                                    Top Performance
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Số bài thi -->
                                <td class="text-center fw-semibold text-secondary">
                                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill">
                                        <i class="bi bi-file-earmark-text text-primary me-1"></i><?= $row['total_exams'] ?> bài
                                    </span>
                                </td>

                                <!-- Điểm trung bình -->
                                <td class="text-end px-4">
                                    <span class="score-badge">
                                        <?= number_format($row['avg_score'], 2) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>