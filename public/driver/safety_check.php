<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Driver.php';
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

$driverModel = new Driver();
$driver = $driverModel->findByUserId($user['id']);
if (!$driver) {
    die("Driver profile record not found.");
}

$safetyCheckModel = new VehicleSafetyCheck();
$error = '';
$success = '';

// Handle safety check submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'safety_check') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $brakes = isset($_POST['brakes']) ? 1 : 0;
    $lights = isset($_POST['lights']) ? 1 : 0;
    $tires = isset($_POST['tires']) ? 1 : 0;
    $fuel = isset($_POST['fuel']) ? 1 : 0;

    // Fetch booking details to verify it exists and is assigned
    $db = Database::getInstance()->getConnection();
    $sqlBooking = "SELECT vehicle_id FROM bookings WHERE id = :booking_id AND driver_id = :driver_id LIMIT 1";
    $stmt = $db->prepare($sqlBooking);
    $stmt->execute([
        'booking_id' => $bookingId,
        'driver_id'  => $driver['id']
    ]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $error = "Booking not found or not assigned to you.";
    } else {
        // Check duplicate check
        $existing = $safetyCheckModel->getByBooking($bookingId);
        if ($existing) {
            $error = "Safety check has already been recorded for this booking.";
        } else {
            // Save safety check
            $checkId = $safetyCheckModel->create([
                'driver_id'  => $driver['id'],
                'vehicle_id' => $booking['vehicle_id'],
                'booking_id' => $bookingId,
                'brakes'     => $brakes,
                'lights'     => $lights,
                'tires'      => $tires,
                'fuel'       => $fuel
            ]);

            if ($checkId) {
                $success = "Vehicle safety checks logged successfully!";
            } else {
                $error = "Failed to log vehicle safety check.";
            }
        }
    }
}

// Fetch assigned bookings to populate select
$assignedBookings = $driverModel->getBookings($driver['id']);

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

    <!-- Safety Checklist Form -->
    <div class="card" style="max-width: 600px;">
        <h2 class="card-title">Pre-Trip Checklist</h2>
        <form action="" method="POST">
            <input type="hidden" name="action" value="safety_check">

            <div class="form-group">
                <label for="booking_id" class="form-label">Select Booking / Trip</label>
                <select name="booking_id" id="booking_id" class="form-control" required>
                    <option value="">-- Select Trip --</option>
                    <?php foreach ($assignedBookings as $b): ?>
                        <option value="<?php echo (int)$b['id']; ?>">
                            Booking #<?php echo htmlspecialchars($b['id']); ?> - <?php echo htmlspecialchars($b['vehicle_make'] . ' ' . $b['vehicle_model']); ?> (Customer: <?php echo htmlspecialchars($b['customer_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3 style="font-size:14px; font-weight:700; margin-top:25px; margin-bottom:10px; color: var(--text-main);">Checklist Items:</h3>
            
            <div style="margin: 15px 0; display: flex; align-items: center; gap: 12px;">
                <input type="checkbox" name="brakes" id="brakes" value="1" required style="width: 18px; height: 18px; cursor: pointer;">
                <label for="brakes" style="font-weight: 500; cursor: pointer; font-size:14px;">Brakes work correctly (checked & responsive)</label>
            </div>

            <div style="margin: 15px 0; display: flex; align-items: center; gap: 12px;">
                <input type="checkbox" name="lights" id="lights" value="1" required style="width: 18px; height: 18px; cursor: pointer;">
                <label for="lights" style="font-weight: 500; cursor: pointer; font-size:14px;">Lights (headlights, signals, brakes) function properly</label>
            </div>

            <div style="margin: 15px 0; display: flex; align-items: center; gap: 12px;">
                <input type="checkbox" name="tires" id="tires" value="1" required style="width: 18px; height: 18px; cursor: pointer;">
                <label for="tires" style="font-weight: 500; cursor: pointer; font-size:14px;">Tires pressure and tread depth are in good status</label>
            </div>

            <div style="margin: 15px 0; display: flex; align-items: center; gap: 12px;">
                <input type="checkbox" name="fuel" id="fuel" value="1" required style="width: 18px; height: 18px; cursor: pointer;">
                <label for="fuel" style="font-weight: 500; cursor: pointer; font-size:14px;">Fuel level is sufficient for the booking trip</label>
            </div>

            <button type="submit" class="btn-blue" style="margin-top: 20px; width: 100%;">Record & Confirm Checklist</button>
        </form>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
