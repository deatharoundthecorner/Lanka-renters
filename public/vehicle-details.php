<?php
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/controllers/PublicVehicleController.php';

AuthHelper::startSession();

$isLoggedIn = AuthHelper::isLoggedIn();
$currentUser = $isLoggedIn ? AuthHelper::getCurrentUser() : null;

// Validate GET vehicle ID
$vehicleId = $_GET['id'] ?? '';
if (empty($vehicleId) || !is_numeric($vehicleId)) {
    header("Location: vehicles.php");
    exit();
}

$vehicleController = new PublicVehicleController();
$v = $vehicleController->getVehicleDetails((int)$vehicleId, './');

// Redirect if vehicle does not exist or is not approved/available
if (!$v) {
    header("Location: vehicles.php");
    exit();
}

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

// Determine CTA target based on auth state
$ctaUrl = '';
if (!$isLoggedIn) {
    $redirectUrl = 'vehicle-details.php?id=' . $v['id'];
    $message = 'Please create an account or log in to continue with a rental request.';
    $ctaUrl = 'login.php?redirect=' . urlencode($redirectUrl) . '&message=' . urlencode($message);
} elseif ($currentUser['role'] === 'customer') {
    $ctaUrl = 'customer/bookings/create.php?vehicle_id=' . $v['id'];
} else {
    // Other roles (owner, driver, admin) go to their dashboard
    $ctaUrl = $dashboardUrl;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?> Details - Lanka Renters</title>
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
            <a href="index.php">Home</a>
            <a href="vehicles.php" class="active">Vehicles</a>
            <a href="index.php#how-it-works">How It Works</a>
            <a href="index.php#why-us">About / Trust</a>
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

    <!-- Main Details Layout -->
    <div class="details-layout">
        <!-- Main Details Column -->
        <main class="details-main">
            <!-- Vehicle Image Gallery/Frame -->
            <div class="details-gallery">
                <img src="<?php echo htmlspecialchars($v['image_url']); ?>" alt="<?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?>">
            </div>

            <!-- Vehicle Specs & Info -->
            <div class="details-card">
                <div class="details-header">
                    <h1><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?></h1>
                    <p class="subtitle">Manufactured Year: <?php echo htmlspecialchars($v['year']); ?> &bull; Verified approved Listing</p>
                </div>

                <h3 style="font-size: 18px; font-weight: 700; color: #0B3A82; margin-top: 20px;">Specifications</h3>
                <div class="details-specs-grid">
                    <div class="detail-spec-item">
                        <span class="label">Vehicle Type</span>
                        <span class="value"><?php echo htmlspecialchars($v['vehicle_type']); ?></span>
                    </div>
                    <div class="detail-spec-item">
                        <span class="label">Transmission</span>
                        <span class="value"><?php echo htmlspecialchars($v['transmission']); ?></span>
                    </div>
                    <div class="detail-spec-item">
                        <span class="label">Fuel Type</span>
                        <span class="value"><?php echo htmlspecialchars($v['fuel_type']); ?></span>
                    </div>
                    <div class="detail-spec-item">
                        <span class="label">Seating Capacity</span>
                        <span class="value"><?php echo htmlspecialchars($v['seating_capacity']); ?> Seats</span>
                    </div>
                    <div class="detail-spec-item">
                        <span class="label">Service Type</span>
                        <span class="value"><?php echo htmlspecialchars($v['service_type']); ?></span>
                    </div>
                    <div class="detail-spec-item">
                        <span class="label">Rental Period</span>
                        <span class="value">28 Days - 6 Months</span>
                    </div>
                </div>

                <!-- Business Warning Message -->
                <div class="details-warning-banner">
                    💡 <strong>Driver Assignment Notice:</strong><br>
                    Customer cannot directly choose a driver. If driver service is selected, the vehicle owner assigns a verified available driver.
                </div>
            </div>
        </main>

        <!-- Sidebar Details / Booking card -->
        <aside class="booking-sidebar-card">
            <h2>Rental Pricing</h2>
            
            <div class="price-detail-row">
                <span class="label">Daily Self-drive</span>
                <span class="value">LKR <?php echo number_format($v['price_per_day']); ?></span>
            </div>
            <div class="price-detail-row">
                <span class="label">Monthly Self-drive</span>
                <span class="value" style="color: #0B3A82;">LKR <?php echo number_format($v['monthly_price']); ?></span>
            </div>

            <?php if (!empty($v['price_with_driver_per_day'])): ?>
                <div class="price-detail-row" style="margin-top: 15px;">
                    <span class="label">Daily with Driver</span>
                    <span class="value">LKR <?php echo number_format($v['price_with_driver_per_day']); ?></span>
                </div>
                <div class="price-detail-row">
                    <span class="label">Monthly with Driver</span>
                    <span class="value" style="color: #0B3A82;">LKR <?php echo number_format($v['monthly_price_with_driver']); ?></span>
                </div>
            <?php endif; ?>

            <div class="price-detail-row total">
                <span class="label">Security Deposit</span>
                <span class="value">LKR <?php echo number_format($v['monthly_price'] * 0.25); ?></span>
            </div>
            <p style="font-size: 11px; color: #64748B; margin-top: 4px; text-align: right;">(Refundable 25% of monthly rental value)</p>

            <?php if ($isLoggedIn && $currentUser['role'] !== 'customer'): ?>
                <div style="background-color: #FEE2E2; border: 1px solid #FCA5A5; color: #B91C1C; padding: 12px; border-radius: 8px; font-size: 13px; font-weight:600; text-align:center; margin-top:20px;">
                    You are signed in as an <?php echo ucfirst($currentUser['role']); ?>. Only Customer accounts can request vehicle bookings.
                </div>
                <a href="<?php echo $ctaUrl; ?>" class="btn-booking-request" style="background-color: #64748B;">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?php echo $ctaUrl; ?>" class="btn-booking-request">
                    <?php echo $isLoggedIn ? 'Request This Vehicle' : 'Sign In to Request Vehicle'; ?>
                </a>
            <?php endif; ?>

            <p class="booking-card-footer">Subject to vehicle availability and owner approval.</p>
        </aside>
    </div>

    <!-- Footer -->
    <footer class="guest-footer" style="margin-top: 80px;">
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
                    <li><a href="index.php#how-it-works">How It Works</a></li>
                    <li><a href="index.php#why-us">Support</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Help</h3>
                <ul>
                    <li><a href="index.php#how-it-works">Help Center</a></li>
                    <li><a href="index.php#why-us">Contact</a></li>
                    <li><a href="index.php#how-it-works">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Legal</h3>
                <ul>
                    <li><a href="index.php#why-us">Privacy Policy</a></li>
                    <li><a href="index.php#why-us">User Guidelines</a></li>
                    <li><a href="index.php#why-us">Rental Rules</a></li>
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
