<?php
// profile.php - Fixed Comprehensive User Profile Management
session_start();

require_once 'config/app.php';

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$user = new User();
$currentUser = $user->getCurrentUser();

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';
$activeTab = $_GET['tab'] ?? 'basic-info'; // Maintain active tab after actions

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle profile photo upload
    if (isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
        $uploadResult = handlePhotoUpload($_FILES['profile_photo'], $currentUser['id']);
        $message = $uploadResult['message'];
        $messageType = $uploadResult['success'] ? 'success' : 'error';
        
        if ($uploadResult['success']) {
            // Refresh current user data to show new photo immediately
            $currentUser = $user->getUserById($currentUser['id']);
        }
    }
    
    // Handle basic profile update
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $updateResult = updateProfile($_POST, $currentUser['id'], $db);
        $message = $updateResult['message'];
        $messageType = $updateResult['success'] ? 'success' : 'error';
        
        if ($updateResult['success']) {
            $currentUser = $user->getUserById($currentUser['id']);
        }
    }
    
    // Handle SEPARATE social media update - FIXED
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_social') {
        $socialResult = updateSocialMedia($_POST, $currentUser['id'], $db);
        $message = $socialResult['message'];
        $messageType = $socialResult['success'] ? 'success' : 'error';
        $activeTab = 'social-media'; // Stay on social media tab
        
        if ($socialResult['success']) {
            $currentUser = $user->getUserById($currentUser['id']);
        }
    }
    
    // Handle password change
    elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $passwordResult = changePassword($_POST, $currentUser['id'], $db);
        $message = $passwordResult['message'];
        $messageType = $passwordResult['success'] ? 'success' : 'error';
        $activeTab = 'security'; // Stay on security tab
    }
    
    // Handle listing deletion - STAY ON PROFILE PAGE
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_listing') {
        $deleteResult = deleteListing($_POST['listing_id'], $currentUser['id'], $db);
        $message = $deleteResult['message'];
        $messageType = $deleteResult['success'] ? 'success' : 'error';
        $activeTab = 'listings'; // Stay on listings tab
    }
    
    // Handle listing status change (inactive/active)
    elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_listing_status') {
        $statusResult = toggleListingStatus($_POST['listing_id'], $currentUser['id'], $db);
        $message = $statusResult['message'];
        $messageType = $statusResult['success'] ? 'success' : 'error';
        $activeTab = 'listings'; // Stay on listings tab
    }
}

// Get user's business listings (for entrepreneurs)
$userListings = [];
if ($currentUser['role'] === 'entrepreneur') {
    $userListings = $db->fetchAll(
        "SELECT bl.*, c.company_name FROM business_listings bl 
         JOIN companies c ON bl.company_id = c.id 
         WHERE bl.user_id = ? ORDER BY bl.created_at DESC",
        [$currentUser['id']]
    );
}

// Handle photo upload
function handlePhotoUpload($file, $userId) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Image must be less than 5MB'];
    }
    
    // Create upload directory
    $uploadDir = __DIR__ . '/uploads/profile_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        global $db;
        try {
            $db->execute(
                "UPDATE users SET profile_photo = ? WHERE id = ?",
                ['uploads/profile_photos/' . $filename, $userId]
            );
            return ['success' => true, 'message' => 'Profile photo updated successfully'];
        } catch (Exception $e) {
            unlink($filepath); // Delete uploaded file if database update fails
            return ['success' => false, 'message' => 'Database update failed'];
        }
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

