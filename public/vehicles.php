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

// Instantiate controller and search
$vehicleController = new PublicVehicleController();
$filters = [
    'location'         => $_GET['location'] ?? '',
    'vehicle_type'     => $_GET['vehicle_type'] ?? '',
    'service_type'     => $_GET['service_type'] ?? '',
    'transmission'     => $_GET['transmission'] ?? '',
    'fuel_type'        => $_GET['fuel_type'] ?? '',
    'seating_capacity' => $_GET['seating_capacity'] ?? '',
    'max_price'        => $_GET['max_price'] ?? ''
];

$vehiclesList = $vehicleController->searchVehicles($filters, './');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Vehicles - Lanka Renters</title>
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

    <!-- Main Listing Layout -->
    <div class="listing-layout">
        <!-- Sidebar Filters -->
        <aside class="filter-sidebar">
            <h2>Filters</h2>
            <form action="vehicles.php" method="GET">
                <!-- Location -->
                <div class="filter-group">
                    <label for="location">Location / District</label>
                    <select name="location" id="location" class="filter-select">
                        <option value="">All Locations</option>
                        <option value="Colombo" <?php echo ($filters['location'] === 'Colombo') ? 'selected' : ''; ?>>Colombo</option>
                        <option value="Gampaha" <?php echo ($filters['location'] === 'Gampaha') ? 'selected' : ''; ?>>Gampaha</option>
                        <option value="Kandy" <?php echo ($filters['location'] === 'Kandy') ? 'selected' : ''; ?>>Kandy</option>
                    </select>
                </div>

                <!-- Vehicle Type -->
                <div class="filter-group">
                    <label for="vehicle_type">Vehicle Type</label>
                    <select name="vehicle_type" id="vehicle_type" class="filter-select">
                        <option value="">All Types</option>
                        <option value="car" <?php echo ($filters['vehicle_type'] === 'car') ? 'selected' : ''; ?>>Car / Sedan</option>
                        <option value="van" <?php echo ($filters['vehicle_type'] === 'van') ? 'selected' : ''; ?>>Van / MPV</option>
                        <option value="suv" <?php echo ($filters['vehicle_type'] === 'suv') ? 'selected' : ''; ?>>SUV / Crossover</option>
                    </select>
                </div>

                <!-- Service Type -->
                <div class="filter-group">
                    <label for="service_type">Service Type</label>
                    <select name="service_type" id="service_type" class="filter-select">
                        <option value="">All Services</option>
                        <option value="self_drive" <?php echo ($filters['service_type'] === 'self_drive') ? 'selected' : ''; ?>>Self-drive only</option>
                        <option value="with_driver" <?php echo ($filters['service_type'] === 'with_driver') ? 'selected' : ''; ?>>With Driver</option>
                    </select>
                </div>

                <!-- Transmission -->
                <div class="filter-group">
                    <label for="transmission">Transmission</label>
                    <select name="transmission" id="transmission" class="filter-select">
                        <option value="">All Transmissions</option>
                        <option value="automatic" <?php echo ($filters['transmission'] === 'automatic') ? 'selected' : ''; ?>>Automatic</option>
                        <option value="manual" <?php echo ($filters['transmission'] === 'manual') ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>

                <!-- Fuel Type -->
                <div class="filter-group">
                    <label for="fuel_type">Fuel Type</label>
                    <select name="fuel_type" id="fuel_type" class="filter-select">
                        <option value="">All Fuels</option>
                        <option value="petrol" <?php echo ($filters['fuel_type'] === 'petrol') ? 'selected' : ''; ?>>Petrol</option>
                        <option value="diesel" <?php echo ($filters['fuel_type'] === 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                        <option value="hybrid" <?php echo ($filters['fuel_type'] === 'hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                        <option value="electric" <?php echo ($filters['fuel_type'] === 'electric') ? 'selected' : ''; ?>>Electric</option>
                    </select>
                </div>

                <!-- Seating Capacity -->
                <div class="filter-group">
                    <label for="seating_capacity">Min Seating Capacity</label>
                    <input type="number" name="seating_capacity" id="seating_capacity" class="filter-input" placeholder="e.g. 5" min="1" value="<?php echo htmlspecialchars($filters['seating_capacity']); ?>">
                </div>

                <!-- Max Price -->
                <div class="filter-group">
                    <label for="max_price">Max Monthly Price (LKR)</label>
                    <input type="number" name="max_price" id="max_price" class="filter-input" placeholder="e.g. 150000" min="0" value="<?php echo htmlspecialchars($filters['max_price']); ?>">
                </div>

                <button type="submit" class="btn-filter-apply">Apply Filters</button>
                <a href="vehicles.php" class="btn-filter-clear">Clear All Filters</a>
            </form>
        </aside>

        <!-- Right Side: Vehicles Listing -->
        <main>
            <div class="listing-header">
                <h1>Available Vehicles</h1>
                <span class="results-count"><?php echo count($vehiclesList); ?> vehicles found</span>
            </div>

            <?php if (empty($vehiclesList)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3>No Vehicles Match Your Search</h3>
                    <p>Try adjusting your location, price range or other filter selections.</p>
                </div>
            <?php else: ?>
                <div class="vehicles-grid">
                    <?php foreach ($vehiclesList as $v): ?>
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
        </main>
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
