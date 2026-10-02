<?php
// login.php - Login + Register UI
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$activeTab = (($_GET['tab'] ?? 'login') === 'register') ? 'register' : 'login';
$error = '';
$field_errors = [];
$old = [
    'login_email' => trim($_POST['email'] ?? ($_COOKIE['remember_email'] ?? '')),
    'name' => trim($_POST['name'] ?? ''),
    'register_email' => trim($_POST['email'] ?? ''),
];

function qt_normalize_name($value)
{
    $value = trim((string)$value);
    return preg_replace('/\s+/u', ' ', $value);
}
function qt_valid_email($email)
{
    return filter_var((string)$email, FILTER_VALIDATE_EMAIL) !== false;
}
function qt_password_errors($password)
{
    $errors = [];
    $len = strlen((string)$password);
    if ($len < 8 || $len > 20) $errors[] = 'Mật khẩu phải từ 8–20 ký tự.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Cần ít nhất 1 chữ hoa.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Cần ít nhất 1 chữ thường.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Cần ít nhất 1 chữ số.';
    if (!preg_match('/[^A-Za-z0-9\s]/', $password)) $errors[] = 'Cần ít nhất 1 ký tự đặc biệt.';
    if (preg_match('/\s/', $password)) $errors[] = 'Mật khẩu không được chứa khoảng trắng.';
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form_type = $_POST['form_type'] ?? 'login';

    if ($form_type === 'register') {
        $activeTab = 'register';
        $name = qt_normalize_name($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $old['name'] = $name;
        $old['register_email'] = $email;

        $nameLen = mb_strlen($name, 'UTF-8');
        if ($name === '') $field_errors['name'] = 'Vui lòng nhập họ và tên.';
        elseif ($nameLen < 3 || $nameLen > 100) $field_errors['name'] = 'Họ và tên phải từ 3–100 ký tự.';
        elseif (!preg_match('/^[\p{L}]+(?:\s+[\p{L}]+)*$/u', $name)) $field_errors['name'] = 'Họ và tên chỉ gồm chữ và khoảng trắng giữa các từ.';

        if ($email === '') $field_errors['register_email'] = 'Vui lòng nhập địa chỉ email.';
        elseif (!qt_valid_email($email)) $field_errors['register_email'] = 'Địa chỉ email không đúng định dạng.';

        $passErrors = qt_password_errors($password);
        if ($passErrors) $field_errors['register_password'] = implode(' ', $passErrors);
        if ($confirm === '') $field_errors['confirm'] = 'Vui lòng nhập lại mật khẩu.';
        elseif ($password !== $confirm) $field_errors['confirm'] = 'Mật khẩu xác nhận không trùng khớp.';

        if (!$field_errors) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $field_errors['register_email'] = 'Email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.';
            } else {
                // Dùng hàm register của dự án để giữ nguyên schema/quyền mặc định.
                $result = register($name, $email, $password, 'student');
                if ($result === true) {
                    setFlash('success', 'Đăng ký thành công! Bạn có thể đăng nhập ngay.');
                    redirect('login.php?tab=login');
                    exit;
                }
                $error = is_string($result) ? $result : 'Không thể tạo tài khoản. Vui lòng thử lại.';
            }
        }
    } else {
        $activeTab = 'login';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);
        $old['login_email'] = $email;

        if ($email === '') $field_errors['login_email'] = 'Vui lòng nhập địa chỉ email.';
        elseif (!qt_valid_email($email)) $field_errors['login_email'] = 'Địa chỉ email không đúng định dạng.';

        $passErrors = qt_password_errors($password);
        if ($password === '') $field_errors['login_password'] = 'Vui lòng nhập mật khẩu.';
        elseif ($passErrors) $field_errors['login_password'] = implode(' ', $passErrors);

        if (!$field_errors) {
            $loginResult = login($email, $password);
            if ($loginResult === true) {
                if ($remember) {
                    setcookie('remember_email', $email, [
                        'expires' => time() + 86400 * 30,
                        'path' => '/',
                        'secure' => !empty($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                } else {
                    setcookie('remember_email', '', time() - 3600, '/');
                }
                setFlash('success', 'Đăng nhập thành công!');
                redirect(homeForRole($_SESSION['role'] ?? 'student'));
                exit;
            } elseif ($loginResult === 'locked') {
                $error = 'Tài khoản đã bị khóa hoặc vô hiệu hóa. Vui lòng liên hệ quản trị viên.';
            } else {
                $error = 'Email hoặc mật khẩu không đúng.';
            }
        }
    }
}
$remember_email = $old['login_email'];
$page_title = 'Đăng nhập & Đăng ký';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập & Đăng ký - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background: #0f172a;
        }

        .auth-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow: hidden;
        }

        /* ================= LEFT PANEL MODERNIZED ================= */
        .auth-left {
            flex: 1.25;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #311042 100%);
            color: white;
            padding: 50px 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Hiệu ứng đốm sáng Neon chìm */
        .glow-orb-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, rgba(0, 0, 0, 0) 70%);
            filter: blur(50px);
            pointer-events: none;
        }

        .glow-orb-2 {
            position: absolute;
            bottom: -15%;
            right: -10%;
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.3) 0%, rgba(0, 0, 0, 0) 70%);
            filter: blur(60px);
            pointer-events: none;
        }

        .glow-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 32px 32px;
            opacity: 0.3;
            pointer-events: none;
        }

        /* Logo QuizTech */
        .auth-left .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 2;
        }

        .auth-left .logo .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .auth-left .logo h2 {
            font-weight: 800;
            font-size: 1.5rem;
            margin: 0;
            letter-spacing: -0.5px;
            color: #fff;
        }

        .auth-left .logo .badge-version {
            font-size: 0.7rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 20px;
            color: #c7d2fe;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Hero Content */
        .auth-left .hero {
            position: relative;
            z-index: 2;
            margin: auto 0;
            padding: 40px 0;
        }

        .auth-left .hero .tagline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 30px;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .auth-left .hero h1 {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.18;
            margin-bottom: 20px;
            letter-spacing: -1px;
            color: #ffffff;
        }

        .auth-left .hero h1 .gradient-text {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-left .hero p {
            font-size: 1.05rem;
            color: #94a3b8;
            max-width: 480px;
            line-height: 1.6;
            margin-bottom: 36px;
        }

        /* Feature Cards Grid */
        .feature-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            position: relative;
            z-index: 2;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 18px 16px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(129, 140, 248, 0.4);
            transform: translateY(-3px);
        }

        .feature-card .icon-box {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 12px;
        }

        .feature-card:nth-child(1) .icon-box {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
        }

        .feature-card:nth-child(2) .icon-box {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
        }

        .feature-card:nth-child(3) .icon-box {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        .feature-card .title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 4px;
        }

        .feature-card .desc {
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.4;
            margin: 0;
        }

        .auth-left .footer-text {
            font-size: 0.85rem;
            color: #64748b;
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ================= RIGHT PANEL MODERNIZED ================= */
        .auth-right {
            flex: 0.95;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
            position: relative;
        }

        .auth-card {
            max-width: 420px;
            width: 100%;
        }

        /* Modern Tabs */
        .auth-tabs {
            display: flex;
            background: #f1f5f9;
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }

        .auth-tabs .tab-btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.25s ease;
            background: transparent;
            color: #64748b;
        }

        .auth-tabs .tab-btn.active {
            background: #ffffff;
            color: #4f46e5;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        /* Input Controls */
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-group-text {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
            border-radius: 12px 0 0 12px;
        }

        .form-control {
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            padding: 11px 16px;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .input-group .form-control {
            border-radius: 0 12px 12px 0;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }

        /* Buttons */
        .btn-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            transition: all 0.25s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-1px);
            color: white;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        /* Demo Accounts Box */
        .demo-accounts {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 14px;
            margin-top: 24px;
        }

        .demo-accounts .title {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .account-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .account-chip:last-child {
            margin-bottom: 0;
        }

        .account-chip:hover {
            border-color: #6366f1;
            background: #f5f3ff;
        }

        .account-chip .role-badge {
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .auth-container {
                flex-direction: column;
            }

            .auth-left {
                padding: 40px 24px;
            }

            .auth-right {
                padding: 40px 20px;
            }

            .auth-left .hero h1 {
                font-size: 2.2rem;
            }

            .feature-cards {
                grid-template-columns: 1fr;
            }
        }

        .field-error {
            color: #dc2626;
            font-size: .78rem;
            margin-top: 6px;
            display: flex;
            gap: 5px;
            align-items: flex-start;
        }

        .input-group.has-error .input-group-text,
        .input-group.has-error .form-control {
            border-color: #ef4444 !important;
        }

        .input-group.has-error .input-group-text {
            color: #dc2626;
            background: #fff1f2;
        }

        .input-group.is-valid-field .input-group-text,
        .input-group.is-valid-field .form-control {
            border-color: #22c55e !important;
        }

        .password-rules {
            margin-top: 9px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
        }

        .password-rules .rule {
            font-size: .74rem;
            color: #64748b;
            margin: 3px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .password-rules .rule.ok {
            color: #16a34a;
        }

        .password-rules .rule.bad {
            color: #dc2626;
        }

        .password-rules .rule i {
            font-size: .7rem;
        }

        .form-hint {
            color: #64748b;
            font-size: .74rem;
            margin-top: 6px;
        }

        .password-toggle {
            border-left: 0;
            cursor: pointer;
            color: #64748b;
        }


        .validation-msg {
            font-size: .76rem;
            margin-top: 6px;
            display: flex;
            gap: 6px;
            align-items: flex-start
        }

        .validation-msg.error {
            color: #dc2626
        }

        .validation-msg.success {
            color: #16a34a
        }

        .input-group.field-invalid .form-control,
        .input-group.field-invalid .input-group-text {
            border-color: #ef4444 !important
        }

        .input-group.field-valid .form-control,
        .input-group.field-valid .input-group-text {
            border-color: #22c55e !important
        }

        .input-group.field-invalid {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .08);
            border-radius: 10px
        }

        .input-group.field-valid {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .08);
            border-radius: 10px
        }
    </style>
</head>

<body>

    <div class="auth-container">
        <!-- LEFT PANEL -->
        <div class="auth-left">
            <div class="glow-orb-1"></div>
            <div class="glow-orb-2"></div>
            <div class="glow-grid"></div>

            <div class="logo">
                <div class="logo-icon"><img src="assets/images/Cardmoi_PLT_Trang.png" alt="" width="55" height="45"></div>
                <h2>QuizTech</h2>
                <span class="badge-version">v2.5</span>
            </div>

            <div class="hero">
                <div class="tagline">
                    <i class="bi bi-stars"></i> Nền tảng đánh giá năng lực CNTT
                </div>
                <h1>Chinh phục tri thức<br><span class="gradient-text">Lập trình & Công nghệ</span></h1>
                <p>Hệ thống ôn luyện trắc nghiệm thông minh, hỗ trợ thi thử thời gian thực, đánh giá năng lực chính xác dành riêng cho sinh viên CNTT.</p>

                <div class="feature-cards">
                    <div class="feature-card">
                        <div class="icon-box"><i class="bi bi-journal-code"></i></div>
                        <div class="title">Đa dạng đề thi</div>
                        <div class="desc">Kho câu hỏi Java, PHP, Python, SQL liên tục cập nhật.</div>
                    </div>
                    <div class="feature-card">
                        <div class="icon-box"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div class="title">Chấm tự động</div>
                        <div class="desc">Hiển thị kết quả & đáp án chi tiết ngay khi nộp bài.</div>
                    </div>
                    <div class="feature-card">
                        <div class="icon-box"><i class="bi bi-bar-chart-line-fill"></i></div>
                        <div class="title">Phân tích kết quả</div>
                        <div class="desc">Thống kê điểm số & tiến độ học tập trực quan.</div>
                    </div>
                </div>
            </div>

            <div class="footer-text">
                <span>© 2026 QuizTech Inc. All rights reserved.</span>
                <span><i class="bi bi-shield-check text-success me-1"></i> Bảo mật 256-bit</span>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="auth-right">
            <div class="auth-card">
                <div class="mb-4 text-center text-lg-start">
                    <h3 class="fw-bold text-dark mb-1" id="pageMainHeading">Chào mừng trở lại!</h3>
                    <p class="text-muted small" id="pageSubHeading">Vui lòng nhập thông tin để đăng nhập hệ thống</p>
                </div>

                <!-- Modern Tabs -->
                <div class="auth-tabs">
                    <button class="tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>" id="tabLoginBtn">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                    </button>
                    <button class="tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" id="tabRegisterBtn">
                        <i class="bi bi-person-plus me-1"></i> Đăng ký
                    </button>
                </div>

                <!-- Flash & Error Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-sm mb-3">
                        <i class="bi bi-exclamation-circle-fill me-2"></i><?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($flash = getFlash()): ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show border-0 rounded-3 shadow-sm mb-3">
                        <?= e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tab Content: LOGIN -->
                <div id="loginForm" style="<?= $activeTab === 'login' ? 'display:block' : 'display:none' ?>">
                    <form method="POST" action="login.php?tab=login" id="loginActualForm" novalidate>
                        <?php csrf_field(); ?>
                        <input type="hidden" name="form_type" value="login">

                        <div class="mb-3">
                            <label class="form-label" for="loginEmail">Địa chỉ Email</label>
                            <div class="input-group <?= isset($field_errors['login_email']) ? 'has-error' : '' ?>" id="loginEmailGroup">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="loginEmail" name="email" class="form-control"
                                    placeholder="name@example.com" value="<?= e($old['login_email']) ?>"
                                    maxlength="190" autocomplete="username" required>
                            </div>
                            <div class="form-hint">Nhập một địa chỉ email hợp lệ, ví dụ <strong>name@example.com</strong>.</div>
                            <?php if (isset($field_errors['login_email'])): ?><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($field_errors['login_email']) ?></span></div><?php endif; ?>
                            <div class="field-error d-none" id="loginEmailError"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="loginPassword">Mật khẩu</label>
                            <div class="input-group <?= isset($field_errors['login_password']) ? 'has-error' : '' ?>" id="loginPasswordGroup">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" id="loginPassword" name="password" class="form-control"
                                    placeholder="8–20 ký tự" minlength="8" maxlength="20" autocomplete="current-password" required>
                                <button class="input-group-text password-toggle" type="button" data-toggle-password="loginPassword"><i class="bi bi-eye"></i></button>
                            </div>
                            <?php if (isset($field_errors['login_password'])): ?><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($field_errors['login_password']) ?></span></div><?php endif; ?>
                            <div class="field-error d-none" id="loginPasswordError"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check"><input type="checkbox" name="remember" class="form-check-input" id="remember" <?= $remember_email ? 'checked' : '' ?>><label class="form-check-label small text-secondary" for="remember">Ghi nhớ đăng nhập</label></div>
                            <a href="forgot-password.php" class="small text-primary text-decoration-none fw-semibold">Quên mật khẩu?</a>
                        </div>
                        <button type="submit" class="btn btn-gradient w-100">Đăng nhập <i class="bi bi-arrow-right ms-1"></i></button>
                    </form>

                    
                </div>

                <!-- Tab Content: REGISTER -->
                <div id="registerForm" style="<?= $activeTab === 'register' ? 'display:block' : 'display:none' ?>">
                    <form method="POST" action="login.php?tab=register" id="registerActualForm" novalidate>
                        <?php csrf_field(); ?>
                        <input type="hidden" name="form_type" value="register">

                        <div class="mb-3">
                            <label class="form-label" for="registerName">Họ và tên</label>
                            <div class="input-group <?= isset($field_errors['name']) ? 'has-error' : '' ?>" id="registerNameGroup">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" id="registerName" name="name" class="form-control" placeholder="Nguyễn Văn An"
                                    value="<?= e($old['name']) ?>" minlength="3" maxlength="100" autocomplete="name" required>
                            </div>
                            <div class="form-hint">3–100 ký tự, chỉ gồm chữ và khoảng trắng giữa các từ.</div>
                            <?php if (isset($field_errors['name'])): ?><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($field_errors['name']) ?></span></div><?php endif; ?>
                            <div class="field-error d-none" id="registerNameError"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="registerEmail">Địa chỉ Email</label>
                            <div class="input-group <?= isset($field_errors['register_email']) ? 'has-error' : '' ?>" id="registerEmailGroup">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="registerEmail" name="email" class="form-control" placeholder="name@example.com"
                                    value="<?= e($old['register_email']) ?>" maxlength="190" autocomplete="email" required>
                            </div>
                            <div class="form-hint">Nhập một địa chỉ email hợp lệ, ví dụ <strong>name@example.com</strong>.</div>
                            <?php if (isset($field_errors['register_email'])): ?><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($field_errors['register_email']) ?></span></div><?php endif; ?>
                            <div class="field-error d-none" id="registerEmailError"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="registerPassword">Mật khẩu</label>
                            <div class="input-group <?= isset($field_errors['register_password']) ? 'has-error' : '' ?>" id="registerPasswordGroup">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" id="registerPassword" name="password" class="form-control" placeholder="8–20 ký tự"
                                    minlength="8" maxlength="20" autocomplete="new-password" required>
                                <button class="input-group-text password-toggle" type="button" data-toggle-password="registerPassword"><i class="bi bi-eye"></i></button>
                            </div>
                            <?php if (isset($field_errors['register_password'])): ?><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($field_errors['register_password']) ?></span></div><?php endif; ?>
                            <div class="password-rules" id="passwordRules">
                                <div class="rule" data-rule="length"><i class="bi bi-circle"></i>8–20 ký tự</div>
                                <div class="rule" data-rule="upper"><i class="bi bi-circle"></i>Ít nhất 1 chữ hoa (A–Z)</div>
                                <div class="rule" data-rule="lower"><i class="bi bi-circle"></i>Ít nhất 1 chữ thường (a–z)</div>
                                <div class="rule" data-rule="number"><i class="bi bi-circle"></i>Ít nhất 1 chữ số (0–9)</div>
                                <div class="rule" data-rule="special"><i class="bi bi-circle"></i>Ít nhất 1 ký tự đặc biệt (!@#$...)</div>
                                <div class="rule" data-rule="space"><i class="bi bi-circle"></i>Không chứa khoảng trắng</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="registerConfirm">Xác nhận mật khẩu</label>
                            <div class="input-group <?= isset($field_errors['confirm']) ? 'has-error' : '' ?>" id="registerConfirmGroup">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" id="registerConfirm" name="confirm" class="form-control" placeholder="Nhập lại mật khẩu"
                                    minlength="8" maxlength="20" autocomplete="new-password" required>
                                <button class="input-group-text password-toggle" type="button" data-toggle-password="registerConfirm"><i class="bi bi-eye"></i></button>
                            </div>
                            <?php if (isset($field_errors['confirm'])): ?><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($field_errors['confirm']) ?></span></div><?php endif; ?>
                            <div class="field-error d-none" id="registerConfirmError"></div>
                        </div>
                        <button type="submit" class="btn btn-gradient w-100">Tạo tài khoản ngay <i class="bi bi-check-circle ms-1"></i></button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        const loginBtn = document.getElementById("tabLoginBtn"),
            registerBtn = document.getElementById("tabRegisterBtn");
        const loginPane = document.getElementById("loginForm"),
            registerPane = document.getElementById("registerForm");
        const mainHeading = document.getElementById("pageMainHeading"),
            subHeading = document.getElementById("pageSubHeading");

        function showLogin() {
            loginBtn?.classList.add("active");
            registerBtn?.classList.remove("active");
            if (loginPane) loginPane.style.display = "block";
            if (registerPane) registerPane.style.display = "none";
            if (mainHeading) mainHeading.textContent = "Chào mừng trở lại";
            if (subHeading) subHeading.textContent = "Vui lòng nhập thông tin để đăng nhập hệ thống";
        }

        function showRegister() {
            registerBtn?.classList.add("active");
            loginBtn?.classList.remove("active");
            if (registerPane) registerPane.style.display = "block";
            if (loginPane) loginPane.style.display = "none";
            if (mainHeading) mainHeading.textContent = "Tạo tài khoản mới";
            if (subHeading) subHeading.textContent = "Đăng ký tài khoản QuizTech";
        }
        loginBtn?.addEventListener("click", e => {
            e.preventDefault();
            showLogin();
            history.replaceState(null, "", "login.php?tab=login")
        });
        registerBtn?.addEventListener("click", e => {
            e.preventDefault();
            showRegister();
            history.replaceState(null, "", "login.php?tab=register")
        });

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;

        function passwordState(p) {
            return {
                length: p.length >= 8 && p.length <= 20,
                upper: /[A-Z]/.test(p),
                lower: /[a-z]/.test(p),
                number: /[0-9]/.test(p),
                special: /[^A-Za-z0-9\s]/.test(p),
                space: !/\s/.test(p)
            }
        }

        function passwordValid(p) {
            return Object.values(passwordState(p)).every(Boolean)
        }

        function normalizeName(v) {
            return v.trim().replace(/\s+/g, " ")
        }

        function validName(v) {
            v = normalizeName(v);
            return v.length >= 3 && v.length <= 100 && /^[\p{L}]+(?:\s+[\p{L}]+)*$/u.test(v)
        }

        function feedback(id, ok, msg) {
            const input = document.getElementById(id);
            if (!input) return ok;
            const group = input.closest(".input-group") || input.parentElement;
            group.classList.remove("field-valid", "field-invalid", "has-error", "is-valid-field");
            if (input.value.length > 0) group.classList.add(ok ? "field-valid" : "field-invalid");
            let box = document.getElementById(id + "Live");
            if (!box) {
                box = document.createElement("div");
                box.id = id + "Live";
                group.insertAdjacentElement("afterend", box)
            }
            box.className = "validation-msg " + (ok ? "success" : "error");
            box.innerHTML = input.value.length === 0 ? '' : (ok ? '<i class="bi bi-check-circle-fill"></i><span>Hợp lệ</span>' : '<i class="bi bi-exclamation-circle-fill"></i><span>' + msg + '</span>');
            input.setAttribute("aria-invalid", ok ? "false" : "true");
            return ok;
        }

        function passwordMessage(p) {
            const r = passwordState(p),
                m = [];
            if (!r.length) m.push("8–20 ký tự");
            if (!r.upper) m.push("1 chữ hoa");
            if (!r.lower) m.push("1 chữ thường");
            if (!r.number) m.push("1 chữ số");
            if (!r.special) m.push("1 ký tự đặc biệt");
            if (!r.space) m.push("không khoảng trắng");
            return "Cần: " + m.join(", ") + ".";
        }

        function updateRules() {
            const el = document.getElementById("registerPassword");
            if (!el) return false;
            const r = passwordState(el.value);
            Object.entries(r).forEach(([k, v]) => {
                const x = document.querySelector('[data-rule="' + k + '"]');
                if (!x) return;
                x.classList.toggle("ok", v);
                x.classList.toggle("bad", el.value.length > 0 && !v);
                const i = x.querySelector("i");
                if (i) i.className = v ? "bi bi-check-circle-fill" : "bi bi-x-circle-fill"
            });
            return Object.values(r).every(Boolean);
        }

        function validateLogin() {
            const e = document.getElementById("loginEmail"),
                p = document.getElementById("loginPassword");
            let ok = true;
            ok = feedback("loginEmail", emailPattern.test(e.value.trim()), "Vui lòng nhập đúng định dạng email.") && ok;
            ok = feedback("loginPassword", passwordValid(p.value), passwordMessage(p.value)) && ok;
            return ok;
        }

        function validateRegister() {
            const n = document.getElementById("registerName"),
                e = document.getElementById("registerEmail"),
                p = document.getElementById("registerPassword"),
                c = document.getElementById("registerConfirm");
            let ok = true;
            n.value = normalizeName(n.value);
            ok = feedback("registerName", validName(n.value), "Họ tên phải 3–100 ký tự, chỉ gồm chữ và khoảng trắng.") && ok;
            ok = feedback("registerEmail", emailPattern.test(e.value.trim()), "Vui lòng nhập đúng định dạng email.") && ok;
            ok = feedback("registerPassword", passwordValid(p.value), passwordMessage(p.value)) && ok;
            const confirmOK = c.value.length > 0 && c.value === p.value && passwordValid(c.value);
            ok = feedback("registerConfirm", confirmOK, c.value !== p.value ? "Mật khẩu xác nhận không trùng khớp." : passwordMessage(c.value)) && ok;
            updateRules();
            return ok;
        }
        document.getElementById("loginActualForm")?.addEventListener("submit", e => {
            if (!validateLogin()) {
                e.preventDefault();
                e.stopPropagation()
            }
        });
        document.getElementById("registerActualForm")?.addEventListener("submit", e => {
            if (!validateRegister()) {
                e.preventDefault();
                e.stopPropagation()
            }
        });
        ["loginEmail", "loginPassword"].forEach(id => document.getElementById(id)?.addEventListener("input", validateLogin));
        ["registerName", "registerEmail", "registerPassword", "registerConfirm"].forEach(id => document.getElementById(id)?.addEventListener("input", validateRegister));
        document.querySelectorAll("[data-toggle-password]").forEach(btn => btn.addEventListener("click", () => {
            const x = document.getElementById(btn.dataset.togglePassword);
            if (!x) return;
            x.type = x.type === "password" ? "text" : "password";
            const i = btn.querySelector("i");
            if (i) i.className = x.type === "password" ? "bi bi-eye" : "bi bi-eye-slash"
        }));
        updateRules();
    </script>
</body>

</html>