// Update user basic profile - SEPARATE from social media
function updateProfile($data, $userId, $db) {
    try {
        $fullName = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $bio = trim($data['bio'] ?? '');
        $locationCity = trim($data['location_city'] ?? '');
        $locationState = trim($data['location_state'] ?? '');
        $yearsExperience = (int)($data['years_experience'] ?? 0);
        $linkedinUrl = trim($data['linkedin_url'] ?? '');
        
        // Basic validation
        if (empty($fullName)) {
            return ['success' => false, 'message' => 'Full name is required'];
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Valid email is required'];
        }
        
        // Check if email is already taken by another user
        $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email is already taken by another user'];
        }
        
        // Update user profile
        $db->execute(
            "UPDATE users SET 
                full_name = ?, email = ?, phone = ?, bio = ?, 
                location_city = ?, location_state = ?, years_experience = ?, 
                linkedin_url = ?, updated_at = NOW() 
             WHERE id = ?",
            [
                $fullName, $email, $phone, $bio, 
                $locationCity, $locationState, $yearsExperience, 
                $linkedinUrl, $userId
            ]
        );
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
        
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

// FIXED: Update ALL social media - now saves all fields to database
function updateSocialMedia($data, $userId, $db) {
    try {
        $linkedinUrl = trim($data['linkedin_url'] ?? '');
        $facebookUrl = trim($data['facebook_url'] ?? '');
        $twitterUrl = trim($data['twitter_url'] ?? '');
        $instagramUrl = trim($data['instagram_url'] ?? '');
        $youtubeUrl = trim($data['youtube_url'] ?? '');
        $websiteUrl = trim($data['website_url'] ?? '');
        
        // Validate URLs if provided
        $urlFields = [
            'LinkedIn' => $linkedinUrl,
            'Facebook' => $facebookUrl,
            'Twitter' => $twitterUrl,
            'Instagram' => $instagramUrl,
            'YouTube' => $youtubeUrl,
            'Website' => $websiteUrl
        ];
        
        foreach ($urlFields as $fieldName => $url) {
            if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                return ['success' => false, 'message' => "Invalid {$fieldName} URL format"];
            }
        }
        
        // Update ALL social media fields
        $db->execute(
            "UPDATE users SET 
                linkedin_url = ?, facebook_url = ?, twitter_url = ?, 
                instagram_url = ?, youtube_url = ?, website_url = ?, 
                updated_at = NOW() 
             WHERE id = ?",
            [
                $linkedinUrl, $facebookUrl, $twitterUrl, 
                $instagramUrl, $youtubeUrl, $websiteUrl, $userId
            ]
        );
        
        return ['success' => true, 'message' => 'Social media links updated successfully'];
        
    } catch (Exception $e) {
        error_log("Social media update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update social media links: ' . $e->getMessage()];
    }
}

// Change password
function changePassword($data, $userId, $db) {
    try {
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            return ['success' => false, 'message' => 'All password fields are required'];
        }
        
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'New passwords do not match'];
        }
        
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters'];
        }
        
        // Verify current password
        $user = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->execute("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$newPasswordHash, $userId]);
        
        return ['success' => true, 'message' => 'Password changed successfully'];
        
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to change password'];
    }
}

