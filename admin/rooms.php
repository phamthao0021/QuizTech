<?php
// admin/rooms.php - Quản lý phòng thi toàn hệ thống
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (function_exists('requireAdmin')) requireAdmin();
else requireRole('admin');

$page_title = 'Quản lý phòng thi';

// Đồng bộ các phòng cũ bị thiếu teacher_id theo giáo viên sở hữu đề thi.
try {
    $pdo->exec("UPDATE exam_rooms r
        INNER JOIN exams e ON e.id = r.exam_id
        SET r.teacher_id = e.teacher_id, r.updated_at = NOW()
        WHERE (r.teacher_id IS NULL OR r.teacher_id = 0)
          AND e.teacher_id IS NOT NULL");
} catch (Throwable $e) {
    // Không chặn trang nếu DB không hỗ trợ UPDATE JOIN.
}

function adminRoomCodeExists($pdo, $code, $id = 0)
{
    if ($id > 0) {
        $s = $pdo->prepare("SELECT id FROM exam_rooms WHERE room_code=? AND id<>? LIMIT 1");
        $s->execute([$code, $id]);
    } else {
        $s = $pdo->prepare("SELECT id FROM exam_rooms WHERE room_code=? LIMIT 1");
        $s->execute([$code]);
    }
    return (bool)$s->fetchColumn();
}
function adminGetSubjectAbbr(PDO $pdo, int $subjectId): string
{
    $stmt = $pdo->prepare("SELECT code, name, subject_name FROM subjects WHERE id = ? LIMIT 1");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $raw = strtoupper(trim((string)($subject['code'] ?? '')));
    $name = strtoupper(trim((string)($subject['name'] ?? ($subject['subject_name'] ?? ''))));

    $map = [
        'CSDL' => 'CSDL',
        'CƠ SỞ DỮ LIỆU' => 'CSDL',
        'CO SO DU LIEU' => 'CSDL',
        'MMT' => 'MMT',
        'MẠNG MÁY TÍNH' => 'MMT',
        'MANG MAY TINH' => 'MMT',
        'KYLT' => 'KYLT',
        'KỸ THUẬT LẬP TRÌNH' => 'KYLT',
        'KY THUAT LAP TRINH' => 'KYLT',
        'JAVA' => 'JAVA',
        'LẬP TRÌNH JAVA' => 'JAVA',
        'LAP TRINH JAVA' => 'JAVA',
        'PHP' => 'PHP',
        'LẬP TRÌNH PHP' => 'PHP',
        'LAP TRINH PHP' => 'PHP',
        'HTML' => 'HTML',
        'CSS' => 'CSS',
        'JAVASCRIPT' => 'JS',
        'JS' => 'JS',
        'PYTHON' => 'PY',
        'LẬP TRÌNH PYTHON' => 'PY',
        'LAP TRINH PYTHON' => 'PY',
        'CTDL' => 'CTDL',
        'CẤU TRÚC DỮ LIỆU' => 'CTDL',
        'CAU TRUC DU LIEU' => 'CTDL',
        'OOP' => 'OOP',
        'LẬP TRÌNH HƯỚNG ĐỐI TƯỢNG' => 'OOP',
        'LAP TRINH HUONG DOI TUONG' => 'OOP',
        'TESTING' => 'TEST',
        'SOFTWARE TESTING' => 'TEST',
        'KIỂM THỬ PHẦN MỀM' => 'TEST',
        'KIEM THU PHAN MEM' => 'TEST',
    ];

    if (isset($map[$raw])) return $map[$raw];
    if (isset($map[$name])) return $map[$name];

    // Nếu mã môn đã có dạng viết tắt, ưu tiên dùng mã đó.
    if ($raw !== '' && preg_match('/^[A-Z0-9]{2,8}$/', $raw)) return $raw;

    // Fallback: lấy chữ cái đầu của các từ trong tên môn, tối đa 6 ký tự.
    $words = preg_split('/[^A-Z0-9À-ỸĐ]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    $abbr = '';
    foreach ($words as $word) {
        $first = function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
        $abbr .= $first;
    }
    $abbr = strtoupper(preg_replace('/[^A-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $abbr) ?: $abbr));
    return substr($abbr !== '' ? $abbr : 'MON', 0, 6);
}

function adminGenerateRoomCode(PDO $pdo, int $subjectId): string
{
    $year = date('y');
    $abbr = adminGetSubjectAbbr($pdo, $subjectId);
    $prefix = $year . $abbr;

    $stmt = $pdo->prepare("SELECT room_code FROM exam_rooms WHERE room_code LIKE ? ORDER BY room_code DESC");
    $stmt->execute([$prefix . '%']);
    $used = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $code) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\\d{3})$/', (string)$code, $m)) {
            $used[(int)$m[1]] = true;
        }
    }

    for ($i = 1; $i <= 999; $i++) {
        if (!isset($used[$i])) {
            $code = $prefix . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            if (!adminRoomCodeExists($pdo, $code)) return $code;
        }
    }
    throw new Exception('Mã phòng của môn ' . $abbr . ' trong năm ' . $year . ' đã đạt giới hạn 999 phòng.');
}
function adminRoomStatus($s)
{
    switch ($s) {
        case 'running':
            return ['Đang diễn ra', 'success', 'bi-play-circle-fill'];
        case 'finished':
            return ['Đã kết thúc', 'secondary', 'bi-check-circle-fill'];
        case 'cancelled':
            return ['Đã hủy', 'danger', 'bi-x-circle-fill'];
        default:
            return ['Chờ bắt đầu', 'warning', 'bi-hourglass-split'];
    }
}

if (isset($_GET['api']) && $_GET['api'] === 'get_room_results') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['room_id'] ?? 0);
    $data = [];
    if ($id > 0) {
        try {
            $s = $pdo->prepare("SELECT ea.id,ea.room_id,ea.student_id AS user_id,u.name,u.email,ea.score,ea.duration_seconds AS time_spent,ea.total_questions,ea.correct_answers,ea.wrong_answers,ea.unanswered,ea.percentage,ea.status,ea.started_at,ea.submitted_at FROM exam_attempts ea JOIN users u ON u.id=ea.student_id WHERE ea.room_id=? ORDER BY ea.score DESC,ea.duration_seconds ASC");
            $s->execute([$id]);
            $data = $s->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
        }
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}


/**
 * Import phòng thi từ CSV/XLSX.
 * Header hỗ trợ:
 * room_name, exam_code/exam_id, teacher_email/teacher_id,
 * max_students, start_time, end_time, room_password, status,
 * description, allow_late_join, late_minutes, auto_start, auto_close.
 */
