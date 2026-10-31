<?php

/**
 * Trang chủ QuizTech - Nền tảng luyện thi & đánh giá năng lực CNTT
 * Redesign: Modern EdTech + Clean SaaS + Soft Purple
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';

$page_title = 'Trang chủ';

// Lấy dữ liệu thực tế từ database
$subjects = getSubjects() ?? [];
$exams    = getExams()    ?? [];
$stats    = getStats()    ?? [];

$stats_subjects  = (int)($stats['subjects'] ?? 0);
$stats_exams     = (int)($stats['exams'] ?? 0);
$stats_questions = (int)($stats['questions'] ?? 0);
$stats_results   = (int)($stats['results'] ?? 0);

require_once 'includes/header_guest.php';
?>

<!-- Link stylesheet riêng cho Landing Page -->
<link rel="stylesheet" href="assets/css/guest-landing.css">

<style>
/* Typewriter: đặt ngay trong index.php để không phụ thuộc cache file CSS */
#qtTypingText {
    display: inline;
    border-right: 3px solid currentColor;
    padding-right: 3px;
    animation: qtTypingBlink .75s step-end infinite;
}
@keyframes qtTypingBlink {
    50% { border-right-color: transparent; }
}
</style>

<div class="qt-home">

  <!-- =========================================================
       SECTION 2: HERO SECTION
       ========================================================= -->
  <section class="qt-hero-section" id="home">
    <div class="qt-hero-bg-blob-1"></div>
    <div class="qt-hero-bg-blob-2"></div>

    <div class="container qt-container">
      <div class="row align-items-center g-5">

        <!-- Cột trái: Nội dung chính -->
        <div class="col-lg-6">
          <div class="qt-badge-caps">
            <i class="bi bi-stars"></i> PLT &bull; NỀN TẢNG LUYỆN THI CNTT
          </div>

          <h1 class="qt-hero-heading">
    Học lập trình<br>
    vững kiến thức<br>
    bằng
    <span class="qt-typing-wrapper">
        <span
            class="qt-highlight qt-typing-text"
            id="qtTypingText"
            aria-label="Luyện tập thực tế, làm chủ kiến thức, chinh phục thử thách"
        >luyện tập thực tế.</span>
    </span>