// Delete business listing
function deleteListing($listingId, $userId, $db) {
    try {
        // Verify ownership
        $listing = $db->fetchOne(
            "SELECT id, title FROM business_listings WHERE id = ? AND user_id = ?",
            [$listingId, $userId]
        );
        
        if (!$listing) {
            return ['success' => false, 'message' => 'Listing not found or you do not have permission to delete it'];
        }
        
        // Delete related records first
        $db->execute("DELETE FROM listing_views WHERE business_listing_id = ?", [$listingId]);
        $db->execute("DELETE FROM user_interests WHERE business_listing_id = ?", [$listingId]);
        $db->execute("DELETE FROM uploaded_files WHERE business_listing_id = ?", [$listingId]);
        $db->execute("DELETE FROM team_members WHERE business_listing_id = ?", [$listingId]);
        $db->execute("DELETE FROM messages WHERE business_listing_id = ?", [$listingId]);
        
        // Delete the listing
        $db->execute("DELETE FROM business_listings WHERE id = ?", [$listingId]);
        
        return ['success' => true, 'message' => 'Listing deleted successfully'];
        
    } catch (Exception $e) {
        error_log("Delete listing error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete listing'];
    }
}

// Toggle listing status (active/inactive)
function toggleListingStatus($listingId, $userId, $db) {
    try {
        $listing = $db->fetchOne(
            "SELECT id, title, status FROM business_listings WHERE id = ? AND user_id = ?",
            [$listingId, $userId]
        );
        
        if (!$listing) {
            return ['success' => false, 'message' => 'Listing not found'];
        }
        
        $newStatus = $listing['status'] === 'active' ? 'inactive' : 'active';
        $db->execute("UPDATE business_listings SET status = ? WHERE id = ?", [$newStatus, $listingId]);
        
        $action = $newStatus === 'active' ? 'activated' : 'deactivated';
        return ['success' => true, 'message' => "Listing {$action} successfully"];
        
    } catch (Exception $e) {
        error_log("Toggle listing status error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update listing status'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Fundify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e5e7eb;
        }
        .tab-button.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
                    <p class="text-gray-600 mt-1">Manage your account and business information</p>
                </div>
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Profile Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <!-- Profile Photo Section -->
                    <div class="text-center mb-6">
                        <?php if (!empty($currentUser['profile_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($currentUser['profile_photo']); ?>?v=<?php echo time(); ?>" 
                                 alt="Profile Photo" class="profile-photo-preview mx-auto mb-4">
                        <?php else: ?>
                            <div class="profile-photo-preview mx-auto mb-4 bg-gradient-to-r from-blue-600 to-purple-600 flex items-center justify-center text-white text-3xl font-bold">
                                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></h3>
                        <p class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($currentUser['role']); ?></p>
                        
                        <!-- Photo Upload Form -->
                        <form method="POST" enctype="multipart/form-data" class="mt-4" id="photo-upload-form">
                            <input type="hidden" name="action" value="upload_photo">
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="hidden">
                            <label for="profile_photo" class="cursor-pointer inline-block bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                <i class="fas fa-camera mr-2"></i>Change Photo
                            </label>
                        </form>
                    </div>

                    <!-- Tab Navigation -->
                    <nav class="space-y-2">
                        <button class="tab-button <?php echo $activeTab === 'basic-info' ? 'active' : ''; ?> w-full text-left px-4 py-3 rounded-lg transition-colors" onclick="showTab('basic-info')">
                            <i class="fas fa-user mr-2"></i>Basic Info
                        </button>
                        <button class="tab-button <?php echo $activeTab === 'social-media' ? 'active' : ''; ?> w-full text-left px-4 py-3 rounded-lg transition-colors" onclick="showTab('social-media')">
                            <i class="fas fa-share-alt mr-2"></i>Social Media
                        </button>
                        <button class="tab-button <?php echo $activeTab === 'security' ? 'active' : ''; ?> w-full text-left px-4 py-3 rounded-lg transition-colors" onclick="showTab('security')">
                            <i class="fas fa-lock mr-2"></i>Security
                        </button>
                        <?php if ($currentUser['role'] === 'entrepreneur'): ?>
                        <button class="tab-button <?php echo $activeTab === 'listings' ? 'active' : ''; ?> w-full text-left px-4 py-3 rounded-lg transition-colors" onclick="showTab('listings')">
                            <i class="fas fa-list mr-2"></i>My Listings (<?php echo count($userListings); ?>)
                        </button>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-3">
                
                <!-- Basic Information Tab -->
                <div id="basic-info" class="tab-content <?php echo $activeTab === 'basic-info' ? 'active' : ''; ?>">
                    <div class="bg-white rounded-xl shadow-sm border p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-user text-white text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Basic Information</h2>
                                <p class="text-gray-600">Update your personal details</p>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Full Name *
                                    </label>
                                    <input type="text" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($currentUser['full_name']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           required>
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address *
                                    </label>
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           required>
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                           placeholder="+91 9876543210"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="years_experience" class="block text-sm font-medium text-gray-700 mb-2">
                                        Years of Experience
                                    </label>
                                    <input type="number" id="years_experience" name="years_experience" 
                                           value="<?php echo $currentUser['years_experience'] ?? ''; ?>"
                                           min="0" max="50"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="location_city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input type="text" id="location_city" name="location_city" 
                                           value="<?php echo htmlspecialchars($currentUser['location_city'] ?? ''); ?>"
                                           placeholder="Ahmedabad"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="location_state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State
                                    </label>
                                    <select id="location_state" name="location_state" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select State</option>
                                        <?php
                                        $states = ['Gujarat', 'Maharashtra', 'Karnataka', 'Delhi', 'Tamil Nadu', 'West Bengal', 'Rajasthan', 'Uttar Pradesh', 'Haryana', 'Punjab'];
                                        foreach ($states as $state):
                                        ?>
                                        <option value="<?php echo $state; ?>" <?php echo ($currentUser['location_state'] ?? '') === $state ? 'selected' : ''; ?>><?php echo $state; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                                    Bio/About Me
                                </label>
                                <textarea id="bio" name="bio" rows="4"
                                          placeholder="Tell us about yourself, your background, expertise, and what you're passionate about..."
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                            </div>

                            <div class="mt-6">
                                <label for="linkedin_url_basic" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fab fa-linkedin text-blue-600 mr-2"></i>LinkedIn Profile
                                </label>
                                <input type="url" id="linkedin_url_basic" name="linkedin_url" 
                                       value="<?php echo htmlspecialchars($currentUser['linkedin_url'] ?? ''); ?>"
                                       placeholder="https://linkedin.com/in/yourprofile"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Social Media Tab - FIXED FORM WITH CURRENT VALUES -->
                <div id="social-media" class="tab-content <?php echo $activeTab === 'social-media' ? 'active' : ''; ?>">
                    <div class="bg-white rounded-xl shadow-sm border p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-share-alt text-white text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Social Media & Links</h2>
                                <p class="text-gray-600">Connect your social profiles and website</p>
                            </div>
                        </div>

                        <!-- FIXED FORM FOR SOCIAL MEDIA - NOW SHOWS CURRENT VALUES -->
                        <form method="POST">
                            <input type="hidden" name="action" value="update_social">
                            
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div>
                                        <label for="linkedin_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fab fa-linkedin text-blue-600 mr-2"></i>LinkedIn Profile
                                        </label>
                                        <input type="url" id="linkedin_url" name="linkedin_url" 
                                               value="<?php echo htmlspecialchars($currentUser['linkedin_url'] ?? ''); ?>"
                                               placeholder="https://linkedin.com/in/yourprofile"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label for="website_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-globe text-green-600 mr-2"></i>Website/Portfolio
                                        </label>
                                        <input type="url" id="website_url" name="website_url" 
                                               value="<?php echo htmlspecialchars($currentUser['website_url'] ?? ''); ?>"
                                               placeholder="https://yourwebsite.com"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label for="twitter_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fab fa-x-twitter text-gray-900 mr-2"></i>X (Twitter) Profile
                                        </label>
                                        <input type="url" id="twitter_url" name="twitter_url" 
                                               value="<?php echo htmlspecialchars($currentUser['twitter_url'] ?? ''); ?>" 
                                               placeholder="https://x.com/yourusername"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fab fa-facebook text-blue-800 mr-2"></i>Facebook Profile
                                        </label>
                                        <input type="url" id="facebook_url" name="facebook_url" 
                                               value="<?php echo htmlspecialchars($currentUser['facebook_url'] ?? ''); ?>" 
                                               placeholder="https://facebook.com/yourprofile"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fab fa-instagram text-pink-600 mr-2"></i>Instagram Profile
                                        </label>
                                        <input type="url" id="instagram_url" name="instagram_url" 
                                               value="<?php echo htmlspecialchars($currentUser['instagram_url'] ?? ''); ?>" 
                                               placeholder="https://instagram.com/yourusername"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fab fa-youtube text-red-600 mr-2"></i>YouTube Channel
                                        </label>
                                        <input type="url" id="youtube_url" name="youtube_url" 
                                               value="<?php echo htmlspecialchars($currentUser['youtube_url'] ?? ''); ?>" 
                                               placeholder="https://youtube.com/@yourchannel"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                    <div class="text-sm text-blue-700">
                                        <strong>Note:</strong> All social media links are optional. Adding your professional profiles helps investors and partners learn more about you and your work.
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>Update Social Links
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="security" class="tab-content <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
                    <div class="bg-white rounded-xl shadow-sm border p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-lock text-white text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Account Security</h2>
                                <p class="text-gray-600">Manage your password and security settings</p>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Current Password *
                                    </label>
                                    <input type="password" id="current_password" name="current_password" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           required>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            New Password *
                                        </label>
                                        <input type="password" id="new_password" name="new_password" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               required>
                                    </div>

                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm New Password *
                                        </label>
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                                <div class="flex items-start">
                                    <i class="fas fa-shield-alt text-yellow-500 mt-1 mr-3"></i>
                                    <div class="text-sm text-yellow-700">
                                        <strong>Password Requirements:</strong> Minimum 8 characters. Use a combination of letters, numbers, and symbols for better security.
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6">
                                <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- My Listings Tab (Entrepreneurs Only) -->
                <?php if ($currentUser['role'] === 'entrepreneur'): ?>
                <div id="listings" class="tab-content <?php echo $activeTab === 'listings' ? 'active' : ''; ?>">
                    <div class="bg-white rounded-xl shadow-sm border p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-list text-white text-lg"></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900">My Business Listings</h2>
                                    <p class="text-gray-600">Manage your business opportunities (<?php echo count($userListings); ?> total)</p>
                                </div>
                            </div>
                            <a href="create-listing.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Create New Listing
                            </a>
                        </div>

                        <?php if (empty($userListings)): ?>
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <i class="fas fa-inbox text-6xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Listings Yet</h3>
                            <p class="text-gray-600 mb-6">Create your first business listing to start connecting with investors.</p>
                            <a href="create-listing.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-rocket mr-2"></i>Create Your First Listing
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($userListings as $listing): ?>
                            <div class="border border-gray-200 rounded-lg p-6 hover:border-gray-300 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900 mr-3"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?php echo getStatusColor($listing['status']); ?>">
                                                <?php echo ucfirst($listing['status']); ?>
                                            </span>
                                            <?php if ($listing['is_featured']): ?>
                                            <span class="inline-block px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full ml-2">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-gray-600 mb-3"><?php echo htmlspecialchars(substr($listing['short_pitch'], 0, 150)); ?>...</p>
                                        
                                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500">Company:</span>
                                                <div class="font-medium"><?php echo htmlspecialchars($listing['company_name']); ?></div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Funding:</span>
                                                <div class="font-medium">₹<?php echo number_format($listing['funding_amount_needed'] / 10000000, 1); ?> Cr</div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Views:</span>
                                                <div class="font-medium"><?php echo number_format($listing['view_count']); ?></div>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Created:</span>
                                                <div class="font-medium"><?php echo date('M j, Y', strtotime($listing['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col space-y-2 ml-6">
                                        <a href="listing/<?php echo htmlspecialchars($listing['slug']); ?>" 
                                           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors text-center">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <a href="edit-listing.php?id=<?php echo $listing['id']; ?>" 
                                           class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition-colors text-center">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </a>
                                        
                                        <!-- Toggle Active/Inactive -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_listing_status">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                            <button type="submit" class="w-full <?php echo $listing['status'] === 'active' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700'; ?> text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                                <i class="fas fa-<?php echo $listing['status'] === 'active' ? 'pause' : 'play'; ?> mr-1"></i>
                                                <?php echo $listing['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this listing? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete_listing">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition-colors">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Find and activate the corresponding button
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => {
                if (btn.onclick && btn.onclick.toString().includes(tabId)) {
                    btn.classList.add('active');
                }
            });
        }

        // Photo upload with immediate preview and auto-submit
        document.getElementById('profile_photo').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Show preview immediately
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.profile-photo-preview');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace div with img
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.className = preview.className;
                        newImg.alt = 'Profile Photo';
                        preview.parentNode.replaceChild(newImg, preview);
                    }
                };
                reader.readAsDataURL(this.files[0]);
                
                // Auto-submit the form
                document.getElementById('photo-upload-form').submit();
            }
        });

        // Initialize the correct tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = '<?php echo $activeTab; ?>';
            showTab(activeTab);
        });
    </script>
</body>
</html>

<?php
// Helper function for status colors
function getStatusColor($status) {
    switch ($status) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'draft': return 'bg-yellow-100 text-yellow-800';
        case 'pending_approval': return 'bg-blue-100 text-blue-800';
        case 'inactive': return 'bg-gray-100 text-gray-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?>