<?php
// includes/footer.php
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
$asset_prefix = $sectionIndex !== null
  ? str_repeat('../', max(1, count($parts) - $sectionIndex - 1))
  : '';
?>
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
    color: #fff;
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .quiztech-footer-brand-note {
    margin: .15rem 0 0;
    font-size: .72rem;
    color: #c4b5fd;
    font-weight: 600;
    letter-spacing: .02em;
  }

  .quiztech-footer-description {
    margin: 0 0 1.2rem;
    line-height: 1.65;
    max-width: 290px;
  }

  .quiztech-footer-title {
    margin: 0 0 1.15rem;
    font-size: .88rem;
    font-weight: 700;
    color: #fff;
  }

  .quiztech-footer-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: .85rem;
  }

  .quiztech-footer-list li {
    line-height: 1.45;
  }

  .quiztech-footer a {
    color: #e9e3f4;
    text-decoration: none;
  }

  .quiztech-footer a:hover,
  .quiztech-footer a:focus-visible {
    color: #ddd6fe;
    text-decoration: underline;
    text-underline-offset: 3px;
  }

  .quiztech-footer-contact {
    display: grid;
    gap: .8rem;
  }

  .quiztech-footer-contact-item {
    display: flex;
    align-items: flex-start;
    gap: .7rem;
    min-width: 0;
    overflow-wrap: anywhere;
  }

  .quiztech-footer-contact-item i {
    color: #c4b5fd;
    font-size: 1rem;
    line-height: 1.4;
    flex: 0 0 18px;
  }

  .quiztech-footer-hours {
    margin: .2rem 0 0 1.9rem;
    font-size: .82rem;
  }

  .quiztech-footer-social {
    display: flex;
    gap: .6rem;
  }

  .quiztech-footer-social a {
    display: inline-grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid rgba(196, 181, 253, .4);
    background: rgba(255, 255, 255, .07);
    color: #fff;
  }

  .quiztech-footer-social a:hover {
    text-decoration: none;
    border-color: #a78bfa;
  }

  .quiztech-footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, .09);
    padding-top: 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem 2rem;
  }

  .quiztech-footer-bottom p {
    margin: 0;
  }

  .quiztech-footer-policy {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem 1.5rem;
  }

  .quiztech-back-to-top {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 1040;
    width: 46px;
    height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
    border-radius: 50%;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .2);
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity .25s, transform .25s, visibility .25s, background .25s;
  }

  .quiztech-back-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }

  .quiztech-back-to-top:hover {
    background: #6d28d9;
    color: #fff;
  }

  .quiztech-back-to-top:focus-visible {
    outline: 3px solid #a78bfa;
    outline-offset: 3px;
  }

  @media (max-width: 900px) {
    .quiztech-footer-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 575.98px) {
    .quiztech-footer-inner {
      width: min(100% - 2rem, 1240px);
      padding: 1rem 0 2rem;
    }

    .quiztech-footer-grid {
      grid-template-columns: 1fr;
      gap: 2rem;
      padding: 2rem 0;
    }

    .quiztech-footer-description {
      max-width: none;
    }

    .quiztech-footer-bottom {
      align-items: flex-start;
      padding-top: 1.5rem;
    }

    .quiztech-footer-policy {
      gap: .8rem 1rem;
    }

    .quiztech-back-to-top {
      right: 16px;
      bottom: 16px;
    }
  }

  @media (prefers-reduced-motion: reduce) {
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