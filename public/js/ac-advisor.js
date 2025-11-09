/**
 * AC Advisor Chat Interface
 * Handles conversation flow, data collection, and AC capacity calculation
 */

class ACAdvisor {
    constructor() {
        this.currentStep = 0;
        this.userData = {};
        this.questions = [
            {
                question: "Please share your room details, and I'll tell you what AC capacity you need. Let's start! What is the length of your room in feet?",
                key: 'length',
                type: 'number',
                validation: (value) => {
                    const num = parseFloat(value);
                    return !isNaN(num) && num > 0 && num <= 100;
                },
                errorMsg: "Please enter a valid length between 1 and 100 feet."
            },
            {
                question: "Great! Now, what is the width of your room in feet?",
                key: 'width',
                type: 'number',
                validation: (value) => {
                    const num = parseFloat(value);
                    return !isNaN(num) && num > 0 && num <= 100;
                },
                errorMsg: "Please enter a valid width between 1 and 100 feet."
            },
            {
                question: "Perfect! What is the ceiling height of your room in feet?",
                key: 'height',
                type: 'number',
                validation: (value) => {
                    const num = parseFloat(value);
                    return !isNaN(num) && num >= 8 && num <= 20;
                },
                errorMsg: "Please enter a valid height between 8 and 20 feet."
            },
            {
                question: "What type of room is this?",
                key: 'roomType',
                type: 'option',
                options: ['Bedroom', 'Living Room', 'Office', 'Kitchen', 'Hall', 'Other'],
                validation: (value) => this.questions[3].options.includes(value),
                errorMsg: "Please select a valid room type."
            },
            {
                question: "How many windows does your room have?",
                key: 'windows',
                type: 'number',
                validation: (value) => {
                    const num = parseInt(value);
                    return !isNaN(num) && num >= 0 && num <= 10;
                },
                errorMsg: "Please enter a valid number of windows (0-10)."
            },
            {
                question: "Which floor is your room on?",
                key: 'floorLevel',
                type: 'option',
                options: ['Ground Floor', 'First Floor', 'Second Floor', 'Third Floor or Above'],
                validation: (value) => this.questions[5].options.includes(value),
                errorMsg: "Please select a valid floor level."
            },
            {
                question: "What is the sun exposure of your room?",
                key: 'sunExposure',
                type: 'option',
                options: ['Direct Sunlight (Most of the day)', 'Partial Sunlight (Few hours)', 'Shaded (Minimal sunlight)'],
                validation: (value) => this.questions[6].options.includes(value),
                errorMsg: "Please select a valid sun exposure option."
            }
        ];
        
        this.init();
    }
    
