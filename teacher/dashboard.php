<?php
// Teacher dashboard: presentation upgrade based on QuizTech source (3).
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

$identity = function_exists('currentUser') ? (currentUser() ?: []) : [];
$teacherId = (int)($identity['id'] ?? $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
if ($teacherId <= 0) { http_response_code(403); exit('Không xác định được tài khoản giảng viên.'); }

function td_query(PDO $pdo, string $sql, int $teacherId): array {
    $statement = $pdo->prepare($sql);
    $statement->execute([':teacher_id' => $teacherId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
function td_count(PDO $pdo, string $sql, int $teacherId): int {
    $rows = td_query($pdo, $sql, $teacherId);
    return (int)($rows[0]['n'] ?? 0);
}
/** @param mixed $value */
function td_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$totalSubjects = td_count($pdo, 'SELECT COUNT(*) n FROM subjects WHERE created_by = :teacher_id', $teacherId);
$totalExams = td_count($pdo, 'SELECT COUNT(*) n FROM exams WHERE teacher_id = :teacher_id', $teacherId);
$totalQuestions = td_count($pdo, 'SELECT COUNT(*) n FROM questions WHERE created_by = :teacher_id', $teacherId);
$totalRooms = td_count($pdo, 'SELECT COUNT(*) n FROM exam_rooms WHERE teacher_id = :teacher_id', $teacherId);

$recentExams = td_query($pdo, 'SELECT e.id,e.title,e.created_at,e.duration,s.name subject_name FROM exams e LEFT JOIN subjects s ON s.id=e.subject_id WHERE e.teacher_id=:teacher_id ORDER BY e.id DESC LIMIT 8', $teacherId);
$topSubjects = td_query($pdo, 'SELECT s.name, COUNT(e.id) total FROM subjects s INNER JOIN exams e ON e.subject_id=s.id WHERE e.teacher_id=:teacher_id GROUP BY s.id,s.name ORDER BY total DESC,s.name ASC LIMIT 5', $teacherId);
$topExams = td_query($pdo, "SELECT e.title,COUNT(a.id) attempts FROM exams e LEFT JOIN exam_attempts a ON a.exam_id=e.id AND a.status IN ('submitted','timeout') WHERE e.teacher_id=:teacher_id GROUP BY e.id,e.title ORDER BY attempts DESC,e.title ASC LIMIT 5", $teacherId);
$topStudents = td_query($pdo, "SELECT u.name, COUNT(a.id) attempts, ROUND(AVG(a.percentage),1) average_percent FROM exam_attempts a INNER JOIN exams e ON e.id=a.exam_id INNER JOIN users u ON u.id=a.student_id WHERE e.teacher_id=:teacher_id AND a.status IN ('submitted','timeout') GROUP BY u.id,u.name ORDER BY attempts DESC,average_percent DESC LIMIT 5", $teacherId);
$attemptSummary = td_query($pdo, "SELECT COUNT(a.id) attempts,ROUND(AVG(a.percentage),1) avg_percent,COUNT(DISTINCT a.student_id) students FROM exams e LEFT JOIN exam_attempts a ON a.exam_id=e.id AND a.status IN ('submitted','timeout') WHERE e.teacher_id=:teacher_id", $teacherId);
$summary = $attemptSummary[0] ?? [];
$monthly = td_query($pdo, "SELECT DATE_FORMAT(a.submitted_at,'%Y-%m') month_key,COUNT(*) total FROM exam_attempts a INNER JOIN exams e ON e.id=a.exam_id WHERE e.teacher_id=:teacher_id AND a.status IN ('submitted','timeout') AND a.submitted_at >= DATE_SUB(CURDATE(),INTERVAL 5 MONTH) GROUP BY DATE_FORMAT(a.submitted_at,'%Y-%m') ORDER BY month_key", $teacherId);
$chartMap = []; foreach ($monthly as $m) { $chartMap[$m['month_key']] = (int)$m['total']; }
$chartLabels = []; $chartValues = [];
for ($i=5;$i>=0;$i--) { $month = date('Y-m',strtotime('first day of -'.$i.' months')); $chartLabels[] = date('m/Y',strtotime($month.'-01')); $chartValues[] = $chartMap[$month] ?? 0; }
$page_title='Tổng quan giảng viên';
include '../includes/header_teacher.php';
?>
<main class="td-dashboard flex-grow-1">
  <section class="td-hero">
    <div><span class="td-eyebrow"><i class="bi bi-stars"></i> TRUNG TÂM GIẢNG VIÊN</span>
      <h1>Chào mừng trở lại, <?= td_h($identity['name'] ?? $_SESSION['user']['name'] ?? 'Giảng viên') ?>!</h1>
      <p>Theo dõi hoạt động giảng dạy, kết quả học tập và quản lý nội dung tại một nơi.</p>
      <div class="td-hero-actions"><a href="exams.php" class="td-button td-button-light"><i class="bi bi-file-earmark-plus"></i> Quản lý đề thi</a><a href="rooms.php" class="td-button td-button-ghost"><i class="bi bi-door-open"></i> Phòng thi</a></div>
    </div><div class="td-hero-mark" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></div>
  </section>
  <section class="td-metrics" aria-label="Thống kê tổng quan">
    <?php foreach ([['bi-journals','Môn học',$totalSubjects,'indigo'],['bi-file-earmark-text','Đề thi',$totalExams,'pink'],['bi-patch-question','Câu hỏi',$totalQuestions,'blue'],['bi-door-open','Phòng thi',$totalRooms,'orange']] as $metric): ?>
      <article class="td-metric td-<?= $metric[3] ?>"><span class="td-metric-icon"><i class="bi <?= $metric[0] ?>"></i></span><div><strong><?= number_format($metric[2]) ?></strong><span><?= td_h($metric[1]) ?></span></div></article>
    <?php endforeach; ?>
  </section>
  <section class="td-panel-grid">
    <article class="td-panel td-chart-panel"><div class="td-panel-title"><div><span class="td-section-tag">HOẠT ĐỘNG</span><h2>Lượt làm bài 6 tháng gần đây</h2></div><span class="td-pill">Dữ liệu thực tế</span></div>
      <div class="td-chart"><canvas id="tdAttemptsChart" aria-label="Biểu đồ lượt làm bài theo tháng" role="img"></canvas></div>
      <div class="td-summary-row"><div><strong><?= number_format((int)($summary['attempts'] ?? 0)) ?></strong><span>Lượt hoàn thành</span></div><div><strong><?= number_format((int)($summary['students'] ?? 0)) ?></strong><span>Sinh viên tham gia</span></div><div><strong><?= td_h($summary['avg_percent'] ?? '0') ?>%</strong><span>Điểm TB theo %</span></div></div>
    </article>
    <article class="td-panel"><div class="td-panel-title"><div><span class="td-section-tag">XẾP HẠNG</span><h2>Top sinh viên</h2></div><a href="results.php" class="td-text-link">Xem kết quả <i class="bi bi-arrow-up-right"></i></a></div>
      <?php if (!$topStudents): ?><p class="td-empty">Chưa có bài làm hoàn thành.</p><?php else: ?><div class="td-rank-list"><?php foreach($topStudents as $i=>$s): ?><div class="td-rank-item"><span class="td-rank-number"><?= $i+1 ?></span><span class="td-avatar"><?= td_h(mb_substr($s['name'] ?: '?',0,1,'UTF-8')) ?></span><span class="td-rank-name"><?= td_h($s['name']) ?><small><?= (int)$s['attempts'] ?> lượt làm bài</small></span><strong><?= td_h($s['average_percent'] ?? 0) ?>%</strong></div><?php endforeach; ?></div><?php endif; ?>
    </article>
  </section>
  <section class="td-panel-grid td-secondary">
    <article class="td-panel"><div class="td-panel-title"><div><span class="td-section-tag">NỘI DUNG</span><h2>Môn học có nhiều đề</h2></div><a href="subjects.php" class="td-text-link">Môn học <i class="bi bi-arrow-up-right"></i></a></div>
      <?php if(!$topSubjects): ?><p class="td-empty">Chưa có dữ liệu.</p><?php else: ?><div class="td-progress-list"><?php $max=max(1,(int)$topSubjects[0]['total']);foreach($topSubjects as $s): ?><div><div class="td-progress-head"><span><?= td_h($s['name']) ?></span><b><?= (int)$s['total'] ?> đề</b></div><div class="td-progress-track"><span style="width:<?= round(100*(int)$s['total']/$max) ?>%"></span></div></div><?php endforeach; ?></div><?php endif; ?>
    </article>
    <article class="td-panel"><div class="td-panel-title"><div><span class="td-section-tag">PHỔ BIẾN</span><h2>Đề thi được làm nhiều</h2></div><a href="exams.php" class="td-text-link">Đề thi <i class="bi bi-arrow-up-right"></i></a></div>
      <?php if(!$topExams): ?><p class="td-empty">Chưa có đề thi.</p><?php else: ?><div class="td-rank-list"><?php foreach($topExams as $i=>$ex): ?><div class="td-rank-item"><span class="td-rank-number"><?= $i+1 ?></span><span class="td-rank-name"><?= td_h($ex['title']) ?><small><?= (int)$ex['attempts'] ?> lượt hoàn thành</small></span><i class="bi bi-graph-up-arrow td-rank-arrow"></i></div><?php endforeach; ?></div><?php endif; ?>
    </article>
  </section>
  <section class="td-panel-grid td-secondary">
    <article class="td-panel"><div class="td-panel-title"><div><span class="td-section-tag">MỚI NHẤT</span><h2>Đề thi vừa tạo</h2></div><span class="td-pill">Tự động chuyển</span></div>
      <?php if(!$recentExams): ?><p class="td-empty">Chưa có đề thi mới.</p><?php else: ?><div class="td-exam-slider" id="tdExamSlider"><?php foreach($recentExams as $i=>$ex): ?><div class="td-exam-slide<?= $i===0?' is-active':'' ?>" <?= $i===0?'':'hidden' ?>><span class="td-exam-icon"><i class="bi bi-journal-check"></i></span><h3><?= td_h($ex['title']) ?></h3><p><?= td_h($ex['subject_name'] ?? 'Chưa phân môn') ?></p><div class="td-exam-meta"><span><i class="bi bi-clock"></i> <?= (int)$ex['duration'] ?> phút</span><span><i class="bi bi-calendar3"></i> <?= td_h(!empty($ex['created_at'])?date('d/m/Y',strtotime($ex['created_at'])):'—') ?></span></div></div><?php endforeach; ?></div><div class="td-slide-controls"><button type="button" id="tdPrev" aria-label="Đề trước"><i class="bi bi-chevron-left"></i></button><span id="tdSlideCounter">1 / <?= count($recentExams) ?></span><button type="button" id="tdNext" aria-label="Đề sau"><i class="bi bi-chevron-right"></i></button></div><?php endif; ?>
    </article>
    <article class="td-panel"><div class="td-panel-title"><div><span class="td-section-tag">TRUY CẬP NHANH</span><h2>Thao tác thường dùng</h2></div></div><div class="td-actions">
      <?php foreach([['questions.php','bi-patch-question','Ngân hàng câu hỏi'],['import.php','bi-cloud-arrow-up','Import câu hỏi'],['exams.php','bi-file-earmark-plus','Quản lý đề thi'],['rooms.php','bi-door-open','Quản lý phòng thi'],['practice/index.php','bi-controller','Luyện tập tự do'],['results.php','bi-bar-chart','Kết quả & thống kê']] as $action): ?><a href="<?= td_h($action[0]) ?>"><i class="bi <?= $action[1] ?>"></i><span><?= td_h($action[2]) ?></span><i class="bi bi-arrow-up-right"></i></a><?php endforeach; ?>
    </div></article>
  </section>
  <p class="td-note">Bảng xếp hạng và biểu đồ trên trang này chỉ thống kê các bài thi thuộc đề do giảng viên quản lý. Dữ liệu Practice có thể xem tại trang luyện tập và thống kê tương ứng.</p>
</main>
<script>
(function(){
  const canvas=document.getElementById('tdAttemptsChart');
  const labels=<?= json_encode($chartLabels,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP) ?>;
  const values=<?= json_encode($chartValues,JSON_NUMERIC_CHECK) ?>;
  function draw(){if(!canvas)return;const w=canvas.clientWidth,h=canvas.clientHeight;if(!w||!h)return;const d=Math.min(devicePixelRatio||1,2);canvas.width=w*d;canvas.height=h*d;const c=canvas.getContext('2d');c.scale(d,d);c.clearRect(0,0,w,h);const left=40,right=12,top=18,bottom=36,iw=w-left-right,ih=h-top-bottom,max=Math.max(4,...values);c.font='12px system-ui';c.textAlign='right';c.fillStyle='#7c7894';c.strokeStyle='#eee8fa';for(let k=0;k<=4;k++){let y=top+ih*k/4;c.beginPath();c.moveTo(left,y);c.lineTo(w-right,y);c.stroke();c.fillText(String(Math.round(max*(4-k)/4)),left-10,y+4)}const slot=iw/values.length,bw=Math.min(48,slot*.58);values.forEach((v,i)=>{const bh=ih*v/max,x=left+i*slot+(slot-bw)/2,y=top+ih-bh;const g=c.createLinearGradient(0,y,0,top+ih);g.addColorStop(0,'#7c3aed');g.addColorStop(1,'#c4b5fd');c.fillStyle=g;c.beginPath();c.roundRect(x,y,bw,Math.max(bh,2),[7,7,0,0]);c.fill();c.textAlign='center';c.fillStyle='#7c7894';c.fillText(labels[i],left+i*slot+slot/2,h-12)})}
  if('ResizeObserver' in window && canvas){new ResizeObserver(draw).observe(canvas)}else{window.addEventListener('resize',draw)}draw();
  const slides=Array.from(document.querySelectorAll('.td-exam-slide'));let idx=0;const counter=document.getElementById('tdSlideCounter');function show(n){if(!slides.length)return;idx=(n+slides.length)%slides.length;slides.forEach((s,i)=>{s.hidden=i!==idx;s.classList.toggle('is-active',i===idx)});if(counter)counter.textContent=(idx+1)+' / '+slides.length}document.getElementById('tdPrev')?.addEventListener('click',()=>show(idx-1));document.getElementById('tdNext')?.addEventListener('click',()=>show(idx+1));if(slides.length>1&&!matchMedia('(prefers-reduced-motion: reduce)').matches){setInterval(()=>{if(!document.hidden)show(idx+1)},6500)}
})();
</script>
<?php echo '</div></div>'; include '../includes/footer.php'; ?>
