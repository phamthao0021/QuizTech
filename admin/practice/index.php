<?php
// admin/practice/index.php
$root=dirname(__DIR__,2);
require_once $root.'/includes/config.php';
require_once $root.'/includes/functions.php';
require_once $root.'/includes/auth.php';
require_once $root.'/includes/practice_admin_service.php';
if(session_status()===PHP_SESSION_NONE)session_start();
if(!isLoggedIn()){setFlash('warning','Vui lòng đăng nhập.');redirect('../../login.php');}
if(!practice_admin_only()){setFlash('danger','Chỉ Admin được quản trị Practice.');redirect('../dashboard.php');}

$stats=practice_stats($pdo);$recent=practice_recent($pdo,8);
$page_title='Quản trị Practice';
require_once $root.'/includes/header_admin.php';
?>
<style>
.pa{--p:#5b4ce6;--ink:#172033}.pa-hero{background:linear-gradient(135deg,#4338ca,#6d5dfc);color:#fff;border-radius:22px;padding:24px}.pa-card{background:#fff;border:1px solid #e8eaf1;border-radius:18px;box-shadow:0 6px 20px rgba(17,24,39,.04)}.pa-stat{padding:17px;height:100%}.pa-stat small{color:#6b7280;font-weight:600}.pa-stat strong{display:block;font-size:1.55rem;color:var(--ink)}.pa-game{padding:20px;height:100%;transition:.2s}.pa-game:hover{transform:translateY(-3px)}.pa-icon{width:46px;height:46px;display:grid;place-items:center;border-radius:14px;background:#eeecff;color:var(--p);font-size:1.25rem}.pa .table>:not(caption)>*>*{padding:.9rem 1rem}.pa-mobile{display:none}@media(max-width:767.98px){.pa-hero{border-radius:16px;padding:18px}.pa-desktop{display:none}.pa-mobile{display:block}.pa-stat{padding:13px}.pa-stat strong{font-size:1.25rem}}
</style>
<?php require __DIR__ . '/_practice_ui.php'; ?>
<div class="container-fluid py-4 pa">
 <div class="pa-hero mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
  <div><small class="text-white-50 fw-bold">QUIZTECH ADMIN</small><h2 class="fw-bold mb-1"><i class="bi bi-controller me-2"></i>Quản trị Practice</h2><p class="mb-0 text-white-50">Trung tâm riêng cho nội dung và kết quả luyện tập.</p></div>
  <div class="d-flex gap-2"><a href="../dashboard.php" class="btn btn-light btn-sm">Dashboard Admin</a><a class="btn btn-warning btn-sm" href="concepts.php"><i class="bi bi-database-gear me-1"></i>Quản lý dữ liệu</a></div>
 </div>
 <?php if(function_exists('getFlash')&&($f=getFlash())):?><div class="alert alert-<?=e($f['type'])?> alert-dismissible fade show"><?=e($f['message'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
 <div class="row g-3 mb-4">
 <?php foreach([['Crossword',$stats['crosswords']??0],['Concept',$stats['concepts']??0],['Quick Quiz',$stats['quizzes']??0],['Lượt luyện',$stats['sessions']??0],['Sinh viên',$stats['students']??0],['Điểm TB',number_format((float)($stats['avg_score']??0),1)]] as $x):?>
  <div class="col-6 col-md-4 col-xl-2"><div class="pa-card pa-stat"><small><?=e($x[0])?></small><strong><?=e($x[1])?></strong></div></div>
 <?php endforeach;?>
 </div>
 <div class="row g-3 mb-4">
  <div class="col-md-4"><div class="pa-card pa-game"><div class="pa-icon mb-3"><i class="bi bi-grid-3x3-gap-fill"></i></div><h5 class="fw-bold">Daily Crossword</h5><p class="text-muted small">Bộ ô chữ, clue, đáp án và trạng thái.</p><a class="btn btn-outline-primary btn-sm" href="crosswords.php">Quản lý</a></div></div>
  <div class="col-md-4"><div class="pa-card pa-game"><div class="pa-icon mb-3"><i class="bi bi-diagram-2-fill"></i></div><h5 class="fw-bold">Nối cặp</h5><p class="text-muted small">Thuật ngữ, định nghĩa và chủ đề.</p><a class="btn btn-outline-primary btn-sm" href="concepts.php">Quản lý</a></div></div>
  <div class="col-md-4"><div class="pa-card pa-game"><div class="pa-icon mb-3"><i class="bi bi-lightning-charge-fill"></i></div><h5 class="fw-bold">Phản xạ nhanh</h5><p class="text-muted small">Câu hỏi, đáp án, thời gian và điểm.</p><a class="btn btn-outline-primary btn-sm" href="quick_quiz.php">Quản lý</a></div></div>
 </div>
 <div class="pa-card overflow-hidden">
  <div class="p-3 border-bottom d-flex justify-content-between"><div><h5 class="fw-bold mb-0">Hoạt động gần đây</h5><small class="text-muted">8 lượt mới nhất</small></div><a href="results.php" class="btn btn-light btn-sm">Kết quả</a></div>
  <div class="table-responsive pa-desktop"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Sinh viên</th><th>Game</th><th>Điểm</th><th>Accuracy</th><th>Thời gian</th><th>Ngày</th></tr></thead><tbody>
  <?php if(!$recent):?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có dữ liệu.</td></tr><?php endif;?>
  <?php foreach($recent as $r):?><tr><td><b><?=e($r['fullname']??($r['username']??'Sinh viên'))?></b><br><small><?=e($r['email']??'')?></small></td><td><?=e($r['game_type'])?></td><td><b><?=e($r['score'])?></b></td><td><?=($r['total_count']??0)>0?number_format(($r['correct_count']*100)/$r['total_count'],1):'0.0'?>%</td><td><?=(int)$r['duration_seconds']?>s</td><td><?=e($r['completed_at']??'')?></td></tr><?php endforeach;?>
  </tbody></table></div>
  <div class="pa-mobile p-3"><?php foreach($recent as $r):?><div class="border rounded-3 p-3 mb-2"><div class="d-flex justify-content-between"><b><?=e($r['fullname']??($r['username']??'Sinh viên'))?></b><span><?=e($r['game_type'])?></span></div><small class="text-muted">Điểm <?=e($r['score'])?> · <?=($r['total_count']??0)>0?number_format(($r['correct_count']*100)/$r['total_count'],1):'0.0'?>% · <?=(int)$r['duration_seconds']?>s</small></div><?php endforeach;?></div>
 </div>
</div>
<?php require_once $root.'/includes/footer.php';?>
