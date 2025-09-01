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

// Handle filters
$filters = [];
if (!empty($_GET['industry'])) $filters['industry'] = $_GET['industry'];
if (!empty($_GET['stage'])) $filters['business_stage'] = $_GET['stage'];
if (!empty($_GET['location'])) $filters['location'] = $_GET['location'];
if (!empty($_GET['min_funding'])) $filters['min_funding'] = (float)$_GET['min_funding'] * 1000000; // Convert to actual amount
if (!empty($_GET['max_funding'])) $filters['max_funding'] = (float)$_GET['max_funding'] * 1000000;

// Get listings
$page = (int)($_GET['page'] ?? 1);
$listings = $businessListing->getListings($userRole, $filters, $page, 20);
$totalListings = $businessListing->countListings($filters);
$totalPages = ceil($totalListings / 20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Investment Opportunities - Fundify</title>
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
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-header p {
            font-size: 1.2em;
            color: #666;
        }
        
        .stats {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .filters {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filters h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-filter {
            padding: 12px 24px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
        }
        
        .btn-clear {
            padding: 12px 24px;
            background: #f8f9ff;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-clear:hover {
            background: #667eea;
            color: white;
        }
        
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .listing-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .listing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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
        
        .listing-header {
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .listing-title {
            color: #667eea;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .short-pitch {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .listing-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .detail-label {
            color: #666;
            font-weight: 600;
        }
        
        .detail-value {
            color: #333;
            font-weight: bold;
        }
        
        .funding-amount {
            color: #28a745;
            font-size: 1.1em;
        }
        
        .revenue-amount {
            color: #667eea;
        }
        
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .tag {
            background: #f0f4ff;
            color: #667eea;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
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
        
        .listing-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            flex: 1;
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
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-results h3 {
            color: #666;
            margin-bottom: 20px;
        }
        
        .privacy-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .listings-grid {
                grid-template-columns: 1fr;
            }
            
            .listing-actions {
                flex-direction: column;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .page-header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">🚀 Fundify</div>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="browse.php">Browse</a>
                <?php if ($currentUser): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="messages.php">Messages</a>
                    <a href="profile.php">Profile</a>
                    <a href="login.php?logout=1">Logout</a>
                <?php else: ?>
                    <a href="register.php">Join Now</a>
                    <a href="login.php">Sign In</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>Investment Opportunities</h1>
            <p>Discover innovative startups and growing businesses across India</p>
        </div>

        <div class="stats">
            <strong><?php echo number_format($totalListings); ?> Active Opportunities</strong>
            | Funding Range: ₹1.8 Cr - ₹100 Cr
            | Industries: Technology, Healthcare, Fintech & More
        </div>

        <?php if ($userRole === 'public'): ?>
        <div class="privacy-notice">
            <strong>👋 Viewing as Guest</strong> - 
            <a href="register.php">Register free</a> to see detailed business plans and contact founders directly
        </div>
        <?php endif; ?>

        <div class="filters">
            <h3>Filter Opportunities</h3>
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Industry</label>
                    <select name="industry">
                        <option value="">All Industries</option>
                        <option value="Technology" <?php echo ($_GET['industry'] ?? '') === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                        <option value="Healthcare" <?php echo ($_GET['industry'] ?? '') === 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                        <option value="Fintech" <?php echo ($_GET['industry'] ?? '') === 'Fintech' ? 'selected' : ''; ?>>Fintech</option>
                        <option value="EdTech" <?php echo ($_GET['industry'] ?? '') === 'EdTech' ? 'selected' : ''; ?>>EdTech</option>
                        <option value="E-commerce" <?php echo ($_GET['industry'] ?? '') === 'E-commerce' ? 'selected' : ''; ?>>E-commerce</option>
                        <option value="AgTech" <?php echo ($_GET['industry'] ?? '') === 'AgTech' ? 'selected' : ''; ?>>AgTech</option>
                        <option value="CleanTech" <?php echo ($_GET['industry'] ?? '') === 'CleanTech' ? 'selected' : ''; ?>>CleanTech</option>
                        <option value="Logistics" <?php echo ($_GET['industry'] ?? '') === 'Logistics' ? 'selected' : ''; ?>>Logistics</option>
                        <option value="Food & Beverage" <?php echo ($_GET['industry'] ?? '') === 'Food & Beverage' ? 'selected' : ''; ?>>Food & Beverage</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Business Stage</label>
                    <select name="stage">
                        <option value="">All Stages</option>
                        <option value="idea" <?php echo ($_GET['stage'] ?? '') === 'idea' ? 'selected' : ''; ?>>Idea Stage</option>
                        <option value="mvp" <?php echo ($_GET['stage'] ?? '') === 'mvp' ? 'selected' : ''; ?>>MVP</option>
                        <option value="early_revenue" <?php echo ($_GET['stage'] ?? '') === 'early_revenue' ? 'selected' : ''; ?>>Early Revenue</option>
                        <option value="growth" <?php echo ($_GET['stage'] ?? '') === 'growth' ? 'selected' : ''; ?>>Growth</option>
                        <option value="established" <?php echo ($_GET['stage'] ?? '') === 'established' ? 'selected' : ''; ?>>Established</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Funding Range (Crores)</label>
                    <select name="min_funding">
                        <option value="">Min Amount</option>
                        <option value="1" <?php echo ($_GET['min_funding'] ?? '') === '1' ? 'selected' : ''; ?>>₹1 Cr+</option>
                        <option value="5" <?php echo ($_GET['min_funding'] ?? '') === '5' ? 'selected' : ''; ?>>₹5 Cr+</option>
                        <option value="10" <?php echo ($_GET['min_funding'] ?? '') === '10' ? 'selected' : ''; ?>>₹10 Cr+</option>
                        <option value="25" <?php echo ($_GET['min_funding'] ?? '') === '25' ? 'selected' : ''; ?>>₹25 Cr+</option>
                        <option value="50" <?php echo ($_GET['min_funding'] ?? '') === '50' ? 'selected' : ''; ?>>₹50 Cr+</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Location</label>
                    <select name="location">
                        <option value="">All Locations</option>
                        <option value="Mumbai" <?php echo ($_GET['location'] ?? '') === 'Mumbai' ? 'selected' : ''; ?>>Mumbai</option>
                        <option value="Bengaluru" <?php echo ($_GET['location'] ?? '') === 'Bengaluru' ? 'selected' : ''; ?>>Bengaluru</option>
                        <option value="Delhi" <?php echo ($_GET['location'] ?? '') === 'Delhi' ? 'selected' : ''; ?>>Delhi</option>
                        <option value="Pune" <?php echo ($_GET['location'] ?? '') === 'Pune' ? 'selected' : ''; ?>>Pune</option>
                        <option value="Ahmedabad" <?php echo ($_GET['location'] ?? '') === 'Ahmedabad' ? 'selected' : ''; ?>>Ahmedabad</option>
                        <option value="Chennai" <?php echo ($_GET['location'] ?? '') === 'Chennai' ? 'selected' : ''; ?>>Chennai</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-filter">Apply Filters</button>
                <a href="browse.php" class="btn-clear">Clear All</a>
            </form>
        </div>

        <?php if (empty($listings)): ?>
            <div class="no-results">
                <h3>No opportunities found matching your criteria</h3>
                <p>Try adjusting your filters or <a href="browse.php">browse all opportunities</a></p>
            </div>
        <?php else: ?>
            <div class="listings-grid">
                <?php foreach ($listings as $listing): ?>
                    <div class="listing-card">
                        <?php if ($listing['is_featured']): ?>
                            <div class="featured-badge">Featured</div>
                        <?php endif; ?>
                        
                        <div class="listing-header">
                            <div class="company-name"><?php echo htmlspecialchars($listing['company_name']); ?></div>
                            <div class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                        </div>
                        
                        <div class="short-pitch">
                            <?php echo htmlspecialchars($listing['short_pitch']); ?>
                        </div>
                        
                        <div class="listing-details">
                            <div class="detail-row">
                                <span class="detail-label">Seeking:</span>
                                <span class="detail-value funding-amount">₹<?php echo number_format($listing['funding_amount_needed'] / 10000000, 1); ?> Cr</span>
                            </div>
                            
                            <?php if ($listing['current_annual_revenue'] > 0): ?>
                            <div class="detail-row">
                                <span class="detail-label">Annual Revenue:</span>
                                <span class="detail-value revenue-amount">₹<?php echo number_format($listing['current_annual_revenue'] / 10000000, 1); ?> Cr</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-row">
                                <span class="detail-label">Equity:</span>
                                <span class="detail-value"><?php echo $listing['equity_offered_min']; ?>-<?php echo $listing['equity_offered_max']; ?>%</span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Views:</span>
                                <span class="detail-value"><?php echo number_format($listing['view_count']); ?></span>
                            </div>
                        </div>
                        
                        <div class="tags">
                            <span class="tag"><?php echo htmlspecialchars($listing['industry']); ?></span>
                            <span class="tag stage-tag"><?php echo ucwords(str_replace('_', ' ', $listing['business_stage'])); ?></span>
                            <span class="tag location-tag"><?php echo htmlspecialchars($listing['location_city']); ?></span>
                        </div>
                        
                        <div class="listing-actions">
                            
                            <a href="listing/<?php echo $listing['slug']; ?>" class="btn btn-secondary">View Details</a>

                            <?php if (!$currentUser): ?>
                                <a href="register.php" class="btn btn-primary">Register to Connect</a>
                            <?php else: ?>
                                <a href="contact.php?listing=<?php echo $listing['id']; ?>" class="btn btn-primary">Contact Founder</a>

                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">← Previous</a>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
                if ($i === $page):
            ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
            <?php endif; endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>