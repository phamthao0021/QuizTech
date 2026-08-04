<?php
// hash_password.php - Công cụ tạo mật khẩu hash

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Khởi tạo session để lưu thông báo nếu chưa khởi tạo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$result = '';
$password = '';
$hash = '';

// Xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $result = '<div class="alert alert-danger">⚠️ Vui lòng nhập mật khẩu.</div>';
    } elseif (strlen($password) < 6) {
        $result = '<div class="alert alert-warning">⚠️ Mật khẩu nên có ít nhất 6 ký tự.</div>';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $result = '<div class="alert alert-success">✅ Đã tạo hash thành công!</div>';
    }
}

// Tạo hash cho các mật khẩu mặc định
$defaultPasswords = [
    '123456' => password_hash('123456', PASSWORD_DEFAULT),
    'Admin@123' => password_hash('Admin@123', PASSWORD_DEFAULT),
    'Teacher@123' => password_hash('Teacher@123', PASSWORD_DEFAULT),
    'Student@123' => password_hash('Student@123', PASSWORD_DEFAULT),
];
echo password_hash('MatKhauMoiCuaBan', PASSWORD_DEFAULT);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hash Password Generator - QuizTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .card-custom {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .hash-box {
            background: #1a1a2e;
            color: #00ff88;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            margin-top: 10px;
        }
        .copy-btn {
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            transform: scale(1.05);
        }
        .table-hash {
            font-size: 13px;
        }
        .table-hash td {
            word-break: break-all;
        }
        .badge-pwd {
            font-family: monospace;
            background: #2d3436;
            color: #fdcb6e;
            padding: 2px 10px;
            border-radius: 5px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h2 {
            font-weight: 700;
            color: #667eea;
        }
        .logo p {
            color: #636e72;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-custom">
            <div class="logo">
                <h2><i class="bi bi-code-square"></i> QuizTech</h2>
                <p>Công cụ tạo mật khẩu hash</p>
            </div>

            <?php if ($result): ?>
                <?= $result ?>
            <?php endif; ?>

            <!-- Form tạo hash -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-plus-circle"></i> Tạo hash mới</h5>
                    <form method="post">
                        <?php csrf_field(); ?>
                        <div class="input-group">
                            <input type="text" name="password" class="form-control" 
                                   placeholder="Nhập mật khẩu cần hash..." 
                                   value="<?= htmlspecialchars($password) ?>" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-hash"></i> Tạo hash
                            </button>
                        </div>
                        <div class="form-text text-muted">Mật khẩu tối thiểu 6 ký tự</div>
                    </form>

                    <?php if ($hash): ?>
                        <div class="mt-3">
                            <label class="form-label fw-semibold">Mật khẩu:</label>
                            <span class="badge bg-dark"><code><?= htmlspecialchars($password) ?></code></span>
                            <br>
                            <label class="form-label fw-semibold mt-2">Hash:</label>
                            <div class="hash-box">
                                <span id="currentHash"><?= htmlspecialchars($hash) ?></span>
                                <button class="btn btn-sm btn-outline-light float-end copy-btn" 
                                        onclick="copyToClipboard('<?= addslashes($hash) ?>')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hash mặc định -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-list-check"></i> Hash mặc định</h5>
                    <p class="text-muted small">Sử dụng cho các tài khoản demo</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-hash">
                            <thead>
                                <tr>
                                    <th>Mật khẩu</th>
                                    <th>Hash</th>
                                    <th class="text-end">Copy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($defaultPasswords as $pwd => $h): ?>
                                    <tr>
                                        <td><span class="badge-pwd"><?= htmlspecialchars($pwd) ?></span></td>
                                        <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <code><?= htmlspecialchars($h) ?></code>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary copy-btn" 
                                                    onclick="copyToClipboard('<?= addslashes($h) ?>')">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SQL Update -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-database"></i> SQL Update</h5>
                    <p class="text-muted small">Copy SQL để cập nhật trực tiếp vào CSDL</p>
                    <div class="bg-dark text-white p-3 rounded" style="font-size: 12px; font-family: monospace;">
                        <span class="text-secondary">-- Cập nhật mật khẩu cho user</span><br>
                        <span class="text-warning">UPDATE</span> users <span class="text-warning">SET</span> password = <span class="text-info">'<?= $hash ? htmlspecialchars($hash) : 'YOUR_HASH_HERE' ?>'</span> <span class="text-warning">WHERE</span> email = <span class="text-info">'admin@quiztech.vn'</span>;
                    </div>
                </div>
            </div>

            <!-- Liên kết -->
            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-outline-primary">
                    <i class="bi bi-box-arrow-in-right"></i> Quay lại đăng nhập
                </a>
                <a href="index.php" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-house"></i> Trang chủ
                </a>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('✅ Đã copy chuỗi hash vào clipboard!');
                }).catch(err => {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                alert('✅ Đã copy chuỗi hash vào clipboard!');
            } catch (err) {
                alert('❌ Không thể copy, vui lòng copy thủ công.');
            }
            document.body.removeChild(textArea);
            
        }
        
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>