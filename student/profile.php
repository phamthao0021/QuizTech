<?php
// student/profile.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireLogin(); // Đảm bảo quyền học viên

$user_id = $_SESSION['user_id'] ?? 0;

// Lấy thông tin học viên hiện tại từ CSDL
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    setFlash('danger', 'Không tìm thấy thông tin tài khoản!');
    redirect('dashboard.php');
}

// -------------------------------------------------------------
// XỬ LÝ CẬP NHẬT THÔNG TIN CÁ NHÂN & UP ẢNH ĐẠI DIỆN
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verify_csrf();

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
            $avatarPath = $currentUser['avatar'] ?? ''; // Giữ lại đường dẫn ảnh cũ mặc định

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

                    $newFileName = 'avatar_student_' . $user_id . '_' . time() . '.' . $fileExt;
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
                $_SESSION['user_name'] = $name; // Cập nhật Session tên
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
    verify_csrf();

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        setFlash('danger', 'Vui lòng điền đầy đủ thông tin mật khẩu!');
    } elseif (!password_verify($current_password, $currentUser['password'])) {
        setFlash('danger', 'Mật khẩu hiện tại không chính xác!');
    } elseif (strlen($new_password) < 6) {
        setFlash('danger', 'Mật khẩu mới phải có ít nhất 6 ký tự!');
    } elseif ($new_password !== $confirm_password) {
        setFlash('danger', 'Mật khẩu xác nhận không trùng khớp!');
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");

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
    ? '../' . e($currentUser['avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&background=0D6EFD&color=fff&size=200';

$page_title = 'Hồ sơ học viên';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h4 class="mb-1">Thẻ cá nhân Học viên</h4>
                <p class="text-muted mb-0">Quản lý hồ sơ, ảnh đại diện và thiết lập bảo mật</p>
            </div>
            <span class="badge bg-primary fs-6 px-3 py-2"><i class="bi bi-person-badge me-1"></i> Học viên</span>
        </div>

        <div class="row g-4">
            <!-- Cột Trái: Card Thông tin & Ảnh Đại Diện -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body p-4">
                        
                        <!-- Avatar Container với nút chọn ảnh overlay -->
                        <div class="position-relative d-inline-block mb-3">
                            <img id="avatarPreview" src="<?= $avatarUrl ?>" 
                                 class="rounded-circle img-thumbnail shadow-sm" 
                                 style="width: 140px; height: 140px; object-fit: cover;" 
                                 alt="Avatar Học viên">
                            <label for="avatarInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow cursor-pointer" 
                                   style="cursor: pointer; width: 40px; height: 40px;" title="Tải ảnh mới lên">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                        </div>

                        <h5 class="fw-bold mb-1"><?= e($currentUser['name']) ?></h5>
                        <p class="text-muted small mb-3"><?= e($currentUser['email']) ?></p>

                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                <i class="bi bi-mortarboard"></i> Student
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                <i class="bi bi-check-circle"></i> Hoạt động
                            </span>
                        </div>

                        <hr class="my-4">

                        <!-- Chi tiết thông tin thêm -->
                        <div class="text-start">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted"><i class="bi bi-hash me-1"></i> ID Học viên:</span>
                                <strong>#<?= $currentUser['id'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted"><i class="bi bi-telephone me-1"></i> Điện thoại:</span>
                                <strong><?= e($currentUser['phone'] ?? 'Chưa cập nhật') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted"><i class="bi bi-calendar-check me-1"></i> Ngày gia nhập:</span>
                                <strong><?= format_date($currentUser['created_at'] ?? date('Y-m-d')) ?></strong>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Cột Phải: Form Chỉnh Sửa Thông Tin & Đổi Mật Khẩu -->
            <div class="col-lg-8">
                <!-- FORM UPDATE PROFILE & AVATAR -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-person-lines-fill me-2"></i> Cập nhật thông tin cá nhân
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <?php csrf_field(); ?>
                            
                            <!-- Input chọn ảnh đại diện ẩn -->
                            <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*" onchange="previewImage(this);">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">Họ và tên</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" name="name" class="form-control" value="<?= e($currentUser['name']) ?>" required placeholder="Nhập họ và tên...">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">Email liên hệ</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" value="<?= e($currentUser['email']) ?>" required placeholder="name@example.com">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Số điện thoại</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                        <input type="text" name="phone" class="form-control" value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="0901234567">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Vai trò hệ thống</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield"></i></span>
                                        <input type="text" class="form-control bg-light" value="Học viên (Student)" readonly disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-middle mt-3 mb-0 p-2 small">
                                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                <div>Bấm vào biểu tượng <strong>máy ảnh</strong> trên hình đại diện bên trái để tải ảnh mới lên (định dạng JPG, PNG, WEBP, tối đa 2MB).</div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" name="update_profile" value="1" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i> Lưu thay đổi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- FORM ĐỔI MẬT KHẨU -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-bold text-danger">
                            <i class="bi bi-key-fill me-2"></i> Đổi mật khẩu
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <?php csrf_field(); ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold required">Mật khẩu hiện tại</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="current_password" class="form-control" placeholder="Nhập mật khẩu đang dùng..." required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">Mật khẩu mới</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="password" name="new_password" class="form-control" placeholder="Tối thiểu 6 ký tự..." required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">Xác nhận mật khẩu mới</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-check2-circle"></i></span>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới..." required>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="change_password" value="1" class="btn btn-danger px-4">
                                    <i class="bi bi-shield-lock-fill me-1"></i> Cập nhật mật khẩu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
// JavaScript xem trước ảnh ngay khi chọn file
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

<?php include '../includes/footer.php'; ?>