<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';

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

// Resolve secure driver profile context
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    die("Driver profile record not found: " . htmlspecialchars($dashboardResult['error']));
}
$driver = $dashboardResult['profile'];

$error = '';
$success = '';

// Handle incident submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report_incident') {
    // Validate CSRF token
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $incidentPhoto = $_FILES['incident_photo'] ?? null;
        $reportResult = $driverController->reportIncident($_POST, $incidentPhoto);
        if ($reportResult['success']) {
            $success = $reportResult['message'];
        } else {
            $error = $reportResult['error'];
        }
    }
}

// Fetch bookings assigned to driver to populate select options via viewTrips
$tripsResult = $driverController->viewTrips();
$assignedBookings = $tripsResult['success'] ? array_merge($tripsResult['active_trips'], $tripsResult['completed_trips']) : [];

// Page config
$pageTitle = "Report Incident - Lanka Renters";
$activePage = "report_incident";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Incident Reporting</h2>
            <p class="welcome-subtitle">File logs for accidents, mechanical problems, or issues during your trips.</p>
        </div>
    </div>

    <!-- Success/Error Messages -->
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

    <!-- Incident Form -->
    <div class="card" style="max-width: 600px;">
        <h2 class="card-title">New Incident Report</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
            <input type="hidden" name="action" value="report_incident">

            <div class="form-group">
                <label for="booking_id" class="form-label">Select Assigned Booking</label>
                <select name="booking_id" id="booking_id" class="form-control" required>
                    <option value="">-- Choose Booking --</option>
                    <?php foreach ($assignedBookings as $b): ?>
                        <option value="<?php echo (int)$b['id']; ?>">
                            Booking #<?php echo htmlspecialchars($b['id']); ?> - <?php echo htmlspecialchars($b['vehicle_make'] . ' ' . $b['vehicle_model']); ?> (Customer: <?php echo htmlspecialchars($b['customer_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="severity" class="form-label">Severity Level</label>
                <select name="severity" id="severity" class="form-control" required>
                    <option value="minor">Minor (Scratch, minor issue, non-accident)</option>
                    <option value="moderate">Moderate (Dents, repair needed but drivable)</option>
                    <option value="major">Major (Accident, breakdown, cannot drive)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="incident_date" class="form-label">Date & Time of Incident</label>
                <input type="datetime-local" name="incident_date" id="incident_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description of Event / Mechanical Issue</label>
                <textarea name="description" id="description" class="form-control" rows="5" placeholder="Detail the event, accident details, or vehicle problems..." required></textarea>
            </div>

            <div class="form-group">
                <label for="incident_photo" class="form-label">Attach Photo (Optional)</label>
                <input type="file" name="incident_photo" id="incident_photo" class="form-control">
            </div>

            <button type="submit" class="btn-blue" style="margin-top: 10px; width:100%;">Submit Incident Report</button>
        </form>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
