<?php
// download.php - Secure File Download System
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
$db = Database::getInstance();

// Get file ID from URL
$fileId = (int)($_GET['file'] ?? 0);

if ($fileId <= 0) {
    http_response_code(400);
    die('Invalid file ID');
}

try {
    // Get file details from database
    $query = "SELECT uf.*, bl.title as listing_title, bl.user_id as listing_owner_id, c.company_name 
              FROM uploaded_files uf
              LEFT JOIN business_listings bl ON uf.business_listing_id = bl.id
              LEFT JOIN companies c ON bl.company_id = c.id
              WHERE uf.id = ?";
    
    $file = $db->fetchOne($query, [$fileId]);
    
    if (!$file) {
        http_response_code(404);
        die('File not found');
    }
    
    // Check if file exists on disk
    if (!file_exists($file['file_path'])) {
        http_response_code(404);
        die('File not found on server');
    }
    
    // Get current user
    $currentUser = $user->getCurrentUser();
    $userRole = 'public';
    
    if ($currentUser) {
        $userRole = $currentUser['email_verified'] ? 'registered' : 'unverified';
    }
    
    // Check access permissions based on file access level
    $hasAccess = false;
    $accessMessage = '';
    
    switch ($file['access_level']) {
        case 'public':
            $hasAccess = true;
            break;
            
        case 'registered':
            if ($userRole === 'registered') {
                $hasAccess = true;
            } else if (!$currentUser) {
                $accessMessage = 'Please <a href="register.php">register</a> or <a href="login.php">login</a> to download this file.';
            } else {
                $accessMessage = 'Please <a href="verify.php">verify your email</a> to download this file.';
            }
            break;
            
        case 'paid_only':
            // Future: Check if user has active subscription
            // For now, treat as registered access
            if ($userRole === 'registered') {
                $hasAccess = true;
            } else {
                $accessMessage = 'This file requires a premium account. Please <a href="register.php">register</a> to access.';
            }
            break;
            
        default:
            $accessMessage = 'Access level not recognized.';
    }
    
    // Special case: listing owners can always download their own files
    if (!$hasAccess && $currentUser && $currentUser['id'] == $file['listing_owner_id']) {
        $hasAccess = true;
    }
    
    // If no access, show error page
    if (!$hasAccess) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Restricted - Fundify</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f8f9ff; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; }
                .icon { font-size: 4em; color: #ff6b6b; margin-bottom: 20px; }
                h1 { color: #333; margin-bottom: 20px; }
                p { color: #666; margin-bottom: 30px; line-height: 1.6; }
                .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(45deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; margin: 5px; }
                .file-info { background: #f8f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">🔒</div>
                <h1>Access Restricted</h1>
                <p>You need additional permissions to download this file.</p>
                
                <div class="file-info">
                    <strong>File:</strong> <?php echo htmlspecialchars($file['file_original_name']); ?><br>
                    <strong>From:</strong> <?php echo htmlspecialchars($file['listing_title'] ?? 'Direct upload'); ?><br>
                    <strong>Required Access:</strong> <?php echo ucwords(str_replace('_', ' ', $file['access_level'])); ?>
                </div>
                
                <p><?php echo $accessMessage; ?></p>
                
                <a href="register.php" class="btn">Register Free</a>
                <a href="login.php" class="btn">Sign In</a>
                <a href="browse.php" class="btn">Back to Browse</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    // Track download
    $downloadQuery = "UPDATE uploaded_files SET download_count = download_count + 1 WHERE id = ?";
    $db->execute($downloadQuery, [$fileId]);
    
    // Log download activity
    $logQuery = "INSERT INTO listing_views (business_listing_id, viewer_user_id, viewer_ip, viewer_user_agent, view_source, viewed_at) 
                 VALUES (?, ?, ?, ?, 'file_download', NOW())";
    $db->execute($logQuery, [
        $file['business_listing_id'],
        $currentUser['id'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // Determine content type
    $contentType = $file['mime_type'] ?? 'application/octet-stream';
    $fileExtension = strtolower(pathinfo($file['file_original_name'], PATHINFO_EXTENSION));
    
    // Set content type based on extension if mime_type is not available
    if ($contentType === 'application/octet-stream') {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'zip' => 'application/zip'
        ];
        $contentType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
    }
    
    // Get file size
    $fileSize = filesize($file['file_path']);
    
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for file download
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . basename($file['file_original_name']) . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    
    // Output file content
    $handle = fopen($file['file_path'], 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        http_response_code(500);
        die('Unable to read file');
    }
    
} catch (Exception $e) {
    error_log("File download error: " . $e->getMessage());
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Download Error - Fundify</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f8f9ff; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; }
            .icon { font-size: 4em; color: #ff6b6b; margin-bottom: 20px; }
            h1 { color: #333; margin-bottom: 20px; }
            p { color: #666; margin-bottom: 30px; }
            .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(45deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; margin: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">⚠️</div>
            <h1>Download Failed</h1>
            <p>Sorry, there was an error processing your download request. Please try again later.</p>
            <a href="javascript:history.back()" class="btn">Go Back</a>
            <a href="browse.php" class="btn">Browse Listings</a>
        </div>
    </body>
    </html>
    <?php
}
?>