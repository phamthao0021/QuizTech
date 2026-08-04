<?php
// includes/footer_guest.php
?>
</main>

<footer class="bg-light text-center py-4 mt-4">
    <div class="container">
        <p class="text-muted mb-0">&copy; <?= date('Y') ?> QuizTech - Trắc nghiệm CNTT</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Lightweight reveal-on-scroll for .qt-reveal elements
    (function() {
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('.qt-reveal').forEach(function(el) {
                el.classList.add('qt-in');
            });
            return;
        }
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('qt-in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('.qt-reveal').forEach(function(el) {
            io.observe(el);
        });
    })();
</script>
</body>
</html>