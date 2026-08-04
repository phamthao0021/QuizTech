<?php
// forgot-password.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = 'Quên mật khẩu';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Vui lòng nhập email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        // Kiểm tra email tồn tại
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Trong thực tế, gửi email reset password
            // Demo: chỉ hiển thị thông báo
            $message = 'Link reset mật khẩu đã được gửi đến email của bạn.';
        } else {
            $error = 'Email không tồn tại trong hệ thống.';
        }
    }
}

include 'includes/header_guest.php';
?>
<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="auth-logo">
                            <i class="bi bi-code-square"></i>
                            <span>QuizTech</span>
                        </div>
                        <h2>Quên mật khẩu 🔑</h2>
                        <p class="text-muted">Nhập email để nhận link reset mật khẩu</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= e($message) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <?php csrf_field(); ?>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="Nhập email của bạn" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-auth">
                            <i class="bi bi-send"></i> Gửi link reset
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p><a href="login.php">Quay lại đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer_guest.php'; ?>