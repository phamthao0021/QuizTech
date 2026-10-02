<?php
// admin/settings.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireSuperAdmin();

// ----------------------------------------------------
// TỰ ĐỘNG KHỞI TẠO BẢNG settings NẾU CHƯA CÓ
// ----------------------------------------------------
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    // Bỏ qua nếu đã tồn tại
}

// ----------------------------------------------------
// NẠP CẤU HÌNH TỪ DATABASE
// ----------------------------------------------------
$db_settings = [];
try {
    $rows = db_fetch_all("SELECT * FROM settings");
    foreach ($rows as $r) {
        $db_settings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Exception $e) {
    // Bỏ qua nếu chưa có dữ liệu
}

// Giá trị mặc định nếu chưa lưu trong CSDL
$site_name          = $db_settings['site_name'] ?? 'QuizTech';
$contact_email      = $db_settings['contact_email'] ?? 'admin@quiztech.com';
$default_time       = (int)($db_settings['default_time'] ?? 20);
$public_leaderboard = (int)($db_settings['public_leaderboard'] ?? 1);
$guest_view         = (int)($db_settings['guest_view'] ?? 1);
$maintenance_mode   = (int)($db_settings['maintenance_mode'] ?? 0);

// ----------------------------------------------------
// XỬ LÝ LƯU CẤU HÌNH TỰ ĐỘNG VÀO DATABASE
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verify_csrf')) {
        verify_csrf();
    }

    $site_name          = trim($_POST['site_name'] ?? '');
    $contact_email      = trim($_POST['contact_email'] ?? '');
    $default_time       = (int)($_POST['default_time'] ?? 20);
    $public_leaderboard = isset($_POST['public_leaderboard']) ? 1 : 0;
    $guest_view         = isset($_POST['guest_view']) ? 1 : 0;
    $maintenance_mode   = isset($_POST['maintenance_mode']) ? 1 : 0;

    if (empty($site_name) || empty($contact_email)) {
        setFlash('danger', 'Vui lòng nhập đầy đủ tên hệ thống và email liên hệ!');
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'Địa chỉ email liên hệ không hợp lệ!');
    } else {
        try {
            $dataToSave = [
                'site_name'          => $site_name,
                'contact_email'      => $contact_email,
                'default_time'       => (string)$default_time,
                'public_leaderboard' => (string)$public_leaderboard,
                'guest_view'         => (string)$guest_view,
                'maintenance_mode'   => (string)$maintenance_mode,
            ];

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                                   VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            foreach ($dataToSave as $key => $val) {
                $stmt->execute([$key, $val]);
            }

            setFlash('success', 'Đã cập nhật và lưu cấu hình hệ thống thành công!');
        } catch (Exception $e) {
            setFlash('danger', 'Lỗi khi lưu cấu hình: ' . $e->getMessage());
        }

        header('Location: settings.php');
        exit;
    }
}

$page_title = 'Cài đặt hệ thống';
include '../includes/header_admin.php';
?>

