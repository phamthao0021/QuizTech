<?php
// includes/footer.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$footerScript = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$parts = array_values(array_filter(explode('/', trim($footerScript, '/'))));
$sectionIndex = null;
foreach (['admin', 'teacher', 'student'] as $section) {
  $index = array_search($section, $parts, true);
  if ($index !== false) {
    $sectionIndex = $index;
    break;
  }
}
$currentSection = $sectionIndex !== null ? $parts[$sectionIndex] : '';
$userRole = strtolower(trim((string)($_SESSION['user']['role'] ?? ($_SESSION['role'] ?? ''))));

// Xóa hoàn toàn footer ở tất cả màn hình thuộc tài khoản Admin và Giảng viên
$isNoFooterScreen = in_array($currentSection, ['admin', 'teacher'], true) 
                 || in_array($userRole, ['admin', 'teacher', 'admin_test'], true);

$asset_prefix = $sectionIndex !== null
  ? str_repeat('../', max(1, count($parts) - $sectionIndex - 1))
  : '';
?>
<?php if ($isNoFooterScreen): ?>
<style>
  /* Tối ưu bố cục khi xóa footer cho Admin và Giảng viên: không khoảng trắng thừa, không cuộn thừa */
  html, body {
    min-height: 100vh;
  }
  .admin-wrapper, .teacher-wrapper {
    min-height: 100vh;
    display: flex;
  }
  .admin-content, .teacher-content {
    flex: 1 1 auto;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }
  .admin-content > main, .teacher-content > main {
    flex: 1 0 auto;
    padding-bottom: 2rem;
  }
  .quiztech-footer {
    display: none !important;
  }
  .quiztech-back-to-top {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 1040;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: #6366f1;
    color: #fff;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transform: translateY(12px);
    transition: all .25s ease;
  }
  .quiztech-back-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }
