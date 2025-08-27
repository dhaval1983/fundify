/ =====================================================
// 7. PUBLIC/INDEX.PHP - Main Entry Point
// =====================================================
?>

<?php
// public/index.php
session_start();

// Load configuration
$config = require_once '../config/app.php';

// Set timezone
date_default_timezone_set($config['timezone']);

// Autoload classes (simple implementation)
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize core classes
$user = new User();
$businessListing = new BusinessListing();

// Get current user
$currentUser = $user->getCurrentUser();
$userRole = $currentUser ? 'registered' : 'public';
if ($currentUser && $currentUser['role'] === 'investor') {
    // Check if has active subscription (implement subscription check)
    $userRole = 'paid'; // Simplified for MVP
}

// Get business listings for homepage
$filters = [];
if (isset($_GET['industry'])) $filters['industry'] = $_GET['industry'];
if (isset($_GET['stage'])) $filters['business_stage'] = $_GET['stage'];
if (isset($_GET['min_funding'])) $filters['min_funding'] = $_GET['min_funding'];
if (isset($_GET['max_funding'])) $filters['max_funding'] = $_GET['max_funding'];

$page = $_GET['page'] ?? 1;
$listings = $businessListing->getListings($userRole, $filters, $page, 20);

// Include header
include '../includes/header.php';
?>

<main class="container">
    <section class="hero">
        <h1>Connect with Active Investors</h1>
        <p>India's Premier Investor-Entrepreneur Marketplace</p>
        
        <?php if (!$currentUser): ?>
            <div class="cta-buttons">
                <a href="register.php?role=entrepreneur" class="btn btn-primary">List Your Business</a>
                <a href="register.php?role=investor" class="btn btn-secondary">Find Investments</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="filters">
        <form method="GET" class="filter-form">
            <select name="industry">
                <option value="">All Industries</option>
                <option value="tech">Technology</option>
                <option value="healthcare">Healthcare</option>
                <option value="fintech">Fintech</option>
                <!-- Add more options -->
            </select>
            
            <select name="stage">
                <option value="">All Stages</option>
                <option value="idea">Idea</option>
                <option value="mvp">MVP</option>
                <option value="early_revenue">Early Revenue</option>
                <option value="growth">Growth</option>
            </select>
            
            <button type="submit" class="btn btn-filter">Apply Filters</button>
        </form>
    </section>

    <section class="business-listings">
        <h2>Investment Opportunities</h2>
        
        <?php if (empty($listings)): ?>
            <p>No business listings found matching your criteria.</p>
        <?php else: ?>
            <div class="listings-grid">
                <?php foreach ($listings as $listing): ?>
                    <div class="listing-card">
                        <h3><?= htmlspecialchars($listing['title']) ?></h3>
                        <p class="company-name"><?= htmlspecialchars($listing['company_name']) ?></p>
                        <p class="founder">Founder: <?= htmlspecialchars($listing['founder_name']) ?></p>
                        <p class="industry"><?= htmlspecialchars($listing['industry']) ?></p>
                        <p class="funding">Seeking: <?= Utils::formatCurrency($listing['funding_amount_needed']) ?></p>
                        
                        <?php if ($listing['current_monthly_revenue'] > 0): ?>
                            <p class="revenue">Revenue: <?= Utils::formatCurrency($listing['current_monthly_revenue']) ?>/month</p>
                        <?php endif; ?>
                        
                        <p class="equity">Equity: <?= $listing['equity_offered_min'] ?>-<?= $listing['equity_offered_max'] ?>%</p>
                        <p class="location"><?= htmlspecialchars($listing['location_city']) ?>, <?= htmlspecialchars($listing['location_state']) ?></p>
                        
                        <div class="listing-actions">
                            <a href="listing.php?id=<?= $listing['id'] ?>" class="btn btn-outline">View Details</a>
                            
                            <?php if (!$currentUser): ?>
                                <a href="register.php" class="btn btn-primary">Register to Connect</a>
                            <?php elseif ($userRole === 'registered'): ?>
                                <a href="upgrade.php" class="btn btn-primary">Upgrade to Contact</a>
                            <?php else: ?>
                                <a href="message.php?listing=<?= $listing['id'] ?>" class="btn btn-primary">Send Message</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>