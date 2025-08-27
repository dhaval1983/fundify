<?php
// Simple working index.php - Build step by step
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fundify - Investor-Entrepreneur Marketplace</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .hero-content {
            max-width: 800px;
            width: 100%;
        }
        
        h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .subtitle {
            font-size: 1.4em;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .launch-status {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            margin: 40px 0;
            backdrop-filter: blur(10px);
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .status-item {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .status-item h3 {
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 40px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
            box-shadow: 0 4px 15px rgba(255,107,107,0.4);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.8);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .features {
            background: white;
            padding: 80px 20px;
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .features h2 {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 50px;
            color: #333;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            background: #f8f9ff;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s ease;
            border: 2px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
        }
        
        .feature-icon {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.8;
        }
        
        .stats {
            background: #667eea;
            color: white;
            padding: 60px 20px;
        }
        
        .stats-container {
            max-width: 800px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }
        
        .stat h3 {
            font-size: 3em;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        
        @media (max-width: 768px) {
            h1 { font-size: 2.5em; }
            .subtitle { font-size: 1.2em; }
            .cta-buttons { flex-direction: column; align-items: center; }
            .btn { width: 250px; }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>🚀 Fundify</h1>
            <p class="subtitle">India's Premier Investor-Entrepreneur Marketplace</p>
            
            
                
                <h3 style="margin-top: 30px;">🚧 Coming Very Soon!</h3>
                <p>Full platform launches in 2-3 weeks with all features</p>
            </div>
            
            <div class="cta-buttons">
                <a href="#features" class="btn btn-primary">Learn More</a>
                <a href="mailto:founder@isowebtech.com" class="btn btn-secondary">Get Early Access</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <h2>Why Choose Fundify?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>For Entrepreneurs</h3>
                    <p>Showcase your business to thousands of verified investors. Upload pitch decks, financial projections, and get discovered by the right investors looking for opportunities in your sector.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>For Investors</h3>
                    <p>Discover promising startups across India. Access detailed business plans, financial data, and connect directly with verified entrepreneurs. Filter by industry, stage, and funding amount.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Safe & Legal</h3>
                    <p>We don't handle money or take equity. Pure marketplace connecting the right people. All deals happen directly between parties. SEBI compliant approach.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🆓</div>
                    <h3>Free Launch Period</h3>
                    <p>First 3 months completely FREE for all users. Build your profile, upload listings, connect with investors - no cost during our launch period.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🇮🇳</div>
                    <h3>India Focused</h3>
                    <p>Built specifically for the Indian startup ecosystem. Regional language support, INR pricing, and understanding of local business practices and regulations.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile First</h3>
                    <p>Fully responsive platform works perfectly on mobile devices. Upload pitches, respond to investors, and manage your profile from anywhere.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat">
                <h3>1000+</h3>
                <p>Target Active Investors</p>
            </div>
            <div class="stat">
                <h3>₹100Cr+</h3>
                <p>Funding Goal (Year 1)</p>
            </div>
            <div class="stat">
                <h3>500+</h3>
                <p>Expected Business Listings</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Fundify - Connecting Entrepreneurs with Investors</p>
        <p>Built with ❤️ for Indian Startups | Contact: founder@isowebtech.com</p>
        <p style="margin-top: 20px; font-size: 0.9em; opacity: 0.8;">
            Platform Status: Development Phase | Expected Launch: September 2025
        </p>
    </footer>

    <script>
        // Simple scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>