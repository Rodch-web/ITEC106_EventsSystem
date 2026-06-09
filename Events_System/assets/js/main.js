document.addEventListener('DOMContentLoaded', function () {
    initMobileNav();
    initScrollReveal();
    initNavbarScroll();
    initStatCounters();
    initStarRatings();
});

function initMobileNav() {
    const toggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (!toggle || !navLinks) return;

    toggle.addEventListener('click', function () {
        toggle.classList.toggle('active');
        navLinks.classList.toggle('open');
    });

    navLinks.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            toggle.classList.remove('active');
            navLinks.classList.remove('open');
        });
    });

    document.addEventListener('click', function (e) {
        if (!toggle.contains(e.target) && !navLinks.contains(e.target)) {
            toggle.classList.remove('active');
            navLinks.classList.remove('open');
        }
    });
}

function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    function update() {
        navbar.classList.toggle('scrolled', window.scrollY > 8);
    }

    update();
    window.addEventListener('scroll', update, { passive: true });
}

function initScrollReveal() {
    const elements = document.querySelectorAll('.reveal, .event-card, .dashboard-card, .chart-container');
    if (!elements.length) return;

    elements.forEach(function (el) {
        if (!el.classList.contains('reveal')) {
            el.classList.add('reveal');
        }
    });

    if (!('IntersectionObserver' in window)) {
        elements.forEach(function (el) { el.classList.add('visible'); });
        return;
    }

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal').forEach(function (el) {
        observer.observe(el);
    });
}

function initStatCounters() {
    document.querySelectorAll('.stat-number[data-count]').forEach(function (el) {
        const target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target)) return;

        const duration = 900;
        const start = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased);
            if (progress < 1) requestAnimationFrame(tick);
        }

        const counterObserver = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) {
                requestAnimationFrame(tick);
                counterObserver.disconnect();
            }
        }, { threshold: 0.5 });

        counterObserver.observe(el);
    });
}

function initStarRatings() {
    document.querySelectorAll('.star-rating').forEach(function (ratingGroup) {
        const stars = ratingGroup.querySelectorAll('.star');
        const input = ratingGroup.querySelector('input[type="hidden"]');

        function updateStars(value) {
            stars.forEach(function (star, index) {
                star.classList.toggle('active', index < value);
            });
        }

        stars.forEach(function (star) {
            star.addEventListener('click', function () {
                const value = parseInt(this.getAttribute('data-value'), 10);
                if (input) input.value = value;
                updateStars(value);
            });

            star.addEventListener('mouseenter', function () {
                const value = parseInt(this.getAttribute('data-value'), 10);
                stars.forEach(function (s, i) {
                    s.classList.toggle('hovered', i < value);
                });
            });
        });

        ratingGroup.addEventListener('mouseleave', function () {
            stars.forEach(function (s) { s.classList.remove('hovered'); });
            if (input) updateStars(parseInt(input.value, 10) || 5);
        });

        if (input) updateStars(parseInt(input.value, 10) || 5);
    });
}

function confirmDelete() {
    return confirm('Are you sure you want to delete this item? This action cannot be undone.');
}

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const csv = [];
    table.querySelectorAll('tr').forEach(function (row) {
        const rowData = [];
        row.querySelectorAll('td, th').forEach(function (col) {
            rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });

    const blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}

function printPage() {
    window.print();
}
