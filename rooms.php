<?php
// rooms.php - Trang Phòng thi (Guest)
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';

$page_title = 'Phòng thi';
$rooms = getRooms() ?? [];
$exams = getExams() ?? [];

include 'includes/header_guest.php';
?>

<style>
.join-banner {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 24px;
    color: white;
}

.room-card {
    border: none;
    border-radius: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.room-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(79, 70, 229, 0.12) !important;
}

.room-code-badge {
    background: #eef2ff;
    color: #4f46e5;
    padding: 6px 12px;
    border-radius: 10px;
    font-family: monospace;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 1px;
}

.btn-gradient {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    border: none;
    color: white;
    font-weight: 600;
    border-radius: 12px;
    padding: 10px;
}

.btn-gradient:hover {
    background: linear-gradient(135deg, #4338ca, #4f46e5);
    color: white;
}
</style>

<div class="container py-4">
    <!-- Banner tham gia phòng -->
    <div class="card border-0 shadow-lg mb-5 join-banner">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <span class="badge bg-white bg-opacity-20 text-dark px-3 py-2 rounded-pill mb-2">Trực tuyến</span>
                    <h2 class="fw-bold mb-2 display-6">Tham gia phòng thi</h2>
                    <p class="text-white-50 mb-0">Nhập mã phòng do giáo viên hoặc bạn bè cung cấp để thi đấu ngay lập tức.</p>
                </div>
                <div class="col-lg-6">
                    <form method="POST" action="room_join.php">
                        <?php if (function_exists('csrf_field')) csrf_field(); ?>
                        <div class="bg-white p-2 rounded-4 shadow-sm d-flex gap-2">
                            <input type="text" name="room_code" class="form-control form-control-lg border-0 bg-transparent text-dark px-3 fw-bold" placeholder="Nhập mã phòng (Ví dụ: ROOM123)..." required>
                            <button type="submit" class="btn btn-gradient px-4 fs-6 text-nowrap rounded-3">
                                Vào phòng <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách phòng -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Phòng thi đang mở</h3>
        <span class="text-muted fs-6">Tổng số: <strong><?= count($rooms) ?></strong> phòng</span>
    </div>

    <?php if (empty($rooms)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body py-4">
                <i class="bi bi-door-closed display-1 text-muted"></i>
                <h4 class="fw-bold mt-3">Hiện chưa có phòng thi nào</h4>
                <p class="text-muted mb-0">Hãy quay lại sau hoặc tạo phòng thi mới nếu bạn là giáo viên.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rooms as $r): ?>
                <?php
                $exam_name = 'Chưa có đề';
                $r_exam_id = $r['exam_id'] ?? null;
                if ($r_exam_id) {
                    foreach ($exams as $ex) {
                        if (($ex['id'] ?? null) == $r_exam_id) {
                            $exam_name = $ex['title'] ?? 'Chưa có đề';
                            break;
                        }
                    }
                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 room-card">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark"><?= e($r['name'] ?? 'Phòng thi') ?></h5>
                                    <span class="room-code-badge"><i class="bi bi-hash me-1"></i><?= e($r['code'] ?? 'N/A') ?></span>
                                </div>
                                <?= function_exists('status_badge') ? status_badge($r['status'] ?? 'waiting') : '<span class="badge bg-success">Đang chờ</span>' ?>
                            </div>

                            <div class="bg-light p-3 rounded-3 mb-4">
                                <small class="text-muted d-block mb-1">Đề thi áp dụng:</small>
                                <strong class="text-dark line-clamp-1"><?= e($exam_name) ?></strong>
                            </div>

                            <div class="row text-center mb-4 g-2">
                                <div class="col-6">
                                    <div class="p-2 border rounded-3 bg-white">
                                        <h4 class="fw-bold text-primary mb-0"><?= intval($r['max_players'] ?? 10) ?></h4>
                                        <small class="text-muted">Sức chứa</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 border rounded-3 bg-white">
                                        <h5 class="fw-bold text-success mb-0 text-capitalize mt-1"><?= e($r['status'] ?? 'waiting') ?></h5>
                                        <small class="text-muted">Trạng thái</small>
                                    </div>
                                </div>
                            </div>

                            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                <a href="room_join.php?code=<?= urlencode($r['code'] ?? '') ?>" class="btn btn-gradient w-100 mt-auto">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Tham gia phòng
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary w-100 rounded-3 mt-auto fw-semibold">
                                    <i class="bi bi-person me-1"></i> Đăng nhập để vào
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer_guest.php'; ?>