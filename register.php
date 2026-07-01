<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$student_code = '';
$fullname = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors[] = 'Phiên đăng ký không hợp lệ.';
    } else {
        $student_code = trim($_POST['student_code'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($password !== $confirm) {
            $errors[] = 'Mật khẩu xác nhận không khớp.';
        }

        if (empty($errors)) {
            $errors = auth_register($student_code, $fullname, $email, $password);
            if (empty($errors)) {
                flash_set('success', 'Đăng ký thành công! Chào mừng bạn đến với ' . APP_NAME);
                redirect('dashboard.php');
            }
        }
    }
}

$page_title = 'Đăng ký';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="fas fa-brain fa-3x text-primary"></i>
                <h3 class="mt-2">Đăng ký tài khoản</h3>
            </div>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">MSSV <span class="text-danger">*</span></label>
                    <input type="text" name="student_code" class="form-control" value="<?= e($student_code) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" name="fullname" class="form-control" value="<?= e($fullname) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required placeholder="example@tdc.edu.vn">
                    <small class="text-muted">Vui lòng sử dụng email trường (@tdc.edu.vn)</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                    <small class="text-muted">Tối thiểu 8 ký tự</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-user-plus"></i> Đăng ký
                </button>
            </form>

            <p class="text-center mt-3">
                Đã có tài khoản? <a href="<?= base_url('login.php') ?>">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>