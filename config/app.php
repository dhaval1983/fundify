<?php
// =====================================================
// 2. CONFIG/APP.PHP - Application Configuration  
// =====================================================

// config/app.php
return [
    'site_name' => 'Fundify',
    'site_url' => 'https://fundify.isowebtech.com',
    'timezone' => 'Asia/Kolkata',
    'free_trial_days' => 90,
    'max_upload_size' => 10, // MB
    'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'mp4', 'xlsx', 'docx'],
    'session_lifetime' => 86400, // 24 hours
    'password_min_length' => 8,
    'pagination_limit' => 20,
    'email_verification_required' => true,
    'listing_approval_required' => false,
    
    // Razorpay Configuration (for future)
    'razorpay' => [
        'key_id' => 'your_razorpay_key',
        'key_secret' => 'your_razorpay_secret',
        'webhook_secret' => 'your_webhook_secret'
    ],
    
    // Email Configuration
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your_email@gmail.com',
        'password' => 'your_app_password',
        'encryption' => 'tls'
    ]
];
?>