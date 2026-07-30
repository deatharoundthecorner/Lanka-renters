<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Localized driver auth check
if (!AuthHelper::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
if (($user['role'] ?? '') !== 'driver') {
    AuthHelper::logout();
    header("Location: login.php");
    exit();
}

$driverController = new DriverController();

// Handle availability update submissions
$statusError = '';
$statusSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_availability') {
        $newStatus = $_POST['availability_status'] ?? '';
        $result = $driverController->updateAvailability($newStatus);
        if ($result['success']) {
            $statusSuccess = $result['message'];
        } else {
            $statusError = $result['error'];
        }
    } elseif ($_POST['action'] === 'logout') {
        AuthHelper::logout();
        header("Location: login.php");
        exit();
    }
}

// Fetch dashboard data through controller
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    die("Error loading driver dashboard: " . htmlspecialchars($dashboardResult['error']));
}

$profile = $dashboardResult['profile'];
$stats = $dashboardResult['dashboard_stats'];
$vehicles = $dashboardResult['assigned_vehicles'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Lanka Renters</title>
    <link rel="stylesheet" href="../assets/css/driver.css">
</head>
<body>
    <!-- 1. Header Navigation -->
    <header class="dash-header">
        <h1>Lanka Renters</h1>
        <div class="nav-links">
            <span>Driver Dashboard</span>
            <form action="" method="POST" style="margin: 0; display: inline;">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Logout</button>
            </form>
        </div>
    </header>

    <div class="container">
        <!-- Welcome Message -->
        <div class="welcome-msg">
            Welcome, <?php echo htmlspecialchars($profile['name']); ?>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($statusSuccess)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($statusSuccess); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($statusError)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($statusError); ?>
            </div>
        <?php endif; ?>

        <!-- 2. Dashboard Stats Summary Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <h3>Verification</h3>
                <p><?php echo htmlspecialchars(ucfirst($stats['verification_status'])); ?></p>
            </div>

            <div class="stat-card">
                <h3>Availability</h3>
                <p>
                    <?php 
                        $availMap = ['available' => 'Available', 'busy' => 'Busy', 'off_duty' => 'Off Duty'];
                        echo htmlspecialchars($availMap[$stats['availability_status']] ?? $stats['availability_status']); 
                    ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Rating</h3>
                <p><?php echo number_format($stats['rating'], 2); ?></p>
            </div>

            <div class="stat-card">
                <h3>Vehicles</h3>
                <p><?php echo (int)$stats['assigned_vehicle_count']; ?></p>
            </div>
        </section>

        <!-- 3. Navigation Buttons Group -->
        <div class="btn-group">
            <a href="documents.php" class="btn-blue">View Documents</a>
            <a href="leave.php" class="btn-blue">Request Leave</a>
            <a href="trips.php" class="btn-blue">View Trips</a>
            <a href="availability.php" class="btn-blue">Update Status</a>
        </div>

        <!-- 4. Assigned Vehicles Table -->
        <h2>Your Assigned Vehicles</h2>
        <?php if (empty($vehicles)): ?>
            <p>No vehicles are currently assigned to your profile.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Plate No</th>
                        <th>Model</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vehicle['license_plate']); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'); ?></td>
                            <td>Active Assignment</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
