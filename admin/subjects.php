<?php // admin/subjects.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$page_title = 'Quản lý Môn học & Phân công';
$msg = '';
$error = '';
$import_report = null; // sẽ chứa thống kê sau khi import: ['success'=>n,'skipped'=>n,'errors'=>[...]]

/* =========================================================
   HÀM HỖ TRỢ IMPORT
   ========================================================= */

/**
 * Đọc file CSV thành danh sách dòng (mỗi dòng là array các cột).
 */
function readCsvRows(string $filepath): array
{
    $rows = [];
    $handle = fopen($filepath, 'r');
    if ($handle === false) {
        throw new Exception("Không thể mở file CSV để đọc.");
    }

    // Bỏ qua BOM (thường gặp khi export CSV từ Excel tiếng Việt)
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    while (($data = fgetcsv($handle, 0, ',')) !== false) {
        // Hỗ trợ cả trường hợp CSV dùng dấu ; (Excel VN thường xuất ra ;)
        if (count($data) === 1 && strpos($data[0], ';') !== false) {
            $data = str_getcsv($data[0], ';');
        }
        $rows[] = $data;
    }
    fclose($handle);
    return $rows;
}

/**
 * Đọc file XLSX/XLS thành danh sách dòng, dùng PhpSpreadsheet nếu có sẵn.
 */
function readExcelRows(string $filepath): array
{
    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
        throw new Exception(
            "Thiếu thư viện PhpSpreadsheet để đọc file Excel. " .
            "Chạy lệnh sau ở thư mục gốc project: composer require phpoffice/phpspreadsheet " .
            "(hoặc tạm thời xuất file dữ liệu sang định dạng .csv rồi import lại)."
        );
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
    $sheet = $spreadsheet->getActiveSheet();
    return $sheet->toArray(null, true, true, false);
}

/**
 * Chuẩn hoá tên cột header (bỏ dấu, lowercase, bỏ khoảng trắng)
 * để nhận diện linh hoạt cả tên tiếng Việt và tiếng Anh.
 */
function normalizeHeader(string $h): string
{
    $h = trim(mb_strtolower($h));
    $map = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
        'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
        'đ'=>'d',
    ];
    $h = strtr($h, $map);
    $h = preg_replace('/[^a-z0-9]+/', '', $h);
    return $h;
}

/**
 * Map header đã chuẩn hoá về đúng tên cột trong bảng subjects.
 */
function mapColumnKey(string $normalized): ?string
{
    $aliases = [
        'name'        => 'name',
        'tenmonhoc'   => 'name',
        'ten'         => 'name',
        'subjectname' => 'name',
        'code'        => 'code',
        'mamonhoc'    => 'code',
        'ma'          => 'code',
        'description' => 'description',
        'mota'        => 'description',
        'icon'        => 'icon',
        'color'       => 'color',
        'mau'         => 'color',
        'status'      => 'status',
        'trangthai'   => 'status',
    ];
    return $aliases[$normalized] ?? null;
}

/**
 * Đổi số cột 0-based thành chữ cái cột kiểu Excel (0->A, 1->B, ..., 26->AA...).
 */
function excelColumnLetter(int $index): string
{
    $letter = '';
    $index++;
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index = (int)(($index - $mod) / 26);
    }
    return $letter;
}

/**
 * Tạo nội dung file .xlsx tối giản (dùng inline string, không cần sharedStrings)
 * từ mảng $rows (mỗi phần tử là 1 dòng, mỗi dòng là mảng các ô dạng chuỗi).
 * Không phụ thuộc PhpSpreadsheet — chỉ cần extension ZipArchive có sẵn trong PHP.
 */
function buildXlsxTemplate(array $rows): string
{
    if (!class_exists('ZipArchive')) {
        throw new Exception(
            "Máy chủ PHP thiếu extension ZipArchive nên không thể tạo file .xlsx mẫu. " .
            "Bạn có thể dùng file mẫu .csv thay thế."
        );
    }

    $sheetRowsXml = '';
    foreach ($rows as $rIndex => $row) {
        $rowNum = $rIndex + 1;
        $cellsXml = '';
        foreach ($row as $cIndex => $value) {
            $cellRef = excelColumnLetter($cIndex) . $rowNum;
            $escaped = htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $cellsXml .= "<c r=\"{$cellRef}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">{$escaped}</t></is></c>";
        }
        $sheetRowsXml .= "<row r=\"{$rowNum}\">{$cellsXml}</row>";
    }

    $contentTypesXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;

    $rootRelsXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;

    $workbookXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Subjects" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML;

    $workbookRelsXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML;

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetData>' . $sheetRowsXml . '</sheetData>'
        . '</worksheet>';

    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_tpl_');
    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
        throw new Exception("Không thể tạo file .xlsx tạm thời trên máy chủ.");
    }
    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $rootRelsXml);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    $content = file_get_contents($tmpFile);
    unlink($tmpFile);
    return $content;
}

