<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/CustomerController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/Database.php';

AuthHelper::startSession();

// Auth check
if (!AuthHelper::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
if (($user['role'] ?? '') !== 'customer') {
    AuthHelper::logout();
    header("Location: ../login.php");
    exit();
}

$customerController = new CustomerController();

$vehicleId = (int)($_GET['vehicle_id'] ?? ($_POST['vehicle_id'] ?? 0));
$startDate = $_GET['start_date'] ?? ($_POST['start_date'] ?? '');
$endDate = $_GET['end_date'] ?? ($_POST['end_date'] ?? '');

$message = '';
$error = '';
$selectedDriverId = 0;

// Test vehicle & dates fallbacks if empty to make page interactive
if (!$vehicleId) {
    // Look up first available vehicle for testing
    $db = Database::getInstance()->getConnection();
    $vehicleId = (int)$db->query("SELECT id FROM vehicles LIMIT 1")->fetchColumn();
}
if (empty($startDate)) {
    $startDate = date('Y-m-d H:i:s', strtotime('+1 day'));
}
if (empty($endDate)) {
    $endDate = date('Y-m-d H:i:s', strtotime('+2 days'));
}

// Fetch eligible drivers
$driversResult = $customerController->getEligibleDriversForVehicle($vehicleId, $startDate, $endDate);
$drivers = $driversResult['success'] ? $driversResult['drivers'] : [];
if (!$driversResult['success']) {
    $error = $driversResult['error'];
}

// Handle selection submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select') {
    $driverId = (int)$_POST['driver_id'];
    $valResult = $customerController->selectDriverForBooking($vehicleId, $driverId, $startDate, $endDate);
    if ($valResult['success']) {
        $message = "Driver Selected Successfully! Driver ID #" . $driverId . " has been assigned to your booking.";
        $selectedDriverId = $driverId;
    } else {
        $error = $valResult['error'];
    }
}

// Fetch vehicle info
$db = Database::getInstance()->getConnection();
$stmtVeh = $db->prepare("SELECT v.*, u.name as owner_name FROM vehicles v JOIN vehicle_owners vo ON v.owner_id = vo.id JOIN users u ON vo.user_id = u.id WHERE v.id = :id");
$stmtVeh->execute(['id' => $vehicleId]);
$vehicle = $stmtVeh->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Driver - Lanka Renters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/driver.css">
    <style>
        :root {
            --primary: #3b82f6; /* Blue for Customer */
        }
        .btn-select {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-select:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Customer Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                Lanka Renters
            </div>
            <nav class="sidebar-nav">
                <a href="select_driver.php" class="nav-link active">
                    Select Driver
                </a>
            </nav>
            <div class="sidebar-footer">
                <form action="../driver/dashboard.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn-sidebar-logout">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Body -->
        <div class="main-body">
            <header class="navbar">
                <div style="font-weight: 700; font-size: 18px; color: var(--text-main);">
                    Customer Booking Portal
                </div>
                <div class="navbar-profile">
                    <span class="user-initial"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                    <span class="profile-name"><?php echo htmlspecialchars($user['name']); ?> (Customer)</span>
                </div>
            </header>

            <main class="main-content">
                <div class="welcome-container">
                    <div>
                        <h2 class="welcome-title">Select Your Driver</h2>
                        <p class="welcome-subtitle">Configure options for your with-driver booking.</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Vehicle Info Panel -->
                <?php if ($vehicle): ?>
                    <div class="card" style="margin-bottom: 25px;">
                        <h2 class="card-title">Selected Vehicle Details</h2>
                        <p style="margin-bottom: 6px;"><strong>Vehicle:</strong> <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?> (<?php echo htmlspecialchars($vehicle['license_plate']); ?>)</p>
                        <p style="margin-bottom: 6px;"><strong>Vehicle Owner:</strong> <?php echo htmlspecialchars($vehicle['owner_name']); ?></p>
                        <p style="margin-bottom: 6px;"><strong>Booking Dates:</strong> <?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Drivers List Card -->
                <div class="card">
                    <h2 class="card-title">Available Drivers for this Vehicle Owner</h2>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                        Only showing verified drivers linked to this vehicle's owner who are available during the requested period.
                    </p>
                    
                    <?php if (empty($drivers)): ?>
                        <p style="font-style: italic; color: var(--text-muted);">No eligible drivers found for this owner/schedule. Try a different vehicle or schedule.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Driver Name</th>
                                    <th>Rating</th>
                                    <th>Completed Trips</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($drivers as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
                                        <td>
                                            <span style="color: #f59e0b; font-weight: bold;">
                                                <?php echo number_format($d['rating_avg'], 1); ?> ⭐
                                            </span>
                                        </td>
                                        <td><?php echo $d['completed_trips']; ?> trips</td>
                                        <td>
                                            <span class="status-pill status-available">Available</span>
                                        </td>
                                        <td>
                                            <?php if ($selectedDriverId === (int)$d['driver_id']): ?>
                                                <span class="status-pill status-approved" style="font-size: 12px; padding: 6px 12px;">Selected</span>
                                            <?php else: ?>
                                                <form action="" method="POST" style="margin:0;">
                                                    <input type="hidden" name="action" value="select">
                                                    <input type="hidden" name="driver_id" value="<?php echo $d['driver_id']; ?>">
                                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicleId; ?>">
                                                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                                                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                                                    <button type="submit" class="btn-select">Select Driver</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
