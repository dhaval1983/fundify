<?php
// messages.php - Complete Messaging Interface
session_start();

// Load configuration and classes
require_once 'config/app.php';

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize classes
$user = new User();
$message = new Message();

// Check if user is logged in
$currentUser = $user->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php?redirect=messages.php');
    exit;
}

// Check if email is verified
if (!$currentUser['email_verified']) {
    header('Location: dashboard.php?error=email-verification-required');
    exit;
}

$currentUserId = $currentUser['id'];
$activeThread = $_GET['thread'] ?? null;
$messageType = 'success';
$messageText = '';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'send_message') {
        $sendResult = $message->sendMessage([
            'sender_id' => $currentUserId,
            'receiver_id' => (int)$_POST['receiver_id'],
            'business_listing_id' => !empty($_POST['business_listing_id']) ? (int)$_POST['business_listing_id'] : null,
            'subject' => $_POST['subject'] ?? '',
            'message' => $_POST['message'],
            'message_type' => $_POST['message_type'] ?? 'follow_up',
            'parent_message_id' => !empty($_POST['parent_message_id']) ? (int)$_POST['parent_message_id'] : null
        ]);
        echo json_encode($sendResult);
        exit;
    }
    
    if ($_POST['action'] === 'mark_read') {
        $threadId = $_POST['thread_id'];
        $result = $message->markThreadAsRead($threadId, $currentUserId);
        echo json_encode(['success' => $result]);
        exit;
    }
    
    if ($_POST['action'] === 'delete_message') {
        $deleteResult = $message->deleteMessage((int)$_POST['message_id'], $currentUserId);
        echo json_encode($deleteResult);
        exit;
    }
}

// Get user's inbox
$inboxResult = $message->getInbox($currentUserId);
$conversations = $inboxResult['success'] ? $inboxResult['conversations'] : [];
$totalUnread = $message->getTotalUnreadCount($currentUserId);

// Get active thread messages if selected
$threadMessages = [];
$threadInfo = null;
if ($activeThread) {
    $threadResult = $message->getThread($activeThread, $currentUserId);
    if ($threadResult['success']) {
        $threadMessages = $threadResult['messages'];
        $threadInfoResult = $message->getThreadInfo($activeThread, $currentUserId);
        if ($threadInfoResult['success']) {
            $threadInfo = $threadInfoResult['info'];
        }
    }
}

