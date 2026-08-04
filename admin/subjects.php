<?php
// subjects.php - Danh sách Môn học (Guest)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$page_title = 'Môn học';
$subjects = getSubjects() ?? [];

include 'includes/header_guest.php';
?>

<style>
.hero-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #fff;
    border-radius: 24px;
    padding: 3rem 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.subject-card {
    border: none;
    border-radius: 20px;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.subject-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12) !important;
}

.subject-icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: linear-gradient(135deg, #e0e7ff 0%, #e0f2fe 100%);
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 1.25rem;
}

.subject-card .btn-custom {
    border-radius: 12px;
    font-weight: 600;
    padding: 0.6rem 1.2rem;
    transition: all 0.2s ease;
}

.subject-card:hover .btn-custom {
    background-color: #4f46e5;
    color: #ffffff !important;
    border-color: #4f46e5;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="hero-header mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-white text-dark px-3 py-2 rounded-pill fw-bold mb-2">Danh mục</span>
            <h1 class="fw-bold mb-2 display-6">Danh sách Môn học</h1>
            <p class="text-white-50 mb-0 fs-6">Chọn môn học bất kỳ để khám phá các đề thi trắc nghiệm phong phú.</p>
        </div>

        <?php if (function_exists('isTeacher') && isTeacher()): ?>
            <div>
                <a href="subject_create.php" class="btn btn-light btn-lg rounded-pill shadow-sm fw-bold text-primary px-4">
                    <i class="bi bi-plus-circle-fill me-2"></i>Thêm môn học
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <?php if (empty($subjects)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body py-4">
                <i class="bi bi-journal-album display-1 text-muted"></i>
                <h4 class="fw-bold mt-4">Chưa có môn học nào</h4>
                <p class="text-muted">Dữ liệu môn học hiện tại đang trống. Vui lòng quay lại sau!</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($subjects as $s): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a href="exams.php?subject=<?= (int)($s['id'] ?? 0) ?>" class="text-decoration-none">
                        <div class="card h-100 subject-card p-3">
                            <div class="card-body text-center d-flex flex-column align-items-center">
                                <!-- Icon -->
                                <div class="subject-icon-wrapper">
                                    <?php 
                                        $icon = $s['icon'] ?? '📚';
                                        // Nếu icon là emoji, hiển thị trực tiếp; nếu là Bootstrap icon name, thêm 'bi bi-'
                                        if (strpos($icon, 'bi-') !== false || strpos($icon, 'fas ') !== false) {
                                            echo '<i class="' . $icon . '"></i>';
                                        } else {
                                            echo $icon;
                                        }
                                    ?>
                                </div>

                                <!-- Subject Name -->
                                <h5 class="fw-bold text-dark mb-2 line-clamp-2" style="min-height: 48px;">
                                    <?= e($s['name'] ?? 'Môn học') ?>
                                </h5>

                                <!-- Description -->
                                <p class="text-muted small flex-grow-1 mb-4 text-break" style="min-height: 42px;">
                                    <?= e($s['description'] ?? 'Chưa có mô tả cho môn học này.') ?>
                                </p>

                                <!-- Action Button -->
                                <span class="btn btn-outline-primary btn-custom w-100 mt-auto">
                                    Xem đề thi <i class="bi bi-arrow-right ms-1"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer_guest.php'; ?>