</style>
</main>
<?php else: ?>
<style>
  .quiztech-footer {
    margin-top: 2rem;
    background: linear-gradient(145deg, #1e123d 0%, #211346 52%, #171329 100%);
    color: #e9e3f4;
    font-size: .91rem;
  }

  .quiztech-footer,
  .quiztech-footer * {
    box-sizing: border-box;
  }

  .quiztech-footer-inner {
    width: min(100% - 3rem, 1240px);
    margin: auto;
    padding: 3rem 0 2.8rem;
  }

  .quiztech-footer-grid {
    display: grid;
    grid-template-columns: 1.25fr 1fr 1fr 1.2fr;
    gap: clamp(1.5rem, 4vw, 4.5rem);
    padding: 3rem 0 2.55rem;
    border-top: 1px solid rgba(255, 255, 255, .09);
  }

  .quiztech-footer-brand {
    display: flex;
    align-items: center;
    gap: .8rem;
    margin-bottom: 1.15rem;
  }

  .quiztech-footer-logo-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 66px;
    height: 66px;
    flex: 0 0 66px;
    border-radius: 15px;
    background: #933ace;
    border: none;
    box-shadow: 0 5px 18px rgba(0, 0, 0, .16);
    overflow: hidden;
  }

  .quiztech-footer-logo {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .quiztech-footer-brand-name {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -.02em;
    color: #fff;
  }

  .quiztech-footer-brand-note {
    margin: .15rem 0 0;
    font-size: .83rem;
    color: rgba(233, 227, 244, .7);
  }

  .quiztech-footer-description {
    margin: 0 0 1.25rem;
    line-height: 1.62;
    color: rgba(233, 227, 244, .8);
  }

  .quiztech-footer-social {
    display: flex;
    gap: .6rem;
  }

  .quiztech-footer-social a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(255, 255, 255, .08);
    color: #fff;
    text-decoration: none;
    transition: background .2s ease, transform .2s ease;
  }

  .quiztech-footer-social a:hover {
    background: #a855f7;
    transform: translateY(-2px);
  }

  .quiztech-footer-title {
    margin: 0 0 1.1rem;
    font-size: .95rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #fff;
  }

  .quiztech-footer-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .quiztech-footer-list li {
    margin-bottom: .6rem;
    color: rgba(233, 227, 244, .82);
  }

  .quiztech-footer-contact {
    display: flex;
    flex-direction: column;
    gap: .75rem;
  }

  .quiztech-footer-contact-item {
    display: flex;
    align-items: flex-start;
    gap: .65rem;
    color: rgba(233, 227, 244, .86);
  }

  .quiztech-footer-contact-item i {
    color: #c084fc;
    margin-top: .15rem;
    flex-shrink: 0;
  }

  .quiztech-footer-contact-item a {
    color: rgba(233, 227, 244, .86);
    text-decoration: none;
  }

  .quiztech-footer-contact-item a:hover {
    color: #fff;
    text-decoration: underline;
  }

  .quiztech-footer-hours {
    margin: .85rem 0 0;
    font-size: .82rem;
    color: rgba(233, 227, 244, .62);
  }

  .quiztech-footer-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-top: 1.8rem;
    border-top: 1px solid rgba(255, 255, 255, .08);
    font-size: .83rem;
    color: rgba(233, 227, 244, .65);
  }

  .quiztech-footer-bottom p {
    margin: 0;
  }

  .quiztech-footer-policy {
    display: flex;
    gap: 1.25rem;
    flex-wrap: wrap;
  }

  .quiztech-footer-policy span {
    color: rgba(233, 227, 244, .65);
  }

  .quiztech-back-to-top {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 1040;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: #7c3aed;
    color: #fff;
    box-shadow: 0 4px 14px rgba(124, 58, 237, .4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transform: translateY(12px);
    transition: all .25s ease;
  }

  .quiztech-back-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }

  @media (max-width: 991.98px) {
    .quiztech-footer-grid {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 575.98px) {
    .quiztech-footer-inner {
      width: calc(100% - 2rem);
      padding: 2.2rem 0 2rem;
    }

    .quiztech-footer-grid {
      grid-template-columns: 1fr;
      gap: 2rem;
      padding-top: 2rem;
    }

    .quiztech-footer-bottom {
      flex-direction: column;
      align-items: flex-start;
      gap: .75rem;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .quiztech-footer-social a,
    .quiztech-back-to-top {
      transition: none;
    }
  }
</style>

</main>
<footer class="quiztech-footer">
  <div class="quiztech-footer-inner">
    <div class="quiztech-footer-grid">
      <div>
        <div class="quiztech-footer-brand">
          <span class="quiztech-footer-logo-wrap"><img src="<?= htmlspecialchars($asset_prefix, ENT_QUOTES, 'UTF-8') ?>assets/images/Cardmoi_PLT_Trang.png" alt="PLT" class="quiztech-footer-logo"></span>
          <div>
            <p class="quiztech-footer-brand-name">QuizTech</p>
            <p class="quiztech-footer-brand-note">Nền tảng học và thi CNTT · PLT Solutions</p>
          </div>
        </div>
        <p class="quiztech-footer-description">Học tập, luyện tập và đánh giá năng lực CNTT trên cùng một nền tảng. QuizTech được phát triển bởi PLT Solutions.</p>
        <div class="quiztech-footer-social"><a href="mailto:pltsolutions3010@gmail.com" aria-label="Gửi email cho PLT Solutions" title="Email"><i class="bi bi-envelope-fill" aria-hidden="true"></i></a></div>
      </div>
      <div>
        <h2 class="quiztech-footer-title">Khám phá QuizTech</h2>
        <ul class="quiztech-footer-list">
          <li>Luyện tập kiến thức</li>
          <li>Đề thi và phòng thi</li>
          <li>Kết quả học tập</li>
        </ul>
      </div>
      <div>
        <h2 class="quiztech-footer-title">Dành cho</h2>
        <ul class="quiztech-footer-list">
          <li>Sinh viên</li>
          <li>Giảng viên</li>
          <li>Quản trị viên</li>
        </ul>
      </div>
      <div>
        <h2 class="quiztech-footer-title">Liên hệ</h2>
        <div class="quiztech-footer-contact">
          <div class="quiztech-footer-contact-item"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>CÔNG TY TNHH PLT SOLUTIONS</span></div>
          <div class="quiztech-footer-contact-item"><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:+84917208678">+84 917 208 678</a></div>
          <div class="quiztech-footer-contact-item"><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:pltsolutions3010@gmail.com">pltsolutions3010@gmail.com</a></div>
        </div>
        <p class="quiztech-footer-hours">Thứ 2 – Thứ 6, 08:00 – 17:00 (ICT)</p>
      </div>
    </div>
    <div class="quiztech-footer-bottom">
      <p>© <?= date('Y') ?> QuizTech · PLT Solutions. Bảo lưu mọi quyền.</p>
      <div class="quiztech-footer-policy"><span>Chính sách bảo mật</span><span>Điều khoản dịch vụ</span><span>Chính sách cookie</span></div>
    </div>
  </div>
</footer>
<?php endif; ?>
<button type="button" class="quiztech-back-to-top" id="quiztechBackToTop" aria-label="Cuộn lên đầu trang" title="Lên đầu trang"><i class="bi bi-chevron-up" aria-hidden="true"></i></button>
<script>
  (function() {
    const btn = document.getElementById('quiztechBackToTop');
    if (!btn) return;
    const toggle = () => btn.classList.toggle('show', window.scrollY > 260);
    window.addEventListener('scroll', toggle, {
      passive: true
    });
    toggle();
    btn.addEventListener('click', () => window.scrollTo({
      top: 0,
      behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'
    }));
  })();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars($asset_prefix, ENT_QUOTES, 'UTF-8') ?>assets/js/app.js"></script>
<script src="<?= htmlspecialchars($asset_prefix, ENT_QUOTES, 'UTF-8') ?>assets/js/datatable.js"></script>
<script src="<?= htmlspecialchars($asset_prefix, ENT_QUOTES, 'UTF-8') ?>assets/js/import.js"></script>
</body>

</html>
