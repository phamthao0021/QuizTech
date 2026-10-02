<?php
// api/import.php
// Module Import dữ liệu từ file Excel (.xlsx) và CSV
// Hỗ trợ: users, rooms, subjects, questions
// Actions: template | preview | commit

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/json; charset=utf-8');

// ============================================================
// AUTHENTICATION CHECK
// ============================================================
session_start();
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['admin', 'admin_test', 'teacher'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Không có quyền truy cập.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$type   = $_REQUEST['type']   ?? 'users';

$actorRole = function_exists('normalizeRole')
    ? normalizeRole($_SESSION['user']['role'] ?? '')
    : strtolower(trim((string)($_SESSION['user']['role'] ?? '')));

// Teacher không được import users qua API
if ($type === 'users' && $actorRole === 'teacher') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Giảng viên không được import tài khoản người dùng.']);
    exit;
}

// Các type hợp lệ
$allowed_types = ['users', 'rooms', 'subjects', 'questions'];
if (!in_array($type, $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Loại dữ liệu không hợp lệ.']);
    exit;
}

// ============================================================
// SCHEMA DEFINITIONS – cột theo từng type
// ============================================================
function getSchema(string $type): array
{
    $schemas = [
        'users' => [
            'columns'  => ['Họ và Tên', 'Email', 'Tên đăng nhập', 'Vai trò (admin/teacher/student)', 'Mật khẩu'],
            'db_keys'  => ['fullname', 'email', 'username', 'role', 'password'],
            'required' => ['fullname', 'email', 'username', 'role', 'password'],
        ],
        'rooms' => [
            'columns'  => ['Tên phòng thi', 'Mô tả', 'Mã phòng', 'Trạng thái (active/inactive)'],
            'db_keys'  => ['name', 'description', 'code', 'status'],
            'required' => ['name', 'code'],
        ],
        'subjects' => [
            'columns'  => ['Tên môn học', 'Mô tả'],
            'db_keys'  => ['name', 'description'],
            'required' => ['name'],
        ],
        'questions' => [
            'columns'  => ['STT', 'NoiDungCauHoi', 'DapAnA', 'DapAnB', 'DapAnC', 'DapAnD', 'DapAnDung', 'GiaiThich', 'MucDo'],
            'db_keys'  => ['stt', 'content', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'difficulty'],
            'required' => ['content', 'option_a', 'option_b', 'correct_answer', 'difficulty'],
        ],
    ];
    return $schemas[$type] ?? [];
}

// ============================================================
// ACTION: TEMPLATE – Tải file Excel mẫu
// ============================================================
if ($action === 'template') {
    $schema  = getSchema($type);
    $headers = $schema['columns'];

    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Template');

    // Header row
    foreach ($headers as $col => $label) {
        $colLetter = chr(65 + $col);
        $cell      = $colLetter . '1';
        $sheet->setCellValue($cell, $label);
        $sheet->getStyle($cell)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6d28d9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }

    // Sample rows
    $samples = getSampleRows($type);
    foreach ($samples as $rowIdx => $row) {
        foreach ($row as $col => $val) {
            $sheet->setCellValue(chr(65 + $col) . ($rowIdx + 2), $val);
        }
    }

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_' . $type . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

// ============================================================
// Sample row helper
// ============================================================
function getSampleRows(string $type): array
{
    $samples = [
        'users' => [
            ['Nguyễn Văn A', 'nguyenvana@example.com', 'nguyenvana', 'student', 'Password@123'],
            ['Trần Thị B',   'tranthib@example.com',   'tranthib',   'teacher', 'Password@456'],
        ],
        'rooms' => [
            ['Phòng thi Toán',  'Phòng thi môn Toán học',   'MATH-01', 'active'],
            ['Phòng thi Lý',    'Phòng thi môn Vật lý',     'PHY-01',  'inactive'],
        ],
        'subjects' => [
            ['Toán học',     'Môn học về toán'],
            ['Vật lý',       'Môn học về vật lý'],
        ],
        'questions' => [
            [1, 'SQL là viết tắt của từ nào sau đây?', 'Structured Query Language', 'Simple Query Language', 'System Query Language', 'Standard Question Language', 'A', 'SQL = Structured Query Language - ngôn ngữ truy vấn có cấu trúc.', 'De'],
            [2, 'Lệnh nào dùng để truy vấn dữ liệu trong SQL?', 'SELECT', 'UPDATE', 'DELETE', 'INSERT', 'A', 'SELECT dùng để lấy (đọc) dữ liệu từ bảng.', 'De'],
            [3, 'PHP là ngôn ngữ lập trình phía nào?', 'Server (Máy chủ)', 'Client (Trình duyệt)', 'Database (Cơ sở dữ liệu)', 'Network (Mạng)', 'A', 'PHP chạy trên máy chủ, xử lý logic rồi trả HTML về trình duyệt.', 'TrungBinh'],
        ],
    ];
    return $samples[$type] ?? [];
}

// ============================================================
// ACTION: PREVIEW – Đọc file, validate, trả về JSON
// ============================================================
if ($action === 'preview') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Không nhận được file.']);
        exit;
    }

    $file     = $_FILES['file'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $maxSize  = 5 * 1024 * 1024; // 5MB

    if (!in_array($ext, ['xlsx', 'csv'])) {
        echo json_encode(['status' => 'error', 'message' => 'Chỉ chấp nhận file .xlsx hoặc .csv.']);
        exit;
    }
    if ($file['size'] > $maxSize) {
        echo json_encode(['status' => 'error', 'message' => 'File vượt quá dung lượng tối đa 5MB.']);
        exit;
    }

    // Lưu file tạm
    $tempDir = __DIR__ . '/../uploads/temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    $tempName = 'import_' . uniqid() . '_' . time() . '.' . $ext;
    $tempPath = $tempDir . $tempName;

    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể lưu file tạm.']);
        exit;
    }

    // Đọc dữ liệu
    try {
        $rows = readSpreadsheet($tempPath, $ext);
    } catch (Exception $e) {
        @unlink($tempPath);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi đọc file: ' . $e->getMessage()]);
        exit;
    }

    // Validate từng dòng
    $schema        = getSchema($type);
    $validatedRows = validateRows($rows, $schema, $type);

    $total  = count($validatedRows);
    $errors = array_filter($validatedRows, fn($r) => !empty($r['_errors']));
    $valid  = $total - count($errors);

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'headers'   => $schema['columns'],
            'rows'      => $validatedRows,
            'temp_file' => $tempName,
            'summary'   => [
                'total' => $total,
                'valid' => $valid,
                'error' => count($errors),
            ],
        ],
    ]);
    exit;
}

