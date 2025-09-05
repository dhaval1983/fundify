<?php
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

// Get current user
$currentUser = $user->getCurrentUser();
$userRole = $currentUser ? 'registered' : 'public';

// Get listing ID
// Get listing by slug or ID
$slug = $_GET['slug'] ?? '';
$listingId = (int)($_GET['id'] ?? 0);

$listing = null;

if (!empty($slug)) {
    // Try to get by slug first
    $listing = $businessListing->getListingBySlug($slug, $userRole);
} elseif ($listingId > 0) {
    // Fallback to ID for backward compatibility
    $listing = $businessListing->getListing($listingId, $userRole);
} else {
    header('Location: /browse.php');
    exit;
}



// Check if user can contact founder
$canContact = $currentUser && $currentUser['email_verified'];
$isOwnListing = $currentUser && $currentUser['id'] == $listing['user_id'];

// If accessed by ID, redirect to slug URL for SEO
if ($listingId > 0 && !empty($slug) === false && !empty($listing['slug'])) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://fundify.isowebtech.com/listing/" . $listing['slug']);
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta name="description" content="<?php echo htmlspecialchars(substr($listing['short_pitch'], 0, 160)); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($listing['industry']); ?>, startup funding, investment opportunity, <?php echo htmlspecialchars($listing['location_city']); ?>">

