</main>
<!-- Footer Section -->
<footer class="modern-footer">
    <div class="footer-top-pattern"></div>
    <div class="container">
        <!-- Main Footer Content -->
        <div class="row g-5">
            <!-- Company Info -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand-section">
                    <div class="footer-logo-wrapper">
                        <img src="<?php echo IMG_URL; ?>/full-logo.png" 
                             alt="Akash Enterprise Logo" 
                             class="footer-logo">
                    </div>
                    <h3 class="footer-brand-name">Akash Enterprise</h3>
                    <p class="footer-description">
                        Your trusted partner for premium air conditioning solutions since 1962. 
                        We provide quality AC sales, installation, and maintenance services with excellence.
                    </p>
                    
                    <!-- Contact Info -->
                    <div class="footer-contact-info">
                        <a href="tel:+911234567890" class="footer-contact-item">
                            <div class="contact-icon-wrapper">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span>+91 98792 35475 /+91 98252 11063</span>
                        </a>
                        <a href="mailto:support@akashent.com" class="footer-contact-item">
                            <div class="contact-icon-wrapper">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <span>aakashjamnagar@gmail.com</span>
                        </a>
                        <div class="footer-contact-item">
                            <div class="contact-icon-wrapper">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <span>Jamnagar, Gujarat-361008, India</span>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="footer-social-links">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-column">
                    <h4 class="footer-column-title">
                        <span class="title-icon"><i class="fas fa-link"></i></span>
                        Quick Links
                    </h4>
                    <ul class="footer-link-list">
                        <li><a href="<?php echo BASE_URL; ?>/index.php" class="footer-link">Home</a></li>
                        <li><a href="<?php echo PUBLIC_URL; ?>/pages/about.php" class="footer-link">About Us</a></li>
                        <li><a href="<?php echo USER_URL; ?>/products/" class="footer-link">Products</a></li>
                        <li><a href="<?php echo USER_URL; ?>/services/" class="footer-link">Services</a></li>
                        <li><a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="footer-link">Contact</a></li>
                    </ul>
                </div>
            </div>

            <!-- Product Categories -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-column">
                    <h4 class="footer-column-title">
                        <span class="title-icon"><i class="fas fa-th-large"></i></span>
                        Products
                    </h4>
                    <ul class="footer-link-list">
                        <li><a href="<?php echo USER_URL; ?>/products/?category=1" class="footer-link">Residential AC</a></li>
                        <li><a href="<?php echo USER_URL; ?>/products/?category=2" class="footer-link">Commercial AC</a></li>
                        <li><a href="<?php echo USER_URL; ?>/products/?category=3" class="footer-link">Cassette AC</a></li>
                        <li><a href="<?php echo USER_URL; ?>/products/?filter=trending" class="footer-link">Trending Products</a></li>
                        <li><a href="<?php echo USER_URL; ?>/products/?filter=featured" class="footer-link">Featured Products</a></li>
                    </ul>
                </div>
            </div>

            <!-- Services & Support -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-column">
                    <h4 class="footer-column-title">
                        <span class="title-icon"><i class="fas fa-tools"></i></span>
                        Services
                    </h4>
                    <ul class="footer-link-list">
                        <li><a href="<?php echo USER_URL; ?>/services/" class="footer-link">Installation</a></li>
                        <li><a href="<?php echo USER_URL; ?>/services/" class="footer-link">Maintenance</a></li>
                        <li><a href="<?php echo USER_URL; ?>/services/" class="footer-link">AMC Plans</a></li>
                        <li><a href="<?php echo USER_URL; ?>/services/" class="footer-link">Repair & Service</a></li>
                        <li><a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="footer-link">24/7 Support</a></li>
                    </ul>
                </div>
            </div>

            <!-- Newsletter -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-column">
                    <h4 class="footer-column-title">
                        <span class="title-icon"><i class="fas fa-bell"></i></span>
                        Newsletter
                    </h4>
                    <p class="newsletter-description">Subscribe to get updates on new products and exclusive offers!</p>
                    <form id="newsletter-form" class="footer-newsletter-form">
                        <div class="newsletter-input-wrapper">
                            <input type="email" 
                                   class="newsletter-input" 
                                   placeholder="Enter your email" 
                                   required 
                                   id="newsletter-email" 
                                   name="email" 
                                   autocomplete="email">
                            <button type="submit" class="newsletter-submit-btn" aria-label="Subscribe">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="newsletter-message" class="newsletter-message"></div>
                    </form>
                    <div class="footer-badges">
                        <div class="footer-badge-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure & Trusted</span>
                        </div>
                        <div class="footer-badge-item">
                            <i class="fas fa-award"></i>
                            <span>ISO Certified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-lg-6 col-md-12 text-center text-md-start">
                    <p class="footer-copyright">
                        &copy; <?= date('Y'); ?> <strong>Akash Enterprise</strong>. All rights reserved.
                    </p>
                </div>
                <div class="col-lg-6 col-md-12 text-center text-md-end">
                    <div class="footer-legal-links">
                        <a href="<?php echo BASE_URL; ?>/public/pages/terms.php" class="legal-link">Terms & Conditions</a>
                        <span class="legal-separator">|</span>
                        <a href="<?php echo BASE_URL; ?>/public/pages/privacy.php" class="legal-link">Privacy Policy</a>
                        <span class="legal-separator">|</span>
