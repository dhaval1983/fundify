<?php
echo "<h1>User.php File Location Inspector</h1>";

echo "<h2>Directory Structure Check:</h2>";
echo "Current directory: " . __DIR__ . "<br>";

// Check multiple possible locations
$possiblePaths = [
    __DIR__ . '/classes/User.php',           // Correct location
    __DIR__ . '/classes/classes/User.php',   // Wrong location (double classes)
    __DIR__ . '/User.php',                   // Wrong location (root)
    __DIR__ . '/Classes/User.php'            // Wrong case
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        echo "<span style='color: green;'>✓ FOUND: $path</span><br>";
        
        // Show content of found file
        $content = file_get_contents($path);
        echo "<h3>File Content Preview:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: scroll;'>";
        echo htmlspecialchars(substr($content, 0, 1000));
        echo "</pre>";
        
        // Check if it starts with <?php
        if (strpos($content, '<?php') === 0) {
            echo "<span style='color: green;'>✓ Starts with &lt;?php</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Does NOT start with &lt;?php</span><br>";
            echo "First 50 chars: <code>" . htmlspecialchars(substr($content, 0, 50)) . "</code><br>";
        }
        
        // Check for class declaration
        if (strpos($content, 'class User') !== false) {
            echo "<span style='color: green;'>✓ Contains 'class User'</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Does NOT contain 'class User'</span><br>";
        }
        
    } else {
        echo "<span style='color: red;'>✗ NOT FOUND: $path</span><br>";
    }
}

echo "<hr>";

// List all files in classes directory
echo "<h2>Files in classes/ directory:</h2>";
$classesDir = __DIR__ . '/classes';
if (is_dir($classesDir)) {
    $files = scandir($classesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $classesDir . '/' . $file;
            $type = is_dir($fullPath) ? '[DIR]' : '[FILE]';
            echo "$type $file<br>";
        }
    }
} else {
    echo "<span style='color: red;'>classes/ directory does not exist!</span><br>";
}

echo "<hr>";
echo "<h2>SOLUTION:</h2>";
echo "<ol>";
echo "<li>The User.php file should be located at: <strong>" . __DIR__ . "/classes/User.php</strong></li>";
echo "<li>NOT in classes/classes/ or any other location</li>";
echo "<li>Use cPanel File Manager to verify the exact location</li>";
echo "<li>Make sure it's directly inside the classes folder</li>";
echo "</ol>";
?>