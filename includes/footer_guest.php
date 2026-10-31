<?php
// includes/footer_guest.php - Footer dành riêng cho Guest / Landing page
?>
</main>

<style>
/* ==========================================================
   QUIZTECH MODERN FOOTER (EDTECH & SAAS CLEAN)
   ========================================================== */
.qt-landing-footer {
    background: #121129;
    color: #E2E1F0;
    padding: 68px 0 32px;
    position: relative;
    overflow: hidden;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}
.qt-landing-footer::before {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(101, 84, 240, 0.12), transparent 70%);
    top: -200px;
    right: -100px;
    pointer-events: none;
}
.qt-landing-footer .container {
    max-width: 1280px;
}
.qt-footer-brand-title {
    font-family: "Space Grotesk", Inter, sans-serif;
    font-size: 1.45rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
}
.qt-footer-brand-title span {
    color: #8B5CF6;
}
.qt-footer-desc {
    color: #9CA3AF;
    font-size: 0.92rem;
    line-height: 1.68;
    max-width: 330px;
}
.qt-footer-heading {
    color: #FFFFFF;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin-bottom: 1.25rem;
    text-transform: uppercase;
}
.qt-footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.qt-footer-links a {
    color: #9CA3AF;
    text-decoration: none;
    font-size: 0.92rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.qt-footer-links a:hover {
    color: #FFFFFF;
    transform: translateX(3px);
}
.qt-footer-contact-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: #9CA3AF;
    font-size: 0.9rem;
    margin-bottom: 0.85rem;
    line-height: 1.5;
}
.qt-footer-contact-item i {
    color: #8B5CF6;
    font-size: 1.05rem;
    flex-shrink: 0;
    margin-top: 2px;
}
.qt-footer-contact-item a {
    color: #9CA3AF;
    text-decoration: none;
    transition: color 0.2s ease;
}
.qt-footer-contact-item a:hover {
    color: #FFFFFF;
}
.qt-footer-bottom {
    margin-top: 50px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    color: #6B7280;
    font-size: 0.86rem;
}
.qt-footer-bottom a {
    color: #6B7280;
    text-decoration: none;
    transition: color 0.2s;
}
.qt-footer-bottom a:hover {
    color: #9CA3AF;
}
.qt-btt-btn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 44px;
    height: 44px;
    background: #6554F0;
    color: #fff;
    border: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    box-shadow: 0 8px 24px rgba(101, 84, 240, 0.35);
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transform: translateY(12px);
    transition: all 0.25s ease;
    z-index: 999;
}
.qt-btt-btn.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
.qt-btt-btn:hover {
    background: #4F3DE5;
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(101, 84, 240, 0.45);
}
</style>