/* =========================================================
   XỬ LÝ CÁC THAO TÁC POST
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // 1. Thêm môn học
        if ($action === 'create') {
            $name = trim($_POST['subject_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if (!empty($name)) {
                $stmt = $pdo->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $desc]);
                $msg = "Thêm môn học thành công!";
            }
        }
        // 2. Cập nhật môn học
        elseif ($action === 'update') {
            $id   = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['subject_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if ($id > 0 && !empty($name)) {
                $stmt = $pdo->prepare("UPDATE subjects SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $desc, $id]);
                $msg = "Cập nhật thông tin môn học thành công!";
            }
        }
        // 3. Xóa 1 môn học
        elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmtDel = $pdo->prepare("DELETE FROM teacher_subjects WHERE subject_id = ?");
                $stmtDel->execute([$id]);

                $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                $stmt->execute([$id]);
                $msg = "Đã xóa môn học thành công!";
            }
        }
        // 4. Gán giảng viên vào môn học
        elseif ($action === 'assign_teacher') {
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            if ($subject_id > 0 && $teacher_id > 0) {
                $check = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE subject_id = ? AND teacher_id = ?");
                $check->execute([$subject_id, $teacher_id]);
                if ($check->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO teacher_subjects (subject_id, teacher_id) VALUES (?, ?)");
                    $stmt->execute([$subject_id, $teacher_id]);
                    $msg = "Phân công giảng viên thành công!";
                } else {
                    $error = "Giảng viên này đã được phân công cho môn học rồi!";
                }
            }
        }
        // 5. Hủy gán giảng viên
        elseif ($action === 'remove_teacher') {
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            if ($subject_id > 0 && $teacher_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE subject_id = ? AND teacher_id = ?");
                $stmt->execute([$subject_id, $teacher_id]);
                $msg = "Đã hủy phân công giảng viên!";
            }
        }
        // 6. XÓA HÀNG LOẠT (mới)
        elseif ($action === 'bulk_delete') {
            $ids = $_POST['ids'] ?? [];
            $ids = array_filter(array_map('intval', $ids), fn($v) => $v > 0);

            if (empty($ids)) {
                $error = "Bạn chưa chọn môn học nào để xóa.";
            } else {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                $stmtDel = $pdo->prepare("DELETE FROM teacher_subjects WHERE subject_id IN ($placeholders)");
                $stmtDel->execute($ids);

                $stmt = $pdo->prepare("DELETE FROM subjects WHERE id IN ($placeholders)");
                $stmt->execute($ids);

                $msg = "Đã xóa " . count($ids) . " môn học được chọn!";
            }
        }
        // 7. IMPORT TỪ FILE CSV / XLSX / XLS (mới)
        elseif ($action === 'import') {
            if (empty($_FILES['import_file']['name'])) {
                $error = "Bạn chưa chọn file để import.";
            } else {
                $tmpPath = $_FILES['import_file']['tmp_name'];
                $originalName = $_FILES['import_file']['name'];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if ($_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Lỗi khi tải file lên (mã lỗi: " . $_FILES['import_file']['error'] . ").");
                }

                if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
                    throw new Exception("Chỉ hỗ trợ file .csv, .xlsx hoặc .xls.");
                }

                $rows = ($ext === 'csv') ? readCsvRows($tmpPath) : readExcelRows($tmpPath);

                if (empty($rows)) {
                    throw new Exception("File không có dữ liệu.");
                }

                // Dòng đầu tiên là header -> map cột
                $headerRow = array_shift($rows);
                $columnMap = []; // vị trí cột -> tên cột DB
                foreach ($headerRow as $idx => $h) {
                    $normalized = normalizeHeader((string)$h);
                    $dbCol = mapColumnKey($normalized);
                    if ($dbCol) {
                        $columnMap[$idx] = $dbCol;
                    }
                }

                if (!in_array('name', $columnMap)) {
                    throw new Exception(
                        "Không tìm thấy cột 'Tên môn học' (name) trong file. " .
                        "Hàng đầu tiên phải là header, ví dụ: name, code, description, icon, color, status"
                    );
                }

                $insertStmt = $pdo->prepare(
                    "INSERT INTO subjects (name, code, description, icon, color, status)
                     VALUES (:name, :code, :description, :icon, :color, :status)"
                );
                $checkCodeStmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE code = ?");

                $successCount = 0;
                $skipCount = 0;
                $rowErrors = [];

                foreach ($rows as $rowIndex => $row) {
                    // Bỏ qua dòng trống hoàn toàn
                    if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                        continue;
                    }

                    $data = [
                        'name'        => null,
                        'code'        => null,
                        'description' => null,
                        'icon'        => null,
                        'color'       => null,
                        'status'      => 'active',
                    ];

                    foreach ($columnMap as $colIdx => $dbCol) {
                        $data[$dbCol] = isset($row[$colIdx]) ? trim((string)$row[$colIdx]) : null;
                    }

                    $data['name'] = $data['name'] ?: null;
                    if (empty($data['name'])) {
                        $skipCount++;
                        $rowErrors[] = "Dòng " . ($rowIndex + 2) . ": thiếu tên môn học, đã bỏ qua.";
                        continue;
                    }

                    // Chuẩn hoá status
                    $statusVal = mb_strtolower((string)($data['status'] ?: 'active'));
                    $data['status'] = in_array($statusVal, ['active', 'inactive']) ? $statusVal : 'active';

                    $data['code'] = $data['code'] ?: null;

                    // Nếu có mã môn học và đã tồn tại -> bỏ qua để tránh lỗi UNIQUE
                    if (!empty($data['code'])) {
                        $checkCodeStmt->execute([$data['code']]);
                        if ((int)$checkCodeStmt->fetchColumn() > 0) {
                            $skipCount++;
                            $rowErrors[] = "Dòng " . ($rowIndex + 2) . ": mã môn học '{$data['code']}' đã tồn tại, đã bỏ qua.";
                            continue;
                        }
                    }

                    try {
                        $insertStmt->execute([
                            ':name'        => $data['name'],
                            ':code'        => $data['code'],
                            ':description' => $data['description'],
                            ':icon'        => $data['icon'],
                            ':color'       => $data['color'],
                            ':status'      => $data['status'],
                        ]);
                        $successCount++;
                    } catch (PDOException $ex) {
                        $skipCount++;
                        $rowErrors[] = "Dòng " . ($rowIndex + 2) . ": lỗi khi thêm - " . $ex->getMessage();
                    }
                }

                $import_report = [
                    'success' => $successCount,
                    'skipped' => $skipCount,
                    'errors'  => $rowErrors,
                ];

                $msg = "Import hoàn tất: thêm mới $successCount môn học" .
                       ($skipCount > 0 ? ", bỏ qua $skipCount dòng." : ".");
            }
        }
    } catch (PDOException $ex) {
        $error = "Lỗi xử lý: " . $ex->getMessage();
    } catch (Exception $ex) {
        $error = "Lỗi import: " . $ex->getMessage();
    }
}

/* =========================================================
   TẢI TEMPLATE MẪU (tải trực tiếp, không cần tạo file lưu trên server)
   ========================================================= */
