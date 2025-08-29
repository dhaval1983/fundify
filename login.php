<?php
// login.php - User Login System
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

// Check if user is already logged in
if ($user->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = $user->login($email, $password, $remember);
        
        if ($result['success']) {
            // Redirect to dashboard or intended page
            $redirectTo = $_GET['redirect'] ?? 'dashboard.php';
            header("Location: $redirectTo");
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Check for flash messages (like from registration)
$flashMessage = Utils::getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In to Fundify</title>
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
        
        .login-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 50px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .form-container {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }
        
        .remember-me input[type="checkbox"] {
            width: auto;
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            color: #666;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
        }
        
        .register-link {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .demo-accounts {
            background: #f8f9ff;
            border: 1px solid #e0e8ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .demo-accounts h4 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .demo-account {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 0.85em;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .demo-account:hover {
            background: #f0f4ff;
        }
        
        .demo-account:last-child {
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                margin: 10px;
            }
            
            .header {
                padding: 40px 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .form-container {
                padding: 30px 20px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h1>🔐 Welcome Back</h1>
            <p>Sign in to your Fundify account</p>
        </div>
        
        <div class="form-container">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                    <?php echo htmlspecialchars($flashMessage['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <strong>Login Failed:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn">Sign In</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </div>
    </div>
    
    <script>
        
        
        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing In...';
            
            // Re-enable button after 3 seconds (in case of errors)
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }, 3000);
        });
        
        // Remove demo accounts section in production
        // Comment out or remove this in production environment
        console.log('Demo accounts are active. Remove in production!');
        
        // Focus on email field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (!emailField.value) {
                emailField.focus();
            }
        });
        
        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const form = document.getElementById('loginForm');
                if (document.activeElement.tagName === 'INPUT') {
                    form.submit();
                }
            }
        });
    </script>
</body>
</html>