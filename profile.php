<?php
// profile.php - QuizTech
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
if (!is_array($user) || empty($user['id'])) {
    setFlash('danger', 'Không xác định được tài khoản đăng nhập.');
    redirect('login.php');
    exit;
}

$userId = (int) $user['id'];
$error = '';
$nameInput = (string) ($user['name'] ?? '');
$emailInput = (string) ($user['email'] ?? '');
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    verify_csrf();

    $nameInput = trim((string) ($_POST['name'] ?? ''));
    $emailInput = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($nameInput === '' || $emailInput === '') {
        $error = 'Vui lòng nhập đầy đủ họ tên và email.';
    } elseif (mb_strlen($nameInput, 'UTF-8') > 150) {
        $error = 'Họ tên không được vượt quá 150 ký tự.';
    } elseif (strlen($emailInput) > 254 || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($password !== '' && strlen($password) > 72) {
        $error = 'Mật khẩu mới không được vượt quá 72 byte.';
    } else {
        try {
            $pdo->beginTransaction();

            // Kiểm tra email đã được tài khoản khác sử dụng hay chưa.
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $stmt->execute([$emailInput, $userId]);

            if ($stmt->fetchColumn()) {
                $pdo->rollBack();
                $error = 'Email đã được sử dụng bởi người dùng khác.';
            } else {
                if ($password !== '') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($passwordHash === false) {
                        throw new RuntimeException('Không thể mã hóa mật khẩu.');
                    }
                    $stmt = $pdo->prepare(
                        'UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?'
                    );
                    $stmt->execute([$nameInput, $emailInput, $passwordHash, $userId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                    $stmt->execute([$nameInput, $emailInput, $userId]);
                }

                $pdo->commit();
                $_SESSION['name'] = $nameInput;
                $_SESSION['email'] = $emailInput;
                setFlash('success', 'Cập nhật hồ sơ thành công!');
                redirect('profile.php');
                exit;
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[profile.php] ' . $exception->getMessage());
            $error = ($exception->getCode() === '23000')
                ? 'Email đã được sử dụng bởi người dùng khác.'
                : 'Không thể cập nhật hồ sơ. Vui lòng thử lại.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[profile.php] ' . $exception->getMessage());
            $error = 'Không thể cập nhật hồ sơ. Vui lòng thử lại.';
        }
    }
}

$page_title = 'Hồ sơ cá nhân';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-4">
                    <div class="display-1" aria-hidden="true">👤</div>
                    <h5 class="fw-bold mt-2 mb-1"><?= e($nameInput) ?></h5>
                    <p class="text-muted text-break mb-3"><?= e($emailInput) ?></p>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <?= e(role_label($user['role'] ?? 'student')) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 px-4 pt-4">
                    <h5 class="fw-bold mb-1">Cập nhật thông tin</h5>
                    <p class="text-muted small mb-0">Chỉnh sửa họ tên, email hoặc đổi mật khẩu của bạn.</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="profile.php" autocomplete="on">
                        <?php csrf_field(); ?>
                        <div class="mb-3">
                            <label for="profileName" class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                            <input id="profileName" type="text" name="name" class="form-control"
                                   maxlength="150" value="<?= e($nameInput) ?>" autocomplete="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="profileEmail" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input id="profileEmail" type="email" name="email" class="form-control"
                                   maxlength="254" value="<?= e($emailInput) ?>" autocomplete="email" required>
                        </div>
                        <div class="mb-4">
                            <label for="profilePassword" class="form-label fw-semibold">Mật khẩu mới</label>
                            <input id="profilePassword" type="password" name="password" class="form-control"
                                   minlength="6" autocomplete="new-password" placeholder="Để trống nếu không đổi mật khẩu">
                            <div class="form-text">Nếu thay đổi, mật khẩu phải có ít nhất 6 ký tự.</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Lưu thay đổi
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4">Quay lại</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
