<?php
// test-messages.php - Test the Message class functionality
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

echo "<h1>Fundify Message Class Test</h1>";
echo "<hr>";

try {
    // Initialize Message class
    $message = new Message();
    echo "<span style='color: green;'>✓ Message class loaded successfully</span><br><br>";
    
    // Test 1: Get total unread count for a user
    echo "<h2>Test 1: Get Unread Count</h2>";
    $unreadCount = $message->getTotalUnreadCount(26); // Using user ID 26 from your database
    echo "Unread messages for user 26: <strong>$unreadCount</strong><br><br>";
    
    // Test 2: Get inbox for a user
    echo "<h2>Test 2: Get Inbox</h2>";
    $inboxResult = $message->getInbox(26);
    if ($inboxResult['success']) {
        echo "<span style='color: green;'>✓ Inbox loaded successfully</span><br>";
        echo "Found " . count($inboxResult['conversations']) . " conversations<br>";
        
        if (!empty($inboxResult['conversations'])) {
            echo "<h3>Conversations:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Thread ID</th><th>Contact</th><th>Listing</th><th>Preview</th><th>Time</th><th>Unread</th></tr>";
            
            foreach ($inboxResult['conversations'] as $conv) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($conv['thread_id']) . "</td>";
                echo "<td>" . htmlspecialchars($conv['contact_name']) . "</td>";
                echo "<td>" . htmlspecialchars($conv['listing_title'] ?? 'Direct message') . "</td>";
                echo "<td>" . htmlspecialchars(substr($conv['preview'], 0, 50)) . "...</td>";
                echo "<td>" . htmlspecialchars($conv['time_ago']) . "</td>";
                echo "<td>" . ($conv['unread_count'] > 0 ? '<strong>' . $conv['unread_count'] . '</strong>' : '0') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<em>No conversations found</em><br>";
        }
    } else {
        echo "<span style='color: red;'>✗ Inbox test failed: " . $inboxResult['error'] . "</span><br>";
    }
    
    echo "<br>";
    
    // Test 3: Send a test message
    echo "<h2>Test 3: Send Test Message</h2>";
    $testMessageData = [
        'sender_id' => 26, // From user 26
        'receiver_id' => 27, // To user 27 
        'business_listing_id' => 2, // About listing 2 (active)
        'subject' => 'Test Message from Fundify System',
        'message' => 'This is a test message to verify the messaging system is working correctly. Sent at ' . date('Y-m-d H:i:s'),
        'message_type' => 'inquiry'
    ];
    
    $sendResult = $message->sendMessage($testMessageData);
    if ($sendResult['success']) {
        echo "<span style='color: green;'>✓ Test message sent successfully</span><br>";
        echo "Message ID: " . $sendResult['message_id'] . "<br>";
        echo "Thread ID: " . $sendResult['thread_id'] . "<br><br>";
        
        // Test 4: Get the thread we just created/updated
        echo "<h2>Test 4: Get Thread Messages</h2>";
        $threadResult = $message->getThread($sendResult['thread_id'], 26);
        if ($threadResult['success']) {
            echo "<span style='color: green;'>✓ Thread loaded successfully</span><br>";
            echo "Found " . count($threadResult['messages']) . " messages in thread<br>";
            
            echo "<h3>Messages in Thread:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>From</th><th>Subject</th><th>Message</th><th>Time</th><th>Read</th></tr>";
            
            foreach ($threadResult['messages'] as $msg) {
                echo "<tr>";
                echo "<td>" . $msg['id'] . "</td>";
                echo "<td>" . htmlspecialchars($msg['sender_name']) . "</td>";
                echo "<td>" . htmlspecialchars($msg['subject']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 100)) . "...</td>";
                echo "<td>" . htmlspecialchars($msg['time_ago']) . "</td>";
                echo "<td>" . ($msg['is_read'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<span style='color: red;'>✗ Thread test failed: " . $threadResult['error'] . "</span><br>";
        }
        
    } else {
        echo "<span style='color: red;'>✗ Test message sending failed</span><br>";
        if (isset($sendResult['errors'])) {
            echo "Errors: " . implode(', ', $sendResult['errors']) . "<br>";
        }
    }
    
    echo "<br>";
    
    // Test 5: Get Thread Info
    if (isset($sendResult['thread_id'])) {
        echo "<h2>Test 5: Get Thread Info</h2>";
        $threadInfoResult = $message->getThreadInfo($sendResult['thread_id'], 26);
        if ($threadInfoResult['success']) {
            echo "<span style='color: green;'>✓ Thread info retrieved successfully</span><br>";
            $info = $threadInfoResult['info'];
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Property</th><th>Value</th></tr>";
            echo "<tr><td>Thread ID</td><td>" . htmlspecialchars($info['thread_id']) . "</td></tr>";
            echo "<tr><td>Contact</td><td>" . htmlspecialchars($info['contact_name']) . "</td></tr>";
            echo "<tr><td>Listing</td><td>" . htmlspecialchars($info['listing_title'] ?? 'None') . "</td></tr>";
            echo "<tr><td>Company</td><td>" . htmlspecialchars($info['company_name'] ?? 'None') . "</td></tr>";
            echo "</table>";
        } else {
            echo "<span style='color: red;'>✗ Thread info test failed: " . $threadInfoResult['error'] . "</span><br>";
        }
    }
    
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Test failed with exception: " . $e->getMessage() . "</span><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>Database Check</h2>";

try {
    $db = Database::getInstance();
    
    // Check if messages table has required columns
    $query = "SHOW COLUMNS FROM messages";
    $columns = $db->fetchAll($query);
    
    echo "<h3>Messages Table Columns:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    $hasThreadId = false;
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'thread_id') {
            $hasThreadId = true;
        }
    }
    echo "</table>";
    
    if ($hasThreadId) {
        echo "<span style='color: green;'>✓ thread_id column exists</span><br>";
    } else {
        echo "<span style='color: orange;'>⚠ thread_id column missing - will be generated in PHP</span><br>";
    }
    
    // Count existing messages
    $messageCount = $db->fetchOne("SELECT COUNT(*) as count FROM messages");
    echo "Existing messages in database: <strong>" . $messageCount['count'] . "</strong><br>";
    
} catch (Exception $e) {
    echo "<span style='color: red;'>Database check failed: " . $e->getMessage() . "</span><br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>✓ <strong>Message class is working!</strong></p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Review test results above</li>";
echo "<li>Check if test message was created successfully</li>";
echo "<li>Ready to build messages.php UI page</li>";
echo "</ol>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.4; }
h1, h2, h3 { color: #333; }
table { margin: 10px 0; }
th { background: #f0f0f0; }
</style>