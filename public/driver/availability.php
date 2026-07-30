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

$error = '';
$success = '';

// Handle availability update submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_availability') {
    $status = $_POST['availability_status'] ?? '';
    $result = $driverController->updateAvailability($status);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['error'];
    }
}

// Fetch dashboard data to get current status and driver profile details
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    die("Error loading driver data: " . htmlspecialchars($dashboardResult['error']));
}

$profile = $dashboardResult['profile'];
$currentStatus = $dashboardResult['dashboard_stats']['availability_status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - Lanka Renters</title>
    <link rel="stylesheet" href="../assets/css/driver.css">
</head>
<body>
    <!-- Header -->
    <header class="dash-header">
        <h1>Lanka Renters</h1>
        <div class="nav-links">
            <span>Manage Availability</span>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-msg">
            Logged in as: <?php echo htmlspecialchars($profile['name']); ?>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Form and Info Section -->
        <div style="display: flex; gap: 40px; flex-wrap: wrap;">
            <!-- Left: Update Status Form -->
            <div style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 20px;">
                <h2>Change Status</h2>
                
                <p>
                    <strong>Current Status:</strong> 
                    <span class="status-pill status-<?php echo ($currentStatus === 'available' ? 'approved' : ($currentStatus === 'busy' ? 'pending' : 'rejected')); ?>">
                        <?php 
                            $statusMap = ['available' => 'Available', 'busy' => 'Busy', 'off_duty' => 'Off Duty'];
                            echo htmlspecialchars($statusMap[$currentStatus] ?? $currentStatus); 
                        ?>
                    </span>
                </p>

                <form action="" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="update_availability">
                    
                    <div class="form-group">
                        <label for="availability_status" class="form-label">Select New Status</label>
                        <select name="availability_status" id="availability_status" class="form-control" required>
                            <option value="available" <?php echo $currentStatus === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="busy" <?php echo $currentStatus === 'busy' ? 'selected' : ''; ?>>Busy</option>
                            <option value="off_duty" <?php echo $currentStatus === 'off_duty' ? 'selected' : ''; ?>>Off Duty</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-blue" style="margin-top: 10px;">Update Status</button>
                </form>
            </div>

            <!-- Right: Status Explanations -->
            <div style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 20px; background-color: #f9f9f9;">
                <h2>Status Definitions</h2>
                <ul style="line-height: 1.8; padding-left: 20px;">
                    <li><strong>Available:</strong> Ready to receive bookings and trips. You will show up as active in searching.</li>
                    <li><strong>Busy:</strong> Currently assigned to an active booking or trip.</li>
                    <li><strong>Off Duty:</strong> Not currently on shift. You will not receive any new booking offers.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
