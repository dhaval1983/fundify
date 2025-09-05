<?php
// process-listing.php - Enhanced Business Listing Processing
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
$businessListing = new BusinessListing();

// Check if user is logged in and is entrepreneur
$currentUser = $user->getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'entrepreneur') {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Check email verification
if (!$currentUser['email_verified']) {
    Utils::redirect('dashboard.php', 'Please verify your email address before creating listings.', 'error');
}

$response = ['success' => false, 'errors' => [], 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input data
        $companyData = [
            'company_name' => Utils::sanitizeInput($_POST['company_name'] ?? ''),
            'brand_name' => Utils::sanitizeInput($_POST['brand_name'] ?? ''),
            'founded_year' => (int)($_POST['founded_year'] ?? date('Y')),
            'website_url' => filter_var($_POST['website_url'] ?? '', FILTER_SANITIZE_URL),
            'location_city' => Utils::sanitizeInput($_POST['location_city'] ?? ''),
            'location_state' => Utils::sanitizeInput($_POST['location_state'] ?? ''),
            'team_size' => Utils::sanitizeInput($_POST['team_size'] ?? ''),
            'user_id' => $currentUser['id']
        ];

        $listingData = [
            'title' => Utils::sanitizeInput($_POST['title'] ?? ''),
            'short_pitch' => Utils::sanitizeInput($_POST['short_pitch'] ?? ''),
            'detailed_description' => Utils::sanitizeInput($_POST['detailed_description'] ?? ''),
            'industry' => Utils::sanitizeInput($_POST['industry'] ?? ''),
            'business_stage' => Utils::sanitizeInput($_POST['business_stage'] ?? ''),
            'funding_amount_needed' => (float)($_POST['funding_amount_needed'] ?? 0),
            'current_monthly_revenue' => (float)($_POST['current_monthly_revenue'] ?? 0),
            'current_annual_revenue' => (float)($_POST['current_annual_revenue'] ?? 0),
            'equity_offered_min' => (float)($_POST['equity_offered_min'] ?? 0),
            'equity_offered_max' => (float)($_POST['equity_offered_max'] ?? 0),
            'fund_usage_plan' => Utils::sanitizeInput($_POST['fund_usage_plan'] ?? ''),
            'target_market' => Utils::sanitizeInput($_POST['target_market'] ?? ''),
            'revenue_model' => Utils::sanitizeInput($_POST['revenue_model'] ?? ''),
            'unique_selling_proposition' => Utils::sanitizeInput($_POST['unique_selling_proposition'] ?? ''),
            'traction_achieved' => Utils::sanitizeInput($_POST['traction_achieved'] ?? ''),
            'previous_funding' => (float)($_POST['previous_funding'] ?? 0),
            'current_valuation' => (float)($_POST['current_valuation'] ?? 0)
        ];

        // Validation
        $errors = [];

        // Company validation
        if (empty($companyData['company_name'])) {
            $errors[] = 'Company name is required';
        }

        if (empty($companyData['location_city']) || empty($companyData['location_state'])) {
            $errors[] = 'Company location is required';
        }

        // Listing validation
        if (empty($listingData['title'])) {
            $errors[] = 'Business listing title is required';
        }

        if (empty($listingData['short_pitch'])) {
            $errors[] = 'One-line pitch is required';
        } elseif (strlen($listingData['short_pitch']) > 300) {
            $errors[] = 'One-line pitch cannot exceed 300 characters';
        }

        if (empty($listingData['detailed_description'])) {
            $errors[] = 'Detailed business description is required';
        }

        if (empty($listingData['industry'])) {
            $errors[] = 'Industry selection is required';
        }

        if (empty($listingData['business_stage'])) {
            $errors[] = 'Business stage is required';
        }

        if ($listingData['funding_amount_needed'] <= 0) {
            $errors[] = 'Valid funding amount is required';
        }

        if ($listingData['equity_offered_min'] <= 0 || $listingData['equity_offered_max'] <= 0) {
            $errors[] = 'Valid equity range is required';
        }

        if ($listingData['equity_offered_min'] > $listingData['equity_offered_max']) {
            $errors[] = 'Minimum equity cannot be greater than maximum equity';
        }

        if (empty($listingData['fund_usage_plan'])) {
            $errors[] = 'Fund usage plan is required';
        }

        // Check if this is a draft save
        $isDraft = isset($_POST['save_draft']) && $_POST['save_draft'] == '1';
        
        if ($isDraft) {
            // For drafts, we can be more lenient with validation
            $listingData['status'] = 'draft';
        } else {
            // Full validation for final submission
            if (!empty($errors)) {
                $response['errors'] = $errors;
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            
            if (!isset($_POST['agree_terms'])) {
                $errors[] = 'You must agree to the terms and conditions';
            }
            
            $listingData['status'] = 'active'; // Or 'pending_approval' if you want manual approval
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        // Database operations
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Check if company already exists for this user
            $existingCompany = $db->fetchOne(
                "SELECT id FROM companies WHERE user_id = ? AND company_name = ?",
                [$currentUser['id'], $companyData['company_name']]
            );

            if ($existingCompany) {
                $companyId = $existingCompany['id'];
                
                // Update existing company
                $db->execute(
                    "UPDATE companies SET brand_name = ?, founded_year = ?, website_url = ?, 
                     location_city = ?, location_state = ?, team_size = ?, updated_at = NOW() 
                     WHERE id = ?",
                    [
                        $companyData['brand_name'],
                        $companyData['founded_year'],
                        $companyData['website_url'],
                        $companyData['location_city'],
                        $companyData['location_state'],
                        $companyData['team_size'],
                        $companyId
                    ]
                );
            } else {
                // Create new company
                $db->execute(
                    "INSERT INTO companies (user_id, company_name, brand_name, founded_year, 
                     website_url, location_city, location_state, team_size, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [
                        $currentUser['id'],
                        $companyData['company_name'],
                        $companyData['brand_name'],
                        $companyData['founded_year'],
                        $companyData['website_url'],
                        $companyData['location_city'],
                        $companyData['location_state'],
                        $companyData['team_size']
                    ]
                );
                $companyId = $db->lastInsertId();
            }

            // Create business listing
            $slug = generateSlug($listingData['title']);
            
            $db->execute(
                "INSERT INTO business_listings (
                    company_id, user_id, title, short_pitch, detailed_description, 
                    industry, business_stage, funding_amount_needed, current_monthly_revenue, 
                    current_annual_revenue, equity_offered_min, equity_offered_max, 
                    fund_usage_plan, previous_funding, current_valuation, 
                    target_market, revenue_model, unique_selling_proposition, 
                    traction_achieved, status, slug, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $companyId,
                    $currentUser['id'],
                    $listingData['title'],
                    $listingData['short_pitch'],
                    $listingData['detailed_description'],
                    $listingData['industry'],
                    $listingData['business_stage'],
                    $listingData['funding_amount_needed'],
                    $listingData['current_monthly_revenue'],
                    $listingData['current_annual_revenue'],
                    $listingData['equity_offered_min'],
                    $listingData['equity_offered_max'],
                    $listingData['fund_usage_plan'],
                    $listingData['previous_funding'],
                    $listingData['current_valuation'],
                    $listingData['target_market'],
                    $listingData['revenue_model'],
                    $listingData['unique_selling_proposition'],
                    $listingData['traction_achieved'],
                    $listingData['status'],
                    $slug
                ]
            );

            $listingId = $db->lastInsertId();

            // Handle file uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $uploadResults = handleFileUploads($_FILES['documents'], $listingId);
                if (!empty($uploadResults['errors'])) {
                    // Log upload errors but don't fail the entire process
                    error_log("File upload errors: " . implode(', ', $uploadResults['errors']));
                }
            }

            $db->commit();

            if ($isDraft) {
                $response['success'] = true;
                $response['message'] = 'Draft saved successfully';
            } else {
                $response['success'] = true;
                $response['message'] = 'Business listing created successfully!';
                $response['redirect'] = 'listing/' . $slug;
                
                // Send notification email to admin (optional)
                try {
                    $mailer = new Mailer();
                    $mailer->send(
                        'admin@fundify.isowebtech.com',
                        'New Business Listing Created',
                        "New listing: {$listingData['title']} by {$currentUser['full_name']}"
                    );
                } catch (Exception $e) {
                    // Don't fail if email sending fails
                    error_log("Failed to send admin notification: " . $e->getMessage());
                }
            }

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Listing creation error: " . $e->getMessage());
        $response['errors'] = ['An error occurred while creating your listing. Please try again.'];
    }
}

