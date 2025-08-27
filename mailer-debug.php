<?php
echo "<h1>Mailer Class Specific Debug</h1>";

echo "<h2>File System Check:</h2>";
$mailerPath = __DIR__ . '/classes/Mailer.php';
echo "Looking for: $mailerPath<br>";

if (file_exists($mailerPath)) {
    echo "<span style='color: green;'>✓ Mailer.php file exists</span><br>";
    
    // Check file permissions
    echo "File permissions: " . substr(sprintf('%o', fileperms($mailerPath)), -4) . "<br>";
    
    // Check file size
    echo "File size: " . filesize($mailerPath) . " bytes<br>";
    
    // Check if file starts with <?php
    $content = file_get_contents($mailerPath);
    if (strpos($content, '<?php') === 0) {
        echo "<span style='color: green;'>✓ File starts with &lt;?php</span><br>";
    } else {
        echo "<span style='color: red;'>✗ File does NOT start with &lt;?php</span><br>";
        echo "First 50 characters: <code>" . htmlspecialchars(substr($content, 0, 50)) . "</code><br>";
    }
    
    // Check for syntax errors by including the file
    echo "<h3>Syntax Check:</h3>";
    try {
        require_once $mailerPath;
        echo "<span style='color: green;'>✓ File included without syntax errors</span><br>";
        
        // Check if class is now available
        if (class_exists('Mailer')) {
            echo "<span style='color: green;'>✓ Mailer class is now available</span><br>";
            
            // Try creating instance
            try {
                $mailer = new Mailer();
                echo "<span style='color: green;'>✓ Mailer instance created successfully</span><br>";
                
                // Test the send method
                echo "<h3>Method Test:</h3>";
                if (method_exists($mailer, 'send')) {
                    echo "<span style='color: green;'>✓ send() method exists</span><br>";
                }
                if (method_exists($mailer, 'sendVerificationEmail')) {
                    echo "<span style='color: green;'>✓ sendVerificationEmail() method exists</span><br>";
                }
                
                // Test actual email sending
                echo "<h3>Email Send Test:</h3>";
                $testResult = $mailer->send('test@example.com', 'Test Subject', 'Test message');
                if ($testResult) {
                    echo "<span style='color: green;'>✓ Test email send returned success</span><br>";
                } else {
                    echo "<span style='color: red;'>✗ Test email send returned failure</span><br>";
                }
                
            } catch (Exception $e) {
                echo "<span style='color: red;'>✗ Error creating Mailer instance: " . $e->getMessage() . "</span><br>";
            }
        } else {
            echo "<span style='color: red;'>✗ Mailer class still not found after include</span><br>";
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>✗ Syntax error in Mailer.php: " . $e->getMessage() . "</span><br>";
        echo "Error on line: " . $e->getLine() . "<br>";
    }
    
} else {
    echo "<span style='color: red;'>✗ Mailer.php file does NOT exist</span><br>";
}

echo "<h2>Autoloader Debug:</h2>";
echo "Current directory: " . __DIR__ . "<br>";

// Test autoloader manually
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    echo "Autoloader trying to load: $class from $file<br>";
    if (file_exists($file)) {
        echo "<span style='color: green;'>✓ File found, requiring...</span><br>";
        require_once $file;
    } else {
        echo "<span style='color: red;'>✗ File not found</span><br>";
    }
});

echo "<h3>Testing autoload of Mailer:</h3>";
try {
    new Mailer();
    echo "<span style='color: green;'>✓ Autoloader successfully loaded Mailer</span><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Autoloader failed: " . $e->getMessage() . "</span><br>";
}

echo "<h2>Registration Integration Test:</h2>";
if (class_exists('User') && class_exists('Mailer')) {
    echo "Testing User class sendVerificationEmail method...<br>";
    try {
        session_start();
        
        // Load classes like registration form does
        spl_autoload_register(function ($class) {
            $file = __DIR__ . '/classes/' . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });
        
        $user = new User();
        echo "<span style='color: green;'>✓ User class loaded</span><br>";
        
        // This is what gets called during registration
        $testResult = $user->register([
            'full_name' => 'Test User',
            'email' => 'devspteam@gmail.com',
            'phone' => '+91 9876543210',
            'password' => 'password123',
            'role' => 'entrepreneur'
        ]);
        
        if ($testResult['success']) {
            echo "<span style='color: green;'>✓ Registration test successful: " . $testResult['message'] . "</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Registration test failed: " . implode(', ', $testResult['errors']) . "</span><br>";
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>✗ Registration integration test failed: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span style='color: red;'>✗ Cannot test registration - User or Mailer class not available</span><br>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.5; }
h1, h2, h3 { color: #333; }
code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
</style>