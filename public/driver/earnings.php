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
$monthlyEarnings = $paymentModel->getMonthlyEarnings($driver['id']);

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
            <h3><?php echo date('F'); ?> Earnings</h3>
            <p style="color: var(--primary);">Rs. <?php echo number_format($monthlyEarnings, 2); ?></p>
            <span>Current month earnings</span>
        </div>

        <div class="stat-card">
            <h3>Pending Payments</h3>
            <p style="color: var(--warning);">Rs. <?php echo number_format($earningsSplit['pending'], 2); ?></p>
            <span>Awaiting clearance</span>
        </div>

        <div class="stat-card">
            <h3>Paid Payments</h3>
            <p style="color: var(--success);">Rs. <?php echo number_format($earningsSplit['paid'], 2); ?></p>
            <span>Successfully cleared</span>
        </div>

        <div class="stat-card">
            <h3>Total Earnings</h3>
            <p style="color: var(--text-main);">Rs. <?php echo number_format($earningsSplit['total'], 2); ?></p>
            <span>Overall accumulated sum</span>
        </div>
    </section>

    <!-- Earnings History List -->
    <div class="card">
        <h2 class="card-title">Trip Earnings History</h2>
        <?php if (empty($payments)): ?>
            <p style="font-style: italic; color: var(--text-muted);">No earnings records found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($pay['booking_id']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($pay['booking_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($pay['customer_name']); ?></strong></td>
                            <td>
                                <strong>
                                    Rs. <?php echo number_format($pay['amount'], 2); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="status-pill status-<?php echo ($pay['payment_status'] === 'paid' ? 'approved' : ($pay['payment_status'] === 'pending' ? 'pending' : 'rejected')); ?>">
                                    <?php echo htmlspecialchars(ucfirst($pay['payment_status'])); ?>
                                </span>
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
