<?php
// Set page metadata
$pageTitle = 'Contact Us';
$pageDescription = 'Get in touch with Akash Enterprise - We\'re here to help with all your air conditioning needs';
$pageKeywords = 'contact us, AC support, air conditioning help, customer service, AC consultation';

require_once __DIR__ . '/../../includes/config/init.php';
require_once INCLUDES_PATH . '/functions/email_helpers.php';
include INCLUDES_PATH . '/templates/header.php';
// Database connection now in init.php

// Handle contact form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $subject = trim($_POST['subject']);
        $message_text = trim($_POST['message']);
        
        // Store in inquiries table
        $stmt = $pdo->prepare("INSERT INTO inquiries (customer_name, phone, email, requirements, status, created_at) 
                               VALUES (?, ?, ?, ?, 'New', NOW())");
        
        $requirements = "Subject: $subject\n\nMessage: $message_text";
        
        $stmt->execute([$name, $phone, $email, $requirements]);
        
        // Send email notification to admin
        $emailSubject = "New Contact Form Submission - " . $subject;
        $emailMessage = "
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
        <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
        <p><strong>Message:</strong></p>
        <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>
            " . nl2br(htmlspecialchars($message_text)) . "
        </div>
        <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
        <hr>
        <p><em>This message was sent from the contact form on your website.</em></p>
        ";
        
        // Send email to admin
        $adminEmail = 'aakashjamnagar@gmail.com'; // From email config
        $emailSent = sendEmail($adminEmail, $emailSubject, $emailMessage);
        
        if ($emailSent) {
            error_log("Contact form email sent successfully to admin");
        } else {
            error_log("Failed to send contact form email to admin");
        }
        
        $message = "Thank you for contacting us! We'll get back to you within 24 hours.";
        $message_type = "success";
        
        // Clear form on success
        $_POST = array();
    } catch (PDOException $e) {
        $message = "Error sending message. Please try again or call us directly.";
        $message_type = "danger";
        error_log("Contact form database error: " . $e->getMessage());
    } catch (Exception $e) {
        $message = "Error sending message. Please try again or call us directly.";
        $message_type = "danger";
        error_log("Contact form email error: " . $e->getMessage());
    }
}
?>

<style>
/* Contact Page - Modern & Professional Design */

/* Hero Section */
.contact-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    padding: 120px 0 80px;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 25% 35%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.contact-hero .container {
    position: relative;
    z-index: 1;
        text-align: center;
    color: white;
}

.contact-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
        margin-bottom: 20px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.contact-hero p {
    font-size: 1.3rem;
    color: #cbd5e1;
    max-width: 700px;
    margin: 0 auto;
}

/* Quick Contact Cards */
.quick-contact-section {
    padding: 80px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
    margin-top: -60px;
    position: relative;
    z-index: 2;
}

.contact-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    max-width: 1400px;
        margin: 0 auto;
    }

.contact-quick-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.contact-quick-card:hover {
    transform: translateY(-10px);
    border-color: #3b82f6;
    box-shadow: 0 20px 60px rgba(59, 130, 246, 0.2);
}

.quick-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    color: white;
    font-size: 2rem;
}

.contact-quick-card h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

.contact-quick-card p {
    font-size: 1rem;
    color: #64748b;
    margin-bottom: 15px;
    line-height: 1.6;
}

.contact-quick-card a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.05rem;
    transition: all 0.3s ease;
    display: inline-block;
}

.contact-quick-card a:hover {
    transform: translateX(5px);
    color: #1d4ed8;
}

/* Main Contact Section */
.main-contact-section {
    padding: 100px 0;
    background: white;
}

.contact-container {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 60px;
    align-items: start;
}

/* Contact Info Side */
.contact-info-side {
    position: sticky;
    top: 100px;
}

