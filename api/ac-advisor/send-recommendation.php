<?php
/**
 * AC Advisor Email Notification Endpoint
 * Sends user's answers and AC recommendation to admin email
 * No database storage required
 */

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['userData']) || !isset($data['recommendation'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Include necessary files
require_once __DIR__ . '/../../includes/config/init.php';
require_once INCLUDES_PATH . '/functions/email_helpers.php';

try {
    $userData = $data['userData'];
    $recommendation = $data['recommendation'];
    
    // Admin email address
    $adminEmail = 'aakashjamnagar@gmail.com';
    
    // Prepare email subject
    $subject = 'New AC Advisor Recommendation - ' . $recommendation['tons'] . ' Ton AC Recommended';
    
    // Prepare email message (HTML format)
    $emailMessage = generateACAdvisorEmail($userData, $recommendation);
    
    // Send email
    $emailSent = sendEmail($adminEmail, $subject, $emailMessage, true);
    
    if ($emailSent) {
        error_log("AC Advisor recommendation email sent successfully to admin");
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully'
        ]);
    } else {
        error_log("Failed to send AC Advisor recommendation email");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email'
        ]);
    }
    
} catch (Exception $e) {
    error_log("AC Advisor email error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Generate HTML email content for AC Advisor recommendation
 */
function generateACAdvisorEmail($userData, $recommendation) {
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AC Advisor Recommendation</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4; 
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white; 
                padding: 20px; 
                border-radius: 8px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            }
            .header { 
                text-align: center; 
                background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
                color: white; 
                padding: 30px; 
                border-radius: 8px 8px 0 0; 
                margin: -20px -20px 20px -20px; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px; 
            }
            .recommendation-box {
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                border: 2px solid #3b82f6;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
            }
            .recommendation-value {
                font-size: 3rem;
                font-weight: 800;
                color: #3b82f6;
                margin: 10px 0;
            }
            .details-section {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .details-section h3 {
                color: #1e293b;
                margin-top: 0;
                border-bottom: 2px solid #3b82f6;
                padding-bottom: 10px;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .detail-label {
                font-weight: 600;
                color: #475569;
            }
            .detail-value {
                color: #1e293b;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #dee2e6;
                color: #6c757d;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🤖 AC Advisor Recommendation</h1>
                <p>New recommendation submitted from website</p>
            </div>
            
            <div class="recommendation-box">
                <h2 style="margin: 0 0 10px 0; color: #1e293b;">Recommended AC Capacity</h2>
                <div class="recommendation-value">' . htmlspecialchars($recommendation['tons']) . ' Ton</div>
                <p style="margin: 10px 0; color: #64748b;">
                    <strong>BTU Rating:</strong> ' . number_format($recommendation['btu']) . ' BTU<br>
                    <strong>Room Area:</strong> ' . number_format($recommendation['area'], 1) . ' sq ft
                </p>
            </div>
            
            <div class="details-section">
                <h3>📋 Room Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Room Length:</span>
                    <span class="detail-value">' . htmlspecialchars($userData['length']) . ' feet</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Width:</span>
                    <span class="detail-value">' . htmlspecialchars($userData['width']) . ' feet</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ceiling Height:</span>
                    <span class="detail-value">' . htmlspecialchars($userData['height']) . ' feet</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Type:</span>
                    <span class="detail-value">' . htmlspecialchars($userData['roomType']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Number of Windows:</span>
                    <span class="detail-value">' . htmlspecialchars($userData['windows']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Floor Level:</span>
                    <span class="detail-value">' . htmlspecialchars($userData['floorLevel']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Sun Exposure:</span>
                    <span class="detail-value">' . htmlspecialchars($userData['sunExposure']) . '</span>
                </div>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
                <p style="margin: 0; color: #856404;">
                    <strong>📅 Submitted:</strong> ' . date('F j, Y \a\t g:i A') . '<br>
                    <strong>🌐 Source:</strong> AC Advisor Chat Interface
                </p>
            </div>
            
            <div class="footer">
                <p>This is an automated notification from the AC Advisor system.</p>
                <p>No action required - this is for your information only.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

