<?php
// student/submit_feedback.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireRole('student');

$user_id = $_SESSION['user']['id'];
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : null;

// Lấy thông tin bài thi nếu có truyền exam_id từ trang thi/luyện tập
$exam_info = null;
if ($exam_id) {
    $exam_info = db_fetch_one("SELECT title FROM exams WHERE id = ?", [$exam_id]);
}

// Tự động kiểm tra và tạo bảng practice_feedback nếu chưa có
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `practice_feedback` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'feedback',
        `exam_id` INT NULL,
        `question_id` INT NULL,
        `title` VARCHAR(255) NULL,
        `content` TEXT NOT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `admin_note` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    // Bỏ qua nếu đã tồn tại
}

// Xử lý gửi Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type    = $_POST['type'] ?? 'feedback';
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $ex_id   = !empty($_POST['exam_id']) ? (int)$_POST['exam_id'] : null;

    if (empty($content)) {
        setFlash('danger', 'Vui lòng nhập nội dung chi tiết góp ý hoặc đề xuất!');
    } elseif (empty($title)) {
        setFlash('danger', 'Vui lòng nhập tiêu đề hoặc tên môn học đề xuất!');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO practice_feedback (user_id, type, exam_id, title, content, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$user_id, $type, $ex_id, $title, $content]);
            
            setFlash('success', 'Cảm ơn bạn! Yêu cầu đã được gửi thành công đến Ban quản trị.');
            header('Location: submit_feedback.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Đã xảy ra lỗi khi gửi yêu cầu: ' . $e->getMessage());
        }
    }
}

// Lấy danh sách lịch sử góp ý của sinh viên
$my_feedbacks = db_fetch_all("
    SELECT f.*, e.title as exam_title 
    FROM practice_feedback f
    LEFT JOIN exams e ON f.exam_id = e.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
", [$user_id]);

$page_title = 'Gửi Góp ý & Đề xuất';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<style>
    :root {
        --primary-color: #4f46e5;
        --border-color: #e2e8f0;
        --bg-body: #f8fafc;
        --card-radius: 20px;
    }
    body { 
        background-color: var(--bg-body); 
    }
    .feedback-card { 
        background: #ffffff; 
        border-radius: var(--card-radius); 
        border: 1px solid var(--border-color); 
    }
    .badge-status { 
        padding: 0.35rem 0.75rem; 
        border-radius: 20px; 
        font-size: 0.75rem; 
        font-weight: 600; 
    }
    .badge-pending { 
        background-color: #fffbeb; 
        color: #d97706; 
        border: 1px solid #fef3c7; 
    }
    .badge-approved { 
        background-color: #f0fdf4; 
        color: #16a34a; 
        border: 1px solid #dcfce7; 
    }
    .badge-rejected { 
        background-color: #fef2f2; 
        color: #dc2626; 
        border: 1px solid #fee2e2; 
    }
    .style-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .style-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .style-scroll::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }
</style>

