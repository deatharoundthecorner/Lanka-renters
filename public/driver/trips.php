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

// Handle pickup status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pickup') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $status = $_POST['pickup_status'] ?? '';
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    
    $result = $driverController->updatePickupStatus($bookingId, $status, $latitude, $longitude);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['error'];
    }
}

// Fetch driver details
$dashboardResult = $driverController->dashboard();
$driverName = $dashboardResult['success'] ? $dashboardResult['profile']['name'] : 'Driver';

// Fetch active and historical trips
$tripsResult = $driverController->viewTrips();
$activeTrips = $tripsResult['success'] ? $tripsResult['active_trips'] : [];
$completedTrips = $tripsResult['success'] ? $tripsResult['completed_trips'] : [];

// Page config
$pageTitle = "Trip Tracking - Lanka Renters";
$activePage = "trips";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">My Trips & Pickup Tracking</h2>
            <p class="welcome-subtitle">Update travel status and GPS coordinates of active bookings.</p>
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

    <!-- Active Trips -->
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Active Assigned Trips</h2>
    <?php if (empty($activeTrips)): ?>
        <p style="font-style: italic; color: var(--text-muted); margin-bottom: 30px;">No active or ongoing bookings are currently assigned to you.</p>
    <?php else: ?>
        <?php foreach ($activeTrips as $trip): ?>
            <div class="card" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px;">
                <!-- Left: Details -->
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--primary);">Booking #<?php echo htmlspecialchars($trip['id']); ?></h3>
                    <table style="border: none; margin-bottom: 20px; font-size:13px;">
                        <tr style="background: none;"><td style="border:none; padding:4px 0; font-weight:600; width:130px;">Customer:</td><td style="border:none; padding:4px 0;"><?php echo htmlspecialchars($trip['customer_name']); ?></td></tr>
                        <tr style="background: none;"><td style="border:none; padding:4px 0; font-weight:600;">Vehicle:</td><td style="border:none; padding:4px 0;"><?php echo htmlspecialchars($trip['vehicle_make'] . ' ' . $trip['vehicle_model']); ?> (<?php echo htmlspecialchars($trip['vehicle_plate']); ?>)</td></tr>
                        <tr style="background: none;"><td style="border:none; padding:4px 0; font-weight:600;">Pickup Address:</td><td style="border:none; padding:4px 0;"><?php echo htmlspecialchars($trip['delivery_address'] ?? 'No address provided'); ?></td></tr>
                        <tr style="background: none;"><td style="border:none; padding:4px 0; font-weight:600;">Start Date:</td><td style="border:none; padding:4px 0;"><?php echo date('Y-m-d H:i', strtotime($trip['start_date'])); ?></td></tr>
                        <tr style="background: none;"><td style="border:none; padding:4px 0; font-weight:600;">End Date:</td><td style="border:none; padding:4px 0;"><?php echo date('Y-m-d H:i', strtotime($trip['end_date'])); ?></td></tr>
                        <tr style="background: none;"><td style="border:none; padding:4px 0; font-weight:600;">Trip Status:</td><td style="border:none; padding:4px 0;"><span class="status-pill status-approved"><?php echo htmlspecialchars($trip['status']); ?></span></td></tr>
                    </table>
                </div>

                <!-- Right: Update Form -->
                <div style="border-left: 1px solid var(--border); padding-left: 30px;">
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 15px;">Update Pickup Status</h3>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_pickup">
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($trip['id']); ?>">
                        
                        <div class="form-group">
                            <label for="pickup_status_<?php echo $trip['id']; ?>" class="form-label">Select Status</label>
                            <select name="pickup_status" id="pickup_status_<?php echo $trip['id']; ?>" class="form-control" required>
                                <option value="pending_pickup" <?php echo $trip['pickup_status'] === 'pending_pickup' ? 'selected' : ''; ?>>Pending Pickup</option>
                                <option value="dispatched" <?php echo $trip['pickup_status'] === 'dispatched' ? 'selected' : ''; ?>>Dispatched / On the Way</option>
                                <option value="arrived" <?php echo $trip['pickup_status'] === 'arrived' ? 'selected' : ''; ?>>Arrived at Location</option>
                                <option value="picked_up" <?php echo $trip['pickup_status'] === 'picked_up' ? 'selected' : ''; ?>>Picked Up / In Transit</option>
                                <option value="dropped_off" <?php echo $trip['pickup_status'] === 'dropped_off' ? 'selected' : ''; ?>>Dropped Off / Completed</option>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:12px;">Latitude (Optional)</label>
                                <input type="number" step="any" name="latitude" id="latitude_<?php echo $trip['id']; ?>" class="form-control" placeholder="e.g. 6.9271">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:12px;">Longitude (Optional)</label>
                                <input type="number" step="any" name="longitude" id="longitude_<?php echo $trip['id']; ?>" class="form-control" placeholder="e.g. 79.8612">
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="btn-secondary" style="flex: 1;" onclick="detectGPS(<?php echo $trip['id']; ?>)">Detect GPS</button>
                            <button type="submit" class="btn-blue" style="flex: 1.5;">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Completed Trip History -->
    <div class="card">
        <h2 class="card-title">Completed Trip History</h2>
        <?php if (empty($completedTrips)): ?>
            <p style="font-style: italic; color: var(--text-muted);">No completed or cancelled trips in your history.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedTrips as $history): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($history['id']); ?></td>
                            <td><?php echo htmlspecialchars($history['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($history['vehicle_make'] . ' ' . $history['vehicle_model']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($history['start_date'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($history['end_date'])); ?></td>
                            <td>
                                <span class="status-pill status-<?php echo ($history['status'] === 'completed' ? 'approved' : 'rejected'); ?>">
                                    <?php echo htmlspecialchars($history['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Geolocation script -->
    <script>
        function detectGPS(tripId) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('latitude_' + tripId).value = position.coords.latitude.toFixed(6);
                        document.getElementById('longitude_' + tripId).value = position.coords.longitude.toFixed(6);
                    },
                    function(error) {
                        alert("Geolocation Error: " + error.message);
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }
    </script>
</main>
<?php
include 'includes/footer.php';
?>