if (($_GET['download'] ?? '') === 'template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_import_mon_hoc.csv"');
    echo "\xEF\xBB\xBF"; // BOM để Excel hiển thị đúng tiếng Việt
    echo "name,code,description,icon,color,status\n";
    echo "Lập trình Python,PY,Ngôn ngữ lập trình Python,code,#3776AB,active\n";
    echo "An toàn thông tin,ATTT,Bảo mật và an toàn hệ thống,shield-lock,#dc2626,active\n";
    exit;
}

if (($_GET['download'] ?? '') === 'template_xlsx') {
    try {
        $templateRows = [
            ['name', 'code', 'description', 'icon', 'color', 'status'],
            ['Lập trình Python', 'PY', 'Ngôn ngữ lập trình Python', 'code', '#3776AB', 'active'],
            ['An toàn thông tin', 'ATTT', 'Bảo mật và an toàn hệ thống', 'shield-lock', '#dc2626', 'active'],
        ];
        $xlsxContent = buildXlsxTemplate($templateRows);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_import_mon_hoc.xlsx"');
        header('Content-Length: ' . strlen($xlsxContent));
        echo $xlsxContent;
    } catch (Exception $ex) {
        // Không tạo được xlsx (thiếu ZipArchive) -> quay lại trang với thông báo lỗi
        header('Location: subjects.php?xlsx_template_error=' . urlencode($ex->getMessage()));
    }
    exit;
}

