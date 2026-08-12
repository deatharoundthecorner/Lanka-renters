<?php
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/controllers/PublicVehicleController.php';

AuthHelper::startSession();

$isLoggedIn = AuthHelper::isLoggedIn();
$currentUser = $isLoggedIn ? AuthHelper::getCurrentUser() : null;

// Determine Dashboard URL based on user role
$dashboardUrl = '';
if ($isLoggedIn && $currentUser) {
    switch ($currentUser['role'] ?? '') {
        case 'admin':
            $dashboardUrl = 'admin/dashboard.php';
            break;
        case 'owner':
            $dashboardUrl = 'owner/dashboard.php';
            break;
        case 'driver':
            $dashboardUrl = 'driver/dashboard.php';
            break;
        case 'customer':
            $dashboardUrl = 'customer/dashboard/index.php';
            break;
    }
}

$vehicleController = new PublicVehicleController();
$featuredVehicles = $vehicleController->getFeaturedVehicles('./');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lanka Renters - Long-Term Vehicle Rentals in Sri Lanka</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/guest.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="guest-navbar">
        <a href="index.php" class="navbar-logo">
            <span class="logo-icon">L</span>
            <span>Lanka<span style="color: #2563EB;">Renters</span></span>
        </a>
        <div class="navbar-menu">
            <a href="index.php" class="active">Home</a>
            <a href="vehicles.php">Vehicles</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#why-us">About / Trust</a>
        </div>
        <div class="navbar-actions">
            <?php if ($isLoggedIn && $currentUser): ?>
                <span style="font-size: 14px; font-weight: 600; color: #1E293B; margin-right: 10px;">
                    Hi, <?php echo htmlspecialchars($currentUser['name']); ?>
                </span>
                <a href="<?php echo $dashboardUrl; ?>" class="btn-register" style="padding: 10px 18px; font-size: 14px;">Dashboard</a>
                <a href="logout.php" class="btn-login" style="padding: 8px 12px; font-size: 14px;">Sign Out</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section-wrapper">
    <header class="hero-section">
        <div class="hero-content">
            <div class="badge-trust">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                </svg>
                <span>Sri Lanka's trusted rental network</span>
            </div>
            <h1>Long-Term Vehicle Rentals Made Simple</h1>
            <p>Find verified vehicles, trusted owners, and professional drivers through Lanka Renters. Flexible monthly rentals from 28 days up to 6 months.</p>
            <div class="hero-ctas">
                <a href="vehicles.php" class="btn-hero-primary">Explore Vehicles</a>
                <?php if (!$isLoggedIn): ?>
                    <a href="register.php" class="btn-hero-secondary">Create Account</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Widget Widget -->
        <div class="search-widget">
            <h2>Find Your Rental Vehicle</h2>
            <form action="vehicles.php" method="GET">
                <div class="search-form-group">
                    <label for="location">District / Location</label>
                    <select name="location" id="location" class="search-input">
                        <option value="">Any Location</option>
                        <option value="Colombo">Colombo</option>
                        <option value="Gampaha">Gampaha</option>
                        <option value="Kandy">Kandy</option>
                    </select>
                </div>

                <div class="search-row">
                    <div class="search-form-group">
                        <label for="vehicle_type">Vehicle Type</label>
                        <select name="vehicle_type" id="vehicle_type" class="search-input">
                            <option value="">Any Type</option>
                            <option value="car">Car / Sedan</option>
                            <option value="van">Van / MPV</option>
                            <option value="suv">SUV / Crossover</option>
                        </select>
                    </div>

                    <div class="search-form-group">
                        <label for="service_type">Service Type</label>
                        <select name="service_type" id="service_type" class="search-input">
                            <option value="">Any Service</option>
                            <option value="self_drive">Self-drive only</option>
                            <option value="with_driver">With Driver</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-search">Search Vehicles</button>
                <p class="search-helper-text">Rental period must be between 28 days and 6 months.</p>
            </form>
        </div>
    </header>
    </div>

    <!-- One Platform Journeys Section -->
    <section class="section-wrapper" style="background-color: #FFFFFF; border-top: 1px solid #E5E7EB; border-bottom: 1px solid #E5E7EB;">
        <div class="section-header">
            <span>How It Works</span>
            <h2>One platform, three simple journeys</h2>
            <p>Whether you're renting, listing, or driving, Lanka Renters guides you every step of the way.</p>
        </div>
        <div class="journeys-grid">
            <!-- Customer -->
            <div class="journey-card">
                <div class="journey-card-header">
                    <span class="journey-icon">👤</span>
                    <h3>Customer</h3>
                </div>
                <div class="journey-steps">
                    <div class="journey-step">
                        <span class="step-num">1</span>
                        <div class="step-content">
                            <h4>Search vehicles</h4>
                            <p>Filter by district, type, price and driver service.</p>
                        </div>
                    </div>
                    <div class="journey-step">
                        <span class="step-num">2</span>
                        <div class="step-content">
                            <h4>Request booking</h4>
                            <p>Send a request — the owner responds within 12 hours.</p>
                        </div>
                    </div>
                    <div class="journey-step">
                        <span class="step-num">3</span>
                        <div class="step-content">
                            <h4>Complete rental safely</h4>
                            <p>Inspection tracking and support throughout.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Owner -->
            <div class="journey-card">
                <div class="journey-card-header">
                    <span class="journey-icon">🚗</span>
                    <h3>Vehicle Owner</h3>
                </div>
                <div class="journey-steps">
                    <div class="journey-step">
                        <span class="step-num">1</span>
                        <div class="step-content">
                            <h4>Add vehicles</h4>
                            <p>Upload documents and images for verification.</p>
                        </div>
                    </div>
                    <div class="journey-step">
                        <span class="step-num">2</span>
                        <div class="step-content">
                            <h4>Manage bookings</h4>
                            <p>Approve or reject requests within the deadline.</p>
                        </div>
                    </div>
                    <div class="journey-step">
                        <span class="step-num">3</span>
                        <div class="step-content">
                            <h4>Earn income</h4>
                            <p>Track earnings, deductions and settlements clearly.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver -->
            <div class="journey-card">
                <div class="journey-card-header">
                    <span class="journey-icon">💼</span>
                    <h3>Driver</h3>
                </div>
                <div class="journey-steps">
                    <div class="journey-step">
                        <span class="step-num">1</span>
                        <div class="step-content">
                            <h4>Get verified</h4>
                            <p>Submit NIC, license and police report.</p>
                        </div>
                    </div>
                    <div class="journey-step">
                        <span class="step-num">2</span>
                        <div class="step-content">
                            <h4>Receive assignments</h4>
                            <p>Accept jobs from owners you are linked to.</p>
                        </div>
                    </div>
                    <div class="journey-step">
                        <span class="step-num">3</span>
                        <div class="step-content">
                            <h4>Complete trips</h4>
                            <p>Update pickup status from start to finish.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust & Safety Section -->
    <section id="why-us" class="section-wrapper">
        <div class="section-header">
            <span>Why Lanka Renters</span>
            <h2>Built on trust and safety</h2>
            <p>Every listing, user, and transaction is verified to ensure a secure rental experience.</p>
        </div>
        <div class="trust-grid">
            <div class="trust-card">
                <span class="journey-icon">🛡️</span>
                <h3>Verified Vehicles</h3>
                <p>Registration and insurance checks</p>
            </div>
            <div class="trust-card">
                <span class="journey-icon">📋</span>
                <h3>Verified Drivers</h3>
                <p>License and police records checked</p>
            </div>
            <div class="trust-card">
                <span class="journey-icon">🔒</span>
                <h3>Secure Booking</h3>
                <p>Centralized deposit holding</p>
            </div>
            <div class="trust-card">
                <span class="journey-icon">🔍</span>
                <h3>Inspection Tracking</h3>
                <p>Condition verified before and after</p>
            </div>
            <div class="trust-card">
                <span class="journey-icon">💵</span>
                <h3>Transparent Earnings</h3>
                <p>Detailed reports and statement logs</p>
            </div>
        </div>
    </section>

    <!-- Featured Vehicles Section -->
    <section class="section-wrapper" style="background-color: #FFFFFF; border-top: 1px solid #E5E7EB;">
        <div class="listing-header">
            <h2>Popular vehicles right now</h2>
            <a href="vehicles.php" class="btn-login" style="font-weight: 700;">View all &rarr;</a>
        </div>

        <?php if (empty($featuredVehicles)): ?>
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3>No Vehicles Available</h3>
                <p>All vehicles are currently rented or undergoing routine maintenance. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="vehicles-grid">
                <?php foreach ($featuredVehicles as $v): ?>
                    <div class="guest-vehicle-card">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($v['image_url']); ?>" alt="<?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?>">
                            <div class="card-badges">
                                <span class="badge-v badge-v-verified">✓ Verified</span>
                                <span class="badge-v-status badge-status-available">Available</span>
                            </div>
                            <span class="badge-service-type"><?php echo htmlspecialchars($v['service_type']); ?></span>
                        </div>
                        <div class="card-details-body">
                            <h3><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?></h3>
                            <p class="model-year"><?php echo htmlspecialchars($v['year']); ?></p>
                            
                            <div class="card-specs-row">
                                <div class="spec-item">
                                    <span>📍 Location</span>
                                    <strong style="margin-top:2px; font-weight:700; color:#1E293B;"><?php echo htmlspecialchars($v['location']); ?></strong>
                                </div>
                                <div class="spec-item">
                                    <span>⚙️ Gearbox</span>
                                    <strong style="margin-top:2px; font-weight:700; color:#1E293B; text-transform:capitalize;"><?php echo htmlspecialchars($v['transmission']); ?></strong>
                                </div>
                                <div class="spec-item">
                                    <span>👥 Seating</span>
                                    <strong style="margin-top:2px; font-weight:700; color:#1E293B;"><?php echo htmlspecialchars($v['seating_capacity']); ?> Seats</strong>
                                </div>
                            </div>

                            <div class="card-price-row">
                                <div class="price-container">
                                    <span class="price-num">LKR <?php echo number_format($v['monthly_price']); ?></span>
                                    <span class="price-period">per month</span>
                                </div>
                                <a href="vehicle-details.php?id=<?php echo $v['id']; ?>" class="btn-card-details">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- How It Works Step Area -->
    <section id="how-it-works" class="section-wrapper" style="background-color: #F8FAFC; border-top: 1px solid #E5E7EB;">
        <div class="section-header">
            <span>Simple steps</span>
            <h2>Simple for everyone involved</h2>
            <p>Renting a vehicle for long-term has never been this seamless.</p>
        </div>
        <div style="margin: 0 auto; display: flex; flex-direction: column; gap: 30px;">
            <div class="journey-step" style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #E5E7EB; align-items: center;">
                <span class="step-num" style="font-size: 32px; min-width: 60px; color: #2563EB;">01</span>
                <div class="step-content">
                    <h3 style="font-size: 18px; font-weight: 700; color: #0B3A82;">Browse Vehicles</h3>
                    <p style="font-size: 14px; color: #64748B; margin-top: 4px;">Explore our catalog of approved, verified passenger and commercial vehicles available in your target districts.</p>
                </div>
            </div>
            <div class="journey-step" style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #E5E7EB; align-items: center;">
                <span class="step-num" style="font-size: 32px; min-width: 60px; color: #2563EB;">02</span>
                <div class="step-content">
                    <h3 style="font-size: 18px; font-weight: 700; color: #0B3A82;">Choose a Vehicle</h3>
                    <p style="font-size: 14px; color: #64748B; margin-top: 4px;">Compare specifications, pricing, and driver options. View full vehicle history and verification documents.</p>
                </div>
            </div>
            <div class="journey-step" style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #E5E7EB; align-items: center;">
                <span class="step-num" style="font-size: 32px; min-width: 60px; color: #2563EB;">03</span>
                <div class="step-content">
                    <h3 style="font-size: 18px; font-weight: 700; color: #0B3A82;">Register / Login</h3>
                    <p style="font-size: 14px; color: #64748B; margin-top: 4px;">Create a secure Customer account to finalize your booking details and make your deposit payments online.</p>
                </div>
            </div>
            <div class="journey-step" style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #E5E7EB; align-items: center;">
                <span class="step-num" style="font-size: 32px; min-width: 60px; color: #2563EB;">04</span>
                <div class="step-content">
                    <h3 style="font-size: 18px; font-weight: 700; color: #0B3A82;">Continue with the rental process</h3>
                    <p style="font-size: 14px; color: #64748B; margin-top: 4px;">Participate in pre-rental inspection tracking, receive key handovers, and track your ongoing rental plan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Banner -->
    <section class="section-wrapper" style="padding: 20px 40px 60px;">
        <div class="cta-banner">
            <h2>Ready to find your next vehicle?</h2>
            <p>Create a free customer account or browse our full collection of active long-term vehicles.</p>
            <div style="display: flex; gap: 15px; z-index: 10;">
                <a href="vehicles.php" class="btn-hero-primary" style="background-color: white; color: #2563EB; box-shadow: none;">Explore Vehicles</a>
                <?php if (!$isLoggedIn): ?>
                    <a href="register.php" class="btn-hero-secondary" style="background-color: transparent; border-color: white; color: white;">Create Account</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="guest-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <a href="index.php" class="footer-logo">
                    <span class="logo-icon" style="background-color: #2563EB;">L</span>
                    <span>LankaRenters</span>
                </a>
                <p>Trusted long-term vehicle rentals connecting customers, owners and professional drivers across Sri Lanka.</p>
            </div>
            <div class="footer-column">
                <h3>Lanka Renters</h3>
                <ul>
                    <li><a href="index.php">About</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#why-us">Support</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Help</h3>
                <ul>
                    <li><a href="#how-it-works">Help Center</a></li>
                    <li><a href="#why-us">Contact</a></li>
                    <li><a href="#how-it-works">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Legal</h3>
                <ul>
                    <li><a href="#why-us">Privacy Policy</a></li>
                    <li><a href="#why-us">User Guidelines</a></li>
                    <li><a href="#why-us">Rental Rules</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Account</h3>
                <ul>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="<?php echo $dashboardUrl; ?>">My Dashboard</a></li>
                        <li><a href="logout.php">Sign Out</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Lanka Renters. All rights reserved.</span>
            <span>Made in Sri Lanka</span>
        </div>
    </footer>

</body>
</html>