    init() {
        this.chatMessages = document.getElementById('chatMessages');
        this.chatInput = document.getElementById('chatInput');
        this.sendBtn = document.getElementById('sendBtn');
        this.restartBtn = document.getElementById('restartBtn');
        
        // Event listeners
        this.sendBtn.addEventListener('click', () => this.handleSend());
        this.chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.handleSend();
            }
        });
        
        this.restartBtn.addEventListener('click', () => this.restart());
        
        // Start conversation
        this.startConversation();
    }
    
    startConversation() {
        this.addBotMessage(this.questions[0].question);
        this.chatInput.focus();
    }
    
    addBotMessage(message, showTyping = false) {
        if (showTyping) {
            this.showTypingIndicator();
            setTimeout(() => {
                this.hideTypingIndicator();
                this.addMessage(message, 'bot');
            }, 1000);
        } else {
            this.addMessage(message, 'bot');
        }
    }
    
    addUserMessage(message) {
        this.addMessage(message, 'user');
    }
    
    addMessage(content, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.innerHTML = type === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        
        // Check if content is HTML string (contains HTML tags)
        if (typeof content === 'string') {
            // Check if string contains HTML tags
            const containsHTML = /<[a-z][\s\S]*>/i.test(content);
            if (containsHTML) {
                messageContent.innerHTML = content;
            } else {
                messageContent.textContent = content;
            }
        } else {
            messageContent.innerHTML = content;
        }
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(messageContent);
        
        this.chatMessages.appendChild(messageDiv);
        this.scrollToBottom();
    }
    
    showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot';
        typingDiv.id = 'typingIndicator';
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.innerHTML = '<i class="fas fa-robot"></i>';
        
        const typingContent = document.createElement('div');
        typingContent.className = 'typing-indicator';
        typingContent.innerHTML = '<span></span><span></span><span></span>';
        
        typingDiv.appendChild(avatar);
        typingDiv.appendChild(typingContent);
        
        this.chatMessages.appendChild(typingDiv);
        this.scrollToBottom();
    }
    
    hideTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.remove();
        }
    }
    
    showOptions(options) {
        const optionsDiv = document.createElement('div');
        optionsDiv.className = 'option-buttons';
        
        options.forEach(option => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = option;
            btn.addEventListener('click', () => {
                this.chatInput.value = option;
                this.handleSend();
            });
            optionsDiv.appendChild(btn);
        });
        
        // Add options to the last bot message
        const lastBotMessage = Array.from(this.chatMessages.querySelectorAll('.message.bot')).pop();
        if (lastBotMessage) {
            const messageContent = lastBotMessage.querySelector('.message-content');
            messageContent.appendChild(optionsDiv);
        }
    }
    
    handleSend() {
        const input = this.chatInput.value.trim();
        
        if (!input) return;
        
        // Disable input while processing
        this.chatInput.disabled = true;
        this.sendBtn.disabled = true;
        
        // Add user message
        this.addUserMessage(input);
        
        // Validate and process
        setTimeout(() => {
            this.processAnswer(input);
        }, 300);
    }
    
    processAnswer(answer) {
        const currentQuestion = this.questions[this.currentStep];
        
        // Validate answer
        if (!currentQuestion.validation(answer)) {
            this.addBotMessage(currentQuestion.errorMsg);
            this.enableInput();
            return;
        }
        
        // Store answer
        this.userData[currentQuestion.key] = answer;
        
        // Move to next question
        this.currentStep++;
        
        if (this.currentStep < this.questions.length) {
            const nextQuestion = this.questions[this.currentStep];
            this.addBotMessage(nextQuestion.question, true);
            
            // Show options if it's an option type question
            if (nextQuestion.type === 'option') {
                setTimeout(() => {
                    this.showOptions(nextQuestion.options);
                }, 1200);
            }
            
            this.enableInput();
        } else {
            // All questions answered, calculate and show recommendation
            this.showRecommendation();
        }
    }
    
    calculateACCapacity() {
        const length = parseFloat(this.userData.length);
        const width = parseFloat(this.userData.width);
        const height = parseFloat(this.userData.height);
        
        // Calculate area
        const area = length * width;
        
        // Base BTU calculation (20 BTU per sq ft)
        let baseBTU = area * 20;
        
        // Room type multipliers
        const roomTypeMultipliers = {
            'Bedroom': 1.0,
            'Living Room': 1.1,
            'Office': 1.0,
            'Kitchen': 1.2,
            'Hall': 1.15,
            'Other': 1.0
        };
        baseBTU *= roomTypeMultipliers[this.userData.roomType] || 1.0;
        
        // Window adjustments (each window adds 5% heat gain)
        const windows = parseInt(this.userData.windows);
        baseBTU *= (1 + (windows * 0.05));
        
        // Floor level adjustments
        const floorMultipliers = {
            'Ground Floor': 0.95,
            'First Floor': 1.0,
            'Second Floor': 1.1,
            'Third Floor or Above': 1.2
        };
        baseBTU *= floorMultipliers[this.userData.floorLevel] || 1.0;
        
        // Sun exposure adjustments
        const sunMultipliers = {
            'Direct Sunlight (Most of the day)': 1.2,
            'Partial Sunlight (Few hours)': 1.1,
            'Shaded (Minimal sunlight)': 1.0
        };
        baseBTU *= sunMultipliers[this.userData.sunExposure] || 1.0;
        
        // Ceiling height adjustment (standard is 10ft, adjust for higher ceilings)
        if (height > 10) {
            baseBTU *= (1 + ((height - 10) * 0.05));
        }
        
        // Convert BTU to tons (1 ton = 12,000 BTU)
        const tons = baseBTU / 12000;
        
        // Round up to nearest standard AC size
        const standardSizes = [0.75, 1, 1.5, 2, 2.5, 3, 4, 5];
        let recommendedTons = standardSizes.find(size => size >= tons) || 5;
        
        // If calculated is very close to a lower size, use that instead
        if (recommendedTons > tons && (recommendedTons - tons) > 0.3) {
            const lowerIndex = standardSizes.indexOf(recommendedTons) - 1;
            if (lowerIndex >= 0 && (tons - standardSizes[lowerIndex]) < 0.2) {
                recommendedTons = standardSizes[lowerIndex];
            }
        }
        
        return {
            tons: recommendedTons,
            btu: Math.round(baseBTU),
            area: area
        };
    }
    
    showRecommendation() {
        const recommendation = this.calculateACCapacity();
        
        const recommendationHTML = `
            <div class="recommendation-card">
                <h3><i class="fas fa-check-circle"></i> Recommended AC Capacity</h3>
                <div class="recommendation-value">${recommendation.tons} Ton</div>
                <div class="recommendation-details">
                    <p><strong>BTU Rating:</strong> ${recommendation.btu.toLocaleString()} BTU</p>
                    <p><strong>Room Area:</strong> ${recommendation.area.toFixed(1)} sq ft</p>
                    <p style="margin-top: 15px;">Based on your room specifications, we recommend a <strong>${recommendation.tons}-ton air conditioner</strong> for optimal cooling performance.</p>
                </div>
                <div class="recommendation-actions">
                    <a href="${BASE_URL}/user/products/" class="recommendation-btn primary">
                        <i class="fas fa-shopping-cart"></i> Browse AC Products
                    </a>
                    <a href="${BASE_URL}/user/services/#request-form" class="recommendation-btn secondary">
                        <i class="fas fa-calendar"></i> Request Consultation
                    </a>
                </div>
            </div>
        `;
        
        this.addBotMessage(recommendationHTML, true);
        
        // Show restart button
        this.restartBtn.style.display = 'block';
        
        // Disable input
        this.chatInput.disabled = true;
        this.sendBtn.disabled = true;
        this.chatInput.placeholder = "Conversation completed";
        
        // Send email notification
        this.sendEmailNotification(recommendation);
    }
    
    async sendEmailNotification(recommendation) {
        try {
            const response = await fetch(`${BASE_URL}/api/ac-advisor/send-recommendation.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userData: this.userData,
                    recommendation: recommendation
                })
            });
            
            const result = await response.json();
            if (result.success) {
                console.log('Email notification sent successfully');
            } else {
                console.error('Failed to send email notification:', result.message);
            }
        } catch (error) {
            console.error('Error sending email notification:', error);
        }
    }
    
    enableInput() {
        this.chatInput.disabled = false;
        this.sendBtn.disabled = false;
        this.chatInput.value = '';
        this.chatInput.focus();
    }
    
    scrollToBottom() {
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }
    
    restart() {
        this.currentStep = 0;
        this.userData = {};
        this.chatMessages.innerHTML = '';
        this.restartBtn.style.display = 'none';
        this.chatInput.placeholder = "Type your answer here...";
        this.startConversation();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the AC Advisor page
    if (document.getElementById('chatMessages')) {
        new ACAdvisor();
    }
});