function adminNormalizeImportHeader($value)
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value));
    if (function_exists('mb_strtolower')) $value = mb_strtolower($value, 'UTF-8');
    $value = strtr($value, [
        'ă'=>'a','â'=>'a','á'=>'a','à'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'ê'=>'e','é'=>'e','è'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ô'=>'o','ơ'=>'o','ó'=>'o','ò'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ư'=>'u','ú'=>'u','ù'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'í'=>'i','ì'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i','ý'=>'y','ỳ'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y','đ'=>'d'
    ]);
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

function adminReadRoomImportFile(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception('Vui lòng chọn file CSV hoặc XLSX hợp lệ.');
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $rows = [];

    if ($ext === 'csv') {
        $handle = fopen($file['tmp_name'], 'rb');
        if (!$handle) throw new Exception('Không thể đọc file CSV.');

        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            return [];
        }
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($handle);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (isset($row[0])) $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$row[0]);
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    if ($ext !== 'xlsx') {
        throw new Exception('Chỉ hỗ trợ file .csv và .xlsx.');
    }
    if (!class_exists('ZipArchive')) {
        throw new Exception('PHP chưa bật ZipArchive nên chưa thể đọc XLSX. Có thể dùng CSV thay thế.');
    }

    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== true) throw new Exception('Không thể mở file XLSX.');

    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = @simplexml_load_string($sharedXml);
        if ($xml) {
            $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($xml->xpath('//a:si') ?: [] as $si) {
                $text = '';
                foreach ($si->xpath('.//a:t') ?: [] as $t) $text .= (string)$t;
                $shared[] = $text;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) throw new Exception('Không tìm thấy sheet đầu tiên trong XLSX.');

    $sheet = @simplexml_load_string($sheetXml);
    if (!$sheet) throw new Exception('File XLSX không hợp lệ.');
    $sheet->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    foreach ($sheet->xpath('//a:sheetData/a:row') ?: [] as $xlsxRow) {
        $out = [];
        foreach ($xlsxRow->xpath('./a:c') ?: [] as $cell) {
            $ref = (string)$cell['r'];
            preg_match('/([A-Z]+)\d+/', $ref, $m);
            $letters = $m[1] ?? 'A';
            $col = 0;
            for ($i = 0; $i < strlen($letters); $i++) $col = $col * 26 + (ord($letters[$i]) - 64);
            $idx = $col - 1;
            while (count($out) <= $idx) $out[] = '';

            $type = (string)$cell['t'];
            $v = '';
            $vNodes = $cell->xpath('./a:v');
            if ($vNodes && isset($vNodes[0])) $v = (string)$vNodes[0];

            if ($type === 's') $v = $shared[(int)$v] ?? '';
            elseif ($type === 'inlineStr') {
                $v = '';
                foreach ($cell->xpath('.//a:t') ?: [] as $t) $v .= (string)$t;
            }
            $out[$idx] = trim((string)$v);
        }
        $rows[] = $out;
    }
    return $rows;
}

