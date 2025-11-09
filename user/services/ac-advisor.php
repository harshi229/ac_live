<?php
// Set page metadata
$pageTitle = 'AC Advisor - Smart AC Size Calculator';
$pageDescription = 'Get personalized AC capacity recommendations based on your room details. Our smart advisor will help you choose the perfect AC size.';
$pageKeywords = 'AC size calculator, AC capacity calculator, AC tonnage calculator, BTU calculator, AC advisor, air conditioner sizing';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';
?>

<style>
/* AC Advisor Page Styles */
.ac-advisor-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    padding: 120px 0 80px;
    overflow: hidden;
}

.ac-advisor-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(34, 197, 94, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.ac-advisor-hero .container {
    position: relative;
    z-index: 1;
    text-align: center;
    color: white;
}

.ac-advisor-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #3b82f6, #22c55e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ac-advisor-hero p {
    font-size: 1.3rem;
    color: #cbd5e1;
    max-width: 800px;
    margin: 0 auto;
}

.ac-advisor-container {
    padding: 60px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
    min-height: 70vh;
}

.chat-wrapper {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 700px;
}

.chat-header {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    padding: 25px 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.chat-header i {
    font-size: 1.5rem;
}

.chat-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 30px;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.message {
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

.message.bot {
    justify-content: flex-start;
}

.message.user {
    justify-content: flex-end;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
}

.message.bot .message-avatar {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.message.user .message-avatar {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

.message-content {
    max-width: 70%;
    padding: 15px 20px;
    border-radius: 18px;
    line-height: 1.6;
}

.message.bot .message-content {
    background: white;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.message.user .message-content {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border-bottom-right-radius: 4px;
}

.typing-indicator {
    display: flex;
    gap: 6px;
    padding: 15px 20px;
    background: white;
    border-radius: 18px;
    border-bottom-left-radius: 4px;
    max-width: 70px;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #3b82f6;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
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

.recommendation-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #3b82f6;
    border-radius: 15px;
    padding: 25px;
    margin-top: 10px;
}

.recommendation-card h3 {
    color: #1e293b;
    margin: 0 0 15px 0;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.recommendation-card .recommendation-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #3b82f6;
    margin: 10px 0;
}

.recommendation-card .recommendation-details {
    color: #64748b;
    margin: 15px 0;
    line-height: 1.8;
}

.recommendation-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.recommendation-btn {
    padding: 12px 24px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.recommendation-btn.primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.recommendation-btn.secondary {
    background: white;
    color: #3b82f6;
    border: 2px solid #3b82f6;
}

.recommendation-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(59, 130, 246, 0.3);
}

.chat-input-container {
    padding: 20px 30px;
    background: white;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 15px;
    align-items: center;
}

.chat-input-wrapper {
    flex: 1;
    position: relative;
}

.chat-input {
    width: 100%;
    padding: 14px 50px 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 50px;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
}

.chat-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.chat-input:disabled {
    background: #f1f5f9;
    cursor: not-allowed;
}

.chat-send-btn {
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
}

.chat-send-btn:hover:not(:disabled) {
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.chat-send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.option-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.option-btn {
    padding: 10px 20px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    color: #1e293b;
}

.option-btn:hover {
    border-color: #3b82f6;
    background: #f0f9ff;
    color: #3b82f6;
    transform: translateY(-2px);
}

.restart-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.restart-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .ac-advisor-hero h1 {
        font-size: 2.5rem;
    }
    
    .chat-wrapper {
        height: 600px;
        border-radius: 0;
    }
    
    .message-content {
        max-width: 85%;
    }
    
    .recommendation-actions {
        flex-direction: column;
    }
    
    .recommendation-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Hero Section -->
<section class="ac-advisor-hero">
    <div class="container">
        <h1><i class="fas fa-robot"></i> Smart AC Advisor</h1>
        <p>Tell me about your room, and I'll recommend the perfect AC capacity for optimal cooling</p>
    </div>
</section>

<!-- AC Advisor Chat Interface -->
<section class="ac-advisor-container">
    <div class="container">
        <div class="chat-wrapper">
            <div class="chat-header">
                <button class="restart-btn" id="restartBtn" style="display: none;">
                    <i class="fas fa-redo"></i> Restart
                </button>
                <i class="fas fa-robot"></i>
                <h2>Akash Aircon Smart AC Advisor</h2>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be added here by JavaScript -->
            </div>
            
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <input 
                        type="text" 
                        id="chatInput" 
                        class="chat-input" 
                        placeholder="Type your answer here..."
                        autocomplete="off"
                    >
                    <button class="chat-send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Define BASE_URL for JavaScript -->
<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<!-- Load AC Advisor JavaScript -->
<script src="<?php echo PUBLIC_URL; ?>/js/ac-advisor.js"></script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>

