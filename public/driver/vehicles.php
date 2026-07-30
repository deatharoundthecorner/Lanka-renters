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

// Fetch driver details and vehicle assignments
$dashboardResult = $driverController->dashboard();
$driverName = $dashboardResult['success'] ? $dashboardResult['profile']['name'] : 'Driver';

$vehiclesResult = $driverController->viewAssignedVehicles();
$vehicles = $vehiclesResult['success'] ? $vehiclesResult['vehicles'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Vehicles - Lanka Renters</title>
    <link rel="stylesheet" href="../assets/css/driver.css">
</head>
<body>
    <!-- Header -->
    <header class="dash-header">
        <h1>Lanka Renters</h1>
        <div class="nav-links">
            <span>Assigned Vehicles</span>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-msg">
            Logged in as: <?php echo htmlspecialchars($driverName); ?>
        </div>

        <h2>Assigned Vehicles List</h2>
        <?php if (empty($vehicles)): ?>
            <p>No vehicles are currently assigned to you.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>Make / Brand</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Assigned At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong></td>
                            <td><?php echo htmlspecialchars($vehicle['make']); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['year']); ?></td>
                            <td style="text-transform: capitalize;"><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                            <td>
                                <span class="status-pill status-<?php echo ($vehicle['status'] === 'available' ? 'approved' : 'pending'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($vehicle['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($vehicle['assigned_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
