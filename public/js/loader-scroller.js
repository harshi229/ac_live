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
     * Create scroll to top button / chatbot icon
     */
    createScrollToTop() {
        const scrollButton = document.createElement('button');
        scrollButton.className = 'scroll-to-top';
        scrollButton.innerHTML = '<i class="fas fa-robot"></i>';
        scrollButton.setAttribute('aria-label', 'AC Advisor Chatbot');
        scrollButton.setAttribute('title', 'AC Advisor Chatbot');
        
        document.body.appendChild(scrollButton);

        // Function to update button state based on scroll position
        const updateButtonState = () => {
            const scrollPosition = window.pageYOffset;
            
            if (scrollPosition === 0 || scrollPosition < 50) {
                // At the top - show chatbot icon
                scrollButton.classList.add('visible', 'chatbot-mode');
                scrollButton.classList.remove('scroll-mode');
                scrollButton.innerHTML = '<i class="fas fa-robot"></i>';
                scrollButton.setAttribute('aria-label', 'AC Advisor Chatbot');
                scrollButton.setAttribute('title', 'AC Advisor Chatbot');
            } else if (scrollPosition > 300) {
                // Scrolled down - show scroll to top arrow
                scrollButton.classList.add('visible', 'scroll-mode');
                scrollButton.classList.remove('chatbot-mode');
                scrollButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
                scrollButton.setAttribute('aria-label', 'Scroll to top');
                scrollButton.setAttribute('title', 'Scroll to top');
            } else {
                // Between 50 and 300 - hide the button
                scrollButton.classList.remove('visible');
            }
        };

        // Initial state check
        updateButtonState();

        // Show/hide button based on scroll position
        window.addEventListener('scroll', updateButtonState);

        // Handle click - show chatbox or scroll to top
        scrollButton.addEventListener('click', () => {
            if (scrollButton.classList.contains('chatbot-mode')) {
                // Show chatbox modal
                this.showChatbox();
            } else {
                // Scroll to top
                this.smoothScrollTo(0);
            }
        });
    }

    /**
     * Create and show chatbox modal
     */
    showChatbox() {
        // Check if chatbox already exists
        let chatbox = document.getElementById('chatboxModal');
        if (chatbox) {
            chatbox.classList.add('active');
            return;
        }

        // Get BASE_URL for JavaScript
        const baseUrl = window.BASE_URL || window.location.origin + (window.location.pathname.includes('/public_html') ? '/public_html' : '');

        // Create chatbox modal
        chatbox = document.createElement('div');
        chatbox.id = 'chatboxModal';
        chatbox.className = 'chatbox-modal';
        chatbox.innerHTML = `
            <div class="chatbox-overlay"></div>
            <div class="chatbox-container">
                <div class="chatbox-header">
                    <div class="chatbox-header-content">
                        <i class="fas fa-robot"></i>
                        <h3>Akash Aircon Smart AC Advisor</h3>
                    </div>
                    <button class="chatbox-close" aria-label="Close chatbox">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chatbox-body">
                    <div class="chat-wrapper chatbox-wrapper">
                        <div class="chat-messages" id="chatboxMessages">
                            <!-- Messages will be added here by JavaScript -->
                        </div>
                        <div class="chat-input-container">
                            <div class="chat-input-wrapper">
                                <input 
                                    type="text" 
                                    id="chatboxInput" 
                                    class="chat-input" 
                                    placeholder="Type your answer here..."
                                    autocomplete="off"
                                >
                                <button class="chat-send-btn" id="chatboxSendBtn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(chatbox);

        // Add styles if not already added
        if (!document.getElementById('chatboxModalStyles')) {
            const style = document.createElement('style');
            style.id = 'chatboxModalStyles';
            style.textContent = `
                .chatbox-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 10000;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.3s ease-out;
                }

                .chatbox-modal.active {
                    display: flex;
                }

                .chatbox-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    backdrop-filter: blur(5px);
                }

                .chatbox-container {
                    position: relative;
                    width: 90%;
                    max-width: 500px;
                    height: 90vh;
                    max-height: 90vh;
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    display: flex;
                    flex-direction: column;
                    z-index: 1;
                    animation: slideUp 0.3s ease-out;
                    overflow: hidden;
                }

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .chatbox-header {
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                    color: white;
                    padding: 20px 25px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    border-radius: 20px 20px 0 0;
                }

                .chatbox-header-content {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .chatbox-header-content i {
                    font-size: 1.5rem;
                }

                .chatbox-header-content h3 {
                    margin: 0;
                    font-size: 1.2rem;
                    font-weight: 700;
                }

                .chatbox-close {
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                    font-size: 1.1rem;
                }

                .chatbox-close:hover {
                    background: rgba(255, 255, 255, 0.3);
                    transform: rotate(90deg);
                }

                .chatbox-body {
                    flex: 1;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    min-height: 0;
                }

                .chatbox-wrapper {
                    height: 100%;
                    margin: 0;
                    border-radius: 0;
                    box-shadow: none;
                    display: flex;
                    flex-direction: column;
                    min-height: 0;
                    overflow: hidden;
                }

                .chatbox-wrapper .chat-messages {
                    flex: 1;
                    overflow-y: auto;
                    overflow-x: hidden;
                    padding: 20px;
                    background: #f8f9fa;
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                    min-height: 0;
                    max-height: 100%;
                }

                /* Custom scrollbar for chat messages */
                .chatbox-wrapper .chat-messages::-webkit-scrollbar {
                    width: 8px;
                }

                .chatbox-wrapper .chat-messages::-webkit-scrollbar-track {
                    background: #e2e8f0;
                    border-radius: 4px;
                }

                .chatbox-wrapper .chat-messages::-webkit-scrollbar-thumb {
                    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                    border-radius: 4px;
                    transition: background 0.3s ease;
                }

                .chatbox-wrapper .chat-messages::-webkit-scrollbar-thumb:hover {
                    background: linear-gradient(135deg, #2563eb, #7c3aed);
                }

                /* Message Styling */
                .chatbox-wrapper .message {
                    display: flex;
                    gap: 12px;
                    animation: slideIn 0.3s ease-out;
                }

                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .chatbox-wrapper .message.bot {
                    justify-content: flex-start;
                }

                .chatbox-wrapper .message.user {
                    justify-content: flex-end;
                }

                .chatbox-wrapper .message-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    font-size: 1.2rem;
                }

                .chatbox-wrapper .message.bot .message-avatar {
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                    color: white;
                }

                .chatbox-wrapper .message.user .message-avatar {
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    color: white;
                }

                .chatbox-wrapper .message-content {
                    max-width: 70%;
                    padding: 15px 20px;
                    border-radius: 18px;
                    line-height: 1.6;
                    word-wrap: break-word;
                }

                .chatbox-wrapper .message.bot .message-content {
                    background: white;
                    color: #1e293b;
                    border-bottom-left-radius: 4px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                }

                .chatbox-wrapper .message.user .message-content {
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                    color: white;
                    border-bottom-right-radius: 4px;
                }

                .chatbox-wrapper .message-content a {
                    color: #3b82f6;
                    text-decoration: underline;
                }

                .chatbox-wrapper .message.user .message-content a {
                    color: white;
                    text-decoration: underline;
                }

                /* Typing Indicator */
                .chatbox-wrapper .typing-indicator {
                    display: flex;
                    gap: 6px;
                    padding: 15px 20px;
                    background: white;
                    border-radius: 18px;
                    border-bottom-left-radius: 4px;
                    max-width: 70px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                }

                .chatbox-wrapper .typing-indicator span {
                    width: 8px;
                    height: 8px;
                    background: #3b82f6;
                    border-radius: 50%;
                    animation: typing 1.4s infinite;
                }

                .chatbox-wrapper .typing-indicator span:nth-child(2) {
                    animation-delay: 0.2s;
                }

                .chatbox-wrapper .typing-indicator span:nth-child(3) {
                    animation-delay: 0.4s;
                }

                @keyframes typing {
                    0%, 60%, 100% {
                        transform: translateY(0);
                        opacity: 0.7;
                    }
                    30% {
                        transform: translateY(-10px);
                        opacity: 1;
                    }
                }

                /* Input Styling */
                .chatbox-wrapper .chat-input-container {
                    border-top: 1px solid #e2e8f0;
                    padding: 15px 20px;
                    background: white;
                    display: flex;
                    gap: 15px;
                    align-items: center;
                }

                .chatbox-wrapper .chat-input-wrapper {
                    flex: 1;
                    position: relative;
                }

                .chatbox-wrapper .chat-input {
                    width: 100%;
                    padding: 14px 50px 14px 18px;
                    border: 2px solid #e2e8f0;
                    border-radius: 50px;
                    font-size: 1rem;
                    transition: all 0.3s ease;
                    font-family: inherit;
                    background: white;
                    color: #1e293b;
                }

                .chatbox-wrapper .chat-input:focus {
                    outline: none;
                    border-color: #3b82f6;
                    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
                }

                .chatbox-wrapper .chat-input:disabled {
                    background: #f1f5f9;
                    cursor: not-allowed;
                }

                .chatbox-wrapper .chat-input::placeholder {
                    color: #94a3b8;
                }

                .chatbox-wrapper .chat-send-btn {
                    position: absolute;
                    right: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 38px;
                    height: 38px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                    color: white;
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                    font-size: 1rem;
                }

                .chatbox-wrapper .chat-send-btn:hover:not(:disabled) {
                    transform: translateY(-50%) scale(1.1);
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
                }

                .chatbox-wrapper .chat-send-btn:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                /* Option Buttons */
                .chatbox-wrapper .option-buttons {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    margin-top: 10px;
                }

                .chatbox-wrapper .option-btn {
                    padding: 10px 20px;
                    background: white;
                    border: 2px solid #e2e8f0;
                    border-radius: 50px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-size: 0.95rem;
                    color: #1e293b;
                }

                .chatbox-wrapper .option-btn:hover {
                    border-color: #3b82f6;
                    background: #f0f9ff;
                    color: #3b82f6;
                    transform: translateY(-2px);
                }

                /* Recommendation Card */
                .chatbox-wrapper .recommendation-card {
                    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                    border: 2px solid #3b82f6;
                    border-radius: 15px;
                    padding: 25px;
                    margin-top: 10px;
                }

                .chatbox-wrapper .recommendation-card h3 {
                    color: #1e293b;
                    margin: 0 0 15px 0;
                    font-size: 1.3rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .chatbox-wrapper .recommendation-card .recommendation-value {
                    font-size: 2.5rem;
                    font-weight: 800;
                    color: #3b82f6;
                    margin: 10px 0;
                }

                .chatbox-wrapper .recommendation-card .recommendation-details {
                    color: #64748b;
                    margin: 15px 0;
                    line-height: 1.8;
                }

                .chatbox-wrapper .recommendation-actions {
                    display: flex;
                    gap: 15px;
                    margin-top: 20px;
                    flex-wrap: wrap;
                }

                .chatbox-wrapper .recommendation-btn {
                    padding: 12px 24px;
                    border-radius: 50px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }

                .chatbox-wrapper .recommendation-btn.primary {
                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                    color: white;
                }

                .chatbox-wrapper .recommendation-btn.secondary {
                    background: white;
                    color: #3b82f6;
                    border: 2px solid #3b82f6;
                }

                .chatbox-wrapper .recommendation-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 20px rgba(59, 130, 246, 0.3);
                }

                @media (max-width: 768px) {
                    .chatbox-container {
                        width: 100%;
                        max-width: 100%;
                        height: 100%;
                        max-height: 100%;
                        border-radius: 0;
                    }

                    .chatbox-header {
                        border-radius: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Show modal
        setTimeout(() => {
            chatbox.classList.add('active');
        }, 10);

        // Close button
        const closeBtn = chatbox.querySelector('.chatbox-close');
        closeBtn.addEventListener('click', () => {
            this.hideChatbox();
        });

        // Close on overlay click
        const overlay = chatbox.querySelector('.chatbox-overlay');
        overlay.addEventListener('click', () => {
            this.hideChatbox();
        });

        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape' && chatbox.classList.contains('active')) {
                this.hideChatbox();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);

        // Restore chat session if exists
        const sessionRestored = this.restoreChatSession();

        // Initialize AC Advisor in chatbox
        // initSimpleChatbox will check for session and skip welcome message if session exists
        this.initChatboxAdvisor();

        // Auto-scroll to bottom when chatbox opens
        setTimeout(() => {
            this.scrollChatboxToBottom();
        }, 200);

        // Watch for new messages and auto-scroll + save session
        const chatMessages = document.getElementById('chatboxMessages');
        if (chatMessages) {
            const observer = new MutationObserver(() => {
                this.scrollChatboxToBottom();
                // Save session whenever messages change
                setTimeout(() => {
                    this.saveChatSession();
                }, 100);
            });
            observer.observe(chatMessages, {
                childList: true,
                subtree: true
            });
        }
    }

    /**
     * Hide chatbox modal
     */
    hideChatbox() {
        const chatbox = document.getElementById('chatboxModal');
        if (chatbox) {
            chatbox.classList.remove('active');
        }
    }

    /**
     * Scroll chatbox messages to bottom
     */
    scrollChatboxToBottom() {
        const chatMessages = document.getElementById('chatboxMessages');
        if (chatMessages) {
            // Use requestAnimationFrame for smooth scrolling
            requestAnimationFrame(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
        }
    }

    /**
     * Save chat messages to session storage
     */
    saveChatSession() {
        const chatMessages = document.getElementById('chatboxMessages');
        if (chatMessages) {
            const messages = [];
            const messageElements = chatMessages.querySelectorAll('.message');
            
            messageElements.forEach(msg => {
                const isBot = msg.classList.contains('bot');
                const isUser = msg.classList.contains('user');
                const content = msg.querySelector('.message-content');
                
                if (content) {
                    messages.push({
                        type: isBot ? 'bot' : 'user',
                        content: content.innerHTML,
                        timestamp: Date.now()
                    });
                }
            });
            
            try {
                sessionStorage.setItem('chatboxSession', JSON.stringify(messages));
            } catch (e) {
                console.error('Error saving chat session:', e);
            }
        }
    }

    /**
     * Restore chat messages from session storage
     */
    restoreChatSession() {
        const chatMessages = document.getElementById('chatboxMessages');
        if (!chatMessages) return false;

        try {
            const savedSession = sessionStorage.getItem('chatboxSession');
            if (savedSession) {
                const messages = JSON.parse(savedSession);
                
                if (messages && messages.length > 0) {
                    // Clear existing messages (except if they're from AC Advisor initialization)
                    chatMessages.innerHTML = '';
                    
                    // Restore messages
                    messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${msg.type}`;
                        
                        const avatar = document.createElement('div');
                        avatar.className = 'message-avatar';
                        avatar.innerHTML = msg.type === 'bot' 
                            ? '<i class="fas fa-robot"></i>' 
                            : '<i class="fas fa-user"></i>';
                        
                        const messageContent = document.createElement('div');
                        messageContent.className = 'message-content';
                        messageContent.innerHTML = msg.content;
                        
                        messageDiv.appendChild(avatar);
                        messageDiv.appendChild(messageContent);
                        chatMessages.appendChild(messageDiv);
                    });
                    
                    // Scroll to bottom
                    setTimeout(() => {
                        this.scrollChatboxToBottom();
                    }, 100);
                    
                    return true;
                }
            }
        } catch (e) {
            console.error('Error restoring chat session:', e);
        }
        
        return false;
    }

    /**
     * Clear chat session
     */
    clearChatSession() {
        try {
            sessionStorage.removeItem('chatboxSession');
        } catch (e) {
            console.error('Error clearing chat session:', e);
        }
    }

    /**
     * Initialize AC Advisor in chatbox
     */
    initChatboxAdvisor() {
        // Wait a bit for DOM to be ready
        setTimeout(() => {
            const chatMessages = document.getElementById('chatboxMessages');
            const chatInput = document.getElementById('chatboxInput');
            const sendBtn = document.getElementById('chatboxSendBtn');

            if (!chatMessages || !chatInput || !sendBtn) {
                return;
            }

            // Try to load AC Advisor script if not already loaded
            if (typeof ACAdvisor === 'undefined') {
                // Try to load the AC Advisor script
                const script = document.createElement('script');
                const baseUrl = window.BASE_URL || window.location.origin + (window.location.pathname.includes('/public_html') ? '/public_html' : '');
                script.src = baseUrl + '/public/js/ac-advisor.js';
                script.onload = () => {
                    this.initACAdvisorInChatbox(chatMessages, chatInput, sendBtn);
                };
                script.onerror = () => {
                    // Fallback if script fails to load
                    this.initSimpleChatbox(chatMessages, chatInput, sendBtn);
                };
                document.head.appendChild(script);
            } else {
                // AC Advisor already loaded
                this.initACAdvisorInChatbox(chatMessages, chatInput, sendBtn);
            }
        }, 100);
    }

    /**
     * Initialize AC Advisor in chatbox with proper element references
     */
    initACAdvisorInChatbox(chatMessages, chatInput, sendBtn) {
        // Temporarily rename elements to match AC Advisor expectations
        const originalChatMessages = document.getElementById('chatMessages');
        const originalChatInput = document.getElementById('chatInput');
        const originalSendBtn = document.getElementById('sendBtn');

        // Temporarily set IDs to match AC Advisor
        chatMessages.id = 'chatMessages';
        chatInput.id = 'chatInput';
        sendBtn.id = 'sendBtn';

        // Store original elements if they exist
        if (originalChatMessages) originalChatMessages.id = 'chatMessages_original';
        if (originalChatInput) originalChatInput.id = 'chatInput_original';
        if (originalSendBtn) originalSendBtn.id = 'sendBtn_original';

        // Initialize AC Advisor
        if (typeof ACAdvisor !== 'undefined' && !window.chatboxAdvisor) {
            try {
                window.chatboxAdvisor = new ACAdvisor();
            } catch (e) {
                console.error('Error initializing AC Advisor in chatbox:', e);
                // Restore original IDs
                chatMessages.id = 'chatboxMessages';
                chatInput.id = 'chatboxInput';
                sendBtn.id = 'chatboxSendBtn';
                if (originalChatMessages) originalChatMessages.id = 'chatMessages';
                if (originalChatInput) originalChatInput.id = 'chatInput';
                if (originalSendBtn) originalSendBtn.id = 'sendBtn';
                // Fallback to simple chatbox
                this.initSimpleChatbox(chatMessages, chatInput, sendBtn);
            }
        } else {
            // Fallback to simple chatbox
            this.initSimpleChatbox(chatMessages, chatInput, sendBtn);
        }
    }

    /**
     * Simple chatbox initialization (fallback)
     */
    initSimpleChatbox(chatMessages, chatInput, sendBtn) {
        // Add welcome message
        const welcomeMsg = document.createElement('div');
        welcomeMsg.className = 'message bot';
        welcomeMsg.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                Hello! I'm your AC Advisor. I can help you find the perfect AC size for your room. 
                <a href="${window.USER_URL || window.location.origin + '/user'}/services/ac-advisor.php" style="color: #3b82f6; text-decoration: underline;">
                    Click here to start the full AC Advisor experience
                </a>
            </div>
        `;
        // Check if messages already exist (session was already restored)
        const existingMessages = chatMessages.querySelectorAll('.message');
        
        if (existingMessages.length === 0) {
            // No messages exist, show welcome message
            chatMessages.appendChild(welcomeMsg);
            // Scroll to bottom after adding welcome message
            setTimeout(() => {
                this.scrollChatboxToBottom();
                this.saveChatSession();
            }, 100);
        }

        // Send button handler
        sendBtn.addEventListener('click', () => {
            const input = chatInput.value.trim();
            if (input) {
                // Add user message
                const userMsg = document.createElement('div');
                userMsg.className = 'message user';
                userMsg.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="message-content">${input}</div>
                `;
                chatMessages.appendChild(userMsg);
                chatInput.value = '';
                this.scrollChatboxToBottom();
                this.saveChatSession();

                // Show response
                setTimeout(() => {
                    const botMsg = document.createElement('div');
                    botMsg.className = 'message bot';
                    botMsg.innerHTML = `
                        <div class="message-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-content">
                            For a complete AC sizing experience, please visit our 
                            <a href="${window.USER_URL || window.location.origin + '/user'}/services/ac-advisor.php" style="color: #3b82f6; text-decoration: underline;">
                                AC Advisor page
                            </a> where I can ask you detailed questions about your room.
                        </div>
                    `;
                    chatMessages.appendChild(botMsg);
                    this.scrollChatboxToBottom();
                    this.saveChatSession();
                }, 500);
            }
        });

        // Enter key handler
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendBtn.click();
            }
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