<span class="designer-credit" style="color:#fff;">
                            Design by <strong>
                                <a href="https://hexadigital.tech" target="_blank" rel="noopener" style="color:#fff; text-decoration:underline;">
                                    hexadigital.tech
                                </a>
                            </strong>
                        </span>                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

    <!-- JavaScript Libraries -->
    <script src="<?php echo JS_URL; ?>/bootstrap.bundle.min.js?v=<?php echo APP_VERSION; ?>"></script>
    <script>
        // Disable source map warnings and other console noise
        if (typeof console !== 'undefined' && console.warn) {
            const originalWarn = console.warn;
            console.warn = function(message) {
                if (typeof message === 'string' && (
                    message.includes('source map') || 
                    message.includes('DevTools') ||
                    message.includes('404')
                )) {
                    return; // Suppress common warnings
                }
                originalWarn.apply(console, arguments);
            };
        }
        
        // Suppress Bootstrap source map errors
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('source map')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
    <script src="<?php echo JS_URL; ?>/loader-scroller.js?v=<?php echo APP_VERSION; ?>"></script>
    <!-- Font Awesome is loaded via CDN in head, no need for local JS -->
    
    <!-- Custom JavaScript -->
    <script>
    // Enhanced footer functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure carousel is initialized after Bootstrap loads
        setTimeout(function() {
            const carouselElement = document.getElementById('heroCarousel');
            if (carouselElement && typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
                // Check if carousel is already initialized
                const existingCarousel = bootstrap.Carousel.getInstance(carouselElement);
                if (!existingCarousel) {
                    try {
                        const carousel = new bootstrap.Carousel(carouselElement, {
                            interval: 5000,
                            ride: 'carousel',
                            wrap: true,
                            touch: true
                        });
                        carousel.cycle();
                        console.log('Carousel fallback initialization successful');
                    } catch (error) {
                        console.error('Carousel fallback initialization failed:', error);
                    }
                }
            }
        }, 500); // Wait 500ms after DOM is ready
        // Newsletter form submission
        const newsletterForm = document.getElementById('newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                
                // Show loading state
                const submitBtn = this.querySelector('button');
                const originalContent = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                submitBtn.disabled = true;
                
                // Simulate API call (replace with actual implementation)
                setTimeout(() => {
                    showNotification('Thank you for subscribing to our newsletter!', 'success');
                    this.reset();
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }, 2000);
            });
        }

        // Add hover effects to footer links
        document.querySelectorAll('footer a').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.color = '#3b82f6';
                this.style.transform = 'translateX(5px)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.color = '';
                this.style.transform = '';
            });
        });

        // Social media link tracking (you can implement analytics here)
        document.querySelectorAll('.social-links a').forEach(link => {
            link.addEventListener('click', function(e) {
                const platform = this.getAttribute('aria-label');
                console.log(`Social media click: ${platform}`);
                // Implement analytics tracking here
            });
        });

        // Smooth scroll for footer links
        document.querySelectorAll('footer a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });

    // Newsletter subscription
    document.addEventListener('DOMContentLoaded', function() {
        const newsletterForm = document.getElementById('newsletter-form');
        const newsletterMessage = document.getElementById('newsletter-message');
        
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = document.getElementById('newsletter-email').value;
                
                if (!email) {
                    showNewsletterMessage('Please enter your email address.', 'error');
                    return;
                }
                
                // Disable form
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Subscribing...';
                submitBtn.disabled = true;
                
                // Send request
                fetch('newsletter_subscribe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=subscribe&email=' + encodeURIComponent(email)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNewsletterMessage(data.message, 'success');
                        newsletterForm.reset();
                    } else {
                        showNewsletterMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNewsletterMessage('An error occurred. Please try again.', 'error');
                    console.error('Newsletter subscription error:', error);
                })
                .finally(() => {
                    // Re-enable form
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        }
        
        function showNewsletterMessage(message, type) {
            if (newsletterMessage) {
                newsletterMessage.innerHTML = message;
                newsletterMessage.className = 'mt-2 small text-' + (type === 'success' ? 'success' : 'danger');
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    newsletterMessage.innerHTML = '';
                    newsletterMessage.className = 'mt-2 small';
                }, 5000);
            }
        }
    });

    // Performance optimization: Lazy load images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    </script>
</body>
</html>
