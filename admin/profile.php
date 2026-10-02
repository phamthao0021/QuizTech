<?php
// admin/profile.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

$user_id = $_SESSION['user_id'] ?? 0;

// Lấy thông tin admin hiện tại từ CSDL
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    setFlash('danger', 'Không tìm thấy thông tin tài khoản!');
    redirect('dashboard.php');
}

// -------------------------------------------------------------
// XỬ LÝ CẬP NHẬT THÔNG TIN CÁ NHÂN & UPLOAD ẢNH ĐẠI DIỆN
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (function_exists('verify_csrf')) {
        verify_csrf();
    }

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {
        setFlash('danger', 'Vui lòng nhập đầy đủ họ tên và email!');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'Địa chỉ email không hợp lệ!');
    } else {
        // Kiểm tra email trùng lặp với tài khoản khác
        $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $user_id]);

        if ($checkEmail->fetchColumn() > 0) {
            setFlash('danger', 'Email này đã được sử dụng bởi tài khoản khác!');
        } else {
            // Giữ lại đường dẫn ảnh cũ mặc định trong CSDL
            $avatarPath = $currentUser['avatar'] ?? ''; 

            // Xử lý upload ảnh đại diện mới
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['avatar']['tmp_name'];
                $fileName    = $_FILES['avatar']['name'];
                $fileSize    = $_FILES['avatar']['size'];
                $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($fileExt, $allowedExtensions)) {
                    setFlash('danger', 'Chỉ chấp nhận các định dạng ảnh: JPG, JPEG, PNG, GIF, WEBP!');
                    redirect('profile.php');
                } elseif ($fileSize > 2 * 1024 * 1024) { // Giới hạn 2MB
                    setFlash('danger', 'Dung lượng ảnh tối đa cho phép là 2MB!');
                    redirect('profile.php');
                } else {
                    $uploadDir = '../uploads/avatars/';
                    
                    // Tự động tạo thư mục uploads/avatars nếu chưa tồn tại
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $newFileName = 'avatar_admin_' . $user_id . '_' . time() . '.' . $fileExt;
                    $destPath    = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Xóa tệp ảnh cũ nếu tồn tại trong thư mục cục bộ
                        if (!empty($currentUser['avatar']) && file_exists('../' . $currentUser['avatar'])) {
                            @unlink('../' . $currentUser['avatar']);
                        }

                        $avatarPath = 'uploads/avatars/' . $newFileName; 
                    } else {
                        setFlash('danger', 'Lỗi trong quá trình lưu tệp ảnh lên máy chủ!');
                        redirect('profile.php');
                    }
                }
            }

            // Cập nhật CSDL
            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, avatar = ? WHERE id = ?");
            if ($updateStmt->execute([$name, $email, $phone, $avatarPath, $user_id])) {
                // Cập nhật Session
                $_SESSION['user_name'] = $name;
                $_SESSION['avatar']    = $avatarPath;
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['name']   = $name;
                    $_SESSION['user']['avatar'] = $avatarPath;
                }

                setFlash('success', 'Cập nhật thông tin cá nhân thành công!');
                redirect('profile.php');
            } else {
                setFlash('danger', 'Không thể lưu thông tin vào hệ thống!');
            }
        }
    }
}

// -------------------------------------------------------------
// XỬ LÝ ĐỔI MẬT KHẨU
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (function_exists('verify_csrf')) {
        verify_csrf();
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        setFlash('danger', 'Vui lòng điền đầy đủ thông tin mật khẩu!');
    } elseif (!password_verify($current_password, ($currentUser['password_hash'] ?? ($currentUser['password_hash'] ?? $currentUser['password'] ?? '') ?? ''))) {
        setFlash('danger', 'Mật khẩu hiện tại không chính xác!');
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s])\S{8,20}$/', $new_password)) {
        setFlash('danger', 'Mật khẩu mới phải từ 8–20 ký tự, có ít nhất 1 chữ hoa, 1 chữ thường, 1 chữ số, 1 ký tự đặc biệt và không chứa khoảng trắng!');
    } elseif ($new_password !== $confirm_password) {
        setFlash('danger', 'Mật khẩu xác nhận không trùng khớp!');
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $passStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");

        if ($passStmt->execute([$hashed_password, $user_id])) {
            setFlash('success', 'Đã thay đổi mật khẩu thành công!');
            redirect('profile.php');
        } else {
            setFlash('danger', 'Lỗi khi cập nhật mật khẩu mới!');
        }
    }
}

// Định dạng ảnh hiển thị
$avatarUrl = !empty($currentUser['avatar']) && file_exists('../' . $currentUser['avatar'])
    ? '../' . e($currentUser['avatar']) . '?v=' . time()
    : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&background=6366f1&color=fff&size=200';

$page_title = 'Hồ sơ cá nhân Admin';
include '../includes/header_admin.php';
?>

