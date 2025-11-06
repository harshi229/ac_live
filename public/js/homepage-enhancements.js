/* ================= HOMEPAGE ENHANCEMENTS JAVASCRIPT ================= */
/* Enhanced functionality for homepage */

// Global variables
let exitIntentShown = false;

// ================= IMAGE LOADING ENHANCEMENT ================= //
function initializeImageLoading() {
    // Force immediate loading of critical images
    const criticalImages = document.querySelectorAll('img[loading="eager"]');
    
    criticalImages.forEach(img => {
        // Add loading state management
        img.addEventListener('load', function() {
            this.style.opacity = '1';
            this.classList.add('loaded');
            this.style.transition = 'opacity 0.3s ease';
            console.log('Image loaded successfully:', this.src);
        });
        
        img.addEventListener('error', function() {
            console.warn('Failed to load image:', this.src);
            // Set opacity to 1 even on error to show fallback
            this.style.opacity = '1';
            this.classList.add('loaded');
        });
        
        // Set initial opacity for smooth transition only if image is not already loaded
        if (!img.complete) {
            img.style.opacity = '0';
        } else {
            // Image is already loaded, show it immediately
            img.style.opacity = '1';
            img.classList.add('loaded');
        }
        
        // Force load by setting src again if not already loaded
        if (!img.complete) {
            const originalSrc = img.src;
            img.src = '';
            img.src = originalSrc;
        }
    });
    
    // Preload images that might be needed soon
    // Use window.IMG_URL if available (set by PHP), otherwise construct from base path
    const preloadImages = [];
    
    // Debug logging
    console.log('Preload images - IMG_URL:', window.IMG_URL, 'APP_BASE_PATH:', window.APP_BASE_PATH, 'location:', window.location.href);
    
    // Get the image URL - prefer window.IMG_URL, fallback to construction
    let imgBaseUrl = '';
    if (window.IMG_URL && window.IMG_URL.trim() !== '' && window.IMG_URL !== 'undefined') {
        imgBaseUrl = window.IMG_URL;
        console.log('Using IMG_URL from PHP:', imgBaseUrl);
    } else {
        // Fallback: construct path manually
        console.warn('window.IMG_URL not available, constructing path manually');
        const isProduction = window.location.hostname === 'akashaircon.com' || 
                             window.location.hostname === 'www.akashaircon.com';
        let basePath = window.APP_BASE_PATH || '';
        
        if (!basePath && !isProduction) {
            const pathname = window.location.pathname;
            if (pathname.startsWith('/public_html') || pathname.includes('/public_html/')) {
                basePath = '/public_html';
            } else if (pathname.startsWith('/ac') || pathname.includes('/ac/')) {
                basePath = '/ac';
            }
            if (!basePath && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
                const segments = window.location.pathname.split('/').filter(s => s);
                if (segments.length > 0 && segments[0] !== 'public' && segments[0] !== 'admin' && segments[0] !== 'user' && segments[0] !== 'api') {
                    basePath = '/' + segments[0];
                }
            }
        }
        
        // Construct full URL
        const protocol = window.location.protocol;
        const host = window.location.host;
        imgBaseUrl = protocol + '//' + host + basePath + '/public/img';
        console.log('Constructed image base URL:', imgBaseUrl);
    }
    
    // Ensure we have a valid URL
    if (imgBaseUrl) {
        const placeholderUrl = imgBaseUrl + '/placeholder-product.png';
        const noImageUrl = imgBaseUrl + '/no-image.png';
        console.log('Preloading images:', placeholderUrl, noImageUrl);
        preloadImages.push(placeholderUrl);
        preloadImages.push(noImageUrl);
        
        preloadImages.forEach(src => {
            const img = new Image();
            img.onload = function() {
                console.log('Preloaded image:', this.src);
            };
            img.onerror = function() {
                console.error('Failed to preload image:', this.src);
            };
            img.src = src;
        });
    } else {
        console.error('Could not determine image base URL for preloading');
    }
}