<!-- Open Graph tags for social media -->
<meta property="og:title" content="<?php echo htmlspecialchars($listing['title']); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($listing['short_pitch']); ?>">
<meta property="og:url" content="https://fundify.isowebtech.com/listing/<?php echo htmlspecialchars($listing['slug']); ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Fundify">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($listing['title']); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($listing['short_pitch']); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($listing['title']); ?> - Fundify</title>
    <link rel="canonical" href="https://fundify.isowebtech.com/listing/<?php echo htmlspecialchars($listing['slug']); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9ff;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8em;
            font-weight: bold;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .listing-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            position: relative;
        }
        
        .featured-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .company-name {
            font-size: 2.2em;
            color: #333;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .listing-title {
            font-size: 1.4em;
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .short-pitch {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 25px;
            font-style: italic;
        }
        
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .tag {
            background: #f0f4ff;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .stage-tag {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .location-tag {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .key-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: #f8f9ff;
            border: 2px solid #e0e8ff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .metric-label {
            color: #666;
            font-size: 14px;
        }
        
        .funding-metric .metric-value {
            color: #28a745;
        }
        
        .revenue-metric .metric-value {
            color: #17a2b8;
        }
        
        .main-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .description-text {
            color: #555;
            line-height: 1.8;
            font-size: 16px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #666;
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .sidebar-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .contact-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #f8f9ff;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .founder-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .founder-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        
        .founder-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .founder-title {
            color: #666;
        }
        
        .stats-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .stats-label {
            color: #666;
        }
        
        .stats-value {
            font-weight: bold;
            color: #333;
        }
        
        .privacy-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .team-members {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .team-member {
            text-align: center;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 12px;
        }
        
        .team-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto 10px;
        }
        
        .team-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .team-role {
            color: #666;
            font-size: 14px;
        }
        
        .files-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9ff;
            border-radius: 8px;
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-icon {
            width: 32px;
            height: 32px;
            background: #667eea;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .key-metrics {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .company-name {
                font-size: 1.8em;
            }
            
            .team-members {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">Fundify</div>
            <nav class="nav-links">
                <a href="/index.php">Home</a>
                <a href="/browse.php">Browse</a>
                <?php if ($currentUser): ?>
                    <a href="/dashboard.php">Dashboard</a>
                    <a href="/messages.php">Messages</a>
                    <a href="/profile.php">Profile</a>
                    <a href="/login.php?logout=1">Logout</a>
                <?php else: ?>
                    <a href="/register.php">Join Now</a>
                    <a href="/login.php">Sign In</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <a href="/browse.php" class="back-link">← Back to Browse</a>
        
        <div class="listing-header">
            <?php if ($listing['is_featured']): ?>
                <div class="featured-badge">Featured</div>
            <?php endif; ?>
            
            <div class="company-name"><?php echo htmlspecialchars($listing['company_name']); ?></div>
            <div class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></div>
            <div class="short-pitch"><?php echo htmlspecialchars($listing['short_pitch']); ?></div>
            
            <div class="tags">
                <span class="tag"><?php echo htmlspecialchars($listing['industry']); ?></span>
                <span class="tag stage-tag"><?php echo ucwords(str_replace('_', ' ', $listing['business_stage'])); ?></span>
                <span class="tag location-tag"><?php echo htmlspecialchars($listing['location_city'] . ', ' . $listing['location_state']); ?></span>
            </div>
            
            <div class="key-metrics">
                <div class="metric-card funding-metric">
                    <div class="metric-value">₹<?php echo number_format($listing['funding_amount_needed'] / 10000000, 1); ?> Cr</div>
                    <div class="metric-label">Funding Needed</div>
                </div>
                
                <?php if ($listing['current_annual_revenue'] > 0): ?>
                <div class="metric-card revenue-metric">
                    <div class="metric-value">₹<?php echo number_format($listing['current_annual_revenue'] / 10000000, 1); ?> Cr</div>
                    <div class="metric-label">Annual Revenue</div>
                </div>
                <?php endif; ?>
                
                <div class="metric-card">
                    <div class="metric-value"><?php echo $listing['equity_offered_min']; ?>-<?php echo $listing['equity_offered_max']; ?>%</div>
                    <div class="metric-label">Equity Offered</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-value"><?php echo number_format($listing['view_count']); ?></div>
                    <div class="metric-label">Views</div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="content-area">
                
                <?php if ($userRole === 'public'): ?>
                <div class="privacy-notice">
                    <strong>Limited Access</strong> - 
                    <a href="/register.php">Register for free</a> to see complete business details, financial information, and contact the founder
                </div>
                <?php endif; ?>

                <div class="main-section">
                    <h2 class="section-title">Business Overview</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($listing['detailed_description'])); ?>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="section-title">Business Details</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Industry</div>
                            <div class="info-value"><?php echo htmlspecialchars($listing['industry']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Business Stage</div>
                            <div class="info-value"><?php echo ucwords(str_replace('_', ' ', $listing['business_stage'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Location</div>
                            <div class="info-value"><?php echo htmlspecialchars($listing['location_city'] . ', ' . $listing['location_state']); ?></div>
                        </div>
                        
                        <?php if ($listing['current_monthly_revenue'] > 0): ?>
                        <div class="info-item">
                            <div class="info-label">Monthly Revenue</div>
                            <div class="info-value">₹<?php echo number_format($listing['current_monthly_revenue'] / 100000, 1); ?> Lakh</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['website_url']) && $userRole !== 'public'): ?>
                        <div class="info-item">
                            <div class="info-label">Website</div>
                            <div class="info-value"><a href="<?php echo htmlspecialchars($listing['website_url']); ?>" target="_blank"><?php echo htmlspecialchars($listing['website_url']); ?></a></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($listing['target_market'])): ?>
                <div class="main-section">
                    <h2 class="section-title">Target Market</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($listing['target_market'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($listing['revenue_model'])): ?>
                <div class="main-section">
                    <h2 class="section-title">Revenue Model</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($listing['revenue_model'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($listing['fund_usage_plan'])): ?>
                <div class="main-section">
                    <h2 class="section-title">Fund Usage Plan</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($listing['fund_usage_plan'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($listing['team_members'])): ?>
                <div class="main-section">
                    <h2 class="section-title">Team</h2>
                    <div class="team-members">
                        <?php foreach ($listing['team_members'] as $member): ?>
                        <div class="team-member">
                            <div class="team-avatar">
                                <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                            </div>
                            <div class="team-name"><?php echo htmlspecialchars($member['name']); ?></div>
                            <div class="team-role"><?php echo htmlspecialchars($member['role']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($listing['files']) && $userRole !== 'public'): ?>
                <div class="main-section">
                    <h2 class="section-title">Documents</h2>
                    <div class="files-list">
                        <?php foreach ($listing['files'] as $file): ?>
                        <div class="file-item">
                            <div class="file-info">
                                <div class="file-icon">
                                    <?php echo strtoupper($file['file_extension'] ?? 'DOC'); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($file['file_original_name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo ucwords(str_replace('_', ' ', $file['file_type'])); ?></div>
                                </div>
                            </div>
                            <a href="download.php?file=<?php echo $file['id']; ?>" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px;">Download</a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="sidebar">
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Founder</h3>
                    <div class="founder-info">
                        <div class="founder-avatar">
                            <?php echo strtoupper(substr($listing['founder_name'], 0, 1)); ?>
                        </div>
                        <div class="founder-name"><?php echo htmlspecialchars($listing['founder_name']); ?></div>
                        <div class="founder-title">Founder & CEO</div>
                    </div>
                    
                    <div class="contact-actions">
                        <?php if ($isOwnListing): ?>
                            <a href="edit-listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-secondary">Edit Listing</a>
                            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                        <?php elseif ($canContact): ?>
                            <a href="message.php?listing=<?php echo $listing['id']; ?>" class="btn btn-primary">Send Message</a>
                            <button onclick="showInterest()" class="btn btn-success">Show Interest</button>
                        <?php elseif ($currentUser): ?>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 15px;">
                                Please verify your email to contact founders
                            </div>
                            <a href="verify.php" class="btn btn-secondary">Verify Email</a>
                        <?php else: ?>
                            <a href="/register.php" class="btn btn-primary">Register to Contact</a>
                            <a href="login.php" class="btn btn-secondary">Sign In</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3 class="sidebar-title">Listing Stats</h3>
                    <div class="stats-item">
                        <span class="stats-label">Views</span>
                        <span class="stats-value"><?php echo number_format($listing['view_count']); ?></span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Investor Interest</span>
                        <span class="stats-value"><?php echo number_format($listing['investor_interest_count']); ?></span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Listed</span>
                        <span class="stats-value"><?php echo date('M j, Y', strtotime($listing['created_at'])); ?></span>
                    </div>
                </div>

                <?php if ($userRole !== 'public'): ?>
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Quick Actions</h3>
                    <div class="contact-actions">
                        <button onclick="saveToWatchlist()" class="btn btn-secondary">Save to Watchlist</button>
                        <button onclick="shareListing()" class="btn btn-secondary">Share Listing</button>
                        <a href="browse.php?industry=<?php echo urlencode($listing['industry']); ?>" class="btn btn-secondary">Similar Opportunities</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showInterest() {
            if (confirm('Show interest in this opportunity? The founder will be notified.')) {
                // AJAX call to record interest
                fetch('api/show-interest.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        listing_id: <?php echo $listing['id']; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Interest recorded! The founder has been notified.');
                    } else {
                        alert('Something went wrong. Please try again.');
                    }
                });
            }
        }

        function saveToWatchlist() {
            alert('Watchlist feature coming soon!');
        }

        function shareListing() {
            if (navigator.share) {
                navigator.share({
                    title: <?php echo json_encode($listing['title']); ?>,
                    text: <?php echo json_encode($listing['short_pitch']); ?>,
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        }
    </script>
</body>
</html>