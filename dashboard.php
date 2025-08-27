<?php
// dashboard.php - User Dashboard
session_start();

// Load configuration and classes
require_once 'config/app.php';

// Simple autoloader for classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize classes
$user = new User();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    header('Location: login.php?redirect=dashboard.php');
    exit;
}

// Get current user data
$currentUser = $user->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// Check email verification
if (!$currentUser['email_verified']) {
    $message = "Please verify your email address to access all features. Check your inbox for the verification link.";
    $messageType = 'warning';
}

// Handle logout
if (isset($_GET['logout'])) {
    $user->logout();
    header('Location: index.php');
    exit;
}

// Get user stats (placeholder for now)
$userStats = [
    'profile_completion' => 75, // Calculate based on filled fields
    'total_views' => 0,
    'messages_count' => 0,
    'connections' => 0
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fundify</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9ff;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8em;
            font-weight: bold;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .welcome-title {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .role-entrepreneur {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .role-investor {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .role-browser {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }
        
        .alert-success {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .quick-actions h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #f8f9ff;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .progress-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .progress-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            color: #666;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .welcome-title {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">🚀 Fundify</div>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="browse.php">Browse</a>
                <a href="messages.php">Messages</a>
                <a href="profile.php">Profile</a>
                <a href="?logout=1" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="welcome-section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h1>
                    <p class="welcome-subtitle">Manage your account and grow your network</p>
                </div>
                <div class="role-badge role-<?php echo $currentUser['role']; ?>">
                    <?php 
                    $roleIcons = ['entrepreneur' => '🚀', 'investor' => '💰', 'browser' => '👀'];
                    echo $roleIcons[$currentUser['role']] ?? '';
                    ?>
                    <?php echo ucfirst($currentUser['role']); ?>
                </div>
            </div>
        </div>
        
        <?php if (!$currentUser['email_verified']): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Email Verification Required:</strong> 
                Please check your email and click the verification link to access all features. 
                Didn't receive it? <a href="resend-verification.php">Resend verification email</a>
            </div>
        <?php endif; ?>
        
        <!-- Stats Dashboard -->
        <div class="dashboard-grid">
            <div class="stats-card">
                <div class="stats-icon">👤</div>
                <div class="stats-number"><?php echo $userStats['profile_completion']; ?>%</div>
                <div class="stats-label">Profile Complete</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">👁️</div>
                <div class="stats-number"><?php echo $userStats['total_views']; ?></div>
                <div class="stats-label">Profile Views</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">💬</div>
                <div class="stats-number"><?php echo $userStats['messages_count']; ?></div>
                <div class="stats-label">Messages</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">🤝</div>
                <div class="stats-number"><?php echo $userStats['connections']; ?></div>
                <div class="stats-label">Connections</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <?php if ($currentUser['role'] === 'entrepreneur'): ?>
                    <a href="create-listing.php" class="btn btn-primary">
                        <span>📝</span> Create Business Listing
                    </a>
                    <a href="my-listings.php" class="btn btn-secondary">
                        <span>📋</span> Manage Listings
                    </a>
                    <a href="investor-interest.php" class="btn btn-info">
                        <span>👥</span> View Investor Interest
                    </a>
                    
                <?php elseif ($currentUser['role'] === 'investor'): ?>
                    <a href="browse.php" class="btn btn-primary">
                        <span>🔍</span> Browse Opportunities
                    </a>
                    <a href="saved-listings.php" class="btn btn-secondary">
                        <span>⭐</span> Saved Listings
                    </a>
                    <a href="investment-criteria.php" class="btn btn-info">
                        <span>⚙️</span> Set Investment Criteria
                    </a>
                    
                <?php else: ?>
                    <a href="browse.php" class="btn btn-primary">
                        <span>🔍</span> Browse Listings
                    </a>
                    <a href="upgrade-account.php" class="btn btn-success">
                        <span>⬆️</span> Upgrade Account
                    </a>
                <?php endif; ?>
                
                <a href="messages.php" class="btn btn-info">
                    <span>💬</span> Messages
                </a>
                <a href="profile.php" class="btn btn-secondary">
                    <span>👤</span> Edit Profile
                </a>
            </div>
        </div>
        
        <!-- Profile Completion -->
        <div class="progress-section">
            <h3>Complete Your Profile</h3>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $userStats['profile_completion']; ?>%;"></div>
            </div>
            <div class="progress-text">
                <?php echo $userStats['profile_completion']; ?>% complete - 
                <a href="profile.php" style="color: #667eea;">Add more details</a> to increase visibility
            </div>
        </div>
    </div>
    
    <script>
        // Simple notifications system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                max-width: 400px;
                animation: slideIn 0.3s ease;
            `;
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
        
        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Welcome message for new users
        <?php if (isset($_GET['welcome'])): ?>
            showNotification('Welcome to Fundify! Complete your profile to get started.', 'success');
        <?php endif; ?>
    </script>
</body>
</html>