<?php
// profile.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();

$user = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($name && $email) {
        // ============================================
        // KIỂM TRA EMAIL TRÙNG (trừ chính mình)
        // ============================================
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $error = 'Email đã được sử dụng bởi người dùng khác.';
        } else {
            // Cập nhật thông tin
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $_SESSION['user_id']]);
            
            if (!empty($password) && strlen($password) >= 6) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $_SESSION['user_id']]);
            }
            
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            
            setFlash('success', 'Cập nhật thành công!');
            redirect('profile.php');
        }
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    }
}

$page_title = 'Hồ sơ';
include 'includes/header.php';
?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="display-1">👤</div>
                    <h5><?= e($user['name']) ?></h5>
                    <p class="text-muted"><?= e($user['email']) ?></p>
                    <span class="badge bg-primary"><?= role_label($user['role']) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Cập nhật thông tin</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <?php csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Họ tên</label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi">
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>