<footer class="qt-landing-footer">
    <div class="container">
        <div class="row g-4 justify-content-between">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="assets/images/Cardmoi_PLT_Trang.png" alt="PLT Solutions" style="height: 34px; width: auto; object-fit: contain;">
                    <span class="qt-footer-brand-title">Quiz<span>Tech</span></span>
                </div>
                <p class="qt-footer-desc">
                    Hệ sinh thái luyện thi và đánh giá năng lực Công nghệ Thông tin dành cho sinh viên. Phát triển và bảo trợ bởi PLT Solutions.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="mailto:pltsolutions3010@gmail.com" class="btn btn-sm btn-outline-light rounded-circle" style="width:36px; height:36px; display:grid; place-items:center;" aria-label="Email" title="Email QuizTech">
                        <i class="bi bi-envelope"></i>
                    </a>
                    <a href="tel:+84917208678" class="btn btn-sm btn-outline-light rounded-circle" style="width:36px; height:36px; display:grid; place-items:center;" aria-label="Điện thoại" title="Hotline">
                        <i class="bi bi-telephone"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="qt-footer-heading">Điều hướng</h6>
                <ul class="qt-footer-links">
                    <li><a href="index.php"><i class="bi bi-chevron-right small"></i>Trang chủ</a></li>
                    <li><a href="index.php#about"><i class="bi bi-chevron-right small"></i>Giới thiệu</a></li>
                    <li><a href="index.php#method"><i class="bi bi-chevron-right small"></i>Phương pháp học</a></li>
                    <li><a href="index.php#features"><i class="bi bi-chevron-right small"></i>Tính năng</a></li>
                    <li><a href="demo.php"><i class="bi bi-chevron-right small"></i>Dùng thử ngay</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="qt-footer-heading">Tính năng chính</h6>
                <ul class="qt-footer-links">
                    <li><a href="exams.php"><i class="bi bi-chevron-right small"></i>Đề thi & Luyện tập</a></li>
                    <li><a href="rooms.php"><i class="bi bi-chevron-right small"></i>Phòng thi Online</a></li>
                    <li><a href="demo.php"><i class="bi bi-chevron-right small"></i>Kiểm tra năng lực</a></li>
                    <li><a href="login.php?tab=login"><i class="bi bi-chevron-right small"></i>Lịch sử làm bài</a></li>
                    <li><a href="index.php#contact"><i class="bi bi-chevron-right small"></i>Trung tâm hỗ trợ</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="qt-footer-heading">Liên hệ & Hỗ trợ</h6>
                <div class="qt-footer-contact-item">
                    <i class="bi bi-geo-alt"></i>
                    <span>72 Lê Thánh Tôn, Bến Nghé, Quận 1, TP. Hồ Chí Minh</span>
                </div>
                <div class="qt-footer-contact-item">
                    <i class="bi bi-telephone"></i>
                    <a href="tel:+84917208678">+84 917 208 678</a>
                </div>
                <div class="qt-footer-contact-item">
                    <i class="bi bi-envelope"></i>
                    <a href="mailto:pltsolutions3010@gmail.com">pltsolutions3010@gmail.com</a>
                </div>
                <div class="qt-footer-contact-item">
                    <i class="bi bi-clock"></i>
                    <span>Hỗ trợ: Thứ 2 – Thứ 6 (08:00 – 17:30)</span>
                </div>
            </div>
        </div>

        <div class="qt-footer-bottom">
            <div>
                &copy; <?= date('Y') ?> <strong>QuizTech</strong> — Nền tảng luyện thi CNTT. Phát triển bởi <strong>PLT Solutions</strong>. Bảo lưu mọi quyền.
            </div>
            <div class="d-flex gap-3">
                <a href="#home">Lên đầu trang</a>
                <span>&bull;</span>
                <a href="index.php#contact">Góp ý hệ thống</a>
                <span>&bull;</span>
                <a href="demo.php">Trải nghiệm thử</a>
            </div>
        </div>
    </div>
</footer>

<button type="button" class="qt-btt-btn" id="qtBackToTop" aria-label="Cuộn lên đầu trang" title="Lên đầu trang">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    // Back to top button logic
    const btt = document.getElementById('qtBackToTop');
    if (btt) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                btt.classList.add('show');
            } else {
                btt.classList.remove('show');
            }
        }, { passive: true });
        btt.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
            });
        });
    }

    // Smooth scroll for nav anchor links & auto collapse mobile navbar
    document.querySelectorAll('a[href^="index.php#"], a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').replace('index.php', '');
            if (targetId && targetId !== '#') {
                const targetElem = document.querySelector(targetId);
                if (targetElem) {
                    e.preventDefault();
                    // Close mobile nav if opened
                    const navCollapse = document.getElementById('navbarNav');
                    if (navCollapse && navCollapse.classList.contains('show')) {
                        const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                        if (bsCollapse) {
                            bsCollapse.hide();
                        }
                    }
                    targetElem.scrollIntoView({
                        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
                    });
                }
            }
        });
    });
})();
</script>
</body>
</html>