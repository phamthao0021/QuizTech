<?php // admin/users.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
require_once '../includes/components/datatable.php';

requireAdmin();

$page_title = 'Quản lý người dùng';

// Lấy ID tài khoản admin đang đăng nhập (chống tự xóa/tự hạ quyền)
$current_admin_id = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
$actor_is_super   = function_exists('isSuperAdmin') && isSuperAdmin();
$actor_is_test    = function_exists('isAdminTest') && isAdminTest();
$assignable_roles = function_exists('assignable_roles_for_actor') ? assignable_roles_for_actor() : [
    'student' => 'Học sinh / Sinh viên',
    'teacher' => 'Giảng viên',
];

$error = '';
$success = '';

// Thư mục lưu trữ avatar upload
$upload_dir = __DIR__ . '/../uploads/avatars/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// ============================================================================
// HÀM CHUẨN HÓA VAI TRÒ DÙNG RIÊNG CHO TRANG USER
// ============================================================================
if (!function_exists('getUserRoleInfo')) {
    function getUserRoleInfo($role) {
        $r = function_exists('normalizeRole') ? normalizeRole($role) : strtolower(trim((string)$role));
        switch ($r) {
            case 'admin':
                return [
                    'code'  => 'admin',
                    'label' => 'Super Admin',
                    'bg'    => 'bg-danger text-danger'
                ];
            case 'admin_test':
                return [
                    'code'  => 'admin_test',
                    'label' => 'Admin kiểm thử',
                    'bg'    => 'bg-info text-info'
                ];
            case 'teacher':
                return [
                    'code'  => 'teacher',
                    'label' => 'Giảng viên',
                    'bg'    => 'bg-warning text-dark'
                ];
            case 'student':
            default:
                return [
                    'code'  => 'student',
                    'label' => 'Học sinh / Sinh viên',
                    'bg'    => 'bg-primary text-primary'
                ];
        }
    }
}

// ============================================================================
// HÀM HỖ TRỢ XỬ LÝ UPLOAD ẢNH ĐẠI DIỆN
// ============================================================================
function handleAvatarUpload($file_input_name, $upload_dir, &$error_msg) {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$file_input_name];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $max_size = 3 * 1024 * 1024; // Giới hạn 3MB

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_exts)) {
        $error_msg = 'Ảnh đại diện không hợp lệ! Chỉ chấp nhận file JPG, PNG, WEBP, GIF.';
        return false;
    }

    if ($file['size'] > $max_size) {
        $error_msg = 'Dung lượng ảnh quá lớn! Tối đa 3MB.';
        return false;
    }

    $new_filename = 'avatar_' . time() . '_' . uniqid() . '.' . $ext;
    $target_file = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return 'uploads/avatars/' . $new_filename;
    }

    $error_msg = 'Không thể tải ảnh lên máy chủ!';
    return false;
}

