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
    // Validate CSRF token
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $status = $_POST['availability_status'] ?? '';
        $result = $driverController->updateAvailability($status);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// Fetch dashboard data to get current status and driver profile details
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    die("Error loading driver data: " . htmlspecialchars($dashboardResult['error']));
}

$profile = $dashboardResult['profile'];
$currentStatus = $dashboardResult['dashboard_stats']['availability_status'];

// Page config
$pageTitle = "Manage Availability - Lanka Renters";
$activePage = "availability";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Driver Availability</h2>
            <p class="welcome-subtitle">Set your active duty shift status for receiving trips.</p>
        </div>
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

    <div style="display: grid; grid-template-columns: 1.2fr 2fr; gap: 30px; align-items: flex-start;">
        <!-- Left: Update Status Form -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Change Status</h2>
            
            <div style="margin-bottom: 20px;">
                <span style="font-size:14px; font-weight:600; color: var(--text-muted);">Current Status:</span>
                <span class="status-pill status-<?php echo ($currentStatus === 'available' ? 'available' : ($currentStatus === 'busy' ? 'busy' : 'off_duty')); ?>" style="margin-left:10px;">
                    <?php 
                        $statusMap = ['available' => 'Available', 'busy' => 'Busy', 'off_duty' => 'Off Duty'];
                        echo htmlspecialchars($statusMap[$currentStatus] ?? $currentStatus); 
                    ?>
                </span>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="update_availability">
                
                <div class="form-group">
                    <label for="availability_status" class="form-label">Select New Status</label>
                    <select name="availability_status" id="availability_status" class="form-control" required>
                        <option value="available" <?php echo $currentStatus === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="busy" <?php echo $currentStatus === 'busy' ? 'selected' : ''; ?>>Busy</option>
                        <option value="off_duty" <?php echo $currentStatus === 'off_duty' ? 'selected' : ''; ?>>Off Duty</option>
                    </select>
                </div>

                <button type="submit" class="btn-blue" style="width: 100%; margin-top: 10px;">Update Status</button>
            </form>
        </div>

        <!-- Right: Status Explanations -->
        <div class="card" style="margin: 0; background-color: #f8fafc;">
            <h2 class="card-title">Status Definitions</h2>
            <ul style="line-height: 1.8; padding-left: 20px; font-size: 14px; color: var(--text-main);">
                <li style="margin-bottom: 10px;"><strong>Available:</strong> Ready to receive bookings and trips. You will show up as active in searching.</li>
                <li style="margin-bottom: 10px;"><strong>Busy:</strong> Currently assigned to an active booking or trip.</li>
                <li><strong>Off Duty:</strong> Not currently on shift. You will not receive any new booking offers.</li>
            </ul>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