<style>
    :root {
        --purple-main: #6366f1;
        --purple-dark: #4f46e5;
        --purple-deep: #3730a3;
        --purple-glow: rgba(99, 102, 241, 0.25);
        --text-dark: #0f172a;
        --text-muted: #64748b;
    }

    body {
        background-color: #f8fafc;
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        color: var(--text-dark);
    }

    /* BANNER GRADIENT TÍM */
    .hero-banner-admin {
        background: linear-gradient(135deg, var(--purple-deep) 0%, var(--purple-dark) 50%, var(--purple-main) 100%);
        border-radius: 24px;
        padding: 2.25rem 2rem;
        color: #ffffff;
        box-shadow: 0 20px 30px -10px var(--purple-glow);
    }

    .stat-pill {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 16px;
        padding: 0.6rem 1.25rem;
        color: #ffffff;
    }

    .creative-card {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
    }

    /* AVATAR OVERLAY BUTTON */
    .avatar-wrapper {
        position: relative;
        display: inline-block;
    }

    .avatar-img {
        width: 140px;
        height: 140px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        transition: all 0.3s ease;
    }

    .avatar-upload-btn {
        position: absolute;
        bottom: 4px;
        right: 4px;
        background: linear-gradient(135deg, var(--purple-main), var(--purple-dark));
        color: #ffffff;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.25s ease;
    }

    .avatar-upload-btn:hover {
        transform: scale(1.1);
        background: linear-gradient(135deg, var(--purple-dark), var(--purple-deep));
    }

    /* FORM STYLING */
    .form-control {
        border-radius: 12px;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--purple-main);
        box-shadow: 0 0 0 4px var(--purple-glow);
    }

    .input-group-text {
        border-radius: 12px 0 0 12px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        color: var(--text-muted);
    }

    .form-label {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.875rem;
        margin-bottom: 0.4rem;
    }

    .btn-purple {
        background: linear-gradient(135deg, var(--purple-main), var(--purple-dark));
        color: #ffffff;
        border: none;
        border-radius: 30px;
        padding: 0.75rem 1.75rem;
        font-weight: 600;
        box-shadow: 0 8px 20px var(--purple-glow);
        transition: all 0.25s ease;
    }

    .btn-purple:hover {
        background: linear-gradient(135deg, var(--purple-dark), var(--purple-deep));
        color: #ffffff;
        transform: translateY(-2px);
    }

    .btn-gradient-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #ffffff;
        border: none;
        border-radius: 30px;
        padding: 0.75rem 1.75rem;
        font-weight: 600;
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
        transition: all 0.25s ease;
    }

    .btn-gradient-danger:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: #ffffff;
        transform: translateY(-2px);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-row:last-child {
        border-bottom: none;
    }
</style>

