<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireRole('student');

$user = currentUser();
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// 1. KIỂM TRA QUYỀN CRUD CỦA SINH VIÊN (Ví dụ: Trưởng nhóm hoặc Admin/Teacher)
$can_manage_exams = false;
if (isset($user['role']) && in_array($user['role'], ['admin', 'teacher'])) {
    $can_manage_exams = true;
} elseif (!empty($user['is_leader']) && $user['is_leader'] == 1) { // Giả định cột is_leader trong DB
    $can_manage_exams = true;
}

// 2. CẤU HÌNH PHÂN TRANG (PAGINATION)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6; // Số đề thi hiển thị trên mỗi trang
$offset = ($page - 1) * $limit;

// 3. TRUY VẤN TÍNH TỔNG SỐ ĐỀ THI (ĐỂ TÍNH TỔNG SỐ TRANG)
$count_query = "SELECT COUNT(*) as total FROM exams e WHERE e.status IN ('active', 'published')";
$count_params = [];

if ($subject_id > 0) {
    $count_query .= " AND e.subject_id = ?";
    $count_params[] = $subject_id;
}

$total_records_result = db_fetch_one($count_query, $count_params);
$total_records = $total_records_result['total'] ?? 0;
$total_pages = ceil($total_records / $limit);

// 4. TRUY VẤN LẤY DANH SÁCH ĐỀ THI THEO TRANG
$query = "
    SELECT e.*, 
           s.name AS subject_name,
           (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) AS total_questions_count,
           latest_sub.score AS last_score,
           latest_sub.submitted_at
    FROM exams e
    LEFT JOIN subjects s ON e.subject_id = s.id
    LEFT JOIN (
        SELECT sub1.exam_id, sub1.score, sub1.submitted_at
        FROM exam_submissions sub1
        INNER JOIN (
            SELECT exam_id, MAX(id) AS max_id
            FROM exam_submissions
            WHERE student_id = ?
            GROUP BY exam_id
        ) sub2 ON sub1.id = sub2.max_id
    ) latest_sub ON e.id = latest_sub.exam_id
    WHERE e.status IN ('active', 'published')
";

$params = [$user['id']];

if ($subject_id > 0) {
    $query .= " AND e.subject_id = ?";
    $params[] = $subject_id;
}

$query .= " ORDER BY e.id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$exams = db_fetch_all($query, $params);

$page_title = 'Danh sách đề thi';
require_once dirname(__DIR__) . '/includes/header_student.php';
?>

