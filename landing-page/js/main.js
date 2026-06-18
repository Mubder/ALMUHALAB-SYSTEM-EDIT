/**
 * ALMuhalab International Holding Group
 * Main JavaScript — Language Toggle & Dynamic Content
 */

(function () {
    'use strict';

    // ─── State ───
    let currentLang = localStorage.getItem('almuhalab-lang') || 'en';
    let contentData = null;

    // ─── DOM Ready ───
    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        await loadContent();
        applyLanguage(currentLang);
        setupLangToggle();
        setupNavScroll();
        setupScrollAnimations();
    }

    // ─── Load Content from JSON ───
    async function loadContent() {
        try {
            const response = await fetch('content/home.json');
            if (!response.ok) throw new Error('Failed to load content');
            contentData = await response.json();
        } catch (err) {
            console.error('Content load error:', err);
            // Fallback: content is hardcoded in HTML
            contentData = null;
        }
    }

    // ─── Language Toggle ───
    function setupLangToggle() {
        const buttons = document.querySelectorAll('.lang-btn');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var lang = this.getAttribute('data-lang');
                currentLang = lang;
                localStorage.setItem('almuhalab-lang', lang);
                applyLanguage(lang);
            });
        });
    }

    // ─── Apply Language ───
    function applyLanguage(lang) {
        // Update active button
        document.querySelectorAll('.lang-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
        });

        // Set direction
        var dir = lang === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', lang);
        document.body.setAttribute('dir', dir);

        if (!contentData) return;

        var data = contentData[lang];
        if (!data) return;

        // Update text content via data-i18n
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            var value = getNestedValue(data, key);
            if (value !== undefined) {
                el.textContent = value;
            }
        });

        // Update placeholders via data-i18n-placeholder
        document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-placeholder');
            var value = getNestedValue(data, key);
            if (value !== undefined) {
                el.setAttribute('placeholder', value);
            }
        });

        // Update title
        document.title = data.site ? data.site.name : 'ALMuhalab International Holding Group';

        // Render dynamic sections
        renderStats(data.stats);
        renderAboutFeatures(data.about ? data.about.features : []);
        renderDivisions(data.divisions ? data.divisions.items : []);
        renderServices(data.services ? data.services.items : []);
        renderFooterLinks(data.footer ? data.footer.links : []);
    }

    // ─── Render Stats ───
    function renderStats(stats) {
        var grid = document.getElementById('statsGrid');
        if (!grid || !stats) return;

        grid.innerHTML = stats.map(function (stat) {
            return '<div class="stat-item animate-in">' +
                '<div class="stat-value">' + escapeHtml(stat.value) + '</div>' +
                '<div class="stat-label">' + escapeHtml(stat.label) + '</div>' +
                '</div>';
        }).join('');

        triggerAnimation(grid);
    }

    // ─── Render About Features ───
    function renderAboutFeatures(features) {
        var container = document.getElementById('aboutFeatures');
        if (!container || !features) return;

        container.innerHTML = features.map(function (feature) {
            return '<div class="about-feature animate-in">' +
                '<div class="about-feature-icon">' + feature.icon + '</div>' +
                '<div>' +
                '<h4>' + escapeHtml(feature.title) + '</h4>' +
                '<p>' + escapeHtml(feature.desc) + '</p>' +
                '</div>' +
                '</div>';
        }).join('');

        triggerAnimation(container);
    }

    // ─── Render Divisions ───
    function renderDivisions(divisions) {
        var container = document.getElementById('divisionsCards');
        if (!container || !divisions) return;

        container.innerHTML = divisions.map(function (div, index) {
            var btnClass = div.featured ? 'btn btn-gold' : 'btn';
            var imageHtml = div.image ? '<div class="card-image"><img src="' + escapeHtml(div.image) + '" alt="' + escapeHtml(div.title) + '" loading="lazy"></div>' : '';
            return '<div class="card animate-in" onclick="window.location.href=\'' + (div.link || '#') + '\'" style="animation-delay: ' + (index * 0.1) + 's">' +
                imageHtml +
                '<div class="card-icon">' + div.icon + '</div>' +
                '<h3>' + escapeHtml(div.title) + '</h3>' +
                '<div class="card-arabic">' + escapeHtml(div.arabic) + '</div>' +
                '<p>' + escapeHtml(div.description) + '</p>' +
                '<a href="' + (div.link || '#') + '" class="' + btnClass + '" onclick="event.stopPropagation();">' + escapeHtml(div.btnText) + '</a>' +
                '</div>';
        }).join('');

        triggerAnimation(container);
    }

    // ─── Render Services ───
    function renderServices(services) {
        var container = document.getElementById('servicesGrid');
        if (!container || !services) return;

        container.innerHTML = services.map(function (service, index) {
            var imageHtml = service.image ? '<div class="service-image"><img src="' + escapeHtml(service.image) + '" alt="' + escapeHtml(service.title) + '" loading="lazy"></div>' : '';
            return '<div class="service-item animate-in" style="animation-delay: ' + (index * 0.08) + 's">' +
                '<div class="service-icon">' + service.icon + '</div>' +
                '<div class="service-content">' +
                imageHtml +
                '<h4>' + escapeHtml(service.title) + '</h4>' +
                '<p>' + escapeHtml(service.description) + '</p>' +
                '</div>' +
                '</div>';
        }).join('');

        triggerAnimation(container);
    }

    // ─── Render Footer Links ───
    function renderFooterLinks(links) {
        var container = document.getElementById('footerLinks');
        if (!container || !links) return;

        container.innerHTML = links.map(function (link) {
            return '<a href="' + link.url + '">' + escapeHtml(link.label) + '</a>';
        }).join('');
    }

    // ─── Navigation Scroll Effect ───
    function setupNavScroll() {
        var nav = document.getElementById('nav');
        if (!nav) return;

        var lastScroll = 0;
        window.addEventListener('scroll', function () {
            var currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > 60) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
            lastScroll = currentScroll;
        }, { passive: true });
    }

    // ─── Scroll Animations ───
    function setupScrollAnimations() {
        if (!('IntersectionObserver' in window)) {
            // Fallback: show all
            document.querySelectorAll('.animate-in').forEach(function (el) {
                el.classList.add('visible');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        });

        // Observe static elements
        document.querySelectorAll('.animate-in').forEach(function (el) {
            observer.observe(el);
        });

        // Store observer globally for dynamically added elements
        window._scrollObserver = observer;
    }

    function triggerAnimation(container) {
        if (!window._scrollObserver) return;
        var elements = container.querySelectorAll('.animate-in');
        elements.forEach(function (el) {
            window._scrollObserver.observe(el);
        });
    }

    // ─── Utilities ───
    function getNestedValue(obj, path) {
        return path.split('.').reduce(function (current, key) {
            return current && current[key] !== undefined ? current[key] : undefined;
        }, obj);
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
