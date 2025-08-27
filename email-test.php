<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'classes/Mailer.php';

echo "<h2>Testing Fundify Email Templates</h2>";

try {
    $mailer = new Mailer();
    
    // Test 1: Verification Email Template
    echo "<h3>Test 1: Verification Email Template</h3>";
    $result1 = $mailer->sendVerificationEmail(
        'info@fundify.isowebtech.com', 
        'test-verification-token-12345', 
        'John Entrepreneur'
    );
    echo $result1 ? "✅ Verification email sent successfully!<br>" : "❌ Verification email failed<br>";
    
    // Test 2: Welcome Email Template  
    echo "<h3>Test 2: Welcome Email Template</h3>";
    $result2 = $mailer->sendWelcomeEmail(
        'info@fundify.isowebtech.com', 
        'Sarah Investor', 
        'investor'
    );
    echo $result2 ? "✅ Welcome email sent successfully!<br>" : "❌ Welcome email failed<br>";
    
    // Test 3: Password Reset Email Template
    echo "<h3>Test 3: Password Reset Email Template</h3>";
    $result3 = $mailer->sendPasswordResetEmail(
        'info@fundify.isowebtech.com', 
        'reset-token-67890', 
        'Mike Browser'
    );
    echo $result3 ? "✅ Password reset email sent successfully!<br>" : "❌ Password reset email failed<br>";
    
    echo "<h3>🎉 Fundify Email System Testing Complete!</h3>";
    echo "<p>Check your inbox for:</p>";
    echo "<ul>";
    echo "<li>📧 Verification email with beautiful HTML template</li>";
    echo "<li>🎉 Welcome email with dashboard link</li>";
    echo "<li>🔐 Password reset email with reset link</li>";
    echo "</ul>";
    
    if ($result1 && $result2 && $result3) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> Your Fundify email system is fully operational and ready for integration!";
        echo "</div>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li><strong>Integration:</strong> Use the Mailer class in your registration system</li>";
        echo "<li><strong>Example Usage:</strong><br><code>require_once 'classes/Mailer.php';<br>\$mailer = new Mailer();<br>\$mailer->sendVerificationEmail(\$email, \$token, \$name);</code></li>";
        echo "<li><strong>Error Handling:</strong> The Mailer logs to error_log() for debugging</li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>