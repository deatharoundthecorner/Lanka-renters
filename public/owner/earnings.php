<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerEarningsController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$earningsController = new OwnerEarningsController();
$data               = $earningsController->getEarningsSummary();
$totalCompleted     = $data['total_completed']    ?? 0;
$totalPending       = $data['total_pending']      ?? 0;
$vehicleBreakdown   = $data['vehicle_breakdown']  ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - LankaRenters</title>
    <meta name="description" content="Track your vehicle rental income, deductions and settlements on LankaRenters.">
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Page Header -->
            <section class="earnings-header">
                <div class="earnings-title">
                    <h1>Earnings</h1>
                    <p>Track income from your vehicle fleet.</p>
                </div>
            </section>

            <!-- Earnings Summary Stats -->
            <section class="earnings-stats" aria-label="Earnings summary">
                <div class="stats-grid" style="margin-bottom: 28px; grid-template-columns: repeat(3, minmax(0, 1fr));">
                    <article class="stat-card">
                        <p class="stat-label">Completed Earnings</p>
                        <p class="stat-value">LKR <?php echo number_format($totalCompleted, 0); ?></p>
                        <p class="stat-meta">From settled bookings</p>
                    </article>

                    <article class="stat-card">
                        <p class="stat-label">Pending Earnings</p>
                        <p class="stat-value">LKR <?php echo number_format($totalPending, 0); ?></p>
                        <p class="stat-meta">Awaiting booking completion</p>
                    </article>

                    <article class="stat-card">
                        <p class="stat-label">Total Gross</p>
                        <p class="stat-value">LKR <?php echo number_format($totalCompleted + $totalPending, 0); ?></p>
                        <p class="stat-meta">All time combined</p>
                    </article>
                </div>
            </section>

            <!-- Vehicle-wise Breakdown -->
            <section class="vehicle-income" aria-labelledby="vehicle-income-heading">
                <div class="form-card" style="padding: 0; overflow: hidden;">
                    <div class="form-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; border-radius: 0;">
                        <h2 id="vehicle-income-heading" style="margin: 0; font-size: 1.1rem;">Vehicle-wise Income</h2>
                        <p style="margin: 6px 0 0; color: #64748b; font-size: 0.9rem;">Earnings breakdown per vehicle (completed bookings only)</p>
                    </div>

                    <?php if (empty($vehicleBreakdown)): ?>
                        <div style="text-align: center; padding: 48px 24px; color: #64748b;">
                            <div style="font-size: 3rem; margin-bottom: 12px;">💰</div>
                            <p style="font-size: 1.1rem; font-weight: 600; color: #0f172a; margin: 0 0 8px;">No vehicles listed yet</p>
                            <p style="margin: 0;"><a href="vehicles.php#add-vehicle-form" style="color: #2563eb; font-weight: 600;">Add a vehicle</a> to start earning.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="booking-table" style="width: 100%;">
                                <caption class="sr-only">Vehicle earnings breakdown</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Vehicle</th>
                                        <th scope="col">License Plate</th>
                                        <th scope="col">Trips</th>
                                        <th scope="col">Earnings (LKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicleBreakdown as $veh): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($veh['make'] . ' ' . $veh['model']); ?></strong></td>
                                            <td><span style="font-family: monospace; font-size: 0.88rem; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;"><?php echo htmlspecialchars($veh['license_plate']); ?></span></td>
                                            <td><?php echo (int)$veh['trips']; ?></td>
                                            <td>
                                                <?php if ($veh['earned'] > 0): ?>
                                                    <strong style="color: #166534;">LKR <?php echo number_format($veh['earned'], 0); ?></strong>
                                                <?php else: ?>
                                                    <span style="color: #94a3b8;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

</body>
</html>