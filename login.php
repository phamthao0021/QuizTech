<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$activeTab = $_GET['tab'] ?? 'login';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } elseif (login($email, $password)) {
        if ($remember) {
            setcookie('remember_email', $email, time() + (86400 * 30), '/');
        }
        setFlash('success', 'Đăng nhập thành công!');
        redirect(homeForRole($_SESSION['role']));
    } else {
        $error = 'Email hoặc mật khẩu không đúng.';
    }
}

$remember_email = $_COOKIE['remember_email'] ?? '';
$page_title = 'Đăng nhập';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .auth-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        
        /* LEFT PANEL */
        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #6d28d9 100%);
            color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        
        .auth-left::before {
            content: ''; position: absolute; top: -50%; right: -30%;
            width: 600px; height: 600px; background: rgba(255,255,255,0.05); border-radius: 50%;
        }
        
        .auth-left::after {
            content: ''; position: absolute; bottom: -40%; left: -20%;
            width: 500px; height: 500px; background: rgba(255,255,255,0.03); border-radius: 50%;
        }
        
        .auth-left .logo { display: flex; align-items: center; gap: 12px; position: relative; z-index: 1; }
        .auth-left .logo .logo-icon {
            width: 48px; height: 48px; background: rgba(255,255,255,0.15);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; backdrop-filter: blur(4px);
        }
        .auth-left .logo h2 { font-weight: 700; font-size: 1.5rem; margin: 0; }
        .auth-left .hero { position: relative; z-index: 1; }
        .auth-left .hero h1 { font-size: 2.8rem; font-weight: 800; line-height: 1.2; margin-bottom: 16px; }
        .auth-left .hero h1 span { display: block; color: #c4b5fd; }
        .auth-left .hero p { font-size: 1.05rem; opacity: 0.85; max-width: 420px; line-height: 1.7; }
        
        .auth-left .stats { display: flex; gap: 24px; margin-top: 32px; position: relative; z-index: 1; }
        .auth-left .stats .stat-item {
            background: rgba(255,255,255,0.12); backdrop-filter: blur(4px);
            padding: 16px 24px; border-radius: 12px; text-align: center; min-width: 100px;
        }
        .auth-left .stats .stat-item .number { font-size: 1.8rem; font-weight: 700; }
        .auth-left .stats .stat-item .label { font-size: 0.8rem; opacity: 0.8; }
        .auth-left .footer-text { font-size: 0.85rem; opacity: 0.6; position: relative; z-index: 1; }
        
        /* RIGHT PANEL */
        .auth-right {
            flex: 1; background: white; display: flex;
            align-items: center; justify-content: center; padding: 40px;
        }
        .auth-right .auth-card { max-width: 420px; width: 100%; }
        .auth-right .auth-card .card-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .auth-right .auth-card .card-subtitle { color: #64748b; font-size: 0.9rem; margin-bottom: 24px; }
        
        /* Tabs */
        .auth-tabs { display: flex; background: #f1f5f9; border-radius: 50px; padding: 4px; margin-bottom: 24px; }
        .auth-tabs .tab-btn {
            flex: 1; padding: 10px 16px; border: none; border-radius: 50px;
            font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s;
            background: transparent; color: #64748b;
        }
        .auth-tabs .tab-btn.active {
            background: #4f46e5; color: white; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        
        /* Form elements */
        .form-label { font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 4px; }
        .form-control { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 10px 14px; font-size: 0.95rem; }
        .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .btn-primary { background: #4f46e5; border: none; border-radius: 10px; padding: 12px; font-weight: 600; }
        .btn-primary:hover { background: #4338ca; }
        .form-options { display: flex; justify-content: space-between; align-items: center; margin: 16px 0 24px; }
        .form-options a { font-size: 0.85rem; color: #4f46e5; text-decoration: none; }
        .alert { border-radius: 10px; border: none; }
        
        @media (max-width: 992px) {
            .auth-container { flex-direction: column; }
            .auth-left { padding: 40px 30px; min-height: 40vh; }
            .auth-right { padding: 30px 20px; min-height: 60vh; }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- LEFT PANEL -->
    <div class="auth-left">
        <div class="logo">
            <div class="logo-icon"><i class="bi bi-code-square"></i></div>
            <h2>QuizTech</h2>
        </div>
        
        <div class="hero">
            <h1>Thi trắc nghiệm<br><span>kiến thức CNTT</span></h1>
            <p>Hệ thống thi trực tuyến với ngân hàng câu hỏi đa dạng: Lập trình C, PHP, JavaScript, SQL, Kiểm thử phần mềm.</p>
            
            <div class="stats">
                <div class="stat-item"><div class="number">5+</div><div class="label">Môn học</div></div>
                <div class="stat-item"><div class="number">25+</div><div class="label">Câu hỏi</div></div>
                <div class="stat-item"><div class="number">∞</div><div class="label">Lượt thi</div></div>
            </div>
        </div>
        
        <div class="footer-text">© 2026 QuizTech • Học tập không giới hạn</div>
    </div>
    
    <!-- RIGHT PANEL -->
    <div class="auth-right">
        <div class="auth-card">
            <h3 class="card-title">Chào mừng trở lại 👋</h3>
            <p class="card-subtitle">Đăng nhập để bắt đầu thi</p>
            
            <!-- Tabs -->
            <div class="auth-tabs">
                <button class="tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>" id="tabLoginBtn">Đăng nhập</button>
                <button class="tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" id="tabRegisterBtn">Đăng ký</button>
            </div>

            <!-- Flash & Error Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-3"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?= $flash['type'] ?> mb-3"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            
            <!-- Tab Content: Login -->
            <div id="loginForm" style="<?= $activeTab === 'login' ? 'display:block' : 'display:none' ?>">
                <form method="POST" action="login.php">
                    <?php csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="admin@quiztech.vn" value="<?= e($remember_email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    
                    <div class="form-options">
                        <div class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember" <?= $remember_email ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remember">Ghi nhớ tôi</label>
                        </div>
                        <a href="forgot-password.php">Quên mật khẩu?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Chưa có tài khoản? 
                        <a href="#" id="switchToRegister" style="color: #4f46e5; font-weight: 600; text-decoration: none;">Đăng ký ngay</a>
                    </small>
                </div>
            </div>
            
            <!-- Tab Content: Register -->
            <div id="registerForm" style="<?= $activeTab === 'register' ? 'display:block' : 'display:none' ?>">
                <form method="POST" action="register.php">
                    <?php csrf_field(); ?>
                    <div class="mb-2">
                        <label class="form-label">Họ tên</label>
                        <input type="text" name="name" class="form-control" placeholder="Nguyễn Văn A" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="student@tdc.edu.vn" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="Ít nhất 6 ký tự" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" name="confirm" class="form-control" placeholder="Nhập lại mật khẩu" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus"></i> Đăng ký
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Đã có tài khoản? 
                        <a href="#" id="switchToLogin" style="color: #4f46e5; font-weight: 600; text-decoration: none;">Đăng nhập</a>
                    </small>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const loginBtn = document.getElementById("tabLoginBtn");
const registerBtn = document.getElementById("tabRegisterBtn");
const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");

function showLogin() {
    loginBtn.classList.add("active");
    registerBtn.classList.remove("active");
    loginForm.style.display = "block";
    registerForm.style.display = "none";
}

function showRegister() {
    registerBtn.classList.add("active");
    loginBtn.classList.remove("active");
    registerForm.style.display = "block";
    loginForm.style.display = "none";
}

loginBtn.addEventListener("click", (e) => { e.preventDefault(); showLogin(); });
registerBtn.addEventListener("click", (e) => { e.preventDefault(); showRegister(); });
document.getElementById("switchToRegister").addEventListener("click", (e) => { e.preventDefault(); showRegister(); });
document.getElementById("switchToLogin").addEventListener("click", (e) => { e.preventDefault(); showLogin(); });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>