<style>
    :root {
        --purple-primary: #7c3aed;
        --purple-hover: #6d28d9;
        --purple-soft: #f3e8ff;
        --card-border: #e2e8f0;
    }

    /* Hero Banner Tím Sang Trọng */
    .exam-hero-card {
        background: linear-gradient(135deg, #1e1b4b 0%, #311059 45%, #6d28d9 100%);
        border: none;
        border-radius: 1.25rem;
        color: #ffffff;
        box-shadow: 0 12px 28px -8px rgba(109, 40, 217, 0.35);
        position: relative;
        overflow: hidden;
    }

    .exam-hero-card::before {
        content: "";
        position: absolute;
        top: -40%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(168, 85, 247, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
        pointer-events: none;
    }

    /* Modern Exam Cards */
    .exam-card-v2 {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 1rem;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .exam-card-v2:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px -6px rgba(124, 58, 237, 0.14) !important;
        border-color: #a855f7;
    }

    .exam-icon-wrapper {
        width: 46px;
        height: 46px;
        border-radius: 0.85rem;
        background: var(--purple-soft);
        color: var(--purple-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    /* Tránh vỡ chữ và tràn layout */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
        min-height: 2.8em;
    }

    .btn-purple {
        background-color: var(--purple-primary);
        border-color: var(--purple-primary);
        color: #ffffff;
    }

    .btn-purple:hover, .btn-purple:focus {
        background-color: var(--purple-hover);
        border-color: var(--purple-hover);
        color: #ffffff;
    }

    .btn-outline-purple {
        border-color: var(--purple-primary);
        color: var(--purple-primary);
    }

    .btn-outline-purple:hover {
        background-color: var(--purple-primary);
        border-color: var(--purple-primary);
        color: #ffffff;
    }

    /* Phân trang Modern Purple */
    .pagination-v2 .page-link {
        border-radius: 0.5rem;
        margin: 0 3px;
        color: #475569;
        font-weight: 600;
        border: 1px solid var(--card-border);
        padding: 0.4rem 0.85rem;
    }

    .pagination-v2 .page-item.active .page-link {
        background-color: var(--purple-primary);
        border-color: var(--purple-primary);
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

    <!-- 1. HERO BANNER HEADER & LỌC -->
    <div class="exam-hero-card p-4 p-md-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 position-relative z-1">
            <div>
                <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-1 rounded-pill small mb-2 text-warning border border-white border-opacity-25">
                    <i class="bi bi-file-earmark-check text-warning"></i>
                    <span>Thư viện bài thi & Kiểm tra</span>
                </div>
                <h3 class="fw-bold mb-1 text-white">Danh Sách Đề Thi</h3>
                <p class="text-white-50 small mb-0">
                    Lựa chọn đề thi trắc nghiệm phù hợp để kiểm tra và củng cố kiến thức
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <?php if ($subject_id > 0): ?>
                    <a href="exams.php" class="btn btn-light text-dark rounded-pill px-3 py-2 small fw-bold shadow-sm d-inline-flex align-items-center gap-1 hover-scale">
                        <i class="bi bi-x-circle-fill text-danger"></i>
                        <span>Bỏ lọc môn học</span>
                    </a>
                <?php endif; ?>

                <?php if ($can_manage_exams): ?>
                    <a href="create_exam.php" class="btn btn-warning text-dark fw-bold rounded-pill px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2 hover-scale">
                        <i class="bi bi-plus-lg fs-6"></i>
                        <span>Tạo đề thi mới</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 2. DANH SÁCH ĐỀ THI (GRID CARDS) -->
    <?php if (empty($exams)): ?>
        <div class="card p-5 text-center border-0 shadow-sm rounded-4 my-4">
            <div class="p-3 d-inline-flex rounded-circle mb-3 mx-auto" style="background-color: #f3e8ff; color: #7c3aed; width: 70px; height: 70px; align-items: center; justify-content: center;">
                <i class="bi bi-journal-x fs-1"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Không tìm thấy đề thi phù hợp</h5>
            <p class="text-muted small mb-0">Vui lòng quay lại sau hoặc chọn môn học khác để tìm bài thi.</p>
        </div>
    <?php else: ?>
        <div class="row g-3 g-md-4">
            <?php foreach ($exams as $exam): 
                $duration = intval($exam['duration'] ?? $exam['duration_minutes'] ?? 45);
                $is_done = !is_null($exam['last_score']);
                $total_q = ($exam['total_questions_count'] > 0) ? $exam['total_questions_count'] : ($exam['total_questions'] ?? 0);
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="exam-card-v2 p-4">
                        
                        <!-- Header Card: Icon + Badge Trạng Thái + Dropdown Quản lý -->
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="exam-icon-wrapper">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>

                            <div class="d-flex align-items-center gap-1">
                                <?php if ($is_done): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1.5 small fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i> Đã làm
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill px-2.5 py-1.5 small fw-semibold" style="background-color: #f3e8ff; color: #7c3aed; border: 1px solid #e9d5ff;">
                                        Sẵn sàng
                                    </span>
                                <?php endif; ?>

                                <!-- Thao tác Quản lý dành cho Quyền cho phép -->
                                <?php if ($can_manage_exams): ?>
                                    <div class="dropdown ms-1">
                                        <button class="btn btn-sm btn-light rounded-circle p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px;">
                                            <i class="bi bi-three-dots-vertical text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item text-warning fw-semibold" href="edit_exam.php?id=<?= $exam['id'] ?>">
                                                    <i class="bi bi-pencil me-2"></i> Chỉnh sửa
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger fw-semibold" href="delete_exam.php?id=<?= $exam['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa đề thi này không?');">
                                                    <i class="bi bi-trash me-2"></i> Xóa đề thi
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Body Card: Tên Môn + Têu đề đề thi -->
                        <div class="mb-3">
                            <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-semibold mb-2 d-inline-block">
                                <i class="bi bi-book me-1"></i><?= e($exam['subject_name'] ?? 'Môn học chung') ?>
                            </span>
                            <h6 class="fw-bold text-dark mb-0 line-clamp-2" title="<?= e($exam['title']) ?>">
                                <?= e($exam['title'] ?? 'Đề thi trắc nghiệm') ?>
                            </h6>
                        </div>

                        <!-- Info Bar: Thời gian & Số câu -->
                        <div class="border-top border-bottom py-2 my-2 bg-light rounded-3 px-3">
                            <div class="row text-muted small g-2">
                                <div class="col-6 d-flex align-items-center">
                                    <i class="bi bi-clock me-1.5 text-primary"></i>
                                    <span><strong><?= $duration ?></strong> phút</span>
                                </div>
                                <div class="col-6 d-flex align-items-center">
                                    <i class="bi bi-question-circle me-1.5 text-primary"></i>
                                    <span><strong><?= $total_q ?></strong> câu hỏi</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Card: Điểm gần nhất & Nút hành động -->
                        <div class="mt-auto pt-2">
                            <?php if ($is_done): 
                                $scoreVal = (float)$exam['last_score'];
                                $scoreClass = ($scoreVal >= 8.0) ? 'text-success' : (($scoreVal >= 5.0) ? 'text-primary' : 'text-danger');
                            ?>
                                <div class="d-flex align-items-center justify-content-between bg-light p-2 px-3 rounded-3 mb-2 border border-light">
                                    <span class="small text-muted fw-semibold">Điểm gần nhất:</span>
                                    <span class="fw-bold fs-6 <?= $scoreClass ?>"><?= number_format($scoreVal, 1) ?> / 10</span>
                                </div>
                                <a href="exam.php?id=<?= $exam['id'] ?>" class="btn btn-outline-purple w-100 rounded-3 fw-bold py-2 shadow-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i> Làm lại bài thi
                                </a>
                            <?php else: ?>
                                <a href="exam.php?id=<?= $exam['id'] ?>" class="btn btn-purple w-100 rounded-3 fw-bold py-2 shadow-sm">
                                    <i class="bi bi-pencil-square me-1"></i> Bắt đầu làm bài
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 3. PHÂN TRANG (PAGINATION) -->
        <?php if ($total_pages > 1): ?>
            <nav class="d-flex justify-content-center mt-5">
                <ul class="pagination pagination-v2">
                    <!-- Nút Trang trước -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?subject_id=<?= $subject_id ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <!-- Các trang số -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?subject_id=<?= $subject_id ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Nút Trang sau -->
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?subject_id=<?= $subject_id ?>&page=<?= $page + 1 ?>" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>