// ============================================================================
// 1. XỬ LÝ CÁC HÀNH ĐỘNG CRUD (POST)
// ============================================================================
$action = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verify_csrf')) {
        verify_csrf();
    }
    $action = trim($_POST['action'] ?? '');

    // ------------------------------------------------------------------------
    // A. THÊM NGƯỜI DÙNG MỚI (CREATE)
    // ------------------------------------------------------------------------
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = function_exists('normalizeRole')
            ? normalizeRole($_POST['role'] ?? 'student')
            : ($_POST['role'] ?? 'student');

        if (empty($username) || empty($password) || empty($fullname)) {
            $error = 'Vui lòng điền đầy đủ Tên đăng nhập, Họ tên và Mật khẩu!';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Định dạng Email không hợp lệ!';
        } elseif (function_exists('can_assign_role') && !can_assign_role($role)) {
            $error = 'Bạn không có quyền tạo tài khoản với vai trò này!';
        } else {
            $uploaded_avatar = handleAvatarUpload('avatar', $upload_dir, $error);

            if ($error === '') {
                $user_data = [
                    'username' => $username,
                    'fullname' => $fullname,
                    'email'    => $email,
                    'password' => $password,
                    'role'     => $role,
                    'avatar'   => $uploaded_avatar ?: ''
                ];

                if (function_exists('addUser')) {
                    $result = addUser($user_data);
                    if ($result) {
                        $success = 'Thêm tài khoản người dùng mới thành công!';
                    } else {
                        $error = 'Thêm thất bại! Tên đăng nhập hoặc Email có thể đã tồn tại, hoặc không đủ quyền.';
                    }
                } else {
                    $error = 'Hệ thống thiếu hàm addUser().';
                }
            }
        }
    }

    // ------------------------------------------------------------------------
    // B. CẬP NHẬT NGƯỜI DÙNG (UPDATE)
    // ------------------------------------------------------------------------
    elseif ($action === 'edit') {
        $user_id       = intval($_POST['user_id'] ?? 0);
        $fullname      = trim($_POST['fullname'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $role          = function_exists('normalizeRole')
            ? normalizeRole($_POST['role'] ?? 'student')
            : ($_POST['role'] ?? 'student');
        $password      = $_POST['password'] ?? '';
        $remove_avatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1';
        $status        = $_POST['status'] ?? null;
        $want_protect  = isset($_POST['is_protected']) ? (int)!empty($_POST['is_protected']) : null;

        $target = function_exists('getUserById') ? getUserById($user_id) : null;

        if ($user_id <= 0 || empty($fullname) || !$target) {
            $error = 'Dữ liệu chỉnh sửa không hợp lệ!';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Định dạng Email không hợp lệ!';
        } elseif (function_exists('can_manage_user') && !can_manage_user($target, 'edit')) {
            $error = 'Bạn không có quyền chỉnh sửa tài khoản này (có thể là tài khoản được bảo vệ hoặc Super Admin)!';
        } elseif ($user_id === $current_admin_id && $role !== normalizeRole($target['role'] ?? 'admin') && normalizeRole($target['role'] ?? '') === 'admin' && $role !== 'admin') {
            $error = 'Bạn không thể tự hạ quyền Super Admin của chính mình!';
        } elseif (function_exists('can_assign_role') && !can_assign_role($role)) {
            $error = 'Bạn không có quyền gán vai trò này!';
        } elseif (function_exists('can_manage_user') && !can_manage_user($target, 'change_role') && $role !== normalizeRole($target['role'] ?? 'student')) {
            $error = 'Bạn không được phép đổi quyền của tài khoản này!';
        } else {
            $avatar_path = $target['avatar'] ?? '';

            if ($remove_avatar) {
                if (!empty($avatar_path) && !preg_match('/^https?:\/\//i', $avatar_path)) {
                    $old_file = __DIR__ . '/../' . ltrim($avatar_path, '/');
                    if (file_exists($old_file) && is_file($old_file)) @unlink($old_file);
                }
                $avatar_path = '';
            }

            $new_avatar = handleAvatarUpload('avatar', $upload_dir, $error);
            if ($new_avatar) {
                if (!empty($avatar_path) && !preg_match('/^https?:\/\//i', $avatar_path)) {
                    $old_file = __DIR__ . '/../' . ltrim($avatar_path, '/');
                    if (file_exists($old_file) && is_file($old_file)) @unlink($old_file);
                }
                $avatar_path = $new_avatar;
            }

            if ($error === '') {
                $update_payload = [
                    'fullname' => $fullname,
                    'email'    => $email,
                    'role'     => $role,
                    'avatar'   => $avatar_path
                ];

                if (!empty($password)) {
                    $update_payload['password'] = $password;
                }
                if ($status !== null && in_array($status, ['active', 'inactive', 'blocked'], true)) {
                    if (function_exists('can_manage_user') && can_manage_user($target, 'lock')) {
                        $update_payload['status'] = $status;
                    } elseif ($status !== ($target['status'] ?? 'active')) {
                        $error = 'Bạn không có quyền khóa/mở khóa tài khoản này!';
                    }
                }
                if ($want_protect !== null && function_exists('can_manage_user') && can_manage_user($target, 'set_protected')) {
                    $update_payload['is_protected'] = $want_protect;
                }

                if ($error === '' && function_exists('updateUser')) {
                    $res = updateUser($user_id, $update_payload);
                    if ($res) {
                        $success = 'Cập nhật thông tin tài khoản thành công!';
                    } else {
                        $error = 'Cập nhật thất bại! Vui lòng thử lại hoặc kiểm tra quyền.';
                    }
                } elseif ($error === '') {
                    $error = 'Hệ thống thiếu hàm updateUser().';
                }
            }
        }
    }

    // ------------------------------------------------------------------------
    // C. IMPORT NGƯỜI DÙNG TỪ CSV / XLSX
    // Xử lý hoàn toàn trong users.php, không cần import_data.php.
    // ------------------------------------------------------------------------
    elseif ($action === 'import_users') {
        $file = $_FILES['import_file'] ?? null;
        $rows = [];

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = 'Vui lòng chọn file CSV hoặc XLSX hợp lệ để import!';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Đọc CSV bằng PHP native.
            if ($ext === 'csv') {
                $handle = fopen($file['tmp_name'], 'r');
                if ($handle) {
                    $first = fgets($handle);
                    if ($first !== false) {
                        $enc = mb_detect_encoding($first, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'Windows-1258', 'ISO-8859-1'], true);
                        if ($enc && strtoupper($enc) !== 'UTF-8') {
                            $first = mb_convert_encoding($first, 'UTF-8', $enc);
                        }
                        $rows[] = str_getcsv($first);
                    }
                    while (($row = fgetcsv($handle)) !== false) $rows[] = $row;
                    fclose($handle);
                }
            }
            // Đọc XLSX trực tiếp bằng ZipArchive, không phụ thuộc PhpSpreadsheet.
            elseif ($ext === 'xlsx') {
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === true) {
                    $shared = [];
                    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
                    if ($sharedXml !== false) {
                        $sx = @simplexml_load_string($sharedXml);
                        if ($sx) {
                            $sx->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                            foreach ($sx->xpath('//a:si') as $si) {
                                $text = '';
                                foreach ($si->xpath('.//a:t') as $t) $text .= (string)$t;
                                $shared[] = $text;
                            }
                        }
                    }
                    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
                    if ($sheetXml !== false) {
                        $sx = @simplexml_load_string($sheetXml);
                        if ($sx) {
                            $sx->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                            foreach ($sx->xpath('//a:sheetData/a:row') as $xlsxRow) {
                                $out = [];
                                foreach ($xlsxRow->xpath('./a:c') as $cell) {
                                    $ref = (string)$cell['r'];
                                    preg_match('/([A-Z]+)\d+/', $ref, $m);
                                    $letters = $m[1] ?? 'A';
                                    $col = 0;
                                    for ($i=0; $i<strlen($letters); $i++) $col = $col * 26 + (ord($letters[$i])-64);
                                    $idx = $col - 1;
                                    while (count($out) <= $idx) $out[] = '';
                                    $v = (string)($cell->v ?? '');
                                    $type = (string)$cell['t'];
                                    if ($type === 's') $v = $shared[(int)$v] ?? $v;
                                    elseif ($type === 'inlineStr') $v = (string)($cell->is->t ?? '');
                                    $out[$idx] = trim($v);
                                }
                                $rows[] = $out;
                            }
                        }
                    }
                    $zip->close();
                }
            } else {
                $error = 'Chỉ hỗ trợ file .csv và .xlsx. File .xls cần lưu lại thành .xlsx hoặc .csv trước khi import.';
            }

            if ($error === '' && empty($rows)) {
                $error = 'Không đọc được dữ liệu từ file import!';
            }

            if ($error === '' && !empty($rows)) {
                // Chuẩn hóa tên cột.
                $normalizeHeader = function($v) {
                    $v = trim((string)$v);
                    $v = preg_replace('/^\xEF\xBB\xBF/', '', $v);
                    $v = mb_strtolower($v, 'UTF-8');
                    $v = strtr($v, [
                        'ă'=>'a','â'=>'a','á'=>'a','à'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a',
                        'ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
                        'ê'=>'e','é'=>'e','è'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
                        'ô'=>'o','ơ'=>'o','ó'=>'o','ò'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
                        'ư'=>'u','ú'=>'u','ù'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
                        'í'=>'i','ì'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i','ý'=>'y','ỳ'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y','đ'=>'d'
                    ]);
                    $v = preg_replace('/[^a-z0-9]+/', '_', $v);
                    return trim($v, '_');
                };

                $headers = array_map($normalizeHeader, array_shift($rows));
                $aliases = [
                    'username'=>['username','ten_dang_nhap','tai_khoan','account','user'],
                    'fullname'=>['fullname','full_name','ho_ten','ho_va_ten','name','ten'],
                    'email'=>['email','email_address','dia_chi_email'],
                    'password'=>['password','mat_khau','pass'],
                    'role'=>['role','vai_tro','quyen'],
                    'status'=>['status','trang_thai']
                ];
                $map = [];
                foreach ($aliases as $key=>$names) {
                    foreach ($names as $name) {
                        $idx = array_search($name, $headers, true);
                        if ($idx !== false) { $map[$key] = $idx; break; }
                    }
                }

                if (!isset($map['username']) || !isset($map['fullname']) || !isset($map['password'])) {
                    $error = 'File import phải có ít nhất các cột: Tên đăng nhập, Họ và tên, Mật khẩu.';
                } else {
                    $inserted = 0;
                    $skipped = 0;
                    $rowErrors = [];
                    $existingUsers = function_exists('getUsers') ? getUsers() : [];
                    $existingUsers = is_array($existingUsers) ? $existingUsers : [];

                    if (function_exists('addUser')) {
                        foreach ($rows as $i=>$row) {
                            $excelRow = $i + 2;
                            $username = trim((string)($row[$map['username']] ?? ''));
                            $fullname = trim((string)($row[$map['fullname']] ?? ''));
                            $email = isset($map['email']) ? trim((string)($row[$map['email']] ?? '')) : '';
                            $password = isset($map['password']) ? (string)($row[$map['password']] ?? '') : '';
                            $roleRaw = isset($map['role']) ? trim((string)($row[$map['role']] ?? 'student')) : 'student';

                            $roleMap = [
                                'admin'=>'admin','quan tri vien'=>'admin','quản trị viên'=>'admin','super admin'=>'admin','superadmin'=>'admin',
                                'admin_test'=>'admin_test','admintest'=>'admin_test','admin test'=>'admin_test','admin kiểm thử'=>'admin_test',
                                'teacher'=>'teacher','giang vien'=>'teacher','giảng viên'=>'teacher','giaovien'=>'teacher','lecturer'=>'teacher',
                                'student'=>'student','sinh vien'=>'student','sinh viên'=>'student','hoc sinh'=>'student','học sinh'=>'student'
                            ];
                            $roleKey = mb_strtolower($roleRaw, 'UTF-8');
                            $role = $roleMap[$roleKey] ?? (function_exists('normalizeRole') ? normalizeRole($roleRaw) : 'student');
                            if (function_exists('can_assign_role') && !can_assign_role($role)) {
                                $rowErrors[] = "Dòng {$excelRow}: không có quyền tạo vai trò '{$roleRaw}'.";
                                $skipped++; continue;
                            }

                            if ($username === '' || $fullname === '' || $password === '') {
                                $rowErrors[] = "Dòng {$excelRow}: thiếu Tên đăng nhập, Họ và tên hoặc Mật khẩu.";
                                $skipped++; continue;
                            }
                            if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
                                $rowErrors[] = "Dòng {$excelRow}: Tên đăng nhập chỉ được chứa chữ, số, dấu chấm, gạch dưới hoặc gạch ngang.";
                                $skipped++; continue;
                            }
                            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $rowErrors[] = "Dòng {$excelRow}: email không hợp lệ.";
                                $skipped++; continue;
                            }

                            $duplicate = false;
                            foreach ($existingUsers as $eu) {
                                $euUsername = trim((string)($eu['username'] ?? ''));
                                $euName = trim((string)($eu['name'] ?? ''));
                                $euEmail = trim((string)($eu['email'] ?? ''));
                                if (($euUsername !== '' && strcasecmp($euUsername, $username) === 0) ||
                                    ($euName !== '' && strcasecmp($euName, $username) === 0) ||
                                    ($email !== '' && $euEmail !== '' && strcasecmp($euEmail, $email) === 0)) {
                                    $duplicate = true; break;
                                }
                            }
                            if ($duplicate) {
                                $rowErrors[] = "Dòng {$excelRow}: tài khoản '{$username}' hoặc email đã tồn tại.";
                                $skipped++; continue;
                            }

                            $userData = [
                                'username' => $username,
                                'fullname' => $fullname,
                                'email'    => $email,
                                'password' => $password,
                                'role'     => $role,
                                'avatar'   => ''
                            ];

                            try {
                                $result = addUser($userData);
                                if ($result) {
                                    $inserted++;
                                    // Cập nhật cache để chống trùng ngay trong cùng file import.
                                    $existingUsers[] = [
                                        'username'=>$username,
                                        'name'=>$username,
                                        'fullname'=>$fullname,
                                        'email'=>$email
                                    ];
                                } else {
                                    $rowErrors[] = "Dòng {$excelRow}: không thể thêm tài khoản (có thể trùng dữ liệu).";
                                    $skipped++;
                                }
                            } catch (Throwable $ex) {
                                $rowErrors[] = "Dòng {$excelRow}: " . $ex->getMessage();
                                $skipped++;
                            }
                        }
                        $success = "Import hoàn tất: {$inserted} tài khoản thêm mới, {$skipped} dòng bỏ qua.";
                        if (!empty($rowErrors)) {
                            $error = implode(' ', array_slice($rowErrors, 0, 8)) . (count($rowErrors) > 8 ? ' ...' : '');
                        }
                    } else {
                        $error = 'Không tìm thấy hàm addUser() để thêm tài khoản.';
                    }
                }
            }
        }
    }

    // ------------------------------------------------------------------------
    // D. XÓA HÀNG LOẠT NGƯỜI DÙNG
    // ------------------------------------------------------------------------
    elseif ($action === 'bulk_delete') {
        $selected_ids = $_POST['user_ids'] ?? [];
        if (!is_array($selected_ids)) $selected_ids = [];
        $selected_ids = array_values(array_unique(array_filter(array_map('intval', $selected_ids), function($id){ return $id > 0; })));
        $selected_ids = array_values(array_diff($selected_ids, [(int)$current_admin_id]));

        if (empty($selected_ids)) {
            $error = 'Vui lòng chọn ít nhất một tài khoản để xóa. Tài khoản đang đăng nhập không thể bị xóa.';
        } else {
            $deleted = 0;
            $skipped = 0;
            if (function_exists('deleteUser') && function_exists('getUserById')) {
                foreach ($selected_ids as $uid) {
                    if ($uid === (int)$current_admin_id) continue;
                    $target = getUserById($uid);
                    if (!$target || (function_exists('can_manage_user') && !can_manage_user($target, 'delete'))) {
                        $skipped++;
                        continue;
                    }
                    $av = trim((string)($target['avatar'] ?? ''));
                    if ($av !== '' && !preg_match('/^https?:\/\//i', $av)) {
                        $avatar_file = __DIR__ . '/../' . ltrim($av, '/');
                        if (file_exists($avatar_file) && is_file($avatar_file)) @unlink($avatar_file);
                    }
                    try {
                        if (deleteUser($uid)) $deleted++;
                        else $skipped++;
                    } catch (Throwable $ex) {
                        $skipped++;
                    }
                }
            }
            $success = "Đã xóa {$deleted} tài khoản được chọn." . ($skipped ? " Bỏ qua {$skipped} tài khoản (protected/không đủ quyền)." : '');
        }
    }

    // ------------------------------------------------------------------------
    // E. XÓA NGƯỜI DÙNG (DELETE)
    // ------------------------------------------------------------------------
    elseif ($action === 'delete') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $target  = function_exists('getUserById') ? getUserById($user_id) : null;

        if ($user_id <= 0 || !$target) {
            $error = 'Yêu cầu xóa không hợp lệ!';
        } elseif ($user_id === $current_admin_id) {
            $error = 'Bạn không thể tự xóa tài khoản đang đăng nhập!';
        } elseif (function_exists('can_manage_user') && !can_manage_user($target, 'delete')) {
            $error = 'Không thể xóa tài khoản được bảo vệ hoặc Super Admin!';
        } else {
            $av = trim($target['avatar'] ?? '');
            if (!empty($av) && !preg_match('/^https?:\/\//i', $av)) {
                $file_to_delete = __DIR__ . '/../' . ltrim($av, '/');
                if (file_exists($file_to_delete) && is_file($file_to_delete)) {
                    @unlink($file_to_delete);
                }
            }

            if (function_exists('deleteUser') && deleteUser($user_id)) {
                $success = 'Đã xóa tài khoản thành công!';
            } else {
                $error = 'Xóa thất bại hoặc không đủ quyền.';
            }
        }
    }
}