// Return JSON response for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle regular form submission
if ($response['success']) {
    if (isset($response['redirect'])) {
        Utils::redirect($response['redirect'], $response['message'], 'success');
    } else {
        Utils::redirect('dashboard.php', $response['message'], 'success');
    }
} else {
    Utils::redirect('create-listing.php', implode(', ', $response['errors']), 'error');
}

/**
 * Generate SEO-friendly slug from title
 */
function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Ensure uniqueness
    $db = Database::getInstance();
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

/**
 * Handle file uploads
 */
function handleFileUploads($files, $listingId) {
    $results = ['success' => [], 'errors' => []];
    $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    $uploadDir = __DIR__ . '/uploads/listings/' . $listingId . '/';
    
    // Create upload directory
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $results['errors'][] = 'Failed to create upload directory';
            return $results;
        }
    }
    
    $db = Database::getInstance();
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $originalName = $files['name'][$i];
        $tmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        
        // Get file extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($extension, $allowedTypes)) {
            $results['errors'][] = "File type not allowed: $originalName";
            continue;
        }
        
        // Validate file size
        if ($fileSize > $maxSize) {
            $results['errors'][] = "File too large: $originalName";
            continue;
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($tmpName, $filepath)) {
            // Determine file type based on extension
            $fileType = getFileType($extension);
            
            // Save to database
            try {
                $db->execute(
                    "INSERT INTO uploaded_files (business_listing_id, file_name, file_original_name, 
                     file_path, file_type, file_extension, file_size_kb, mime_type, upload_date, access_level) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'paid_only')",
                    [
                        $listingId,
                        $filename,
                        $originalName,
                        $filepath,
                        $fileType,
                        $extension,
                        round($fileSize / 1024),
                        $files['type'][$i]
                    ]
                );
                
                $results['success'][] = $originalName;
                
            } catch (Exception $e) {
                $results['errors'][] = "Database error for $originalName: " . $e->getMessage();
                unlink($filepath); // Clean up file
            }
            
        } else {
            $results['errors'][] = "Failed to upload: $originalName";
        }
    }
    
    return $results;
}

/**
 * Map file extension to file type
 */
function getFileType($extension) {
    $typeMap = [
        'pdf' => 'business_plan',
        'ppt' => 'pitch_deck',
        'pptx' => 'pitch_deck',
        'doc' => 'business_plan',
        'docx' => 'business_plan',
        'xls' => 'financial_projection',
        'xlsx' => 'financial_projection'
    ];
    
    return $typeMap[$extension] ?? 'other';
}
?>