// ================= CAROUSEL INITIALIZATION ================= //
function initializeCarousel() {
    // Preload all carousel images immediately
    function preloadCarouselImages() {
        const carouselElement = document.getElementById('heroCarousel');
        if (!carouselElement) {
            return Promise.resolve();
        }
        
        const carouselImages = carouselElement.querySelectorAll('.carousel-item img');
        const imagePromises = [];
        
        carouselImages.forEach((img, index) => {
            // Force immediate loading of all carousel images
            if (!img.complete) {
                const imgPreload = new Image();
                imgPreload.src = img.src;
                
                const promise = new Promise((resolve) => {
                    imgPreload.onload = () => {
                        console.log(`Carousel image ${index + 1} preloaded:`, img.src);
                        resolve();
                    };
                    imgPreload.onerror = () => {
                        console.warn(`Carousel image ${index + 1} failed to preload:`, img.src);
                        resolve(); // Continue even if one image fails
                    };
                });
                
                imagePromises.push(promise);
                
                // Also trigger the actual img element to load
                if (img.loading !== 'eager') {
                    img.loading = 'eager';
                }
                // Force reload if not loading
                const currentSrc = img.src;
                img.src = '';
                img.src = currentSrc;
            } else {
                console.log(`Carousel image ${index + 1} already loaded:`, img.src);
                imagePromises.push(Promise.resolve());
            }
        });
        
        return imagePromises.length > 0 ? Promise.all(imagePromises) : Promise.resolve();
    }
    
    // Start preloading images immediately (don't wait for Bootstrap)
    const preloadPromise = preloadCarouselImages();
    
    // Wait for Bootstrap to be available
    function waitForBootstrap() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
            const carouselElement = document.getElementById('heroCarousel');
            if (carouselElement) {
                try {
                    // Wait for images to preload (with timeout), then initialize carousel
                    Promise.race([
                        preloadPromise,
                        new Promise(resolve => setTimeout(resolve, 2000)) // Max 2 second wait
                    ]).then(() => {
                        // Destroy any existing carousel instance
                        const existingCarousel = bootstrap.Carousel.getInstance(carouselElement);
                        if (existingCarousel) {
                            existingCarousel.dispose();
                        }
                        
                        // Initialize new carousel
                        const carousel = new bootstrap.Carousel(carouselElement, {
                            interval: 5000,
                            ride: 'carousel',
                            wrap: true,
                            touch: true
                        });
                        
                        // Ensure carousel starts properly
                        carousel.cycle();
                        
                        console.log('Carousel initialized successfully');
                    }).catch(error => {
                        console.error('Error preloading carousel images:', error);
                        // Initialize carousel anyway
                        try {
                            const existingCarousel = bootstrap.Carousel.getInstance(carouselElement);
                            if (existingCarousel) {
                                existingCarousel.dispose();
                            }
                            
                            const carousel = new bootstrap.Carousel(carouselElement, {
                                interval: 5000,
                                ride: 'carousel',
                                wrap: true,
                                touch: true
                            });
                            
                            carousel.cycle();
                            console.log('Carousel initialized (some images may still be loading)');
                        } catch (initError) {
                            console.error('Carousel initialization failed:', initError);
                        }
                    });
                } catch (error) {
                    console.error('Carousel initialization failed:', error);
                }
            }
        } else {
            // Retry after a short delay
            setTimeout(waitForBootstrap, 100);
        }
    }
    
    // Start waiting for Bootstrap
    waitForBootstrap();
}

// Initialize homepage enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Don't interfere with existing loader on homepage
    // The loader is now handled directly in index.php
    
    // Wait a bit for window.IMG_URL to be set if it's not already
    if (!window.IMG_URL && typeof window.IMG_URL === 'undefined') {
        console.warn('window.IMG_URL not set, waiting...');
        setTimeout(function() {
            if (window.IMG_URL) {
                console.log('window.IMG_URL now available:', window.IMG_URL);
                initializeImageLoading();
            } else {
                console.error('window.IMG_URL still not available after wait');
                initializeImageLoading(); // Try anyway with fallback
            }
        }, 100);
    } else {
        initializeImageLoading();
    }
    
    initializeCarousel();
    initializeLazyLoading();
    initializeFAQ();
    initializeScrollProgress();
    initializeExitIntent();
    initializeMicroInteractions();
    initializeFloatingContact();
});

