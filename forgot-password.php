<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (function_exists('isLoggedIn') && isLoggedIn()) {
    $role = $_SESSION['role'] ?? $_SESSION['user']['role'] ?? 'student';
    redirect(function_exists('homeForRole') ? homeForRole($role) : 'index.php');
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$error = '';
$success = '';
$devOtp = '';
$emailValue = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    if (function_exists('verify_csrf')) verify_csrf();

    if (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vui lòng nhập đúng định dạng email.';
    } else {
        $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ? AND (deleted_at IS NULL) LIMIT 1");
        $stmt->execute([$emailValue]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Thông báo chung để tránh dò tài khoản.
        $success = 'Nếu email tồn tại trong hệ thống, mã xác nhận đã được tạo.';

        if ($user) {
            $otp = (string)random_int(100000, 999999);
            $_SESSION['password_reset_dev'] = [
                'user_id' => (int)$user['id'],
                'email' => $user['email'],
                'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
                'expires_at' => time() + 300,
                'attempts' => 0,
                'verified' => false,
                'last_sent_at' => time()
            ];
            $devOtp = $otp; // DEV ONLY: hiển thị OTP vì chưa có SMTP thật.
        }
    }
}

$page_title = 'Quên mật khẩu';
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($page_title) ?> - QuizTech</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box} body{margin:0;min-height:100vh;background:#f6f7fb;color:#172033;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
.auth-shell{min-height:100vh;display:grid;grid-template-columns:minmax(320px,.9fr) minmax(420px,1.1fr)}
.brand-side{padding:56px;display:flex;flex-direction:column;justify-content:center;color:#fff;background:linear-gradient(145deg,#312e81,#5b21b6 55%,#7c3aed)}
.brand-mark{width:56px;height:56px;border-radius:17px;display:grid;place-items:center;background:rgba(255,255,255,.15);font-size:25px;margin-bottom:24px}
.brand-side h1{font-size:42px;font-weight:800;letter-spacing:-1.5px}.brand-side p{max-width:520px;color:rgba(255,255,255,.78);font-size:16px;line-height:1.7}
.step-list{margin-top:30px;display:grid;gap:14px}.step{display:flex;gap:12px;align-items:center;color:rgba(255,255,255,.9)}.step i{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.14);display:grid;place-items:center}
.form-side{display:flex;align-items:center;justify-content:center;padding:40px 24px}.auth-card{width:min(100%,520px);background:#fff;border:1px solid #e8eaf1;border-radius:24px;padding:34px;box-shadow:0 18px 50px rgba(31,41,55,.08)}
.icon-head{width:58px;height:58px;border-radius:18px;background:#ede9fe;color:#6d28d9;display:grid;place-items:center;font-size:25px;margin-bottom:20px}
.auth-card h2{font-weight:800;letter-spacing:-.5px}.muted{color:#718096}.form-label{font-weight:700;font-size:14px}
.form-control{height:50px;border-radius:12px;border-color:#dfe3eb;padding-left:14px}.form-control:focus{border-color:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,.1)}
.form-control.live-bad{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.08)!important}.form-control.live-good{border-color:#22c55e!important;box-shadow:0 0 0 3px rgba(34,197,94,.08)!important}
.live-msg{font-size:12px;margin-top:6px}.live-msg.bad{color:#dc2626}.live-msg.good{color:#16a34a}
.btn-main{height:50px;border:0;border-radius:12px;background:linear-gradient(135deg,#5b21b6,#7c3aed);color:#fff;font-weight:750;width:100%}
.dev-box{border:1px dashed #8b5cf6;background:#f5f3ff;border-radius:14px;padding:16px}.otp-code{font-size:28px;font-weight:850;letter-spacing:8px;color:#5b21b6}
.back-link{color:#6d28d9;text-decoration:none;font-weight:700}.security-note{font-size:12px;color:#7b8496;margin-top:14px}
@media(max-width:850px){.auth-shell{grid-template-columns:1fr}.brand-side{display:none}.form-side{padding:22px 14px}.auth-card{padding:25px 20px;border-radius:20px}}
</style>
</head>
<body>
<div class="auth-shell">
<section class="brand-side">
  <div class="brand-mark"><i class="bi bi-shield-lock"></i></div>
  <h1>Khôi phục tài khoản</h1>
  <p>QuizTech sử dụng quy trình xác minh trước khi cho phép đặt mật khẩu mới. Bản localhost hiện chạy ở chế độ kiểm thử OTP.</p>
  <div class="step-list">
    <div class="step"><i class="bi bi-envelope"></i><span>1. Nhập email đã đăng ký</span></div>
    <div class="step"><i class="bi bi-123"></i><span>2. Xác minh OTP 6 số</span></div>
    <div class="step"><i class="bi bi-key"></i><span>3. Tạo mật khẩu mới an toàn</span></div>
  </div>
</section>
<main class="form-side">
<div class="auth-card">
  <div class="icon-head"><i class="bi bi-envelope-check"></i></div>
  <h2 class="mb-2">Quên mật khẩu?</h2>
  <p class="muted mb-4">Nhập email đang gắn với tài khoản QuizTech.</p>

  <?php if ($error): ?><div class="alert alert-danger rounded-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success rounded-3"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form method="post" id="forgotForm" novalidate>
    <?php if (function_exists('csrf_field')) csrf_field(); ?>
    <label class="form-label" for="forgotEmail">Email tài khoản</label>
    <div class="input-group">
      <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="bi bi-envelope"></i></span>
      <input type="email" class="form-control border-start-0" id="forgotEmail" name="email"
             value="<?= htmlspecialchars($emailValue) ?>" placeholder="name@example.com" autocomplete="email" required>
    </div>
    <div id="emailLive" class="live-msg"></div>
    <button class="btn-main mt-4" type="submit" name="send_otp" value="1"><i class="bi bi-send me-2"></i>Tạo mã xác nhận</button>
  </form>

  <?php if ($devOtp): ?>
  <div class="dev-box mt-4">
    <div class="small fw-bold text-uppercase mb-1">Chế độ thử nghiệm localhost</div>
    <div class="small text-muted mb-2">Chưa cấu hình SMTP nên OTP chưa được gửi ra email. Dùng mã sau để kiểm thử:</div>
    <div class="otp-code"><?= htmlspecialchars($devOtp) ?></div>
    <div class="small text-muted mt-2">Mã hết hạn sau 5 phút.</div>
    <a class="btn btn-outline-primary rounded-3 mt-3" href="verify-reset-otp.php"><i class="bi bi-arrow-right me-1"></i>Nhập OTP</a>
  </div>
  <?php endif; ?>

  <div class="security-note"><i class="bi bi-shield-check me-1"></i>Hệ thống dùng thông báo chung để hạn chế dò tìm tài khoản bằng email.</div>
  <div class="text-center mt-4"><a class="back-link" href="login.php"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a></div>
</div>
</main>
</div>
<script>
const email=document.getElementById('forgotEmail'), msg=document.getElementById('emailLive'), form=document.getElementById('forgotForm');
const validEmail=v=>/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(v.trim());
function validateEmail(){
 const v=email.value.trim(),ok=validEmail(v);
 email.classList.remove('live-bad','live-good');msg.className='live-msg';
 if(!v){msg.textContent='';return false}
 email.classList.add(ok?'live-good':'live-bad');msg.classList.add(ok?'good':'bad');
 msg.innerHTML=ok?'<i class="bi bi-check-circle-fill me-1"></i>Email hợp lệ':'<i class="bi bi-exclamation-circle-fill me-1"></i>Email chưa đúng định dạng';
 return ok;
}
email.addEventListener('input',validateEmail);form.addEventListener('submit',e=>{if(!validateEmail())e.preventDefault()});
</script>
</body></html>
