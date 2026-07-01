/**
 * QuizTech Dashboard JavaScript
 */

// ============================================
// Document Ready
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
    initCharts();
    initNotifications();
});

// ============================================
// Dashboard Initialization
// ============================================
function initDashboard() {
    // Auto-refresh stats every 30 seconds
    if (document.querySelector('.auto-refresh')) {
        setInterval(function() {
            refreshStats();
        }, 30000);
    }
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) {
        return new bootstrap.Tooltip(el);
    });
}

// ============================================
// Refresh Stats
// ============================================
function refreshStats() {
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.stats) {
            updateStats(data.stats);
        }
    })
    .catch(error => console.error('Error refreshing stats:', error));
}

function updateStats(stats) {
    Object.keys(stats).forEach(key => {
        const el = document.querySelector(`[data-stat="${key}"]`);
        if (el) {
            el.textContent = formatNumber(stats[key]);
        }
    });
}

// ============================================
// Format Helpers
// ============================================
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
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

// ============================================
// Charts
// ============================================
function initCharts() {
    // Chart.js is loaded globally
    if (typeof Chart === 'undefined') return;
    
    // Performance Chart
    const perfCtx = document.getElementById('performanceChart');
    if (perfCtx) {
        const labels = perfCtx.dataset.labels ? JSON.parse(perfCtx.dataset.labels) : [];
        const scores = perfCtx.dataset.scores ? JSON.parse(perfCtx.dataset.scores) : [];
        
        new Chart(perfCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Điểm số',
                    data: scores,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
}

// ============================================
// Notifications
// ============================================
function initNotifications() {
    // Check for new notifications every 60 seconds
    if (document.querySelector('.notification-badge')) {
        setInterval(function() {
            checkNotifications();
        }, 60000);
    }
}

function checkNotifications() {
    fetch('/api/notifications.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.count > 0) {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = 'inline';
            }
        }
    })
    .catch(error => console.error('Error checking notifications:', error));
}

// ============================================
// Search
// ============================================
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                window.location.href = '/search.php?q=' + encodeURIComponent(this.value);
            }
        });
    }
}

// ============================================
// Export Functions
// ============================================
window.QuizTech = {
    formatNumber: formatNumber,
    formatDate: formatDate,
    timeAgo: timeAgo,
    refreshStats: refreshStats
};