// Lấy danh sách Môn học
$subjects = [];
try {
    $stmt = $pdo->query("SELECT * FROM subjects ORDER BY id DESC");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Lấy danh sách Giảng viên
$teachers = [];
try {
    $stmt_teachers = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY id DESC");
    $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Lấy danh sách Phân công
$teacher_assignments = [];
try {
    $stmt_assign = $pdo->query("
        SELECT ts.subject_id, ts.teacher_id, u.*
        FROM teacher_subjects ts
        JOIN users u ON ts.teacher_id = u.id
    ");
    while ($row = $stmt_assign->fetch(PDO::FETCH_ASSOC)) {
        $teacher_assignments[$row['subject_id']][] = $row;
    }
} catch (PDOException $e) {}

include '../includes/header_admin.php';
?>

<style>
/* ===== SUBJECTS ADMIN UI =====
   Không đặt background cho .admin-page-head:
   màu header dùng theme chung của Admin. */
.subjects-wrapper{animation:subjectsFade .25s ease}
@keyframes subjectsFade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}

.subjects-wrapper .subjects-card{
  background:#fff;border:1px solid #edf0f5!important;border-radius:18px!important;
  box-shadow:0 8px 26px rgba(15,23,42,.055)!important;overflow:hidden;
}
.subjects-wrapper .subjects-toolbar{
  display:flex;align-items:center;gap:10px;flex-wrap:wrap;
  padding:12px;background:#fff;border:1px solid #edf0f5;border-radius:15px;margin-bottom:14px;
}
.subjects-wrapper .subjects-search{flex:1 1 320px;max-width:440px}
.subjects-wrapper .filter-control{
  min-height:42px;border:1px solid #e2e8f0!important;border-radius:11px!important;
  background:#f8fafc!important;box-shadow:none!important;
}
.subjects-wrapper .subjects-search .input-group-text{border-radius:11px 0 0 11px!important}
.subjects-wrapper .subjects-search .form-control{border-radius:0 11px 11px 0!important}
.subjects-wrapper .filter-control:focus{
  background:#fff!important;border-color:#a78bfa!important;
  box-shadow:0 0 0 3px rgba(124,58,237,.10)!important;
}
.subjects-wrapper .subjects-count{
  display:inline-flex;align-items:center;gap:6px;padding:.48rem .7rem;border-radius:10px;
  background:#f5f3ff;color:#6d28d9;font-size:.78rem;font-weight:700;
}

.subjects-wrapper .creative-table{border-collapse:separate!important;border-spacing:0 8px!important}
.subjects-wrapper .creative-table thead th{
  border:0!important;padding:.78rem 1rem!important;white-space:nowrap;
  color:#697386!important;font-size:.72rem!important;font-weight:800!important;
  letter-spacing:.055em;text-transform:uppercase;background:transparent!important;
}
.subjects-wrapper .creative-table tbody td{
  border:0!important;padding:.9rem 1rem!important;background:#f8fafc!important;vertical-align:middle;
}
.subjects-wrapper .creative-table tbody tr:hover td{background:#f3f0ff!important}
.subjects-wrapper .creative-table tbody td:first-child{border-radius:12px 0 0 12px}
.subjects-wrapper .creative-table tbody td:last-child{border-radius:0 12px 12px 0}

.subjects-wrapper .subject-icon{
  width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex:0 0 42px;
  background:#ede9fe;color:#6d28d9;font-size:1.05rem;
}
.subjects-wrapper .subject-desc{
  max-width:310px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  color:#7b8494;font-size:.79rem;margin-top:3px;
}
.subjects-wrapper .teacher-badge{
  display:inline-flex;align-items:center;gap:.35rem;padding:.34rem .62rem;
  border:1px solid #ddd6fe;border-radius:999px;background:#f5f3ff;color:#6d28d9;
  font-size:.77rem;font-weight:650;
}
.subjects-wrapper .teacher-badge .btn-remove-teacher{
  border:0;background:transparent;color:#7c3aed;padding:0;line-height:1;font-size:.95rem;opacity:.7
}
.subjects-wrapper .teacher-badge .btn-remove-teacher:hover{color:#dc2626;opacity:1}
.subjects-wrapper .assign-teacher-select{min-width:185px;min-height:38px}
.subjects-wrapper .action-btn-circle{
  width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;
  border-radius:10px!important;border:1px solid #e8ebf1!important;background:#fff!important;
  box-shadow:0 2px 7px rgba(15,23,42,.04);transition:.18s
}
.subjects-wrapper .action-btn-circle:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(15,23,42,.09)}
.subjects-wrapper .bulk-toolbar{
  display:none;align-items:center;gap:.75rem;padding:.72rem .9rem;margin-bottom:12px;
  border:1px solid #fecaca;border-radius:14px;background:#fff7f7
}
.subjects-wrapper .bulk-toolbar.show{display:flex}
.subjects-wrapper .import-report{
  max-height:220px;overflow:auto;font-size:.82rem;background:#fff7ed;
  border:1px solid #fed7aa;border-radius:14px;padding:.8rem 1rem
}
.subjects-wrapper .empty-state{padding:4rem 1rem;text-align:center;color:#7b8494}
.subjects-wrapper .empty-icon{
  width:62px;height:62px;display:grid;place-items:center;margin:0 auto 12px;
  border-radius:18px;background:#f3f0ff;color:#6d28d9
}
.subjects-wrapper .form-check-input:checked{background-color:#6d28d9;border-color:#6d28d9}

/* Modal Create/Edit/Import */
.subject-modal .modal-content{
  border:0!important;border-radius:22px!important;overflow:hidden;
  box-shadow:0 28px 70px rgba(30,27,75,.22)!important
}
.subject-modal .modal-header{
  position:relative;border:0!important;padding:1.2rem 1.4rem!important;
  background:linear-gradient(135deg,#4c1d95,#6d28d9)!important;color:#fff!important
}
.subject-modal .modal-header:after{
  content:"";position:absolute;width:145px;height:145px;border-radius:50%;
  right:-50px;top:-80px;background:rgba(255,255,255,.10)
}
.subject-modal .modal-title{position:relative;z-index:1;color:#fff!important;font-weight:800!important}
.subject-modal .modal-title i{color:#fff!important}
.subject-modal .btn-close{position:relative;z-index:2;filter:brightness(0) invert(1);opacity:.9}
.subject-modal .modal-body{padding:1.35rem!important;background:#fbfcfe}
.subject-modal .modal-footer{border:0!important;padding:1rem 1.4rem 1.3rem!important;background:#fff}
.subject-modal .form-label{font-size:.82rem;font-weight:700!important;color:#4b5563!important}
.subject-modal .form-control,.subject-modal .form-select{
  min-height:44px;border-radius:11px!important;border:1px solid #dde3eb!important;background:#fff!important
}
.subject-modal textarea.form-control{min-height:100px}
.subject-modal .form-control:focus,.subject-modal .form-select:focus{
  border-color:#a78bfa!important;box-shadow:0 0 0 3px rgba(124,58,237,.10)!important
}
.subject-modal .modal-footer .btn{min-height:42px;border-radius:11px!important;font-weight:700}
.subject-modal .btn-primary{
  background:linear-gradient(135deg,#6d28d9,#7c3aed)!important;border:0!important;
  box-shadow:0 6px 14px rgba(124,58,237,.18)
}
.subject-modal .import-note-box{
  background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:1rem;
  font-size:.84rem;color:#334155;margin-bottom:1rem
}
.subject-modal .import-note-box code{
  display:inline-block;background:#ede9fe;color:#5b21b6;padding:.13rem .38rem;
  border-radius:6px;font-size:.77rem;margin:2px 0
}
.subject-modal .import-columns{
  margin-top:.7rem;padding:.65rem .75rem;border-radius:10px;background:#fff;border:1px dashed #d8dee8
}

@media(max-width:991.98px){
  .subjects-wrapper .subjects-search{max-width:none}
  .subjects-wrapper .subjects-toolbar>*{flex:1 1 100%}
}
@media(max-width:767.98px){
  .subjects-wrapper .header-actions{display:grid!important;grid-template-columns:1fr;width:100%}
  .subjects-wrapper .header-actions .btn{width:100%}
  .subjects-wrapper .bulk-toolbar{align-items:flex-start;flex-direction:column}
  .subjects-wrapper .bulk-toolbar .btn{margin-left:0!important;width:100%}
  .subject-modal .modal-dialog{margin:.65rem}
}

/* Import dữ liệu chuẩn dùng chung với trang Phòng */
.data-import-modal .modal-content{border:0!important;border-radius:1.25rem!important;overflow:hidden;box-shadow:0 24px 60px rgba(15,23,42,.22)!important}
.data-import-modal .modal-header{background:#fff!important;color:#172033!important;border:0!important;padding:1.15rem 1.25rem .7rem!important}
.data-import-modal .modal-header:after{display:none!important}
.data-import-modal .modal-title,.data-import-modal .modal-title i{color:#172033!important}
.data-import-modal .modal-title i{color:#16a34a!important}
.data-import-modal .btn-close{filter:none!important;opacity:.55}
.data-import-modal .modal-body{background:#fff!important;padding:.7rem 1.25rem 1.25rem!important}
.data-import-modal .modal-footer{background:#fff!important;border:0!important;padding:0 1.25rem 1.25rem!important}
.data-import-modal .modal-footer .btn{min-height:42px;border-radius:.65rem!important;font-weight:700}
.data-import-drop{border:1.5px dashed #c4b5fd;background:linear-gradient(180deg,#faf8ff,#fff);border-radius:1rem;padding:1.1rem}
.data-import-format{background:#f8fafc;border:1px solid #e7ebf1;border-radius:.85rem;padding:.85rem 1rem;font-size:.85rem}
.data-import-format code{color:#6d28d9;line-height:1.8}
</style>

<div class="container-fluid px-0 subjects-wrapper">

  <!-- Header Banner -->
  <div class="card border-0 text-white mb-4 shadow-sm admin-page-head subjects-header">
    <div class="card-body p-3 p-md-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
      <div>
        <h3 class="fw-bold mb-1 fs-4 fs-md-3"><i class="bi bi-journal-bookmark-fill me-2"></i>Quản lý môn học & Phân công</h3>
        <p class="text-white-50 mb-0 small">Thêm mới môn học, nhập mô tả và phân công giảng viên giảng dạy.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap header-actions">
        <button type="button" class="btn btn-outline-light fw-semibold px-3 py-2 rounded-3 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalImportSubject">
          <i class="bi bi-file-earmark-arrow-up me-1"></i> Import file
        </button>
        <button type="button" class="btn btn-light text-primary fw-semibold px-3 py-2 rounded-3 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalCreateSubject">
          <i class="bi bi-journal-plus me-1"></i> Thêm môn học
        </button>
      </div>
    </div>
  </div>

  <!-- Thông báo -->
  <?php if (!empty($msg)): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($_GET['xlsx_template_error'])): ?>
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($_GET['xlsx_template_error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Chi tiết các dòng bị bỏ qua khi import (nếu có) -->
  <?php if ($import_report && !empty($import_report['errors'])): ?>
    <div class="import-report mb-3">
      <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i>Chi tiết các dòng bị bỏ qua:</div>
      <ul class="mb-0 ps-3">
        <?php foreach ($import_report['errors'] as $line): ?>
          <li><?= e($line) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Tìm kiếm nhanh -->
  <div class="subjects-toolbar">
    <div class="subjects-search">
      <div class="input-group">
        <span class="input-group-text filter-control border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="subjectSearch" class="form-control filter-control border-start-0"
               placeholder="Tìm môn học, mô tả, giảng viên..." oninput="filterSubjects()">
      </div>
    </div>
    <div class="ms-auto">
      <span class="subjects-count"><i class="bi bi-journals"></i><?= count($subjects) ?> môn học</span>
    </div>
  </div>

  <!-- Thanh công cụ xóa hàng loạt (chỉ hiện khi có dòng được chọn) -->
  <form method="POST" action="subjects.php" id="bulkDeleteForm" onsubmit="return confirm('Xóa toàn bộ ' + document.querySelectorAll('.row-checkbox:checked').length + ' môn học đã chọn? Dữ liệu phân công liên quan cũng sẽ bị xóa.');">
    <input type="hidden" name="action" value="bulk_delete">
    <div class="bulk-toolbar" id="bulkToolbar">
      <i class="bi bi-check2-square text-danger fs-5"></i>
      <span class="fw-semibold text-danger"><span id="bulkSelectedCount">0</span> môn học đã chọn</span>
      <button type="submit" class="btn btn-danger btn-sm rounded-3 ms-auto">
        <i class="bi bi-trash me-1"></i> Xóa đã chọn
      </button>
    </div>

    <!-- Bảng Môn Học -->
    <div class="card subjects-card">
      <div class="card-body p-3 pt-2">
        <?php if (empty($subjects)): ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-journal-x fs-3"></i></div>
            <div class="fw-bold text-dark mb-1">Chưa có môn học</div>
            <div class="small">Thêm môn học mới hoặc import dữ liệu để bắt đầu.</div>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table creative-table align-middle mb-0">
              <thead>
                <tr>
                  <th class="ps-3" style="width: 40px;">
                    <input type="checkbox" class="form-check-input" id="selectAllCheckbox">
                  </th>
                  <th style="width: 60px;">ID</th>
                  <th style="min-width: 200px;">THÔNG TIN MÔN HỌC</th>
                  <th style="min-width: 220px;">GIẢNG VIÊN PHỤ TRÁCH</th>
                  <th style="min-width: 200px;">PHÂN CÔNG MỚI</th>
                  <th class="text-end pe-3" style="width: 100px;">THAO TÁC</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($subjects as $s): ?>
                  <?php
                    $sid          = (int)$s['id'];
                    $sname        = $s['name'] ?? 'Môn học không tên';
                    $sdesc        = $s['description'] ?? '';
                    $assigned_list= isset($teacher_assignments[$sid]) ? $teacher_assignments[$sid] : [];
                  ?>
                  <?php
                    $teacher_search_names = [];
                    foreach ($assigned_list as $ta) {
                      $teacher_search_names[] = $ta['fullname'] ?? ($ta['name'] ?? ($ta['username'] ?? ''));
                    }
                    $row_search = mb_strtolower($sname . ' ' . $sdesc . ' ' . implode(' ', $teacher_search_names), 'UTF-8');
                  ?>
                  <tr class="subject-row" data-search="<?= e($row_search) ?>">
                    <td class="ps-3">
                      <input type="checkbox" class="form-check-input row-checkbox" name="ids[]" value="<?= $sid ?>">
                    </td>
                    <td class="fw-bold text-secondary">
                      #<?= $sid ?>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="subject-icon"><i class="bi bi-book"></i></div>
                        <div class="min-w-0">
                          <div class="fw-bold text-dark fs-6"><?= e($sname) ?></div>
                          <?php if (!empty($sdesc)): ?>
                            <div class="subject-desc" title="<?= e($sdesc) ?>"><?= e($sdesc) ?></div>
                          <?php else: ?>
                            <div class="subject-desc opacity-50">Chưa có mô tả</div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1.5 align-items-center">
                        <?php if (empty($assigned_list)): ?>
                          <span class="text-muted small fs-7 fs-italic"><i class="bi bi-info-circle me-1"></i>Chưa phân công</span>
                        <?php else: ?>
                          <?php foreach ($assigned_list as $t): ?>
                            <?php
                              $t_fullname = !empty($t['fullname']) ? $t['fullname'] : (!empty($t['name']) ? $t['name'] : $t['username']);
                            ?>
                            <span class="teacher-badge">
                              <i class="bi bi-person-badge"></i>
                              <?= e($t_fullname) ?>
                              <button type="button" class="btn-remove-teacher"
                                      title="Gỡ phân công"
                                      onclick="removeTeacher(<?= $sid ?>, <?= (int)$t['teacher_id'] ?>)">&times;</button>
                            </span>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex gap-1 align-items-center">
                        <select class="form-select form-select-sm bg-white border-light-subtle rounded-3 assign-teacher-select" data-subject-id="<?= $sid ?>" style="font-size: 0.8rem;">
                          <option value="">+ Chọn giảng viên...</option>
                          <?php foreach ($teachers as $tch): ?>
                            <?php
                              $tch_name = !empty($tch['fullname']) ? $tch['fullname'] : (!empty($tch['name']) ? $tch['name'] : $tch['username']);
                            ?>
                            <option value="<?= $tch['id'] ?>"><?= e($tch_name) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-sm btn-primary rounded-3 text-nowrap px-2.5" style="font-size: 0.8rem;" onclick="assignTeacher(<?= $sid ?>, this)">
                          Gán
                        </button>
                      </div>
                    </td>
                    <td class="text-end pe-3">
                      <div class="d-flex align-items-center justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-light text-primary action-btn-circle"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditSubject<?= $sid ?>"
                                title="Chỉnh sửa môn học">
                          <i class="bi bi-pencil"></i>
                        </button>

                        <button type="button" class="btn btn-sm btn-light text-danger action-btn-circle"
                                title="Xóa môn học"
                                onclick="deleteSingle(<?= $sid ?>)">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>

                      <!-- Modal Sửa Môn Học -->
                      <div class="modal fade text-start subject-modal" id="modalEditSubject<?= $sid ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Chỉnh sửa môn học</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="subjects.php">
                              <input type="hidden" name="action" value="update">
                              <input type="hidden" name="id" value="<?= $sid ?>">
                              <div class="modal-body">
                                <div class="mb-3">
                                  <label class="form-label small fw-bold text-secondary">Tên môn học <span class="text-danger">*</span></label>
                                  <input type="text" name="subject_name" class="form-control rounded-3" value="<?= e($sname) ?>" required>
                                </div>
                                <div class="mb-3">
                                  <label class="form-label small fw-bold text-secondary">Mô tả môn học</label>
                                  <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Nhập tóm tắt nội dung môn học..."><?= e($sdesc) ?></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
                                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Lưu thay đổi</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </form>

</div>

<!-- Form ẩn dùng chung cho các action gọi qua JS (xóa 1 dòng, gán/gỡ giảng viên) -->
<form method="POST" action="subjects.php" id="hiddenActionForm" style="display:none;">
  <input type="hidden" name="action" id="hiddenAction" value="">
  <input type="hidden" name="id" id="hiddenId" value="">
  <input type="hidden" name="subject_id" id="hiddenSubjectId" value="">
  <input type="hidden" name="teacher_id" id="hiddenTeacherId" value="">
</form>

<!-- Modal Tạo Môn Học Mới -->
<div class="modal fade subject-modal" id="modalCreateSubject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-journal-plus text-primary me-2"></i>Thêm môn học mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="subjects.php">
        <input type="hidden" name="action" value="create">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Tên môn học <span class="text-danger">*</span></label>
            <input type="text" name="subject_name" class="form-control rounded-3" placeholder="vd: Lập trình Web PHP, Đô thị thông minh..." required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Mô tả môn học</label>
            <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Nhập tóm tắt nội dung môn học..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
          <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Thêm môn học</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Import Môn Học từ file - đồng bộ chuẩn Import dữ liệu -->
<div class="modal fade data-import-modal" id="modalImportSubject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="subjects.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="import">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import môn học</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div class="data-import-drop mb-3">
            <label class="form-label fw-semibold">Chọn file dữ liệu</label>
            <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx,.xls" required>
            <div class="form-text mt-2">Tối thiểu cần <b>Tên môn học</b>. Các cột còn lại có thể để trống nếu hệ thống cho phép.</div>
          </div>
          <div class="data-import-format">
            <div class="fw-bold mb-2"><i class="bi bi-info-circle me-1 text-primary"></i>Cột hỗ trợ</div>
            <code>name, code, description, icon, color, status</code>
            <hr class="my-2">
            <div class="text-muted">Có thể dùng tiêu đề tiếng Việt như: <b>Tên môn học, Mã môn học, Mô tả, Biểu tượng, Màu, Trạng thái</b>.</div>
            <div class="d-flex gap-3 flex-wrap mt-3">
              <a href="subjects.php?download=template" class="fw-semibold"><i class="bi bi-filetype-csv me-1"></i>Tải mẫu CSV</a>
              <a href="subjects.php?download=template_xlsx" class="fw-semibold"><i class="bi bi-filetype-xlsx me-1"></i>Tải mẫu XLSX</a>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-success px-4"><i class="bi bi-upload me-1"></i>Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function filterSubjects() {
    const keyword = (document.getElementById('subjectSearch')?.value || '').toLocaleLowerCase('vi').trim();
    document.querySelectorAll('.subject-row').forEach(row => {
      const haystack = (row.dataset.search || '').toLocaleLowerCase('vi');
      row.style.display = (!keyword || haystack.includes(keyword)) ? '' : 'none';
    });
  }


  // ===== Chọn tất cả / bỏ chọn tất cả + hiện thanh xóa hàng loạt =====
  const selectAllCheckbox = document.getElementById('selectAllCheckbox');
  const rowCheckboxes = document.querySelectorAll('.row-checkbox');
  const bulkToolbar = document.getElementById('bulkToolbar');
  const bulkSelectedCount = document.getElementById('bulkSelectedCount');

  function updateBulkToolbar() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    bulkSelectedCount.textContent = checked;
    bulkToolbar.classList.toggle('show', checked > 0);
  }

  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', () => {
      rowCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
      updateBulkToolbar();
    });
  }

  rowCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkToolbar));

  // ===== Xóa 1 môn học (dùng lại form ẩn) =====
  function deleteSingle(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa môn học này? Dữ liệu phân công cũng sẽ bị xóa.')) return;
    document.getElementById('hiddenAction').value = 'delete';
    document.getElementById('hiddenId').value = id;
    document.getElementById('hiddenActionForm').submit();
  }

  // ===== Gán giảng viên =====
  function assignTeacher(subjectId, btnEl) {
    const select = document.querySelector(`.assign-teacher-select[data-subject-id="${subjectId}"]`);
    const teacherId = select ? select.value : '';
    if (!teacherId) {
      alert('Vui lòng chọn giảng viên trước khi gán.');
      return;
    }
    document.getElementById('hiddenAction').value = 'assign_teacher';
    document.getElementById('hiddenSubjectId').value = subjectId;
    document.getElementById('hiddenTeacherId').value = teacherId;
    document.getElementById('hiddenActionForm').submit();
  }

  // ===== Hủy gán giảng viên =====
  function removeTeacher(subjectId, teacherId) {
    if (!confirm('Hủy phân công giảng viên này khỏi môn học?')) return;
    document.getElementById('hiddenAction').value = 'remove_teacher';
    document.getElementById('hiddenSubjectId').value = subjectId;
    document.getElementById('hiddenTeacherId').value = teacherId;
    document.getElementById('hiddenActionForm').submit();
  }
</script>

</main> <!-- Đóng thẻ main từ header_admin.php -->
<?php include '../includes/footer.php'; ?>