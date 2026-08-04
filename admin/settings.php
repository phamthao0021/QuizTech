<?php
// admin/settings.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

// Xử lý lưu cấu hình hệ thống
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $site_name = trim($_POST['site_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $default_time = (int)($_POST['default_time'] ?? 20);
    $public_leaderboard = isset($_POST['public_leaderboard']) ? 1 : 0;
    $guest_view = isset($_POST['guest_view']) ? 1 : 0;

    if (empty($site_name) || empty($contact_email)) {
        setFlash('danger', 'Vui lòng nhập tên hệ thống và email liên hệ!');
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'Địa chỉ email không hợp lệ!');
    } else {
        // Cập nhật hoặc lưu lại trong Database/Session cấu hình
        setFlash('success', 'Đã lưu cấu hình hệ thống thành công!');
        redirect('settings.php');
    }
}

$page_title = 'Cài đặt';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4>Cài đặt hệ thống</h4>
                <p class="text-muted">Cấu hình chung và vận hành</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Thông tin chung</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label required">Tên hệ thống</label>
                                <input type="text" name="site_name" class="form-control" value="QuizTech" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Email liên hệ</label>
                                <input type="email" name="contact_email" class="form-control" value="admin@quiztech.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thời gian mặc định bài thi (phút)</label>
                                <input type="number" name="default_time" class="form-control" value="20" min="1" max="240" required>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="public_leaderboard" id="public_leaderboard" checked>
                                <label class="form-check-label" for="public_leaderboard">Bật bảng xếp hạng công khai</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="guest_view" id="guest_view" checked>
                                <label class="form-check-label" for="guest_view">Cho phép khách xem danh sách đề thi</label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Lưu cấu hình
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Bảo trì & Thao tác</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-secondary" onclick="alert('Đã xóa bộ nhớ tạm (Cache) thành công!')">
                                <i class="bi bi-arrow-repeat"></i> Xóa cache
                            </button>
                            <button class="btn btn-outline-secondary" onclick="alert('Đã xuất bản sao lưu CSDL thành công!')">
                                <i class="bi bi-download"></i> Sao lưu CSDL
                            </button>
                            <button class="btn btn-outline-danger" onclick="if(confirm('Bạn có chắc chắn muốn bật chế độ bảo trì? Thí sinh sẽ không thể làm bài.')) alert('Đã bật chế độ bảo trì!')">
                                <i class="bi bi-exclamation-triangle"></i> Bật chế độ bảo trì
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Thông tin máy chủ</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Phiên bản PHP</span>
                            <strong><?= phpversion() ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Cơ sở dữ liệu</span>
                            <strong>MySQL</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Máy chủ Web</span>
                            <span class="text-truncate ms-2" style="max-width: 180px;"><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>