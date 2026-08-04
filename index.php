<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// index.php - Trang chủ QuizTech
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';

$page_title = 'Trang chủ';

$subjects = getSubjects() ?? [];
$exams    = getExams()    ?? [];
$stats    = getStats()    ?? [];

$stats_subjects  = (int)($stats['subjects']  ?? count($subjects));
$stats_exams     = (int)($stats['exams']     ?? count($exams));
$stats_questions = (int)($stats['questions'] ?? 0);
$stats_results   = (int)($stats['results']   ?? 0);

include 'includes/header_guest.php';
?>

<style>
  :root {
    --primary      : #6366f1;
    --primary-dark : #4f46e5;
    --accent       : #8b5cf6;
    --accent-glow  : #a855f7;
    --bg           : #f8fafc;
    --text         : #0f172a;
  }

  body { background: var(--bg); color: var(--text); overflow-x: hidden; }

  /* ── ANIMATIONS ── */
  @keyframes floatBlob {
    0%,100% { transform: translate(0,0) scale(1); }
    33%     { transform: translate(20px,-30px) scale(1.08); }
    66%     { transform: translate(-15px,15px) scale(.95); }
  }
  @keyframes pulseGlow {
    0%,100% { box-shadow: 0 0 12px rgba(139,92,246,.25); }
    50%     { box-shadow: 0 0 25px rgba(168,85,247,.6); }
  }
  @keyframes borderGradient {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }
  @keyframes blinkCursor { 50% { border-color: transparent; } }

  /* ── HERO ── */
  .hero {
    position: relative;
    padding: 60px 0 100px;
    background:
      radial-gradient(circle at 10% 20%, rgba(139,92,246,.08) 0%, transparent 40%),
      radial-gradient(circle at 90% 80%, rgba(99,102,241,.08) 0%, transparent 40%),
      linear-gradient(180deg, #fff 0%, #f8fafc 100%);
    overflow: hidden;
  }
  .hero-blob {
    position: absolute;
    width: 280px; height: 280px;
    filter: blur(70px);
    border-radius: 50%;
    z-index: 0;
    pointer-events: none;
    opacity: .5;
    animation: floatBlob 10s infinite ease-in-out;
  }
  .hero-blob-1 { top:-40px; right:5%;
    background: linear-gradient(135deg,#a855f7,#6366f1); }
  .hero-blob-2 { bottom:-60px; left:2%;
    background: linear-gradient(135deg,#ec4899,#8b5cf6);
    animation-delay:-5s; }
  .hero-container { position: relative; z-index: 2; }

  .hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 16px; border-radius: 50px;
    background: rgba(139,92,246,.1); color: var(--accent);
    font-weight: 700; font-size: .85rem;
    border: 1px solid rgba(139,92,246,.25);
    backdrop-filter: blur(8px);
    animation: pulseGlow 3s infinite;
  }
  .hero h1 {
    font-size: clamp(2rem,5vw,3.25rem);
    font-weight: 800; line-height: 1.2;
    margin-top: 18px; letter-spacing: -.5px;
  }
  .text-gradient {
    background: linear-gradient(135deg,#8b5cf6 0%,#6366f1 50%,#ec4899 100%);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .typing-text {
    border-right: 3px solid var(--accent);
    white-space: pre-wrap; word-break: break-word;
    animation: blinkCursor .75s step-end infinite;
  }

  .hero-card-wrapper {
    position: relative; padding: 3px; border-radius: 24px;
    background: linear-gradient(135deg,rgba(139,92,246,.6),rgba(99,102,241,.2),rgba(236,72,153,.6));
    background-size: 200% 200%;
    animation: borderGradient 6s ease infinite;
    box-shadow: 0 15px 35px rgba(139,92,246,.12);
  }
  .hero-card {
    background: rgba(255,255,255,.94);
    backdrop-filter: blur(16px);
    border-radius: 21px; padding: 24px;
  }
  .hero-icon {
    width: 48px; height: 48px; flex-shrink: 0;
    border-radius: 14px; background: rgba(139,92,246,.12);
    color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; margin-right: 15px;
    transition: all .3s ease;
  }
  .hero-item:hover .hero-icon {
    transform: rotate(8deg) scale(1.08);
    background: linear-gradient(135deg,var(--accent),var(--primary));
    color: #fff;
  }

  /* ── STATS ── */
  .stats { margin-top: -30px; position: relative; z-index: 10; }
  .stat-box {
    background: #fff; border-radius: 20px; padding: 20px 15px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,.04);
    border: 1px solid rgba(229,231,235,.8);
    transition: all .3s ease;
  }
  .stat-box:hover {
    transform: translateY(-5px);
    border-color: rgba(139,92,246,.4);
    box-shadow: 0 15px 30px rgba(139,92,246,.12);
  }
  .stat-number {
    font-size: clamp(2rem,4vw,2.75rem);
    font-weight: 800; line-height: 1;
    background: linear-gradient(135deg,#8b5cf6,#4f46e5);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* ── SHARED ── */
  .btn-purple {
    background: linear-gradient(135deg,#8b5cf6,#6366f1);
    color: #fff !important; border: none;
    box-shadow: 0 6px 18px rgba(139,92,246,.3);
    transition: all .3s ease;
  }
  .btn-purple:hover {
    background: linear-gradient(135deg,#7c3aed,#4f46e5);
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(139,92,246,.45);
  }
  .card-hover {
    transition: all .3s ease; border-radius: 18px;
  }
  .card-hover:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 30px rgba(139,92,246,.15) !important;
    border-color: rgba(139,92,246,.3) !important;
  }
  .badge-soft {
    background: rgba(139,92,246,.12);
    color: var(--accent); font-weight: 700;
  }
  .text-purple { color: var(--accent) !important; }

  /* ── SCROLL REVEAL ── */
  .reveal {
    opacity: 0; transform: translateY(25px);
    transition: all .6s cubic-bezier(.2,.8,.2,1);
  }
  .reveal.visible { opacity: 1; transform: translateY(0); }

  @media (max-width: 575.98px) {
    .hero { padding: 40px 0 80px; }
    .hero-card { padding: 20px 15px; }
    .stats { margin-top: -20px; }
  }
</style>

<!-- ═══════════════════════════════════════
     HERO
═══════════════════════════════════════ -->
<section class="hero">
  <div class="hero-blob hero-blob-1"></div>
  <div class="hero-blob hero-blob-2"></div>

  <div class="container hero-container">
    <div class="row align-items-center g-4 g-lg-5">

      <!-- Left -->
      <div class="col-lg-6 text-center text-lg-start">
        <span class="hero-badge mb-2">
          <i class="bi bi-stars"></i> Nền tảng luyện thi CNTT Thế Hệ Mới
        </span>

        <h1>
          Học lập trình bằng cách<br>
          <span class="text-gradient">
            <span id="typing-target" class="typing-text">làm đề thi thực tế</span>
          </span>
        </h1>

        <p class="lead text-muted mt-3 fs-6">
          <strong>QuizTech</strong> giúp sinh viên bứt phá kỹ năng lập trình, CSDL và mạng máy tính
          với hệ thống chấm điểm tự động, phòng thi trực tuyến realtime và bảng xếp hạng thông minh.
        </p>

        <div class="mt-4 d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-3">
          <a href="exams.php" class="btn btn-purple btn-lg px-4 py-3 fw-bold rounded-3">
            <i class="bi bi-play-circle-fill me-1"></i> Làm bài ngay
          </a>
          <a href="rooms.php" class="btn btn-outline-dark btn-lg px-4 py-3 fw-bold rounded-3">
            <i class="bi bi-people-fill me-1 text-purple"></i> Phòng thi Online
          </a>
        </div>
      </div>

      <!-- Right: Card Glassmorphism -->
      <div class="col-lg-6">
        <div class="hero-card-wrapper">
          <div class="hero-card">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
              <i class="bi bi-rocket-takeoff-fill text-purple fs-4"></i>
              Trải nghiệm vượt trội tại QuizTech
            </h5>

            <?php
            $features = [
              ['bi-lightning-charge-fill', 'Chấm điểm tức thì',        'Hiển thị kết quả & đáp án chi tiết ngay sau khi nộp.'],
              ['bi-cpu-fill',              'Ngân hàng câu hỏi chuẩn CNTT','Cập nhật liên tục theo giáo trình và đề thi doanh nghiệp.'],
              ['bi-trophy-fill',           'Bảng xếp hạng realtime',    'Thi đua xếp hạng điểm số và thời gian làm bài.'],
              ['bi-controller',            'Đấu trường Multiplayer',    'Tạo phòng riêng, mời bạn bè cùng vào thách đấu.'],
            ];
            foreach ($features as $i => [$icon, $title, $desc]):
            ?>
            <div class="hero-item d-flex align-items-center <?= $i < count($features)-1 ? 'mb-3' : '' ?> text-start">
              <div class="hero-icon"><i class="bi <?= $icon ?>"></i></div>
              <div>
                <strong class="d-block text-dark"><?= $title ?></strong>
                <small class="text-muted"><?= $desc ?></small>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     STATS
═══════════════════════════════════════ -->
<section class="stats">
  <div class="container">
    <div class="row g-3 g-md-4">
      <?php
      $stat_items = [
        [$stats_subjects,  'Môn học CNTT'],
        [$stats_exams,     'Đề thi chất lượng'],
        [$stats_questions, 'Câu hỏi trắc nghiệm'],
        [$stats_results,   'Lượt thi thành công'],
      ];
      foreach ($stat_items as [$val, $label]):
      ?>
      <div class="col-6 col-md-3 reveal">
        <div class="stat-box">
          <div class="stat-number counter" data-target="<?= $val ?>">0</div>
          <div class="fw-semibold text-muted small mt-1"><?= $label ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     WHY QUIZTECH
═══════════════════════════════════════ -->
<section class="py-5 bg-white my-4">
  <div class="container">
    <div class="text-center mb-4 mb-md-5 reveal">
      <span class="badge badge-soft px-3 py-2 fs-6 mb-2">
        <i class="bi bi-shield-check me-1"></i> Ưu thế QuizTech
      </span>
      <h2 class="fw-bold">Thiết kế tối ưu cho sinh viên Công Nghệ</h2>
      <p class="text-muted col-lg-8 mx-auto mb-0">
        Được tối ưu để việc rèn luyện trắc nghiệm Code, kiến thức lý thuyết & thực hành trở nên hiệu quả nhất.
      </p>
    </div>

    <div class="row g-4">
      <?php
      $why = [
        ['bi-lightning-charge-fill', 'Chấm điểm tự động',     'Nhận kết quả ngay lập tức cùng đáp án tham khảo chi tiết sau khi bấm nộp bài.'],
        ['bi-journal-code',          'Đề thi đa dạng',         'Chủ đề trải dài từ C/C++, Java, PHP, Web, Database cho tới Mạng máy tính.'],
        ['bi-bar-chart-line-fill',   'Bảng xếp hạng',          'Theo dõi sự tiến bộ, vinh danh sinh viên có điểm số xuất sắc mỗi tuần.'],
        ['bi-people-fill',           'Phòng thi Multiplayer',  'Tạo phòng thi riêng biệt, thi đấu thời gian thực trực tiếp cùng bạn bè.'],
      ];
      foreach ($why as [$icon, $title, $desc]):
      ?>
      <div class="col-sm-6 col-lg-3 reveal">
        <div class="card h-100 border-0 shadow-sm card-hover">
          <div class="card-body text-center p-4">
            <div class="fs-1 text-purple mb-3"><i class="bi <?= $icon ?>"></i></div>
            <h5 class="fw-bold"><?= $title ?></h5>
            <p class="text-muted small mb-0"><?= $desc ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     MÔN HỌC NỔI BẬT
═══════════════════════════════════════ -->
<section class="py-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 reveal">
      <div>
        <span class="badge badge-soft px-3 py-2 fs-6">Danh mục Môn</span>
        <h2 class="fw-bold mt-2 mb-0">Môn học nổi bật</h2>
      </div>
      <a href="subjects.php" class="btn btn-outline-primary rounded-pill px-4 btn-sm fs-6">
        Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-3 g-md-4">
      <?php if (!empty($subjects)): ?>
        <?php foreach (array_slice($subjects, 0, 8) as $subject): ?>
        <div class="col-sm-6 col-md-4 col-lg-3 reveal">
          <a href="exams.php?subject=<?= (int)$subject['id'] ?>" class="text-decoration-none text-dark">
            <div class="card border-0 shadow-sm h-100 card-hover">
              <div class="card-body text-center p-4">
                <div class="mb-3 text-purple fs-2"><i class="bi bi-code-slash"></i></div>
                <h5 class="fw-bold mb-2 fs-6"><?= e($subject['name'] ?? '') ?></h5>
                <p class="text-muted small mb-0">
                  <?= e($subject['description'] ?? 'Ngân hàng câu hỏi trắc nghiệm rèn luyện.') ?>
                </p>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted py-4">Đang cập nhật môn học...</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     ĐỀ THI PHỔ BIẾN
═══════════════════════════════════════ -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 reveal">
      <div>
        <span class="badge bg-success-subtle text-success px-3 py-2 fs-6">Mới cập nhật</span>
        <h2 class="fw-bold mt-2 mb-0">Đề thi phổ biến</h2>
      </div>
      <a href="exams.php" class="btn btn-outline-success rounded-pill px-4 btn-sm fs-6">
        Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php if (!empty($exams)): ?>
        <?php foreach (array_slice($exams, 0, 6) as $exam): ?>
        <div class="col-md-6 col-lg-4 reveal">
          <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex flex-column p-4">
              <div class="mb-3">
                <span class="badge badge-soft px-3 py-2">
                  <?= e($exam['subject_name'] ?? '') ?>
                </span>
              </div>
              <h5 class="fw-bold text-dark fs-6"><?= e($exam['title'] ?? '') ?></h5>
              <p class="text-muted small mb-3 flex-grow-1">
                <?= e($exam['description'] ?? 'Bài thi trắc nghiệm tổng hợp kiến thức.') ?>
              </p>
              <div class="pt-3 border-top d-flex justify-content-between text-muted small mb-3">
                <span><i class="bi bi-list-check me-1 text-purple"></i><?= (int)($exam['question_count'] ?? 0) ?> câu</span>
                <span><i class="bi bi-clock me-1 text-purple"></i><?= (int)($exam['time_limit'] ?? 20) ?> phút</span>
              </div>
              <a href="exam.php?id=<?= (int)$exam['id'] ?>" class="btn btn-purple w-100 fw-bold">
                <i class="bi bi-play-fill me-1"></i> Bắt đầu làm bài
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted py-4">Đang cập nhật đề thi...</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     CALL TO ACTION
═══════════════════════════════════════ -->
<section class="py-4 py-md-5">
  <div class="container reveal">
    <div class="rounded-4 p-4 p-md-5 text-center text-white position-relative overflow-hidden"
         style="background:linear-gradient(135deg,#4f46e5,#8b5cf6,#d946ef);
                box-shadow:0 15px 35px rgba(139,92,246,.3);">
      <h2 class="fw-bold mb-3">Sẵn sàng thử thách kiến thức?</h2>
      <p class="lead opacity-90 mb-4 mx-auto fs-6" style="max-width:650px">
        Hàng nghìn sinh viên CNTT đang ôn luyện mỗi ngày.
        Hãy thử sức ngay bây giờ để kiểm tra trình độ của bạn!
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="exams.php" class="btn btn-light btn-lg px-4 fw-bold text-dark shadow-sm">
          <i class="bi bi-play-circle-fill text-purple me-1"></i> Bắt đầu làm đề
        </a>
        <a href="rooms.php" class="btn btn-outline-light btn-lg px-4 fw-bold">
          <i class="bi bi-people-fill me-1"></i> Phòng thi Online
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     FOOTER
═══════════════════════════════════════ -->
<footer class="bg-dark text-light pt-5 pb-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 text-center text-lg-start">
        <h4 class="fw-bold text-gradient mb-3">QuizTech</h4>
        <p class="text-light opacity-75 small">
          Nền tảng thi trắc nghiệm trực tuyến thông minh, hỗ trợ sinh viên ngành Công nghệ Thông tin
          rèn luyện kỹ năng lập trình & lý thuyết toàn diện.
        </p>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="fw-bold mb-3 text-purple">Hệ thống</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="index.php"    class="text-light text-decoration-none opacity-75">Trang chủ</a></li>
          <li class="mb-2"><a href="subjects.php" class="text-light text-decoration-none opacity-75">Môn học</a></li>
          <li class="mb-2"><a href="exams.php"    class="text-light text-decoration-none opacity-75">Đề thi</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-3">
        <h6 class="fw-bold mb-3 text-purple">Tính năng</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="leaderboard.php" class="text-light text-decoration-none opacity-75">Bảng xếp hạng</a></li>
          <li class="mb-2"><a href="rooms.php"       class="text-light text-decoration-none opacity-75">Phòng thi Realtime</a></li>
          <li class="mb-2"><a href="login.php"       class="text-light text-decoration-none opacity-75">Đăng nhập</a></li>
        </ul>
      </div>
      <div class="col-lg-3 text-center text-lg-start">
        <h6 class="fw-bold mb-3 text-purple">Thống kê hệ thống</h6>
        <div class="small opacity-75 d-flex flex-column gap-2">
          <div><i class="bi bi-journal-text me-2 text-purple"></i><?= $stats_subjects ?> môn học</div>
          <div><i class="bi bi-file-earmark-code me-2 text-purple"></i><?= $stats_exams ?> đề thi</div>
          <div><i class="bi bi-question-circle me-2 text-purple"></i><?= $stats_questions ?> câu hỏi</div>
        </div>
      </div>
    </div>

    <hr class="border-secondary my-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small opacity-75">
      <div>© <?= date('Y') ?> QuizTech. All rights reserved.</div>
      <div>Phát triển bằng PHP • Bootstrap 5 • MySQL</div>
    </div>
  </div>
</footer>

<?php include 'includes/footer_guest.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // 1. Typing effect
  const phrases = ['làm đề thi thực tế','thách đấu cùng bạn bè','chấm điểm tự động','chinh phục đỉnh cao'];
  let pIdx = 0, cIdx = 0, deleting = false;
  const el = document.getElementById('typing-target');

  function type() {
    if (!el) return;
    const phrase = phrases[pIdx];
    el.textContent = deleting
      ? phrase.substring(0, --cIdx)
      : phrase.substring(0, ++cIdx);

    let speed = deleting ? 40 : 80;
    if (!deleting && cIdx === phrase.length)  { speed = 2000; deleting = true; }
    else if (deleting && cIdx === 0)          { deleting = false; pIdx = (pIdx+1) % phrases.length; speed = 400; }
    setTimeout(type, speed);
  }
  type();

  // 2. Scroll reveal
  const ro = new IntersectionObserver(
    entries => entries.forEach(e => e.isIntersecting && e.target.classList.add('visible')),
    { threshold: 0.1 }
  );
  document.querySelectorAll('.reveal').forEach(el => ro.observe(el));

  // 3. Counter animation
  const counters = document.querySelectorAll('.counter');
  if (!counters.length) return;

  let fired = false;
  const co = new IntersectionObserver(entries => {
    if (!entries[0].isIntersecting || fired) return;
    fired = true;
    counters.forEach(c => {
      const target = +c.dataset.target || 0;
      if (!target) { c.textContent = '0'; return; }
      const inc = target / (1200 / 16);
      let cur = 0;
      const tick = () => {
        cur += inc;
        if (cur < target) { c.textContent = Math.ceil(cur); requestAnimationFrame(tick); }
        else               { c.textContent = target; }
      };
      tick();
    });
  }, { threshold: 0.3 });
  co.observe(counters[0]);

});
</script>