<?php
// admin/leaderboard.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

$leaderboard = getLeaderboard();

// Sắp xếp lại thứ tự ưu tiên điểm số từ cao đến thấp
usort($leaderboard, function($a, $b) {
    return ($b['best_score'] ?? 0) <=> ($a['best_score'] ?? 0);
});

// Tính toán chỉ số tổng quan
$total_candidates = count($leaderboard);
$highest_score = !empty($leaderboard) ? ($leaderboard[0]['best_score'] ?? 0) : 0;
$total_exams_taken = array_sum(array_column($leaderboard, 'exam_count'));

$top1 = $leaderboard[0] ?? null;
$top2 = $leaderboard[1] ?? null;
$top3 = $leaderboard[2] ?? null;

$page_title = 'Bảng xếp hạng Thí sinh';
include '../includes/header_admin.php';
?>

<style>
    :root {
        --purple-main: #6366f1;
        --purple-dark: #4f46e5;
        --purple-deep: #3730a3;
        --purple-glow: rgba(99, 102, 241, 0.25);
        --gold-gradient: linear-gradient(135deg, #f59e0b, #d97706);
        --silver-gradient: linear-gradient(135deg, #94a3b8, #64748b);
        --bronze-gradient: linear-gradient(135deg, #ea580c, #c2410c);
        --text-dark: #0f172a;
        --text-muted: #64748b;
    }

    body {
        background-color: #f8fafc;
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        color: var(--text-dark);
    }

    /* BANNER TIÊU ĐỀ GRADIENT TÍM */
    .hero-banner-admin {
        background: linear-gradient(135deg, var(--purple-deep) 0%, var(--purple-dark) 50%, var(--purple-main) 100%);
        border-radius: 24px;
        padding: 2.25rem 2rem;
        color: #ffffff;
        box-shadow: 0 20px 30px -10px var(--purple-glow);
    }

    .stat-pill {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 16px;
        padding: 0.75rem 1.25rem;
        color: #ffffff;
    }

    /* PODIUM TOP 3 CARD */
    .podium-card {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .podium-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 35px var(--purple-glow);
    }

    .podium-card.rank-1 { border-top: 5px solid #f59e0b; }
    .podium-card.rank-2 { border-top: 5px solid #94a3b8; }
    .podium-card.rank-3 { border-top: 5px solid #ea580c; }

    .podium-crown {
        font-size: 1.8rem;
        line-height: 1;
    }

    .avatar-top {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.25rem;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .avatar-top.gold { background: var(--gold-gradient); }
    .avatar-top.silver { background: var(--silver-gradient); }
    .avatar-top.bronze { background: var(--bronze-gradient); }

    /* CREATIVE CARD & TABLE */
    .creative-card {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
    }

    .avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--purple-main), var(--purple-deep));
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .table-modern thead th {
        background-color: #f8fafc;
        color: var(--text-muted);
        font-size: 0.75rem;
        text-transform: uppercase;
        padding: 1rem 1.25rem;
        letter-spacing: 0.5px;
    }

    .table-modern tbody td {
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .rank-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 30px;
        font-weight: 700;
        font-size: 0.825rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .rank-1-badge { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .rank-2-badge { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .rank-3-badge { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .rank-normal-badge { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
</style>

<div class="container-fluid py-4 px-3 px-md-4">
    <!-- HERO BANNER TÍM GRADIENT -->
    <div class="hero-banner-admin mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h3 class="fw-bold mb-1 fs-3 text-white">Bảng Xếp Hạng Thí Sinh</h3>
            <p class="mb-0 text-white-50 small">Vinh danh những thí sinh có thành tích xuất sắc nhất toàn hệ thống</p>
        </div>
        <div class="d-flex flex-wrap gap-2 gap-sm-3">
            <div class="stat-pill d-flex align-items-center gap-3">
                <i class="bi bi-people-fill fs-4 text-warning"></i>
                <div>
                    <div class="fs-5 fw-bold"><?= number_format($total_candidates) ?></div>
                    <div class="small opacity-75" style="font-size: 0.75rem;">Thí sinh</div>
                </div>
            </div>
            <div class="stat-pill d-flex align-items-center gap-3">
                <i class="bi bi-star-fill fs-4 text-warning"></i>
                <div>
                    <div class="fs-5 fw-bold"><?= number_format($highest_score, 1) ?></div>
                    <div class="small opacity-75" style="font-size: 0.75rem;">Điểm cao nhất</div>
                </div>
            </div>
            <div class="stat-pill d-flex align-items-center gap-3">
                <i class="bi bi-journal-check fs-4 text-info"></i>
                <div>
                    <div class="fs-5 fw-bold"><?= number_format($total_exams_taken) ?></div>
                    <div class="small opacity-75" style="font-size: 0.75rem;">Lượt làm bài</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHU VỰC TOP 3 (PODIUM CARDS) -->
    <?php if (!empty($leaderboard)): ?>
        <div class="row g-3 mb-4">
            <!-- TOP 2 -->
            <div class="col-md-4 order-2 order-md-1">
                <?php if ($top2): ?>
                    <div class="podium-card rank-2 p-3 p-xl-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="rank-badge rank-2-badge">🥈 Hạng 2</span>
                                <span class="badge bg-light text-secondary border rounded-pill"><?= $top2['exam_count'] ?? 0 ?> bài thi</span>
                            </div>
                            <div class="avatar-top silver mx-auto mb-3">
                                <?= mb_strtoupper(mb_substr($top2['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <h5 class="fw-bold text-dark mb-1 text-truncate"><?= e($top2['name']) ?></h5>
                            <p class="text-muted small mb-3"><?= !empty($top2['student_code']) ? 'MSSV: ' . e($top2['student_code']) : 'Thành viên' ?></p>
                        </div>
                        <div class="bg-light p-2.5 rounded-4 d-flex justify-content-around">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Điểm TB</small>
                                <strong class="text-dark fs-6"><?= number_format($top2['avg_score'] ?? 0, 1) ?></strong>
                            </div>
                            <div class="vr opacity-25"></div>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Cao nhất</small>
                                <strong class="text-primary fs-6"><?= number_format($top2['best_score'] ?? 0, 1) ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TOP 1 -->
            <div class="col-md-4 order-1 order-md-2">
                <?php if ($top1): ?>
                    <div class="podium-card rank-1 p-3 p-xl-4 text-center h-100 d-flex flex-column justify-content-between border-warning">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="rank-badge rank-1-badge">👑 Quán Quân</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill"><?= $top1['exam_count'] ?? 0 ?> bài thi</span>
                            </div>
                            <div class="avatar-top gold mx-auto mb-3">
                                <?= mb_strtoupper(mb_substr($top1['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <h4 class="fw-bold text-dark mb-1 text-truncate"><?= e($top1['name']) ?></h4>
                            <p class="text-muted small mb-3"><?= !empty($top1['student_code']) ? 'MSSV: ' . e($top1['student_code']) : 'Thành viên xuất sắc' ?></p>
                        </div>
                        <div class="bg-warning-subtle p-3 rounded-4 d-flex justify-content-around border border-warning-subtle">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Điểm TB</small>
                                <strong class="text-dark fs-5"><?= number_format($top1['avg_score'] ?? 0, 1) ?></strong>
                            </div>
                            <div class="vr opacity-25"></div>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Cao nhất</small>
                                <strong class="text-warning-emphasis fs-4"><?= number_format($top1['best_score'] ?? 0, 1) ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TOP 3 -->
            <div class="col-md-4 order-3 order-md-3">
                <?php if ($top3): ?>
                    <div class="podium-card rank-3 p-3 p-xl-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="rank-badge rank-3-badge">🥉 Hạng 3</span>
                                <span class="badge bg-light text-secondary border rounded-pill"><?= $top3['exam_count'] ?? 0 ?> bài thi</span>
                            </div>
                            <div class="avatar-top bronze mx-auto mb-3">
                                <?= mb_strtoupper(mb_substr($top3['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <h5 class="fw-bold text-dark mb-1 text-truncate"><?= e($top3['name']) ?></h5>
                            <p class="text-muted small mb-3"><?= !empty($top3['student_code']) ? 'MSSV: ' . e($top3['student_code']) : 'Thành viên' ?></p>
                        </div>
                        <div class="bg-light p-2.5 rounded-4 d-flex justify-content-around">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Điểm TB</small>
                                <strong class="text-dark fs-6"><?= number_format($top3['avg_score'] ?? 0, 1) ?></strong>
                            </div>
                            <div class="vr opacity-25"></div>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Cao nhất</small>
                                <strong class="text-primary fs-6"><?= number_format($top3['best_score'] ?? 0, 1) ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- DANH SÁCH CHI TIẾT -->
    <div class="creative-card p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h5 class="fw-bold mb-1 text-dark">Bảng Chi Tiết Xếp Hạng</h5>
                <p class="text-muted small mb-0">Danh sách hiển thị tự động sắp xếp theo điểm số cao nhất giảm dần</p>
            </div>
            <div class="position-relative" style="min-width: 260px;">
                <input type="text" id="searchInput" onkeyup="filterLeaderboard()" class="form-control ps-4 rounded-pill" placeholder="Tìm tên thí sinh, MSSV...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0" id="leaderboardTable">
                <thead>
                    <tr>
                        <th style="width: 110px;" class="text-center">Xếp hạng</th>
                        <th>Thí sinh</th>
                        <th class="text-center">Số bài thi</th>
                        <th class="text-center">Điểm trung bình</th>
                        <th class="text-end">Điểm cao nhất</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaderboard)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-trophy fs-1 d-block text-secondary opacity-50 mb-2"></i>
                                Chưa có dữ liệu xếp hạng thí sinh.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaderboard as $index => $r): ?>
                            <tr data-search="<?= strtolower(e(($r['name'] ?? '') . ' ' . ($r['student_code'] ?? ''))) ?>">
                                <td class="text-center">
                                    <?php if ($index == 0): ?>
                                        <span class="rank-badge rank-1-badge">🥇 1</span>
                                    <?php elseif ($index == 1): ?>
                                        <span class="rank-badge rank-2-badge">🥈 2</span>
                                    <?php elseif ($index == 2): ?>
                                        <span class="rank-badge rank-3-badge">🥉 3</span>
                                    <?php else: ?>
                                        <span class="rank-badge rank-normal-badge">#<?= $index + 1 ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle">
                                            <?= mb_strtoupper(mb_substr($r['name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= e($r['name']) ?></div>
                                            <?php if (!empty($r['student_code'])): ?>
                                                <small class="text-muted">MSSV: <?= e($r['student_code']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Thành viên</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-semibold">
                                        <?= $r['exam_count'] ?? 0 ?> bài
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
                                        <?= number_format($r['avg_score'] ?? 0, 1) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-primary px-3 py-2 rounded-pill fs-6 fw-bold shadow-sm">
                                        <?= number_format($r['best_score'] ?? 0, 1) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterLeaderboard() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#leaderboardTable tbody tr');

    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        if (searchData.includes(input)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>