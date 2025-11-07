<?php
// Set page metadata
$pageTitle = 'Terms and Conditions';
$pageDescription = 'Terms and Conditions for Akash Enterprise - Air Conditioning Solutions';
$pageKeywords = 'terms, conditions, legal, agreement, AC services';

require_once __DIR__ . '/../../includes/config/init.php';
require_once __DIR__ . '/../../includes/templates/header.php';
?>

<style>
/* Terms and Conditions Page Styles */
.terms-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-top: 40px;
    margin-bottom: 40px;
}

.terms-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 2px solid #e5e7eb;
}

.terms-header h1 {
    color: #1e293b;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.terms-header p {
    color: #64748b;
    font-size: 1.1rem;
    margin: 0;
}

.terms-content {
    line-height: 1.8;
    color: #374151;
}

.terms-content h2 {
    color: #1e293b;
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 40px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}

.terms-content h3 {
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 30px;
    margin-bottom: 15px;
}

.terms-content p {
    margin-bottom: 20px;
    text-align: justify;
}

.terms-content ul, .terms-content ol {
    margin-bottom: 20px;
    padding-left: 30px;
}

.terms-content li {
    margin-bottom: 10px;
}

.terms-content strong {
    color: #1e293b;
    font-weight: 600;
}

.contact-info {
    background: #f8fafc;
    padding: 30px;
    border-radius: 10px;
    margin-top: 40px;
    border-left: 4px solid #3b82f6;
}

.contact-info h3 {
    color: #1e293b;
    margin-bottom: 20px;
}

.contact-info p {
    margin-bottom: 10px;
}

.contact-info a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.contact-info a:hover {
    text-decoration: underline;
}

.last-updated {
    text-align: center;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #e5e7eb;
    color: #64748b;
    font-style: italic;
}

.back-link {
    text-align: center;
    margin-top: 30px;
}

.back-link a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    padding: 12px 24px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.back-link a:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .terms-container {
        margin: 20px 10px;
        padding: 30px 15px;
    }
    
    .terms-header h1 {
        font-size: 2rem;
    }
    
    .terms-content h2 {
        font-size: 1.3rem;
    }
    
    .terms-content h3 {
        font-size: 1.1rem;
    }
}
</style>

<div class="terms-container">
    <div class="terms-header">
        <h1>Terms and Conditions</h1>
        <p>Please read these terms and conditions carefully before using our services</p>
    </div>

    <div class="terms-content">
        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using the Akash Enterprise website and services, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

        <h2>2. Description of Service</h2>
        <p>Akash Enterprise provides air conditioning solutions including but not limited to:</p>
        <ul>
            <li>Sales of residential and commercial air conditioning units</li>
            <li>Installation and maintenance services</li>
            <li>Annual Maintenance Contracts (AMC)</li>
            <li>Repair and servicing of AC units</li>
            <li>Consultation and technical support</li>
        </ul>

        <h2>3. User Accounts</h2>
        <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for:</p>
        <ul>
            <li>Safeguarding the password and all activities under your account</li>
            <li>Notifying us immediately of any unauthorized use of your account</li>
            <li>Ensuring your account information remains accurate and up-to-date</li>
        </ul>

        <h2>4. Product Information and Pricing</h2>
        <p>We strive to provide accurate product information and pricing on our website. However:</p>
        <ul>
            <li>Product specifications and features may vary from descriptions</li>
            <li>Prices are subject to change without notice</li>
            <li>All prices are in Indian Rupees (₹) unless otherwise specified</li>
            <li>Final pricing may include applicable taxes and installation charges</li>
        </ul>

        <h2>5. Orders and Payment</h2>
        <h3>5.1 Order Processing</h3>
        <p>All orders are subject to acceptance and availability. We reserve the right to refuse or cancel any order for any reason, including but not limited to:</p>
        <ul>
            <li>Product availability</li>
            <li>Errors in product description or pricing</li>
            <li>Suspected fraudulent activity</li>
        </ul>

        <h3>5.2 Payment Terms</h3>
        <p>We accept various payment methods including:</p>
        <ul>
            <li>Cash on Delivery (COD)</li>
            <li>Credit/Debit Cards</li>
            <li>Net Banking</li>
            <li>UPI Payments</li>
        </ul>
        <p>Payment must be made in full before delivery unless otherwise agreed upon.</p>

        <h2>6. Delivery and Installation</h2>
        <p>Delivery and installation services are subject to:</p>
        <ul>
            <li>Availability of our service team</li>
            <li>Site conditions and accessibility</li>
            <li>Customer availability for installation</li>
            <li>Weather conditions for outdoor installations</li>
        </ul>
        <p>We will make reasonable efforts to deliver and install products within the estimated timeframe.</p>

        <h2>7. Warranty and Returns</h2>
        <h3>7.1 Product Warranty</h3>
        <p>All products come with manufacturer warranty as specified. Our services include:</p>
        <ul>
            <li>Installation warranty for workmanship</li>
            <li>Support for warranty claims</li>
            <li>Maintenance services as per AMC terms</li>
        </ul>

        <h3>7.2 Return Policy</h3>
        <p>Returns are accepted under the following conditions:</p>
        <ul>
            <li>Products must be in original condition</li>
            <li>Return request must be made within 7 days of delivery</li>
            <li>Installation charges are non-refundable</li>
            <li>Custom or special order items cannot be returned</li>
        </ul>

        <h2>8. Limitation of Liability</h2>
        <p>To the maximum extent permitted by law, Akash Enterprise shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your use of our services.</p>

        <h2>9. Privacy and Data Protection</h2>
        <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of our services, to understand our practices.</p>

        <h2>10. Intellectual Property</h2>
        <p>The service and its original content, features, and functionality are and will remain the exclusive property of Akash Enterprise and its licensors. The service is protected by copyright, trademark, and other laws.</p>

        <h2>11. Termination</h2>
        <p>We may terminate or suspend your account and bar access to the service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of the Terms.</p>

        <h2>12. Governing Law</h2>
        <p>These Terms shall be interpreted and governed by the laws of India. Any disputes arising from these terms shall be subject to the jurisdiction of the courts in Gujarat, India.</p>

        <h2>13. Changes to Terms</h2>
        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days notice prior to any new terms taking effect.</p>

        <h2>14. Contact Information</h2>
        <p>If you have any questions about these Terms and Conditions, please contact us:</p>

        <div class="contact-info">
            <h3>Akash Enterprise</h3>
            <p><strong>Address:</strong> [Your Business Address]</p>
            <p><strong>Phone:</strong> <a href="tel:+919879235475">+91 98792 35475</a></p>
            <p><strong>Email:</strong> <a href="mailto:aakashjamnagar@gmail.com">aakashjamnagar@gmail.com</a></p>
            <p><strong>Website:</strong> <a href="<?php echo BASE_URL; ?>"><?php echo BASE_URL; ?></a></p>
        </div>
    </div>

    <div class="last-updated">
        <p>Last updated: <?php echo date('F d, Y'); ?></p>
    </div>

    <div class="back-link">
        <a href="<?php echo BASE_URL; ?>/index.php">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/templates/footer.php'; ?>
