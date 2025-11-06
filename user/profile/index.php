<?php
// Set page metadata
$pageTitle = 'My Profile';
$pageDescription = 'View and manage your profile information at Akash Enterprise';
$pageKeywords = 'profile, account, user information, personal details';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';
// Database connection now in init.php

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please login to view your profile.";
    exit();
}

// Fetch the user's information from the database
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Profile Page - Modern & Professional Design */

/* Hero Section */
.profile-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    padding: 120px 0 80px;
    overflow: hidden;
}

.profile-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.profile-hero .container {
    position: relative;
    z-index: 1;
    text-align: center;
    color: white;
}

.profile-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.profile-hero p {
    font-size: 1.3rem;
    color: #cbd5e1;
    max-width: 600px;
    margin: 0 auto;
}

/* Profile Content */
.profile-content {
    padding: 80px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
}

.profile-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(59, 130, 246, 0.1);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    padding: 40px;
    margin: 0 auto;
    max-width: 600px;
    position: relative;
    overflow: hidden;
}

.profile-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
}

.profile-card h2 {
    color: #1e293b;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
}

.profile-info {
    display: grid;
    gap: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 12px;
    border-left: 4px solid #3b82f6;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: rgba(59, 130, 246, 0.1);
    transform: translateX(5px);
}

.info-label {
    font-weight: 600;
    color: #374151;
    min-width: 120px;
    margin-right: 15px;
}

.info-value {
    color: #1f2937;
    font-weight: 500;
}

.profile-actions {
    margin-top: 30px;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-hero h1 {
        font-size: 2.5rem;
    }
    
    .profile-card {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .info-label {
        min-width: auto;
        margin-right: 0;
    }
}
</style>

<!-- Hero Section -->
<section class="profile-hero">
    <div class="container">
        <h1>My Profile</h1>
        <p>Manage your account information and preferences</p>
    </div>
</section>

<!-- Profile Content -->
<section class="profile-content">
    <div class="container">
        <div class="profile-card">
            <h2>Profile Information</h2>
            
            <div class="profile-info">
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['address']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone_number']); ?></span>
                </div>
            </div>
            
            <div class="profile-actions">
                <a href="edit.php" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
    </div>
</section>

<?php
include INCLUDES_PATH . '/templates/footer.php';
?>
