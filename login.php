<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Phiên đăng nhập không hợp lệ.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (auth_login($email, $password)) {
            flash_set('success', 'Đăng nhập thành công. Chào mừng bạn!');
            redirect('dashboard.php');
        } else {
            $error = 'Email/MSSV hoặc mật khẩu không đúng.';
        }
    }
}

$page_title = 'Đăng nhập';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="fas fa-brain fa-3x text-primary"></i>
                <h3 class="mt-2"><?= APP_NAME ?></h3>
                <p class="text-muted">Đăng nhập để bắt đầu thi trắc nghiệm</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Email hoặc MSSV</label>
                    <input type="text" name="email" class="form-control" value="<?= e($email) ?>" required autofocus placeholder="example@mail.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </button>
            </form>

            <p class="text-center mt-3">
                Chưa có tài khoản? <a href="<?= base_url('register.php') ?>">Đăng ký ngay</a>
            </p>
            <p class="text-center text-muted small">
                <strong>Demo:</strong><br>
                admin@quiztech.com / Admin@123<br>
                teacher@quiztech.com / Teacher@123<br>
                student1@example.com / Student@123
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>