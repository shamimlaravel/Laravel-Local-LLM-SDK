/**
 * Laravel Local LLM SDK - Documentation Scroll System
 * @version 2.1.0 - Clean & Fixed
 */

(function() {
    'use strict';

    // ============================================
    // CONFIG
    // ============================================
    const CONFIG = {
        headerHeight: { mobile: 64, desktop: 80 },
        observer: { rootMargin: '-80px 0px -70% 0px', threshold: 0 },
        scroll: { offset: 24, duration: 400 },
        backToTop: { threshold: 400 }
    };

    // ============================================
    // UTILITIES
    // ============================================
    function debounce(fn, ms) {
        let t;
        return function(...a) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, a), ms);
        };
    }

    function throttle(fn, ms) {
        let last = 0, t;
        return function(...a) {
            const now = Date.now();
            if (now - last >= ms) {
                last = now;
                fn.apply(this, a);
            }
        };
    }

    function getHeaderHeight() {
        return window.innerWidth >= 768 ? CONFIG.headerHeight.desktop : CONFIG.headerHeight.mobile;
    }

    // ============================================
    // SMOOTH SCROLL
    // ============================================
    function smoothScrollTo(target, opts = {}) {
        const el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) return false;

        const startY = window.scrollY;
        const endY = el.getBoundingClientRect().top + startY - getHeaderHeight() - (opts.offset ?? CONFIG.scroll.offset);
        const dist = endY - startY;
        const dur = opts.duration ?? CONFIG.scroll.duration;
        let start = null;

        if (window._ssFrame) cancelAnimationFrame(window._ssFrame);

        function step(t) {
            start ??= t;
            const p = Math.min((t - start) / dur, 1);
            const ease = 1 - Math.pow(1 - p, 4);
            window.scrollTo(0, startY + dist * ease);
            if (p < 1) window._ssFrame = requestAnimationFrame(step);
            else window._ssFrame = null;
        }

        window._ssFrame = requestAnimationFrame(step);
        return true;
    }

    // ============================================
    // SCROLLSPY
    // ============================================
    class ScrollSpy {
        constructor(onChange) {
            this.sections = [];
            this.activeId = '';
            this.observer = null;
            this.onChange = onChange;
        }

        init() {
            this.sections = Array.from(document.querySelectorAll('section[id]'));
            if (!this.sections.length) return;

            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver(this.onIntersect.bind(this), CONFIG.observer);
                this.sections.forEach(s => this.observer.observe(s));
            }

            // Set initial
            this.setActiveFromScroll();
        }

        onIntersect(entries) {
            const visible = entries.filter(e => e.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (visible.length) this.setActive(visible[0].target.id);
        }

        setActiveFromScroll() {
            const sy = window.scrollY;
            const hh = getHeaderHeight();
            for (const s of this.sections) {
                if (sy >= s.offsetTop - hh - 100 && sy < s.offsetTop + s.offsetHeight - hh - 100) {
                    this.setActive(s.id);
                    break;
                }
            }
        }

        setActive(id) {
            if (id === this.activeId) return;
            this.activeId = id;
            this.onChange(id);
        }

        destroy() {
            this.observer?.disconnect();
        }
    }

    // ============================================
    // SIDEBAR
    // ============================================
    class Sidebar {
        constructor() {
            this.nav = document.querySelector('aside nav');
            this.links = [];
        }

        getLinks() {
            if (!this.links.length && this.nav) {
                this.links = Array.from(this.nav.querySelectorAll('a[href^="#"]'));
            }
            return this.links;
        }

        setActive(id) {
            this.getLinks().forEach(link => {
                const sid = link.getAttribute('href')?.slice(1);
                if (sid === id) {
                    this.activate(link);
                } else {
                    this.deactivate(link);
                }
            });
        }

        activate(link) {
            link.classList.remove('menu-link', 'text-gray-600', 'dark:text-gray-400');
            link.classList.add('menu-link-active', 'active', 'text-indigo-600', 'dark:text-indigo-400');
            link.setAttribute('aria-current', 'section');
        }

        deactivate(link) {
            link.classList.remove('menu-link-active', 'active', 'text-indigo-600', 'dark:text-indigo-400');
            link.classList.add('menu-link', 'text-gray-600', 'dark:text-gray-400');
            link.removeAttribute('aria-current');
        }
    }

    // ============================================
    // BACK TO TOP
    // ============================================
    class BackToTop {
        constructor() {
            this.btn = document.getElementById('back-to-top');
            this.t = CONFIG.backToTop.threshold;
        }

        init() {
            if (!this.btn) return;
            this.update();
            this.btn.onclick = () => smoothScrollTo(document.body, { offset: 0 });
            window.addEventListener('scroll', throttle(() => this.update(), 100), { passive: true });
        }

        update() {
            if (!this.btn) return;
            const show = window.scrollY > this.t;
            this.btn.classList.toggle('opacity-100', show);
            this.btn.classList.toggle('translate-y-0', show);
            this.btn.classList.toggle('opacity-0', !show);
            this.btn.classList.toggle('translate-y-4', !show);
            this.btn.classList.toggle('invisible', !show && this.btn.classList.contains('opacity-0'));
        }
    }

    // ============================================
    // SCROLL PROGRESS
    // ============================================
    class Progress {
        constructor() {
            this.bar = document.querySelector('.scroll-progress');
        }

        init() {
            if (!this.bar) return;
            this.update();
            window.addEventListener('scroll', throttle(() => this.update(), 50), { passive: true });
        }

        update() {
            if (!this.bar) return;
            const p = document.documentElement.scrollHeight - window.innerHeight;
            this.bar.style.width = (p > 0 ? (window.scrollY / p) * 100 : 0) + '%';
        }
    }

    // ============================================
    // MAIN SYSTEM
    // ============================================
    class ScrollSystem {
        constructor() {
            this.spy = null;
            this.sidebar = new Sidebar();
            this.backToTop = new BackToTop();
            this.progress = new Progress();
        }

        init() {
            // Init scrollspy
            this.spy = new ScrollSpy((id) => {
                this.sidebar.setActive(id);
                if (history.pushState) history.pushState(null, '', '#' + id);
            });
            this.spy.init();

            // Init components
            this.backToTop.init();
            this.progress.init();

            // Click handler
            document.addEventListener('click', (e) => {
                const a = e.target.closest('a[href^="#"]');
                if (!a) return;
                const id = a.getAttribute('href').slice(1);
                if (!id) return;
                e.preventDefault();
                smoothScrollTo(document.getElementById(id));
                this.closeMobileSidebar();
            });

            // Hash change
            window.addEventListener('hashchange', () => {
                const id = location.hash.slice(1);
                if (id) {
                    this.spy.setActive(id);
                    this.sidebar.setActive(id);
                }
            });

            // Initial hash
            const hash = location.hash.slice(1);
            if (hash) {
                setTimeout(() => smoothScrollTo(document.getElementById(hash), { duration: 0 }), 0);
            }

            // Resize
            window.addEventListener('resize', debounce(() => this.spy?.setActiveFromScroll(), 150));

            console.log('[ScrollSystem] Ready');
        }

        closeMobileSidebar() {
            if (window.innerWidth >= 1024) return;
            try {
                const d = Alpine?.$data?.(document.querySelector('[x-data]'));
                if (d) d.sidebarOpen = false;
            } catch {}
        }
    }

    // ============================================
    // START
    // ============================================
    const system = new ScrollSystem();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => system.init());
    } else {
        system.init();
    }

    window.scrollSystem = {
        to: (id) => smoothScrollTo(document.getElementById(id)),
        toTop: () => smoothScrollTo(document.body, { offset: 0 })
    };
})();
