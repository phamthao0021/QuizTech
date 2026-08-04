// assets/js/app.js
document.addEventListener('DOMContentLoaded', function() {
    // Auto dismiss alerts
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(el) {
        setTimeout(function() {
            el.classList.add('fade');
            setTimeout(function() {
                el.remove();
            }, 500);
        }, 5000);
    });
    
    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Bạn có chắc chắn?')) {
                e.preventDefault();
            }
        });
    });
    
    // Tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }
});

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

function timeAgo(dateString) {
    const now = new Date();
    const past = new Date(dateString);
    const diff = Math.floor((now - past) / 1000);
    
    if (diff < 60) return 'vài giây trước';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
    if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước';
    return formatDate(dateString);
}