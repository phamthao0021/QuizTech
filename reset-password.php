<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$reset=$_SESSION['password_reset_dev'] ?? null;
if (!$reset || empty($reset['verified']) || time()>($reset['expires_at']??0)) {
    unset($_SESSION['password_reset_dev']);
    setFlash('danger','Phiên đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
    redirect('forgot-password.php');
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['reset_password'])){
    if(function_exists('verify_csrf'))verify_csrf();
    $password=$_POST['password']??'';$confirm=$_POST['confirm_password']??'';
    $valid=(bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s])\S{8,20}$/',$password);
    if(!$valid)$error='Mật khẩu phải 8–20 ký tự, có chữ hoa, chữ thường, số, ký tự đặc biệt và không khoảng trắng.';
    elseif($password!==$confirm)$error='Mật khẩu xác nhận không trùng khớp.';
    else{
        $hash=password_hash($password,PASSWORD_DEFAULT);
        $stmt=$pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if($stmt->execute([$hash,(int)$reset['user_id']])){
            unset($_SESSION['password_reset_dev']);
            setFlash('success','Đặt lại mật khẩu thành công. Bạn có thể đăng nhập bằng mật khẩu mới.');
            redirect('login.php');
        } else $error='Không thể cập nhật mật khẩu. Vui lòng thử lại.';
    }
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mật khẩu mới - QuizTech</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{min-height:100vh;background:#f6f7fb;display:grid;place-items:center;font-family:Inter,system-ui}.box{width:min(92%,520px);background:#fff;padding:34px;border-radius:24px;box-shadow:0 18px 50px #1f293712}.form-control{height:50px;border-radius:12px}.good{border-color:#22c55e!important}.bad{border-color:#ef4444!important}.live{font-size:12px;margin-top:6px}.btn-main{width:100%;height:50px;border:0;border-radius:12px;color:#fff;font-weight:700;background:linear-gradient(135deg,#5b21b6,#7c3aed)}</style></head>
<body><div class="box"><h3 class="fw-bold">Tạo mật khẩu mới</h3><p class="text-muted">Ví dụ hợp lệ: <strong>Admin@123</strong></p>
<?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" id="resetForm"><?php if(function_exists('csrf_field'))csrf_field();?>
<label class="form-label fw-semibold">Mật khẩu mới</label><input type="password" class="form-control" id="pw" name="password" autocomplete="new-password" required>
<div id="pwMsg" class="live"></div><label class="form-label fw-semibold mt-3">Xác nhận mật khẩu</label><input type="password" class="form-control" id="cf" name="confirm_password" autocomplete="new-password" required><div id="cfMsg" class="live"></div>
<button class="btn-main mt-4" name="reset_password" value="1">Đặt lại mật khẩu</button></form></div>
<script>
const p=document.getElementById('pw'),c=document.getElementById('cf'),pm=document.getElementById('pwMsg'),cm=document.getElementById('cfMsg');
const valid=v=>v.length>=8&&v.length<=20&&/[A-Z]/.test(v)&&/[a-z]/.test(v)&&/[0-9]/.test(v)&&/[^A-Za-z0-9\s]/.test(v)&&!/\s/.test(v);
function paint(el,msg,ok,text){el.classList.toggle('good',el.value&&ok);el.classList.toggle('bad',el.value&&!ok);msg.style.color=ok?'#16a34a':'#dc2626';msg.textContent=el.value?(ok?'✓ Hợp lệ':text):''}
function check(){const a=valid(p.value),b=a&&c.value===p.value;paint(p,pm,a,'8–20 ký tự, gồm chữ hoa, chữ thường, số, ký tự đặc biệt và không khoảng trắng.');paint(c,cm,b,c.value!==p.value?'Mật khẩu xác nhận không trùng khớp.':'Mật khẩu chưa hợp lệ.');return a&&b}p.addEventListener('input',check);c.addEventListener('input',check);document.getElementById('resetForm').addEventListener('submit',e=>{if(!check())e.preventDefault()});
</script></body></html>