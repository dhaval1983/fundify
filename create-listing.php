<?php
// create-listing-v3.php - Version 3 with email notifications and save draft
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

// Initialize classes
$user = new User();

// Check if user is logged in and is entrepreneur
$currentUser = $user->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

if ($currentUser['role'] !== 'entrepreneur') {
    header('Location: dashboard.php?error=only-entrepreneurs-can-create-listings');
    exit;
}

// Handle AJAX draft save
if (isset($_POST['action']) && $_POST['action'] === 'save_draft') {
    header('Content-Type: application/json');
    
    try {
        $db = Database::getInstance();
        
        // Save draft data to session
        $_SESSION['listing_draft'] = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'short_pitch' => trim($_POST['short_pitch'] ?? ''),
            'detailed_description' => trim($_POST['detailed_description'] ?? ''),
            'industry' => trim($_POST['industry'] ?? ''),
            'business_stage' => trim($_POST['business_stage'] ?? ''),
            'funding_amount_needed' => $_POST['funding_amount_needed'] ?? '',
            'current_monthly_revenue' => $_POST['current_monthly_revenue'] ?? '',
            'equity_offered_min' => $_POST['equity_offered_min'] ?? '',
            'equity_offered_max' => $_POST['equity_offered_max'] ?? '',
            'fund_usage_plan' => trim($_POST['fund_usage_plan'] ?? ''),
            'target_market' => trim($_POST['target_market'] ?? ''),
            'location_city' => trim($_POST['location_city'] ?? ''),
            'location_state' => trim($_POST['location_state'] ?? ''),
            'saved_at' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode(['success' => true, 'message' => 'Draft saved successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save draft']);
    }
    exit;
}