<div class="container-fluid py-4 px-3 px-md-4">
    <!-- HERO BANNER TÍM -->
    <div class="hero-banner-admin admin-page-head mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold mb-1 fs-3 text-white">Hồ Sơ Cá Nhân Admin</h3>
            <p class="mb-0 text-white-50 small">Quản lý thông tin tài khoản, ảnh đại diện và bảo mật hệ thống</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="stat-pill d-flex align-items-center gap-2">
                <i class="bi bi-shield-check fs-5 text-warning"></i>
                <span class="fw-bold small">Quản trị viên</span>
            </div>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    <?php if (function_exists('getFlash') && $flash = getFlash()): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- CỘT TRÁI: THẺ THÔNG TIN & ANH DẠI DIỆN -->
        <div class="col-lg-4">
            <div class="creative-card p-4 text-center h-100">
                <div class="avatar-wrapper mb-3">
                    <img id="avatarPreview" src="<?= $avatarUrl ?>" class="avatar-img" alt="Avatar Admin">
                    <label for="avatarInput" class="avatar-upload-btn" title="Tải ảnh mới lên">
                        <i class="bi bi-camera-fill fs-6"></i>
                    </label>
                </div>

                <h5 class="fw-bold text-dark mb-1"><?= e($currentUser['name']) ?></h5>
                <p class="text-muted small mb-3"><?= e($currentUser['email']) ?></p>

                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small">
                        <i class="bi bi-person-badge me-1"></i> Administrator
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill small">
                        <i class="bi bi-check-circle me-1"></i> Hoạt động
                    </span>
                </div>

                <hr class="my-4 opacity-50">

                <div class="text-start">
                    <div class="info-row">
                        <span class="text-muted small fw-semibold"><i class="bi bi-hash text-primary me-1.5"></i> ID Tài khoản</span>
                        <strong class="text-dark">#<?= $currentUser['id'] ?></strong>
                    </div>
                    <div class="info-row">
                        <span class="text-muted small fw-semibold"><i class="bi bi-telephone text-primary me-1.5"></i> Điện thoại</span>
                        <strong class="text-dark"><?= e($currentUser['phone'] ?? 'Chưa cập nhật') ?></strong>
                    </div>
                    <div class="info-row">
                        <span class="text-muted small fw-semibold"><i class="bi bi-calendar-check text-primary me-1.5"></i> Ngày khởi tạo</span>
                        <strong class="text-dark"><?= function_exists('format_date') ? format_date($currentUser['created_at'] ?? date('Y-m-d')) : date('d/m/Y', strtotime($currentUser['created_at'] ?? date('Y-m-d'))) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: FORM CẬP NHẬT & ĐỔI MẬT KHẨU -->
        <div class="col-lg-8">
            <div class="d-flex flex-column gap-4">
                
                <!-- FORM CẬP NHẬT THÔNG TIN -->
                <div class="creative-card p-4">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="p-2.5 rounded-3 bg-primary-subtle text-primary">
                            <i class="bi bi-person-lines-fill fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Thông Tin Cá Nhân</h5>
                            <p class="text-muted small mb-0">Cập nhật danh tính và thông tin liên lạc</p>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <?php if (function_exists('csrf_field')) csrf_field(); ?>
                        
                        <!-- Input chọn ảnh ẩn -->
                        <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*" onchange="previewImage(this);">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="name" class="form-control" value="<?= e($currentUser['name']) ?>" required placeholder="Nhập họ và tên...">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email liên hệ <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?= e($currentUser['email']) ?>" required placeholder="name@example.com">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="text" name="phone" class="form-control" value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="0901234567">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Quyền hạn tài khoản</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                    <input type="text" class="form-control bg-light" value="Administrator (Cao nhất)" readonly disabled>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center mb-4 p-3 rounded-4 border-0 bg-info-subtle text-info-emphasis">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <div class="small">Bấm vào biểu tượng <strong>máy ảnh</strong> trên ảnh đại diện để đổi hình ảnh mới (định dạng JPG, PNG, WEBP, tối đa 2MB), sau đó chọn <strong>Lưu thay đổi</strong>.</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="update_profile" value="1" class="btn btn-purple">
                                <i class="bi bi-check-circle-fill me-2"></i> Lưu Thay Đổi
                            </button>
                        </div>
                    </form>
                </div>

                <!-- FORM ĐỔI MẬT KHẨU -->
                <div class="creative-card p-4">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="p-2.5 rounded-3 bg-danger-subtle text-danger">
                            <i class="bi bi-key-fill fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Thiết Lập Mật Khẩu</h5>
                            <p class="text-muted small mb-0">Đổi mật khẩu định kỳ để bảo vệ tài khoản</p>
                        </div>
                    </div>

                    <form method="POST">
                        <?php if (function_exists('csrf_field')) csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="current_password" class="form-control" placeholder="Nhập mật khẩu đang dùng..." required>
                            </div>
                        </div>

                        <div class="small text-muted mb-3">Mật khẩu hiện tại chỉ cần nhập đúng mật khẩu đang dùng. Mật khẩu mới phải 8–20 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt. Ví dụ hợp lệ: Admin@123.</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" name="new_password" class="form-control" placeholder="Ví dụ: Admin@123" minlength="8" maxlength="20" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s])\S{8,20}" title="8–20 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-check2-circle"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới..." required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="change_password" value="1" class="btn btn-gradient-danger">
                                <i class="bi bi-shield-lock-fill me-2"></i> Cập Nhật Mật Khẩu
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Xem trước ảnh đại diện (Preview) khi chọn file
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>


<script>
(function(){
 const p=document.querySelector('input[name="new_password"]'), c=document.querySelector('input[name="confirm_password"]');
 if(!p||!c)return;
 const valid=v=>v.length>=8&&v.length<=20&&/[A-Z]/.test(v)&&/[a-z]/.test(v)&&/[0-9]/.test(v)&&/[^A-Za-z0-9\s]/.test(v)&&!/\s/.test(v);
 function paint(el,ok,msg){let b=el.parentElement.nextElementSibling;if(!b||!b.classList.contains("password-live")){b=document.createElement("div");b.className="password-live small mt-1";el.parentElement.insertAdjacentElement("afterend",b)}const g=el.closest(".input-group")||el;g.style.boxShadow=el.value?"0 0 0 3px "+(ok?"rgba(34,197,94,.10)":"rgba(239,68,68,.10)"):"";el.style.borderColor=el.value?(ok?"#22c55e":"#ef4444"):"";b.style.color=ok?"#16a34a":"#dc2626";b.textContent=el.value?(ok?"✓ Mật khẩu hợp lệ":msg):""}
 function check(){const a=valid(p.value),b=c.value===p.value&&valid(c.value);paint(p,a,"8–20 ký tự, cần chữ hoa, chữ thường, số, ký tự đặc biệt và không khoảng trắng.");paint(c,b,c.value!==p.value?"Mật khẩu xác nhận không trùng khớp.":"Mật khẩu xác nhận chưa hợp lệ.");return a&&b}
 p.addEventListener("input",check);c.addEventListener("input",check);p.form?.addEventListener("submit",e=>{if(!check())e.preventDefault()});
})();
</script>

<?php include '../includes/footer.php'; ?>  