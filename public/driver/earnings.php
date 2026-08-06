<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Driver.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverPayment.php';

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

$paymentModel = new DriverPayment();
$summary = $paymentModel->getEarningsSummary($driver['id']);
$payments = $paymentModel->getPaymentHistory($driver['id']);
$earningsSplit = $paymentModel->getEarningsSplit($driver['id']);

// Page config
$pageTitle = "Earnings History - Lanka Renters";
$activePage = "earnings";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Earnings History</h2>
            <p class="welcome-subtitle">Review completed trips payments, pending clearances, and totals.</p>
        </div>
    </div>

    <!-- Stats summary grid -->
    <section class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <h3>Paid Earnings</h3>
            <p style="color: var(--success);">Rs. <?php echo number_format($earningsSplit['paid'], 2); ?></p>
            <span>Payments received</span>
        </div>

        <div class="stat-card">
            <h3>Pending Earnings</h3>
            <p style="color: var(--warning);">Rs. <?php echo number_format($earningsSplit['pending'], 2); ?></p>
            <span>In clearance process</span>
        </div>

        <div class="stat-card">
            <h3>Total Earnings</h3>
            <p style="color: var(--primary);">Rs. <?php echo number_format($earningsSplit['total'], 2); ?></p>
            <span>Overall accumulated sum</span>
        </div>

        <div class="stat-card">
            <h3>Completed Paid Trips</h3>
            <p><?php echo $summary['trips_count']; ?></p>
            <span>Trip counts</span>
        </div>
    </section>

    <!-- Earnings History List -->
    <div class="card">
        <h2 class="card-title">Earnings & Payment Logs</h2>
        <?php if (empty($payments)): ?>
            <p style="font-style: italic; color: var(--text-muted);">No completed earnings records found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Vehicle Assigned</th>
                        <th>Rental Period</th>
                        <th>Earning Amount</th>
                        <th>Payment Status</th>
                        <th>Date Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($pay['booking_id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($pay['make'] . ' ' . $pay['model']); ?></strong><br>
                                <span style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($pay['license_plate']); ?></span>
                            </td>
                            <td>
                                <?php echo date('Y-m-d', strtotime($pay['start_date'])); ?> 
                                to 
                                <?php echo date('Y-m-d', strtotime($pay['end_date'])); ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo !empty($pay['amount']) ? 'Rs. ' . number_format($pay['amount'], 2) : 'N/A'; ?>
                                </strong>
                            </td>
                            <td>
                                <span class="status-pill status-<?php echo ($pay['payment_status'] === 'completed' ? 'approved' : ($pay['payment_status'] === 'pending' ? 'pending' : 'rejected')); ?>">
                                    <?php echo htmlspecialchars(ucfirst($pay['payment_status'] ?? 'pending')); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo !empty($pay['paid_at']) ? date('Y-m-d H:i', strtotime($pay['paid_at'])) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
