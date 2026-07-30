<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Driver.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverPerformance.php';

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

$performanceModel = new DriverPerformance();
$stats = $performanceModel->getStatistics($driver['id']);
$reviews = $performanceModel->getRatingSummary($driver['id']);

// Page config
$pageTitle = "Performance Stats - Lanka Renters";
$activePage = "performance";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Driver Performance Reports</h2>
            <p class="welcome-subtitle">Review your active ratings, driving metrics, and reviews logs.</p>
        </div>
    </div>

    <!-- Stats summary grid -->
    <section class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <h3>Completed Trips</h3>
            <p><?php echo $stats['completed_trips']; ?></p>
            <span>Total trips completed</span>
        </div>

        <div class="stat-card">
            <h3>Month Trips</h3>
            <p><?php echo $stats['month_trips']; ?></p>
            <span>Trips in current month</span>
        </div>

        <div class="stat-card">
            <h3>Driving Hours</h3>
            <p><?php echo number_format($stats['total_hours'], 1); ?> hrs</p>
            <span>Total operation duration</span>
        </div>

        <div class="stat-card">
            <h3>Average Rating</h3>
            <p><?php echo number_format($stats['avg_rating'], 2); ?> / 5.0</p>
            <span>Overall driver score</span>
        </div>
    </section>

    <!-- Ratings & Reviews Log -->
    <div class="card">
        <h2 class="card-title">Customer Reviews & Feedback</h2>
        <?php if (empty($reviews)): ?>
            <p style="font-style: italic; color: var(--text-muted);">No reviews received yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Rating Score</th>
                        <th>Review Comments</th>
                        <th>Customer</th>
                        <th>Date Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo (int)($rev['driver_rating'] ?? 5); ?> / 5
                                </strong>
                            </td>
                            <td><?php echo !empty($rev['review_text']) ? htmlspecialchars($rev['review_text']) : '<em>No comment left.</em>'; ?></td>
                            <td><?php echo htmlspecialchars($rev['customer_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($rev['created_at'])); ?></td>
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
