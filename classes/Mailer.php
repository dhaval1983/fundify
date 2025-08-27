<?php

class Mailer {
    private $config;
    
    public function __construct() {
        $this->config = [
            'use_smtp' => false,  // Set to true to use SMTP, false for simple mail()
            'smtp_host' => 'mail.isowebtech.com',
            'smtp_port' => 587,
            'smtp_username' => 'info@fundify.isowebtech.com',
            'smtp_password' => 'info@fundify.isowebtech.com',
            'smtp_encryption' => 'ssl',
            'from_email' => 'info@fundify.isowebtech.com',
            'from_name' => 'Fundify Team'
        ];
    }
    
    public function send($to, $subject, $body, $isHtml = true) {
        try {
            if ($this->config['use_smtp']) {
                return $this->sendViaSMTP($to, $subject, $body, $isHtml);
            } else {
                return $this->sendViaSimpleMail($to, $subject, $body, $isHtml);
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendViaSimpleMail($to, $subject, $body, $isHtml = true) {
        // Build headers based on your working version
        $headers = 'From: ' . $this->config['from_email'] . "\r\n";
        $headers .= 'Reply-To: ' . $this->config['from_email'] . "\r\n";
        
        if ($isHtml) {
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        }
        
        $headers .= 'X-Mailer: PHP/' . phpversion();
        
        $result = mail($to, $subject, $body, $headers);
        
        if ($result) {
            error_log("Email sent via PHP mail() to: $to");
            return true;
        } else {
            error_log("Failed to send email via PHP mail() to: $to");
            return false;
        }
    }
    
    private function sendViaSMTP($to, $subject, $body, $isHtml = true) {
        // SMTP implementation placeholder - falls back to simple mail for now
        error_log("SMTP sending not implemented yet, falling back to simple mail");
        return $this->sendViaSimpleMail($to, $subject, $body, $isHtml);
    }
    
    public function sendVerificationEmail($email, $token, $userName = '') {
        $subject = 'Verify Your Fundify Account';
        $verificationUrl = "https://fundify.isowebtech.com/verify.php?token=" . $token;
        $userName = $userName ?: 'there';
        
        $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #e0e0e0; }
        .footer { background: #f8f9ff; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #666; }
        .btn { display: inline-block; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Fundify!</h1>
            <p>India\'s Premier Investor-Entrepreneur Marketplace</p>
        </div>
        
        <div class="content">
            <h2>Hi ' . htmlspecialchars($userName) . ',</h2>
            
            <p>Thank you for joining Fundify! Please verify your email address by clicking the button below:</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . htmlspecialchars($verificationUrl) . '" class="btn">Verify My Email</a>
            </div>
            
            <p>Or copy and paste this link: ' . htmlspecialchars($verificationUrl) . '</p>
            
            <p>This link expires in 24 hours.</p>
            
            <p>Best regards,<br>The Fundify Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 Fundify - Connecting Entrepreneurs with Investors</p>
        </div>
    </div>
</body>
</html>';
        
        return $this->send($email, $subject, $body, true);
    }
    
    public function sendWelcomeEmail($email, $userName, $userRole) {
        $subject = 'Welcome to Fundify - Your Account is Ready!';
        
        $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 20px; border: 1px solid #e0e0e0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Fundify!</h1>
        </div>
        <div class="content">
            <h2>Hi ' . htmlspecialchars($userName) . '!</h2>
            <p>Your Fundify account is now active and ready to use.</p>
            <p><strong>Your Role:</strong> ' . htmlspecialchars(ucfirst($userRole)) . '</p>
            <p><a href="https://fundify.isowebtech.com/dashboard.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Visit Your Dashboard</a></p>
            <p>Best regards,<br>The Fundify Team</p>
        </div>
    </div>
</body>
</html>';
        
        return $this->send($email, $subject, $body, true);
    }
    
    public function sendPasswordResetEmail($email, $token, $userName = '') {
        $subject = 'Reset Your Fundify Password';
        $resetUrl = "https://fundify.isowebtech.com/reset-password.php?token=" . $token;
        $userName = $userName ?: 'User';
        
        $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ff6b6b; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 20px; border: 1px solid #e0e0e0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <h2>Hi ' . htmlspecialchars($userName) . ',</h2>
            <p>We received a request to reset your Fundify password.</p>
            <p><a href="' . htmlspecialchars($resetUrl) . '" style="background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Reset My Password</a></p>
            <p>Or copy and paste this link: ' . htmlspecialchars($resetUrl) . '</p>
            <p><strong>This link expires in 1 hour.</strong></p>
            <p>If you didn\'t request this, please ignore this email.</p>
            <p>Best regards,<br>The Fundify Team</p>
        </div>
    </div>
</body>
</html>';
        
        return $this->send($email, $subject, $body, true);
    }
    
    // Test method for quick testing
    public function sendTestEmail($email) {
        $subject = 'Fundify Email System Test';
        $body = 'This is a test email from the Fundify platform. If you receive this, the email system is working correctly!';
        
        return $this->send($email, $subject, $body, false);
    }
}

?>