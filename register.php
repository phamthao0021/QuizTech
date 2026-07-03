<?php
// register.php
require_once 'auth.php';
require_once 'helpers.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $studentCode = $_POST['student_code'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif (empty($fullName)) {
        $error = 'Vui lòng nhập họ tên.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        $pdo = db();
        
        // Kiểm tra email đã tồn tại
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email đã được đăng ký.';
        } else {
            // Kiểm tra student_code đã tồn tại
            if (!empty($studentCode)) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE student_code = ?');
                $stmt->execute([$studentCode]);
                if ($stmt->fetch()) {
                    $error = 'MSSV đã được đăng ký.';
                }
            }
            
            if (empty($error)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                
                // SỬA: Dùng đúng tên cột trong database
                // Nếu database có cột 'full_name' thì dùng:
                $stmt = $pdo->prepare('INSERT INTO users (full_name, student_code, email, password_hash, role) VALUES (?, ?, ?, ?, "student")');
                $stmt->execute([$fullName, $studentCode, $email, $hash]);
                
                // HOẶC nếu database có cột 'fullname' thì dùng:
                // $stmt = $pdo->prepare('INSERT INTO users (fullname, student_code, email, password_hash, role) VALUES (?, ?, ?, ?, "student")');
                // $stmt->execute([$fullName, $studentCode, $email, $hash]);
                
                flash('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
                redirect('login.php');
            }
        }
    }
}

$page_title = 'Đăng ký';
ob_start();
?>
<div class="row justify-content-center" style="min-height: 70vh; align-items: center;">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-2">Đăng ký tài khoản</h4>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Họ tên</label>
                        <input type="text" name="full_name" class="form-control" value="<?= e($_POST['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MSSV</label>
                        <input type="text" name="student_code" class="form-control" value="<?= e($_POST['student_code'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu (ít nhất 6 ký tự)</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Đăng ký</button>
                </form>
                
                <p class="text-center mt-3">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
?>