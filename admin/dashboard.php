<?php
// Dashboard Admin - giao diện độc lập, không thay đổi CSDL, header hay nghiệp vụ.
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();
$page_title = 'Tổng quan quản trị - QuizTech';
$statsRaw = function_exists('getStats') ? getStats() : [];
$stats = is_array($statsRaw) ? $statsRaw : [];
$usersRaw = function_exists('getUsers') ? getUsers() : [];
$users = is_array($usersRaw) ? $usersRaw : [];
$examsRaw = function_exists('getExams') ? getExams() : [];
$exams = is_array($examsRaw) ? $examsRaw : [];
$recentUsers = array_slice($users, 0, 5);
$recentExams = array_slice($exams, 0, 5);
$roles = ['student'=>0,'teacher'=>0,'admin_test'=>0,'admin'=>0];
foreach ($users as $u) { $role=(string)($u['role'] ?? 'student'); if(isset($roles[$role])) $roles[$role]++; }
$maxRole = max(1, ...array_values($roles));
// Read-only pending feedback; errors do not prevent Dashboard rendering.
$qtFeedbackCount = 0;
$qtFeedbackRecent = [];
$qtFeedbackByType = ['subject_request'=>0, 'question_report'=>0, 'feedback'=>0];
$qtFeedbackAvailable = true;
try {
    $qtCountStmt = $pdo->query("SELECT type, COUNT(*) AS total FROM practice_feedback WHERE status='pending' GROUP BY type");
    foreach ($qtCountStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $n = (int)$row['total']; $qtFeedbackCount += $n;
        if (array_key_exists($row['type'], $qtFeedbackByType)) $qtFeedbackByType[$row['type']] += $n;
    }
    $qtRecentStmt = $pdo->query("SELECT id, type, title, created_at FROM practice_feedback WHERE status='pending' ORDER BY created_at DESC, id DESC LIMIT 5");
    $qtFeedbackRecent = $qtRecentStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $qtFeedbackAvailable = false;
    error_log('QuizTech Dashboard feedback summary: '.$e->getMessage());
}

