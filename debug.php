<?php
session_start();
require_once 'config/app.php';
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$db = Database::getInstance();

echo "<h2>Files Debug</h2>";

// Check uploaded_files table
$files = $db->fetchAll("SELECT * FROM uploaded_files");
echo "Total files in database: " . count($files) . "<br><br>";

if (!empty($files)) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Listing ID</th><th>File Name</th><th>Access Level</th></tr>";
    foreach ($files as $file) {
        echo "<tr>";
        echo "<td>" . $file['id'] . "</td>";
        echo "<td>" . $file['business_listing_id'] . "</td>";
        echo "<td>" . htmlspecialchars($file['file_original_name']) . "</td>";
        echo "<td>" . $file['access_level'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<strong>No files found! You need to upload files first.</strong>";
}
?>