// Handle URL flash messages
$flashMessage = Utils::getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Fundify</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9ff;
            height: 100vh;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.5em;
        }
        
        .header .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .header .nav-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .header .nav-links a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .messages-container {
            display: flex;
            height: calc(100vh - 70px);
        }
        
        .sidebar {
            width: 350px;
            background: white;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-header h2 {
            font-size: 1.3em;
            color: #333;
        }
        
        .unread-badge {
            background: #ff6b6b;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .conversation-item:hover {
            background: #f8f9ff;
        }
        
        .conversation-item.active {
            background: #e3f2fd;
            border-right: 3px solid #667eea;
        }
        
        .conversation-item.unread {
            background: #fff3cd;
        }
        
        .conv-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .conv-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .conv-listing {
            font-size: 12px;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .conv-preview {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .conv-time {
            font-size: 12px;
            color: #999;
        }
        
        .conv-unread {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff6b6b;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .thread-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .thread-info {
            display: flex;
            align-items: center;
        }
        
        .thread-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .thread-details h3 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .thread-listing {
            color: #667eea;
            font-size: 14px;
        }
        
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9ff;
        }
        
        .message-item {
            margin-bottom: 20px;
            display: flex;
        }
        
        .message-item.own {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 15px;
            border-radius: 15px;
            position: relative;
        }
        
        .message-item:not(.own) .message-content {
            background: white;
            border: 1px solid #e0e0e0;
        }
        
        .message-item.own .message-content {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .message-sender {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .message-item.own .message-sender {
            color: rgba(255,255,255,0.9);
        }
        
        .message-text {
            line-height: 1.4;
            margin-bottom: 8px;
        }
        
        .message-time {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .message-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .message-item:hover .message-actions {
            opacity: 1;
        }
        
        .message-actions button {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .message-actions button:hover {
            background: rgba(0,0,0,0.1);
        }
        
        .message-form {
            border-top: 1px solid #e0e0e0;
            padding: 20px;
            background: white;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #f8f9ff;
            color: #667eea;
            border: 1px solid #667eea;
        }
        
        .btn:hover {
            transform: translateY(-2px);
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
        
        @media (max-width: 768px) {
            .messages-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: 50vh;
            }
            
            .message-content {
                max-width: 85%;
            }
            
            .form-row {
                flex-direction: column;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <h1><i class="fas fa-envelope mr-2"></i>Messages</h1>
        <nav class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home mr-1"></i>Dashboard</a>
            <a href="browse.php"><i class="fas fa-search mr-1"></i>Browse</a>
            <a href="profile.php"><i class="fas fa-user mr-1"></i>Profile</a>
            <a href="login.php?logout=1"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
        </nav>
    </header>

    <?php if ($flashMessage): ?>
    <div class="alert alert-<?php echo $flashMessage['type']; ?>">
        <?php echo htmlspecialchars($flashMessage['message']); ?>
    </div>
    <?php endif; ?>

    <div class="messages-container">
        <!-- Sidebar - Conversations List -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Conversations</h2>
                <?php if ($totalUnread > 0): ?>
                <span class="unread-badge"><?php echo $totalUnread; ?> unread</span>
                <?php endif; ?>
            </div>
            
            <div class="conversations-list">
                <?php if (empty($conversations)): ?>
                <div style="padding: 40px 20px; text-align: center; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 15px; color: #ddd;"></i>
                    <p>No conversations yet</p>
                    <p style="font-size: 14px; margin-top: 10px;">Messages will appear here when investors contact you or when you reach out to entrepreneurs.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item <?php echo ($conv['thread_id'] === $activeThread) ? 'active' : ''; ?> <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>"
                         onclick="selectConversation('<?php echo htmlspecialchars($conv['thread_id']); ?>')">
                        
                        <div class="conv-avatar">
                            <?php echo strtoupper(substr($conv['contact_name'], 0, 1)); ?>
                        </div>
                        
                        <div class="conv-name"><?php echo htmlspecialchars($conv['contact_name']); ?></div>
                        
                        <?php if ($conv['listing_title']): ?>
                        <div class="conv-listing"><?php echo htmlspecialchars($conv['listing_title']); ?></div>
                        <?php endif; ?>
                        
                        <div class="conv-preview"><?php echo htmlspecialchars($conv['preview']); ?></div>
                        
                        <div class="conv-time"><?php echo htmlspecialchars($conv['time_ago']); ?></div>
                        
                        <?php if ($conv['unread_count'] > 0): ?>
                        <div class="conv-unread"><?php echo $conv['unread_count']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <?php if (!$activeThread || empty($threadMessages)): ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>Select a conversation</h3>
                <p>Choose a conversation from the left to start messaging</p>
            </div>
            <?php else: ?>
            
            <!-- Thread Header -->
            <div class="thread-header">
                <div class="thread-info">
                    <div class="thread-avatar">
                        <?php echo strtoupper(substr($threadInfo['contact_name'], 0, 1)); ?>
                    </div>
                    <div class="thread-details">
                        <h3><?php echo htmlspecialchars($threadInfo['contact_name']); ?></h3>
                        <?php if ($threadInfo['listing_title']): ?>
                        <div class="thread-listing">
                            Re: <?php echo htmlspecialchars($threadInfo['listing_title']); ?>
                            <a href="listing/<?php echo htmlspecialchars($threadInfo['listing_slug']); ?>" target="_blank" style="margin-left: 10px;">
                                <i class="fas fa-external-link-alt"></i> View Listing
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="messages-area" id="messages-area">
                <?php foreach ($threadMessages as $msg): ?>
                <div class="message-item <?php echo $msg['is_own_message'] ? 'own' : ''; ?>">
                    <div class="message-content">
                        <?php if (!$msg['is_own_message']): ?>
                        <div class="message-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                        <?php endif; ?>
                        
                        <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                        
                        <div class="message-time"><?php echo htmlspecialchars($msg['time_ago']); ?></div>
                        
                        <?php if ($msg['is_own_message']): ?>
                        <div class="message-actions">
                            <button onclick="deleteMessage(<?php echo $msg['id']; ?>)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Message Form -->
            <form class="message-form" id="reply-form" onsubmit="sendReply(event)">
                <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($threadInfo['contact_user_id']); ?>">
                <input type="hidden" name="business_listing_id" value="<?php echo htmlspecialchars($threadInfo['business_listing_id'] ?? ''); ?>">
                <input type="hidden" name="thread_id" value="<?php echo htmlspecialchars($activeThread); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <textarea name="message" placeholder="Type your message..." rows="3" required></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <div style="font-size: 14px; color: #666;">
                        Press Ctrl+Enter to send quickly
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>Send Message
                    </button>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Select conversation
        function selectConversation(threadId) {
            window.location.href = 'messages.php?thread=' + encodeURIComponent(threadId);
        }

        // Send reply
        async function sendReply(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'send_message');
            formData.append('message_type', 'follow_up');
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('messages.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Reload page to show new message
                    window.location.reload();
                } else {
                    alert('Failed to send message: ' + (result.errors ? result.errors.join(', ') : 'Unknown error'));
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
                
            } catch (error) {
                alert('Error sending message. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }

        // Delete message
        async function deleteMessage(messageId) {
            if (!confirm('Are you sure you want to delete this message?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_message');
            formData.append('message_id', messageId);
            
            try {
                const response = await fetch('messages.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Failed to delete message: ' + (result.error || 'Unknown error'));
                }
                
            } catch (error) {
                alert('Error deleting message. Please try again.');
            }
        }

        // Auto-scroll messages to bottom
        const messagesArea = document.getElementById('messages-area');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Ctrl+Enter to send
        document.addEventListener('keydown', function(event) {
            if (event.ctrlKey && event.key === 'Enter') {
                const form = document.getElementById('reply-form');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.hidden) return; // Don't refresh if tab is not active
            
            const currentThread = '<?php echo $activeThread; ?>';
            if (currentThread) {
                // Refresh current thread
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>