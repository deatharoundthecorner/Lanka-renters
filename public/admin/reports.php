<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Report.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Reports & Analytics";
$districts = getSriLankanDistricts();

try {
    $reportModel = new Report();
    $financialSummary = $reportModel->getFinancialSummary();
    $statusBreakdown = $reportModel->getBookingsByStatus();
    $categoryPopularity = $reportModel->getVehicleCategoryPopularity();
} catch (Throwable $e) {
    error_log("Reports Error: " . $e->getMessage());
    $financialSummary = ['gross_revenue' => 0, 'total_commission' => 0, 'total_payouts' => 0, 'total_bookings' => 0];
    $statusBreakdown = [];
    $categoryPopularity = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Reports & Analytics</h1>
                <p class="page-subtitle">Generate custom analytical summaries and export system performance metrics.</p>
            </div>

            <!-- Financial Summary Cards -->
            <div class="grid-4">
                <div class="stat-card" style="border-left: 4px solid var(--primary);">
                    <span class="stat-label">Gross Completed Rental Revenue</span>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format((float)$financialSummary['gross_revenue'], 2) ?></div>
                    <span class="stat-comparison">Total Completed Trips</span>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--success);">
                    <span class="stat-label">Platform Commission (10%)</span>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format((float)$financialSummary['total_commission'], 2) ?></div>
                    <span class="stat-comparison">Platform Revenue</span>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--info);">
                    <span class="stat-label">Owner Payouts (90%)</span>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format((float)$financialSummary['total_payouts'], 2) ?></div>
                    <span class="stat-comparison">Paid to Vehicle Owners</span>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--warning);">
                    <span class="stat-label">Completed Bookings</span>
                    <div class="stat-value"><?= number_format((int)$financialSummary['total_bookings']) ?></div>
                    <span class="stat-comparison">Finished Reservations</span>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <!-- Booking Status Breakdown -->
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Bookings Status Breakdown</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Total Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusBreakdown as $sb): ?>
                                    <tr>
                                        <td><span class="badge badge-blue"><?= $escape(str_replace('_', ' ', ucfirst($sb['status']))) ?></span></td>
                                        <td><span class="cell-primary-text"><?= number_format((int)$sb['count']) ?> Bookings</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vehicle Category Popularity -->
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Vehicle Category Popularity</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Bookings Count</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryPopularity as $cp): ?>
                                    <tr>
                                        <td><span class="cell-primary-text"><?= $escape(ucfirst($cp['vehicle_type'])) ?></span></td>
                                        <td><?= number_format((int)$cp['booking_count']) ?></td>
                                        <td><span class="cell-primary-text">Rs. <?= number_format((float)$cp['total_spent'], 2) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
