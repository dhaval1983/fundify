<?php
// debug.php - Find the exact 500 error cause
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fundify 500 Error Debug</h1>";
echo "<hr>";

// Test 1: Basic PHP
echo "<h2>Test 1: Basic PHP</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "<span style='color: green;'>✓ Basic PHP works</span><br><hr>";

// Test 2: Session
echo "<h2>Test 2: Session Start</h2>";
try {
    session_start();
    echo "<span style='color: green;'>✓ Session started successfully</span><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Session failed: " . $e->getMessage() . "</span><br>";
}
echo "<hr>";

// Test 3: Directory Structure
echo "<h2>Test 3: Directory Structure</h2>";
echo "Current directory: " . __DIR__ . "<br>";

$requiredDirs = ['classes', 'config'];
foreach ($requiredDirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "<span style='color: green;'>✓ $dir/ directory exists</span><br>";
    } else {
        echo "<span style='color: red;'>✗ $dir/ directory missing</span><br>";
    }
}
echo "<hr>";

// Test 4: Individual Class Files
echo "<h2>Test 4: Class Files Check</h2>";
$classFiles = [
    'Database.php',
    'User.php', 
    'Utils.php',
    'BusinessListing.php',
    'Mailer.php'
];

foreach ($classFiles as $file) {
    $path = __DIR__ . '/classes/' . $file;
    if (file_exists($path)) {
        // Check if file starts with <?php
        $content = file_get_contents($path);
        if (strpos($content, '<?php') === 0) {
            echo "<span style='color: green;'>✓ $file exists and starts with &lt;?php</span><br>";
        } else {
            echo "<span style='color: red;'>✗ $file exists but missing &lt;?php tag</span><br>";
            echo "First 100 characters: <code>" . htmlspecialchars(substr($content, 0, 100)) . "</code><br>";
        }
    } else {
        echo "<span style='color: red;'>✗ $file not found</span><br>";
    }
}
echo "<hr>";

// Test 5: Config Files
echo "<h2>Test 5: Config Files</h2>";
$configFiles = ['database.php', 'app.php'];
foreach ($configFiles as $file) {
    $path = __DIR__ . '/config/' . $file;
    if (file_exists($path)) {
        echo "<span style='color: green;'>✓ config/$file exists</span><br>";
    } else {
        echo "<span style='color: red;'>✗ config/$file missing</span><br>";
    }
}
echo "<hr>";

// Test 6: Try Loading Database Class
echo "<h2>Test 6: Database Class Test</h2>";
try {
    if (file_exists(__DIR__ . '/classes/Database.php')) {
        require_once __DIR__ . '/classes/Database.php';
        echo "<span style='color: green;'>✓ Database.php loaded successfully</span><br>";
        
        // Try creating instance (this will likely fail but shows the error)
        try {
            $db = Database::getInstance();
            echo "<span style='color: green;'>✓ Database instance created</span><br>";
        } catch (Exception $e) {
            echo "<span style='color: orange;'>⚠ Database connection failed (expected): " . $e->getMessage() . "</span><br>";
        }
    } else {
        echo "<span style='color: red;'>✗ Database.php not found</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database.php syntax error: " . $e->getMessage() . "</span><br>";
}
echo "<hr>";

// Test 7: Try Loading User Class
echo "<h2>Test 7: User Class Test</h2>";
try {
    if (file_exists(__DIR__ . '/classes/User.php')) {
        require_once __DIR__ . '/classes/User.php';
        echo "<span style='color: green;'>✓ User.php loaded successfully</span><br>";
    } else {
        echo "<span style='color: red;'>✗ User.php not found</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ User.php syntax error: " . $e->getMessage() . "</span><br>";
    echo "Error line: " . $e->getLine() . "<br>";
    echo "Error file: " . $e->getFile() . "<br>";
}
echo "<hr>";

// Test 8: Autoloader Test
echo "<h2>Test 8: Autoloader Test</h2>";
try {
    spl_autoload_register(function ($class) {
        $file = __DIR__ . '/classes/' . $class . '.php';
        if (file_exists($file)) {
            echo "Loading: $class from $file<br>";
            require_once $file;
        } else {
            echo "<span style='color: red;'>Class file not found: $file</span><br>";
        }
    });
    echo "<span style='color: green;'>✓ Autoloader registered</span><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Autoloader failed: " . $e->getMessage() . "</span><br>";
}
echo "<hr>";

echo "<h2>Summary</h2>";
echo "<p>This debug script should help identify exactly where the 500 error is occurring.</p>";
echo "<p><strong>Next steps based on results:</strong></p>";
echo "<ul>";
echo "<li>If class files show missing &lt;?php tags → Add them</li>";
echo "<li>If syntax errors shown → Fix the specific line mentioned</li>";
echo "<li>If all tests pass → The error is in your register.php file specifically</li>";
echo "</ul>";

?>

<style>
body { font-family: monospace; margin: 20px; line-height: 1.4; }
h1, h2 { color: #333; }
code { background: #f0f0f0; padding: 2px 4px; }
</style>