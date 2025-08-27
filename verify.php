<?php
// verify.php - Email Verification System
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

// Initialize User class
$user = new User();

$message = '';
$messageType = '';
$verified = false;

// Handle email verification
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    
    $result = $user->verifyEmail($token);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        $verified = true;
    } else {
        $message = $result['error'];
        $messageType = 'error';
    }
} else {
    $message = 'Invalid verification link. Please check your email for the correct verification link.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Fundify</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .verification-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 50px 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .status-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .status-icon.success {
            color: #28a745;
        }
        
        .status-icon.error {
            color: #dc3545;
        }
        
        .message {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #333;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.3s ease;
            margin: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        
        .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }
        
        .help-text a {
            color: #667eea;
            text-decoration: none;
        }
        
        .help-text a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .verification-container {
                margin: 10px;
            }
            
            .header {
                padding: 40px 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .status-icon {
                font-size: 3em;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="header">
            <h1>Email Verification</h1>
        </div>
        
        <div class="content">
            <?php if ($messageType === 'success'): ?>
                <div class="status-icon success">✅</div>
                <div class="message">
                    <strong>Email Verified Successfully!</strong><br>
                    Your account has been activated and you can now access all features of Fundify.
                </div>
                <a href="login.php" class="btn">Sign In Now</a>
                <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                
            <?php else: ?>
                <div class="status-icon error">❌</div>
                <div class="message">
                    <strong>Verification Failed</strong><br>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <a href="register.php" class="btn">Register Again</a>
                <a href="login.php" class="btn btn-secondary">Try Login</a>
            <?php endif; ?>
            
            <div class="help-text">
                <p><strong>Need Help?</strong></p>
                <p>
                    If you're having trouble with email verification, 
                    <a href="mailto:support@fundify.isowebtech.com">contact our support team</a> 
                    or <a href="resend-verification.php">resend verification email</a>.
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-redirect to login after successful verification
        <?php if ($verified): ?>
            setTimeout(function() {
                if (confirm('Email verified successfully! Would you like to sign in now?')) {
                    window.location.href = 'login.php';
                }
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>