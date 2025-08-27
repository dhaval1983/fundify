<?php
session_start();

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$user = new User();

if ($user->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'role' => $_POST['role'] ?? '',
        'agree_terms' => isset($_POST['agree_terms'])
    ];
    
    if (empty($formData['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (empty($formData['password']) || strlen($formData['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!in_array($formData['role'], ['entrepreneur', 'investor', 'browser'])) {
        $errors[] = 'Please select a valid role';
    }
    
    if (!$formData['agree_terms']) {
        $errors[] = 'You must agree to the terms and conditions';
    }
    
    if (empty($errors)) {
        $result = $user->register($formData);
        
        if ($result['success']) {
            $success = $result['message'];
            $formData = [];
        } else {
            $errors = $result['errors'];
        }
    }
}

$selectedRole = $_GET['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Fundify</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container {
            max-width: 600px; margin: 0 auto; background: white;
            border-radius: 15px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; font-size: 2em; margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input {
            width: auto; padding: 12px; border: 2px solid #ddd; border-radius: 8px;
            font-size: 16px; transition: border-color 0.3s;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .role-selection { display: none; }
        .role-option { display: none; }
        .btn {
            width: 100%; padding: 15px; background: linear-gradient(45deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 8px; font-size: 16px;
            font-weight: bold; cursor: pointer; transition: transform 0.3s;
        }
        .btn:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin: 20px 0; }
        .checkbox-group input { width: auto; }
        .login-link { text-align: center; margin-top: 20px; }
        .login-link a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Join Fundify</h1>
            <p>Connect entrepreneurs with investors</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix these errors:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>I am a: *</label>
                <div style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; margin-bottom: 15px; font-weight: normal; cursor: pointer; padding: 12px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="role" value="entrepreneur" <?php echo ($selectedRole === 'entrepreneur' || ($formData['role'] ?? '') === 'entrepreneur') ? 'checked' : ''; ?> style="margin-right: 12px; transform: scale(1.2);">
                        <span style="font-size: 1.2em; margin-right: 8px;">🚀</span>
                        <span><strong>Entrepreneur</strong> - Looking for investors</span>
                    </label>
                    <label style="display: flex; align-items: center; margin-bottom: 15px; font-weight: normal; cursor: pointer; padding: 12px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="role" value="investor" <?php echo ($selectedRole === 'investor' || ($formData['role'] ?? '') === 'investor') ? 'checked' : ''; ?> style="margin-right: 12px; transform: scale(1.2);">
                        <span style="font-size: 1.2em; margin-right: 8px;">💰</span>
                        <span><strong>Investor</strong> - Looking for opportunities</span>
                    </label>
                    <label style="display: flex; align-items: center; margin-bottom: 15px; font-weight: normal; cursor: pointer; padding: 12px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="role" value="browser" <?php echo ($formData['role'] ?? '') === 'browser' ? 'checked' : ''; ?> style="margin-right: 12px; transform: scale(1.2);">
                        <span style="font-size: 1.2em; margin-right: 8px;">👀</span>
                        <span><strong>Browser</strong> - Just exploring</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+91 9876543210"
                       value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
                <small style="color: #666;">Minimum 8 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="agree_terms" name="agree_terms">
                <label for="agree_terms">I agree to the Terms of Service</label>
            </div>
            
            <button type="submit" class="btn">Create My Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.role-option').forEach(option => option.classList.remove('selected'));
                this.closest('.role-option').classList.add('selected');
            });
        });
    </script>
</body>
</html>