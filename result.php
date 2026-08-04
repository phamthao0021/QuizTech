  <?php
  // result.php
  require_once 'includes/config.php';
  require_once 'includes/functions.php';
  require_once 'includes/auth.php';
  require_once 'includes/data.php';
  requireLogin();

  // ---- XỬ LÝ NỘP BÀI (POST) ----
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      verify_csrf();

      $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
      $time_taken = isset($_POST['time_taken']) ? (int)$_POST['time_taken'] : 0;

      // Validate exam exists
      $exam = getExamById($exam_id);
  if (!$exam) {
      setFlash('danger', 'Đề thi không tồn tại.');
      redirect('exams.php');
  }

  // Cột đúng là "duration" (phút)
  $max_time = ($exam['duration'] ?? 30) * 60;
  if ($time_taken < 5 || $time_taken > $max_time) {
      $time_taken = $max_time;
  }

      // Chống spam cho lượt thi trực tiếp (không qua phòng: room_id IS NULL)
  $stmt = $pdo->prepare("
      SELECT id FROM exam_attempts
      WHERE student_id = ? AND exam_id = ? AND room_id IS NULL
  ");
  $stmt->execute([$_SESSION['user_id'], $exam_id]);
  if ($stmt->fetch()) {
      setFlash('warning', 'Bạn đã làm bài thi này rồi!');
      redirect('exams.php');
  }

      $questions = getQuestionsByExam($exam_id);
  $total = count($questions);
  $correct = 0;
  $unanswered = 0;
  $answers = [];

  foreach ($questions as $q) {
      $user_answer = isset($_POST['q_' . $q['id']]) ? $_POST['q_' . $q['id']] : '';
      $answers[$q['id']] = $user_answer;
      if ($user_answer === '') {
          $unanswered++;
      } elseif ($user_answer === $q['correct_answer']) {
          $correct++;
      }
  }

  $wrong = $total - $correct - $unanswered;
  $score = round(($correct / max($total, 1)) * 10, 1);
  $percentage = round(($correct / max($total, 1)) * 100, 2);
  $started_at = date('Y-m-d H:i:s', time() - $time_taken);

  $stmt = $pdo->prepare("
      INSERT INTO exam_attempts
      (room_id, exam_id, student_id, started_at, submitted_at,
      duration_seconds, score, total_questions, correct_answers,
      wrong_answers, unanswered, percentage, status, answers_json)
      VALUES
      (NULL, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'submitted', ?)
  ");
  $stmt->execute([
      $exam_id, $_SESSION['user_id'], $started_at, $time_taken,
      $score, $total, $correct, $wrong, $unanswered, $percentage,
      json_encode($answers, JSON_UNESCAPED_UNICODE)
  ]);

  // Trigger trg_submit_exam chỉ chạy khi UPDATE, không phải INSERT
  // nên tự ghi log cho luồng nộp bài trực tiếp
  $pdo->prepare("
      INSERT INTO activity_logs (user_id, action, description)
      VALUES (?, 'submit', 'Nộp bài thi')
  ")->execute([$_SESSION['user_id']]);

      // Lưu kết quả vào DB
      saveResult($_SESSION['user_id'], $exam_id, $score, $correct, $total, $time_taken, $answers);

      // PRG: lưu kết quả vào session, redirect sang GET để tránh
      // trình duyệt hỏi "gửi lại form?" khi người dùng F5 trang kết quả,
      // và tránh việc refresh vô tình gọi lại toàn bộ logic chấm điểm.
      $_SESSION['last_result'] = [
          'exam_id'      => $exam_id,
          'exam_title'   => $exam['title'],
          'subject_name' => $exam['subject_name'],
          'score'        => $score,
          'correct'      => $correct,
          'total'        => $total,
          'time_taken'   => $time_taken,
      ];

      redirect('result.php');
  }

  // ---- HIỂN THỊ KẾT QUẢ (GET, đọc từ session) ----
  if (empty($_SESSION['last_result'])) {
      setFlash('warning', 'Không có kết quả để hiển thị.');
      redirect('exams.php');
  }

  $result = $_SESSION['last_result'];
  unset($_SESSION['last_result']); // xem 1 lần; F5 sau đó sẽ không hiển thị lại kết quả cũ

  $exam_title   = $result['exam_title'];
  $subject_name = $result['subject_name'];
  $score        = $result['score'];
  $correct      = $result['correct'];
  $total        = $result['total'];
  $time_taken   = $result['time_taken'];

  $page_title = 'Kết quả thi';
  include 'includes/header.php';
  ?>
  <div class="page-wrapper">
      <?php include 'includes/sidebar.php'; ?>
      <div class="main-content">
          <!-- Topbar -->
          <div class="topbar">
              <div>
                  <h4>Kết quả thi</h4>
                  <p class="text-muted"><?= e($exam_title) ?> • <?= e($subject_name) ?></p>
              </div>
              <span class="badge bg-<?= $score >= 8 ? 'success' : ($score >= 5 ? 'warning' : 'danger') ?>">
                  <?= $score >= 8 ? 'Đạt loại Giỏi' : ($score >= 5 ? 'Đạt' : 'Chưa đạt') ?>
              </span>
          </div>

          <!-- Score Display -->
          <div class="row justify-content-center mb-4">
              <div class="col-lg-8">
                  <!-- Score Card -->
                  <div class="card mb-4" style="border: none; border-radius: 20px; background: linear-gradient(135deg, <?= $score >= 8 ? '#00b894, #00cec9' : ($score >= 5 ? '#fdcb6e, #e17055' : '#e17055, #d63031') ?>); box-shadow: 0 8px 32px rgba(0,0,0,0.15);">
                      <div class="card-body text-center text-white py-5">
                          <div style="font-size: 5rem; line-height: 1; font-weight: 800;">
                              <?php if ($score >= 8): ?>🎉<?php elseif ($score >= 5): ?>👍<?php else: ?>📚<?php endif; ?>
                          </div>
                          <div class="mt-3" style="font-size: 1.1rem; opacity: 0.9;">Điểm số của bạn</div>
                          <div style="font-size: 5rem; font-weight: 800; line-height: 1.1;"><?= number_format($score, 1) ?></div>
                          <div style="font-size: 1.2rem; opacity: 0.8;">/10</div>
                      </div>
                  </div>

                  <!-- Stats Row -->
                  <div class="row g-3 mb-4">
                      <div class="col-4">
                          <div class="stat-card text-center">
                              <div class="stat-number text-success"><?= $correct ?></div>
                              <div class="stat-label">Câu đúng</div>
                          </div>
                      </div>
                      <div class="col-4">
                          <div class="stat-card text-center">
                              <div class="stat-number text-danger"><?= $total - $correct ?></div>
                              <div class="stat-label">Câu sai</div>
                          </div>
                      </div>
                      <div class="col-4">
                          <div class="stat-card text-center">
                              <div class="stat-number text-primary"><?= $total ?></div>
                              <div class="stat-label">Tổng số câu</div>
                          </div>
                      </div>
                  </div>

                  <!-- Progress Bar -->
                  <div class="card mb-4">
                      <div class="card-body">
                          <div class="d-flex justify-content-between mb-2">
                              <span class="fw-600">Tỷ lệ đúng</span>
                              <span class="fw-bold"><?= round(($correct / max($total, 1)) * 100) ?>%</span>
                          </div>
                          <div class="progress" style="height: 14px; border-radius: 7px;">
                              <div class="progress-bar bg-<?= $score >= 8 ? 'success' : ($score >= 5 ? 'warning' : 'danger') ?>"
                                  style="width: <?= ($correct / max($total, 1)) * 100 ?>%; border-radius: 7px;">
                              </div>
                          </div>
                          <div class="text-muted small mt-2">
                              <i class="bi bi-clock"></i> Thời gian làm bài: <?= gmdate('i:s', $time_taken) ?>
                          </div>
                      </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="d-flex gap-2 flex-wrap">
                      <a href="exams.php" class="btn btn-primary">
                          <i class="bi bi-file-text"></i> Danh sách đề thi
                      </a>
                      <a href="leaderboard.php" class="btn btn-outline-primary">
                          <i class="bi bi-trophy"></i> Bảng xếp hạng
                      </a>
                      <a href="history.php" class="btn btn-outline-secondary">
                          <i class="bi bi-clock-history"></i> Lịch sử thi
                      </a>
                      <a href="dashboard.php" class="btn btn-outline-secondary">
                          <i class="bi bi-speedometer2"></i> Dashboard
                      </a>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <?php include 'includes/footer.php'; ?>