<style>
    :root {
        --purple-main: #6366f1;
        --purple-dark: #4f46e5;
        --purple-deep: #3730a3;
        --purple-glow: rgba(99, 102, 241, 0.25);
        --text-dark: #0f172a;
        --text-muted: #64748b;
    }

    body {
        background-color: #f8fafc;
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        color: var(--text-dark);
    }

    .hero-banner-admin {
        background: linear-gradient(135deg, var(--purple-deep) 0%, var(--purple-dark) 50%, var(--purple-main) 100%);
        border-radius: 24px;
        padding: 2.25rem 2rem;
        color: #ffffff;
        box-shadow: 0 20px 30px -10px var(--purple-glow);
    }

    .creative-card {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
    }

    .form-control {
        border-radius: 12px;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--purple-main);
        box-shadow: 0 0 0 4px var(--purple-glow);
    }

    .form-label {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.875rem;
        margin-bottom: 0.4rem;
    }

    .setting-toggle-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        transition: all 0.2s ease;
    }

    .setting-toggle-card:hover {
        background-color: #f1f5f9;
    }

    .btn-purple {
        background: linear-gradient(135deg, var(--purple-main), var(--purple-dark));
        color: #ffffff;
        border: none;
        border-radius: 30px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        box-shadow: 0 8px 20px var(--purple-glow);
        transition: all 0.25s ease;
    }

    .btn-purple:hover {
        background: linear-gradient(135deg, var(--purple-dark), var(--purple-deep));
        color: #ffffff;
        transform: translateY(-2px);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-row:last-child {
        border-bottom: none;
    }
</style>

<div class="container-fluid py-4 px-3 px-md-4">
    <!-- BANNER TIÊU ĐỀ -->
    <div class="hero-banner-admin admin-page-head mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold mb-1 fs-3 text-white">Cài Đặt Hệ Thống</h3>
            <p class="mb-0 text-white-50 small">Quản lý các tham số vận hành chung của hệ thống thi trắc nghiệm</p>
        </div>
        <div>
            <?php if ($maintenance_mode): ?>
                <span class="badge bg-danger px-3 py-2 rounded-pill fs-6"><i class="bi bi-exclamation-triangle-fill me-1"></i> Đang Bảo Trì</span>
            <?php else: ?>
                <span class="badge bg-success px-3 py-2 rounded-pill fs-6"><i class="bi bi-check-circle-fill me-1"></i> Hoạt Động Bình Thường</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    <?php if (function_exists('getFlash') && $flash = getFlash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php if (function_exists('csrf_field')) csrf_field(); ?>
        
        <div class="row g-4">
            <!-- CẤU HÌNH THÔNG TIN CHUNG -->
            <div class="col-lg-8">
                <div class="creative-card p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="p-2.5 rounded-3 bg-primary-subtle text-primary">
                            <i class="bi bi-sliders fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Thông Tin & Quy Định Khởi Tạo</h5>
                            <p class="text-muted small mb-0">Thiết lập các thông số cơ bản áp dụng toàn hệ thống</p>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên hệ thống <span class="text-danger">*</span></label>
                            <input type="text" name="site_name" class="form-control" value="<?= e($site_name) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email liên hệ hệ thống <span class="text-danger">*</span></label>
                            <input type="email" name="contact_email" class="form-control" value="<?= e($contact_email) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Thời gian làm bài mặc định (Phút) <span class="text-danger">*</span></label>
                        <input type="number" name="default_time" class="form-control" value="<?= $default_time ?>" min="1" max="300" required>
                        <small class="text-muted mt-1 d-block" style="font-size: 0.775rem;">Gợi ý mặc định khi tạo mới một đề thi</small>
                    </div>

                    <h6 class="fw-bold text-dark mb-3">Tùy Chọn Chức Năng & Quyền Hạn</h6>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="setting-toggle-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold text-dark">Bảng xếp hạng công khai</div>
                                <div class="small text-muted">Cho phép thí sinh xem bảng tổng hợp điểm số các thành viên khác</div>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input ms-0" type="checkbox" name="public_leaderboard" id="public_leaderboard" <?= $public_leaderboard ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <div class="setting-toggle-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold text-dark">Khách chưa đăng nhập xem đề thi</div>
                                <div class="small text-muted">Cho phép người dùng chưa đăng nhập xem danh sách đề thi (phải đăng nhập mới làm được bài)</div>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input ms-0" type="checkbox" name="guest_view" id="guest_view" <?= $guest_view ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <div class="setting-toggle-card border-danger-subtle bg-danger-subtle bg-opacity-10 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Bật chế độ bảo trì hệ thống</div>
                                <div class="small text-muted">Tạm thời khóa tính năng làm bài thi của thí sinh để nâng cấp hệ thống</div>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input ms-0" type="checkbox" name="maintenance_mode" id="maintenance_mode" <?= $maintenance_mode ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-end">
                        <button type="submit" class="btn btn-purple">
                            <i class="bi bi-save me-1"></i> Lưu Tất Cả Cài Đặt
                        </button>
                    </div>
                </div>
            </div>

            <!-- THÔNG TIN MÁY CHỦ -->
            <div class="col-lg-4">
                <div class="creative-card p-4">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                        <div class="p-2.5 rounded-3 bg-info-subtle text-info">
                            <i class="bi bi-cpu fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Thông Số Máy Chủ</h5>
                            <p class="text-muted small mb-0">Môi trường thực thi PHP & CSDL</p>
                        </div>
                    </div>

                    <div class="info-row">
                        <span class="text-muted small fw-semibold">Phiên bản PHP</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace fw-bold px-2.5"><?= phpversion() ?></span>
                    </div>

                    <div class="info-row">
                        <span class="text-muted small fw-semibold">Cơ sở dữ liệu</span>
                        <span class="badge bg-light text-dark border rounded-pill px-2.5 fw-bold">MySQL</span>
                    </div>

                    <div class="info-row">
                        <span class="text-muted small fw-semibold">Bộ nhớ cho phép</span>
                        <span class="fw-bold text-dark small"><?= ini_get('memory_limit') ?: 'N/A' ?></span>
                    </div>

                    <div class="info-row">
                        <span class="text-muted small fw-semibold">File upload tối đa</span>
                        <span class="fw-bold text-dark small"><?= ini_get('upload_max_filesize') ?: 'N/A' ?></span>
                    </div>

                    <div class="info-row">
                        <span class="text-muted small fw-semibold">Web Server</span>
                        <span class="text-truncate text-secondary small fw-semibold ms-2" style="max-width: 140px;" title="<?= e($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?>">
                            <?= e($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>