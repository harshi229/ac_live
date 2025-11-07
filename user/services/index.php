<?php
// Set page metadata
$pageTitle = 'Our Services';
$pageDescription = 'Professional AC installation, maintenance, repair, and AMC services - Your complete cooling solution partner';
$pageKeywords = 'AC installation, AC repair, AC maintenance, AMC services, air conditioner service, AC cleaning';

require_once __DIR__ . '/../../includes/config/init.php';
require_once INCLUDES_PATH . '/functions/email_helpers.php';
include INCLUDES_PATH . '/templates/header.php';
// Database connection now in init.php

// Handle service request submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_service_request'])) {
    try {
        $customer_name = trim($_POST['customer_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $service_type = $_POST['service_type'];
        $preferred_date = $_POST['preferred_date'];
        $preferred_time = $_POST['preferred_time'];
        $address = trim($_POST['address']);
        $description = trim($_POST['description']);
        
        // For now, store in inquiries table (you can create a service_requests table if needed)
        $stmt = $pdo->prepare("INSERT INTO inquiries (customer_name, phone, email, requirements, status, created_at) 
                               VALUES (?, ?, ?, ?, 'New', NOW())");
        
        $requirements = "Service Type: $service_type\nPreferred Date: $preferred_date\nPreferred Time: $preferred_time\nAddress: $address\nDescription: $description";
        
        $stmt->execute([$customer_name, $phone, $email, $requirements]);
        
        // Send email notification to admin
        $emailSubject = "New Service Request - " . $service_type;
        $emailMessage = "
        <h2>New Service Request Submitted</h2>
        <p><strong>Customer Name:</strong> " . htmlspecialchars($customer_name) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
        <p><strong>Service Type:</strong> " . htmlspecialchars($service_type) . "</p>
        <p><strong>Preferred Date:</strong> " . htmlspecialchars($preferred_date) . "</p>
        <p><strong>Preferred Time:</strong> " . htmlspecialchars($preferred_time) . "</p>
        <p><strong>Service Address:</strong></p>
        <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>
            " . nl2br(htmlspecialchars($address)) . "
        </div>
        <p><strong>Additional Details:</strong></p>
        <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>
            " . nl2br(htmlspecialchars($description)) . "
        </div>
        <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
        <hr>
        <p><em>This service request was submitted from your website.</em></p>
        ";
        
        // Send email to admin
        $adminEmail = 'aakashjamnagar@gmail.com'; // From email config
        $emailSent = sendEmail($adminEmail, $emailSubject, $emailMessage);
        
        if ($emailSent) {
            error_log("Service request email sent successfully to admin");
        } else {
            error_log("Failed to send service request email to admin");
        }
        
        $message = "Service request submitted successfully! We'll contact you soon.";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Error submitting request. Please try again.";
        $message_type = "danger";
        error_log("Service request database error: " . $e->getMessage());
    } catch (Exception $e) {
        $message = "Error submitting request. Please try again.";
        $message_type = "danger";
        error_log("Service request email error: " . $e->getMessage());
    }
}
?>

<style>
/* Services Page - Modern & Professional Design */

/* Hero Section */
.services-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    padding: 120px 0 80px;
    overflow: hidden;
}

.services-hero::before {
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

.services-hero .container {
    position: relative;
    z-index: 1;
    text-align: center;
    color: white;
}

.services-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #3b82f6, #22c55e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.services-hero p {
    font-size: 1.3rem;
    color: #cbd5e1;
    max-width: 800px;
    margin: 0 auto;
}

/* Main Services Grid */
.main-services {
    padding: 100px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    margin-top: 60px;
}

.service-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #3b82f6, #22c55e);
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.service-card:hover::before {
    transform: scaleX(1);
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(59, 130, 246, 0.2);
    border-color: #3b82f6;
}

.service-icon-wrapper {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.service-icon-wrapper::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0%, 100% { transform: translate(-50%, -50%) scale(0); }
    50% { transform: translate(-50%, -50%) scale(1); }
}

.service-icon-wrapper i {
    font-size: 2.5rem;
    color: white;
    position: relative;
    z-index: 1;
}

.service-icon-wrapper img {
    width: 50px;
    height: 50px;
    filter: brightness(0) invert(1);
    position: relative;
    z-index: 1;
}

.service-card h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

.service-card p {
    font-size: 1.05rem;
    color: #64748b;
    line-height: 1.7;
    margin-bottom: 25px;
}

.service-features {
    list-style: none;
    padding: 0;
    margin: 0 0 25px 0;
}

.service-features li {
    padding: 10px 0;
    color: #475569;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.service-features li i {
    color: #22c55e;
    font-size: 0.9rem;
}

.service-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 30px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.service-btn:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
    color: white;
}

