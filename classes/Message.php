<?php

class Message {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Send a new message
     */
    public function sendMessage($data) {
        try {
            // Validate required fields
            $errors = $this->validateMessageData($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            $senderId = $data['sender_id'];
            $receiverId = $data['receiver_id']; 
            $businessListingId = $data['business_listing_id'] ?? null;
            $subject = trim($data['subject'] ?? '');
            $message = trim($data['message']);
            $messageType = $data['message_type'] ?? 'inquiry';
            $parentMessageId = $data['parent_message_id'] ?? null;
            
            // Generate thread_id for conversation grouping
            $threadId = $this->generateThreadId($senderId, $receiverId, $businessListingId);
            
            // Insert message
            $query = "INSERT INTO messages (
                sender_id, receiver_id, business_listing_id, thread_id,
                subject, message, message_type, parent_message_id, sent_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $senderId, $receiverId, $businessListingId, $threadId,
                $subject, $message, $messageType, $parentMessageId
            ];
            
            $this->db->execute($query, $params);
            $messageId = $this->db->lastInsertId();
            
            // Send notification email (optional)
            $this->sendNotificationEmail($messageId);
            
            return [
                'success' => true, 
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'message' => 'Message sent successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Message sending failed: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to send message. Please try again.']];
        }
    }
    
    /**
     * Get user's inbox (all conversations)
     */
    public function getInbox($userId, $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get latest message from each conversation thread
            $query = "SELECT 
                m.thread_id,
                m.business_listing_id,
                m.subject,
                LEFT(m.message, 150) as preview,
                m.sent_at,
                m.sender_id,
                m.receiver_id,
                m.is_read,
                
                -- Contact details
                CASE 
                    WHEN m.sender_id = ? THEN receiver_u.full_name 
                    ELSE sender_u.full_name 
                END as contact_name,
                
                CASE 
                    WHEN m.sender_id = ? THEN receiver_u.profile_photo 
                    ELSE sender_u.profile_photo 
                END as contact_photo,
                
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as contact_user_id,
                
                -- Business listing details
                bl.title as listing_title,
                c.company_name,
                
                -- Unread count for this thread
                (SELECT COUNT(*) FROM messages m2 
                 WHERE m2.thread_id = m.thread_id 
                 AND m2.receiver_id = ? 
                 AND m2.is_read = FALSE) as unread_count
                
            FROM messages m
            JOIN users sender_u ON m.sender_id = sender_u.id
            JOIN users receiver_u ON m.receiver_id = receiver_u.id
            LEFT JOIN business_listings bl ON m.business_listing_id = bl.id
            LEFT JOIN companies c ON bl.company_id = c.id
            
            WHERE (m.sender_id = ? OR m.receiver_id = ?)
            AND m.sent_at = (
                SELECT MAX(sent_at) 
                FROM messages m3 
                WHERE m3.thread_id = m.thread_id
            )
            ORDER BY m.sent_at DESC
            LIMIT ? OFFSET ?";
            
            $params = [$userId, $userId, $userId, $userId, $userId, $userId, $limit, $offset];
            $conversations = $this->db->fetchAll($query, $params);
            
            // Format data for display
            foreach ($conversations as &$conv) {
                $conv['time_ago'] = $this->timeAgo($conv['sent_at']);
                $conv['is_unread'] = ($conv['unread_count'] > 0);
            }
            
            return [
                'success' => true,
                'conversations' => $conversations,
                'total_unread' => $this->getTotalUnreadCount($userId)
            ];
            
        } catch (Exception $e) {
            error_log("Inbox fetch failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to load inbox'];
        }
    }
    
    /**
     * Get all messages in a specific thread/conversation
     */
    public function getThread($threadId, $currentUserId) {
        try {
            // Verify user has access to this thread
            if (!$this->userHasAccessToThread($threadId, $currentUserId)) {
                return ['success' => false, 'error' => 'Access denied'];
            }
            
            $query = "SELECT 
                m.*,
                u.full_name as sender_name,
                u.profile_photo as sender_photo,
                u.role as sender_role,
                bl.title as listing_title,
                c.company_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN business_listings bl ON m.business_listing_id = bl.id
            LEFT JOIN companies c ON bl.company_id = c.id
            WHERE m.thread_id = ?
            ORDER BY m.sent_at ASC";
            
            $messages = $this->db->fetchAll($query, [$threadId]);
            
            // Mark messages as read for current user
            $this->markThreadAsRead($threadId, $currentUserId);
            
            // Format messages for display
            foreach ($messages as &$msg) {
                $msg['time_ago'] = $this->timeAgo($msg['sent_at']);
                $msg['is_own_message'] = ($msg['sender_id'] == $currentUserId);
            }
            
            return [
                'success' => true,
                'messages' => $messages,
                'thread_id' => $threadId
            ];
            
        } catch (Exception $e) {
            error_log("Thread fetch failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to load conversation'];
        }
    }
    
    /**
     * Get total unread message count for user
     */
    public function getTotalUnreadCount($userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = FALSE";
            $result = $this->db->fetchOne($query, [$userId]);
            return (int)$result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Mark all messages in a thread as read for current user
     */
    public function markThreadAsRead($threadId, $userId) {
        try {
            $query = "UPDATE messages 
                      SET is_read = TRUE, read_at = NOW() 
                      WHERE thread_id = ? AND receiver_id = ? AND is_read = FALSE";
            
            $this->db->execute($query, [$threadId, $userId]);
            return true;
        } catch (Exception $e) {
            error_log("Mark as read failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get thread info for display
     */
    public function getThreadInfo($threadId, $currentUserId) {
        try {
            $query = "SELECT 
                m.thread_id,
                m.business_listing_id,
                m.subject,
                
                -- Get the other participant
                CASE 
                    WHEN m.sender_id = ? THEN receiver_u.full_name 
                    ELSE sender_u.full_name 
                END as contact_name,
                
                CASE 
                    WHEN m.sender_id = ? THEN receiver_u.profile_photo 
                    ELSE sender_u.profile_photo 
                END as contact_photo,
                
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as contact_user_id,
                
                -- Business listing info
                bl.title as listing_title,
                bl.slug as listing_slug,
                c.company_name
                
            FROM messages m
            JOIN users sender_u ON m.sender_id = sender_u.id
            JOIN users receiver_u ON m.receiver_id = receiver_u.id
            LEFT JOIN business_listings bl ON m.business_listing_id = bl.id
            LEFT JOIN companies c ON bl.company_id = c.id
            WHERE m.thread_id = ?
            LIMIT 1";
            
            $params = [$currentUserId, $currentUserId, $currentUserId, $threadId];
            $info = $this->db->fetchOne($query, $params);
            
            return $info ? ['success' => true, 'info' => $info] : ['success' => false, 'error' => 'Thread not found'];
            
        } catch (Exception $e) {
            error_log("Thread info fetch failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to get thread info'];
        }
    }
    
    /**
     * Delete a message (sender only)
     */
    public function deleteMessage($messageId, $userId) {
        try {
            // Verify user owns this message
            $query = "SELECT sender_id FROM messages WHERE id = ?";
            $message = $this->db->fetchOne($query, [$messageId]);
            
            if (!$message || $message['sender_id'] != $userId) {
                return ['success' => false, 'error' => 'Permission denied'];
            }
            
            // Delete message
            $this->db->execute("DELETE FROM messages WHERE id = ?", [$messageId]);
            
            return ['success' => true, 'message' => 'Message deleted'];
            
        } catch (Exception $e) {
            error_log("Message deletion failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete message'];
        }
    }
    
    // Private helper methods
    
    private function validateMessageData($data) {
        $errors = [];
        
        if (empty($data['sender_id'])) {
            $errors[] = 'Sender ID is required';
        }
        
        if (empty($data['receiver_id'])) {
            $errors[] = 'Receiver ID is required';
        }
        
        if (empty($data['message']) || strlen(trim($data['message'])) < 1) {
            $errors[] = 'Message content is required';
        }
        
        if (strlen($data['message']) > 5000) {
            $errors[] = 'Message is too long (max 5000 characters)';
        }
        
        // Check if users exist
        if (!empty($data['sender_id']) && !$this->userExists($data['sender_id'])) {
            $errors[] = 'Sender not found';
        }
        
        if (!empty($data['receiver_id']) && !$this->userExists($data['receiver_id'])) {
            $errors[] = 'Recipient not found';
        }
        
        // Check if business listing exists (if provided)
        if (!empty($data['business_listing_id']) && !$this->listingExists($data['business_listing_id'])) {
            $errors[] = 'Business listing not found';
        }
        
        return $errors;
    }
    
    private function generateThreadId($senderId, $receiverId, $businessListingId = null) {
        // Create consistent thread ID regardless of who sends first
        $participants = [$senderId, $receiverId];
        sort($participants);
        
        $threadId = implode('-', $participants);
        
        if ($businessListingId) {
            $threadId .= '-listing-' . $businessListingId;
        }
        
        return $threadId;
    }
    
    private function userHasAccessToThread($threadId, $userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM messages 
                      WHERE thread_id = ? AND (sender_id = ? OR receiver_id = ?)";
            $result = $this->db->fetchOne($query, [$threadId, $userId, $userId]);
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function userExists($userId) {
        try {
            $query = "SELECT id FROM users WHERE id = ? AND account_status = 'active'";
            $user = $this->db->fetchOne($query, [$userId]);
            return $user !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function listingExists($listingId) {
        try {
            $query = "SELECT id FROM business_listings WHERE id = ? AND status = 'active'";
            $listing = $this->db->fetchOne($query, [$listingId]);
            return $listing !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function sendNotificationEmail($messageId) {
        try {
            // Get message details
            $query = "SELECT m.*, 
                      sender.full_name as sender_name,
                      receiver.full_name as receiver_name,
                      receiver.email as receiver_email,
                      bl.title as listing_title
                      FROM messages m
                      JOIN users sender ON m.sender_id = sender.id
                      JOIN users receiver ON m.receiver_id = receiver.id
                      LEFT JOIN business_listings bl ON m.business_listing_id = bl.id
                      WHERE m.id = ?";
            
            $message = $this->db->fetchOne($query, [$messageId]);
            
            if ($message) {
                require_once __DIR__ . '/Mailer.php';
                $mailer = new Mailer();
                
                $subject = 'New Message on Fundify';
                if ($message['listing_title']) {
                    $subject .= ' - ' . $message['listing_title'];
                }
                
                $body = $this->createNotificationEmailBody($message);
                $mailer->send($message['receiver_email'], $subject, $body, true);
            }
            
        } catch (Exception $e) {
            // Don't fail message sending if email fails
            error_log("Message notification email failed: " . $e->getMessage());
        }
    }
    
    private function createNotificationEmailBody($message) {
        $messagePreview = substr($message['message'], 0, 200) . '...';
        $listingText = $message['listing_title'] ? "regarding your listing: {$message['listing_title']}" : '';
        
        return "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: white; padding: 20px; border: 1px solid #e0e0e0; }
                .btn { display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Message on Fundify</h1>
                </div>
                <div class='content'>
                    <h2>Hi {$message['receiver_name']},</h2>
                    <p>You have received a new message from <strong>{$message['sender_name']}</strong> {$listingText}.</p>
                    <p><strong>Subject:</strong> {$message['subject']}</p>
                    <p><strong>Message:</strong><br>{$messagePreview}</p>
                    <p><a href='https://fundify.isowebtech.com/messages.php?thread={$message['thread_id']}' class='btn'>View Full Message</a></p>
                    <p>Best regards,<br>The Fundify Team</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        
        return date('M j, Y', strtotime($datetime));
    }
}
?>