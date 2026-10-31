<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/qt_teacher_import.php';
requireTeacher();

$teacherId = (int)((function_exists('currentUser') ? (currentUser()['id'] ?? null) : null) ?? ($_SESSION['user_id'] ?? null) ?? ($_SESSION['user']['id'] ?? 0));
$stmt=$pdo->prepare("SELECT id,name FROM subjects WHERE created_by=? OR id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id=?) ORDER BY name");
$stmt->execute([$teacherId,$teacherId]);
$subjects=$stmt->fetchAll(PDO::FETCH_ASSOC);
$allowedIds=array_map('intval',array_column($subjects,'id'));
$errors=[];$success=0;$imported=0;$preview=[];$fileName='';$selectedSubject=0;
if (((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') ?? 'GET')==='POST' && ($_POST['action'] ?? '')==='import') {
    verify_csrf();
    $selectedSubject=(int)($_POST['subject_id'] ?? 0);
    $upload=$_FILES['question_file'] ?? null;
    if (!in_array($selectedSubject,$allowedIds,true)) $errors[]='Môn học không hợp lệ hoặc không thuộc quyền của bạn.';
    if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) $errors[]='Chưa chọn file hoặc tải file lên thất bại.';
    if (!$errors) {
        $fileName=basename((string)$upload['name']);
        $ext=strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
        if (!in_array($ext,['csv','txt','xlsx','docx'],true)) $errors[]='Định dạng không hợp lệ. Chỉ nhận CSV, XLSX, DOCX.';
        if ((int)$upload['size']>5*1024*1024 || (int)$upload['size']<=0) $errors[]='File phải có dung lượng từ 1 byte đến 5 MB.';
        if (!is_uploaded_file($upload['tmp_name'])) $errors[]='File upload không hợp lệ.';
        if (!$errors) {
            try {
                [$valid,$validationErrors]=qt_import_validate(qt_import_rows($upload['tmp_name'],$ext));
                $preview=array_slice($valid,0,5);
                $errors=array_merge($errors,$validationErrors);
                if (!$valid && !$errors) $errors[]='File không có câu hỏi để import.';
                if (!$errors) {
                    $pdo->beginTransaction();
                    try {
                        $insert=$pdo->prepare('INSERT INTO questions (subject_id,created_by,content,option_a,option_b,option_c,option_d,correct_answer,difficulty,explanation,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())');
                        foreach ($valid as $item) {
                            $r=$item['data'];
                            $insert->execute([$selectedSubject,$teacherId,$r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7]]);
                            $imported++;
                        }
                        $pdo->commit();
                        $success=$imported;
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        $imported=0;
                        $errors[]='Không thể lưu dữ liệu. Toàn bộ thao tác đã được hoàn tác.';
                    }
                }
            } catch (Throwable $e) {
                $errors[]=$e->getMessage();
            }
        }
    }
}
$page_title='Import câu hỏi';
include '../includes/header_teacher.php';
?>
<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-import">
    <section class="qt-t-hero qt-i-hero">
        <div class="qt-t-hero-icon"><i class="bi bi-cloud-arrow-up"></i></div>
        <div class="qt-t-hero-copy"><div class="qt-t-eyebrow">QUIZTECH · TEACHER</div><h1>Import câu hỏi</h1><p>Nhập câu hỏi hàng loạt từ Excel, Word hoặc CSV</p></div>
        <a class="qt-t-hero-home" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
    </section>
    <section class="qt-i-top">
        <div><h2>Nhập câu hỏi theo mẫu</h2><p>Chọn môn học, tải template và import dữ liệu. Hệ thống kiểm tra toàn bộ file trước khi lưu.</p></div>
        <a href="questions.php" class="qt-i-back"><i class="bi bi-arrow-left"></i> Về ngân hàng câu hỏi</a>
    </section>
    <section class="qt-i-steps" aria-label="Các bước import">
        <div><b>01</b><span>Tải template</span></div><i class="bi bi-arrow-right"></i>
        <div><b>02</b><span>Nhập dữ liệu</span></div><i class="bi bi-arrow-right"></i>
        <div><b>03</b><span>Tải file & kiểm tra</span></div><i class="bi bi-arrow-right"></i>
        <div><b>04</b><span>Import thành công</span></div>
    </section>

    <?php if ($success>0): ?>
    <div class="qt-i-alert success" role="status"><i class="bi bi-check-circle-fill"></i><div><strong>Import thành công <?= $success ?> câu hỏi!</strong><p>Tất cả câu hỏi đã được thêm vào ngân hàng câu hỏi của bạn.</p><a href="questions.php">Xem ngân hàng câu hỏi <i class="bi bi-arrow-right"></i></a></div></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="qt-i-alert error" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div><strong>Import chưa được thực hiện – <?= count($errors) ?> vấn đề cần sửa</strong><p>Không có câu hỏi nào được lưu từ lần import này. Hãy sửa file và tải lại.</p><ul><?php foreach(array_slice($errors,0,100) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php if(count($errors)>100): ?><p>…và <?= count($errors)-100 ?> lỗi khác.</p><?php endif; ?></div></div>
    <?php endif; ?>

    <div class="qt-i-layout">
        <section class="qt-i-card">
            <header class="qt-i-card-head"><i class="bi bi-cloud-arrow-up"></i><div><h3>Tải lên dữ liệu</h3><p>Chọn file và môn học cần import</p></div></header>
            <div class="qt-i-card-body">
                <form action="import.php" method="POST" enctype="multipart/form-data" id="qtImportForm">
                    <?php csrf_field(); ?><input type="hidden" name="action" value="import">
                    <label class="qt-i-label" for="qtSubject">Môn học <span>*</span></label>
                    <select name="subject_id" id="qtSubject" class="form-select" required>
                        <option value="">— Chọn môn học —</option>
                        <?php foreach($subjects as $sub): ?><option value="<?= (int)$sub['id'] ?>" <?= $selectedSubject===(int)$sub['id']?'selected':'' ?>><?= e($sub['name']) ?></option><?php endforeach; ?>
                    </select>
                    <label class="qt-i-label" for="qtImportFile">File dữ liệu <span>*</span></label>
                    <label class="qt-i-drop" id="qtDropArea" for="qtImportFile" tabindex="0">
                        <i class="bi bi-file-earmark-arrow-up"></i>
                        <strong>Chọn hoặc kéo thả file vào đây</strong>
                        <span>Excel (.xlsx), Word (.docx), CSV (.csv) · tối đa 5 MB</span>
                        <em id="qtSelectedFile">Chưa chọn file</em>
                    </label>
                    <input id="qtImportFile" name="question_file" type="file" accept=".xlsx,.docx,.csv,.txt" required class="qt-i-file-input">
                    <div class="qt-i-file-details" id="qtFileDetails" hidden><i class="bi bi-file-earmark-check"></i><span id="qtFileInfo"></span><button type="button" id="qtClearFile" aria-label="Bỏ chọn file"><i class="bi bi-x-circle"></i></button></div>
                    <div class="qt-i-safe"><i class="bi bi-shield-check"></i> Kiểm tra toàn bộ dữ liệu trước khi lưu; nếu có lỗi, không import một phần.</div>
                    <button class="qt-i-submit" type="submit" id="qtImportSubmit"><i class="bi bi-cloud-arrow-up"></i> Kiểm tra & Import câu hỏi</button>
                </form>
            </div>
        </section>

        <section class="qt-i-card">
            <header class="qt-i-card-head"><i class="bi bi-file-earmark-text"></i><div><h3>Tải template chuẩn</h3><p>Chọn định dạng phù hợp để nhập dữ liệu</p></div></header>
            <div class="qt-i-card-body">
                <div class="qt-i-template-list">
                    <a href="../templates/quiztech_questions_template.xlsx" download class="qt-i-template excel"><i class="bi bi-file-earmark-excel"></i><span><strong>Excel (.xlsx)</strong><small>Dễ chỉnh sửa, phù hợp nhiều câu hỏi</small></span><i class="bi bi-download"></i></a>
                    <a href="../templates/quiztech_questions_template.docx" download class="qt-i-template word"><i class="bi bi-file-earmark-word"></i><span><strong>Word (.docx)</strong><small>Bảng 8 cột, không gộp ô</small></span><i class="bi bi-download"></i></a>
                    <a href="../templates/quiztech_questions_template.csv" download class="qt-i-template csv"><i class="bi bi-filetype-csv"></i><span><strong>CSV (.csv)</strong><small>UTF-8, phân tách bằng dấu phẩy</small></span><i class="bi bi-download"></i></a>
                </div>
                <div class="qt-i-help"><i class="bi bi-lightbulb"></i><span>Chỉ cần điền nội dung theo template. Cột <strong>correct_answer</strong> nhập A/B/C/D; <strong>difficulty</strong> nhập easy/medium/hard.</span></div>
            </div>
        </section>
    </div>

    <section class="qt-i-card qt-i-guide">
        <header class="qt-i-card-head"><i class="bi bi-table"></i><div><h3>Quy tắc dữ liệu & định dạng 8 cột</h3><p>Áp dụng chung cho CSV, Excel và bảng Word</p></div></header>
        <div class="qt-i-card-body">
            <div class="qt-i-table-wrap"><table><thead><tr><th>STT</th><th>Tên cột</th><th>Bắt buộc</th><th>Quy tắc</th></tr></thead><tbody>
            <?php
            $definitions=[
                ['content','Có','Nội dung câu hỏi, không để trống'],
                ['option_a','Có','Phương án A'],
                ['option_b','Có','Phương án B'],
                ['option_c','Không','Phương án C (có thể để trống)'],
                ['option_d','Không','Phương án D (có thể để trống)'],
                ['correct_answer','Có','A, B, C hoặc D; phương án đúng phải có nội dung'],
                ['difficulty','Không','easy / medium / hard; mặc định easy'],
                ['explanation','Không','Giải thích đáp án đúng']
            ];
            foreach($definitions as $i=>$d): ?>
                <tr><td><?= $i+1 ?></td><td><code><?= e($d[0]) ?></code></td><td><span class="qt-i-required <?= $d[1]==='Có'?'yes':'no' ?>"><?= e($d[1]) ?></span></td><td><?= e($d[2]) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <div class="qt-i-guidelines">
                <p><i class="bi bi-check-circle"></i> Dòng đầu tiên bắt buộc là tiêu đề đúng 8 cột, đúng thứ tự.</p>
                <p><i class="bi bi-check-circle"></i> Mỗi dòng/bảng Word là một câu hỏi; không gộp ô; tối đa 1.000 câu hỏi/lần.</p>
                <p><i class="bi bi-check-circle"></i> Dữ liệu nhập được gắn với tài khoản Teacher đang đăng nhập và môn học đã chọn.</p>
                <p><i class="bi bi-info-circle"></i> Nếu CSV có dấu phẩy trong nội dung, dùng dấu ngoặc kép theo chuẩn CSV.</p>
            </div>
        </div>
    </section>
</main>
<script src="../assets/js/qt-teacher-import.js?v=1" defer></script>
<?php echo '</div></div>'; include '../includes/footer.php'; ?>
