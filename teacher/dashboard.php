<?php
// teacher/dashboard.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';

// Kiểm tra quyền chuẩn (chấp nhận cả teacher, giang_vien, admin)
requireTeacher(); 

// Thống kê số lượng
$total_subjects  = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$total_exams     = (int)$pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
$total_questions = (int)$pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$total_users     = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Đề thi mới nhất
$recent_exams = $pdo->query("
    SELECT e.*, s.name as subject_name 
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id 
    ORDER BY e.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard';
include '../includes/header.php';
?>

<div class="page-wrapper d-flex">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1 p-4 bg-light">
        
        <!-- Header Xin Chào -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h3 class="mb-1 fw-bold text-dark">Dashboard</h3>
                <p class="text-muted mb-0">Xin chào, <strong><?= e($_SESSION['user']['name'] ?? 'Teacher') ?></strong> 👋</p>
            </div>
        </div>

        <!-- 4 Thẻ Thống Kê (Top Cards) -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 text-center py-3">
                    <div class="card-body">
                        <h2 class="display-6 fw-bold text-primary mb-1"><?= $total_subjects ?></h2>
                        <span class="text-muted fw-semibold">Môn học</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 text-center py-3">
                    <div class="card-body">
                        <h2 class="display-6 fw-bold text-success mb-1"><?= $total_exams ?></h2>
                        <span class="text-muted fw-semibold">Đề thi</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 text-center py-3">
                    <div class="card-body">
                        <h2 class="display-6 fw-bold text-warning mb-1"><?= $total_questions ?></h2>
                        <span class="text-muted fw-semibold">Câu hỏi</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 text-center py-3">
                    <div class="card-body">
                        <h2 class="display-6 fw-bold text-info mb-1"><?= $total_users ?></h2>
                        <span class="text-muted fw-semibold">Người dùng</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Khối Nội Dung Chính -->
        <div class="row g-4">
            <!-- Bảng đề thi gần đây -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Đề thi gần đây</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Tiêu đề</th>
                                        <th>Môn</th>
                                        <th class="text-end pe-3">Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_exams)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Chưa có đề thi nào.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_exams as $exam): ?>
                                            <tr>
                                                <td class="ps-3 fw-semibold text-dark"><?= e($exam['title']) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border px-2 py-1">
                                                        <?= e($exam['subject_name'] ?? 'Khác') ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-3 text-muted small">
                                                    <?= date('d/m/Y', strtotime($exam['created_at'] ?? 'now')) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khối Hành động nhanh bên phải -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Hành động nhanh</h5>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <a href="questions.php" class="btn btn-outline-primary text-start p-3 fw-semibold d-flex align-items-center">
                            <i class="bi bi-question-square fs-4 me-3"></i> Quản lý câu hỏi
                        </a>
                        <a href="exams.php" class="btn btn-outline-success text-start p-3 fw-semibold d-flex align-items-center">
                            <i class="bi bi-file-earmark-text fs-4 me-3"></i> Quản lý đề thi
                        </a>
                        <a href="rooms.php" class="btn btn-outline-warning text-dark text-start p-3 fw-semibold d-flex align-items-center">
                            <i class="bi bi-door-open fs-4 me-3"></i> Quản lý phòng thi
                        </a>
                        <a href="ai.php" class="btn btn-outline-info text-start p-3 fw-semibold d-flex align-items-center">
                            <i class="bi bi-robot fs-4 me-3"></i> AI Generator
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>