/* Process Section */
.process-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
}

.section-header {
    text-align: center;
    margin-bottom: 60px;
}

.section-header h2 {
    font-size: 2.8rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.section-header p {
    font-size: 1.2rem;
    opacity: 0.9;
}

.process-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.process-step {
    text-align: center;
    position: relative;
}

.process-step::after {
    content: '';
    position: absolute;
    top: 50px;
    right: -15px;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, #3b82f6, transparent);
}

.process-step:last-child::after {
    display: none;
}

.step-number {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #3b82f6, #22c55e);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    font-size: 2.5rem;
    font-weight: 800;
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
}

.process-step h4 {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.process-step p {
    font-size: 0.95rem;
    color: #cbd5e1;
    line-height: 1.6;
}

/* Service Request Form */
.request-section {
    padding: 100px 0;
    background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
}

.request-form-container {
    max-width: 800px;
    margin: 60px auto 0;
    background: white;
    border-radius: 20px;
    padding: 50px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-group label .required {
    color: #ef4444;
    margin-left: 3px;
}

.services-form .form-control {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
}

.services-form .form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.services-form textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    border-radius: 10px;
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
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}

/* AMC Section */
.amc-section {
    padding: 100px 0;
    background: white;
}

.amc-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.amc-plan {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 40px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
    text-align: center;
}

.amc-plan:hover {
    transform: translateY(-10px);
    border-color: #3b82f6;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

.amc-plan.featured {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    transform: scale(1.05);
}

.amc-plan.featured h3,
.amc-plan.featured .price,
.amc-plan.featured ul li {
    color: white;
}

.plan-badge {
    display: inline-block;
    background: #22c55e;
    color: white;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 20px;
}

.amc-plan h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: #1e293b;
}

.price {
    font-size: 2.5rem;
    font-weight: 800;
    color: #3b82f6;
    margin-bottom: 10px;
}

.amc-plan.featured .price {
    color: white;
}

.price-duration {
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 30px;
}

.amc-plan.featured .price-duration {
    color: rgba(255, 255, 255, 0.9);
}

.amc-plan ul {
    list-style: none;
    padding: 0;
    margin: 0 0 30px 0;
    text-align: left;
}

.amc-plan ul li {
    padding: 12px 0;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 10px;
}

.amc-plan ul li i {
    color: #22c55e;
}

.amc-plan.featured ul li i {
    color: white;
}

.plan-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 40px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.amc-plan.featured .plan-btn {
    background: white;
    color: #3b82f6;
}

.plan-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    color: white;
}

.amc-plan.featured .plan-btn:hover {
    color: #3b82f6;
}

/* Responsive Design */
@media (max-width: 992px) {
    .services-grid,
    .amc-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .process-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .process-step::after {
        display: none;
    }
}

@media (max-width: 768px) {
    .services-hero h1 {
        font-size: 2.5rem;
    }
    
    .services-grid,
    .amc-grid,
    .process-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .request-form-container {
        padding: 30px 20px;
    }
    
    .amc-plan.featured {
        transform: scale(1);
    }
}
</style>

<!-- Hero Section -->
<section class="services-hero">
    <div class="container">
        <h1><i class="fas fa-tools me-3"></i>Our Services</h1>
        <p>Comprehensive AC solutions from installation to maintenance - Your comfort is our commitment</p>
    </div>
</section>

<?php if ($message): ?>
<div class="container mt-4">
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<!-- Main Services -->
<section class="main-services">
    <div class="container">
        <div class="section-header" style="color: #1e293b;">
            <h2>What We Offer</h2>
            <p style="color: #64748b;">Professional services tailored to your cooling needs</p>
        </div>
        
        <div class="services-grid">
            <!-- Installation Service -->
            <div class="service-card">
                <div class="service-icon-wrapper">
                    <i class="fas fa-wrench"></i>
                </div>
                <h3>Installation Services</h3>
                <p>Expert installation of all types of air conditioning systems with precision and care.</p>
                <ul class="service-features">
                    <li><i class="fas fa-check-circle"></i> Residential & Commercial Installation</li>
                    <li><i class="fas fa-check-circle"></i> Professional Site Assessment</li>
                    <li><i class="fas fa-check-circle"></i> Proper Sizing & Positioning</li>
                    <li><i class="fas fa-check-circle"></i> Quality Guarantee</li>
                    <li><i class="fas fa-check-circle"></i> Post-Installation Support</li>
                </ul>
                <a href="#request-form" class="service-btn">
                    Request Service <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Repair Service -->
            <div class="service-card">
                <div class="service-icon-wrapper">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>Repair & Troubleshooting</h3>
                <p>Quick and efficient repair services to get your AC running smoothly again.</p>
                <ul class="service-features">
                    <li><i class="fas fa-check-circle"></i> 24/7 Emergency Support</li>
                    <li><i class="fas fa-check-circle"></i> Comprehensive Diagnostics</li>
                    <li><i class="fas fa-check-circle"></i> Genuine Parts Replacement</li>
                    <li><i class="fas fa-check-circle"></i> Same-Day Service Available</li>
                    <li><i class="fas fa-check-circle"></i> 90-Day Repair Warranty</li>
                </ul>
                <a href="#request-form" class="service-btn">
                    Request Service <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Maintenance Service -->
            <div class="service-card">
                <div class="service-icon-wrapper">
                    <i class="fas fa-cog"></i>
                </div>
                <h3>Maintenance & Cleaning</h3>
                <p>Regular maintenance to ensure optimal performance and longevity of your AC.</p>
                <ul class="service-features">
                    <li><i class="fas fa-check-circle"></i> Deep Cleaning Service</li>
                    <li><i class="fas fa-check-circle"></i> Filter Replacement</li>
                    <li><i class="fas fa-check-circle"></i> Gas Refilling</li>
                    <li><i class="fas fa-check-circle"></i> Performance Optimization</li>
                    <li><i class="fas fa-check-circle"></i> Preventive Care</li>
                </ul>
                <a href="#request-form" class="service-btn">
                    Request Service <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- AMC Service -->
            <div class="service-card">
                <div class="service-icon-wrapper">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>AMC Plans</h3>
                <p>Annual Maintenance Contracts for hassle-free year-round AC care.</p>
                <ul class="service-features">
                    <li><i class="fas fa-check-circle"></i> Scheduled Maintenance Visits</li>
                    <li><i class="fas fa-check-circle"></i> Priority Service</li>
                    <li><i class="fas fa-check-circle"></i> Cost Savings</li>
                    <li><i class="fas fa-check-circle"></i> Free Consultations</li>
                    <li><i class="fas fa-check-circle"></i> Flexible Plans</li>
                </ul>
                <a href="#amc-plans" class="service-btn">
                    View Plans <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Consultation Service -->
            <div class="service-card">
                <div class="service-icon-wrapper">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Expert Consultation</h3>
                <p>Professional guidance to help you choose the perfect cooling solution.</p>
                <ul class="service-features">
                    <li><i class="fas fa-check-circle"></i> Free Site Survey</li>
                    <li><i class="fas fa-check-circle"></i> Cooling Load Calculation</li>
                    <li><i class="fas fa-check-circle"></i> Energy Efficiency Analysis</li>
                    <li><i class="fas fa-check-circle"></i> Budget Planning</li>
                    <li><i class="fas fa-check-circle"></i> Custom Recommendations</li>
                </ul>
                <a href="#request-form" class="service-btn">
                    Request Service <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Inspection Service -->
            <div class="service-card">
                <div class="service-icon-wrapper">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Inspection & Assessment</h3>
                <p>Thorough inspection to identify issues and optimize performance.</p>
                <ul class="service-features">
                    <li><i class="fas fa-check-circle"></i> Complete System Checkup</li>
                    <li><i class="fas fa-check-circle"></i> Performance Testing</li>
                    <li><i class="fas fa-check-circle"></i> Detailed Reports</li>
                    <li><i class="fas fa-check-circle"></i> Efficiency Recommendations</li>
                    <li><i class="fas fa-check-circle"></i> Safety Inspection</li>
                </ul>
                <a href="#request-form" class="service-btn">
                    Request Service <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="process-section">
    <div class="container">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Our simple 4-step service process</p>
        </div>
        
        <div class="process-grid">
            <div class="process-step">
                <div class="step-number">1</div>
                <h4>Request Service</h4>
                <p>Fill out our service request form or call us directly</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">2</div>
                <h4>Schedule Appointment</h4>
                <p>We'll confirm your preferred date and time</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">3</div>
                <h4>Expert Service</h4>
                <p>Our certified technician arrives and completes the work</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">4</div>
                <h4>Quality Check</h4>
                <p>We ensure everything is working perfectly</p>
            </div>
        </div>
    </div>
</section>

<!-- AMC Plans -->
<section class="amc-section" id="amc-plans">
    <div class="container">
        <div class="section-header" style="color: #1e293b;">
            <h2>Annual Maintenance Contract Plans</h2>
            <p style="color: #64748b;">Choose a plan that suits your needs and budget</p>
        </div>
        
        <div class="amc-grid">
            <!-- Basic Plan -->
            <div class="amc-plan">
                <h3>Basic Plan</h3>
                <div class="price">₹2,999</div>
                <div class="price-duration">per year / per unit</div>
                <ul>
                    <li><i class="fas fa-check"></i> 2 Service Visits</li>
                    <li><i class="fas fa-check"></i> Basic Cleaning</li>
                    <li><i class="fas fa-check"></i> Filter Cleaning</li>
                    <li><i class="fas fa-check"></i> General Checkup</li>
                    <li><i class="fas fa-check"></i> Phone Support</li>
                </ul>
                <a href="#request-form" class="plan-btn">
                    Choose Plan <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Premium Plan -->
            <div class="amc-plan featured">
                <span class="plan-badge">Most Popular</span>
                <h3>Premium Plan</h3>
                <div class="price">₹5,999</div>
                <div class="price-duration">per year / per unit</div>
                <ul>
                    <li><i class="fas fa-check"></i> 4 Service Visits</li>
                    <li><i class="fas fa-check"></i> Deep Cleaning</li>
                    <li><i class="fas fa-check"></i> Filter Replacement</li>
                    <li><i class="fas fa-check"></i> Gas Top-up (if needed)</li>
                    <li><i class="fas fa-check"></i> Priority Support</li>
                    <li><i class="fas fa-check"></i> 10% Discount on Repairs</li>
                </ul>
                <a href="#request-form" class="plan-btn">
                    Choose Plan <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Gold Plan -->
            <div class="amc-plan">
                <h3>Gold Plan</h3>
                <div class="price">₹8,999</div>
                <div class="price-duration">per year / per unit</div>
                <ul>
                    <li><i class="fas fa-check"></i> 6 Service Visits</li>
                    <li><i class="fas fa-check"></i> Premium Deep Cleaning</li>
                    <li><i class="fas fa-check"></i> Filter Replacement (2x)</li>
                    <li><i class="fas fa-check"></i> Gas Refilling Included</li>
                    <li><i class="fas fa-check"></i> 24/7 Priority Support</li>
                    <li><i class="fas fa-check"></i> 20% Discount on Repairs</li>
                    <li><i class="fas fa-check"></i> Free Emergency Visits</li>
                </ul>
                <a href="#request-form" class="plan-btn">
                    Choose Plan <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Service Request Form -->
<section class="request-section" id="request-form">
    <div class="container">
        <div class="section-header" style="color: #1e293b;">
            <h2>Request a Service</h2>
            <p style="color: #64748b;">Fill out the form below and we'll get back to you promptly</p>
        </div>
        
        <div class="request-form-container">
            <form method="POST" action="" class="services-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Service Type <span class="required">*</span></label>
                    <select name="service_type" class="form-control" required>
                        <option value="">Select a service...</option>
                        <option value="Installation">Installation</option>
                        <option value="Repair">Repair & Troubleshooting</option>
                        <option value="Maintenance">Maintenance & Cleaning</option>
                        <option value="AMC">AMC Plan</option>
                        <option value="Inspection">Inspection & Assessment</option>
                        <option value="Consultation">Expert Consultation</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Preferred Date <span class="required">*</span></label>
                        <input type="date" name="preferred_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Preferred Time</label>
                        <select name="preferred_time" class="form-control">
                            <option value="">Select time...</option>
                            <option value="Morning (9 AM - 12 PM)">Morning (9 AM - 12 PM)</option>
                            <option value="Afternoon (12 PM - 4 PM)">Afternoon (12 PM - 4 PM)</option>
                            <option value="Evening (4 PM - 7 PM)">Evening (4 PM - 7 PM)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Service Address <span class="required">*</span></label>
                    <textarea name="address" class="form-control" required placeholder="Enter your complete address"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Additional Details</label>
                    <textarea name="description" class="form-control" placeholder="Tell us more about your requirements or any specific issues..."></textarea>
                </div>
                
                <button type="submit" name="submit_service_request" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Service Request
                </button>
            </form>
        </div>
    </div>
</section>

<script>
// Smooth scroll to form
document.querySelectorAll('a[href="#request-form"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('request-form').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
