<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$reset = $_SESSION['password_reset_dev'] ?? null;
if (!$reset) { setFlash('danger','Phiên khôi phục mật khẩu không tồn tại.'); redirect('forgot-password.php'); }

$error='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['verify_otp'])) {
    if (function_exists('verify_csrf')) verify_csrf();
    $otp=preg_replace('/\D/','',$_POST['otp'] ?? '');
    if (time() > ($reset['expires_at'] ?? 0)) {
        unset($_SESSION['password_reset_dev']);
        setFlash('danger','Mã OTP đã hết hạn. Vui lòng tạo mã mới.');
        redirect('forgot-password.php');
    } elseif (($reset['attempts'] ?? 0) >= 5) {
        unset($_SESSION['password_reset_dev']);
        setFlash('danger','Bạn đã nhập sai OTP quá số lần cho phép.');
        redirect('forgot-password.php');
    } elseif (!preg_match('/^\d{6}$/',$otp)) {
        $error='OTP phải gồm đúng 6 chữ số.';
    } elseif (!password_verify($otp,$reset['otp_hash'])) {
        $_SESSION['password_reset_dev']['attempts']=($reset['attempts'] ?? 0)+1;
        $error='Mã OTP không chính xác.';
    } else {
        $_SESSION['password_reset_dev']['verified']=true;
        $_SESSION['password_reset_dev']['verified_at']=time();
        redirect('reset-password.php');
    }
}
$remaining=max(0,($reset['expires_at']??time())-time());
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Xác minh OTP - QuizTech</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{min-height:100vh;background:#f6f7fb;display:grid;place-items:center;font-family:Inter,system-ui}.box{width:min(92%,480px);background:#fff;padding:34px;border-radius:24px;box-shadow:0 18px 50px #1f293712}.otp{font-size:26px;letter-spacing:10px;text-align:center;height:58px;border-radius:13px}.otp.bad{border-color:#ef4444}.otp.good{border-color:#22c55e}.msg{font-size:13px}.btn-main{width:100%;height:50px;border:0;border-radius:12px;color:#fff;font-weight:700;background:linear-gradient(135deg,#5b21b6,#7c3aed)}</style></head>
<body><div class="box"><h3 class="fw-bold">Xác minh OTP</h3><p class="text-muted">Nhập mã 6 số vừa được tạo. Mã còn hiệu lực <strong id="timer"></strong>.</p>
<?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" id="otpForm"><?php if(function_exists('csrf_field'))csrf_field();?>
<input class="form-control otp" id="otp" name="otp" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>
<div id="otpMsg" class="msg mt-2"></div><button class="btn-main mt-4" name="verify_otp" value="1">Xác minh mã</button></form>
<a class="d-block text-center mt-3" href="forgot-password.php">Tạo mã mới</a></div>
<script>
let remain=<?=$remaining?>;const timer=document.getElementById('timer');function tick(){const m=Math.floor(remain/60),s=remain%60;timer.textContent=m+':'+String(s).padStart(2,'0');if(remain>0){remain--;setTimeout(tick,1000)}}tick();
const o=document.getElementById('otp'),m=document.getElementById('otpMsg');function check(){o.value=o.value.replace(/\D/g,'').slice(0,6);const ok=/^\d{6}$/.test(o.value);o.classList.toggle('good',ok);o.classList.toggle('bad',o.value.length>0&&!ok);m.style.color=ok?'#16a34a':'#dc2626';m.textContent=o.value?(ok?'✓ OTP đủ 6 số':'OTP phải đủ 6 chữ số.') : '';return ok}o.addEventListener('input',check);document.getElementById('otpForm').addEventListener('submit',e=>{if(!check())e.preventDefault()});
</script></body></html>