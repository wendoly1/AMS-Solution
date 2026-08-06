// AMS Legal Solutions - JS base

document.addEventListener('DOMContentLoaded', function () {
    var menuToggle = document.querySelector('.menu-toggle');
    var mainNav = document.querySelector('.main-nav');

    function closeMenu() {
        if (!menuToggle || !mainNav) return;
        menuToggle.classList.remove('is-open');
        mainNav.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Abrir menú');
    }

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function () {
            var willOpen = !mainNav.classList.contains('is-open');
            menuToggle.classList.toggle('is-open', willOpen);
            mainNav.classList.toggle('is-open', willOpen);
            menuToggle.setAttribute('aria-expanded', String(willOpen));
            menuToggle.setAttribute('aria-label', willOpen ? 'Cerrar menú' : 'Abrir menú');
        });

        mainNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeMenu();
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 860) closeMenu();
        });
    }

    var revealEls = document.querySelectorAll('.reveal, .reveal-group');
    var counterEls = document.querySelectorAll('[data-count]');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        counterEls.forEach(function (el) {
            el.textContent = el.dataset.count + (el.dataset.suffix || '');
        });
        return;
    }

    // Group each reveal-group's children by row (same vertical position)
    // so a whole row fades in together instead of one card at a time.
    function assignRowDelays() {
        document.querySelectorAll('.reveal-group').forEach(function (group) {
            var children = Array.prototype.slice.call(group.children);
            if (!children.length) return;

            var stagger = parseFloat(group.dataset.stagger) || 0.12;
            var tops = children.map(function (el) {
                return Math.round(el.getBoundingClientRect().top);
            });
            var rows = Array.from(new Set(tops)).sort(function (a, b) { return a - b; });

            children.forEach(function (el, i) {
                var rowIndex = rows.indexOf(tops[i]);
                el.style.transitionDelay = (rowIndex * stagger) + 's';
            });
        });
    }

    // Animated counters (stat numbers): count up from 0 each time they
    // come into view, and reset so they can replay on re-entry.
    function animateCounter(el) {
        var target = parseFloat(el.dataset.count);
        var suffix = el.dataset.suffix || '';
        var duration = 1400;
        var startTime = null;

        cancelAnimationFrame(el._counterRaf);

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.round(target * eased);
            el.textContent = current.toLocaleString('es-HN') + suffix;
            if (progress < 1) {
                el._counterRaf = requestAnimationFrame(step);
            }
        }

        el._counterRaf = requestAnimationFrame(step);
    }

    function resetCounter(el) {
        cancelAnimationFrame(el._counterRaf);
        el.textContent = '0' + (el.dataset.suffix || '');
    }

    if (!('IntersectionObserver' in window)) {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        counterEls.forEach(function (el) { el.textContent = el.dataset.count + (el.dataset.suffix || ''); });
        return;
    }

    assignRowDelays();

    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(assignRowDelays, 200);
    });

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            entry.target.classList.toggle('is-visible', entry.isIntersecting);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

    revealEls.forEach(function (el) { observer.observe(el); });

    if (counterEls.length) {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                } else {
                    resetCounter(entry.target);
                }
            });
        }, { threshold: 0.2, rootMargin: '0px 0px -40px 0px' });

        counterEls.forEach(function (el) { counterObserver.observe(el); });
    }
});
