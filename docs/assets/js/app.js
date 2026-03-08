/**
 * Laravel Local LLM SDK - Documentation Alpine.js App
 * Handles: dark mode, sidebar toggle, mouse follower, UI state
 */

function app() {
    return {
        sidebarOpen: false,
        darkMode: false,
        currentSection: 'introduction',
        expandedSections: ['getting-started', 'llm-engines', 'usage', 'advanced', 'reference'],
        scrolled: false,
        scrollY: 0,
        
        init() {
            this.initDarkMode();
            this.initScrollState();
            this.initRevealObserver();
            this.initBackToTop();
            if (window.innerWidth > 1024) this.setupMouseFollower();
            
            window.addEventListener('resize', debounce(() => this.handleResize(), 150));
            window.addEventListener('scroll', throttle(() => this.updateScrollY(), 50), { passive: true });
        },
        
        initDarkMode() {
            const stored = localStorage.getItem('darkMode');
            const prefers = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (stored === 'true' || (!stored && prefers)) {
                this.darkMode = true;
                document.documentElement.classList.add('dark');
            }
        },
        
        initScrollState() {
            this.scrollY = window.scrollY;
        },
        
        updateScrollY() {
            this.scrollY = window.scrollY;
            this.scrolled = window.scrollY > 20;
            this.updateBackToTop();
        },
        
        initBackToTop() {
            const btn = document.getElementById('back-to-top');
            if (!btn) return;
            
            // Initial visibility check
            this.updateBackToTop();
            
            // Click handler with smooth scroll
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        },
        
        updateBackToTop() {
            const btn = document.getElementById('back-to-top');
            if (!btn) return;
            
            const shouldShow = window.scrollY > 300;
            
            if (shouldShow) {
                btn.classList.remove('opacity-0', 'translate-y-4', 'invisible');
                btn.classList.add('opacity-100', 'translate-y-0', 'visible');
            } else {
                btn.classList.add('opacity-0', 'translate-y-4', 'invisible');
                btn.classList.remove('opacity-100', 'translate-y-0', 'visible');
            }
        },
        
        initRevealObserver() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('revealed');
                        }
                    });
                }, { threshold: 0.1, rootMargin: '-50px' });
                
                document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
            }
        },
        
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
        
        toggleSection(section) {
            const i = this.expandedSections.indexOf(section);
            if (i > -1) this.expandedSections.splice(i, 1);
            else this.expandedSections.push(section);
        },
        
        animateMenu(el) {
            // Animation handled by CSS
        },
        
        animateClick(el) {
            // Smooth click animation
        },
        
        copyCode(el) {
            // Copy code functionality
        },
        
        closeSidebarOnMobile() {
            if (window.innerWidth < 1024) this.sidebarOpen = false;
        },
        
        handleResize() {
            document.querySelector('main')?.style && (document.querySelector('main').style.transform = '');
            if (window.innerWidth <= 1024) {
                const f = document.querySelector('.mouse-follower');
                if (f) f.style.display = 'none';
            } else {
                const f = document.querySelector('.mouse-follower');
                if (f) f.style.display = 'block';
            }
        },
        
        setupMouseFollower() {
            const f = document.querySelector('.mouse-follower');
            if (!f) return;
            
            let mx = 0, my = 0, fx = 0, fy = 0;
            let rafId = null;
            
            // Use passive event listener for better scroll performance
            document.addEventListener('mousemove', (e) => {
                mx = e.clientX;
                my = e.clientY;
                
                // Request animation frame only when needed
                if (!rafId) {
                    rafId = requestAnimationFrame(() => {
                        fx += (mx - fx) * 0.1;
                        fy += (my - fy) * 0.1;
                        f.style.left = (fx - 10) + 'px';
                        f.style.top = (fy - 10) + 'px';
                        rafId = null;
                    });
                }
            }, { passive: true });
        }
    };
}

function debounce(fn, ms) {
    let t;
    return function(...a) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, a), ms);
    };
}

function throttle(fn, ms) {
    let last = 0;
    return function(...a) {
        if (Date.now() - last >= ms) {
            last = Date.now();
            fn.apply(this, a);
        }
    };
}

// Copy code buttons
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.copy-btn');
    if (!btn) return;
    
    const code = btn.closest('.bg-gray-900, .bg-gray-800')?.querySelector('code');
    if (!code) return;
    
    navigator.clipboard.writeText(code.textContent).then(() => {
        const span = btn.querySelector('span') || btn;
        const orig = span.textContent;
        span.textContent = 'Copied!';
        btn.classList.add('bg-green-600');
        setTimeout(() => { span.textContent = orig; btn.classList.remove('bg-green-600'); }, 1500);
    });
});

// Keyboard: Escape closes sidebar
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape' || window.innerWidth >= 1024) return;
    try {
        Alpine.$data(document.querySelector('[x-data]'))?.set('sidebarOpen', false);
    } catch {}
});
