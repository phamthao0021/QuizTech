<?php
// auth.php - Đăng nhập/Đăng ký
require_once 'config/database.php';

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // ĐĂNG KÝ
        if ($_POST['action'] === 'register') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate
            if (empty($name) || empty($email) || empty($password)) {
                $error = 'Vui lòng điền đầy đủ thông tin!';
            } elseif ($password !== $confirm_password) {
                $error = 'Mật khẩu xác nhận không khớp!';
            } elseif (strlen($password) < 6) {
                $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
            } else {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $hashed_password]);
                    $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
                    $active_tab = 'login';
                } catch(PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = 'Email đã được sử dụng!';
                    } else {
                        $error = 'Lỗi: ' . $e->getMessage();
                    }
                }
            }
        }
        
        // ĐĂNG NHẬP
        if ($_POST['action'] === 'login') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                $error = 'Vui lòng nhập email và mật khẩu!';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Email hoặc mật khẩu không đúng!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập/Đăng ký - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-card {
            max-width: 450px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .auth-card .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-card .logo h2 {
            font-weight: 700;
            color: #667eea;
        }
        .btn-auth {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-auth:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .nav-tabs .nav-link {
            color: #666;
            border: none;
            padding: 10px 25px;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: transparent;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="logo">
                <h2><i class="bi bi-code-square"></i> QuizTech</h2>
                <p class="text-muted">TRẮC NGHIỆM CNTT</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <ul class="nav nav-tabs justify-content-center mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab === 'login' ? 'active' : '' ?>" href="?tab=login">Đăng nhập</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab === 'register' ? 'active' : '' ?>" href="?tab=register">Đăng ký</a>
                </li>
            </ul>
            
            <?php if ($active_tab === 'login'): ?>
            <!-- Login Form -->
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-auth">Đăng nhập</button>
            </form>
            <?php else: ?>
            <!-- Register Form -->
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Họ tên</label>
                    <input type="text" name="name" class="form-control" placeholder="Nguyễn Văn A" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="Ít nhất 6 ký tự" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>
                </div>
                <button type="submit" class="btn-auth">Đăng ký</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>