</h1>

          <p class="qt-hero-lead">
            QuizTech giúp bạn ôn luyện lập trình, CSDL, Web, Mạng máy tính và An toàn thông tin
            thông qua ngân hàng câu hỏi, bài thi trực tuyến và hệ thống phân tích kết quả học tập.
          </p>

          <div class="d-flex flex-wrap gap-3 align-items-center justify-content-lg-start justify-content-center">
            <a href="demo.php" class="qt-btn-primary">
              <i class="bi bi-play-circle-fill"></i> Bắt đầu học ngay
            </a>
            <a href="#features" class="qt-btn-secondary">
              <i class="bi bi-compass"></i> Khám phá tính năng
            </a>
          </div>

          <div class="qt-hero-pills">
            <div class="qt-hero-pill-item">
              <i class="bi bi-check-circle-fill"></i> Nhiều chủ đề CNTT
            </div>
            <div class="qt-hero-pill-item">
              <i class="bi bi-check-circle-fill"></i> Luyện tập theo năng lực
            </div>
            <div class="qt-hero-pill-item">
              <i class="bi bi-check-circle-fill"></i> Theo dõi tiến độ học tập
            </div>
          </div>
        </div>

        <!-- Cột phải: Minh họa sản phẩm (Mockup Dashboard trực quan) -->
        <div class="col-lg-6">
          <div class="qt-mockup-wrap">
            <div class="qt-dashboard-card">

              <!-- Mockup Header -->
              <div class="qt-db-header">
                <div class="qt-db-brand">
                  <div class="qt-db-icon-box">
                    <i class="bi bi-cpu-fill"></i>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold" style="font-size:0.96rem;">Bảng điều khiển QuizTech</h6>
                    <small class="text-muted" style="font-size:0.75rem;">Phiên bản sinh viên CNTT</small>
                  </div>
                </div>
                <div class="qt-db-live-tag">
                  <span class="qt-db-live-dot"></span> Đang hoạt động
                </div>
              </div>

              <!-- Card bài thi đang diễn ra -->
              <div class="qt-exam-in-progress">
                <div class="qt-eip-meta">
                  <span class="qt-eip-tag">Đang luyện tập</span>
                  <span class="qt-eip-time"><i class="bi bi-clock-history"></i> Còn 25:00</span>
                </div>
                <div class="qt-eip-title">
                  Kiến trúc Dữ liệu & Giải thuật nâng cao
                </div>
                <div class="d-flex justify-content-between small text-muted mb-1" style="font-size:0.75rem;">
                  <span>Tiến độ hoàn thành</span>
                  <span class="fw-bold text-dark">15 / 20 câu (75%)</span>
                </div>
                <div class="qt-progress-bar-wrap">
                  <div class="qt-progress-bar-fill"></div>
                </div>
              </div>

              <!-- 2 Card kết quả / năng lực -->
              <div class="qt-db-grid-cards">
                <div class="qt-db-mini-card">
                  <div class="qt-db-mini-card-label">
                    <i class="bi bi-bullseye text-primary me-1"></i> Tỷ lệ chính xác
                  </div>
                  <div class="qt-db-mini-card-value text-success">
                    86.5%
                  </div>
                  <small class="text-muted" style="font-size:0.72rem;">&uarr; Cải thiện sau 3 lần thi</small>
                </div>
                <div class="qt-db-mini-card">
                  <div class="qt-db-mini-card-label">
                    <i class="bi bi-layers-half text-primary me-1"></i> Ngân hàng đề thi
                  </div>
                  <div class="qt-db-mini-card-value">
                    <?= $stats_exams > 0 ? $stats_exams : '5+' ?> <span class="small fw-normal text-muted" style="font-size:0.8rem;">bộ đề</span>
                  </div>
                  <small class="text-muted" style="font-size:0.72rem;">Đa dạng môn học CNTT</small>
                </div>
              </div>

              <!-- Danh sách chủ đề CNTT nổi bật -->
              <div class="qt-db-topics-list">
                <div class="qt-db-topic-row">
                  <span class="qt-topic-name">
                    <i class="bi bi-code-slash text-primary"></i> Lập trình Web & Backend
                  </span>
                  <span class="qt-topic-badge qt-tb-high">Thành thạo</span>
                </div>
                <div class="qt-db-topic-row">
                  <span class="qt-topic-name">
                    <i class="bi bi-database text-info"></i> Cơ sở dữ liệu & SQL
                  </span>
                  <span class="qt-topic-badge qt-tb-mid">Đang ôn luyện</span>
                </div>
                <div class="qt-db-topic-row">
                  <span class="qt-topic-name">
                    <i class="bi bi-shield-lock text-warning"></i> An toàn thông tin
                  </span>
                  <span class="qt-topic-badge qt-tb-plan">Cần củng cố</span>
                </div>
              </div>

              <!-- Mockup Footer -->
              <div class="qt-db-footer">
                <span><i class="bi bi-check2-circle text-primary me-1"></i> Chấm điểm tức thì sau khi nộp</span>
                <span>Hệ thống chấm tự động</span>
              </div>

            </div>

            <!-- Floating Badge -->
            <div class="qt-mockup-floating-badge">
              <i class="bi bi-patch-check-fill"></i>
              <span>Hoàn thành bài đánh giá &bull; 9.0/10</span>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- =========================================================
       SECTION 3: TRUST / HIGHLIGHTS (GIÁ TRỊ CỐT LÕI)
       ========================================================= -->
  <section class="qt-trust-section">
    <div class="container qt-container">
      <div class="qt-trust-grid">

        <div class="qt-trust-item">
          <div class="qt-trust-icon">
            <i class="bi bi-lightbulb"></i>
          </div>
          <div class="qt-trust-content">
            <h6>Học tập chủ động</h6>
            <p>Tự do lựa chọn kiến thức cần củng cố theo nhịp học cá nhân.</p>
          </div>
        </div>

        <div class="qt-trust-item">
          <div class="qt-trust-icon">
            <i class="bi bi-folder2-open"></i>
          </div>
          <div class="qt-trust-content">
            <h6>Luyện tập theo chủ đề</h6>
            <p>Ngân hàng câu hỏi phân tách rõ ràng từng chuyên ngành CNTT.</p>
          </div>
        </div>

        <div class="qt-trust-item">
          <div class="qt-trust-icon">
            <i class="bi bi-bar-chart-line"></i>
          </div>
          <div class="qt-trust-content">
            <h6>Theo dõi kết quả</h6>
            <p>Hệ thống tự động lưu lịch sử và phân tích tỷ lệ câu đúng.</p>
          </div>
        </div>

        <div class="qt-trust-item">
          <div class="qt-trust-icon">
            <i class="bi bi-laptop"></i>
          </div>
          <div class="qt-trust-content">
            <h6>Hỗ trợ thi trực tuyến</h6>
            <p>Phòng thi thời gian thực hỗ trợ lớp học và câu lạc bộ.</p>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- =========================================================
       SECTION 4: GIỚI THIỆU QUIZTECH
       ========================================================= -->
  <section class="qt-about-section" id="about">
    <div class="container qt-container">
      <div class="row align-items-center g-5">

        <div class="col-lg-5">
          <div class="qt-section-tag">
            <i class="bi bi-info-circle"></i> Về QuizTech
          </div>
          <h2 class="qt-section-title">
            Không chỉ làm bài thi, mà còn hiểu rõ năng lực của bạn.
          </h2>
          <p class="qt-section-subtitle mb-4">
            QuizTech được thiết kế nhằm đồng hành cùng sinh viên công nghệ thông tin trong mọi giai đoạn học tập.
            Thay vì chỉ nhận một con số điểm tổng quát, bạn sẽ biết rõ phần kiến thức nào mình đã nắm chắc
            và phần nào cần được cải thiện ngay lập tức.
          </p>

          <!-- Thống kê thực tế từ hệ thống -->
          <div class="row g-3">
            <div class="col-6">
              <div class="qt-stat-box">
                <strong><?= $stats_subjects > 0 ? $stats_subjects : 5 ?></strong>
                <span>Môn học CNTT</span>
              </div>
            </div>
            <div class="col-6">
              <div class="qt-stat-box">
                <strong><?= $stats_questions > 0 ? $stats_questions : 50 ?></strong>
                <span>Câu hỏi chuẩn hóa</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="qt-journey-wrap">

            <div class="qt-journey-step">
              <div class="qt-journey-num">1</div>
              <div class="qt-journey-info">
                <strong>Chọn chủ đề &bull; Bắt đầu có mục tiêu</strong>
                <small>Lựa chọn môn học đang ôn thi: Lập trình C/C++, Java, Web, CSDL hoặc Mạng.</small>
              </div>
              <div class="qt-journey-icon">
                <i class="bi bi-bullseye"></i>
              </div>
            </div>

            <div class="qt-journey-step">
              <div class="qt-journey-num">2</div>
              <div class="qt-journey-info">
                <strong>Luyện tập & Trải nghiệm tương tác</strong>
                <small>Trả lời câu hỏi trắc nghiệm kèm giải thích trực quan, củng cố lý thuyết nền tảng.</small>
              </div>
              <div class="qt-journey-icon">
                <i class="bi bi-puzzle"></i>
              </div>
            </div>

            <div class="qt-journey-step">
              <div class="qt-journey-num">3</div>
              <div class="qt-journey-info">
                <strong>Làm bài thi thử & Thi trực tuyến</strong>
                <small>Kiểm tra dưới áp lực thời gian thực tế với ngân hàng đề phong phú.</small>
              </div>
              <div class="qt-journey-icon">
                <i class="bi bi-stopwatch"></i>
              </div>
            </div>

            <div class="qt-journey-step">
              <div class="qt-journey-num">4</div>
              <div class="qt-journey-info">
                <strong>Xem kết quả & Phân tích chi tiết</strong>
                <small>Hệ thống tự động chấm điểm, chỉ ra các câu sai và thống kê theo nhóm năng lực.</small>
              </div>
              <div class="qt-journey-icon">
                <i class="bi bi-graph-up-arrow"></i>
              </div>
            </div>

            <div class="qt-journey-step">
              <div class="qt-journey-num">5</div>
              <div class="qt-journey-info">
                <strong>Ôn tập phần còn yếu & Tiến bộ mỗi ngày</strong>
                <small>Tập trung thời gian vào các chủ đề chưa đạt để sẵn sàng cho bài thi chính thức.</small>
              </div>
              <div class="qt-journey-icon">
                <i class="bi bi-award"></i>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- =========================================================
       SECTION 5: TÍNH NĂNG NỔI BẬT (6 FEATURE CARDS)
       ========================================================= -->
  <section class="qt-features-section" id="features">
    <div class="container qt-container">

      <div class="text-center mb-5">
        <div class="qt-section-tag">
          <i class="bi bi-stars"></i> Tính năng hệ thống
        </div>
        <h2 class="qt-section-title">
          Công cụ cần thiết cho hành trình chinh phục CNTT
        </h2>
        <p class="qt-section-subtitle mx-auto">
          Mỗi tính năng trên QuizTech được xây dựng bám sát nhu cầu học tập và ôn thi thực tế của sinh viên CNTT.
        </p>
      </div>

      <div class="row g-4">

        <!-- Feature 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="qt-feature-card">
            <div class="qt-feature-icon-box">
              <i class="bi bi-cpu"></i>
            </div>
            <h5 class="qt-feature-title">Ngân hàng câu hỏi CNTT</h5>
            <p class="qt-feature-desc">
              Tập hợp câu hỏi đa dạng các môn học lập trình, cấu trúc dữ liệu, cơ sở dữ liệu, mạng máy tính và an toàn thông tin.
            </p>
            <a href="demo.php" class="qt-feature-link">
              Trải nghiệm thử <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="qt-feature-card">
            <div class="qt-feature-icon-box">
              <i class="bi bi-controller"></i>
            </div>
            <h5 class="qt-feature-title">Luyện tập & Chế độ tương tác</h5>
            <p class="qt-feature-desc">
              Chế độ luyện tập nhẹ nhàng giúp người học làm quen với các khái niệm phức tạp một cách trực quan, ghi nhớ lâu hơn.
            </p>
            <a href="demo.php" class="qt-feature-link">
              Luyện tập ngay <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-lg-4 col-md-6">
          <div class="qt-feature-card">
            <div class="qt-feature-icon-box">
              <i class="bi bi-stopwatch"></i>
            </div>
            <h5 class="qt-feature-title">Thi trực tuyến thời gian thực</h5>
            <p class="qt-feature-desc">
              Môi trường làm bài thi có bấm giờ, hỗ trợ phòng thi mã PIN để thầy cô và bạn bè dễ dàng tổ chức kiểm tra chung.
            </p>
            <a href="rooms.php" class="qt-feature-link">
              Khám phá phòng thi <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 4 -->
        <div class="col-lg-4 col-md-6">
          <div class="qt-feature-card">
            <div class="qt-feature-icon-box">
              <i class="bi bi-lightning-charge"></i>
            </div>
            <h5 class="qt-feature-title">Chấm điểm & Xem kết quả</h5>
            <p class="qt-feature-desc">
              Nhận kết quả bài làm ngay khi nộp, xem lại đáp án đúng sai chi tiết giúp sửa sai kịp thời sau mỗi lần thử sức.
            </p>
            <a href="demo.php" class="qt-feature-link">
              Làm bài thử <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 5 -->
        <div class="col-lg-4 col-md-6">
          <div class="qt-feature-card">
            <div class="qt-feature-icon-box">
              <i class="bi bi-clock-history"></i>
            </div>
            <h5 class="qt-feature-title">Theo dõi lịch sử học tập</h5>
            <p class="qt-feature-desc">
              Lưu giữ toàn bộ bài thi đã thực hiện, giúp sinh viên quan sát tiến trình điểm số và mức độ tiến bộ qua từng tuần.
            </p>
            <a href="login.php?tab=login" class="qt-feature-link">
              Đăng nhập để xem <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 6 -->
        <div class="col-lg-4 col-md-6">
          <div class="qt-feature-card">
            <div class="qt-feature-icon-box">
              <i class="bi bi-trophy"></i>
            </div>
            <h5 class="qt-feature-title">Bảng xếp hạng & Thi đua</h5>
            <p class="qt-feature-desc">
              Tạo động lực học tập với bảng thành tích dựa trên điểm số và tốc độ hoàn thành, khích lệ sinh viên rèn luyện mỗi ngày.
            </p>
            <a href="login.php?tab=login" class="qt-feature-link">
              Tham gia thi đua <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- =========================================================
       SECTION 6: PHƯƠNG PHÁP HỌC (4 STEPS)
       ========================================================= -->
  <section class="qt-method-section" id="method">
    <div class="container qt-container">

      <div class="text-center mb-5">
        <div class="qt-section-tag">
          <i class="bi bi-diagram-3"></i> Phương pháp học tập
        </div>
        <h2 class="qt-section-title">
          Bắt đầu học theo cách phù hợp với bạn
        </h2>
        <p class="qt-section-subtitle mx-auto">
          Quy trình 4 bước tinh gọn giúp bạn chuyển từ người học thụ động thành người chủ động làm chủ kiến thức chuyên ngành.
        </p>
      </div>

      <div class="row g-4">

        <div class="col-lg-3 col-md-6">
          <div class="qt-step-card">
            <div class="qt-step-header">
              <span class="qt-step-digit">01</span>
              <div class="qt-step-icon">
                <i class="bi bi-book"></i>
              </div>
            </div>
            <h5 class="qt-step-title">Chọn chủ đề cần ôn luyện</h5>
            <p class="qt-step-desc">
              Xác định môn học hoặc phân vùng kiến thức bạn đang chuẩn bị kiểm tra trên trường lớp.
            </p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="qt-step-card">
            <div class="qt-step-header">
              <span class="qt-step-digit">02</span>
              <div class="qt-step-icon">
                <i class="bi bi-pencil-square"></i>
              </div>
            </div>
            <h5 class="qt-step-title">Làm bài tập hoặc luyện tập</h5>
            <p class="qt-step-desc">
              Thực hành các câu hỏi trắc nghiệm với thời gian thực hoặc chế độ tự do thoải mái.
            </p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="qt-step-card">
            <div class="qt-step-header">
              <span class="qt-step-digit">03</span>
              <div class="qt-step-icon">
                <i class="bi bi-check2-circle"></i>
              </div>
            </div>
            <h5 class="qt-step-title">Kiểm tra kết quả & xác định câu sai</h5>
            <p class="qt-step-desc">
              Quan sát đáp án đúng sai, đọc lại phần giải thích để hiểu rõ lý do vì sao mình chọn nhầm.
            </p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="qt-step-card">
            <div class="qt-step-header">
              <span class="qt-step-digit">04</span>
              <div class="qt-step-icon">
                <i class="bi bi-arrow-repeat"></i>
              </div>
            </div>
            <h5 class="qt-step-title">Ôn tập & cải thiện kiến thức</h5>
            <p class="qt-step-desc">
              Lặp lại bài thi ở các phần chưa thành thạo để củng cố phản xạ và ghi nhớ sâu bền.
            </p>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- =========================================================
       SECTION 7: KHU VỰC CTA NỔI BẬT
       ========================================================= -->
  <section class="qt-cta-section">
    <div class="container qt-container">
      <div class="qt-cta-banner">

        <div class="qt-cta-badge">
          <i class="bi bi-rocket-takeoff-fill"></i> Bắt đầu hoàn toàn miễn phí
        </div>

        <h2 class="qt-cta-title">
          Sẵn sàng nâng cao kỹ năng CNTT của bạn?
        </h2>

        <p class="qt-cta-desc">
          Bắt đầu hành trình luyện tập, củng cố kiến thức và theo dõi tiến bộ cùng QuizTech ngay hôm nay.
        </p>

        <div class="qt-cta-actions">
          <a href="login.php?tab=register" class="qt-btn-cta-white">
            <i class="bi bi-person-plus-fill"></i> Đăng ký ngay
          </a>
          <a href="login.php?tab=login" class="qt-btn-cta-outline">
            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
          </a>
          <a href="demo.php" class="qt-btn-cta-outline">
            <i class="bi bi-play-circle"></i> Dùng thử miễn phí
          </a>
        </div>

      </div>
    </div>
  </section>


  <!-- =========================================================
       SECTION LIÊN HỆ & GÓP Ý
       ========================================================= -->
  <section class="qt-contact-section" id="contact">
    <div class="container qt-container">
      <div class="row g-5 align-items-center">

        <div class="col-lg-5">
          <div class="qt-section-tag">
            <i class="bi bi-envelope"></i> Hỗ trợ & Phản hồi
          </div>
          <h2 class="qt-section-title">
            Liên hệ với nhóm phát triển QuizTech
          </h2>
          <p class="qt-section-subtitle mb-4">
            Bạn có câu hỏi, góp ý về nội dung câu hỏi hoặc muốn đề xuất tính năng mới? Hãy gửi tin nhắn cho chúng tôi.
          </p>

          <div class="qt-contact-info-row">
            <i class="bi bi-geo-alt-fill"></i>
            <div>
              <strong>Địa chỉ văn phòng</strong>
              <div class="text-muted small">72 Lê Thánh Tôn, Bến Nghé, Quận 1, TP. Hồ Chí Minh</div>
            </div>
          </div>

          <div class="qt-contact-info-row">
            <i class="bi bi-telephone-fill"></i>
            <div>
              <strong>Số điện thoại hỗ trợ</strong>
              <div class="text-muted small">+84 917 208 678</div>
            </div>
          </div>

          <div class="qt-contact-info-row">
            <i class="bi bi-envelope-fill"></i>
            <div>
              <strong>Email liên hệ</strong>
              <div class="text-muted small">pltsolutions3010@gmail.com</div>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="qt-contact-box">
            <h5 class="fw-bold mb-2">Gửi tin nhắn trực tiếp</h5>
            <p class="text-muted small mb-4">
              Điền thông tin bên dưới, hệ thống sẽ mở ứng dụng email để bạn gửi góp ý trực tiếp đến PLT Solutions.
            </p>

            <form id="qtContactForm">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Họ và tên</label>
                  <input type="text" id="qtName" class="form-control" placeholder="Nguyễn Văn A" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Email của bạn</label>
                  <input type="email" id="qtEmail" class="form-control" placeholder="student@example.com" required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold small">Chủ đề liên hệ</label>
                  <input type="text" id="qtSubject" class="form-control" placeholder="Ví dụ: Góp ý câu hỏi môn CSDL" required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold small">Nội dung tin nhắn</label>
                  <textarea id="qtMessage" class="form-control" rows="4" placeholder="Mô tả nội dung bạn cần hỗ trợ hoặc góp ý..." required></textarea>
                </div>
                <div class="col-12">
                  <button type="submit" class="qt-btn-primary w-100 justify-content-center">
                    <i class="bi bi-send-fill"></i> Gửi qua Email
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // 1. HERO TYPEWRITER ANIMATION
    // ==========================================

    const typingElement = document.getElementById('qtTypingText');

    if (typingElement) {
        // Luôn chạy hiệu ứng: phiên bản cũ dừng hoàn toàn nếu hệ điều hành
        // bật chế độ Reduce motion.
        const phrases = [
            'luyện tập thực tế.',
            'làm chủ kiến thức.',
            'chinh phục thử thách.'
        ];
        const typingSpeed = 85;
        const deletingSpeed = 45;
        const pauseAfterTyping = 1800;
        const pauseBeforeTyping = 400;

        let phraseIndex = 0;
        let charIndex = 0;
        let deleting = false;
        let timerId = null;

        // Tách theo ký tự Unicode để không cắt đôi dấu tiếng Việt.
        const chars = phrases.map(phrase => Array.from(phrase));
        typingElement.textContent = '';

        function typeWriter() {
            const letters = chars[phraseIndex];
            if (!deleting) {
                charIndex = Math.min(charIndex + 1, letters.length);
                typingElement.textContent = letters.slice(0, charIndex).join('');
                if (charIndex === letters.length) {
                    deleting = true;
                    timerId = setTimeout(typeWriter, pauseAfterTyping);
                } else {
                    timerId = setTimeout(typeWriter, typingSpeed);
                }
            } else {
                charIndex = Math.max(charIndex - 1, 0);
                typingElement.textContent = letters.slice(0, charIndex).join('');
                if (charIndex === 0) {
                    deleting = false;
                    phraseIndex = (phraseIndex + 1) % chars.length;
                    timerId = setTimeout(typeWriter, pauseBeforeTyping);
                } else {
                    timerId = setTimeout(typeWriter, deletingSpeed);
                }
            }
        }
        typeWriter();
        window.addEventListener('pagehide', function () {
            if (timerId !== null) clearTimeout(timerId);
        }, { once: true });
    } else {
        console.warn('[QuizTech] Thiếu phần tử #qtTypingText');
    }


    // ==========================================
    // 2. CONTACT FORM SUBMISSION
    // ==========================================

    const form = document.getElementById('qtContactForm');

    if (form) {

        form.addEventListener('submit', function (event) {

            event.preventDefault();

            const name = (
                document.getElementById('qtName')?.value || ''
            ).trim();

            const email = (
                document.getElementById('qtEmail')?.value || ''
            ).trim();

            const subject = (
                document.getElementById('qtSubject')?.value || ''
            ).trim();

            const message = (
                document.getElementById('qtMessage')?.value || ''
            ).trim();

            const mailSubject = '[QuizTech] ' + subject;

            const mailBody =
                'Họ tên: ' + name +
                '\nEmail: ' + email +
                '\n\n' + message;

            window.location.href =
                'mailto:pltsolutions3010@gmail.com' +
                '?subject=' + encodeURIComponent(mailSubject) +
                '&body=' + encodeURIComponent(mailBody);

        });
    }

});
</script>

<?php
// Bao gồm footer chuẩn của Guest
require_once 'includes/footer_guest.php';
?>