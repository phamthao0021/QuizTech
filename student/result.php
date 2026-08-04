<?php
// student/result.php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = currentUser();
$user_id = (int)($user['id'] ?? $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

$result_id = (int)($_GET['id'] ?? 0);
if ($result_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

// 1. LẤY DỮ LIỆU KẾT QUẢ BÀI THI
$stmt = $pdo->prepare("
    SELECT r.*, e.title AS exam_title, s.name AS subject_name 
    FROM results r
    LEFT JOIN exams e ON r.exam_id = e.id
    LEFT JOIN subjects s ON e.subject_id = s.id
    WHERE r.id = :result_id AND r.user_id = :user_id
");
$stmt->execute([
    'result_id' => $result_id,
    'user_id'   => $user_id
]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die("Không tìm thấy kết quả hoặc bạn không có quyền xem bài thi này!");
}

// 2. LẤY DANH SÁCH CÂU HỎI TRONG ĐỀ THI
$stmtQ = $pdo->prepare("
    SELECT * FROM questions 
    WHERE subject_id = (SELECT subject_id FROM exams WHERE id = :exam_id)
    ORDER BY id ASC
");
$stmtQ->execute(['exam_id' => $result['exam_id']]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Giải mã đáp án người dùng đã chọn (Lưu dưới dạng JSON trong DB)
$user_answers = json_decode($result['answers'] ?? '{}', true) ?: [];
$page_title   = 'Kết quả bài thi - ' . ($result['exam_title'] ?? '');

// INCLUDES HEADER & SIDEBAR HỆ THỐNG
$headerFile = dirname(__DIR__) . '/includes/header_user.php';
if (!file_exists($headerFile)) $headerFile = dirname(__DIR__) . '/includes/header.php';
if (file_exists($headerFile)) include $headerFile;

$sidebarFile = dirname(__DIR__) . '/includes/sidebar.php';
if (file_exists($sidebarFile)) include $sidebarFile;
?>

<!-- CSS ĐỒNG BỘ LAYOUT SIDEBAR & TRIỆT TIÊU KHOẢNG TRỐNG TÊN TOP -->
<style>
  body {
    display: flex !important;
    margin: 0 !important;
    padding: 0 !important;
    background-color: #f8fafc !important;
  }

  /* Định vị Sidebar bên trái */
  .sidebar-wrapper {
    width: 240px !important;
    min-width: 240px !important;
    height: 100vh !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 1000 !important;
  }

  /* Ép nội dung lên sát mép đỉnh trang (top: 0) bên phải Sidebar */
  .main-content-layout {
    margin-left: 240px !important;
    width: calc(100% - 240px) !important;
    min-height: 100vh !important;
    padding: 24px 30px !important;
    box-sizing: border-box !important;
    margin-top: 0 !important;
    padding-top: 24px !important;
  }

  /* Thẻ hiển thị điểm số */
  .score-card {
    background: linear-gradient(135deg, #635bff, #805ad5);
    color: white;
    border-radius: 16px;
  }

  /* Khung thẻ câu hỏi */
  .question-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    margin-bottom: 16px;
  }

  /* Thẻ hiển thị từng đáp án A, B, C, D */
  .option-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    margin-top: 8px;
    background: #ffffff;
  }

  /* Tô màu đáp án đúng/sai */
  .opt-correct {
    background-color: #d1fae5 !important;
    border-color: #10b981 !important;
    color: #065f46 !important;
    font-weight: 600;
  }

  .opt-wrong {
    background-color: #fee2e2 !important;
    border-color: #ef4444 !important;
    color: #991b1b !important;
    font-weight: 600;
  }

  @media (max-width: 768px) {
    body { display: block !important; }
    .sidebar-wrapper { position: relative !important; width: 100% !important; height: auto !important; }
    .main-content-layout { margin-left: 0 !important; width: 100% !important; padding: 15px !important; }
  }
</style>

<!-- NỘI DUNG TRANG KẾT QUẢ CÂN BẰNG BÊN PHẢI SIDEBAR -->
<div class="main-content-layout">
  <div class="container-fluid p-0" style="max-width: 900px;">
    
    <!-- BANNER TỔNG QUAN ĐIỂM SỐ -->
    <div class="card border-0 shadow-sm score-card p-4 text-center mb-4">
      <span class="badge bg-white text-primary fw-bold mx-auto px-3 py-2 mb-2 rounded-pill fs-6">
        <?= htmlspecialchars($result['subject_name'] ?? 'Môn học') ?>
      </span>
      <h3 class="fw-bold mb-1"><?= htmlspecialchars($result['exam_title'] ?? 'Kết quả bài thi') ?></h3>
      <p class="opacity-75 small mb-3"><i class="bi bi-calendar-check me-1"></i> Ngày làm: <?= date('H:i - d/m/Y', strtotime($result['created_at'])) ?></p>
      
      <div class="display-3 fw-bold my-1"><?= number_format($result['score'], 1) ?><span class="fs-4">/10</span></div>

      <div class="d-flex justify-content-center gap-3 mt-3 fs-6 flex-wrap">
        <div class="bg-white bg-opacity-10 px-3 py-2 rounded-3">
          <i class="bi bi-check-circle-fill text-warning me-1"></i> Đúng: <strong><?= $result['correct_answers'] ?>/<?= $result['total_questions'] ?></strong> câu
        </div>
        <div class="bg-white bg-opacity-10 px-3 py-2 rounded-3">
          <i class="bi bi-clock-history me-1"></i> Thời gian: <strong><?= floor($result['time_taken'] / 60) ?>m <?= $result['time_taken'] % 60 ?>s</strong>
        </div>
      </div>
    </div>

    <!-- NÚT TRỞ VỀ -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold text-dark mb-0"><i class="bi bi-card-checklist me-2 text-primary"></i>Chi tiết đáp án</h5>
      <a href="dashboard.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-3">
        <i class="bi bi-arrow-left me-1"></i> Quay lại Trang chủ
      </a>
    </div>

    <!-- DANH SÁCH CHI TIẾT CÂU HỎI -->
    <?php if (empty($questions)): ?>
      <div class="alert alert-info border-0 shadow-sm">Không có dữ liệu chi tiết các câu hỏi cho bài thi này.</div>
    <?php else: ?>
      <?php foreach ($questions as $idx => $q): ?>
        <?php 
          $q_id = $q['id'];
          // Chuẩn hóa tên trường lấy đáp án đúng
          $correct_opt = strtoupper(trim($q['correct_option'] ?? $q['correct_answer'] ?? $q['answer'] ?? $q['correct'] ?? ''));
          $user_opt = isset($user_answers[$q_id]) ? strtoupper(trim($user_answers[$q_id])) : 'CHƯA_CHỌN';
          
          $options = [
            'A' => $q['option_a'] ?? $q['option_A'] ?? $q['a'] ?? '',
            'B' => $q['option_b'] ?? $q['option_B'] ?? $q['b'] ?? '',
            'C' => $q['option_c'] ?? $q['option_C'] ?? $q['c'] ?? '',
            'D' => $q['option_d'] ?? $q['option_D'] ?? $q['d'] ?? ''
          ];
          
          $isUserCorrect = ($user_opt === $correct_opt);
        ?>

        <div class="question-card shadow-sm">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge bg-secondary fs-6">Câu <?= $idx + 1 ?></span>
            <?php if ($isUserCorrect): ?>
              <span class="badge bg-success-subtle text-success fs-6"><i class="bi bi-check-lg me-1"></i>Chính xác</span>
            <?php else: ?>
              <span class="badge bg-danger-subtle text-danger fs-6"><i class="bi bi-x-lg me-1"></i>Chưa chính xác</span>
            <?php endif; ?>
          </div>

          <div class="fw-semibold text-dark fs-6 mb-3">
            <?= nl2br(htmlspecialchars($q['question_text'] ?? $q['content'] ?? $q['question'] ?? '')) ?>
          </div>

          <div>
            <?php foreach ($options as $key => $val): ?>
              <?php if ($val !== '' && $val !== null): ?>
                <?php 
                  $cssClass = '';
                  if ($key === $correct_opt) {
                    $cssClass = 'opt-correct';
                  } elseif ($key === $user_opt && !$isUserCorrect) {
                    $cssClass = 'opt-wrong';
                  }
                ?>
                <div class="option-box <?= $cssClass ?>">
                  <div><strong class="me-2"><?= $key ?>.</strong> <?= htmlspecialchars($val) ?></div>
                  <div>
                    <?php if ($key === $correct_opt): ?>
                      <span class="badge bg-success text-white">Đáp án đúng</span>
                    <?php endif; ?>
                    <?php if ($key === $user_opt): ?>
                      <span class="badge bg-dark ms-1">Bạn đã chọn</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<?php 
$footerFile = dirname(__DIR__) . '/includes/footer.php';
if (file_exists($footerFile)) include $footerFile;
?>