// ================= LAZY LOADING ================= //
function initializeLazyLoading() {
    // Add lazy loading to images below the fold
    const images = document.querySelectorAll('img:not([loading="eager"])');
    
    // Track loading images to prevent concurrent request issues
    const loadingImages = new Set();
    const maxConcurrent = 4; // Limit concurrent image loads
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    // Skip if already loading or loaded
                    if (loadingImages.has(img) || img.complete) {
                        return;
                    }
                    
                    // Limit concurrent loads
                    if (loadingImages.size >= maxConcurrent) {
                        // Wait a bit and retry
                        setTimeout(() => {
                            if (!loadingImages.has(img) && !img.complete) {
                                loadImage(img);
                            }
                        }, 100);
                        return;
                    }
                    
                    loadImage(img);
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.1
        });

        function loadImage(img) {
            loadingImages.add(img);
            img.classList.add('lazy-image');
            
            // Load image
            const loadHandler = function() {
                this.classList.add('loaded');
                this.classList.remove('lazy-image');
                loadingImages.delete(this);
                this.removeEventListener('load', loadHandler);
                this.removeEventListener('error', errorHandler);
            };
            
            // Handle error with retry logic
            const errorHandler = function() {
                loadingImages.delete(this);
                this.classList.remove('lazy-image');
                
                // Use data-fallback if available, otherwise construct fallback URL
                if (this.dataset.fallback) {
                    this.src = this.dataset.fallback;
                } else {
                    // Use window.IMG_URL if available (set by PHP), otherwise construct from base path
                    let fallbackUrl = '';
                    if (window.IMG_URL && window.IMG_URL.trim() !== '' && window.IMG_URL !== 'undefined') {
                        fallbackUrl = window.IMG_URL + '/placeholder-product.png';
                    } else {
                        // Fallback: construct full URL
                        const isProduction = window.location.hostname === 'akashaircon.com' || 
                                             window.location.hostname === 'www.akashaircon.com';
                        let basePath = window.APP_BASE_PATH || '';
                        if (!basePath && !isProduction) {
                            const pathname = window.location.pathname;
                            if (pathname.startsWith('/public_html') || pathname.includes('/public_html/')) {
                                basePath = '/public_html';
                            } else if (pathname.startsWith('/ac') || pathname.includes('/ac/')) {
                                basePath = '/ac';
                            }
                            if (!basePath && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
                                const segments = window.location.pathname.split('/').filter(s => s);
                                if (segments.length > 0 && segments[0] !== 'public' && segments[0] !== 'admin' && segments[0] !== 'user' && segments[0] !== 'api') {
                                    basePath = '/' + segments[0];
                                }
                            }
                        }
                        const protocol = window.location.protocol;
                        const host = window.location.host;
                        fallbackUrl = protocol + '//' + host + basePath + '/public/img/placeholder-product.png';
                    }
                    this.src = fallbackUrl;
                }
                
                // Only mark as loaded if fallback also fails
                this.addEventListener('error', function() {
                    this.classList.add('loaded');
                    this.style.opacity = '0.5';
                    this.style.backgroundColor = '#f5f5f5';
                }, { once: true });
                
                this.removeEventListener('load', loadHandler);
                this.removeEventListener('error', errorHandler);
            };
            
            img.addEventListener('load', loadHandler, { once: true });
            img.addEventListener('error', errorHandler, { once: true });
            
            // Force load if src is already set but not loading
            if (img.src && !img.complete) {
                const currentSrc = img.src;
                img.src = '';
                img.src = currentSrc;
            }
        }

        images.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.classList.add('loaded');
        });
    }
}


// ================= FAQ ACCORDION ================= //
function initializeFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        
        question.addEventListener('click', () => {
            const isActive = question.classList.contains('active');
            
            // Close all other FAQ items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.querySelector('.faq-question').classList.remove('active');
                    otherItem.querySelector('.faq-answer').classList.remove('active');
                }
            });
            
            // Toggle current item
            if (!isActive) {
                question.classList.add('active');
                answer.classList.add('active');
            } else {
                question.classList.remove('active');
                answer.classList.remove('active');
            }
        });
    });
}

// ================= SCROLL PROGRESS ================= //
function initializeScrollProgress() {
    // Create scroll progress bar
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);
    
    // Update progress on scroll
    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        
        progressBar.style.width = scrollPercent + '%';
    });
}


