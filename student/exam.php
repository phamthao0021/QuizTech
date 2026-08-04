<?php
// student/exam.php - Giao diện làm bài thi
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$exam_id = (int)($_GET['id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['id'] ?? 0);

// 1. Lấy thông tin đề thi
$stmt = $pdo->prepare("
    SELECT e.*, s.name AS subject_name 
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id 
    WHERE e.id = :id
");
$stmt->execute(['id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Đề thi không tồn tại hoặc đã bị xóa.");
}

$subject_id = (int)($exam['subject_id'] ?? 0);

// 2. Lấy danh sách câu hỏi theo subject_id
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = :subject_id ORDER BY RAND()");
$stmtQ->execute(['subject_id' => $subject_id]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    die("Môn học này hiện chưa có câu hỏi nào trong hệ thống.");
}

// Xáo trộn đáp án
foreach ($questions as &$q) {
    $options = [
        'A' => $q['option_a'] ?? $q['option_A'] ?? $q['a'] ?? '',
        'B' => $q['option_b'] ?? $q['option_B'] ?? $q['b'] ?? '',
        'C' => $q['option_c'] ?? $q['option_C'] ?? $q['c'] ?? '',
        'D' => $q['option_d'] ?? $q['option_D'] ?? $q['d'] ?? ''
    ];
    $keys = array_keys($options);
    shuffle($keys);
    
    $shuffled = [];
    foreach ($keys as $k) {
        $shuffled[$k] = $options[$k];
    }
    $q['shuffled_options'] = $shuffled;
}
unset($q);

$duration_minutes = (int)($exam['duration'] ?? $exam['time_limit'] ?? 20);
$duration_seconds = $duration_minutes * 60;
$page_title = 'Thi: ' . ($exam['title'] ?? 'Bài thi');

$headerFile = dirname(__DIR__) . '/includes/header_user.php';
if (!file_exists($headerFile)) {
    $headerFile = dirname(__DIR__) . '/includes/header.php';
}
if (file_exists($headerFile)) {
    include $headerFile;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
  
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrownight.min.css">
  
  <style>
    :root {
      --primary-purple: #6366f1;
      --accent-purple: #8b5cf6;
      --bg-light: #f8fafc;
    }
    body { background-color: var(--bg-light); user-select: none; -webkit-user-select: none; }
    .exam-header-sticky { position: sticky; top: 0; z-index: 1020; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 2px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .timer-box { background: linear-gradient(135deg, #1e293b, #0f172a); color: #38bdf8; font-family: 'Courier New', Courier, monospace; font-size: 1.35rem; font-weight: 700; padding: 6px 16px; border-radius: 8px; letter-spacing: 2px; display: inline-flex; align-items: center; gap: 8px; }
    .timer-box.warning { color: #f87171; animation: pulseAlert 1s infinite; }
    @keyframes pulseAlert { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.85; transform: scale(1.03); } }
    .question-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .question-card.answered { border-left: 5px solid var(--accent-purple); }
    .option-label { display: flex; align-items: center; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; background: #fff; width: 100%; }
    .option-label:hover { background-color: rgba(139, 92, 246, 0.05); border-color: var(--accent-purple); }
    .form-check-input:checked + .option-label { background-color: rgba(99, 102, 241, 0.1); border-color: var(--primary-purple); font-weight: 600; color: var(--primary-purple); }
    .question-nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 8px; }
    .btn-nav-q { width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; }
    .btn-nav-q.done { background: var(--accent-purple); color: #fff; border-color: var(--accent-purple); }
  </style>
</head>
<body>

<div class="exam-header-sticky py-2 mb-4">
  <div class="container d-flex justify-content-between align-items-center">
    <div>
      <span class="badge bg-primary mb-1"><?= htmlspecialchars($exam['subject_name'] ?? 'Môn thi', ENT_QUOTES, 'UTF-8') ?></span>
      <h5 class="fw-bold mb-0 text-truncate" style="max-width: 350px;"><?= htmlspecialchars($exam['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h5>
    </div>
    
    <div class="d-flex align-items-center gap-3">
      <div class="timer-box" id="timerBox">
        <i class="bi bi-clock-history"></i>
        <span id="timerDisplay">00:00</span>
      </div>

      <button type="button" class="btn btn-danger fw-bold px-4" onclick="confirmSubmit()">
        <i class="bi bi-send-check-fill me-1"></i> Nộp bài
      </button>
    </div>
  </div>
</div>

<div class="container mb-5">
  <!-- FORM BẮT ĐẦU TỪ ĐÂY -->
  <form id="examForm" action="../api/submit_exam.php" method="POST">
    <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
    <input type="hidden" name="user_id" value="<?= $user_id ?>">
    <input type="hidden" id="timeTaken" name="time_taken_seconds" value="0">

    <div class="row g-4">
      <div class="col-lg-8">
        <?php foreach ($questions as $index => $q): ?>
          <div class="question-card" id="q-card-<?= $index + 1 ?>">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="badge bg-secondary fs-6">Câu <?= $index + 1 ?></span>
            </div>

            <div class="question-text fw-medium fs-6 text-dark mb-3">
              <?= nl2br(htmlspecialchars($q['question_text'] ?? $q['content'] ?? $q['question'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
            </div>

            <?php if (!empty($q['code_snippet'] ?? $q['code'] ?? '')): ?>
              <pre><code class="language-clike"><?= htmlspecialchars($q['code_snippet'] ?? $q['code'], ENT_QUOTES, 'UTF-8') ?></code></pre>
            <?php endif; ?>

            <div class="options-list d-flex flex-column gap-2 mt-3">
              <?php foreach ($q['shuffled_options'] as $key => $optValue): ?>
                <?php if ($optValue !== null && $optValue !== ''): ?>
                  <div>
                    <input type="radio" 
                           class="btn-check form-check-input" 
                           name="answers[<?= $q['id'] ?>]" 
                           id="q_<?= $q['id'] ?>_<?= $key ?>" 
                           value="<?= $key ?>" 
                           onchange="markAnswered(<?= $index + 1 ?>)">
                    
                    <label class="option-label" for="q_<?= $q['id'] ?>_<?= $key ?>">
                      <strong class="me-2"><?= $key ?>.</strong> <?= htmlspecialchars($optValue, ENT_QUOTES, 'UTF-8') ?>
                    </label>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px;">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Danh sách câu hỏi</h6>
            <div class="question-nav-grid mb-4">
              <?php for ($i = 1; $i <= count($questions); $i++): ?>
                <button type="button" class="btn btn-nav-q" id="nav-btn-<?= $i ?>" onclick="scrollToQuestion(<?= $i ?>)">
                  <?= $i ?>
                </button>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script>
const totalDurationSeconds = <?= $duration_seconds ?>;
let timeRemaining = totalDurationSeconds;
let timeTakenSeconds = 0;

const timerDisplay = document.getElementById('timerDisplay');
const timerBox = document.getElementById('timerBox');
const timeTakenInput = document.getElementById('timeTaken');

const timerInterval = setInterval(function() {
  timeRemaining--;
  timeTakenSeconds++;
  timeTakenInput.value = timeTakenSeconds;

  let minutes = Math.floor(timeRemaining / 60);
  let seconds = timeRemaining % 60;
  minutes = minutes < 10 ? '0' + minutes : minutes;
  seconds = seconds < 10 ? '0' + seconds : seconds;

  timerDisplay.textContent = `${minutes}:${seconds}`;

  if (timeRemaining <= 300) {
    timerBox.classList.add('warning');
  }

  if (timeRemaining <= 0) {
    clearInterval(timerInterval);
    Swal.fire({
      title: 'Hết giờ làm bài!',
      text: 'Hệ thống đang tự động nộp bài...',
      icon: 'clock',
      timer: 2000,
      showConfirmButton: false,
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    }).then(() => {
      document.getElementById('examForm').submit();
    });
  }
}, 1000);

function confirmSubmit() {
  const totalQuestions = document.querySelectorAll('.question-card').length;
  const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
  const unansweredCount = totalQuestions - answeredQuestions;

  let warningMessage = 'Bạn có chắc chắn muốn nộp bài thi ngay bây giờ?';
  if (unansweredCount > 0) {
    warningMessage = `Bạn còn <strong class="text-danger">${unansweredCount}</strong> câu chưa trả lời! Bạn vẫn muốn nộp bài?`;
  }

  Swal.fire({
    title: 'Xác nhận nộp bài?',
    html: warningMessage,
    icon: unansweredCount > 0 ? 'warning' : 'question',
    showCancelButton: true,
    confirmButtonColor: '#6366f1',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Đồng ý nộp',
    cancelButtonText: 'Hủy, làm tiếp',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      clearInterval(timerInterval);
      Swal.fire({
        title: 'Đang gửi bài làm...',
        text: 'Vui lòng chờ trong giây lát',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });
      document.getElementById('examForm').submit();
    }
  });
}

function markAnswered(index) {
  document.getElementById(`nav-btn-${index}`).classList.add('done');
  document.getElementById(`q-card-${index}`).classList.add('answered');
}

function scrollToQuestion(index) {
  const el = document.getElementById(`q-card-${index}`);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>
</body>
</html>