<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole('student');

// BUG FIX: tìm kiếm được xử lý bằng prepared statement, không nối chuỗi
$q = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($q !== '') {
    $where = "WHERE s.name LIKE ? OR s.code LIKE ?";
    $params = ['%' . $q . '%', '%' . $q . '%'];
}

// BUG FIX: COUNT(DISTINCT e.id) tránh nhân bản khi có JOIN khác,
// và ORDER BY ưu tiên môn có đề thi
$subjects = db_fetch_all("
    SELECT s.*, COUNT(DISTINCT e.id) AS total_exams
    FROM subjects s
    LEFT JOIN exams e ON s.id = e.subject_id AND e.status = 'published'
    $where
    GROUP BY s.id
    ORDER BY total_exams DESC, s.name ASC
", $params);

$page_title = 'Danh sách môn học';
require_once dirname(__DIR__) . '/includes/header_student.php';
?>

<style>
    :root {
        --sub-purple-primary: #7c3aed;
        --sub-purple-hover: #6d28d9;
        --sub-card-border: #e2e8f0;
    }

    /* Hero Banner Tím Gradient Sang Trọng */
    .subject-hero-card {
        background: linear-gradient(135deg, #1e1b4b 0%, #311059 45%, #6d28d9 100%);
        border: none;
        border-radius: 1.25rem;
        color: #ffffff;
        box-shadow: 0 12px 28px -8px rgba(109, 40, 217, 0.35);
        position: relative;
        overflow: hidden;
    }

    .subject-hero-card::before {
        content: "";
        position: absolute;
        top: -40%;
        right: -10%;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(168, 85, 247, 0.28) 0%, rgba(255, 255, 255, 0) 70%);
        pointer-events: none;
    }

    /* Thanh Tìm Kiếm Hiện Đại */
    .subject-search-box .input-group-text {
        background-color: #ffffff;
        border-color: rgba(255, 255, 255, 0.2);
        color: #7c3aed;
    }

    .subject-search-box .form-control {
        border-color: rgba(255, 255, 255, 0.2);
    }

    .subject-search-box .form-control:focus {
        box-shadow: none;
        background-color: #ffffff;
    }

    /* Thẻ Môn Học Subject Card */
    .subject-card {
        background: #ffffff;
        border: 1px solid var(--sub-card-border);
        border-radius: 1rem;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .subject-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px -6px rgba(124, 58, 237, 0.15) !important;
        border-color: #a855f7;
    }

    .subject-icon-box {
        width: 50px;
        height: 50px;
        border-radius: 0.85rem;
        background: rgba(124, 58, 237, 0.1);
        color: #7c3aed;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Cắt tỉa văn bản thông minh chống đè chữ */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
        min-height: 2.6em;
    }

    .btn-purple {
        background-color: var(--sub-purple-primary);
        border-color: var(--sub-purple-primary);
        color: #ffffff;
    }

    .btn-purple:hover, .btn-purple:focus {
        background-color: var(--sub-purple-hover);
        border-color: var(--sub-purple-hover);
        color: #ffffff;
    }

    .hover-scale {
        transition: transform 0.2s ease;
    }
    .hover-scale:hover {
        transform: scale(1.03);
    }
</style>

<div class="container-xl py-4 px-3 px-md-4">

    <!-- 1. HERO BANNER & THANH TÌM KIẾM -->
    <div class="subject-hero-card p-4 p-md-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 position-relative z-1">
            <div class="min-w-0">
                <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-1 rounded-pill small mb-2 text-warning border border-white border-opacity-25">
                    <i class="bi bi-book-half text-warning"></i>
                    <span>Thư viện môn học</span>
                </div>
                <h3 class="fw-bold mb-1 text-white text-truncate">Danh Sách Môn Học</h3>
                <p class="text-white-50 small mb-0">
                    Hiện có <strong class="text-white"><?= count($subjects) ?></strong> môn học <?= $q !== '' ? 'khớp với từ khóa tìm kiếm' : 'đang mở trên hệ thống' ?>.
                </p>
            </div>

            <a href="create_subject.php" class="btn btn-light text-primary fw-bold rounded-pill px-4 py-2 flex-shrink-0 shadow-sm hover-scale">
                <i class="bi bi-plus-circle-fill me-1 text-primary"></i> Đề xuất môn học
            </a>
        </div>

        <!-- FORM TÌM KIẾM -->
        <form method="get" role="search" class="mt-3 position-relative z-1">
            <div class="input-group input-group-lg subject-search-box shadow-sm rounded-3 overflow-hidden">
                <span class="input-group-text border-0 ps-3">
                    <i class="bi bi-search fs-5"></i>
                </span>
                <input type="search" name="q" value="<?= e($q) ?>" class="form-control border-0 fs-6 py-2"
                       placeholder="Tìm theo tên môn học hoặc mã môn (VD: CTHDL, Toán rời rạc)..." aria-label="Tìm môn học">
                <?php if ($q !== ''): ?>
                    <a href="subjects.php" class="btn btn-light border-0 px-3 d-flex align-items-center text-muted fw-semibold">
                        <i class="bi bi-x-circle-fill me-1"></i> Xóa lọc
                    </a>
                <?php endif; ?>
                <button class="btn btn-purple px-4 fw-bold" type="submit">
                    Tìm kiếm
                </button>
            </div>
        </form>
    </div>

    <!-- 2. DANH SÁCH MÔN HỌC (GRID CARDS) -->
    <?php if (empty($subjects)): ?>
        <!-- Empty State -->
        <div class="text-center py-5 bg-white rounded-4 border shadow-sm my-4 px-3">
            <div class="p-3 d-inline-flex rounded-circle mb-3" style="background-color: #f3e8ff; color: #7c3aed;">
                <i class="bi bi-journal-x display-5"></i>
            </div>
            <h5 class="fw-bold text-dark">
                <?= $q !== '' ? 'Không tìm thấy môn học phù hợp' : 'Hiện chưa có môn học nào' ?>
            </h5>
            <p class="text-muted small mb-3">Bạn có thể gửi yêu cầu đề xuất môn học mới để quản trị viên xét duyệt.</p>
            <a href="create_subject.php" class="btn btn-purple btn-sm rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-plus-lg me-1"></i> Đề xuất môn học
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3 g-md-4">
            <?php foreach ($subjects as $subject): ?>
                <?php
                    $total = (int)($subject['total_exams'] ?? 0);
                    $sid   = (int)$subject['id'];
                    $desc  = trim((string)($subject['description'] ?? ''));
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
                    <div class="card subject-card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex flex-column p-3 p-md-4">
                            
                            <!-- Card Header & Icon -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="subject-icon-box">
                                    <i class="bi bi-journal-bookmark-fill fs-4"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <h6 class="card-title fw-bold mb-0 text-dark text-truncate" title="<?= e($subject['name']) ?>">
                                        <?= e($subject['name']) ?>
                                    </h6>
                                    <?php if (!empty($subject['code'])): ?>
                                        <span class="badge bg-light text-secondary border small mt-1 font-monospace">
                                            <?= e($subject['code']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="small text-muted">Môn học</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Description -->
                            <p class="card-text text-muted small line-clamp-2 mb-3">
                                <?= e($desc !== '' ? $desc : 'Chưa có thông tin mô tả chi tiết cho môn học này.') ?>
                            </p>

                            <!-- Footer Card: mt-auto giữ hàng nút luôn thẳng hàng -->
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-auto pt-3 border-top border-light">
                                <span class="badge rounded-pill px-2.5 py-1.5 <?= $total > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?> fw-semibold">
                                    <i class="bi bi-file-earmark-text me-1"></i><?= $total ?> đề thi
                                </span>

                                <?php if ($total > 0): ?>
                                    <a href="exams.php?subject_id=<?= $sid ?>" class="btn btn-sm btn-purple rounded-3 px-3 fw-bold shadow-sm">
                                        Vào làm bài <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- Không cho bấm vào môn chưa có đề thi -->
                                    <button class="btn btn-sm btn-light text-muted border rounded-3 px-3" disabled>
                                        Chưa có đề
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>