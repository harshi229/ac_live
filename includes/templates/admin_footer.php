            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo JS_URL; ?>/bootstrap.bundle.min.js?v=<?php echo APP_VERSION; ?>"></script>
    
    <!-- Custom Admin JS -->
    <script>
        // Ensure proper layout initialization
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const layout = document.querySelector('.admin-layout');

            if (sidebar) {
                sidebar.style.cssText += ';position: fixed !important; left: 0 !important; top: 0 !important; width: 280px !important; z-index: 1030 !important;';
            }

            if (layout) {
                layout.style.cssText += ';width: 100% !important; max-width: 100vw !important; margin: 0 !important; padding: 0 !important;';
            }
        });
        
        // Calculate scrollbar width to prevent layout shift
        function calculateScrollbarWidth() {
            const outer = document.createElement('div');
            outer.style.visibility = 'hidden';
            outer.style.overflow = 'scroll';
            outer.style.msOverflowStyle = 'scrollbar';
            document.body.appendChild(outer);
            
            const inner = document.createElement('div');
            outer.appendChild(inner);
            
            const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
            outer.parentNode.removeChild(outer);
            
            // Apply consistent padding to prevent layout shift
            if (scrollbarWidth > 0) {
                document.body.classList.add('has-scrollbar');
                document.body.style.paddingRight = scrollbarWidth + 'px';
            }
            
            return scrollbarWidth;
        }
        
        // Ensure scrollbar visibility is consistent
        document.documentElement.style.overflow = 'scroll';
        
        // Set scrollbar width on load and resize
        calculateScrollbarWidth();
        window.addEventListener('resize', calculateScrollbarWidth);
        
        // Enhanced Sidebar Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
            const sidebar = document.getElementById('adminSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const main = document.getElementById('adminMain');

            // Mobile sidebar toggle
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('show');
                    }
                });
            }

            // Close sidebar when clicking overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }

            // Close sidebar on window resize if mobile
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    sidebar.classList.remove('show');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('show');
                    }
                }
            });

            // Auto-close sidebar on mobile when clicking nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        sidebar.classList.remove('show');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('show');
                        }
                    }
                });
            });

            // Sidebar collapse functionality
            if (sidebarCollapseBtn && sidebar) {
                sidebarCollapseBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('sidebar-collapsed');

                    // Save collapsed state
                    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebar-collapsed', isCollapsed);
                    
                    // Update main content styling and add class for CSS specificity
                    if (main) {
                        if (isCollapsed) {
                            main.classList.add('sidebar-collapsed-main');
                            main.style.setProperty('margin-left', '80px', 'important');
                            main.style.setProperty('width', 'calc(100% - 80px)', 'important');
                            main.style.setProperty('max-width', 'calc(100% - 80px)', 'important');
                            main.style.setProperty('min-width', 'calc(100% - 80px)', 'important');
                        } else {
                            main.classList.remove('sidebar-collapsed-main');
                            main.style.setProperty('margin-left', '280px', 'important');
                            main.style.setProperty('width', 'calc(100% - 280px)', 'important');
                            main.style.setProperty('max-width', 'calc(100% - 280px)', 'important');
                            main.style.setProperty('min-width', 'calc(100% - 280px)', 'important');
                        }
                    }
                });

                // Load saved collapsed state
                const savedCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                if (savedCollapsed) {
                    sidebar.classList.add('sidebar-collapsed');
                    if (main) {
                        main.classList.add('sidebar-collapsed-main');
                        main.style.setProperty('margin-left', '80px', 'important');
                        main.style.setProperty('width', 'calc(100% - 80px)', 'important');
                        main.style.setProperty('max-width', 'calc(100% - 80px)', 'important');
                        main.style.setProperty('min-width', 'calc(100% - 80px)', 'important');
                    }
                }
            }

            // Collapsible navigation sections
            const sectionToggleBtns = document.querySelectorAll('.section-toggle-btn');
            sectionToggleBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const section = this.dataset.target;
                    const content = document.querySelector(`.nav-section-content[data-section="${section}"]`);

                    if (content) {
                        const isExpanded = content.classList.contains('show');

                        // Toggle current section
                        if (isExpanded) {
                            content.classList.remove('show');
                            this.querySelector('i').className = 'fas fa-chevron-down';
                        } else {
                            content.classList.add('show');
                            this.querySelector('i').className = 'fas fa-chevron-up';
                        }

                        // Save section state
                        const expandedSections = JSON.parse(localStorage.getItem('expanded-sections') || '{}');
                        expandedSections[section] = !isExpanded;
                        localStorage.setItem('expanded-sections', JSON.stringify(expandedSections));
                    }
                });
            });

            // Load saved section states
            const savedSections = JSON.parse(localStorage.getItem('expanded-sections') || '{}');
            Object.keys(savedSections).forEach(section => {
                const content = document.querySelector(`.nav-section-content[data-section="${section}"]`);
                const btn = document.querySelector(`[data-target="${section}"]`);

                if (content && btn) {
                    if (savedSections[section]) {
                        content.classList.add('show');
                        btn.querySelector('i').className = 'fas fa-chevron-up';
                    } else {
                        content.classList.remove('show');
                        btn.querySelector('i').className = 'fas fa-chevron-down';
                    }
                }
            });

            // Table responsive functionality
            const tableResponsive = document.querySelector('.table-responsive');
            if (tableResponsive) {
                // Check if table is scrollable
                function checkScrollable() {
                    if (tableResponsive.scrollWidth > tableResponsive.clientWidth) {
                        tableResponsive.classList.add('scrollable');
                    } else {
                        tableResponsive.classList.remove('scrollable');
                    }
                }
                
                // Check on load and resize
                checkScrollable();
                window.addEventListener('resize', checkScrollable);
                
                // Add scroll event listener
                tableResponsive.addEventListener('scroll', function() {
                    const scrollLeft = this.scrollLeft;
                    const maxScroll = this.scrollWidth - this.clientWidth;
                    
                    // Add/remove scroll indicators
                    if (scrollLeft > 0) {
                        this.classList.add('scrolled-left');
                    } else {
                        this.classList.remove('scrolled-left');
                    }
                    
                    if (scrollLeft < maxScroll) {
                        this.classList.add('scrolled-right');
                    } else {
                        this.classList.remove('scrolled-right');
                    }
                });
            }

            // Enhanced nav link hover effects
            navLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('active')) {
                        const icon = this.querySelector('.nav-link-icon');
                        if (icon) {
                            icon.style.background = 'rgba(99, 102, 241, 0.2)';
                            icon.style.transform = 'scale(1.1)';
                        }
                    }
                });

                link.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('active')) {
                        const icon = this.querySelector('.nav-link-icon');
                        if (icon) {
                            icon.style.background = 'rgba(255, 255, 255, 0.1)';
                            icon.style.transform = 'scale(1)';
                        }
                    }
                });
            });
        });
        
        // Enhanced Theme Toggle
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            // Add transition class for smooth theme switching
            body.classList.add('theme-transition');
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);

            // Update theme toggle icon
            const themeToggleIcon = document.querySelector('#themeToggle i');
            if (themeToggleIcon) {
                themeToggleIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }

            // Remove transition class after animation
            setTimeout(() => {
                body.classList.remove('theme-transition');
            }, 300);

            // Trigger custom event for theme change
            document.dispatchEvent(new CustomEvent('themeChanged', {
                detail: { theme: newTheme }
            }));
        }

        // Load saved theme with smooth transition
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            const body = document.body;

            // Set initial theme without transition
            body.setAttribute('data-theme', savedTheme);

            // Update theme toggle icon
            const themeToggleIcon = document.querySelector('#themeToggle i');
            if (themeToggleIcon) {
                themeToggleIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }

            // Add theme transition styles
            const style = document.createElement('style');
            style.textContent = `
                .theme-transition * {
                    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease !important;
                }
            `;
            document.head.appendChild(style);
        });

        // Quick Actions functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add Product button
            document.querySelectorAll('.quick-action-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (icon.classList.contains('fa-plus')) {
                        // Add product
                        window.location.href = '<?php echo admin_url('products/add.php'); ?>';
                    } else if (icon.classList.contains('fa-download')) {
                        // Export data - trigger download
                        exportData();
                    } else if (icon.classList.contains('fa-sync-alt')) {
                        // Refresh - add loading state
                        refreshData();
                    }
                });
            });
        });

        function exportData() {
            // Show loading state
            const exportBtn = document.querySelector('.quick-action-btn i.fa-download').parentElement;
            const originalIcon = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            exportBtn.disabled = true;

            // Simulate export process
            setTimeout(() => {
                exportBtn.innerHTML = originalIcon;
                exportBtn.disabled = false;

                // Trigger download (replace with actual implementation)
                const link = document.createElement('a');
                link.href = '#'; // Replace with actual export URL
                link.download = 'admin-data-export.csv';
                link.click();

                showToast('Data exported successfully!', 'success');
            }, 2000);
        }

        function refreshData() {
            // Show loading state
            const refreshBtn = document.querySelector('.quick-action-btn i.fa-sync-alt').parentElement;
            const originalIcon = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;

            // Simulate refresh process
            setTimeout(() => {
                refreshBtn.innerHTML = originalIcon;
                refreshBtn.disabled = false;

                // Trigger page refresh or data reload
                if (window.updateDashboardStats) {
                    updateDashboardStats();
                }

                showToast('Data refreshed successfully!', 'success');
            }, 1500);
        }

        
        // Enhanced Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('headerSearch');
            const searchSuggestions = document.getElementById('searchSuggestions');
            const searchLoading = document.querySelector('.search-loading');

            if (searchInput && searchSuggestions) {
                let searchTimeout;

                searchInput.addEventListener('input', function(e) {
                    const searchTerm = this.value.trim();

                    // Clear previous timeout
                    clearTimeout(searchTimeout);

                    // Hide suggestions if empty
                    if (!searchTerm) {
                        searchSuggestions.style.display = 'none';
                        return;
                    }

                    // Show loading state
                    searchSuggestions.style.display = 'block';
                    if (searchLoading) {
                        searchLoading.style.display = 'flex';
                    }

                    // Set new timeout for search
                    searchTimeout = setTimeout(() => {
                        performSearch(searchTerm);
                    }, 300);
                });

                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const searchTerm = this.value.trim();
                        if (searchTerm) {
                            // Redirect to search results or perform search
                            window.location.href = `<?php echo admin_url('products/search.php'); ?>?q=${encodeURIComponent(searchTerm)}`;
                        }
                    }
                });

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                        searchSuggestions.style.display = 'none';
                    }
                });
            }

            function performSearch(term) {
                if (term.length < 2) {
                    searchSuggestions.style.display = 'none';
                    return;
                }

                // Show loading state
                const searchLoading = searchSuggestions.querySelector('.search-loading');
                if (searchLoading) {
                    searchLoading.style.display = 'block';
                }
                searchSuggestions.style.display = 'block';

                // Fetch real search results from database
                fetch('<?php echo admin_url('search'); ?>?q=' + encodeURIComponent(term))
                    .then(response => response.json())
                    .then(data => {
                        displaySearchResults(data.results || []);
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        displaySearchResults([]);
                    });
            }

            function displaySearchResults(results) {
                if (!searchSuggestions) return;

                const searchLoading = searchSuggestions.querySelector('.search-loading');
                if (searchLoading) {
                    searchLoading.style.display = 'none';
                }

                if (results.length === 0) {
                    searchSuggestions.innerHTML = `
                        <div class="search-no-results" style="padding: var(--spacing-lg); text-align: center; color: var(--text-muted);">
                            <i class="fas fa-search" style="font-size: 1.5rem; margin-bottom: var(--spacing-sm); opacity: 0.5;"></i>
                            <div>No results found</div>
                        </div>
                    `;
                    return;
                }

                const resultsHtml = results.map(result => `
                    <a href="${result.url}" class="search-result-item" style="
                        display: flex;
                        align-items: center;
                        gap: var(--spacing-md);
                        padding: var(--spacing-md) var(--spacing-lg);
                        color: var(--text-primary);
                        text-decoration: none;
                        border-bottom: 1px solid var(--border-light);
                        transition: background-color var(--transition-fast);
                    ">
                        <div class="search-result-icon" style="
                            width: 32px;
                            height: 32px;
                            border-radius: var(--radius-md);
                            background: var(--primary-gradient);
                            color: var(--text-white);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: var(--text-xs);
                            flex-shrink: 0;
                        ">
                            <i class="fas fa-${result.type === 'product' ? 'box' : result.type === 'order' ? 'shopping-cart' : 'user'}"></i>
                        </div>
                        <div class="search-result-content" style="flex: 1; min-width: 0;">
                            <div class="search-result-title" style="font-weight: 600; margin-bottom: 2px;">${result.title}</div>
                            <div class="search-result-type" style="font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">${result.type}</div>
                        </div>
                    </a>
                `).join('');

                searchSuggestions.innerHTML = resultsHtml;

                // Add hover effects
                searchSuggestions.querySelectorAll('.search-result-item').forEach(item => {
                    item.addEventListener('mouseenter', () => {
                        item.style.backgroundColor = 'var(--bg-tertiary)';
                    });
                    item.addEventListener('mouseleave', () => {
                        item.style.backgroundColor = 'transparent';
                    });
                });
            }
        });
        
        // Enhanced Notification handling
        document.addEventListener('DOMContentLoaded', function() {
            // Mark individual notifications as read
            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-mark-read')) {
                    e.preventDefault();
                    const btn = e.target.closest('.btn-mark-read');
                    const notificationItem = btn.closest('.notification-item');
                    const notificationId = notificationItem.dataset.notificationId;

                    markNotificationAsRead(notificationId);
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');

                    // Update badge count
                    updateNotificationBadge();
                }
            });

            // Mark all notifications as read
            document.addEventListener('click', function(e) {
                if (e.target.closest('.mark-all-read')) {
                    e.preventDefault();
                    const unreadNotifications = document.querySelectorAll('.notification-item.unread');

                    unreadNotifications.forEach(item => {
                        const notificationId = item.dataset.notificationId;
                        markNotificationAsRead(notificationId);
                        item.classList.remove('unread');
                        item.classList.add('read');
                    });

                    updateNotificationBadge();
                }
            });

            // Update notification badge count
            function updateNotificationBadge() {
                const unreadCount = document.querySelectorAll('.notification-item.unread').length;
                const badge = document.querySelector('.notification-badge');

                if (badge) {
                    badge.textContent = unreadCount;
                    if (unreadCount === 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = 'inline-flex';
                    }
                }
            }
        });

        function markNotificationAsRead(notificationId) {
            // Implement actual notification read functionality here
            console.log('Marking notification as read:', notificationId);

            // Example AJAX call (replace with actual implementation)
            /*
            fetch('/api/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Notification marked as read successfully');
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
            */
        }
        
        // Real-time updates (Future implementation)
        function startRealTimeUpdates() {
            // Implement real-time updates for dashboard
            setInterval(function() {
                // Update dashboard data
                updateDashboardStats();
            }, 30000); // Update every 30 seconds
        }
        
        function updateDashboardStats() {
            // Implement dashboard stats update
            console.log('Updating dashboard stats...');
        }
        
        // Initialize real-time updates
        document.addEventListener('DOMContentLoaded', function() {
            startRealTimeUpdates();
        });

        // Back to top button functionality
        let mybutton = document.getElementById("btn-back-to-top");
        if (mybutton) {
        // When the user scrolls down 20px from the top of the document, show the button
        window.onscroll = function () {
            scrollFunction();
        };

        function scrollFunction() {
            if (
                document.body.scrollTop > 20 ||
                document.documentElement.scrollTop > 20
            ) {
                mybutton.classList.add("show");
            } else {
                mybutton.classList.remove("show");
            }
        }

        // When the user clicks on the button, scroll to the top of the document
        mybutton.addEventListener("click", backToTop);

        function backToTop() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
            }
        }

        // Enhanced Mobile and Touch Functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize mobile features
            initializeMobileFeatures();

            // Add touch feedback to interactive elements
            addTouchFeedback();

            // Initialize swipe gestures
            initializeSwipeGestures();

            // Handle orientation changes
            handleOrientationChange();

            // Initialize pull-to-refresh
            initializePullToRefresh();
        });

        function initializeMobileFeatures() {
            // Only run on mobile devices
            if (window.innerWidth <= 768) {
                // Add mobile-specific classes
                document.body.classList.add('mobile-device');

                // Optimize performance for mobile
                optimizeMobilePerformance();

                // Add mobile navigation improvements
                enhanceMobileNavigation();

                // Initialize touch-friendly interactions
                initializeTouchInteractions();
            }
        }

        function optimizeMobilePerformance() {
            // Reduce animations on mobile for better performance
            const style = document.createElement('style');
            style.textContent = `
                .mobile-device * {
                    animation-duration: 0.2s !important;
                    transition-duration: 0.2s !important;
                }

                .mobile-device .stat-card::after,
                .mobile-device .pulse {
                    animation: none !important;
                }
            `;
            document.head.appendChild(style);
        }

        function enhanceMobileNavigation() {
            // Add swipe area for sidebar
            const sidebar = document.getElementById('adminSidebar');
            const main = document.getElementById('adminMain');

            if (sidebar && main) {
                // Add swipe area to main content
                const swipeArea = document.createElement('div');
                swipeArea.className = 'swipe-area';
                swipeArea.addEventListener('touchstart', handleSwipeStart);
                swipeArea.addEventListener('touchmove', handleSwipeMove);
                swipeArea.addEventListener('touchend', handleSwipeEnd);

                main.appendChild(swipeArea);

                // Variables for swipe detection
                let startX = 0;
                let currentX = 0;
                let isSwipeInProgress = false;

                function handleSwipeStart(e) {
                    startX = e.touches[0].clientX;
                    isSwipeInProgress = true;
                }

                function handleSwipeMove(e) {
                    if (!isSwipeInProgress) return;

                    currentX = e.touches[0].clientX;
                    const diffX = currentX - startX;

                    // Only handle right-to-left swipes
                    if (diffX < -50 && window.innerWidth <= 768) {
                        sidebar.classList.add('show');
                        const overlay = document.getElementById('sidebarOverlay');
                        if (overlay) overlay.classList.add('show');
                    }
                }

                function handleSwipeEnd() {
                    isSwipeInProgress = false;
                }
            }
        }

        function initializeTouchInteractions() {
            // Add touch feedback to buttons and interactive elements
            const interactiveElements = document.querySelectorAll('.btn, .nav-link, .btn-table-action, .quick-action-btn');

            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.classList.add('touch-feedback');
                });

                element.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.classList.remove('touch-feedback');
                    }, 200);
                });
            });

            // Improve dropdown interactions on mobile
            const dropdownBtns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            dropdownBtns.forEach(btn => {
                btn.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    // Let Bootstrap handle the dropdown
                });
            });
        }

        function initializeSwipeGestures() {
            // Horizontal swipe for navigation (future enhancement)
            let touchStartX = 0;
            let touchEndX = 0;

            document.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            });

            document.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipeGesture();
            });

            function handleSwipeGesture() {
                const swipeThreshold = 50;
                const swipeLength = Math.abs(touchEndX - touchStartX);

                if (swipeLength > swipeThreshold) {
                    // Handle swipe gestures here if needed
                    // For example, swipe left/right to navigate between pages
                }
            }
        }

        function handleOrientationChange() {
            window.addEventListener('orientationchange', function() {
                // Adjust layout for orientation changes
                setTimeout(() => {
                    // Force layout recalculation
                    const main = document.getElementById('adminMain');
                    if (main) {
                        main.style.display = 'none';
                        main.offsetHeight; // Trigger reflow
                        main.style.display = '';
                    }

                    // Update charts if they exist
                    const charts = Chart.instances;
                    Object.values(charts).forEach(chart => {
                        chart.resize();
                    });
                }, 100);
            });
        }

        function initializePullToRefresh() {
            const mainContent = document.getElementById('adminMain');
            if (!mainContent) return;

            let pullStartY = 0;
            let pullCurrentY = 0;
            let isPulling = false;
            let pullThreshold = 80;

            // Add pull indicator
            const pullIndicator = document.createElement('div');
            pullIndicator.className = 'pull-to-refresh';
            pullIndicator.innerHTML = `
                <i class="fas fa-arrow-down"></i>
                <span>Pull to refresh</span>
            `;

            // Add styles for pull indicator
            const style = document.createElement('style');
            style.textContent = `
                .pull-to-refresh {
                    position: absolute;
                    top: -60px;
                    left: 0;
                    right: 0;
                    background: var(--primary-color);
                    color: var(--text-white);
                    text-align: center;
                    padding: var(--spacing-sm);
                    font-size: var(--text-sm);
                    border-radius: var(--radius-md);
                    opacity: 0;
                    transition: all 0.3s ease;
                    z-index: 1000;
                }

                .pull-to-refresh.visible {
                    opacity: 1;
                    top: 0;
                }

                .pull-to-refresh.refreshing {
                    background: var(--success-color);
                }

                .pull-to-refresh i {
                    margin-right: var(--spacing-xs);
                    transition: transform 0.3s ease;
                }

                .pull-to-refresh.refreshing i {
                    transform: rotate(360deg);
                }
            `;

            if (!document.querySelector('#pull-refresh-styles')) {
                document.head.appendChild(style);
            }

            mainContent.style.position = 'relative';
            mainContent.appendChild(pullIndicator);

            mainContent.addEventListener('touchstart', function(e) {
                if (window.scrollY === 0) {
                    pullStartY = e.touches[0].clientY;
                    isPulling = true;
                }
            });

            mainContent.addEventListener('touchmove', function(e) {
                if (!isPulling) return;

                pullCurrentY = e.touches[0].clientY;
                const pullDistance = pullCurrentY - pullStartY;

                if (pullDistance > 0 && pullDistance < pullThreshold) {
                    pullIndicator.classList.add('visible');
                    const opacity = pullDistance / pullThreshold;
                    pullIndicator.style.opacity = opacity;
                }
            });

            mainContent.addEventListener('touchend', function() {
                if (!isPulling) return;

                const pullDistance = pullCurrentY - pullStartY;

                if (pullDistance > pullThreshold) {
                    // Trigger refresh
                    pullIndicator.classList.add('refreshing');
                    pullIndicator.innerHTML = `
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Refreshing...</span>
                    `;

                    setTimeout(() => {
                        pullIndicator.classList.remove('visible', 'refreshing');
                        pullIndicator.innerHTML = `
                            <i class="fas fa-arrow-down"></i>
                            <span>Pull to refresh</span>
                        `;

                        // Refresh dashboard data
                        if (window.updateDashboardStats) {
                            updateDashboardStats();
                        }

                        showToast('Dashboard refreshed!', 'success');
                    }, 1500);
                } else {
                    pullIndicator.classList.remove('visible');
                }

                isPulling = false;
                pullStartY = 0;
                pullCurrentY = 0;
            });
        }

        function addTouchFeedback() {
            // Add touch feedback styles
            const style = document.createElement('style');
            style.textContent = `
                .touch-feedback {
                    animation: touch-feedback 0.2s ease-out !important;
                }

                @media (hover: none) {
                    .btn:hover, .nav-link:hover, .btn-table-action:hover {
                        transform: none !important;
                        box-shadow: none !important;
                    }

                    .btn:active, .nav-link:active, .btn-table-action:active {
                        animation: touch-feedback 0.2s ease-out;
                        transform: scale(0.95);
                    }
                }
            `;

            if (!document.querySelector('#touch-feedback-styles')) {
                document.head.appendChild(style);
            }

            // Add active states for touch devices
            document.addEventListener('touchstart', function(e) {
                const target = e.target.closest('.btn, .nav-link, .btn-table-action, .quick-action-btn');
                if (target) {
                    target.classList.add('touch-active');
                }
            });

            document.addEventListener('touchend', function(e) {
                const target = e.target.closest('.btn, .nav-link, .btn-table-action, .quick-action-btn');
                if (target) {
                    setTimeout(() => {
                        target.classList.remove('touch-active');
                    }, 200);
                }
            });
        }

        // Mobile-specific utility functions
        function isMobileDevice() {
            return window.innerWidth <= 768 || 'ontouchstart' in window;
        }

        function showMobileToast(message, type = 'info') {
            if (isMobileDevice()) {
                // Show bottom-positioned toast for mobile
                const toast = document.createElement('div');
                toast.className = `mobile-toast toast-${type}`;
                toast.innerHTML = `
                    <div class="toast-content">
                        <span>${message}</span>
                        <button class="toast-close">×</button>
                    </div>
                `;

                // Add mobile toast styles
                const style = document.createElement('style');
                style.textContent = `
                    .mobile-toast {
                        position: fixed;
                        bottom: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: var(--bg-primary);
                        border: 1px solid var(--border-light);
                        border-radius: var(--radius-lg);
                        box-shadow: var(--shadow-xl);
                        padding: var(--spacing-md);
                        z-index: 10000;
                        min-width: 300px;
                        max-width: calc(100vw - 40px);
                        animation: slideInUp 0.3s ease-out;
                    }

                    .toast-success { border-left: 4px solid var(--success-color); }
                    .toast-error { border-left: 4px solid var(--danger-color); }
                    .toast-info { border-left: 4px solid var(--info-color); }

                    .toast-content {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        gap: var(--spacing-md);
                    }

                    .toast-close {
                        background: none;
                        border: none;
                        color: var(--text-muted);
                        cursor: pointer;
                        font-size: var(--text-lg);
                        padding: var(--spacing-xs);
                        border-radius: var(--radius-md);
                        transition: all var(--transition-fast);
                    }

                    .toast-close:hover {
                        color: var(--text-primary);
                        background: var(--bg-tertiary);
                    }
                `;

                if (!document.querySelector('#mobile-toast-styles')) {
                    document.head.appendChild(style);
                }

                document.body.appendChild(toast);

                // Auto remove after 3 seconds
                setTimeout(() => {
                    toast.style.animation = 'slideOutDown 0.3s ease-in forwards';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 3000);

                // Manual close
                toast.querySelector('.toast-close').addEventListener('click', () => {
                    toast.style.animation = 'slideOutDown 0.3s ease-in forwards';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                });
            } else {
                // Use regular toast for desktop
                showToast(message, type);
            }
        }

        // showToast function is now available globally from admin_header.php

        // Make functions globally available
        window.updateDashboardStats = updateDashboardStats;
        window.isMobileDevice = isMobileDevice;
    </script>
</body>
</html>