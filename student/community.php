<?php
// student/community.php
$page_title = "Cộng đồng đề thi & Tài liệu";
require_once __DIR__ . '/../includes/header_student.php';

$user_id = $_SESSION['user_id'] ?? 0;

// 1. Lấy danh sách Đề thi CỘNG ĐỒNG (Đã được duyệt)
$sql_community = "SELECT e.*, s.name as subject_name, u.name as author_name,
                  (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as total_questions,
                  (SELECT file_path FROM exam_files ef WHERE ef.exam_id = e.id LIMIT 1) as attach_file
                  FROM exams e
                  LEFT JOIN subjects s ON e.subject_id = s.id
                  LEFT JOIN users u ON e.created_by = u.id
                  WHERE e.status = 'approved' AND e.is_public = 1
                  ORDER BY e.id DESC";
$community_exams = db_fetch_all($sql_community);

// 2. Lấy danh sách Đề thi DO CHÍNH SINH VIÊN NÀY TẠO (Đang chờ duyệt hoặc đã duyệt)
$sql_my_exams = "SELECT e.*, s.name as subject_name,
                 (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as total_questions
                 FROM exams e
                 LEFT JOIN subjects s ON e.subject_id = s.id
                 WHERE e.created_by = ?
                 ORDER BY e.id DESC";
$my_exams = db_fetch_all($sql_my_exams, [$user_id]);
?>

<div class="container-fluid px-4">
    <!-- Header Banner -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm border">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Góc Cộng Đồng & Chia Sẻ</h4>
            <p class="text-muted small mb-0">Cùng đóng góp đề thi, ôn luyện kiến thức và tải tài liệu miễn phí.</p>
        </div>
        <div>
            <a href="create_exam.php" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Tạo đề thi mới (AI / Thủ công)
            </a>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs nav-tabs-bordered mb-4" id="communityTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-semibold" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-exams">
                <i class="bi bi-globe me-1"></i> Đề thi từ Cộng đồng (<?= count($community_exams) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold" id="my-tab" data-bs-toggle="tab" data-bs-target="#my-exams">
                <i class="bi bi-person-workspace me-1"></i> Đề thi tôi đã đóng góp (<?= count($my_exams) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="communityTabContent">
        
        <!-- TAB 1: ĐỀ THI CỘNG ĐỒNG -->
        <div class="tab-pane fade show active" id="all-exams">
            <?php if (empty($community_exams)): ?>
                <div class="text-center py-5 bg-white rounded shadow-sm">
                    <i class="bi bi-journal-x fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Chưa có đề thi nào được chia sẻ trên cộng đồng.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($community_exams as $exam): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary-subtle text-primary fw-bold">
                                            <?= e($exam['subject_name'] ?? 'Môn tự do') ?>
                                        </span>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= $exam['duration_minutes'] ?> phút</small>
                                    </div>

                                    <h5 class="card-title fw-bold text-dark text-truncate"><?= e($exam['title']) ?></h5>
                                    <p class="card-text text-muted small me-2">
                                        <i class="bi bi-person-circle me-1"></i>Đăng bởi: <strong><?= e($exam['author_name'] ?? 'Ẩn danh') ?></strong>
                                    </p>
                                    
                                    <div class="d-flex align-items-center gap-3 text-muted small my-3">
                                        <span><i class="bi bi-question-circle text-info me-1"></i><?= $exam['total_questions'] ?> câu hỏi</span>
                                    </div>

                                    <div class="d-flex gap-2 mt-auto pt-2 border-top">
                                        <!-- Làm bài ngay -->
                                        <a href="take_exam.php?id=<?= $exam['id'] ?>" class="btn btn-sm btn-success flex-grow-1">
                                            <i class="bi bi-play-circle me-1"></i> Vào làm bài
                                        </a>

                                        <!-- Tải File đề thi nếu người đăng có đính kèm PDF/DOCX -->
                                        <?php if (!empty($exam['attach_file'])): ?>
                                            <a href="../<?= e($exam['attach_file']) ?>" download class="btn btn-sm btn-outline-secondary" title="Tải file đề thi gốc">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: ĐỀ THI DO CHÍNH TÔI ĐÓNG GÓP -->
        <div class="tab-pane fade" id="my-exams">
            <div class="table-responsive bg-white rounded shadow-sm p-3">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tên đề thi</th>
                            <th>Môn học</th>
                            <th>Số câu</th>
                            <th>Trạng thái kiểm duyệt</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_exams)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Bạn chưa đóng góp đề thi nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_exams as $me): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($me['title']) ?></td>
                                    <td><?= e($me['subject_name'] ?? 'Môn tự do') ?></td>
                                    <td><?= $me['total_questions'] ?> câu</td>
                                    <td>
                                        <?php if ($me['status'] === 'approved'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Đã duyệt (Công khai)</span>
                                        <?php elseif ($me['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Từ chối</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Chờ Admin duyệt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Sinh viên tạo xong CÓ THỂ VÀO LÀM NGAY bài của chính mình mà không cần chờ duyệt -->
                                        <a href="take_exam.php?id=<?= $me['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil-square me-1"></i> Thử nghiệm / Làm bài
                                        </a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>