// ================= EXIT INTENT POPUP ================= //
function initializeExitIntent() {
    // Create exit intent popup
    const popupHTML = `
        <div class="exit-intent-popup" id="exitIntentPopup">
            <div class="exit-intent-content">
                <button class="exit-intent-close" onclick="closeExitIntent()">&times;</button>
                <h3 class="exit-intent-title">Wait! Don't Miss Out</h3>
                <p class="exit-intent-desc">
                    Get exclusive deals and updates on our latest AC products and services. 
                    Subscribe to our newsletter and save up to 20% on your next purchase!
                </p>
                <form class="exit-intent-form" onsubmit="handleNewsletterSubmit(event)">
                    <input type="email" placeholder="Enter your email address" required>
                    <button type="submit">Subscribe Now</button>
                </form>
                <p class="small text-muted">No spam, unsubscribe anytime.</p>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', popupHTML);
    
    // Track mouse movement for exit intent
    let mouseY = 0;
    document.addEventListener('mousemove', (e) => {
        mouseY = e.clientY;
    });
    
    document.addEventListener('mouseleave', (e) => {
        if (e.clientY <= 0 && mouseY > 0 && !exitIntentShown) {
            showExitIntent();
        }
    });
}

function showExitIntent() {
    if (exitIntentShown) return;
    
    const popup = document.getElementById('exitIntentPopup');
    popup.classList.add('active');
    exitIntentShown = true;
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
        closeExitIntent();
    }, 10000);
}

function closeExitIntent() {
    const popup = document.getElementById('exitIntentPopup');
    popup.classList.remove('active');
}

function handleNewsletterSubmit(event) {
    event.preventDefault();
    const email = event.target.querySelector('input[type="email"]').value;
    
    // Show loading state
    const button = event.target.querySelector('button');
    const originalText = button.textContent;
    button.textContent = 'Subscribing...';
    button.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        showNotification('Thank you for subscribing! Check your email for confirmation.', 'success');
        closeExitIntent();
        button.textContent = originalText;
        button.disabled = false;
        event.target.reset();
    }, 2000);
}


// ================= MICRO-INTERACTIONS ================= //
function initializeMicroInteractions() {
    // Add micro-interactions to elements
    const interactiveElements = document.querySelectorAll('.product-card, .category-card, .service-card, .feature-card');
    
    interactiveElements.forEach(el => {
        el.classList.add('micro-bounce');
        
        // Add hover effects
        el.addEventListener('mouseenter', () => {
            el.style.transform = 'translateY(-5px)';
        });
        
        el.addEventListener('mouseleave', () => {
            el.style.transform = 'translateY(0)';
        });
    });
    
    // Add glow effect to buttons
    const buttons = document.querySelectorAll('.btn-primary, .carousel-btn-primary, .product-btn-primary');
    buttons.forEach(btn => {
        btn.classList.add('micro-glow');
    });
}


// ================= FLOATING CONTACT ================= //
function initializeFloatingContact() {
    // Create floating contact button
    const contactHTML = `
        <a href="https://wa.me/911234567890?text=Hi, I'm interested in your AC products" 
           class="floating-contact-btn" 
           target="_blank" 
           rel="noopener"
           aria-label="Contact us on WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    `;
    
    document.body.insertAdjacentHTML('beforeend', contactHTML);
}

// ================= UTILITY FUNCTIONS ================= //

function toggleWishlist(productId) {
    // Check if user is logged in by making a test request
    fetch('../user/products/ajax_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'check'
        })
    })
    .then(response => {
        if (response.status === 401) {
            showNotification('Please login to add items to your wishlist', 'error');
            return;
        }
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            const action = data.in_wishlist ? 'remove' : 'add';
            
            // Perform the actual add/remove action
            return fetch('../user/products/ajax_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    action: action
                })
            });
        } else if (data && !data.success) {
            showNotification(data.message || 'Please login to add items to your wishlist', 'error');
            return;
        }
    })
    .then(response => {
        if (response) {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
        }
    })
    .then(data => {
        if (data && data.success) {
            const message = data.action === 'added' ? 'Added to wishlist!' : 'Removed from wishlist!';
            showNotification(message, 'success');
            
            // Update button state
            const button = event.target.closest('.product-action-btn.wishlist');
            if (button) {
                if (data.action === 'added') {
                    button.classList.add('active');
                    button.title = 'Remove from Wishlist';
                } else {
                    button.classList.remove('active');
                    button.title = 'Add to Wishlist';
                }
            }
        } else if (data && !data.success) {
            showNotification(data.message || 'Failed to update wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Wishlist error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function showNotification(message, type = 'info') {
    // Use existing notification system from header.php
    if (window.showNotification) {
        window.showNotification(message, type);
    } else {
        // Fallback notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// ================= ACCESSIBILITY ENHANCEMENTS ================= //
function initializeAccessibility() {
    // Add ARIA labels to interactive elements
    const interactiveElements = document.querySelectorAll('button, a, input, select, textarea');
    
    interactiveElements.forEach(el => {
        if (!el.getAttribute('aria-label') && !el.textContent.trim()) {
            const title = el.getAttribute('title') || el.getAttribute('alt') || 'Interactive element';
            el.setAttribute('aria-label', title);
        }
    });
    
    // Add keyboard navigation for custom elements
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            // Close modals and popups
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            });
            
            closeExitIntent();
        }
    });
}

// Initialize accessibility enhancements
document.addEventListener('DOMContentLoaded', initializeAccessibility);

