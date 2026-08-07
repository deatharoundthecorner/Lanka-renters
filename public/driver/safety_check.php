<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/models/VehicleSafetyCheck.php';

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

// 1. Handle safety check submission (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'safety_check') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $checkResult = $driverController->submitSafetyCheck($_POST);
        if ($checkResult['success']) {
            $success = $checkResult['message'];
        } else {
            $error = $checkResult['error'];
        }
    }
}

// 2. Handle safety check edit submission (Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_safety_check') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $checkId = (int)($_POST['check_id'] ?? 0);
        $result = $driverController->editSafetyCheck($checkId, $_POST);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// 3. Handle safety check delete submission (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_safety_check') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $checkId = (int)($_POST['check_id'] ?? 0);
        $result = $driverController->deleteSafetyCheck($checkId);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// 4. Resolve editing context if GET param is specified
$editCheck = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $safetyCheckModel = new VehicleSafetyCheck();
    $fetchedCheck = $safetyCheckModel->getById($editId);
    if ($fetchedCheck && $fetchedCheck['driver_id'] === $driver['id']) {
        $db = Database::getInstance()->getConnection();
        $sqlBooking = "SELECT pickup_status FROM bookings WHERE id = :booking_id LIMIT 1";
        $stmt = $db->prepare($sqlBooking);
        $stmt->execute(['booking_id' => $fetchedCheck['booking_id']]);
        $pickupStatus = $stmt->fetchColumn();
        
        if ($pickupStatus === 'pending_pickup') {
            $editCheck = $fetchedCheck;
        } else {
            $error = "Cannot edit safety checks for trips that have already started.";
        }
    }
}

// Fetch assigned bookings to populate select via viewTrips
$tripsResult = $driverController->viewTrips();
$assignedBookings = $tripsResult['success'] ? array_merge($tripsResult['active_trips'], $tripsResult['completed_trips']) : [];

// Fetch previous safety checks (Read)
$safetyLogsResult = $driverController->viewSafetyChecks();
$inspections = $safetyLogsResult['success'] ? $safetyLogsResult['checks'] : [];

// Page config
$pageTitle = "Vehicle Inspection - Lanka Renters";
$activePage = "safety_check";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Vehicle Safety Inspection</h2>
            <p class="welcome-subtitle">Register and record pre-trip inspection safety checklists.</p>
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
        <!-- Safety Checklist Form (Create/Update state) -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title"><?php echo $editCheck ? 'Edit Checklist' : 'Pre-Trip Checklist'; ?></h2>
            <form action="safety_check.php<?php echo $editCheck ? '?edit_id=' . $editCheck['id'] : ''; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="<?php echo $editCheck ? 'edit_safety_check' : 'safety_check'; ?>">
                <?php if ($editCheck): ?>
                    <input type="hidden" name="check_id" value="<?php echo htmlspecialchars($editCheck['id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="booking_id" class="form-label">Select Booking / Trip</label>
                    <?php if ($editCheck): ?>
                        <input type="text" class="form-control" value="Booking #<?php echo htmlspecialchars($editCheck['booking_id']); ?>" readonly disabled>
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($editCheck['booking_id']); ?>">
                    <?php else: ?>
                        <select name="booking_id" id="booking_id" class="form-control" required>
                            <option value="">-- Select Trip --</option>
                            <?php foreach ($assignedBookings as $b): ?>
                                <option value="<?php echo (int)$b['id']; ?>">
                                    Booking #<?php echo htmlspecialchars($b['id']); ?> - <?php echo htmlspecialchars($b['vehicle_make'] . ' ' . $b['vehicle_model']); ?> (Customer: <?php echo htmlspecialchars($b['customer_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <h3 style="font-size:13px; font-weight:700; margin-top:20px; margin-bottom:10px; color: var(--text-main);">Checklist Items:</h3>
                
                <div style="margin: 12px 0; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="brakes" id="brakes" value="1" <?php echo ($editCheck && $editCheck['brakes']) ? 'checked' : ''; ?> required style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="brakes" style="font-weight: 500; cursor: pointer; font-size:13px;">Brakes work correctly (checked & responsive)</label>
                </div>

                <div style="margin: 12px 0; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="lights" id="lights" value="1" <?php echo ($editCheck && $editCheck['lights']) ? 'checked' : ''; ?> required style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="lights" style="font-weight: 500; cursor: pointer; font-size:13px;">Lights (headlights, signals) function properly</label>
                </div>

                <div style="margin: 12px 0; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="tires" id="tires" value="1" <?php echo ($editCheck && $editCheck['tires']) ? 'checked' : ''; ?> required style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="tires" style="font-weight: 500; cursor: pointer; font-size:13px;">Tires pressure and tread depth are in order</label>
                </div>

                <div style="margin: 12px 0; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="fuel" id="fuel" value="1" <?php echo ($editCheck && $editCheck['fuel']) ? 'checked' : ''; ?> required style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="fuel" style="font-weight: 500; cursor: pointer; font-size:13px;">Fuel level is sufficient for the booking trip</label>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-blue" style="flex: 1.5;"><?php echo $editCheck ? 'Save Changes' : 'Confirm Checklist'; ?></button>
                    <?php if ($editCheck): ?>
                        <a href="safety_check.php" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; padding: 10px 0;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- History Log (Read & Delete state) -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Previous Inspections Log</h2>
            <?php if (empty($inspections)): ?>
                <p style="font-style: italic; color: var(--text-muted);">No inspections recorded yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Trip #</th>
                            <th>Vehicle</th>
                            <th>Inspection Results</th>
                            <th>Recorded On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inspections as $check): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($check['booking_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($check['make'] . ' ' . $check['model']); ?></strong><br>
                                    <span style="font-size:11px; color: var(--text-muted);"><?php echo htmlspecialchars($check['license_plate']); ?></span>
                                </td>
                                <td style="font-size: 11px;">
                                    Brakes: <?php echo $check['brakes'] ? '🟢 Pass' : '🔴 Fail'; ?><br>
                                    Lights: <?php echo $check['lights'] ? '🟢 Pass' : '🔴 Fail'; ?><br>
                                    Tires: <?php echo $check['tires'] ? '🟢 Pass' : '🔴 Fail'; ?><br>
                                    Fuel: <?php echo $check['fuel'] ? '🟢 Pass' : '🔴 Fail'; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($check['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <!-- Edit Check (Only active when booking pickup status is pending_pickup) -->
                                        <?php if ($check['pickup_status'] === 'pending_pickup'): ?>
                                            <a href="safety_check.php?edit_id=<?php echo $check['id']; ?>" class="btn-secondary" style="padding: 4px 8px; font-size: 11px; text-decoration: none;">Edit</a>
                                            
                                            <!-- Delete Check -->
                                            <form action="" method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this safety inspection?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                                <input type="hidden" name="action" value="delete_safety_check">
                                                <input type="hidden" name="check_id" value="<?php echo $check['id']; ?>">
                                                <button type="submit" style="padding: 4px 8px; font-size: 11px; background-color: var(--danger); color: white; border: none; border-radius: 4px; cursor: pointer;">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 11px;">Trip Started</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