// ============================================================
// ACTION: COMMIT – Lưu dữ liệu hợp lệ bằng Transaction
// ============================================================
if ($action === 'commit') {
    $tempFile = basename($_POST['temp_file'] ?? '');
    if (!$tempFile) {
        echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin file tạm.']);
        exit;
    }

    $tempDir  = __DIR__ . '/../uploads/temp/';
    $tempPath = $tempDir . $tempFile;

    if (!file_exists($tempPath)) {
        echo json_encode(['status' => 'error', 'message' => 'File tạm đã hết hạn hoặc không tồn tại. Vui lòng upload lại.']);
        exit;
    }

    // Tham số riêng cho questions
    $subjectId  = (int)($_POST['subject_id']  ?? 0);
    $examTitle  = trim($_POST['exam_title']   ?? '');
    $currentUID = (int)($_SESSION['user']['id'] ?? 0);

    // subject_id bắt buộc khi import questions
    if ($type === 'questions' && $subjectId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng chọn Môn học (subject_id) trước khi import câu hỏi.']);
        exit;
    }

    $ext = strtolower(pathinfo($tempPath, PATHINFO_EXTENSION));

    try {
        $rows   = readSpreadsheet($tempPath, $ext);
        $schema = getSchema($type);
        $rows   = validateRows($rows, $schema, $type);

        // Kiểm tra lại – từ chối nếu còn dòng lỗi
        $hasError = array_filter($rows, fn($r) => !empty($r['_errors']));
        if ($hasError) {
            echo json_encode(['status' => 'error', 'message' => 'Dữ liệu vẫn còn lỗi. Vui lòng kiểm tra lại file.']);
            exit;
        }

        global $pdo;
        $pdo->beginTransaction();

        $inserted    = 0;
        $questionIds = []; // Lưu danh sách ID câu hỏi vừa insert (cho questions)

        foreach ($rows as $row) {
            unset($row['_errors']);
            $newId = insertRow($pdo, $type, $row, $schema, $subjectId, $currentUID);
            if ($type === 'questions' && $newId > 0) {
                $questionIds[] = $newId;
            }
            $inserted++;
        }

        // Nếu type=questions và có exam_title → tạo đề thi và liên kết câu hỏi
        $examId = null;
        if ($type === 'questions' && $examTitle !== '' && !empty($questionIds)) {
            // Sinh exam_code ngẫu nhiên tránh trùng
            $examCode = 'IMP-' . strtoupper(substr(md5(uniqid()), 0, 8));

            $stmtExam = $pdo->prepare("
                INSERT INTO exams
                    (subject_id, teacher_id, title, exam_code, exam_type, difficulty,
                     total_questions, duration, status, created_at)
                VALUES
                    (:subject_id, :teacher_id, :title, :exam_code, 'quiz', 'mixed',
                     :total_questions, 30, 'draft', NOW())
            ");
            $stmtExam->execute([
                ':subject_id'      => $subjectId,
                ':teacher_id'      => $currentUID,
                ':title'           => $examTitle,
                ':exam_code'       => $examCode,
                ':total_questions' => count($questionIds),
            ]);
            $examId = (int)$pdo->lastInsertId();

            // Liên kết từng câu hỏi vào exam_questions
            $stmtEQ = $pdo->prepare("
                INSERT INTO exam_questions (exam_id, question_type, question_id, question_order, score)
                VALUES (:exam_id, 'single', :question_id, :question_order, 1.00)
            ");
            foreach ($questionIds as $order => $qId) {
                $stmtEQ->execute([
                    ':exam_id'        => $examId,
                    ':question_id'    => $qId,
                    ':question_order' => $order + 1,
                ]);
            }
        }

        $pdo->commit();
        @unlink($tempPath); // Xóa file tạm sau khi commit thành công

        $responseMsg = "Đã import thành công $inserted câu hỏi.";
        if ($examId) {
            $responseMsg .= " Đề thi '$examTitle' đã được tạo (ID: $examId).";
        }

        echo json_encode([
            'status'   => 'success',
            'message'  => $responseMsg,
            'count'    => $inserted,
            'exam_id'  => $examId,
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, đã rollback: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// ACTION không hợp lệ
// ============================================================
echo json_encode(['status' => 'error', 'message' => "Action '$action' không hợp lệ."]);
exit;

// ============================================================
// HELPERS
// ============================================================

/**
 * Đọc file Excel/CSV, bỏ dòng header, trả về mảng associative
 */
function readSpreadsheet(string $path, string $ext): array
{
    if ($ext === 'csv') {
        $reader = IOFactory::createReader('Csv');
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);
    } else {
        $reader = IOFactory::createReader('Xlsx');
    }
    $reader->setReadDataOnly(true);

    $spreadsheet = $reader->load($path);
    $sheet       = $spreadsheet->getActiveSheet();
    $data        = $sheet->toArray(null, true, true, false);

    if (count($data) < 2) {
        throw new Exception('File không có dữ liệu (thiếu dòng dữ liệu phía dưới header).');
    }

    // Bỏ dòng header (dòng đầu tiên)
    array_shift($data);

    // Lọc dòng hoàn toàn trống
    $data = array_filter($data, function ($row) {
        return !empty(array_filter(array_map('trim', $row)));
    });

    return array_values($data);
}

/**
 * Validate từng dòng và gắn key theo db_keys của schema
 */
function validateRows(array $rows, array $schema, string $type): array
{
    global $pdo;

    $dbKeys   = $schema['db_keys'];
    $required = $schema['required'];

    // Cache email đã tồn tại (chỉ cho users)
    $existingEmails    = [];
    $existingUsernames = [];
    $existingRoomCodes = [];
    $existingSubjects  = [];

    if ($type === 'users') {
        $existingEmails    = $pdo->query("SELECT LOWER(email) FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $existingUsernames = $pdo->query("SELECT LOWER(username) FROM users")->fetchAll(PDO::FETCH_COLUMN);
    }
    if ($type === 'rooms') {
        $existingRoomCodes = $pdo->query("SELECT LOWER(code) FROM rooms")->fetchAll(PDO::FETCH_COLUMN);
    }
    if ($type === 'subjects') {
        $existingSubjects = $pdo->query("SELECT LOWER(name) FROM subjects")->fetchAll(PDO::FETCH_COLUMN);
    }

    $result           = [];
    $seenEmailsInFile = [];

    foreach ($rows as $rowIndex => $rawRow) {
        $lineNumber = $rowIndex + 2; // +2 vì dòng 1 là header
        $mapped     = [];
        $errors     = [];

        // Map cột theo thứ tự
        foreach ($dbKeys as $colIdx => $key) {
            $val          = trim($rawRow[$colIdx] ?? '');
            $mapped[$key] = $val;
        }

        // Validate required
        foreach ($required as $key) {
            if ($mapped[$key] === '') {
                $label = $schema['columns'][array_search($key, $dbKeys)] ?? $key;
                $errors[] = "Dòng $lineNumber: Thiếu trường '$label'";
            }
        }

        // Validate users
        if ($type === 'users' && $mapped['email'] !== '') {
            // Format email
            if (!filter_var($mapped['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Dòng $lineNumber: Email không hợp lệ";
            }
            // Duplicate in DB
            elseif (in_array(strtolower($mapped['email']), $existingEmails)) {
                $errors[] = "Dòng $lineNumber: Email đã tồn tại trong hệ thống";
            }
            // Duplicate in file
            elseif (in_array(strtolower($mapped['email']), $seenEmailsInFile)) {
                $errors[] = "Dòng $lineNumber: Email bị trùng lặp trong file";
            } else {
                $seenEmailsInFile[] = strtolower($mapped['email']);
            }
        }

        if ($type === 'users' && $mapped['username'] !== '') {
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $mapped['username'])) {
                $errors[] = "Dòng $lineNumber: Tên đăng nhập chỉ được chứa chữ cái, số, dấu gạch dưới (3-50 ký tự)";
            } elseif (in_array(strtolower($mapped['username']), $existingUsernames)) {
                $errors[] = "Dòng $lineNumber: Tên đăng nhập đã tồn tại";
            }
        }

        if ($type === 'users' && $mapped['role'] !== '') {
            $roleNorm = function_exists('normalizeRole') ? normalizeRole($mapped['role']) : strtolower(trim($mapped['role']));
            $mapped['role'] = $roleNorm;
            if (!in_array($roleNorm, ['admin', 'admin_test', 'teacher', 'student'], true)) {
                $errors[] = "Dòng $lineNumber: Vai trò không hợp lệ";
            } elseif (function_exists('can_assign_role') && !can_assign_role($roleNorm)) {
                $errors[] = "Dòng $lineNumber: Bạn không có quyền tạo vai trò '$roleNorm'";
            }
        }

        if ($type === 'users' && $mapped['password'] !== '') {
            if (strlen($mapped['password']) < 8) {
                $errors[] = "Dòng $lineNumber: Mật khẩu phải từ 8 ký tự";
            }
        }

        // Validate rooms
        if ($type === 'rooms' && $mapped['code'] !== '') {
            if (in_array(strtolower($mapped['code']), $existingRoomCodes)) {
                $errors[] = "Dòng $lineNumber: Mã phòng đã tồn tại";
            }
            if (isset($mapped['status']) && $mapped['status'] !== '' && !in_array($mapped['status'], ['active', 'inactive'])) {
                $errors[] = "Dòng $lineNumber: Trạng thái phải là 'active' hoặc 'inactive'";
            }
        }

        // Validate subjects
        if ($type === 'subjects' && $mapped['name'] !== '') {
            if (in_array(strtolower($mapped['name']), $existingSubjects)) {
                $errors[] = "Dòng $lineNumber: Môn học '$mapped[name]' đã tồn tại";
            }
        }

        // Validate questions
        if ($type === 'questions') {
            $dapAnDung = strtoupper(trim($mapped['correct_answer'] ?? ''));
            $mucDo     = trim($mapped['difficulty'] ?? '');

            // Chuẩn hoá MucDo → DB enum
            $mucDoMap = ['De' => 'easy', 'TrungBinh' => 'medium', 'Kho' => 'hard'];
            if ($mucDo !== '' && !array_key_exists($mucDo, $mucDoMap)) {
                $errors[] = "Dòng $lineNumber: MucDo phải là 'De', 'TrungBinh' hoặc 'Kho' (hiện tại: '$mucDo')";
            } else {
                // Lưu giá trị đã chuẩn hoá vào _raw khi commit
                $mapped['difficulty'] = $mucDoMap[$mucDo] ?? 'easy';
            }

            // Validate DapAnDung
            if ($dapAnDung !== '' && !in_array($dapAnDung, ['A', 'B', 'C', 'D'])) {
                $errors[] = "Dòng $lineNumber: DapAnDung phải là A, B, C hoặc D (hiện tại: '$dapAnDung')";
            } elseif ($dapAnDung !== '') {
                // Kiểm tra cột đáp án tương ứng không được rỗng
                $answerKeyMap = ['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'];
                $targetKey    = $answerKeyMap[$dapAnDung];
                if (empty(trim($mapped[$targetKey] ?? ''))) {
                    $errors[] = "Dòng $lineNumber: DapAnDung là '$dapAnDung' nhưng nội dung Đáp án $dapAnDung đang bị bỏ trống";
                }
                $mapped['correct_answer'] = $dapAnDung; // Normalize
            }
        }

        // Gắn errors và columns hiển thị preview
        $displayRow = [];
        foreach ($schema['columns'] as $colIdx => $colLabel) {
            $displayRow[$colLabel] = $mapped[$dbKeys[$colIdx]] ?? '';
        }
        $displayRow['_errors'] = $errors;
        $displayRow['_raw']    = $mapped;

        $result[] = $displayRow;
    }

    return $result;
}

/**
 * Insert một dòng vào database theo type.
 * Trả về ID vừa insert (0 nếu không áp dụng).
 */
function insertRow(PDO $pdo, string $type, array $row, array $schema, int $subjectId = 0, int $createdBy = 0): int
{
    // Lấy _raw nếu có (để get giá trị theo db_keys)
    $raw = $row['_raw'] ?? [];
    if (empty($raw)) {
        // Fallback: map từ columns
        foreach ($schema['columns'] as $idx => $col) {
            $raw[$schema['db_keys'][$idx]] = $row[$col] ?? '';
        }
    }

    switch ($type) {
        case 'users':
            $roleNorm = function_exists('normalizeRole') ? normalizeRole($raw['role'] ?? 'student') : 'student';
            if (function_exists('can_assign_role') && !can_assign_role($roleNorm)) {
                throw new RuntimeException('Không có quyền tạo vai trò: ' . $roleNorm);
            }
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, username, password_hash, role, status, is_protected, created_at)
                VALUES (:name, :email, :username, :password_hash, :role, 'active', 0, NOW())
            ");
            $stmt->execute([
                ':name'          => $raw['fullname'] ?? $raw['name'] ?? '',
                ':email'         => strtolower(trim($raw['email'] ?? '')),
                ':username'      => strtolower(trim($raw['username'] ?? '')),
                ':password_hash' => password_hash($raw['password'] ?? '', PASSWORD_DEFAULT),
                ':role'          => $roleNorm,
            ]);
            return (int)$pdo->lastInsertId();

        case 'rooms':
            $stmt = $pdo->prepare("
                INSERT INTO rooms (name, description, code, status, created_at)
                VALUES (:name, :description, :code, :status, NOW())
            ");
            $stmt->execute([
                ':name'        => $raw['name'],
                ':description' => $raw['description'] ?? '',
                ':code'        => strtoupper(trim($raw['code'])),
                ':status'      => in_array($raw['status'], ['active', 'inactive']) ? $raw['status'] : 'active',
            ]);
            return 0;

        case 'subjects':
            $stmt = $pdo->prepare("
                INSERT INTO subjects (name, description, created_at)
                VALUES (:name, :description, NOW())
            ");
            $stmt->execute([
                ':name'        => $raw['name'],
                ':description' => $raw['description'] ?? '',
            ]);
            return 0;

        case 'questions':
            // Map MucDo tiếng Việt → DB enum (đã chuẩn hoá trong validateRows, nhưng phòng khi bypass)
            $difficultyMap = ['De' => 'easy', 'TrungBinh' => 'medium', 'Kho' => 'hard'];
            $difficulty    = $raw['difficulty'] ?? 'easy';
            // Nếu vẫn còn giá trị tiếng Việt (chưa chuẩn hoá) thì map lại
            if (isset($difficultyMap[$difficulty])) {
                $difficulty = $difficultyMap[$difficulty];
            }
            // Đảm bảo giá trị hợp lệ
            if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
                $difficulty = 'easy';
            }

            $stmt = $pdo->prepare("
                INSERT INTO questions
                    (subject_id, created_by, content, option_a, option_b, option_c, option_d,
                     correct_answer, explanation, difficulty, is_public, is_active, created_at)
                VALUES
                    (:subject_id, :created_by, :content, :option_a, :option_b, :option_c, :option_d,
                     :correct_answer, :explanation, :difficulty, 1, 1, NOW())
            ");
            $stmt->execute([
                ':subject_id'    => $subjectId,
                ':created_by'    => $createdBy > 0 ? $createdBy : null,
                ':content'       => $raw['content'],
                ':option_a'      => $raw['option_a'],
                ':option_b'      => $raw['option_b'],
                ':option_c'      => $raw['option_c'] ?? null,
                ':option_d'      => $raw['option_d'] ?? null,
                ':correct_answer'=> strtoupper($raw['correct_answer']),
                ':explanation'   => $raw['explanation'] ?? null,
                ':difficulty'    => $difficulty,
            ]);
            return (int)$pdo->lastInsertId();
    }

    return 0;
}