.info-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.info-card h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.info-card h3 i {
    color: #3b82f6;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 20px 0;
    border-bottom: 1px solid #e2e8f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.info-content h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}

.info-content p {
    font-size: 1.1rem;
    color: #1e293b;
    margin: 0;
    font-weight: 600;
}

.info-content a {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.3s ease;
}

.info-content a:hover {
    color: #1d4ed8;
}

/* Working Hours */
.hours-list {
    list-style: none;
    padding: 0;
    margin: 20px 0 0 0;
}

.hours-list li {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
    color: #475569;
}

.hours-list li:last-child {
    border-bottom: none;
}

.hours-list li strong {
    color: #1e293b;
}

.hours-list li.closed {
    color: #ef4444;
}

/* Social Media */
.social-media {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.social-link {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
}

.social-link.facebook { background: linear-gradient(135deg, #1877f2, #0c63d4); }
.social-link.twitter { background: linear-gradient(135deg, #1da1f2, #0d8bd9); }
.social-link.instagram { background: linear-gradient(135deg, #e4405f, #d31e40); }
.social-link.linkedin { background: linear-gradient(135deg, #0077b5, #005d8f); }
.social-link.whatsapp { background: linear-gradient(135deg, #25d366, #1da851); }

/* Contact Form Side */
.contact-form-side {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 20px;
    padding: 50px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

.contact-form-side h2 {
    font-size: 2.2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

.contact-form-side .subtitle {
    font-size: 1.05rem;
    color: #64748b;
    margin-bottom: 35px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 10px;
    font-size: 0.95rem;
}

.form-group label .required {
    color: #ef4444;
    margin-left: 3px;
}

.contact-form .form-control {
        width: 100%;
    padding: 15px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
    background: white;
}

.contact-form .form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.contact-form textarea.form-control {
    resize: vertical;
    min-height: 150px;
}

.submit-btn {
    width: 100%;
    padding: 18px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
        cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.4);
}

/* Map Section */
.map-section {
    padding: 0;
    background: #f8f9fa;
    position: relative;
}

.map-container {
    width: 100%;
    height: 600px;
    position: relative;
    overflow: hidden;
    background: #e2e8f0;
    border-radius: 0;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

.map-placeholder {
    text-align: center;
    z-index: 1;
    position: relative;
}

.map-placeholder i {
    font-size: 4rem;
    color: #94a3b8;
    margin-bottom: 15px;
}

/* Map Actions */
.map-actions {
    padding: 40px 20px;
    background: white;
    text-align: center;
    border-top: 1px solid #e2e8f0;
}

.map-actions-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.map-address-info {
    margin-bottom: 10px;
}

.map-address-info h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}

.map-address-info p {
    color: #64748b;
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.6;
}

.map-actions .btn {
    border: none;
    padding: 16px 40px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    min-width: 220px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.map-actions .btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
}

.map-actions .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    color: white;
}

.map-actions .btn-primary:active {
    transform: translateY(-1px);
}

.map-actions .btn-primary i {
    font-size: 1.1rem;
}

.map-actions .btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.map-actions .btn-success:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: white;
}

/* Responsive adjustments for map actions */
@media (max-width: 768px) {
    .map-container {
        height: 450px;
    }
    
    .map-actions {
        padding: 30px 15px;
    }
    
    .map-actions .btn {
        width: 100%;
        max-width: 300px;
        padding: 14px 30px;
    }
}

@media (max-width: 576px) {
    .map-container {
        height: 400px;
    }
    
    .map-actions {
        padding: 25px 15px;
    }
    
    .map-actions-wrapper {
        gap: 15px;
    }
    
    .map-actions .btn {
        width: 100%;
        max-width: 100%;
        padding: 14px 25px;
        font-size: 0.95rem;
    }
    
    .map-address-info h4 {
        font-size: 1rem;
    }
    
    .map-address-info p {
        font-size: 0.9rem;
    }
}

/* FAQ Section */
.faq-section {
    padding: 100px 0;
    background: white;
}

.faq-container {
    max-width: 900px;
    margin: 60px auto 0;
}

.faq-item {
    background: #f8f9fa;
    border-radius: 15px;
        margin-bottom: 20px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.faq-item.active {
    border-color: #3b82f6;
}

.faq-question {
    padding: 25px 30px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    font-size: 1.1rem;
    color: #1e293b;
    transition: all 0.3s ease;
}

.faq-question:hover {
    color: #3b82f6;
}

.faq-question i {
    color: #3b82f6;
    transition: transform 0.3s ease;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.faq-item.active .faq-answer {
    max-height: 500px;
}

.faq-answer-content {
    padding: 0 30px 25px;
    color: #64748b;
    line-height: 1.7;
    font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .contact-cards-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .contact-container {
        grid-template-columns: 1fr;
    }
    
    .contact-info-side {
        position: static;
    }
}

@media (max-width: 768px) {
    .contact-hero h1 {
        font-size: 2.5rem;
    }
    
    .contact-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-form-side {
        padding: 30px 20px;
    }
    
    .map-container {
        height: 350px;
        }
    }
</style>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <h1><i class="fas fa-envelope me-3"></i>Get In Touch</h1>
        <p>We're here to help! Reach out to us for any questions, support, or inquiries</p>
    </div>
</section>

<!-- Quick Contact Cards -->
<section class="quick-contact-section">
    <div class="container">
        <div class="contact-cards-grid">
            <div class="contact-quick-card">
                <div class="quick-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <h3>Call Us</h3>
                <p>Speak directly with our team</p>
                <a href="tel:+919879235475">
                    +91 98792 35475 <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            
            <div class="contact-quick-card">
                <div class="quick-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email Us</h3>
                <p>Send us a detailed message</p>
                <a href="mailto:aakashjamnagar@gmail.com">
                    aakashjamnagar@gmail.com <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            
            <div class="contact-quick-card">
                <div class="quick-icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <h3>WhatsApp</h3>
                <p>Quick chat with us</p>
                <a href="https://wa.me/919879235475" target="_blank">
                    Start Chat <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            
            <div class="contact-quick-card">
                <div class="quick-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>Visit Us</h3>
                <p>Come to our office</p>
                <a href="#map-section">
                    View Location <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<?php if ($message): ?>
<div class="container">
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<!-- Main Contact Section -->
<section class="main-contact-section">
    <div class="container">
        <div class="contact-container">
            <!-- Contact Info Side -->
            <div class="contact-info-side">
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Contact Information</h3>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Address</h4>
                            <p>Akash Enterprise,<br>Jamnagar, Gujarat, India</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <h4>Phone</h4>
                            <p><a href="tel:+919879235475">+91 98792 35475</a></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h4>Email</h4>
                            <p><a href="mailto:aakashjamnagar@gmail.com">aakashjamnagar@gmail.com</a></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="info-content">
                            <h4>Website</h4>
                            <p><a href="http://www.akashent.com" target="_blank">www.akashent.com</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-clock"></i> Working Hours</h3>
                    <ul class="hours-list">
                        <li>
                            <strong>Monday - Friday</strong>
                            <span>10:00 AM - 9:00 PM</span>
                        </li>
                        <li>
                            <strong>Saturday</strong>
                            <span>10:00 AM - 9:00 PM</span>
                        </li>
                        <li>
                            <strong>Sunday</strong>
                            <span>Closed</span>
                        </li>
                        <li>
                            <strong>Emergency Service</strong>
                            <span>24/7 Available</span>
                        </li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-share-alt"></i> Follow Us</h3>
                    <p style="color: #64748b; margin-bottom: 20px;">Stay connected on social media</p>
                    <div class="social-media">
                        <a href="#" class="social-link facebook" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link twitter" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link instagram" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link linkedin" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://wa.me/919879235475" class="social-link whatsapp" aria-label="Chat on WhatsApp with +91 98792 35475">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form Side -->
            <div class="contact-form-side">
                <h2>Send Us a Message</h2>
                <p class="subtitle">Fill out the form below and we'll respond as soon as possible</p>
                
                <form method="POST" action="" id="contactForm" class="contact-form">
                    <div class="form-group">
                        <label>Your Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter your full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="your.email@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="tel" name="phone" class="form-control" required placeholder="10-digit mobile number" pattern="[0-9]{10}" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Subject <span class="required">*</span></label>
                        <select name="subject" class="form-control" required>
                            <option value="">Select a subject...</option>
                            <option value="Product Inquiry">Product Inquiry</option>
                            <option value="Service Request">Service Request</option>
                            <option value="Technical Support">Technical Support</option>
                            <option value="Sales & Pricing">Sales & Pricing</option>
                            <option value="Complaint">Complaint</option>
                            <option value="Feedback">Feedback</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Your Message <span class="required">*</span></label>
                        <textarea name="message" class="form-control" required placeholder="Tell us how we can help you..."><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_contact" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section" id="map-section">
    <div class="map-container">
        <!-- Google Maps Embed for Akash Enterprise, Jamnagar -->
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3720.863338495676!2d70.0529878!3d22.4848749!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39574001f272cd05%3A0xda450341fdac961b!2s10%2C+Pandit+Nehru+Rd%2C+opp.+OM+TVS%2C+Patel+Colony%2C+Jamnagar%2C+Gujarat+361008!5e0!3m2!1sen!2sin!4v1731234567890"
            width="100%" 
            height="600" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade"
            title="Akash Enterprise Location - 10, Pandit Nehru Rd, opp. OM TVS, Patel Colony, Jamnagar, Gujarat 361008">
        </iframe>
    </div>
    
    <!-- Map Actions -->
    <div class="map-actions">
        <div class="map-actions-wrapper">
            <div class="map-address-info">
                <h4><i class="fas fa-map-marker-alt me-2" style="color: #3b82f6;"></i>Our Location</h4>
                <p>10, Pandit Nehru Rd, opp. OM TVS, Patel Colony, Jamnagar, Gujarat 361008</p>
            </div>
            <a href="https://www.google.com/maps/dir/?api=1&destination=10,+Pandit+Nehru+Rd,+opp.+OM+TVS,+Patel+Colony,+Jamnagar,+Gujarat+361008" 
               target="_blank" 
               rel="noopener noreferrer"
               class="btn btn-primary">
                <i class="fas fa-directions"></i>
                <span>Get Directions</span>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="section-header" style="text-align: center; color: #1e293b;">
            <h2>Frequently Asked Questions</h2>
            <p style="color: #64748b;">Quick answers to common questions</p>
        </div>
        
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What are your service charges?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Our service charges vary depending on the type of service and AC unit. Basic service visits start from ₹500, while installation and repair costs depend on the specific requirements. Contact us for a detailed quote.
                    </div>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Do you provide emergency services?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Yes, we offer 24/7 emergency services for urgent AC issues. Call our emergency hotline, and our technicians will reach you as soon as possible.
                    </div>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What brands do you service?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        We service all major AC brands including LG, Samsung, Daikin, Voltas, Hitachi, Blue Star, Carrier, and more. Our technicians are trained to work with all types of air conditioning systems.
                    </div>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How long does installation take?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Standard split AC installation typically takes 4-6 hours. Window AC installation takes 2-3 hours. Commercial installations may take longer depending on the system complexity.
                    </div>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Do you offer warranty on services?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Yes, all our services come with a warranty. Installation services have 1-year warranty on workmanship, repairs have 90-day warranty on parts and labor, and AMC plans include warranty coverage throughout the contract period.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// FAQ Toggle Function
function toggleFaq(element) {
    const faqItem = element.parentElement;
    const isActive = faqItem.classList.contains('active');
    
    // Close all FAQ items
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked item if it wasn't active
    if (!isActive) {
        faqItem.classList.add('active');
    }
}

// Form Validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const phone = this.querySelector('input[name="phone"]').value;
    
    if (phone.length !== 10 || !/^\d+$/.test(phone)) {
        e.preventDefault();
        alert('Please enter a valid 10-digit phone number');
        return false;
    }
});

// Auto-dismiss alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Smooth scroll to map
document.querySelectorAll('a[href="#map-section"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('map-section').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
});

// Smooth scroll to contact form
document.querySelectorAll('a[href="#contactForm"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('contactForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
        // Add a subtle highlight effect to the form
        const form = document.getElementById('contactForm');
        form.style.transition = 'box-shadow 0.3s ease';
        form.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.3)';
        
        setTimeout(() => {
            form.style.boxShadow = '';
        }, 2000);
    });
});
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
