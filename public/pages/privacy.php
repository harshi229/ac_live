<?php
// Set page metadata
$pageTitle = 'Privacy Policy';
$pageDescription = 'Privacy Policy for Akash Enterprise - How we collect, use, and protect your personal information';
$pageKeywords = 'privacy, policy, data protection, personal information, GDPR';

require_once __DIR__ . '/../../includes/config/init.php';
require_once __DIR__ . '/../../includes/templates/header.php';
?>

<style>
/* Privacy Policy Page Styles */
.privacy-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-top: 40px;
    margin-bottom: 40px;
}

.privacy-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 2px solid #e5e7eb;
}

.privacy-header h1 {
    color: #1e293b;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.privacy-header p {
    color: #64748b;
    font-size: 1.1rem;
    margin: 0;
}

.privacy-content {
    line-height: 1.8;
    color: #374151;
}

.privacy-content h2 {
    color: #1e293b;
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 40px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}

.privacy-content h3 {
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 30px;
    margin-bottom: 15px;
}

.privacy-content p {
    margin-bottom: 20px;
    text-align: justify;
}

.privacy-content ul, .privacy-content ol {
    margin-bottom: 20px;
    padding-left: 30px;
}

.privacy-content li {
    margin-bottom: 10px;
}

.privacy-content strong {
    color: #1e293b;
    font-weight: 600;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.data-table th {
    background: #3b82f6;
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.data-table tr:hover {
    background: #f8fafc;
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
    .privacy-container {
        margin: 20px 10px;
        padding: 30px 15px;
    }
    
    .privacy-header h1 {
        font-size: 2rem;
    }
    
    .privacy-content h2 {
        font-size: 1.3rem;
    }
    
    .privacy-content h3 {
        font-size: 1.1rem;
    }
    
    .data-table {
        font-size: 0.9rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 10px;
    }
}
</style>

<div class="privacy-container">
    <div class="privacy-header">
        <h1>Privacy Policy</h1>
        <p>Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
    </div>

    <div class="privacy-content">
        <h2>1. Introduction</h2>
        <p>Akash Enterprise ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our services.</p>

        <h2>2. Information We Collect</h2>
        <h3>2.1 Personal Information</h3>
        <p>We may collect the following types of personal information:</p>
        <ul>
            <li><strong>Contact Information:</strong> Name, email address, phone number, and mailing address</li>
            <li><strong>Account Information:</strong> Username, password, and account preferences</li>
            <li><strong>Service Information:</strong> Installation addresses, service history, and maintenance records</li>
            <li><strong>Payment Information:</strong> Billing address and payment method details (processed securely)</li>
            <li><strong>Communication Records:</strong> Customer service interactions and feedback</li>
        </ul>

        <h3>2.2 Technical Information</h3>
        <p>We automatically collect certain technical information when you use our website:</p>
        <ul>
            <li>IP address and device information</li>
            <li>Browser type and version</li>
            <li>Operating system</li>
            <li>Pages visited and time spent on our site</li>
            <li>Referring website information</li>
            <li>Cookies and similar tracking technologies</li>
        </ul>

        <h2>3. How We Use Your Information</h2>
        <p>We use your personal information for the following purposes:</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Purpose</th>
                    <th>Information Used</th>
                    <th>Legal Basis</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Service Delivery</td>
                    <td>Contact info, service details</td>
                    <td>Contract performance</td>
                </tr>
                <tr>
                    <td>Account Management</td>
                    <td>Account credentials, preferences</td>
                    <td>Contract performance</td>
                </tr>
                <tr>
                    <td>Customer Support</td>
                    <td>Contact info, service history</td>
                    <td>Legitimate interest</td>
                </tr>
                <tr>
                    <td>Marketing Communications</td>
                    <td>Contact info, preferences</td>
                    <td>Consent</td>
                </tr>
                <tr>
                    <td>Website Improvement</td>
                    <td>Technical data, usage patterns</td>
                    <td>Legitimate interest</td>
                </tr>
                <tr>
                    <td>Legal Compliance</td>
                    <td>All relevant information</td>
                    <td>Legal obligation</td>
                </tr>
            </tbody>
        </table>

        <h2>4. Information Sharing and Disclosure</h2>
        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following circumstances:</p>

        <h3>4.1 Service Providers</h3>
        <p>We may share information with trusted third-party service providers who assist us in:</p>
        <ul>
            <li>Payment processing</li>
            <li>Email communications</li>
            <li>Website hosting and maintenance</li>
            <li>Customer support services</li>
            <li>Analytics and marketing</li>
        </ul>

        <h3>4.2 Legal Requirements</h3>
        <p>We may disclose your information if required by law or in response to:</p>
        <ul>
            <li>Legal processes or court orders</li>
            <li>Government investigations</li>
            <li>Protection of our rights and property</li>
            <li>Safety of our users and the public</li>
        </ul>

        <h2>5. Data Security</h2>
        <p>We implement appropriate technical and organizational measures to protect your personal information:</p>
        <ul>
            <li><strong>Encryption:</strong> Data is encrypted in transit and at rest</li>
            <li><strong>Access Controls:</strong> Limited access to personal information on a need-to-know basis</li>
            <li><strong>Regular Audits:</strong> Security assessments and vulnerability testing</li>
            <li><strong>Staff Training:</strong> Regular privacy and security training for employees</li>
            <li><strong>Incident Response:</strong> Procedures for handling security breaches</li>
        </ul>

        <h2>6. Data Retention</h2>
        <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy:</p>
        <ul>
            <li><strong>Account Information:</strong> Until account closure plus 3 years</li>
            <li><strong>Service Records:</strong> 7 years for warranty and legal purposes</li>
            <li><strong>Marketing Data:</strong> Until consent is withdrawn</li>
            <li><strong>Technical Data:</strong> Up to 2 years for analytics purposes</li>
        </ul>

        <h2>7. Your Rights and Choices</h2>
        <p>You have the following rights regarding your personal information:</p>

        <h3>7.1 Access and Portability</h3>
        <ul>
            <li>Request access to your personal information</li>
            <li>Receive a copy of your data in a portable format</li>
            <li>Verify the accuracy of your information</li>
        </ul>

        <h3>7.2 Correction and Updates</h3>
        <ul>
            <li>Correct inaccurate or incomplete information</li>
            <li>Update your account preferences</li>
            <li>Modify your communication preferences</li>
        </ul>

        <h3>7.3 Deletion and Restriction</h3>
        <ul>
            <li>Request deletion of your personal information</li>
            <li>Restrict processing of your data</li>
            <li>Object to certain uses of your information</li>
        </ul>

        <h3>7.4 Marketing Communications</h3>
        <ul>
            <li>Opt-out of marketing emails and communications</li>
            <li>Unsubscribe from newsletters and promotional materials</li>
            <li>Manage your communication preferences</li>
        </ul>

        <h2>8. Cookies and Tracking Technologies</h2>
        <p>We use cookies and similar technologies to enhance your experience:</p>

        <h3>8.1 Types of Cookies</h3>
        <ul>
            <li><strong>Essential Cookies:</strong> Required for website functionality</li>
            <li><strong>Performance Cookies:</strong> Help us understand website usage</li>
            <li><strong>Functional Cookies:</strong> Remember your preferences</li>
            <li><strong>Marketing Cookies:</strong> Used for targeted advertising (with consent)</li>
        </ul>

        <h3>8.2 Cookie Management</h3>
        <p>You can control cookies through your browser settings. However, disabling certain cookies may affect website functionality.</p>

        <h2>9. Third-Party Links</h2>
        <p>Our website may contain links to third-party websites. We are not responsible for the privacy practices of these external sites. We encourage you to review their privacy policies before providing any personal information.</p>

        <h2>10. Children's Privacy</h2>
        <p>Our services are not directed to children under 13 years of age. We do not knowingly collect personal information from children under 13. If we become aware that we have collected such information, we will take steps to delete it promptly.</p>

        <h2>11. International Data Transfers</h2>
        <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place to protect your information during such transfers.</p>

        <h2>12. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. We will notify you of any material changes by:</p>
        <ul>
            <li>Posting the updated policy on our website</li>
            <li>Sending email notifications to registered users</li>
            <li>Displaying prominent notices on our website</li>
        </ul>

        <h2>13. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>

        <div class="contact-info">
            <h3>Data Protection Officer</h3>
            <p><strong>Akash Enterprise</strong></p>
            <p><strong>Address:</strong> [Your Business Address]</p>
            <p><strong>Phone:</strong> <a href="tel:+919879235475">+91 98792 35475</a></p>
            <p><strong>Email:</strong> <a href="mailto:aakashjamnagar@gmail.com">aakashjamnagar@gmail.com</a></p>
            <p><strong>Website:</strong> <a href="<?php echo BASE_URL; ?>"><?php echo BASE_URL; ?></a></p>
        </div>

        <h2>14. Complaints</h2>
        <p>If you believe we have not handled your personal information in accordance with this policy, you have the right to lodge a complaint with the relevant data protection authority in your jurisdiction.</p>
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