$roleLabels=['student'=>'Học sinh / Sinh viên','teacher'=>'Giảng viên','admin_test'=>'Admin kiểm thử','admin'=>'Super Admin'];
$roleColors=['student'=>'#6366f1','teacher'=>'#06b6d4','admin_test'=>'#a855f7','admin'=>'#f59e0b'];
$esc = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$formatDate = static function ($value): string { if (empty($value)) return '—'; $t=strtotime((string)$value); return $t ? date('d/m/Y',$t) : '—'; };
$displayName = (string)($user_name ?? (function_exists('currentUser') ? (currentUser()['name'] ?? 'Quản trị viên') : 'Quản trị viên'));
include '../includes/header_admin.php';
?>
<style>
/* Scope exclusively to dashboard: no global overrides */
.qt-ad7{--violet:#6d28d9;--ink:#221642;--muted:#766b91;color:var(--ink);max-width:1600px;margin:auto;padding-bottom:32px;font-family:inherit}
.qt-ad7 *{box-sizing:border-box}.qt-ad7 a{text-decoration:none}.qt-ad7 .qt-hero{position:relative;overflow:hidden;background:linear-gradient(118deg,#4f46e5 0%,#6d28d9 52%,#7e22ce 100%);color:#fff;border-radius:24px;padding:30px 34px;display:flex;justify-content:space-between;align-items:center;gap:24px;box-shadow:0 18px 35px #5b21b629}
.qt-ad7 .qt-hero:before,.qt-ad7 .qt-hero:after{content:'';position:absolute;border:1px solid #ffffff30;border-radius:50%;width:280px;height:280px;right:-60px;top:-145px;pointer-events:none}.qt-ad7 .qt-hero:after{width:200px;height:200px;right:90px;top:80px}.qt-ad7 .qt-hero>*{position:relative;z-index:1}.qt-ad7 .qt-eyebrow{font-size:11px;font-weight:800;letter-spacing:.14em;color:#e9ddff}.qt-ad7 .qt-hero h1{font-size:clamp(23px,2.4vw,34px);font-weight:800;line-height:1.25;margin:8px 0}.qt-ad7 .qt-hero p{color:#e7dcff;margin:0;font-size:14px}.qt-ad7 .qt-hero-actions{display:flex;gap:9px;flex-wrap:wrap}.qt-ad7 .qt-hero-actions a,.qt-ad7 .qt-hero-actions button{border:1px solid #ffffff70;border-radius:12px;padding:11px 15px;background:#fff;color:#5b21b6;font-weight:750;white-space:nowrap;cursor:pointer}.qt-ad7 .qt-hero-actions button{background:#ffffff1c;color:#fff}.qt-ad7 .qt-hero-actions a:hover{background:#f2eaff}
.qt-ad7 .qt-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin:20px 0 22px}.qt-ad7 .qt-kpi{min-width:0;background:#fff;border:1px solid #ebe4f8;border-radius:20px;padding:20px;box-shadow:0 8px 22px #24135c0b;transition:transform .2s,box-shadow .2s;display:flex;justify-content:space-between;align-items:center;gap:12px}.qt-ad7 .qt-kpi:hover{transform:translateY(-3px);box-shadow:0 14px 26px #4c1d9520}.qt-ad7 .qt-kpi small{font-size:11px;font-weight:800;color:#756b8b;letter-spacing:.06em;text-transform:uppercase;display:block}.qt-ad7 .qt-kpi strong{display:block;font-size:31px;color:#20143d;line-height:1.4}.qt-ad7 .qt-kpi em{display:block;font-size:12px;color:#857b99;font-style:normal}.qt-ad7 .qt-kpi-icon{display:grid;place-items:center;width:52px;height:52px;border-radius:16px;flex-shrink:0;background:#eee8ff;color:#6d28d9;font-size:23px}.qt-ad7 .qt-kpi:nth-child(2) .qt-kpi-icon{background:#dcfce7;color:#059669}.qt-ad7 .qt-kpi:nth-child(3) .qt-kpi-icon{background:#fff1d6;color:#d97706}.qt-ad7 .qt-kpi:nth-child(4) .qt-kpi-icon{background:#dbeafe;color:#2563eb}
.qt-ad7 .qt-kpi-feedback .qt-kpi-icon{background:#fef3c7;color:#b45309}
.qt-ad7 .qt-feedback-panel{margin-bottom:22px}.qt-ad7 .qt-feedback-badge{display:inline-flex;align-items:center;justify-content:center;min-width:26px;padding:3px 8px;border-radius:100px;background:#fef3c7;color:#92400e;font-size:12px;margin-left:7px}.qt-ad7 .qt-feedback-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.qt-ad7 .qt-feedback-summary>a{min-width:0;display:flex;align-items:center;gap:10px;border-radius:14px;background:#faf7ff;border:1px solid #ede5f8;padding:14px;color:#49327a;font-size:13px}.qt-ad7 .qt-feedback-summary>a>strong{margin-left:auto;font-size:19px;color:#5b21b6}.qt-ad7 .qt-feedback-summary>a>i{font-size:20px}.qt-ad7 .qt-feedback-feed{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:14px}.qt-ad7 .qt-feedback-item{display:flex;align-items:center;gap:10px;border:1px solid #f1ebfa;border-radius:12px;padding:12px;min-width:0;color:#291743}.qt-ad7 .qt-feedback-item>span:nth-child(2){flex:1;min-width:0}.qt-ad7 .qt-feedback-item strong{font-size:13px;display:block;overflow-wrap:anywhere}.qt-ad7 .qt-feedback-item small{font-size:11px;color:#8a7a9f;display:block}.qt-ad7 .qt-feedback-dot{height:9px;width:9px;border-radius:50%;background:#f59e0b;flex-shrink:0}.qt-ad7 .qt-feedback-item:hover{background:#f8f3ff}
.qt-ad7 .qt-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:18px;margin-bottom:23px}.qt-ad7 .qt-panel{min-width:0;background:#fff;border:1px solid #e9e2f5;border-radius:20px;padding:21px;box-shadow:0 8px 24px #26154d0a}.qt-ad7 .qt-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:18px}.qt-ad7 .qt-panel-head h2{font-size:17px;font-weight:800;margin:3px 0;color:#26163f}.qt-ad7 .qt-panel-head small{color:#857b98;font-size:12px}.qt-ad7 .qt-panel-head a{font-size:12px;color:#6d28d9;font-weight:800;white-space:nowrap;background:#f5f0ff;padding:8px 10px;border-radius:10px}.qt-ad7 .qt-panel-head a:hover{background:#ebe0ff}
.qt-ad7 .qt-roles{display:flex;flex-direction:column;gap:17px}.qt-ad7 .qt-role-label{display:flex;justify-content:space-between;font-size:13px;margin-bottom:7px;gap:10px}.qt-ad7 .qt-role-label span{color:#635778}.qt-ad7 .qt-role-label strong{font-weight:800}.qt-ad7 .qt-track{height:10px;background:#f0ebf8;border-radius:100px;overflow:hidden}.qt-ad7 .qt-track>span{height:100%;display:block;border-radius:100px;min-width:0}
.qt-ad7 .qt-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.qt-ad7 .qt-action{display:flex;gap:12px;align-items:center;padding:14px;border:1px solid #ece5f6;border-radius:15px;background:linear-gradient(135deg,#fff,#fbfaff);color:#25173d;transition:transform .2s,border-color .2s}.qt-ad7 .qt-action:hover{transform:translateY(-2px);border-color:#a78bfa;color:#5b21b6}.qt-ad7 .qt-action>i:first-child{font-size:20px;width:42px;height:42px;display:grid;place-items:center;background:#eee7ff;color:#6d28d9;border-radius:13px;flex-shrink:0}.qt-ad7 .qt-action strong{display:block;font-size:13px}.qt-ad7 .qt-action small{display:block;font-size:11px;color:#817793;margin-top:3px}
.qt-ad7 .qt-list{display:flex;flex-direction:column;gap:10px}.qt-ad7 .qt-list-item{display:flex;gap:12px;align-items:center;min-width:0;padding:11px 12px;border:1px solid #f1edf7;border-radius:14px;background:#faf9fd}.qt-ad7 .qt-avatar,.qt-ad7 .qt-exam-icon{flex-shrink:0;width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#ede9fe;color:#6d28d9;font-weight:800}.qt-ad7 .qt-list-text{min-width:0;flex:1}.qt-ad7 .qt-list-text strong{display:block;font-size:13px;color:#25173d;overflow-wrap:anywhere;line-height:1.45}.qt-ad7 .qt-list-text small{display:block;color:#817692;font-size:12px;overflow-wrap:anywhere;margin-top:3px}.qt-ad7 .qt-pill{font-size:10px;line-height:1.3;border-radius:100px;padding:6px 9px;background:#efe7ff;color:#6d28d9;font-weight:750;text-align:center;max-width:125px;flex-shrink:0}.qt-ad7 .qt-date{font-size:11px;color:#817692;white-space:nowrap}.qt-ad7 .qt-empty{padding:28px 10px;text-align:center;color:#8b809f}.qt-ad7 .qt-empty i{display:block;font-size:30px;margin-bottom:8px}.qt-ad7 .qt-note{font-size:12px;color:#817592;margin:12px 0 0}
@media(max-width:1250px){.qt-ad7 .qt-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.qt-ad7 .qt-grid{grid-template-columns:1fr}}
@media(max-width:650px){.qt-ad7 .qt-feedback-summary{grid-template-columns:1fr}.qt-ad7 .qt-feedback-feed{grid-template-columns:1fr}.qt-ad7 .qt-hero{padding:23px 20px;flex-direction:column;align-items:flex-start}.qt-ad7 .qt-kpis{gap:10px}.qt-ad7 .qt-kpi{padding:14px}.qt-ad7 .qt-kpi strong{font-size:25px}.qt-ad7 .qt-kpi-icon{width:39px;height:39px;font-size:18px}.qt-ad7 .qt-panel{padding:16px}.qt-ad7 .qt-actions{grid-template-columns:1fr}.qt-ad7 .qt-list-item{flex-wrap:wrap}.qt-ad7 .qt-pill,.qt-ad7 .qt-date{margin-left:54px}.qt-ad7 .qt-hero-actions{width:100%}}
@media(prefers-reduced-motion:reduce){.qt-ad7 *{transition:none!important}}
</style>
<div class="qt-ad7">
  <section class="qt-hero" aria-labelledby="qt-ad-title">
    <div><h1 id="qt-ad-title">Chào mừng trở lại, <?= $esc($displayName) ?>!</h1><p>Quản lý nội dung, tài khoản và hoạt động hệ thống trong một không gian trực quan.</p></div>
    <div class="qt-hero-actions"><a href="approve_items.php"><i class="bi bi-check2-circle"></i> Duyệt đề xuất</a><button type="button" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> Làm mới</button></div>
  </section>
  <section class="qt-kpis" aria-label="Chỉ số tổng quan">
    <?php foreach ([['users','Người dùng',count($users),'bi-people-fill','Quản lý tài khoản','users.php'],['subjects','Môn học',0,'bi-journal-bookmark-fill','Danh mục môn học','subjects.php'],['questions','Ngân hàng câu hỏi',0,'bi-patch-question-fill','Quản lý câu hỏi','questions.php'],['exams','Đề thi',count($exams),'bi-file-earmark-text-fill','Quản lý đề thi','exams.php']] as $k): ?>
      <a class="qt-kpi" href="<?= $esc($k[5]) ?>"><div><small><?= $esc($k[1]) ?></small><strong><?= number_format((int)($stats[$k[0]] ?? $k[2])) ?></strong><em><?= $esc($k[4]) ?> <i class="bi bi-arrow-up-right"></i></em></div><span class="qt-kpi-icon"><i class="bi <?= $esc($k[3]) ?>"></i></span></a>
    <?php endforeach; ?>
    <a class="qt-kpi qt-kpi-feedback" href="approve_items.php?status=pending" aria-label="Xem các đề xuất đang chờ duyệt"><div><small>Đề xuất chờ duyệt</small><strong><?= $qtFeedbackAvailable ? number_format($qtFeedbackCount) : '—' ?></strong><em><?= $qtFeedbackAvailable ? 'Xem và xử lý yêu cầu' : 'Chưa tải được dữ liệu' ?> <i class="bi bi-arrow-up-right"></i></em></div><span class="qt-kpi-icon"><i class="bi bi-inbox-fill"></i></span></a>
  </section>
  <section class="qt-panel qt-feedback-panel" aria-label="Thông báo phản hồi mới">
    <div class="qt-panel-head"><div><small>TRUNG TÂM TIẾP NHẬN</small><h2><i class="bi bi-bell-fill" aria-hidden="true"></i> Đề xuất, báo lỗi và góp ý <span class="qt-feedback-badge"><?= $qtFeedbackAvailable ? $qtFeedbackCount : '!' ?></span></h2></div><a href="approve_items.php?status=pending">Duyệt tất cả <i class="bi bi-arrow-up-right"></i></a></div>
    <div class="qt-feedback-summary">
      <?php foreach ([['subject_request','bi-journal-plus','Đề xuất môn học'],['question_report','bi-bug','Báo lỗi câu hỏi'],['feedback','bi-chat-left-dots','Góp ý']] as $kind): ?>
      <a href="approve_items.php?status=pending&amp;type=<?= $esc($kind[0]) ?>"><i class="bi <?= $esc($kind[1]) ?>"></i><span><?= $esc($kind[2]) ?></span><strong><?= $qtFeedbackAvailable ? number_format($qtFeedbackByType[$kind[0]]) : '—' ?></strong></a>
      <?php endforeach; ?>
    </div>
    <?php if (!$qtFeedbackAvailable): ?><p class="qt-note">Không thể tải dữ liệu đề xuất. Vui lòng kiểm tra bảng practice_feedback.</p>
    <?php elseif (!$qtFeedbackRecent): ?><p class="qt-note">Không có yêu cầu nào đang chờ xử lý.</p>
    <?php else: ?><div class="qt-feedback-feed">
      <?php foreach ($qtFeedbackRecent as $item): ?>
      <a href="approve_items.php?feedback_id=<?= (int)$item['id'] ?>" class="qt-feedback-item"><span class="qt-feedback-dot"></span><span><strong><?= $esc($item['title'] ?: 'Phản hồi mới') ?></strong><small><?= $esc(['subject_request'=>'Đề xuất môn học','question_report'=>'Báo lỗi câu hỏi','feedback'=>'Góp ý'][$item['type']] ?? 'Phản hồi') ?> · <?= $esc($formatDate($item['created_at'])) ?></small></span><i class="bi bi-chevron-right"></i></a>
      <?php endforeach; ?>
    </div><?php endif; ?>
  </section>
  <div class="qt-grid">
    <section class="qt-panel"><div class="qt-panel-head"><div><small>PHÂN BỐ TÀI KHOẢN</small><h2>Vai trò người dùng</h2></div><a href="users.php">Xem tài khoản <i class="bi bi-arrow-up-right"></i></a></div>
      <div class="qt-roles"><?php foreach($roles as $role=>$count): ?><div><div class="qt-role-label"><span><?= $esc($roleLabels[$role]) ?></span><strong><?= number_format($count) ?></strong></div><div class="qt-track"><span style="width:<?= round(100*$count/$maxRole,2) ?>%;background:<?= $esc($roleColors[$role]) ?>"></span></div></div><?php endforeach; ?></div>
      <p class="qt-note">Số liệu tổng hợp từ danh sách tài khoản hiện có.</p>
    </section>
    <section class="qt-panel"><div class="qt-panel-head"><div><small>TRUY CẬP NHANH</small><h2>Thao tác thường dùng</h2></div></div>
      <div class="qt-actions"><?php foreach ([['users.php','bi-person-plus-fill','Người dùng','Quản lý tài khoản'],['subjects.php','bi-journal-plus','Môn học','Quản lý danh mục'],['exams.php','bi-file-earmark-plus','Đề thi','Quản lý đề thi'],['rooms.php','bi-door-open-fill','Phòng thi','Quản lý phòng thi']] as $action): ?><a class="qt-action" href="<?= $esc($action[0]) ?>"><i class="bi <?= $esc($action[1]) ?>"></i><span><strong><?= $esc($action[2]) ?></strong><small><?= $esc($action[3]) ?></small></span></a><?php endforeach; ?></div>
    </section>
  </div>
  <div class="qt-grid">
    <section class="qt-panel"><div class="qt-panel-head"><div><small>DỮ LIỆU GẦN ĐÂY</small><h2><i class="bi bi-people text-primary"></i> Thành viên mới</h2></div><a href="users.php">Xem tất cả <i class="bi bi-arrow-up-right"></i></a></div>
      <?php if (!$recentUsers): ?><div class="qt-empty"><i class="bi bi-person-x"></i>Chưa có thành viên.</div><?php else: ?><div class="qt-list"><?php foreach($recentUsers as $u): $name=(string)($u['name'] ?? $u['fullname'] ?? $u['username'] ?? 'Tài khoản');$role=(string)($u['role'] ?? 'student'); ?><div class="qt-list-item"><span class="qt-avatar"><?= $esc(function_exists('mb_substr') ? mb_strtoupper(mb_substr($name,0,1,'UTF-8'),'UTF-8') : strtoupper(substr($name,0,1))) ?></span><div class="qt-list-text"><strong><?= $esc($name) ?></strong><small><?= $esc($u['email'] ?? 'Chưa có email') ?></small></div><span class="qt-pill"><?= $esc($roleLabels[$role] ?? $role) ?></span></div><?php endforeach; ?></div><?php endif; ?>
    </section>
    <section class="qt-panel"><div class="qt-panel-head"><div><small>NỘI DUNG GẦN ĐÂY</small><h2><i class="bi bi-journal-text text-primary"></i> Đề thi mới tạo</h2></div><a href="exams.php">Xem tất cả <i class="bi bi-arrow-up-right"></i></a></div>
      <?php if (!$recentExams): ?><div class="qt-empty"><i class="bi bi-journal-x"></i>Chưa có đề thi.</div><?php else: ?><div class="qt-list"><?php foreach($recentExams as $exam): ?><div class="qt-list-item"><span class="qt-exam-icon"><i class="bi bi-file-earmark-text"></i></span><div class="qt-list-text"><strong><?= $esc($exam['title'] ?? $exam['name'] ?? 'Đề thi không tên') ?></strong><small><?= $esc($exam['subject_name'] ?? $exam['subject'] ?? 'Chưa phân môn') ?></small></div><span class="qt-date"><?= $esc($formatDate($exam['created_at'] ?? null)) ?></span></div><?php endforeach; ?></div><?php endif; ?>
    </section>
  </div>
  <p class="qt-note">Dữ liệu trên trang được lấy từ các hàm thống kê và danh sách hiện có. Các chức năng quản lý vẫn thực hiện tại từng trang chuyên biệt.</p>
</div>
<?php include '../includes/footer.php'; ?>
