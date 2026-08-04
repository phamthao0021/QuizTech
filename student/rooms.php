<?php
// student/rooms.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireLogin();

$rooms = getRooms() ?? [];
$exams = getExams() ?? [];

$page_title = 'Phòng thi';
include '../includes/header.php';
?>

<style>
    .room-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        transition: all 0.25s ease;
    }
    .room-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }
    .room-code-badge {
        font-family: monospace;
        letter-spacing: 1px;
        background-color: #f1f5f9;
        color: #334155;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
    }
</style>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content container-fluid py-4 px-3 px-lg-4">

        <?php if (function_exists('displayFlash')) displayFlash(); ?>

        <!-- HEADER TOPBAR -->
        <div class="topbar mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1">Phòng thi trực tuyến</h4>
                <p class="text-muted small mb-0">Tham gia bằng mã phòng thi hoặc lựa chọn phòng đang mở bên dưới.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold fs-7">
                <i class="bi bi-door-open me-1"></i> Sinh viên
            </span>
        </div>

        <!-- FORM NHẬP MÃ PHÒNG THI -->
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-key me-2 text-primary"></i>Tham gia bằng mã phòng</h6>
                <form method="POST" action="../room_join.php" class="row g-2 align-items-center">
                    <?php if (function_exists('csrf_field')) csrf_field(); ?>
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-hash"></i></span>
                            <input type="text" name="room_code" class="form-control border-start-0 ps-0" placeholder="Nhập mã phòng (VD: PT001)" required style="text-transform: uppercase;">
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold rounded-2 py-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Vào phòng
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DANH SÁCH PHÒNG THI (CARD GRID) -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark">Danh sách phòng thi đang mở</h5>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3"><?= count($rooms) ?> phòng</span>
        </div>

        <?php if (empty($rooms)): ?>
            <div class="card border-0 shadow-sm rounded-3 py-5 text-center">
                <div class="card-body">
                    <i class="bi bi-door-closed fs-1 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">Hiện chưa có phòng thi nào đang mở.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($rooms as $r): ?>
                    <?php
                    $exam_name = 'Chưa gán đề thi';
                    foreach ($exams as $ex) {
                        if ($ex['id'] == $r['exam_id']) {
                            $exam_name = $ex['title'];
                            break;
                        }
                    }
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card room-card h-100 bg-white">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="room-code-badge fs-7"><?= e($r['code']) ?></span>
                                        <?php if (function_exists('status_badge')): ?>
                                            <?= status_badge($r['status'] ?? 'waiting') ?>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info fw-semibold fs-8"><?= e($r['status'] ?? 'waiting') ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="fw-bold text-dark mb-2"><?= e($r['name']) ?></h5>
                                    
                                    <p class="text-muted small mb-3 text-truncate">
                                        <i class="bi bi-journal-text me-1"></i> Đề thi: <strong><?= e($exam_name) ?></strong>
                                    </p>
                                </div>

                                <div>
                                    <hr class="my-3 opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">
                                            <i class="bi bi-people me-1"></i> Tối đa: <strong><?= (int)($r['max_players'] ?? 10) ?></strong> người
                                        </span>
                                        
                                        <a href="../room_join.php?code=<?= e($r['code']) ?>" class="btn btn-outline-primary btn-sm px-3 fw-semibold rounded-2">
                                            Tham gia <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../includes/footer.php'; ?>