// Load draft data if exists
$draftData = $_SESSION['listing_draft'] ?? [];

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $errors = [];
    
    // Enhanced validation
    $company_name = trim($_POST['company_name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $short_pitch = trim($_POST['short_pitch'] ?? '');
    $detailed_description = trim($_POST['detailed_description'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $business_stage = trim($_POST['business_stage'] ?? '');
    $funding_amount = (float)($_POST['funding_amount_needed'] ?? 0);
    $min_equity = (float)($_POST['equity_offered_min'] ?? 0);
    $max_equity = (float)($_POST['equity_offered_max'] ?? 0);
    $current_revenue = (float)($_POST['current_monthly_revenue'] ?? 0);
    $fund_usage = trim($_POST['fund_usage_plan'] ?? '');
    $target_market = trim($_POST['target_market'] ?? '');
    $location_city = trim($_POST['location_city'] ?? '');
    $location_state = trim($_POST['location_state'] ?? '');
    
    // Validation
    if (empty($company_name)) $errors[] = 'Company name is required';
    if (empty($title)) $errors[] = 'Business title is required';
    if (empty($short_pitch)) $errors[] = 'Short pitch is required';
    if (empty($detailed_description)) $errors[] = 'Detailed description is required';
    if (empty($industry)) $errors[] = 'Industry is required';
    if (empty($business_stage)) $errors[] = 'Business stage is required';
    if ($funding_amount <= 0) $errors[] = 'Funding amount must be greater than 0';
    if ($min_equity <= 0 || $max_equity <= 0) $errors[] = 'Equity percentages must be greater than 0';
    if ($min_equity > $max_equity) $errors[] = 'Minimum equity cannot be greater than maximum equity';
    if (empty($fund_usage)) $errors[] = 'Fund usage plan is required';
    if (empty($location_city) || empty($location_state)) $errors[] = 'Company location is required';
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            // Create or update company record
            $existingCompany = $db->fetchOne(
                "SELECT id FROM companies WHERE user_id = ? AND company_name = ?",
                [$currentUser['id'], $company_name]
            );
            
            if ($existingCompany) {
                $companyId = $existingCompany['id'];
                // Update existing company
                $db->execute(
                    "UPDATE companies SET location_city = ?, location_state = ?, updated_at = NOW() WHERE id = ?",
                    [$location_city, $location_state, $companyId]
                );
            } else {
                // Create new company
                $db->execute(
                    "INSERT INTO companies (user_id, company_name, location_city, location_state, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, NOW(), NOW())",
                    [$currentUser['id'], $company_name, $location_city, $location_state]
                );
                $companyId = $db->lastInsertId();
            }
            
            // Generate slug
            $slug = generateSlug($title, $db);
            
            // Insert business listing with more fields
            $db->execute(
                "INSERT INTO business_listings (
                    company_id, user_id, title, short_pitch, detailed_description, industry, business_stage,
                    funding_amount_needed, current_monthly_revenue, equity_offered_min, equity_offered_max,
                    fund_usage_plan, target_market, status, slug, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())",
                [
                    $companyId, $currentUser['id'], $title, $short_pitch, $detailed_description, 
                    $industry, $business_stage, $funding_amount, $current_revenue, 
                    $min_equity, $max_equity, $fund_usage, $target_market, $slug
                ]
            );
            
            $listingId = $db->lastInsertId();
            $db->commit();
            
            // Clear draft from session
            unset($_SESSION['listing_draft']);
            
            // Send email notifications
            try {
                $mailer = new Mailer();
                
                // Email to admin
                $adminSubject = "New Business Listing Created - Fundify";
                $adminBody = createAdminNotificationEmail($currentUser, $title, $company_name, $industry, $funding_amount, $slug);
                $adminEmailSent = $mailer->send('info@fundify.isowebtech.com', $adminSubject, $adminBody, true);
                
                // Email to entrepreneur (confirmation)
                $entrepreneurSubject = "Your Business Listing is Live - Fundify";
                $entrepreneurBody = createEntrepreneurConfirmationEmail($currentUser, $title, $company_name, $slug);
                $entrepreneurEmailSent = $mailer->send($currentUser['email'], $entrepreneurSubject, $entrepreneurBody, true);
                
                if (!$adminEmailSent || !$entrepreneurEmailSent) {
                    error_log("Email notification failed - Admin: " . ($adminEmailSent ? 'OK' : 'FAILED') . 
                             " - Entrepreneur: " . ($entrepreneurEmailSent ? 'OK' : 'FAILED'));
                }
                
            } catch (Exception $e) {
                // Don't fail the listing creation if email fails
                error_log("Email notification error: " . $e->getMessage());
            }
            
            $message = 'Business listing created successfully! Investors will now be able to discover your opportunity.';
            $messageType = 'success';
            $_POST = []; // Clear form
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Listing creation error: " . $e->getMessage());
            $message = 'Error creating listing. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = implode(', ', $errors);
        $messageType = 'error';
    }
}

function generateSlug($title, $db) {
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title));
    $slug = trim($slug, '-');
    
    // Ensure uniqueness
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $exists = $db->fetchOne("SELECT id FROM business_listings WHERE slug = ?", [$slug]);
        if (!$exists) break;
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