function adminImportBool($value): int
{
    $v = strtolower(trim((string)$value));
    return in_array($v, ['1','true','yes','y','co','có','x'], true) ? 1 : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verify_csrf')) verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'import_rooms') {
            $rawRows = adminReadRoomImportFile($_FILES['import_file'] ?? []);
            if (!$rawRows) throw new Exception('File import không có dữ liệu.');

            $headers = array_map('adminNormalizeImportHeader', array_shift($rawRows));
            $aliases = [
                'room_name' => ['room_name','ten_phong','phong','name'],
                'exam_code' => ['exam_code','ma_de','ma_de_thi'],
                'exam_id' => ['exam_id','id_de','id_de_thi'],
                'teacher_email' => ['teacher_email','email_giang_vien','email_gv'],
                'teacher_id' => ['teacher_id','giang_vien_id','id_giang_vien'],
                'max_students' => ['max_students','suc_chua','so_luong','capacity'],
                'start_time' => ['start_time','bat_dau','thoi_gian_bat_dau'],
                'end_time' => ['end_time','ket_thuc','thoi_gian_ket_thuc'],
                'room_password' => ['room_password','mat_khau_phong','password'],
                'status' => ['status','trang_thai'],
                'description' => ['description','mo_ta'],
                'allow_late_join' => ['allow_late_join','cho_phep_vao_tre'],
                'late_minutes' => ['late_minutes','phut_tre'],
                'auto_start' => ['auto_start','tu_dong_bat_dau'],
                'auto_close' => ['auto_close','tu_dong_ket_thuc']
            ];
            $map = [];
            foreach ($aliases as $key => $names) {
                foreach ($names as $name) {
                    $idx = array_search($name, $headers, true);
                    if ($idx !== false) { $map[$key] = $idx; break; }
                }
            }
            if (!isset($map['room_name']) || (!isset($map['exam_code']) && !isset($map['exam_id']))) {
                throw new Exception('File import phải có ít nhất cột room_name (Tên phòng) và exam_code hoặc exam_id.');
            }

            $inserted = 0; $skipped = 0; $errors = [];
            foreach ($rawRows as $i => $row) {
                $excelRow = $i + 2;
                $get = function($key, $default = '') use ($row, $map) {
                    return isset($map[$key]) ? trim((string)($row[$map[$key]] ?? $default)) : $default;
                };
                $roomName = $get('room_name');
                if ($roomName === '') { $skipped++; $errors[] = "Dòng {$excelRow}: thiếu tên phòng."; continue; }

                $exam = null;
                if ($get('exam_id') !== '') {
                    $q = $pdo->prepare("SELECT id,teacher_id,subject_id,title,exam_code FROM exams WHERE id=? LIMIT 1");
                    $q->execute([(int)$get('exam_id')]);
                    $exam = $q->fetch(PDO::FETCH_ASSOC);
                } else {
                    $q = $pdo->prepare("SELECT id,teacher_id,subject_id,title,exam_code FROM exams WHERE exam_code=? LIMIT 1");
                    $q->execute([$get('exam_code')]);
                    $exam = $q->fetch(PDO::FETCH_ASSOC);
                }
                if (!$exam) { $skipped++; $errors[] = "Dòng {$excelRow}: không tìm thấy đề thi."; continue; }

                $teacherId = (int)($exam['teacher_id'] ?? 0);
                if ($get('teacher_id') !== '') $teacherId = (int)$get('teacher_id');
                elseif ($get('teacher_email') !== '') {
                    $q = $pdo->prepare("SELECT id FROM users WHERE role='teacher' AND email=? LIMIT 1");
                    $q->execute([$get('teacher_email')]);
                    $teacherId = (int)$q->fetchColumn();
                }
                if ($teacherId <= 0 || $teacherId !== (int)$exam['teacher_id']) {
                    $skipped++; $errors[] = "Dòng {$excelRow}: giảng viên không hợp lệ hoặc không sở hữu đề thi."; continue;
                }

                $subjectId = (int)($exam['subject_id'] ?? 0);
                if ($subjectId <= 0) { $skipped++; $errors[] = "Dòng {$excelRow}: đề thi chưa có môn học."; continue; }

                $statusRaw = strtolower($get('status', 'waiting'));
                $statusMap = ['cho_bat_dau'=>'waiting','chờ_bắt_đầu'=>'waiting','dang_dien_ra'=>'running','đang_diễn_ra'=>'running','da_ket_thuc'=>'finished','đã_kết_thúc'=>'finished','da_huy'=>'cancelled','đã_hủy'=>'cancelled'];
                $status = $statusMap[$statusRaw] ?? $statusRaw;
                if (!in_array($status, ['waiting','running','finished','cancelled'], true)) $status = 'waiting';

                $max = max(1, min(10000, (int)$get('max_students', '50')));
                $startRaw = $get('start_time');
                $endRaw = $get('end_time');
                $start = $startRaw !== '' && strtotime($startRaw) ? date('Y-m-d H:i:s', strtotime($startRaw)) : date('Y-m-d H:i:s');
                $end = $endRaw !== '' && strtotime($endRaw) ? date('Y-m-d H:i:s', strtotime($endRaw)) : date('Y-m-d H:i:s', strtotime($start . ' +30 minutes'));
                if (strtotime($end) <= strtotime($start)) { $skipped++; $errors[] = "Dòng {$excelRow}: thời gian kết thúc phải sau bắt đầu."; continue; }

                try {
                    $code = adminGenerateRoomCode($pdo, $subjectId);
                    $late = adminImportBool($get('allow_late_join'));
                    $lateMin = max(0, min(1440, (int)$get('late_minutes', '0')));
                    $stmt = $pdo->prepare("INSERT INTO exam_rooms(exam_id,teacher_id,room_code,room_name,room_password,description,max_students,start_time,end_time,allow_late_join,late_minutes,auto_start,auto_close,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
                    $stmt->execute([
                        (int)$exam['id'], $teacherId, $code, $roomName,
                        $get('room_password') !== '' ? $get('room_password') : null,
                        $get('description') !== '' ? $get('description') : null,
                        $max, $start, $end, $late, $late ? $lateMin : 0,
                        adminImportBool($get('auto_start')), adminImportBool($get('auto_close')), $status
                    ]);
                    $inserted++;
                } catch (Throwable $rowEx) {
                    $skipped++;
                    $errors[] = "Dòng {$excelRow}: " . $rowEx->getMessage();
                }
            }
            $message = "Import hoàn tất: {$inserted} phòng được thêm, {$skipped} dòng bỏ qua.";
            if ($errors) $message .= ' ' . implode(' ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' ...' : '');
            setFlash($inserted > 0 ? 'success' : 'warning', $message);
        } elseif (in_array($action, ['create', 'update'], true)) {
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            $exam_id = (int)($_POST['exam_id'] ?? 0);
            $name = trim($_POST['room_name'] ?? '');
            $code = strtoupper(trim($_POST['room_code'] ?? ''));
            $pass = trim($_POST['room_password'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $max = max(1, min(10000, (int)($_POST['max_students'] ?? 50)));
            $start = !empty($_POST['start_time']) ? date('Y-m-d H:i:s', strtotime($_POST['start_time'])) : null;
            $end = !empty($_POST['end_time']) ? date('Y-m-d H:i:s', strtotime($_POST['end_time'])) : null;
            $status = $_POST['status'] ?? 'waiting';
            $late = !empty($_POST['allow_late_join']) ? 1 : 0;
            $lateMin = max(0, min(1440, (int)($_POST['late_minutes'] ?? 0)));
            $autoStart = !empty($_POST['auto_start']) ? 1 : 0;
            $autoClose = !empty($_POST['auto_close']) ? 1 : 0;

            if (!$teacher_id || !$exam_id || $name === '') throw new Exception('Vui lòng nhập đủ tên phòng, giảng viên và đề thi.');
            if (!in_array($status, ['waiting', 'running', 'finished', 'cancelled'], true)) throw new Exception('Trạng thái không hợp lệ.');

            $s = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='teacher' LIMIT 1");
            $s->execute([$teacher_id]);
            if (!$s->fetchColumn()) throw new Exception('Giảng viên không hợp lệ.');

            // Phòng và đề thi phải thuộc cùng một giáo viên để Teacher/Admin nhìn thấy cùng dữ liệu.
            $s = $pdo->prepare("SELECT id,teacher_id,subject_id FROM exams WHERE id=? LIMIT 1");
            $s->execute([$exam_id]);
            $exam = $s->fetch(PDO::FETCH_ASSOC);
            if (!$exam) throw new Exception('Đề thi không hợp lệ.');
            if ((int)$exam['teacher_id'] !== $teacher_id) throw new Exception('Đề thi này không thuộc giảng viên đã chọn. Vui lòng chọn đúng cặp giảng viên - đề thi.');

            if ($start && $end && strtotime($end) <= strtotime($start)) throw new Exception('Thời gian kết thúc phải sau thời gian bắt đầu.');
            if ($start === null) $start = date('Y-m-d H:i:s');
            if ($end === null) $end = date('Y-m-d H:i:s', strtotime($start . ' +30 minutes'));

            if ($action === 'create') {
                $subject_id = (int)($exam['subject_id'] ?? 0);
                if ($subject_id <= 0) throw new Exception('Đề thi chưa được gán môn học nên không thể tạo mã phòng.');
                // Mã phòng tự động: YY + viết tắt môn + số thứ tự 001-999.
                $code = adminGenerateRoomCode($pdo, $subject_id);
                if (!preg_match('/^[A-Z0-9_-]{4,30}$/', $code)) throw new Exception('Mã phòng chỉ được chứa A-Z, 0-9, dấu gạch ngang hoặc gạch dưới (4-30 ký tự).');
                if (adminRoomCodeExists($pdo, $code)) throw new Exception('Mã phòng đã tồn tại.');
                $s = $pdo->prepare("INSERT INTO exam_rooms(exam_id,teacher_id,room_code,room_name,room_password,description,max_students,start_time,end_time,allow_late_join,late_minutes,auto_start,auto_close,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
                $s->execute([$exam_id, $teacher_id, $code, $name, $pass !== '' ? $pass : null, $desc !== '' ? $desc : null, $max, $start, $end, $late, $late ? $lateMin : 0, $autoStart, $autoClose, $status]);
                if ($s->rowCount() !== 1) throw new Exception('Không thể tạo phòng thi.');
                setFlash('success', 'Tạo phòng thi thành công. Mã phòng: ' . $code);
            } else {
                if ($id <= 0) throw new Exception('Phòng thi không hợp lệ.');
                if ($code === '') throw new Exception('Mã phòng không được để trống khi cập nhật.');
                if (!preg_match('/^[A-Z0-9_-]{4,30}$/', $code)) throw new Exception('Mã phòng chỉ được chứa A-Z, 0-9, dấu gạch ngang hoặc gạch dưới (4-30 ký tự).');
                if (adminRoomCodeExists($pdo, $code, $id)) throw new Exception('Mã phòng đã tồn tại.');
                $s = $pdo->prepare("UPDATE exam_rooms SET exam_id=?,teacher_id=?,room_code=?,room_name=?,room_password=?,description=?,max_students=?,start_time=?,end_time=?,allow_late_join=?,late_minutes=?,auto_start=?,auto_close=?,status=?,updated_at=NOW() WHERE id=?");
                $s->execute([$exam_id, $teacher_id, $code, $name, $pass !== '' ? $pass : null, $desc !== '' ? $desc : null, $max, $start, $end, $late, $late ? $lateMin : 0, $autoStart, $autoClose, $status, $id]);
                if ($s->rowCount() === 0) throw new Exception('Không tìm thấy phòng hoặc dữ liệu không thay đổi.');
                setFlash('success', 'Cập nhật phòng thi thành công.');
            }
        } elseif (in_array($action, ['delete', 'bulk_delete'], true)) {
            $ids = [];
            if ($action === 'delete') $ids = $id > 0 ? [$id] : [];
            else $ids = array_values(array_unique(array_filter(array_map('intval', $_POST['ids'] ?? []), static fn($v) => $v > 0)));
            if (!$ids) throw new Exception('Vui lòng chọn ít nhất một phòng thi.');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->beginTransaction();
            // Xóa dữ liệu phụ thuộc trước để không lỗi FK nếu DB chưa bật CASCADE.
            $q = $pdo->prepare("DELETE FROM room_members WHERE room_id IN ($placeholders)");
            $q->execute($ids);
            $q = $pdo->prepare("DELETE FROM exam_attempts WHERE room_id IN ($placeholders)");
            $q->execute($ids);
            $q = $pdo->prepare("DELETE FROM exam_rooms WHERE id IN ($placeholders)");
            $q->execute($ids);
            $deleted = $q->rowCount();
            $pdo->commit();
            if ($deleted === 0) throw new Exception('Không tìm thấy phòng thi để xóa.');
            setFlash('success', $action === 'delete' ? 'Xóa phòng thi thành công.' : 'Đã xóa ' . $deleted . ' phòng thi đã chọn.');
        } elseif ($action === 'set_status' && $id > 0) {
            $status = $_POST['status'] ?? 'waiting';
            if (!in_array($status, ['waiting', 'running', 'finished', 'cancelled'], true)) throw new Exception('Trạng thái không hợp lệ.');
            $s = $pdo->prepare("UPDATE exam_rooms SET status=?,updated_at=NOW() WHERE id=?");
            $s->execute([$status, $id]);
            if ($s->rowCount() === 0) throw new Exception('Không tìm thấy phòng hoặc trạng thái không thay đổi.');
            setFlash('success', 'Đã cập nhật trạng thái phòng.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', $e->getMessage());
    }
    header('Location: rooms.php');
    exit;
}

$teachers = $pdo->query("SELECT id,name,email FROM users WHERE role='teacher' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$exams = $pdo->query("SELECT id,title,exam_code,teacher_id FROM exams ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT r.*,e.title exam_title,e.exam_code,COALESCE(r.teacher_id,e.teacher_id) AS assigned_teacher_id,u.name teacher_name,(SELECT COUNT(*) FROM room_members rm WHERE rm.room_id=r.id) participant_count FROM exam_rooms r LEFT JOIN exams e ON e.id=r.exam_id LEFT JOIN users u ON u.id=COALESCE(r.teacher_id,e.teacher_id) ORDER BY r.created_at DESC,r.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$total = count($rooms);
$waiting = $running = $finished = $cancelled = 0;
foreach ($rooms as $r) {
    if ($r['status'] === 'waiting') $waiting++;
    elseif ($r['status'] === 'running') $running++;
    elseif ($r['status'] === 'finished') $finished++;
    elseif ($r['status'] === 'cancelled') $cancelled++;
}
require_once '../includes/header_admin.php';
?>
<style>
    :root {
        --purple: #7c3aed;
        --dark: #1e1b4b
    }

    .rooms-wrap {
        animation: fadeIn .3s ease
    }
    .hero {
        border-radius: 1.25rem;
        color: #fff;
        box-shadow: 0 10px 28px rgba(76, 29, 149, .16)
    }

    .stat-card,
    .main-card {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .06)
    }

    .breadcrumb-wrap {
        margin-bottom: 1rem
    }

    .breadcrumb {
        margin-bottom: 0;
        font-size: .84rem
    }

    .breadcrumb-item a {
        text-decoration: none;
        color: #6d28d9
    }

    .breadcrumb-item.active {
        color: #64748b
    }

    .stat-card {
        transition: .2s
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 9px 24px rgba(15, 23, 42, .08)
    }

    .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 1.05rem
    }

    .toolbar-row {
        display: flex;
        gap: .65rem;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap
    }

    .filter-control {
        min-width: 0;
        height: 40px;
        border-radius: 9px
    }

    .toolbar-filters {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1 1 auto;
        min-width: 0
    }

    .search-control {
        width: 340px;
        flex: 1 1 340px;
        max-width: 360px
    }

    .status-control {
        width: 175px;
        flex: 0 1 175px
    }

    .exam-control {
        width: 210px;
        flex: 0 1 210px
    }

    .rows-control {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 auto;
        white-space: nowrap
    }

    .rows-control select {
        width: 72px;
        height: 38px;
        border-radius: 9px
    }

    .table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch
    }

    .room-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 1080px
    }

    .room-table thead th {
        border: 0;
        color: #64748b;
        font-size: .72rem;
        letter-spacing: .04em;
        white-space: nowrap
    }

    .room-table tbody td {
        background: #f8fafc;
        border: 0;
        padding: .85rem;
        vertical-align: middle
    }

    .room-table tbody td:first-child {
        border-radius: .8rem 0 0 .8rem
    }

    .room-table tbody td:last-child {
        border-radius: 0 .8rem .8rem 0
    }

    .icon-btn {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .55rem
    }

    .code {
        font-family: monospace;
        font-weight: 700;
        letter-spacing: 1px
    }

    .btn-purple {
        background: var(--purple);
        border-color: var(--purple);
        color: #fff
    }

    .btn-purple:hover {
        background: #6d28d9;
        color: #fff
    }

    .room-row {
        transition: .2s
    }

    .room-row:hover td {
        background: #f1f5f9
    }

    .bulk-selection-bar {
        position: sticky;
        top: 10px;
        z-index: 20;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .9rem;
        padding: .7rem 1rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .06)
    }

    .pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eef2f7
    }

    .pagination {
        margin: 0
    }

    .pagination .page-link {
        border: 0;
        margin: 0 .15rem;
        border-radius: .55rem;
        color: #475569
    }

    .pagination .page-item.active .page-link {
        background: var(--purple);
        color: #fff
    }

    .sort-btn {
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        padding: 0
    }

    .sort-btn:hover {
        color: var(--purple)
    }

    .sort-btn i {
        font-size: .72rem
    }

    .table-empty {
        display: none;
        padding: 3rem 1rem;
        text-align: center;
        color: #64748b
    }

    .action-group {
        display: flex;
        justify-content: flex-end;
        gap: .35rem;
        flex-wrap: nowrap
    }

    @media(max-width:1100px) {
        .toolbar-row {
            flex-wrap: wrap
        }

        .toolbar-filters {
            flex: 1 1 100%
        }

        .rows-control {
            margin-left: auto
        }
    }

    @media(max-width:768px) {
        .hero {
            padding: 1.25rem !important
        }

        .main-card {
            padding: 1rem !important
        }

        .toolbar-row {
            align-items: stretch
        }

        .filter-control {
            width: 100%;
            max-width: none !important
        }

        .pagination-bar {
            align-items: stretch;
            flex-direction: column
        }

        .action-group {
            justify-content: flex-start
        }

        .bulk-selection-bar {
            position: relative;
            top: auto
        }

        .room-table {
            min-width: 1000px
        }
    }

    /* ===== Đồng bộ phong cách với Quản lý người dùng ===== */
    .rooms-wrap { animation: fadeIn .35s ease-in-out; }
    .rooms-wrap .admin-page-head { border:0; border-radius:1.25rem; overflow:hidden; box-shadow:0 .35rem 1rem rgba(46,16,101,.12)!important; }
    .rooms-wrap .main-card { border:1px solid #eef2f7; border-radius:1.25rem; box-shadow:0 8px 26px rgba(15,23,42,.055); }
    .rooms-wrap .toolbar-row { background:#fff; border:1px solid #eef2f7; border-radius:1rem; padding:.85rem; }
    .rooms-wrap .filter-control, .rooms-wrap .rows-control select { border:1px solid #e2e8f0!important; background:#f8fafc!important; }
    .rooms-wrap .filter-control:focus, .rooms-wrap .rows-control select:focus { background:#fff!important; border-color:#a78bfa!important; box-shadow:0 0 0 .2rem rgba(124,58,237,.10)!important; }
    .rooms-wrap .room-table { border-collapse:separate; border-spacing:0 .5rem; }
    .rooms-wrap .room-table tbody td { background:#f8fafc; padding:.9rem 1rem; }
    .rooms-wrap .room-table tbody tr:hover td { background:#f1f5f9; }
    .rooms-wrap .icon-btn { border:1px solid #e9edf4; background:#fff!important; box-shadow:0 2px 8px rgba(15,23,42,.04); }
    .rooms-wrap .icon-btn:hover { transform:translateY(-1px) scale(1.04); }
    .rooms-wrap .toolbar-action-btn { height:40px; border-radius:.65rem; font-weight:600; white-space:nowrap; }
    .rooms-wrap .btn-import { border-color:#16a34a; color:#15803d; background:#fff; }
    .rooms-wrap .btn-import:hover { background:#16a34a; color:#fff; }
    .rooms-wrap .stat-card { border:1px solid #eef2f7; }
    .rooms-wrap .pagination .page-link { min-width:34px; text-align:center; border:1px solid #e9edf4; background:#fff; }
    .rooms-wrap .pagination .page-item.active .page-link { border-color:var(--purple); background:var(--purple); }
    .data-import-modal .modal-content{border:0!important;border-radius:1.25rem!important;overflow:hidden;box-shadow:0 24px 60px rgba(15,23,42,.22)!important}
    .data-import-modal .modal-header{background:#fff!important;color:#172033!important;border:0!important;padding:1.15rem 1.25rem .7rem!important}
    .data-import-modal .modal-title{color:#172033!important;font-weight:800!important}
    .data-import-modal .btn-close{filter:none!important;opacity:.55}
    .data-import-modal .modal-body{background:#fff!important;padding: .7rem 1.25rem 1.25rem!important}
    .data-import-modal .modal-footer{background:#fff!important;border:0!important;padding:0 1.25rem 1.25rem!important}
    .data-import-modal .modal-footer .btn{min-height:42px;border-radius:.65rem!important;font-weight:700}
    .room-import-drop { border:1.5px dashed #c4b5fd; background:linear-gradient(180deg,#faf8ff,#fff); border-radius:1rem; padding:1.1rem; }
    .room-import-format { background:#f8fafc; border:1px solid #e7ebf1; border-radius:.85rem; padding:.85rem 1rem; font-size:.85rem; }
    .room-import-format code { color:#6d28d9; line-height:1.8; }
    @media(max-width:768px) {
        .rooms-wrap .toolbar-filters { display:grid; grid-template-columns:1fr; width:100%; }
        .rooms-wrap .search-control,.rooms-wrap .status-control,.rooms-wrap .exam-control { width:100%; max-width:none; }
        .rooms-wrap .toolbar-actions { width:100%; display:grid!important; grid-template-columns:1fr 1fr; }
        .rooms-wrap .toolbar-action-btn { width:100%; }
    }


    .header-actions .btn { min-height:42px; display:inline-flex; align-items:center; justify-content:center; }
    @media(max-width:575.98px){
        .header-actions{width:100%;display:grid!important;grid-template-columns:1fr 1fr}
        .header-actions .btn{width:100%;font-size:.86rem}
    }
</style>
<div class="container-fluid px-0 rooms-wrap">
    <div class="hero p-4 mb-4 admin-page-head">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h3 class="fw-bold mb-1 fs-4 fs-md-3"><i class="bi bi-door-open-fill me-2"></i>Quản lý phòng thi</h3>
                <div class="text-white-50 small"><?= number_format($total) ?> phòng thi trong hệ thống</div>
            </div>
            <div class="header-actions d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-light fw-semibold rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#roomImportModal">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel/CSV
                </button>
                <button type="button" class="btn btn-light text-primary fw-bold rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="openCreate()">
                    <i class="bi bi-plus-lg me-1"></i>Tạo phòng
                </button>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Tổng phòng</div>
                        <div class="fs-3 fw-bold"><?= $total ?></div>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-door-open-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Chờ bắt đầu</div>
                        <div class="fs-3 fw-bold text-warning"><?= $waiting ?></div>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Đang diễn ra</div>
                        <div class="fs-3 fw-bold text-success"><?= $running ?></div>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-broadcast-pin"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Đã kết thúc</div>
                        <div class="fs-3 fw-bold text-secondary"><?= $finished ?></div>
                    </div>
                    <div class="stat-icon bg-secondary-subtle text-secondary"><i class="bi bi-check2-circle"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card main-card p-3 p-md-4">
        <div class="toolbar-row mb-3">
            <div class="toolbar-filters">
                <div class="input-group filter-control search-control"><span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span><input id="search" class="form-control bg-light border-0" placeholder="Tìm mã phòng, tên phòng, giảng viên..." oninput="applyRoomView()"></div>
                <select id="status" class="form-select bg-light border-0 filter-control status-control" onchange="applyRoomView()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="waiting">Chờ bắt đầu</option>
                    <option value="running">Đang diễn ra</option>
                    <option value="finished">Đã kết thúc</option>
                    <option value="cancelled">Đã hủy</option>
                </select>
                <select id="examFilter" class="form-select bg-light border-0 filter-control exam-control" onchange="applyRoomView()">
                    <option value="">Tất cả đề thi</option><?php foreach ($exams as $ex): ?><option value="<?= (int)$ex['id'] ?>"><?= e(($ex['exam_code'] ? $ex['exam_code'] . ' - ' : '') . $ex['title']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="rows-control"><select id="rowsPerPage" class="form-select form-select-sm" onchange="applyRoomView()">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select><span class="small text-muted text-nowrap">/ trang</span></div>
        </div>
        <div id="bulkBar" class="bulk-selection-bar d-none align-items-center justify-content-between mb-3"><strong><span id="selectedCount">0</span> phòng đã chọn</strong><button type="button" class="btn btn-sm btn-danger" onclick="bulkDelete()"><i class="bi bi-trash3 me-1"></i>Xóa đã chọn</button></div>
        <div class="table-wrap">
            <table class="table room-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:42px"><input type="checkbox" id="selectAll" class="form-check-input" title="Chọn tất cả"></th>
                        <th><button type="button" class="sort-btn" data-sort="code">MÃ PHÒNG <i class="bi bi-arrow-down-up"></i></button></th>
                        <th><button type="button" class="sort-btn" data-sort="name">PHÒNG / ĐỀ THI <i class="bi bi-arrow-down-up"></i></button></th>
                        <th><button type="button" class="sort-btn" data-sort="teacher">GIẢNG VIÊN <i class="bi bi-arrow-down-up"></i></button></th>
                        <th class="text-center">SỨC CHỨA</th>
                        <th class="text-center">THỜI GIAN</th>
                        <th class="text-center">TRẠNG THÁI</th>
                        <th class="text-end">THAO TÁC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): $st = adminRoomStatus($r['status']);
                        $search = strtolower(($r['room_code'] ?? '') . ' ' . ($r['room_name'] ?? '') . ' ' . ($r['teacher_name'] ?? '')); ?>
                        <tr class="room-row" data-search="<?= e($search) ?>" data-status="<?= e($r['status']) ?>" data-exam="<?= (int)$r['exam_id'] ?>" data-code="<?= e(strtolower($r['room_code'] ?? '')) ?>" data-name="<?= e(strtolower(($r['room_name'] ?? '') . ' ' . ($r['exam_title'] ?? ''))) ?>" data-teacher="<?= e(strtolower($r['teacher_name'] ?? '')) ?>">
                            <td><input type="checkbox" class="form-check-input room-select" value="<?= $r['id'] ?>"></td>
                            <td><span class="badge bg-dark-subtle text-dark code"><?= e($r['room_code']) ?></span></td>
                            <td>
                                <div class="fw-bold"><?= e($r['room_name']) ?></div><small class="text-muted"><i class="bi bi-journal-text me-1"></i><?= e($r['exam_title'] ?: 'Chưa gán') ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($r['teacher_name'] ?: 'Chưa gán') ?></div>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><i class="bi bi-people-fill text-primary me-1"></i><?= (int)$r['participant_count'] ?>/<?= (int)$r['max_students'] ?></span></td>
                            <td class="text-center small">
                                <div><?= $r['start_time'] ? date('H:i d/m/Y', strtotime($r['start_time'])) : 'Không giới hạn' ?></div>
                                <div class="text-muted"><?= $r['end_time'] ? date('H:i d/m/Y', strtotime($r['end_time'])) : 'Không giới hạn' ?></div>
                            </td>
                            <td class="text-center"><span class="badge text-<?= $st[1] ?> bg-<?= $st[1] ?>-subtle"><i class="bi <?= $st[2] ?> me-1"></i><?= $st[0] ?></span></td>
                            <td class="text-end"><button class="btn btn-light text-warning icon-btn" title="Đổi trạng thái" onclick="openStatus(<?= (int)$r['id'] ?>,'<?= e($r['room_name']) ?>','<?= e($r['status']) ?>')"><i class="bi bi-arrow-repeat"></i></button> <button class="btn btn-light text-primary icon-btn" onclick='openEdit(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil-square"></i></button> <button class="btn btn-light text-danger icon-btn" onclick="deleteRoom(<?= (int)$r['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="tableEmpty" class="table-empty"><i class="bi bi-search fs-2 d-block mb-2"></i>Không tìm thấy phòng thi phù hợp.</div>
        <div class="pagination-bar"><small id="pageInfo" class="text-muted"></small>
            <nav aria-label="Phân trang phòng thi">
                <ul id="roomPagination" class="pagination pagination-sm"></ul>
            </nav>
        </div>
    </div>
</div>


<div class="modal fade data-import-modal" id="roomImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form method="post" enctype="multipart/form-data">
                <?php if (function_exists('csrf_field')) csrf_field(); ?>
                <input type="hidden" name="action" value="import_rooms">
                <div class="modal-header border-0 pb-1">
                    <div>
                        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Import phòng</h5>
                        
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="room-import-drop mb-3">
                        <label class="form-label fw-semibold">Chọn file dữ liệu</label>
                        <input type="file" class="form-control" name="import_file" accept=".csv,.xlsx" required>
                        <div class="form-text mt-2">Tối thiểu cần <b>Tên phòng</b> và <b>Mã đề thi</b> hoặc <b>ID đề thi</b>. Mã phòng được hệ thống tạo tự động.</div>
                    </div>
                    <div class="room-import-format">
                        <div class="fw-bold mb-2"><i class="bi bi-info-circle me-1 text-primary"></i>Cột hỗ trợ</div>
                        <code>room_name, exam_code, teacher_email, max_students, start_time, end_time, room_password, status, description, allow_late_join, late_minutes, auto_start, auto_close</code>
                        <hr class="my-2">
                        <div class="text-muted">Có thể dùng tiêu đề tiếng Việt như: <b>Tên phòng, Mã đề, Email giảng viên, Sức chứa, Bắt đầu, Kết thúc, Trạng thái, Mô tả</b>.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success rounded-3 fw-semibold px-4"><i class="bi bi-upload me-1"></i>Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form method="post"><?php if (function_exists('csrf_field')) csrf_field(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="roomModalTitle">Tạo phòng thi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input type="hidden" name="action" id="action" value="create"><input type="hidden" name="id" id="id">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Tên phòng *</label><input id="room_name" name="room_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Mã phòng</label><input id="room_code" name="room_code" class="form-control bg-light" placeholder="Tự động: 26CSDL001" readonly></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Giảng viên *</label><select id="teacher_id" name="teacher_id" class="form-select" required>
                                <option value="">-- Chọn giảng viên --</option><?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?><?= $t['email'] ? ' - ' . e($t['email']) : '' ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Đề thi *</label><select id="exam_id" name="exam_id" class="form-select" required>
                                <option value="">-- Chọn đề thi --</option><?php foreach ($exams as $e): ?><option value="<?= $e['id'] ?>" data-teacher-id="<?= (int)$e['teacher_id'] ?>"><?= e($e['title']) ?><?= $e['exam_code'] ? ' (' . e($e['exam_code']) . ')' : '' ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Sức chứa</label><input id="max_students" type="number" min="1" name="max_students" value="50" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Bắt đầu</label><input id="start_time" type="datetime-local" name="start_time" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Kết thúc</label><input id="end_time" type="datetime-local" name="end_time" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Mật khẩu phòng</label><input id="room_password" name="room_password" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Trạng thái</label><select id="room_status" name="status" class="form-select">
                                <option value="waiting">Chờ bắt đầu</option>
                                <option value="running">Đang diễn ra</option>
                                <option value="finished">Đã kết thúc</option>
                                <option value="cancelled">Đã hủy</option>
                            </select></div>
                        <div class="col-12"><label class="form-label fw-semibold">Mô tả</label><textarea id="description" name="description" class="form-control" rows="2"></textarea></div>
                        <div class="col-md-4 form-check ms-2"><input id="allow_late_join" class="form-check-input" type="checkbox" name="allow_late_join" value="1"><label class="form-check-label">Cho phép vào trễ</label></div>
                        <div class="col-md-4"><input id="late_minutes" type="number" min="0" name="late_minutes" value="0" class="form-control" placeholder="Phút trễ"></div>
                        <div class="col-md-6 form-check ms-2"><input id="auto_start" class="form-check-input" type="checkbox" name="auto_start" value="1"><label class="form-check-label">Tự động bắt đầu</label></div>
                        <div class="col-md-6 form-check"><input id="auto_close" class="form-check-input" type="checkbox" name="auto_close" value="1"><label class="form-check-label">Tự động kết thúc</label></div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-purple px-4">Lưu</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <form method="post"><?php if (function_exists('csrf_field')) csrf_field(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Cập nhật trạng thái</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" id="status_id">
                    <p id="status_name" class="text-muted"></p><select name="status" id="status_value" class="form-select">
                        <option value="waiting">Chờ bắt đầu</option>
                        <option value="running">Đang diễn ra</option>
                        <option value="finished">Đã kết thúc</option>
                        <option value="cancelled">Đã hủy</option>
                    </select>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-purple">Cập nhật</button></div>
            </form>
        </div>
    </div>
</div>
<form id="deleteForm" method="post" class="d-none"><?php if (function_exists('csrf_field')) csrf_field(); ?><input name="action" value="delete"><input name="id" id="delete_id"></form>
<form method="post" id="bulkForm" class="d-none"><?php if (function_exists('csrf_field')) csrf_field(); ?><input type="hidden" name="action" value="bulk_delete">
    <div id="bulkInputs"></div>
</form>
<script>
    function modal(id) {
        return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
    }

    function openCreate() {
        document.getElementById('roomModalTitle').textContent = 'Tạo phòng thi';
        document.getElementById('action').value = 'create';
        document.getElementById('id').value = '';
        document.querySelector('#roomModal form').reset();
        document.getElementById('max_students').value = 50;
        document.getElementById('room_status').value = 'waiting';
        syncExamOptions();
    }

    function syncExamOptions() {
        var teacher = document.getElementById('teacher_id');
        var exam = document.getElementById('exam_id');
        if (!teacher || !exam) return;
        var tid = teacher.value;
        Array.from(exam.options).forEach(function(o) {
            if (!o.value) {
                o.hidden = false;
                return;
            }
            o.hidden = !!tid && o.dataset.teacherId !== tid;
            if (o.value === exam.value && o.hidden) exam.value = '';
        });
    }
    document.getElementById('teacher_id').addEventListener('change', syncExamOptions);

    function openEdit(r) {
        document.getElementById('roomModalTitle').textContent = 'Chỉnh sửa phòng thi';
        document.getElementById('action').value = 'update';
        document.getElementById('id').value = r.id;
        document.getElementById('room_name').value = r.room_name || '';
        document.getElementById('room_code').value = r.room_code || '';
        document.getElementById('teacher_id').value = r.assigned_teacher_id || r.teacher_id || '';
        document.getElementById('exam_id').value = r.exam_id || '';
        syncExamOptions();
        document.getElementById('exam_id').value = r.exam_id || '';
        document.getElementById('max_students').value = r.max_students || 50;
        document.getElementById('room_password').value = r.room_password || '';
        document.getElementById('description').value = r.description || '';
        document.getElementById('room_status').value = r.status || 'waiting';
        document.getElementById('late_minutes').value = r.late_minutes || 0;
        document.getElementById('allow_late_join').checked = Number(r.allow_late_join) === 1;
        document.getElementById('auto_start').checked = Number(r.auto_start) === 1;
        document.getElementById('auto_close').checked = Number(r.auto_close) === 1;
        document.getElementById('start_time').value = r.start_time ? r.start_time.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('end_time').value = r.end_time ? r.end_time.replace(' ', 'T').substring(0, 16) : '';
        modal('roomModal').show();
    }

    function openStatus(id, name, status) {
        document.getElementById('status_id').value = id;
        document.getElementById('status_name').textContent = 'Phòng: ' + name;
        document.getElementById('status_value').value = status;
        modal('statusModal').show();
    }

    function deleteRoom(id) {
        if (confirm('Bạn có chắc muốn xóa phòng thi này? Dữ liệu kết quả liên quan có thể bị xóa theo cấu hình CSDL.')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    function bulkDelete() {
        var ids = [...document.querySelectorAll('.room-select:checked')].map(x => x.value);
        if (!ids.length) {
            alert('Vui lòng chọn ít nhất một phòng thi.');
            return;
        }
        if (!confirm('Bạn có chắc muốn xóa ' + ids.length + ' phòng thi đã chọn?')) return;
        var box = document.getElementById('bulkInputs');
        box.innerHTML = '';
        ids.forEach(function(id) {
            var i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'ids[]';
            i.value = id;
            box.appendChild(i)
        });
        document.getElementById('bulkForm').submit();
    }

    function syncBulk() {
        var boxes = [...document.querySelectorAll('.room-select')],
            selected = boxes.filter(x => x.checked);
        var bar = document.getElementById('bulkBar');
        document.getElementById('selectedCount').textContent = selected.length;
        if (selected.length > 0) {
            bar.classList.remove('d-none');
            bar.style.display = 'flex';
        } else {
            bar.classList.add('d-none');
            bar.style.display = 'none';
        }
        var all = document.getElementById('selectAll');
        all.checked = boxes.length > 0 && selected.length === boxes.length;
        all.indeterminate = selected.length > 0 && selected.length < boxes.length;
    }
    document.addEventListener('change', function(e) {
        if (e.target.id === 'selectAll') {
            document.querySelectorAll('.room-select').forEach(function(x) {
                x.checked = e.target.checked;
            });
            syncBulk();
            return;
        }
        if (e.target.classList.contains('room-select')) syncBulk();
    });
    document.addEventListener('click', function(e) {
        if (e.target.closest('#bulkBar,#selectAll,.room-select')) return;
        syncBulk();
    });
    syncBulk();
    var roomState = {
        page: 1,
        sortKey: 'created',
        sortDir: 'desc'
    };

    function roomRows() {
        return [...document.querySelectorAll('.room-row')];
    }

    function applyRoomView() {
        var q = document.getElementById('search').value.toLowerCase().trim(),
            st = document.getElementById('status').value,
            ex = document.getElementById('examFilter').value;
        var rows = roomRows().filter(function(r) {
            return (!q || r.dataset.search.indexOf(q) >= 0) && (!st || r.dataset.status === st) && (!ex || r.dataset.exam === ex);
        });
        rows.sort(function(a, b) {
            var av = roomState.sortKey === 'code' ? a.dataset.code : roomState.sortKey === 'name' ? a.dataset.name : roomState.sortKey === 'teacher' ? a.dataset.teacher : a.dataset.created || '';
            var bv = roomState.sortKey === 'code' ? b.dataset.code : roomState.sortKey === 'name' ? b.dataset.name : roomState.sortKey === 'teacher' ? b.dataset.teacher : b.dataset.created || '';
            return av.localeCompare(bv, 'vi', {
                numeric: true
            }) * roomState.sortDir;
        });
        var per = +document.getElementById('rowsPerPage').value || 25,
            total = rows.length,
            pages = Math.max(1, Math.ceil(total / per));
        if (roomState.page > pages) roomState.page = pages;
        var from = (roomState.page - 1) * per,
            to = Math.min(from + per, total),
            visible = new Set(rows.slice(from, to));
        roomRows().forEach(function(r) {
            r.style.display = visible.has(r) ? '' : 'none';
        });
        document.getElementById('tableEmpty').style.display = total ? 'none' : 'block';
        document.getElementById('pageInfo').textContent = total ? 'Hiển thị ' + (from + 1) + '–' + to + ' / ' + total + ' phòng' : '0 phòng';
        renderPagination(pages);
        syncBulk();
    }

    function renderPagination(pages) {
        var ul = document.getElementById('roomPagination');
        ul.innerHTML = '';
        if (pages <= 1) return;

        function add(p, label, disabled, active) {
            var li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'page-link';
            b.innerHTML = label;
            b.disabled = disabled;
            b.onclick = function() {
                roomState.page = p;
                applyRoomView();
            };
            li.appendChild(b);
            ul.appendChild(li);
        }
        add(roomState.page - 1, '<i class="bi bi-chevron-left"></i>', roomState.page === 1, false);
        var a = Math.max(1, roomState.page - 2),
            z = Math.min(pages, a + 4);
        a = Math.max(1, z - 4);
        for (var p = a; p <= z; p++) add(p, p, false, p === roomState.page);
        add(roomState.page + 1, '<i class="bi bi-chevron-right"></i>', roomState.page === pages, false);
    }
    document.querySelectorAll('.sort-btn').forEach(function(b) {
        b.addEventListener('click', function() {
            var key = b.dataset.sort;
            if (roomState.sortKey === key) roomState.sortDir *= -1;
            else {
                roomState.sortKey = key;
                roomState.sortDir = 1;
            }
            roomState.page = 1;
            applyRoomView();
        });
    });

    function filterRooms() {
        roomState.page = 1;
        applyRoomView();
    }
</script>
<?php require_once '../includes/footer.php'; ?>