// ============================================================================
// 2. LẤY DANH SÁCH & LỌC DỮ LIỆU (READ)
// ============================================================================
$users_raw = function_exists('getUsers') ? getUsers() : [];
$users     = is_array($users_raw) ? $users_raw : [];

$search      = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_role = isset($_GET['role']) ? trim($_GET['role']) : '';

if ($search !== '' || $filter_role !== '') {
    $users = array_filter($users, function($u) use ($search, $filter_role) {
        $match_q = true;
        $match_r = true;

        if ($search !== '') {
            $name  = isset($u['fullname']) ? $u['fullname'] : ($u['name'] ?? '');
            $email = $u['email'] ?? '';
            $uname = $u['username'] ?? '';
            $match_q = (mb_stripos($name, $search) !== false || mb_stripos($email, $search) !== false || mb_stripos($uname, $search) !== false);
        }

        if ($filter_role !== '') {
            $role_info = getUserRoleInfo($u['role'] ?? 'student');
            $match_r = ($role_info['code'] === $filter_role);
        }

        return $match_q && $match_r;
    });
}

// Pagination logic
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($limit, [10, 25, 50, 100])) $limit = 10;
$total_records = count($users);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$start = ($current_page - 1) * $limit;
$users_paginated = array_slice($users, $start, $limit);