function createAdminNotificationEmail($user, $title, $companyName, $industry, $fundingAmount, $slug) {
    $fundingFormatted = '₹' . number_format($fundingAmount / 10000000, 1) . ' Cr';
    $listingUrl = 'https://fundify.isowebtech.com/listing/' . $slug;
    
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 20px; border: 1px solid #e0e0e0; border-radius: 0 0 8px 8px; }
        .btn { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .details { background: #f8f9ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 New Business Listing</h1>
            <p>Fundify Admin Notification</p>
        </div>
        
        <div class="content">
            <h2>New Listing Created</h2>
            
            <div class="details">
                <p><strong>Entrepreneur:</strong> ' . htmlspecialchars($user['full_name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($user['email']) . '</p>
                <p><strong>Company:</strong> ' . htmlspecialchars($companyName) . '</p>
                <p><strong>Listing Title:</strong> ' . htmlspecialchars($title) . '</p>
                <p><strong>Industry:</strong> ' . htmlspecialchars($industry) . '</p>
                <p><strong>Funding Needed:</strong> ' . $fundingFormatted . '</p>
            </div>
            
            <p>A new business listing has been created on the platform. Please review it and take any necessary actions.</p>
            
            <a href="' . $listingUrl . '" class="btn">View Listing</a>
            <a href="https://fundify.isowebtech.com/admin/manage.php" class="btn">Admin Dashboard</a>
        </div>
    </div>
</body>
</html>';
}

function createEntrepreneurConfirmationEmail($user, $title, $companyName, $slug) {
    $listingUrl = 'https://fundify.isowebtech.com/listing/' . $slug;
    
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #e0e0e0; border-radius: 0 0 8px 8px; }
        .btn { display: inline-block; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
        .tips { background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Your Listing is Live!</h1>
            <p>Congratulations, ' . htmlspecialchars($user['full_name']) . '</p>
        </div>
        
        <div class="content">
            <h2>Your Business Listing is Now Active</h2>
            <p><strong>Company:</strong> ' . htmlspecialchars($companyName) . '</p>
            <p><strong>Listing:</strong> ' . htmlspecialchars($title) . '</p>
            
            <p>Your business listing has been successfully created and is now visible to our network of investors!</p>
            
            <div class="tips">
                <h3>📈 What Happens Next?</h3>
                <ul>
                    <li>Your listing is now live and searchable by investors</li>
                    <li>You\'ll receive email notifications when investors express interest</li>
                    <li>Check your dashboard regularly for investor messages</li>
                    <li>Consider uploading additional documents like pitch decks</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="' . $listingUrl . '" class="btn">View Your Listing</a>
                <a href="https://fundify.isowebtech.com/dashboard.php" class="btn">Go to Dashboard</a>
            </div>
            
            <p style="margin-top: 30px;">
                <strong>Need Help?</strong><br>
                Contact our support team at <a href="mailto:info@fundify.isowebtech.com">info@fundify.isowebtech.com</a>
            </p>
        </div>
    </div>
</body>
</html>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Business Listing - Fundify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-6xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Your Business Listing</h1>
                    <p class="text-gray-600 mt-1">Share your startup story with potential investors</p>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (!empty($draftData)): ?>
                    <div class="text-sm text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                        <i class="fas fa-save mr-1"></i>Draft saved: <?php echo date('M j, H:i', strtotime($draftData['saved_at'])); ?>
                    </div>
                    <?php endif; ?>
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-6 py-8">
        
        <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
            <?php if ($messageType === 'success'): ?>
                <div class="mt-2">
                    <a href="browse.php" class="text-green-700 underline">View All Listings</a> | 
                    <a href="dashboard.php" class="text-green-700 underline">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="listing-form" class="space-y-8">
            
            <!-- Company Information -->
            <div class="bg-white rounded-xl shadow-sm border p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-building text-white text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Company Information</h2>
                            <p class="text-gray-600">Basic details about your company</p>
                        </div>
                    </div>
                    <button type="button" id="save-draft-btn" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-save mr-1"></i>Save Draft
                    </button>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="lg:col-span-2">
                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Company Name *
                        </label>
                        <input type="text" id="company_name" name="company_name" 
                               value="<?php echo htmlspecialchars($draftData['company_name'] ?? $_POST['company_name'] ?? ''); ?>"
                               placeholder="TechLogic Solutions Pvt Ltd"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>

                    <div>
                        <label for="location_city" class="block text-sm font-medium text-gray-700 mb-2">
                            City *
                        </label>
                        <input type="text" id="location_city" name="location_city" 
                               value="<?php echo htmlspecialchars($draftData['location_city'] ?? $_POST['location_city'] ?? ''); ?>"
                               placeholder="Ahmedabad"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>

                    <div>
                        <label for="location_state" class="block text-sm font-medium text-gray-700 mb-2">
                            State *
                        </label>
                        <select id="location_state" name="location_state" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                            <option value="">Select State</option>
                            <?php
                            $states = ['Gujarat', 'Maharashtra', 'Karnataka', 'Delhi', 'Tamil Nadu', 'West Bengal', 'Rajasthan', 'Uttar Pradesh'];
                            $selectedState = $draftData['location_state'] ?? $_POST['location_state'] ?? '';
                            foreach ($states as $state):
                            ?>
                            <option value="<?php echo $state; ?>" <?php echo $selectedState === $state ? 'selected' : ''; ?>><?php echo $state; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Business Details -->
            <div class="bg-white rounded-xl shadow-sm border p-8">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-rocket text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Business Details</h2>
                        <p class="text-gray-600">Describe your business opportunity</p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Business Title *
                        </label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($draftData['title'] ?? $_POST['title'] ?? ''); ?>"
                               placeholder="e.g., AI-Powered Business Automation for SMEs"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>

                    <div>
                        <label for="short_pitch" class="block text-sm font-medium text-gray-700 mb-2">
                            Short Pitch *
                        </label>
                        <textarea id="short_pitch" name="short_pitch" rows="3" maxlength="300"
                                  placeholder="Summarize your business in 1-2 compelling sentences"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  required><?php echo htmlspecialchars($draftData['short_pitch'] ?? $_POST['short_pitch'] ?? ''); ?></textarea>
                        <div class="flex justify-between text-sm text-gray-500 mt-1">
                            <span>This will be the first thing investors see</span>
                            <span id="pitch-counter">0/300</span>
                        </div>
                    </div>

                    <div>
                        <label for="detailed_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Detailed Description *
                        </label>
                        <textarea id="detailed_description" name="detailed_description" rows="6"
                                  placeholder="Provide a comprehensive description of your business, the problem you solve, your solution, and what makes you unique..."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  required><?php echo htmlspecialchars($draftData['detailed_description'] ?? $_POST['detailed_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">
                                Industry *
                            </label>
                            <select id="industry" name="industry" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required>
                                <option value="">Select Industry</option>
                                <?php
                                $industries = ['Technology', 'Healthcare', 'Fintech', 'EdTech', 'E-commerce', 'AgTech', 'CleanTech', 'Logistics', 'Food & Beverage'];
                                $selectedIndustry = $draftData['industry'] ?? $_POST['industry'] ?? '';
                                foreach ($industries as $industry):
                                ?>
                                <option value="<?php echo $industry; ?>" <?php echo $selectedIndustry === $industry ? 'selected' : ''; ?>><?php echo $industry; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="business_stage" class="block text-sm font-medium text-gray-700 mb-2">
                                Business Stage *
                            </label>
                            <select id="business_stage" name="business_stage" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required>
                                <option value="">Select Stage</option>
                                <?php
                                $stages = [
                                    'idea' => 'Idea Stage',
                                    'mvp' => 'MVP/Prototype', 
                                    'early_revenue' => 'Early Revenue',
                                    'growth' => 'Growth Stage',
                                    'established' => 'Established'
                                ];
                                $selectedStage = $draftData['business_stage'] ?? $_POST['business_stage'] ?? '';
                                foreach ($stages as $value => $label):
                                ?>
                                <option value="<?php echo $value; ?>" <?php echo $selectedStage === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="target_market" class="block text-sm font-medium text-gray-700 mb-2">
                            Target Market
                        </label>
                        <textarea id="target_market" name="target_market" rows="3"
                                  placeholder="Describe your target customers and market opportunity..."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($draftData['target_market'] ?? $_POST['target_market'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="bg-white rounded-xl shadow-sm border p-8">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-chart-pie text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Investment & Financial Details</h2>
                        <p class="text-gray-600">Funding requirements and current metrics</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="funding_amount_needed" class="block text-sm font-medium text-gray-700 mb-2">
                            Funding Needed (₹) *
                        </label>
                        <input type="number" id="funding_amount_needed" name="funding_amount_needed" 
                               value="<?php echo $draftData['funding_amount_needed'] ?? $_POST['funding_amount_needed'] ?? ''; ?>"
                               min="100000" step="100000" placeholder="25000000"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                        <p class="text-sm text-gray-500 mt-1">Amount in INR</p>
                    </div>

                    <div>
                        <label for="current_monthly_revenue" class="block text-sm font-medium text-gray-700 mb-2">
                            Current Monthly Revenue (₹)
                        </label>
                        <input type="number" id="current_monthly_revenue" name="current_monthly_revenue" 
                               value="<?php echo $draftData['current_monthly_revenue'] ?? $_POST['current_monthly_revenue'] ?? ''; ?>"
                               min="0" step="10000" placeholder="850000"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">Leave blank if no revenue yet</p>
                    </div>

                    <div>
                        <label for="equity_offered_min" class="block text-sm font-medium text-gray-700 mb-2">
                            Min Equity Offered (%) *
                        </label>
                        <input type="number" id="equity_offered_min" name="equity_offered_min" 
                               value="<?php echo $draftData['equity_offered_min'] ?? $_POST['equity_offered_min'] ?? ''; ?>"
                               min="0.1" max="100" step="0.1" placeholder="8.0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>

                    <div>
                        <label for="equity_offered_max" class="block text-sm font-medium text-gray-700 mb-2">
                            Max Equity Offered (%) *
                        </label>
                        <input type="number" id="equity_offered_max" name="equity_offered_max" 
                               value="<?php echo $draftData['equity_offered_max'] ?? $_POST['equity_offered_max'] ?? ''; ?>"
                               min="0.1" max="100" step="0.1" placeholder="15.0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>
                </div>

                <div class="mt-6">
                    <label for="fund_usage_plan" class="block text-sm font-medium text-gray-700 mb-2">
                        Fund Usage Plan *
                    </label>
                    <textarea id="fund_usage_plan" name="fund_usage_plan" rows="4"
                              placeholder="Product development (40%), Marketing & Sales (35%), Team expansion (15%), Operations (10%)"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              required><?php echo htmlspecialchars($draftData['fund_usage_plan'] ?? $_POST['fund_usage_plan'] ?? ''); ?></textarea>
                </div>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-700">
                            <strong>Investment Tip:</strong> Most startups offer 10-25% equity in early funding rounds. Be specific about how you'll use the investment funds.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-6">
                <div class="flex space-x-4">
                    <a href="dashboard.php" class="px-6 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                        Cancel
                    </a>
                    <button type="button" id="save-draft-btn-2" class="px-6 py-3 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Draft
                    </button>
                </div>
                
                <button type="submit" 
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-colors shadow-lg">
                    <i class="fas fa-rocket mr-2"></i>Create Business Listing
                </button>
            </div>
        </form>
    </div>

    <!-- Draft Save Notification -->
    <div id="draft-notification" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg hidden">
        <i class="fas fa-check mr-2"></i>Draft saved!
    </div>

    <script>
        // Character counter for pitch
        document.getElementById('short_pitch').addEventListener('input', function() {
            const counter = document.getElementById('pitch-counter');
            const length = this.value.length;
            counter.textContent = `${length}/300`;
            
            if (length > 280) {
                counter.classList.add('text-red-500');
            } else {
                counter.classList.remove('text-red-500');
            }
        });

        // Save draft functionality
        function saveDraft() {
            const formData = new FormData(document.getElementById('listing-form'));
            formData.append('action', 'save_draft');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Draft saved!');
                } else {
                    showNotification('Failed to save draft', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to save draft', 'error');
            });
        }

        // Auto-save every 2 minutes
        setInterval(saveDraft, 2 * 60 * 1000);

        // Manual save draft buttons
        document.getElementById('save-draft-btn').addEventListener('click', saveDraft);
        document.getElementById('save-draft-btn-2').addEventListener('click', saveDraft);

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('draft-notification');
            notification.textContent = message;
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            notification.classList.remove('hidden');
            
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const minEquity = parseFloat(document.getElementById('equity_offered_min').value) || 0;
            const maxEquity = parseFloat(document.getElementById('equity_offered_max').value) || 0;
            
            if (minEquity > maxEquity) {
                e.preventDefault();
                alert('Minimum equity cannot be greater than maximum equity');
                return false;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating Listing...';
        });

        // Initialize character counter
        document.addEventListener('DOMContentLoaded', function() {
            const pitchField = document.getElementById('short_pitch');
            if (pitchField.value) {
                const counter = document.getElementById('pitch-counter');
                counter.textContent = `${pitchField.value.length}/300`;
            }
        });
    </script>
</body>
</html>