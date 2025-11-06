<?php
// Set page metadata
$pageTitle = 'Edit Profile';
$pageDescription = 'Update your profile information at Akash Enterprise';
$pageKeywords = 'edit profile, update account, user information, personal details';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';
// Database connection now in init.php

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please login to edit your profile.";
    exit();
}

// Fetch the user's information from the database
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Update user information if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];

    // Validate and update the user information
    $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, address = :address, phone_number = :phone_number WHERE id = :user_id");
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'address' => $address,
        'phone_number' => $phone_number,
        'user_id' => $user_id
    ]);

    $success_message = "Profile updated successfully!";
}
?>

<style>
/* Edit Profile Page - Modern & Professional Design */

/* Hero Section */
.edit-profile-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    padding: 120px 0 80px;
    overflow: hidden;
}

.edit-profile-hero::before {
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

.edit-profile-hero .container {
    position: relative;
    z-index: 1;
    text-align: center;
    color: white;
}

.edit-profile-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.edit-profile-hero p {
    font-size: 1.3rem;
    color: #cbd5e1;
    max-width: 600px;
    margin: 0 auto;
}

/* Form Content */
.edit-profile-content {
    padding: 80px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
}

.profile-form-card {
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

.profile-form-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
}

.profile-form-card h2 {
    color: #1e293b;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: white;
}

.form-actions {
    margin-top: 30px;
    text-align: center;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
    color: white;
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);
    color: white;
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(107, 114, 128, 0.3);
    color: white;
    text-decoration: none;
    display: inline-block;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #4b5563, #374151);
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(107, 114, 128, 0.4);
    color: white;
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    border: none;
    font-weight: 500;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #059669;
    border-left: 4px solid #22c55e;
}

/* Responsive Design */
@media (max-width: 768px) {
    .edit-profile-hero h1 {
        font-size: 2.5rem;
    }
    
    .profile-form-card {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        max-width: 200px;
    }
}
</style>

<!-- Hero Section -->
<section class="edit-profile-hero">
    <div class="container">
        <h1>Edit Profile</h1>
        <p>Update your personal information and preferences</p>
    </div>
</section>

<!-- Form Content -->
<section class="edit-profile-content">
    <div class="container">
        <div class="profile-form-card">
            <h2>Profile Information</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" 
                              name="address" 
                              class="form-control" 
                              rows="3" 
                              required><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="tel" 
                           id="phone_number" 
                           name="phone_number" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone_number']); ?>" 
                           required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
include INCLUDES_PATH . '/templates/footer.php';
?>