include '../includes/header_admin.php';
?>

<style>
  .users-wrapper {
    animation: fadeIn 0.35s ease-in-out;
  }

  .creative-table-card {
    border-radius: 1.25rem !important;
    overflow: hidden;
  }

  .creative-table {
    border-collapse: separate;
    border-spacing: 0 0.5rem;
  }

  .creative-table thead th {
    border: none;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.06em;
    color: #64748b;
    padding: 0.85rem 1rem;
    background: transparent;
  }

  .creative-table tbody tr {
    background: #ffffff;
    transition: all 0.25s ease;
  }

  .creative-table tbody tr td {
    border: none;
    padding: 0.85rem 1rem;
    background: #f8fafc;
  }

  .creative-table tbody tr td:first-child {
    border-top-left-radius: 0.85rem;
    border-bottom-left-radius: 0.85rem;
  }

  .creative-table tbody tr td:last-child {
    border-top-right-radius: 0.85rem;
    border-bottom-right-radius: 0.85rem;
  }

  .creative-table tbody tr:hover td {
    background: #f1f5f9;
  }

  .avatar-cell-wrapper {
    position: relative;
    width: 44px;
    height: 44px;
    cursor: pointer;
    border-radius: 50%;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .avatar-cell-wrapper:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(124, 58, 237, 0.35);
  }

  .avatar-img-table {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 2px solid #a855f7;
    border-radius: 50%;
  }

  .avatar-overlay-icon {
    position: absolute;
    inset: 0;
    background: rgba(124, 58, 237, 0.65);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
    font-size: 0.9rem;
    border-radius: 50%;
  }

  .avatar-cell-wrapper:hover .avatar-overlay-icon {
    opacity: 1;
  }

  .avatar-initial-table {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a855f7, #6d28d9);
    color: #ffffff;
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s ease;
  }

  .avatar-initial-table:hover {
    transform: scale(1.1);
  }

  .search-box-group .form-control,
  .search-box-group .form-select {
    border-radius: 0.75rem;
  }

  .action-btn-circle {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
  }
  .action-btn-circle:hover {
    transform: scale(1.12);
  }


  /* ===== Đồng bộ giao diện Admin: Rooms / Exams / Subjects ===== */
  .users-wrapper .admin-page-head { border:0!important; border-radius:1.25rem!important; overflow:hidden; box-shadow:0 .35rem 1rem rgba(46,16,101,.12)!important; }
  .users-wrapper .creative-table-card { border:1px solid #eef2f7!important; border-radius:1.25rem!important; box-shadow:0 8px 26px rgba(15,23,42,.055)!important; }
  .users-wrapper .creative-table { border-collapse:separate; border-spacing:0 .5rem; }
  .users-wrapper .creative-table tbody td { background:#f8fafc; padding:.9rem 1rem; }
  .users-wrapper .creative-table tbody tr:hover td { background:#f1f5f9; }
  .users-wrapper .action-btn-circle { width:34px; height:34px; border-radius:.55rem!important; border:1px solid #e9edf4!important; background:#fff!important; box-shadow:0 2px 8px rgba(15,23,42,.04); }
  .users-wrapper .action-btn-circle:hover { transform:translateY(-1px) scale(1.04); }
  .users-wrapper .toolbar-action-btn { height:40px; border-radius:.65rem; font-weight:600; white-space:nowrap; }
  .users-wrapper .btn-import { border-color:#16a34a; color:#15803d; background:#fff; }
  .users-wrapper .btn-import:hover { background:#f0fdf4; color:#166534; border-color:#16a34a; }
  .users-wrapper .form-control:focus, .users-wrapper .form-select:focus { border-color:#a78bfa!important; box-shadow:0 0 0 .2rem rgba(124,58,237,.10)!important; }
  .qt-modern-modal .modal-content { border:0!important; border-radius:1.25rem!important; overflow:hidden; box-shadow:0 24px 60px rgba(15,23,42,.18)!important; }
  .qt-modern-modal .modal-header { padding:1rem 1.25rem!important; border-bottom:1px solid #eef2f7!important; background:#fff!important; color:#0f172a!important; }
  .qt-modern-modal .modal-title { color:#0f172a!important; font-weight:700!important; }
  .qt-modern-modal .modal-body { padding:1.25rem!important; background:#fff!important; }
  .qt-modern-modal .modal-footer { padding:1rem 1.25rem!important; border-top:1px solid #eef2f7!important; background:#fff!important; }
  .qt-modern-modal .form-control, .qt-modern-modal .form-select { min-height:44px; border-radius:.7rem!important; border-color:#e2e8f0; }
  .qt-modern-modal .btn-close { filter:none!important; }
  .qt-import-modal .modal-dialog { max-width:760px; }
  .qt-import-modal .import-dropzone { border:2px dashed #cbd5e1; border-radius:1rem; padding:1.1rem; background:#f8fafc; }
  @media(max-width:767.98px){ .users-wrapper .admin-page-head .card-body{align-items:stretch!important}.users-wrapper .admin-head-actions{width:100%}.users-wrapper .admin-head-actions .btn{flex:1 1 auto}.qt-modern-modal .modal-dialog{margin:.75rem} }


  .users-wrapper .min-w-0 { min-width: 0; }
  .users-wrapper .avatar-cell-wrapper, .users-wrapper .avatar-initial-table { width: 42px; height: 42px; }
</style>

<div class="container-fluid px-0 users-wrapper">

    <!-- Header Banner -->
    <div class="card border-0 text-white mb-4 shadow-sm admin-page-head">
      <div class="card-body p-3 p-md-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
          <h3 class="fw-bold mb-1 fs-4"><i class="bi bi-people-fill me-2"></i>Quản lý người dùng</h3>
          <div class="text-white-50 small"><?= $total_records ?> tài khoản</div>
        </div>
        <div class="d-flex gap-2 flex-wrap admin-head-actions">
          <button type="button" class="btn btn-light text-success toolbar-action-btn btn-import px-3" data-bs-toggle="modal" data-bs-target="#modalImportUser">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Excel/CSV
          </button>
          <button type="button" class="btn btn-light text-primary toolbar-action-btn px-3" data-bs-toggle="modal" data-bs-target="#modalAddUser">
            <i class="bi bi-person-plus-fill me-1"></i> Thêm tài khoản
          </button>
        </div>
      </div>
    </div>

  <!-- Thông báo Lỗi / Thành công -->
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> <?= e($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php
  $dt_config = [
      'search_placeholder' => 'Tìm theo tên, email, tài khoản...',
      'filters' => [
          [
              'name' => 'role',
              'options' => [
                  'student' => 'Học sinh / Sinh viên',
                  'teacher' => 'Giảng viên',
                  'admin_test' => 'Admin kiểm thử',
                  'admin' => 'Super Admin'
              ],
              'selected' => $filter_role,
              'default_label' => '-- Tất cả vai trò --'
          ]
      ],
      'bulk_action' => 'bulk_delete',
      'bulk_action_url' => 'users.php',
      'custom_actions' => '',
      'columns' => [
          ['label' => 'TÀI KHOẢN', 'key' => 'account_cell', 'width' => '390px'],
          ['label' => 'VAI TRÒ', 'key' => 'role_cell'],
          ['label' => 'TRẠNG THÁI', 'key' => 'status_cell', 'align' => 'center'],
          ['label' => 'THAO TÁC', 'key' => 'actions_cell', 'align' => 'end']
      ],
      'row_renderer' => function($row, $col_key) use ($current_admin_id) {
          $uid = (int)($row['id'] ?? 0);

          /*
           * getUsers() ở một số phiên bản chỉ trả các cột dùng cho danh sách.
           * Lấy thêm bản ghi chi tiết một lần/user để avatar Profile và bảng Users
           * luôn dùng cùng dữ liệu users.avatar.
           */
          static $detail_cache = [];
          $detail = [];
          if ($uid > 0 && function_exists('getUserById')) {
              if (!array_key_exists($uid, $detail_cache)) {
                  $tmp = getUserById($uid);
                  $detail_cache[$uid] = is_array($tmp) ? $tmp : [];
              }
              $detail = $detail_cache[$uid];
          }

          // Tên tài khoản: hỗ trợ cả schema username và schema cũ dùng name.
          $username_val = trim((string)(
              $row['username']
              ?? $detail['username']
              ?? $row['name']
              ?? $detail['name']
              ?? ''
          ));

          $email_val = trim((string)(
              $row['email']
              ?? $detail['email']
              ?? ''
          ));

          $user_disp_name = trim((string)(
              $row['fullname']
              ?? $detail['fullname']
              ?? $row['full_name']
              ?? $detail['full_name']
              ?? ''
          ));

          // Không còn hiển thị @N/A.
          if ($username_val === '') {
              $username_val = $email_val !== '' ? $email_val : ('user_' . $uid);
          }
          if ($user_disp_name === '') {
              $user_disp_name = $username_val;
          }
          if ($email_val === '') {
              $email_val = 'Chưa cập nhật email';
          }

          $initial_char = mb_strtoupper(mb_substr($username_val, 0, 1, 'UTF-8'), 'UTF-8');
          $role_info = getUserRoleInfo($row['role'] ?? ($detail['role'] ?? 'student'));
          $is_protected = !empty($row['is_protected'] ?? $detail['is_protected'] ?? false);
          $can_edit = !function_exists('can_manage_user') || can_manage_user($row, 'edit');
          $can_delete = (!function_exists('can_manage_user') || can_manage_user($row, 'delete')) && $uid !== (int)$current_admin_id;

          // ===== AVATAR DÙNG CHUNG PROFILE / HEADER / USERS TABLE =====
          $has_avatar = false;
          $avatar_url = '';
          $raw_avatar = trim((string)(
              $row['avatar']
              ?? $detail['avatar']
              ?? $row['avatar_url']
              ?? $detail['avatar_url']
              ?? $row['profile_image']
              ?? $detail['profile_image']
              ?? $row['profile_picture']
              ?? $detail['profile_picture']
              ?? ''
          ));

          if ($raw_avatar !== '') {
              if (preg_match('#^https?://#i', $raw_avatar)) {
                  $has_avatar = true;
                  $avatar_url = $raw_avatar;
              } else {
                  $clean_path = str_replace('\\', '/', $raw_avatar);
                  while (strpos($clean_path, '../') === 0) {
                      $clean_path = substr($clean_path, 3);
                  }
                  $clean_path = ltrim($clean_path, '/');

                  $candidate_paths = [$clean_path];
                  if (strpos($clean_path, 'uploads/') !== 0) {
                      $candidate_paths[] = 'uploads/' . $clean_path;
                  }
                  if (strpos($clean_path, 'uploads/avatars/') !== 0) {
                      $candidate_paths[] = 'uploads/avatars/' . basename($clean_path);
                  }
                  $candidate_paths = array_values(array_unique(array_filter($candidate_paths)));

                  foreach ($candidate_paths as $candidate) {
                      $server_file_path = dirname(__DIR__) . '/' . $candidate;
                      if (is_file($server_file_path)) {
                          $has_avatar = true;
                          $avatar_url = '../' . $candidate . '?v=' . filemtime($server_file_path);
                          break;
                      }
                  }
              }
          }

          switch ($col_key) {
              case 'account_cell':
                  $avatar_html = $has_avatar
                      ? '<div class="avatar-cell-wrapper flex-shrink-0" data-bs-toggle="modal" data-bs-target="#modalViewAvatar" data-name="'.e($user_disp_name).'" data-username="'.e($username_val).'" data-email="'.e($email_val).'" data-role="'.e($role_info['label']).'" data-avatar="'.e($avatar_url).'" data-has-avatar="true" data-initial="'.e($initial_char).'" title="Xem ảnh đại diện"><img src="'.e($avatar_url).'" alt="'.e($username_val).'" class="avatar-img-table"><div class="avatar-overlay-icon"><i class="bi bi-zoom-in"></i></div></div>'
                      : '<div class="avatar-initial-table flex-shrink-0" data-bs-toggle="modal" data-bs-target="#modalViewAvatar" data-name="'.e($user_disp_name).'" data-username="'.e($username_val).'" data-email="'.e($email_val).'" data-role="'.e($role_info['label']).'" data-avatar="" data-has-avatar="false" data-initial="'.e($initial_char).'" title="Xem thông tin">'.e($initial_char).'</div>';

                  $prot = $is_protected
                      ? ' <span class="badge bg-dark bg-opacity-10 text-dark border rounded-pill ms-1" style="font-size:.62rem">Protected</span>'
                      : '';

                  return '<div class="d-flex align-items-center gap-3">'
                      .$avatar_html.
                      '<div class="min-w-0">'
                      .'<div class="fw-bold text-dark text-truncate">'.e($username_val).$prot.'</div>'
                      .'<div class="small text-muted text-truncate" style="max-width:280px">'.e($email_val).'</div>'
                      .'</div></div>';

              case 'role_cell':
                  return '<span class="badge '.$role_info['bg'].' bg-opacity-10 border border-current rounded-pill px-2.5 py-1" style="font-size:.725rem;">'.e($role_info['label']).'</span>';

              case 'status_cell':
                  $status = strtolower((string)($row['status'] ?? ($detail['status'] ?? 'active')));
                  $statusMap = [
                      'active'   => ['Đang hoạt động', 'success', 'bi-check-circle-fill'],
                      'inactive' => ['Tạm ngưng', 'secondary', 'bi-pause-circle-fill'],
                      'blocked'  => ['Đã khóa', 'danger', 'bi-lock-fill']
                  ];
                  $st = $statusMap[$status] ?? $statusMap['active'];
                  return '<span class="badge bg-'.$st[1].' bg-opacity-10 text-'.$st[1].' border border-'.$st[1].' border-opacity-25 rounded-pill px-2 py-1"><i class="bi '.$st[2].' me-1"></i>'.e($st[0]).'</span>';

              case 'actions_cell':
                  $del_btn = !$can_delete
                      ? '<button type="button" class="btn btn-sm btn-light text-muted action-btn-circle opacity-50" disabled title="Không thể xóa"><i class="bi bi-trash"></i></button>'
                      : '<button type="submit" class="btn btn-sm btn-light text-danger action-btn-circle dt-single-delete" form="form_del_'.$uid.'" title="Xóa tài khoản"><i class="bi bi-trash"></i></button><form id="form_del_'.$uid.'" method="POST" action="users.php" onsubmit="return confirm(\'Bạn có chắc chắn muốn xóa tài khoản '.e($username_val).'? Hành động này không thể hoàn tác!\');" class="d-none"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="'.$uid.'">'.(function_exists('csrf_field') ? '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">' : '').'</form>';

                  $edit_btn = !$can_edit
                      ? '<button type="button" class="btn btn-sm btn-light text-muted action-btn-circle opacity-50" disabled title="Không thể sửa (protected)"><i class="bi bi-pencil-fill"></i></button>'
                      : '<button type="button" class="btn btn-sm btn-light text-primary action-btn-circle btn-edit-user" data-bs-toggle="modal" data-bs-target="#modalEditUser" data-id="'.$uid.'" data-username="'.e($username_val).'" data-fullname="'.e($user_disp_name).'" data-email="'.e($email_val === 'Chưa cập nhật email' ? '' : $email_val).'" data-role="'.e($role_info['code']).'" data-status="'.e($row['status'] ?? ($detail['status'] ?? 'active')).'" data-protected="'.($is_protected ? '1' : '0').'" data-avatar="'.e($avatar_url).'" data-has-avatar="'.($has_avatar ? 'true' : 'false').'" title="Chỉnh sửa"><i class="bi bi-pencil-fill"></i></button>';

                  return '<div class="d-flex align-items-center justify-content-end gap-1">'
                      .'<button type="button" class="btn btn-sm btn-light action-btn-circle" style="color:#7c3aed;background-color:#f3e8ff" data-bs-toggle="modal" data-bs-target="#modalViewAvatar" data-name="'.e($user_disp_name).'" data-username="'.e($username_val).'" data-email="'.e($email_val).'" data-role="'.e($role_info['label']).'" data-avatar="'.e($avatar_url).'" data-has-avatar="'.($has_avatar ? 'true' : 'false').'" data-initial="'.e($initial_char).'" title="Xem chi tiết"><i class="bi bi-eye-fill"></i></button>'
                      .$edit_btn.$del_btn.'</div>';
          }

          return '';
      }
  ];

  render_datatable($dt_config, $users_paginated, $total_records, $current_page, $limit);
  ?>

</div>

<!-- ============================================================================ -->
<!-- MODAL 1: XEM CHI TIẾT AVATAR & THÔNG TIN -->
<!-- ============================================================================ -->
<div class="modal fade" id="modalViewAvatar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered qt-modern-modal">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem; overflow: hidden;">
      <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%);">
        <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
          <i class="bi bi-image text-warning"></i> Chi tiết ảnh đại diện
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-center bg-light">
        <div id="avatarModalImgContainer" class="d-flex align-items-center justify-content-center my-2" style="min-height: 220px;"></div>
        <div class="mt-3 pt-3 border-top">
          <h5 class="fw-bold text-dark mb-0" id="avatarModalName">--</h5>
          <div class="text-muted small mb-2" id="avatarModalUsername">@--</div>
          <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mt-2">
            <span class="badge rounded-pill px-3 py-1.5" style="background: #f3e8ff; color: #6b21a8; font-weight: 600;" id="avatarModalRole">--</span>
            <span class="text-muted small" id="avatarModalEmail"><i class="bi bi-envelope me-1"></i>--</span>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 bg-white justify-content-between py-3 px-4">
        <div id="avatarOpenOriginalBtn"></div>
        <button type="button" class="btn btn-secondary rounded-3 px-4 fw-semibold ms-auto" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================================ -->
<!-- MODAL 2: THÊM NGƯỜI DÙNG MỚI (CREATE) -->
<!-- ============================================================================ -->
<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered qt-modern-modal">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
      <div class="modal-header border-0 pb-0 pt-4 px-4">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus text-primary me-2"></i>Thêm người dùng mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="users.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <?php if (function_exists('csrf_field')) csrf_field(); ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Tên đăng nhập <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control rounded-3" placeholder="vd: nguyenvana" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" name="fullname" class="form-control rounded-3" placeholder="vd: Nguyễn Văn A" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Địa chỉ Email</label>
            <input type="email" name="email" class="form-control rounded-3" placeholder="vd: nva@gmail.com">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control rounded-3" placeholder="Nhập mật khẩu..." required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Vai trò hệ thống</label>
            <select name="role" class="form-select rounded-3">
              <?php foreach ($assignable_roles as $rval => $rlabel): ?>
                <option value="<?= e($rval) ?>" <?= $rval === 'student' ? 'selected' : '' ?>><?= e($rlabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Ảnh đại diện (Tùy chọn)</label>
            <input type="file" name="avatar" class="form-control rounded-3" accept="image/*">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
          <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Thêm tài khoản</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================================================ -->
<!-- MODAL 3: SỬA TÀI KHOẢN NGƯỜI DÙNG (UPDATE) -->
<!-- ============================================================================ -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered qt-modern-modal">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
      <div class="modal-header border-0 pb-0 pt-4 px-4">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Chỉnh sửa tài khoản</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="users.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="user_id" id="edit_user_id" value="0">
        <?php if (function_exists('csrf_field')) csrf_field(); ?>
        
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Tên đăng nhập</label>
            <input type="text" id="edit_username" class="form-control rounded-3 bg-light" readonly disabled>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" name="fullname" id="edit_fullname" class="form-control rounded-3" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Địa chỉ Email</label>
            <input type="email" name="email" id="edit_email" class="form-control rounded-3">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Đổi Mật khẩu</label>
            <input type="password" name="password" class="form-control rounded-3" placeholder="Bỏ trống nếu giữ nguyên mật khẩu cũ">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Vai trò hệ thống</label>
            <select name="role" id="edit_role" class="form-select rounded-3">
              <?php foreach ($assignable_roles as $rval => $rlabel): ?>
                <option value="<?= e($rval) ?>"><?= e($rlabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Trạng thái</label>
            <select name="status" id="edit_status" class="form-select rounded-3">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="blocked">Blocked</option>
            </select>
          </div>

          <?php if ($actor_is_super): ?>
          <div class="mb-3 form-check">
            <input class="form-check-input" type="checkbox" name="is_protected" value="1" id="edit_is_protected">
            <label class="form-check-label small" for="edit_is_protected">Tài khoản được bảo vệ (is_protected)</label>
          </div>
          <?php endif; ?>

          <!-- Preview & Đổi Avatar trong Edit Modal -->
          <div class="mb-3 pt-2 border-top">
            <label class="form-label small fw-bold text-secondary d-block">Ảnh đại diện</label>
            <div id="edit_avatar_preview" class="mb-2"></div>
            <input type="file" name="avatar" class="form-control rounded-3" accept="image/*">
            <div class="form-check mt-2" id="edit_remove_avatar_box" style="display: none;">
              <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="remove_avatar_check">
              <label class="form-check-label text-danger small" for="remove_avatar_check">
                Xóa ảnh đại diện hiện tại
              </label>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
          <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================================================ -->
<!-- MODAL IMPORT NGƯỜI DÙNG -->
<!-- ============================================================================ -->
<div class="modal fade" id="modalImportUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered qt-modern-modal qt-import-modal">
    <div class="modal-content border-0 shadow-lg" style="border-radius:1.25rem;">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import tài khoản</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="users.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="import_users">
        <div class="modal-body p-4">
          <div class="alert alert-warning border-0 rounded-3 small mb-3">
            <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>Lưu ý khi Import</div>
            <ul class="mb-0 ps-3">
              <li>File phải đúng định dạng <strong>.xlsx</strong> hoặc <strong>.csv</strong>.</li>
              <li>Các cột bắt buộc: <strong>Tên đăng nhập, Họ và tên, Mật khẩu</strong>.</li>
              <li>Cột tùy chọn: <strong>Email, Vai trò, Trạng thái</strong>.</li>
              <li>Không đổi tên hoặc xóa dòng tiêu đề; mỗi dòng dữ liệu tương ứng một tài khoản.</li>
              <li>Tên đăng nhập chỉ gồm chữ, số, dấu chấm, gạch dưới hoặc gạch ngang.</li>
              <li>Email nếu nhập phải đúng định dạng; tài khoản/email đã tồn tại sẽ được bỏ qua.</li>
            </ul>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold text-dark"><i class="bi bi-table me-1"></i>Mẫu dữ liệu Excel</div>
            <a href="../assets/mau_import_tai_khoan_quiztech.xlsx" download class="btn btn-sm btn-outline-success rounded-3 fw-semibold">
              <i class="bi bi-download me-1"></i>Tải mẫu Excel
            </a>
          </div>

          <div class="table-responsive border rounded-3 mb-3">
            <table class="table table-sm table-bordered align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Tên đăng nhập</th>
                  <th>Họ và tên</th>
                  <th>Mật khẩu</th>
                  <th>Email</th>
                  <th>Vai trò</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>nguyenvana</td>
                  <td>Nguyễn Văn A</td>
                  <td>Abc12345</td>
                  <td>nguyenvana@gmail.com</td>
                  <td>student</td>
                  <td>active</td>
                </tr>
                <tr>
                  <td>tranthib</td>
                  <td>Trần Thị B</td>
                  <td>Bcd12345</td>
                  <td>tranthib@gmail.com</td>
                  <td>teacher</td>
                  <td>active</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="small text-muted mb-2">
            <strong>Giá trị hợp lệ:</strong> Vai trò = <code>student</code>, <code>teacher</code>, <code>admin</code> ·
            Trạng thái = <code>active</code>, <code>inactive</code>, <code>blocked</code>.
          </div>

          <label class="form-label fw-semibold">Chọn file dữ liệu</label>
          <input type="file" name="import_file" class="form-control rounded-3 import-dropzone" accept=".csv,.xlsx" required>
          <div class="form-text">Khuyến nghị tải mẫu Excel ở trên, điền dữ liệu rồi lưu lại dưới dạng .xlsx để hạn chế sai định dạng cột.</div>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light rounded-3 fw-semibold" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary rounded-3 fw-semibold"><i class="bi bi-upload me-1"></i>Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="bulkDeleteForm" method="POST" action="users.php" class="d-none">
  <input type="hidden" name="action" value="bulk_delete">
</form>

<!-- ============================================================================ -->
<!-- SCRIPTS XỬ LÝ DỮ LIỆU ĐỘNG MODALS -->
<!-- ============================================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  
  // 1. Modal Xem Avatar Phóng To
  const modalViewAvatar = document.getElementById('modalViewAvatar');
  if (modalViewAvatar) {
    modalViewAvatar.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (!button) return;

      const name = button.getAttribute('data-name') || 'N/A';
      const username = button.getAttribute('data-username') || 'N/A';
      const email = button.getAttribute('data-email') || 'Chưa cập nhật';
      const role = button.getAttribute('data-role') || 'N/A';
      const avatarSrc = button.getAttribute('data-avatar') || '';
      const hasAvatar = button.getAttribute('data-has-avatar') === 'true';
      const initial = button.getAttribute('data-initial') || 'U';

      document.getElementById('avatarModalName').textContent = name;
      document.getElementById('avatarModalUsername').textContent = '@' + username;
      document.getElementById('avatarModalEmail').innerHTML = '<i class="bi bi-envelope me-1"></i>' + email;
      document.getElementById('avatarModalRole').textContent = role;

      const imgContainer = document.getElementById('avatarModalImgContainer');
      const openBtnContainer = document.getElementById('avatarOpenOriginalBtn');

      if (hasAvatar && avatarSrc) {
        imgContainer.innerHTML = `
          <div class="position-relative d-inline-block">
            <img src="${avatarSrc}" class="img-fluid rounded-4 shadow-lg border border-3 border-white" style="max-height: 290px; width: auto; object-fit: contain;">
          </div>
        `;
        openBtnContainer.innerHTML = `
          <a href="${avatarSrc}" target="_blank" class="btn btn-sm btn-outline-primary rounded-3 fw-semibold">
            <i class="bi bi-box-arrow-up-right me-1"></i> Xem ảnh gốc
          </a>
        `;
      } else {
        imgContainer.innerHTML = `
          <div class="d-flex flex-column align-items-center py-2">
            <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow" 
                 style="width: 110px; height: 110px; font-size: 2.8rem; background: linear-gradient(135deg, #a855f7, #6d28d9);">
              ${initial}
            </div>
            <p class="text-muted mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Tài khoản này chưa cập nhật ảnh đại diện.</p>
          </div>
        `;
        openBtnContainer.innerHTML = '';
      }
    });
  }

  // 2. Modal Chỉnh Sửa Người Dùng (Edit User Modal)
  const modalEditUser = document.getElementById('modalEditUser');
  if (modalEditUser) {
    modalEditUser.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (!button) return;

      const id = button.getAttribute('data-id') || '0';
      const username = button.getAttribute('data-username') || '';
      const fullname = button.getAttribute('data-fullname') || '';
      const email = button.getAttribute('data-email') || '';
      const role = button.getAttribute('data-role') || 'student';
      const status = button.getAttribute('data-status') || 'active';
      const isProtected = button.getAttribute('data-protected') === '1';
      const avatarSrc = button.getAttribute('data-avatar') || '';
      const hasAvatar = button.getAttribute('data-has-avatar') === 'true';

      document.getElementById('edit_user_id').value = id;
      document.getElementById('edit_username').value = '@' + username;
      document.getElementById('edit_fullname').value = fullname;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_role').value = role;
      const statusEl = document.getElementById('edit_status');
      if (statusEl) statusEl.value = status;
      const protEl = document.getElementById('edit_is_protected');
      if (protEl) protEl.checked = isProtected;

      const previewBox = document.getElementById('edit_avatar_preview');
      const removeBox = document.getElementById('edit_remove_avatar_box');
      document.getElementById('remove_avatar_check').checked = false;

      if (hasAvatar && avatarSrc) {
        previewBox.innerHTML = `
          <div class="d-flex align-items-center gap-3 bg-light p-2 rounded-3 border">
            <img src="${avatarSrc}" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover;">
            <span class="small text-muted">Ảnh hiện tại trên hệ thống</span>
          </div>
        `;
        removeBox.style.display = 'block';
      } else {
        previewBox.innerHTML = '<span class="small text-muted">Chưa có ảnh đại diện</span>';
        removeBox.style.display = 'none';
      }
    });
  }

});

</script>

</main>

<?php include '../includes/components/import_modal.php'; ?>
<?php include '../includes/footer.php'; ?>  