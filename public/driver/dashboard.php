<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverPerformance.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverPayment.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Notification.php';

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

// Fetch advanced analytics for Overview Section
$performanceModel = new DriverPerformance();
$perfStats = $performanceModel->getStatistics($profile['id']);
$reviewsList = $performanceModel->getRatingSummary($profile['id']);
$totalReviews = count($reviewsList);

$paymentModel = new DriverPayment();
$paymentSummary = $paymentModel->getEarningsSummary($profile['id']);
$earningsSplit = $paymentModel->getEarningsSplit($profile['id']);

// Fetch trips for Operations Section
$tripsResult = $driverController->viewTrips();
$activeTrips = $tripsResult['success'] ? $tripsResult['active_trips'] : [];

// Fetch notifications
$notificationModel = new Notification();
$notifications = $notificationModel->getByUserId($user['id']);
$latestNotifications = array_slice($notifications, 0, 3);

// Page configs
$pageTitle = "Driver Dashboard - Lanka Renters";
$activePage = "dashboard";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    
    <!-- Welcome section -->
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Welcome back, <?php echo htmlspecialchars($profile['name']); ?> 👋</h2>
            <p class="welcome-subtitle">Here's what's happening with your account today.</p>
        </div>
        <div class="card" style="margin: 0; padding: 12px 20px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Current Status:</span>
            <span class="status-pill status-<?php echo ($stats['availability_status'] === 'available' ? 'available' : ($stats['availability_status'] === 'busy' ? 'busy' : 'off_duty')); ?>">
                <?php 
                    $availMap = ['available' => 'Available', 'busy' => 'Busy', 'off_duty' => 'Off Duty'];
                    echo htmlspecialchars($availMap[$stats['availability_status']] ?? $stats['availability_status']); 
                ?>
            </span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($statusSuccess)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($statusSuccess); ?></div>
    <?php endif; ?>
    <?php if (!empty($statusError)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($statusError); ?></div>
    <?php endif; ?>

    <!-- 1. Statistics Cards -->
    <section class="stats-grid">
        <div class="stat-card">
            <h3>Verification Status</h3>
            <p style="text-transform: capitalize; color: <?php echo $stats['verification_status'] === 'approved' ? 'var(--success)' : ($stats['verification_status'] === 'rejected' ? 'var(--danger)' : 'var(--warning)'); ?>;">
                <?php echo htmlspecialchars($stats['verification_status']); ?>
            </p>
            <span>Your driver documents state</span>
        </div>

        <div class="stat-card">
            <h3>Average Rating</h3>
            <p><?php echo number_format($stats['rating'], 1); ?> / 5</p>
            <span>Based on <?php echo $totalReviews; ?> <?php echo $totalReviews === 1 ? 'review' : 'reviews'; ?></span>
        </div>

        <div class="stat-card">
            <h3>Completed Trips</h3>
            <p><?php echo $perfStats['completed_trips']; ?></p>
            <span>Total completed trips</span>
        </div>

        <div class="stat-card">
            <h3>Total Earnings</h3>
            <p>Rs. <?php echo number_format($paymentSummary['total_earnings'], 2); ?></p>
            <span>This month</span>
        </div>
    </section>

    <!-- 2. Active Trip Section -->
    <div class="card">
        <h2 class="card-title">Active Trip</h2>
        <?php if (empty($activeTrips)): ?>
            <p style="font-style: italic; color: var(--text-muted);">No active trips at the moment.</p>
        <?php else: 
            $activeTrip = $activeTrips[0]; // Display the main ongoing trip
            $pStatus = $activeTrip['pickup_status'];
        ?>
            <div class="active-trip-grid">
                <!-- Trip Details -->
                <div>
                    <table style="border: none; margin-bottom: 20px;">
                        <tr style="background: none;"><td style="border:none; padding:6px 0; font-weight:600; width:150px;">Booking ID:</td><td style="border:none; padding:6px 0;">#<?php echo htmlspecialchars($activeTrip['id']); ?></td></tr>
                        <tr style="background: none;"><td style="border:none; padding:6px 0; font-weight:600;">Customer Name:</td><td style="border:none; padding:6px 0;"><?php echo htmlspecialchars($activeTrip['customer_name']); ?> (<?php echo htmlspecialchars($activeTrip['customer_phone']); ?>)</td></tr>
                        <tr style="background: none;"><td style="border:none; padding:6px 0; font-weight:600;">Vehicle:</td><td style="border:none; padding:6px 0;"><?php echo htmlspecialchars($activeTrip['vehicle_make'] . ' ' . $activeTrip['vehicle_model']); ?> (<?php echo htmlspecialchars($activeTrip['vehicle_plate']); ?>)</td></tr>
                        <tr style="background: none;"><td style="border:none; padding:6px 0; font-weight:600;">Pickup Location:</td><td style="border:none; padding:6px 0;"><?php echo htmlspecialchars($activeTrip['delivery_address'] ?? 'N/A'); ?></td></tr>
                        <tr style="background: none;"><td style="border:none; padding:6px 0; font-weight:600;">Trip Dates:</td><td style="border:none; padding:6px 0;"><?php echo date('Y-m-d H:i', strtotime($activeTrip['start_date'])); ?> to <?php echo date('Y-m-d H:i', strtotime($activeTrip['end_date'])); ?></td></tr>
                    </table>
                    
                    <a href="trips.php" class="btn-blue">Update Pickup Status</a>
                </div>

                <!-- Trip Timeline -->
                <div style="border-left: 1px solid var(--border); padding-left: 20px;">
                    <h4 style="font-size:14px; font-weight:700; margin-bottom: 15px; color: var(--text-muted);">Trip Status Timeline</h4>
                    <div class="timeline">
                        <!-- Step 1: Driver Assigned -->
                        <div class="timeline-step completed">
                            <div class="timeline-icon">✓</div>
                            <span>Assigned</span>
                        </div>
                        
                        <!-- Step 2: Driver Arriving -->
                        <?php 
                            $step2Class = in_array($pStatus, ['dispatched', 'arrived', 'picked_up', 'dropped_off']) ? 'completed' : 'active';
                            $step2Icon = ($step2Class === 'completed') ? '✓' : '2';
                        ?>
                        <div class="timeline-step <?php echo $step2Class; ?>">
                            <div class="timeline-icon"><?php echo $step2Icon; ?></div>
                            <span>Arriving</span>
                        </div>

                        <!-- Step 3: Customer Picked Up -->
                        <?php 
                            $step3Class = in_array($pStatus, ['picked_up', 'dropped_off']) ? 'completed' : (($pStatus === 'arrived') ? 'active' : '');
                            $step3Icon = ($step3Class === 'completed') ? '✓' : '3';
                        ?>
                        <div class="timeline-step <?php echo $step3Class; ?>">
                            <div class="timeline-icon"><?php echo $step3Icon; ?></div>
                            <span>Picked Up</span>
                        </div>

                        <!-- Step 4: Trip Completed -->
                        <?php 
                            $step4Class = ($pStatus === 'dropped_off') ? 'completed' : (($pStatus === 'picked_up') ? 'active' : '');
                            $step4Icon = ($step4Class === 'completed') ? '✓' : '4';
                        ?>
                        <div class="timeline-step <?php echo $step4Class; ?>">
                            <div class="timeline-icon"><?php echo $step4Icon; ?></div>
                            <span>Completed</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Quick Action Cards -->
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Quick Actions</h2>
    <section class="quick-actions-grid">
        <a href="documents.php" class="quick-action-card">
            <svg width="24" height="24" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3>Documents</h3>
            <p>Upload/View documents</p>
        </a>

        <a href="availability.php" class="quick-action-card">
            <svg width="24" height="24" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3>Availability</h3>
            <p>Update working status</p>
        </a>

        <a href="leave.php" class="quick-action-card">
            <svg width="24" height="24" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h3>Leave Request</h3>
            <p>Request leave</p>
        </a>

        <a href="safety_check.php" class="quick-action-card">
            <svg width="24" height="24" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <h3>Safety Check</h3>
            <p>Vehicle inspection</p>
        </a>

        <a href="report_incident.php" class="quick-action-card">
            <svg width="24" height="24" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3>Report Incident</h3>
            <p>Report issue</p>
        </a>
    </section>

    <!-- 4. Bottom Dashboard Grid -->
    <section class="bottom-grid">
        <!-- Recent Notifications -->
        <div class="card" style="margin-bottom:0;">
            <h2 class="card-title" style="margin-bottom: 15px;">Recent Notifications</h2>
            <?php if (empty($latestNotifications)): ?>
                <p style="font-style: italic; color: var(--text-muted); font-size: 13px;">No notifications.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php foreach ($latestNotifications as $notif): ?>
                        <div style="border-bottom: 1px solid var(--border); padding-bottom:8px;">
                            <span style="font-size:13px; font-weight:600; color:var(--text-main);"><?php echo htmlspecialchars($notif['title']); ?></span><br>
                            <span style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($notif['message']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Earnings Summary -->
        <div class="card" style="margin-bottom:0;">
            <h2 class="card-title" style="margin-bottom: 15px;">Earnings Summary</h2>
            <table style="border: none; font-size: 13px; margin: 0;">
                <tr style="background: none;"><td style="border:none; padding:8px 0; font-weight:600;">Paid Earnings:</td><td style="border:none; padding:8px 0; text-align:right; color: var(--success); font-weight:700;">Rs. <?php echo number_format($earningsSplit['paid'], 2); ?></td></tr>
                <tr style="background: none;"><td style="border:none; padding:8px 0; font-weight:600;">Pending Earnings:</td><td style="border:none; padding:8px 0; text-align:right; color: var(--warning); font-weight:700;">Rs. <?php echo number_format($earningsSplit['pending'], 2); ?></td></tr>
                <tr style="background: none; border-top: 1px solid var(--border);"><td style="border:none; padding:8px 0; font-weight:700;">Total Earnings:</td><td style="border:none; padding:8px 0; text-align:right; font-weight:800; color: var(--primary);">Rs. <?php echo number_format($earningsSplit['total'], 2); ?></td></tr>
            </table>
        </div>

        <!-- Performance Overview -->
        <div class="card" style="margin-bottom:0;">
            <h2 class="card-title" style="margin-bottom: 15px;">Performance Overview</h2>
            <?php 
                $comp = $perfStats['completed_trips'];
                $canc = $perfStats['cancelled_trips'];
                $totalT = $comp + $canc;
                $cancRate = $totalT > 0 ? ($canc / $totalT) * 100 : 0.0;
            ?>
            <table style="border: none; font-size: 13px; margin: 0;">
                <tr style="background: none;"><td style="border:none; padding:8px 0; font-weight:600;">Average Rating:</td><td style="border:none; padding:8px 0; text-align:right; font-weight:700;"><?php echo number_format($stats['rating'], 1); ?> / 5.0</td></tr>
                <tr style="background: none;"><td style="border:none; padding:8px 0; font-weight:600;">Completed Trips:</td><td style="border:none; padding:8px 0; text-align:right; font-weight:700;"><?php echo $comp; ?></td></tr>
                <tr style="background: none;"><td style="border:none; padding:8px 0; font-weight:600;">Cancellation Rate:</td><td style="border:none; padding:8px 0; text-align:right; color: var(--danger); font-weight:700;"><?php echo number_format($cancRate, 1); ?>%</td></tr>
            </table>
        </div>
    </section>

</main>
<?php
include 'includes/footer.php';
?>