<div class="container py-4 py-md-5">
    <div class="row justify-content-center g-4">
        
        <!-- Form gửi yêu cầu -->
        <div class="col-lg-6">
            <div class="feedback-card p-4 p-md-5 shadow-sm h-100">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-chat-heart-fill fs-3"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Gửi Góp Ý & Đề Xuất</h4>
                    <p class="text-muted small mb-0">Đề xuất mở môn học mới hoặc đóng góp ý kiến về bài thi</p>
                </div>

                <!-- Thông báo Flash -->
                <?php if (function_exists('getFlash') && $flash = getFlash()): ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show border-0 rounded-3 small mb-4" role="alert">
                        <?= $flash['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?php if ($exam_id): ?>
                        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">LOẠI YÊU CẦU <span class="text-danger">*</span></label>
                        <select name="type" id="requestType" class="form-select rounded-3" onchange="updateFormLabels()">
                            <option value="subject_request" <?= !$exam_id ? 'selected' : '' ?>>📚 Đề xuất mở thêm môn học mới</option>
                            <option value="feedback" <?= $exam_id ? 'selected' : '' ?>>💬 Góp ý về bài thi / câu hỏi</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted" id="titleLabel">TÊN MÔN HỌC ĐỀ XUẤT <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="titleInput" class="form-control rounded-3" 
                               value="<?= $exam_info ? e($exam_info['title']) : '' ?>" 
                               placeholder="VD: Lập trình Python nâng cao" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">NỘI DUNG CHI TIẾT <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control rounded-3" rows="4" placeholder="Mô tả chi tiết góp ý hoặc lý do mong muốn mở môn học này..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold py-2.5 shadow-sm">
                        <i class="bi bi-send me-1"></i> Gửi Yêu Cầu
                    </button>
                    
                    <a href="dashboard.php" class="btn btn-link w-100 text-decoration-none text-muted small mt-2">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại trang chủ
                    </a>
                </form>
            </div>
        </div>

        <!-- Danh sách lịch sử đã gửi -->
        <div class="col-lg-6">
            <div class="feedback-card p-4 shadow-sm h-100">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i> Lịch Sử Gửi Yêu Cầu
                </h5>

                <?php if (empty($my_feedbacks)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                        <p class="small mb-0">Bạn chưa gửi đề xuất hoặc góp ý nào.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 style-scroll" style="max-height: 520px; overflow-y: auto; padding-right: 4px;">
                        <?php foreach ($my_feedbacks as $item): ?>
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-white text-dark border fw-semibold" style="font-size: 0.75rem;">
                                        <?= $item['type'] === 'subject_request' ? '📚 Đề xuất môn' : ($item['type'] === 'question_report' ? '🚩 Báo lỗi câu hỏi' : '💬 Góp ý bài thi') ?>
                                    </span>
                                    
                                    <?php if ($item['status'] === 'approved'): ?>
                                        <span class="badge badge-status badge-approved"><i class="bi bi-check-circle me-1"></i>Đã tiếp thu</span>
                                    <?php elseif ($item['status'] === 'rejected'): ?>
                                        <span class="badge badge-status badge-rejected"><i class="bi bi-x-circle me-1"></i>Từ chối</span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-pending"><i class="bi bi-hourglass-split me-1"></i>Chờ duyệt</span>
                                    <?php endif; ?>
                                </div>

                                <h6 class="fw-bold text-dark mb-1"><?= e($item['title'] ?: 'Không có tiêu đề') ?></h6>
                                
                                <?php if ($item['exam_title']): ?>
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-journal-bookmark me-1"></i>Bài thi: <?= e($item['exam_title']) ?>
                                    </div>
                                <?php endif; ?>

                                <p class="small text-secondary mb-2"><?= nl2br(e($item['content'])) ?></p>

                                <?php if (!empty($item['admin_note'])): ?>
                                    <div class="bg-white p-2 rounded border-start border-3 border-primary small text-dark mt-2 shadow-sm">
                                        <strong class="text-primary"><i class="bi bi-person-badge me-1"></i>Phản hồi Admin:</strong> 
                                        <span><?= e($item['admin_note']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="text-end mt-2">
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function updateFormLabels() {
    const type = document.getElementById('requestType').value;
    const titleLabel = document.getElementById('titleLabel');
    const titleInput = document.getElementById('titleInput');

    if (type === 'subject_request') {
        titleLabel.innerHTML = 'TÊN MÔN HỌC ĐỀ XUẤT <span class="text-danger">*</span>';
        titleInput.placeholder = 'VD: Lập trình Python nâng cao';
    } else {
        titleLabel.innerHTML = 'TIÊU ĐỀ / TÊN BÀI THI <span class="text-danger">*</span>';
        titleInput.placeholder = 'VD: Góp ý câu 5 bài thi Giữa kỳ';
    }
}

document.addEventListener('DOMContentLoaded', updateFormLabels);
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>