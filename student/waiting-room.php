<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireRole('student');
$user = currentUser();

$code = trim($_GET['code'] ?? '');

// Lấy thông tin phòng thi dùng PDO chuẩn
$stmt = $pdo->prepare("
    SELECT r.*, e.title as exam_title 
    FROM exam_rooms r 
    LEFT JOIN exams e ON r.exam_id = e.id 
    WHERE r.room_code = ?
");
$stmt->execute([$code]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: rooms.php");
    exit();
}

// Kiểm tra danh sách cột thực tế của bảng users để tự động chọn đúng cột
$user_columns = [];
try {
    $col_stmt = $pdo->query("SHOW COLUMNS FROM users");
    $user_columns = $col_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $user_columns = [];
}

// 1. Xác định cột họ tên (fullname, full_name, name)
$name_field = "'Học sinh'";
if (in_array('fullname', $user_columns)) {
    $name_field = "u.fullname";
} elseif (in_array('full_name', $user_columns)) {
    $name_field = "u.full_name";
} elseif (in_array('name', $user_columns)) {
    $name_field = "u.name";
}

// 2. Xác định cột tên tài khoản (username, email, account, user_name)
$account_field = $name_field;
if (in_array('username', $user_columns)) {
    $account_field = "u.username";
} elseif (in_array('email', $user_columns)) {
    $account_field = "u.email";
} elseif (in_array('account', $user_columns)) {
    $account_field = "u.account";
} elseif (in_array('user_name', $user_columns)) {
    $account_field = "u.user_name";
}

// Lấy Bảng xếp hạng các thành viên đã nộp bài trong phòng thi này
$stmt_lb = $pdo->prepare("
    SELECT {$name_field} AS display_name, {$account_field} AS account_name, sub.score, sub.submitted_at
    FROM exam_submissions sub
    JOIN users u ON sub.student_id = u.id
    WHERE sub.exam_id = ?
    ORDER BY sub.score DESC, sub.submitted_at ASC
");
$stmt_lb->execute([$room['exam_id']]);
$leaderboard = $stmt_lb->fetchAll();

$total_joined = count($leaderboard);
$is_leader = ($room['created_by'] == $user['id']);

// Xử lý khi Trưởng phòng (Leader) bấm nút "Bắt đầu bài thi"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_start'])) {
    if ($is_leader) {
        $update_stmt = $pdo->prepare("UPDATE exam_rooms SET status = 'active' WHERE id = ?");
        $update_stmt->execute([$room['id']]);
        
        header("Location: waiting-room.php?code=" . urlencode($code));
        exit();
    }
}

$page_title = 'Phòng thi: ' . $room['room_name'];
require_once dirname(__DIR__) . '/includes/header_student.php';
?>

<style>
    :root { 
        --indigo-primary: #4f46e5; 
        --indigo-hover: #4338ca;
        --indigo-soft: #eef2ff;
        --border-color: #e2e8f0; 
        --bg-body: #f8fafc; 
        --card-radius: 1.25rem; 
    }

    body { background-color: var(--bg-body); font-family: 'Inter', system-ui, sans-serif; color: #1e293b; }
    
    .waiting-card, .leaderboard-card { 
        background: #ffffff; 
        border-radius: var(--card-radius); 
        border: 1px solid var(--border-color); 
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04); 
    }

    /* Rank badges */
    .rank-badge { 
        width: 34px; 
        height: 34px; 
        border-radius: 50%; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: 700;
        font-size: 0.85rem;
    }
    .rank-1 { background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .rank-2 { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .rank-3 { background-color: #ffedd5; color: #ea580c; border: 1px solid #fed7aa; }
    .rank-other { background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

    /* Avatar placeholder */
    .avatar-initial {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--indigo-soft);
        color: var(--indigo-primary);
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Status Pulse Animation */
    .pulse-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    .pulse-warning {
        background-color: #f59e0b;
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
        animation: pulse-amber 1.8s infinite;
    }
    .pulse-success {
        background-color: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 1.8s infinite;
    }

    @keyframes pulse-amber {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Code Badge Copy */
    .code-badge {
        background-color: #f1f5f9;
        border: 1px dashed #cbd5e1;
        border-radius: 30px;
        padding: 6px 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .code-badge:hover {
        background-color: var(--indigo-soft);
        border-color: #c7d2fe;
    }
</style>

<div class="container-xl py-4 px-3 px-md-4">
    <div class="row g-4 justify-content-center">
        
        <!-- THÔNG TIN PHÒNG THI -->
        <div class="col-lg-5">
            <div class="waiting-card p-4 p-md-5 text-center h-100 d-flex flex-column justify-content-between">
                <div>
                    <!-- Badge Mã phòng & Trưởng phòng -->
                    <div class="d-flex justify-content-center align-items-center gap-2 mb-4 flex-wrap">
                        <div class="code-badge d-inline-flex align-items-center gap-2" onclick="copyRoomCode('<?= e($room['room_code']) ?>')" title="Bấm để sao chép mã">
                            <i class="bi bi-key-fill text-primary"></i>
                            <span class="fw-bold text-dark font-monospace">MÃ: <?= e($room['room_code']) ?></span>
                            <i class="bi bi-copy text-muted small" id="copyIcon"></i>
                        </div>
                        <?php if ($is_leader): ?>
                            <span class="badge bg-dark text-white fw-semibold px-3 py-2 rounded-pill">
                                <i class="bi bi-shield-lock-fill text-warning me-1"></i> Trưởng phòng
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Tên phòng & Đề thi -->
                    <h3 class="fw-bold text-dark mb-2"><?= e($room['room_name']) ?></h3>
                    
                    <div class="bg-light rounded-3 p-3 mb-4 text-start border">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-journal-check text-primary fs-5"></i>
                            <span class="text-muted small">Đề thi áp dụng:</span>
                        </div>
                        <div class="fw-bold text-dark px-1"><?= e($room['exam_title'] ?? 'Chưa cập nhật đề thi') ?></div>
                        
                        <hr class="my-2 opacity-25">
                        
                        <div class="d-flex justify-content-between align-items-center small text-secondary">
                            <span><i class="bi bi-people-fill me-1"></i> Sức chứa phòng:</span>
                            <span class="fw-semibold text-dark"><?= $room['max_participants'] ?? 10 ?> thí sinh</span>
                        </div>
                    </div>

                    <!-- Trạng thái phòng -->
                    <div class="p-3.5 rounded-3 mb-4 <?= $room['status'] === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?>">
                        <div class="fw-semibold small d-flex align-items-center justify-content-center">
                            <?php if ($room['status'] === 'active'): ?>
                                <span class="pulse-dot pulse-success"></span>
                                <span>Phòng thi đang mở! Bấm nút bên dưới để bắt đầu làm bài.</span>
                            <?php else: ?>
                                <span class="pulse-dot pulse-warning"></span>
                                <span><?= $is_leader ? 'Đang chờ bạn kích hoạt. Bấm Bắt đầu để mọi người cùng làm bài!' : 'Đang chờ Trưởng phòng mở đề thi...' ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Các nút hành động -->
                <div class="d-flex flex-column gap-2.5 max-w-xs mx-auto w-100">
                    <?php if ($room['status'] === 'active'): ?>
                        <a href="exam.php?id=<?= $room['exam_id'] ?>" class="btn btn-success btn-lg rounded-pill fw-bold shadow-sm py-2.5">
                            <i class="bi bi-pencil-square me-1"></i> Vào làm bài ngay
                        </a>
                    <?php elseif ($is_leader): ?>
                        <form method="POST">
                            <button type="submit" name="action_start" value="1" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold py-2.5 shadow-sm" style="background-color: var(--indigo-primary); border-color: var(--indigo-primary);">
                                <i class="bi bi-play-fill me-1 fs-5"></i> Bắt đầu bài thi
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" onclick="location.reload();" class="btn btn-outline-primary rounded-pill fw-semibold py-2">
                            <i class="bi bi-arrow-clockwise me-1"></i> Cập nhật trạng thái
                        </button>
                    <?php endif; ?>
                    
                    <a href="rooms.php" class="btn btn-link text-decoration-none text-muted small mt-1">
                        <i class="bi bi-box-arrow-left me-1"></i> Rời phòng thi
                    </a>
                </div>
            </div>
        </div>

        <!-- BẢNG XẾP HẠNG TRỰC TIẾP -->
        <div class="col-lg-7">
            <div class="leaderboard-card p-4 h-100 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-trophy-fill text-warning fs-4"></i>
                            <span>Bảng Xếp Hạng Phòng Thi</span>
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Cập nhật kết quả nộp bài của các thành viên</p>
                    </div>
                    <span class="badge bg-indigo-soft text-primary border px-3 py-2 rounded-pill small fw-bold" style="background-color: var(--indigo-soft); color: var(--indigo-primary);">
                        <i class="bi bi-check-circle-fill me-1"></i>Đã nộp: <?= $total_joined ?>
                    </span>
                </div>

                <?php if (empty($leaderboard)): ?>
                    <div class="text-center py-5 my-auto">
                        <div class="p-3 d-inline-flex rounded-circle mb-3" style="background-color: var(--indigo-soft); color: var(--indigo-primary);">
                            <i class="bi bi-award fs-1"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Chưa có kết quả</h6>
                        <p class="text-muted small mb-0">Chưa có thành viên nào nộp bài thi trong phòng này.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th style="width: 70px;" class="text-center">Hạng</th>
                                    <th>Thành viên</th>
                                    <th>Thời gian nộp</th>
                                    <th class="text-end">Điểm số</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaderboard as $idx => $row): 
                                    $rank = $idx + 1;
                                    $rank_class = $rank === 1 ? "rank-1" : ($rank === 2 ? "rank-2" : ($rank === 3 ? "rank-3" : "rank-other"));
                                ?>
                                    <tr>
                                        <!-- Hạng -->
                                        <td class="text-center">
                                            <div class="rank-badge <?= $rank_class ?> mx-auto">
                                                <?php if ($rank === 1): ?>🥇
                                                <?php elseif ($rank === 2): ?>🥈
                                                <?php elseif ($rank === 3): ?>🥉
                                                <?php else: ?><?= $rank ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Thành viên -->
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar-initial">
                                                    <?= mb_substr(e($row['display_name']), 0, 1) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0"><?= e($row['display_name']) ?></div>
                                                    <div class="small text-muted">@<?= e($row['account_name']) ?></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Thời gian nộp bài -->
                                        <td class="small text-muted">
                                            <i class="bi bi-clock me-1"></i><?= date('H:i:s d/m', strtotime($row['submitted_at'])) ?>
                                        </td>

                                        <!-- Điểm số -->
                                        <td class="text-end">
                                            <span class="badge px-3 py-1.5 rounded-pill fs-6 fw-bold" style="background-color: var(--indigo-soft); color: var(--indigo-primary);">
                                                <?= number_format($row['score'], 1) ?> <small class="fw-normal opacity-75">/ 10</small>
                                            </span>
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
</div>

<!-- Script Copy mã phòng -->
<script>
    function copyRoomCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            const icon = document.getElementById('copyIcon');
            icon.className = 'bi bi-check2 text-success small';
            setTimeout(() => {
                icon.className = 'bi bi-copy text-muted small';
            }, 2000);
        });
    }
</script>

<?php if ($room['status'] !== 'active'): ?>
<script>
    // Tự động làm mới trang mỗi 4 giây để đồng bộ khi Trưởng phòng bắt đầu
    setTimeout(function() { 
        location.reload(); 
    }, 4000);
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>