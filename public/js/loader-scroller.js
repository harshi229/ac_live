/**
 * Modern Loader & Smooth Scroller JavaScript
 * Professional loading animations and smooth scrolling functionality
 */

class LoaderScroller {
    constructor() {
        this.init();
    }

    init() {
        this.createPageLoader();
        this.createScrollToTop();
        this.initSmoothScrolling();
        this.initLazyLoading();
        this.initLoadingStates();
        this.initPageTransitions();
        this.bindEvents();
    }

    /**
     * Create and manage page loader
     */
    createPageLoader() {
        // Check if loader already exists
        if (document.getElementById('pageLoader')) {
            return;
        }

        // Create loader HTML
        const loaderHTML = `
            <div class="page-loader" id="pageLoader">
                <div class="loader-container">
                    <div class="loader-ring"></div>
                    <div class="loader-ring"></div>
                    <div class="loader-ring"></div>
                    <div class="loader-ring"></div>
                    <div class="loader-logo">
                        <i class="fas fa-snowflake"></i>
                    </div>
                </div>
                <div class="loader-text">Akash Enterprise</div>
                <div class="loader-subtext">Loading your AC experience...</div>
                <div class="loader-progress">
                    <div class="loader-progress-bar"></div>
                </div>
            </div>
        `;

        // Add loader to body
        document.body.insertAdjacentHTML('afterbegin', loaderHTML);

        // Hide loader when page is fully loaded
        window.addEventListener('load', () => {
            setTimeout(() => {
                this.hideLoader();
            }, 1000); // Show loader for at least 1 second
        });

        // Also hide loader after a maximum time to prevent it from staying forever
        setTimeout(() => {
            this.hideLoader();
        }, 5000); // Maximum 5 seconds
    }

    /**
     * Hide page loader with fade out animation
     */
    hideLoader() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            loader.classList.add('fade-out');
            setTimeout(() => {
                loader.remove();
            }, 500);
        }
    }

    /**
     * Create scroll to top button
     */
    createScrollToTop() {
        const scrollButton = document.createElement('button');
        scrollButton.className = 'scroll-to-top';
        scrollButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollButton.setAttribute('aria-label', 'Scroll to top');
        scrollButton.setAttribute('title', 'Scroll to top');
        
        document.body.appendChild(scrollButton);

        // Show/hide button based on scroll position
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollButton.classList.add('visible');
            } else {
                scrollButton.classList.remove('visible');
            }
        });

        // Scroll to top on click
        scrollButton.addEventListener('click', () => {
            this.smoothScrollTo(0);
        });
    }

    /**
     * Initialize smooth scrolling for anchor links
     */
    initSmoothScrolling() {
        // Handle anchor links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    const offsetTop = targetElement.offsetTop - 80; // Account for fixed header
                    this.smoothScrollTo(offsetTop);
                }
            }
        });
    }

    /**
     * Smooth scroll to specific position
     */
    smoothScrollTo(targetPosition) {
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        const duration = Math.min(Math.abs(distance) / 2, 1000); // Max 1 second
        let startTime = null;

        function animation(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const progress = Math.min(timeElapsed / duration, 1);
            
            // Easing function (ease-out)
            const ease = 1 - Math.pow(1 - progress, 3);
            
            window.scrollTo(0, startPosition + distance * ease);
            
            if (progress < 1) {
                requestAnimationFrame(animation);
            }
        }

        requestAnimationFrame(animation);
    }

    /**
     * Initialize lazy loading for images and content
     */
    initLazyLoading() {
        const lazyElements = document.querySelectorAll('.lazy-load');
        
        if ('IntersectionObserver' in window) {
            const lazyObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('loaded');
                        lazyObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            lazyElements.forEach(element => {
                lazyObserver.observe(element);
            });
        } else {
            // Fallback for older browsers
            lazyElements.forEach(element => {
                element.classList.add('loaded');
            });
        }
    }

    /**
     * Initialize loading states for forms and buttons
     */
    initLoadingStates() {
        // Form loading states
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.classList.contains('form-with-loader')) {
                form.classList.add('form-loading');
            }
        });

        // Button loading states
        document.addEventListener('click', (e) => {
            const button = e.target.closest('button[data-loading]');
            if (button) {
                button.classList.add('btn-loading');
                button.disabled = true;
                
                // Remove loading state after 3 seconds (or when form submits)
                setTimeout(() => {
                    button.classList.remove('btn-loading');
                    button.disabled = false;
                }, 3000);
            }
        });
    }

    /**
     * Initialize page transitions
     */
    initPageTransitions() {
        const pageContent = document.querySelector('main, .main-content, .container');
        if (pageContent) {
            pageContent.classList.add('page-transition');
            
            window.addEventListener('load', () => {
                setTimeout(() => {
                    pageContent.classList.add('loaded');
                }, 100);
            });
        }
    }

    /**
     * Bind additional events
     */
    bindEvents() {
        // Keyboard navigation for scroll to top
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Home' && e.ctrlKey) {
                e.preventDefault();
                this.smoothScrollTo(0);
            }
        });

        // Handle window resize
        window.addEventListener('resize', this.debounce(() => {
            // Recalculate positions if needed
        }, 250));

        // Handle visibility change (tab switching)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Pause animations when tab is not visible
                document.body.style.animationPlayState = 'paused';
            } else {
                // Resume animations when tab becomes visible
                document.body.style.animationPlayState = 'running';
            }
        });
    }

    /**
     * Utility function to debounce events
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Show loading overlay for specific element
     */
    showElementLoader(element, text = 'Loading...') {
        const loader = document.createElement('div');
        loader.className = 'element-loader';
        loader.innerHTML = `
            <div class="element-loader-content">
                <div class="element-loader-spinner"></div>
                <div class="element-loader-text">${text}</div>
            </div>
        `;
        
        element.style.position = 'relative';
        element.appendChild(loader);
        
        return loader;
    }

    /**
     * Hide loading overlay for specific element
     */
    hideElementLoader(element) {
        const loader = element.querySelector('.element-loader');
        if (loader) {
            loader.remove();
        }
    }

    /**
     * Create skeleton loading for content
     */
    createSkeletonLoader(container, count = 3) {
        const skeletonHTML = Array(count).fill(0).map(() => `
            <div class="skeleton-item">
                <div class="skeleton-avatar"></div>
                <div class="skeleton-content">
                    <div class="skeleton-text short"></div>
                    <div class="skeleton-text medium"></div>
                    <div class="skeleton-text long"></div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = skeletonHTML;
    }

    /**
     * Replace skeleton with actual content
     */
    replaceSkeleton(container, content) {
        container.innerHTML = content;
        container.classList.add('loaded');
    }
}

// Additional utility functions
const LoaderUtils = {
    /**
     * Show toast notification
     */
    showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, duration);
    },

    /**
     * Get icon for toast type
     */
    getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    /**
     * Animate counter from 0 to target value
     */
    animateCounter(element, target, duration = 2000) {
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 16);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.loaderScroller = new LoaderScroller();
    window.loaderUtils = LoaderUtils;
});

// Also initialize immediately if DOM is already loaded (for homepage)
if (document.readyState === 'loading') {
    // DOM is still loading, wait for DOMContentLoaded
} else {
    // DOM is already loaded, initialize immediately
    window.loaderScroller = new LoaderScroller();
    window.loaderUtils = LoaderUtils;
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { LoaderScroller, LoaderUtils };
}
