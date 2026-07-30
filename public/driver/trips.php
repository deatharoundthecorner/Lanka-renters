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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Trips - Lanka Renters</title>
    <link rel="stylesheet" href="../assets/css/driver.css">
</head>
<body>
    <!-- Header -->
    <header class="dash-header">
        <h1>Lanka Renters</h1>
        <div class="nav-links">
            <span>Trip Tracking</span>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-msg">
            Logged in as: <?php echo htmlspecialchars($driverName); ?>
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
        <h2>Active Assigned Trips</h2>
        <?php if (empty($activeTrips)): ?>
            <p>No active or ongoing bookings are currently assigned to you.</p>
        <?php else: ?>
            <?php foreach ($activeTrips as $trip): ?>
                <div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; background-color: #f9f9f9; display: flex; gap: 40px; flex-wrap: wrap;">
                    
                    <!-- Left: Details -->
                    <div style="flex: 1; min-width: 250px;">
                        <h3>Booking #<?php echo htmlspecialchars($trip['id']); ?></h3>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($trip['customer_name']); ?></p>
                        <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($trip['vehicle_make'] . ' ' . $trip['vehicle_model'] . ' (' . $trip['vehicle_plate'] . ')'); ?></p>
                        <p><strong>Pickup Address:</strong> <?php echo htmlspecialchars($trip['delivery_address'] ?? 'No address provided'); ?></p>
                        <p><strong>Start Date:</strong> <?php echo date('Y-m-d H:i', strtotime($trip['start_date'])); ?></p>
                        <p><strong>End Date:</strong> <?php echo date('Y-m-d H:i', strtotime($trip['end_date'])); ?></p>
                        <p>
                            <strong>Status:</strong> 
                            <span class="status-pill status-<?php echo ($trip['status'] === 'ongoing' ? 'approved' : 'pending'); ?>">
                                <?php echo htmlspecialchars($trip['status']); ?>
                            </span>
                        </p>
                    </div>

                    <!-- Right: Update Form -->
                    <div style="flex: 1; min-width: 250px; border-left: 1px solid #ddd; padding-left: 30px;">
                        <h3>Update Pickup Status</h3>
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

                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Latitude (Optional)</label>
                                    <input type="number" step="any" name="latitude" id="latitude_<?php echo $trip['id']; ?>" class="form-control" placeholder="e.g. 6.9271">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Longitude (Optional)</label>
                                    <input type="number" step="any" name="longitude" id="longitude_<?php echo $trip['id']; ?>" class="form-control" placeholder="e.g. 79.8612">
                                </div>
                            </div>

                            <button type="button" class="btn-blue" style="background-color: #6c757d; margin-bottom: 10px; display: block; width: 100%;" onclick="detectGPS(<?php echo $trip['id']; ?>)">
                                Detect My GPS Location
                            </button>

                            <button type="submit" class="btn-blue" style="display: block; width: 100%;">Update Status</button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Completed Trip History -->
        <h2>Completed Trip History</h2>
        <?php if (empty($completedTrips)): ?>
            <p>No completed or cancelled trips in your history.</p>
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
</body>
</html>
