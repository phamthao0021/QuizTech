<?php
// Student feedback: three categories, existing practice_feedback table.
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole('student');
$user = currentUser();
$user_id = (int)($user['id'] ?? $_SESSION['user']['id'] ?? 0);
$exam_id = max(0, (int)($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0));
$tabs = [
 'question_report'=>['name'=>'Báo cáo lỗi đề / câu hỏi','icon'=>'bi-flag','label'=>'Tiêu đề báo lỗi','placeholder'=>'VD: Câu 5 đề kiểm tra PHP bị sai đáp án'],
 'subject_request'=>['name'=>'Đề xuất môn mới','icon'=>'bi-book','label'=>'Tên môn học đề xuất','placeholder'=>'VD: Lập trình Python nâng cao'],
 'feedback'=>['name'=>'Góp ý cải thiện chức năng / màn hình','icon'=>'bi-lightbulb','label'=>'Tiêu đề góp ý','placeholder'=>'VD: Cải thiện giao diện màn hình luyện tập']
];
$active = (string)($_POST['type'] ?? $_GET['tab'] ?? ($exam_id ? 'question_report':'question_report'));
if (!isset($tabs[$active])) $active='question_report';
$title = (string)($_POST['title'] ?? '');
$content = (string)($_POST['content'] ?? '');
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
 verify_csrf();
 $title=trim($title);$content=trim($content);
 if ($user_id<1) $errors[]='Phiên đăng nhập không hợp lệ.';
 if (mb_strlen($title,'UTF-8')<3 || mb_strlen($title,'UTF-8')>255) $errors[]='Tiêu đề cần từ 3 đến 255 ký tự.';
 if (mb_strlen($content,'UTF-8')<10 || mb_strlen($content,'UTF-8')>2600) $errors[]='Nội dung cần từ 10 đến 2600 ký tự.';
 if ($exam_id && $active==='question_report') {
  try {
   $q=$pdo->prepare('SELECT id FROM exams WHERE id=? LIMIT 1');$q->execute([$exam_id]);
   if (!$q->fetchColumn()) $errors[]='Đề thi không tồn tại.';
  } catch (Throwable $e) { $errors[]='Không xác minh được đề thi.'; }
 }
 $file = $_FILES['prototype_image'] ?? null;
 $hasFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
 if ($hasFile && $active!=='feedback') $errors[]='Chỉ được đính kèm ảnh trong tab góp ý màn hình.';
 if ($hasFile && ($file['error'] ?? 1)!==UPLOAD_ERR_OK) $errors[]='Không tải được hình ảnh lên.';
 if ($hasFile && (int)($file['size'] ?? 0)>3*1024*1024) $errors[]='Hình ảnh tối đa 3 MB.';
 $mime='';$ext='';
 if ($hasFile && !$errors) {
  $info = new finfo(FILEINFO_MIME_TYPE);
  $mime = $info->file($file['tmp_name']) ?: '';
  $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($allowed[$mime]) || !@getimagesize($file['tmp_name'])) $errors[]='Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.';
  else $ext=$allowed[$mime];
 }
 $uploadPath = null;
 if (!$errors) {
  try {
   if ($hasFile) {
    $dir=dirname(__DIR__).'/uploads/feedback';
    if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) throw new RuntimeException('Không tạo được thư mục tải ảnh.');
    $filename='feedback_'.bin2hex(random_bytes(16)).'.'.$ext;
    $target=$dir.'/'.$filename;
    if (!move_uploaded_file($file['tmp_name'],$target)) throw new RuntimeException('Không lưu được ảnh.');
    $uploadPath='uploads/feedback/'.$filename;
   }
   // Keep attachment reference in content: no database migration required.
   $savedContent=$content.($uploadPath ? "\n\n[Ảnh góp ý: ".$uploadPath."]" : '');
   $stmt=$pdo->prepare("INSERT INTO practice_feedback(user_id,type,exam_id,title,content,status,created_at) VALUES (?,?,?,?,?,'pending',NOW())");
   $stmt->execute([$user_id,$active,($active==='question_report' && $exam_id>0 ? $exam_id : null),$title,$savedContent]);
   // Notification is best-effort; form submission must remain successful.
   $notice=dirname(__DIR__).'/includes/qt_feedback_admin_notice.php';
   if (is_file($notice)) {
    try {
     require_once $notice;
     if (function_exists('qt_feedback_alert_admin')) {
      qt_feedback_alert_admin($pdo,$user_id,(int)$pdo->lastInsertId(),$active,$title);
     }
    } catch(Throwable $ignored) { error_log('Feedback notice unavailable'); }
   }
   setFlash('success','Đã gửi yêu cầu thành công. Bạn có thể theo dõi trạng thái bên dưới.');
   header('Location: submit_feedback.php?tab='.rawurlencode($active));exit;
  } catch (Throwable $e) {
   if ($uploadPath) @unlink(dirname(__DIR__).'/'.$uploadPath);
   error_log('Student feedback: '.$e->getMessage());
   $errors[]='Chưa thể gửi yêu cầu. Vui lòng thử lại sau.';
  }
 }
}
$history=[];
try {
 $q=$pdo->prepare('SELECT f.*,e.title AS exam_title FROM practice_feedback f LEFT JOIN exams e ON e.id=f.exam_id WHERE f.user_id=? ORDER BY f.created_at DESC');
 $q->execute([$user_id]);$history=$q->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { error_log('Feedback history: '.$e->getMessage()); }
$page_title='Góp ý và đề xuất';
require_once dirname(__DIR__).'/includes/header_student.php';
?>
<link rel="stylesheet" href="../assets/css/qt-student-feedback-tabs.css?v=1">
<section class="container-xl qt-feedback-page">
 <div class="qt-feedback-hero">
  <div><div class="qt-feedback-eyebrow"><i class="bi bi-chat-square-heart"></i> TRUNG TÂM HỖ TRỢ SINH VIÊN</div>
  <h1>Góp ý &amp; Đề xuất</h1>
  <p>Báo cáo lỗi đề thi, đề xuất môn học và đóng góp ý tưởng để QuizTech ngày càng tốt hơn.</p></div>
  <a class="btn btn-light" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
 </div>
 <?php if ($errors): ?><div class="alert alert-danger" role="alert"><?php foreach($errors as $err): ?><div><?= e($err) ?></div><?php endforeach ?></div><?php endif ?>
 <div class="row g-4 align-items-start">
  <div class="col-lg-7">
   <div class="qt-feedback-panel">
    <h2 class="h5 fw-bold mb-3">Gửi yêu cầu mới</h2>
    <div class="qt-feedback-tabs" role="tablist" aria-label="Loại yêu cầu">
     <?php foreach($tabs as $key=>$tab): ?>
      <button type="button" class="qt-feedback-tab <?= $active===$key?'is-active':'' ?>" role="tab" aria-selected="<?= $active===$key?'true':'false' ?>" data-feedback-tab="<?= e($key) ?>">
       <i class="bi <?= e($tab['icon']) ?>"></i><span><?= e($tab['name']) ?></span>
      </button>
     <?php endforeach ?>
    </div>
    <form method="post" enctype="multipart/form-data" id="qtFeedbackForm">
     <?php csrf_field(); ?>
     <input type="hidden" name="type" id="qtFeedbackType" value="<?= e($active) ?>">
     <?php if ($exam_id): ?><input type="hidden" name="exam_id" value="<?= $exam_id ?>"><?php endif ?>
     <div class="mb-3"><label for="qtFeedbackTitle" class="form-label fw-semibold" id="qtFeedbackTitleLabel"><?= e($tabs[$active]['label']) ?> <span class="text-danger">*</span></label>
      <input class="form-control" id="qtFeedbackTitle" name="title" maxlength="255" minlength="3" required value="<?= e($title) ?>" placeholder="<?= e($tabs[$active]['placeholder']) ?>">
     </div>
     <div class="mb-3"><label for="qtFeedbackContent" class="form-label fw-semibold">Mô tả chi tiết <span class="text-danger">*</span></label>
      <textarea class="form-control" id="qtFeedbackContent" name="content" rows="6" minlength="10" maxlength="2600" required placeholder="Mô tả rõ vấn đề, các bước tái hiện hoặc ý tưởng cải thiện..."><?= e($content) ?></textarea>
      <div class="form-text">Tối thiểu 10 ký tự. Không nhập mật khẩu hoặc thông tin nhạy cảm.</div>
     </div>
     <div class="mb-3" id="qtFeedbackUpload" <?= $active==='feedback'?'':'hidden' ?>>
      <label for="qtFeedbackImage" class="form-label fw-semibold">Góp ý màn hình/prototype (nếu có)</label>
      <input type="file" class="form-control" id="qtFeedbackImage" name="prototype_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" <?= $active==='feedback'?'':'disabled' ?>>
      <div class="form-text">Ảnh JPG, PNG, WebP; tối đa 3 MB. Không bắt buộc.</div>
      <img id="qtFeedbackPreview" alt="Xem trước ảnh góp ý" class="qt-feedback-preview" hidden>
     </div>
     <button type="submit" class="btn qt-feedback-submit"><i class="bi bi-send me-2"></i> Gửi yêu cầu</button>
    </form>
   </div>
  </div>
  <div class="col-lg-5">
   <div class="qt-feedback-panel">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3"><h2 class="h5 fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Lịch sử yêu cầu</h2><span class="badge rounded-pill text-bg-light"><?= count($history) ?> yêu cầu</span></div>
    <?php if (!$history): ?>
     <div class="qt-feedback-empty"><i class="bi bi-inbox"></i><p>Bạn chưa gửi yêu cầu nào.</p></div>
    <?php else: ?>
     <div class="qt-feedback-history">
      <?php foreach($history as $item):
       $kind=$tabs[$item['type'] ?? '']['name'] ?? 'Góp ý';
       $status=$item['status'] ?? 'pending';
       $statusText=['pending'=>'Chờ xử lý','approved'=>'Đã duyệt','rejected'=>'Từ chối'][$status] ?? $status;
      ?>
       <article class="qt-feedback-history-item">
        <div class="d-flex justify-content-between gap-2 align-items-start"><span class="qt-feedback-kind"><?= e($kind) ?></span><span class="qt-feedback-status qt-status-<?= e($status) ?>"><?= e($statusText) ?></span></div>
        <h3><?= e($item['title'] ?? '') ?></h3>
        <p><?= nl2br(e($item['content'] ?? '')) ?></p>
        <?php if (!empty($item['admin_note'])): ?><div class="qt-feedback-reply"><strong>Phản hồi từ Admin:</strong> <?= e($item['admin_note']) ?></div><?php endif ?>
        <small><i class="bi bi-calendar3 me-1"></i><?= e(date('d/m/Y H:i',strtotime($item['created_at'] ?? 'now'))) ?></small>
       </article>
      <?php endforeach ?>
     </div>
    <?php endif ?>
   </div>
  </div>
 </div>
</section>
<script>
(()=>{const tabs=document.querySelectorAll('[data-feedback-tab]'),field=document.getElementById('qtFeedbackType'),label=document.getElementById('qtFeedbackTitleLabel'),title=document.getElementById('qtFeedbackTitle'),upload=document.getElementById('qtFeedbackUpload'),file=document.getElementById('qtFeedbackImage'),preview=document.getElementById('qtFeedbackPreview');
const config={
question_report:['Tiêu đề báo lỗi','VD: Câu 5 đề kiểm tra PHP bị sai đáp án'],
subject_request:['Tên môn học đề xuất','VD: Lập trình Python nâng cao'],
feedback:['Tiêu đề góp ý','VD: Cải thiện giao diện màn hình luyện tập']
};
function select(type){if(!config[type])return;field.value=type;label.innerHTML=config[type][0]+' <span class="text-danger">*</span>';title.placeholder=config[type][1];upload.hidden=type!=='feedback';file.disabled=type!=='feedback';if(type!=='feedback'){file.value='';preview.hidden=true;preview.removeAttribute('src')}tabs.forEach(t=>{const on=t.dataset.feedbackTab===type;t.classList.toggle('is-active',on);t.setAttribute('aria-selected',on?'true':'false')});}
tabs.forEach(t=>t.addEventListener('click',()=>select(t.dataset.feedbackTab)));
file.addEventListener('change',()=>{preview.hidden=true;preview.removeAttribute('src');const f=file.files[0];if(!f)return;if(f.size>3*1024*1024){alert('Ảnh tối đa 3 MB');file.value='';return}if(!['image/jpeg','image/png','image/webp'].includes(f.type)){alert('Chỉ chọn JPG, PNG hoặc WebP');file.value='';return}const url=URL.createObjectURL(f);preview.src=url;preview.hidden=false;preview.onload=()=>URL.revokeObjectURL(url)});
select(field.value);
})();
</script>
<?php require_once dirname(__DIR__).'/includes/footer.php'; ?>
