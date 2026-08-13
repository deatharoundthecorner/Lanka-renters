<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerBookingController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$bookingController = new OwnerBookingController();
$dataResult        = $bookingController->getOwnerBookings();
$bookings          = $dataResult['success'] ? $dataResult['bookings'] : [];
$summary           = $bookingController->getBookingSummary();

// Status display map
$statusMap = [
    'pending_payment' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Pending Payment'],
    'confirmed'       => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Confirmed'],
    'ongoing'         => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Ongoing'],
    'completed'       => ['bg' => '#f0fdf4', 'color' => '#166534', 'label' => 'Completed'],
    'cancelled'       => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Cancelled'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - LankaRenters</title>
    <meta name="description" content="View and manage all bookings for your vehicle fleet on LankaRenters.">
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Page Header -->
            <section class="booking-header">
                <div class="booking-title">
                    <h1>Booking Management</h1>
                    <p>Track all bookings across your vehicle fleet.</p>
                </div>
            </section>

            <!-- Booking Summary Stats -->
            <?php if (!empty($summary)): ?>
            <div class="stats-grid" style="margin-bottom: 24px;">
                <article class="stat-card">
                    <p class="stat-label">Total Bookings</p>
                    <p class="stat-value"><?php echo (int)($summary['total'] ?? 0); ?></p>
                    <p class="stat-meta">All time</p>
                </article>
                <article class="stat-card">
                    <p class="stat-label">Confirmed</p>
                    <p class="stat-value"><?php echo (int)($summary['confirmed'] ?? 0); ?></p>
                    <p class="stat-meta">Awaiting pickup</p>
                </article>
                <article class="stat-card">
                    <p class="stat-label">Ongoing</p>
                    <p class="stat-value"><?php echo (int)($summary['ongoing'] ?? 0); ?></p>
                    <p class="stat-meta">Currently active</p>
                </article>
                <article class="stat-card">
                    <p class="stat-label">Completed</p>
                    <p class="stat-value"><?php echo (int)($summary['completed'] ?? 0); ?></p>
                    <p class="stat-meta">Finished rentals</p>
                </article>
            </div>
            <?php endif; ?>

            <!-- Bookings Table -->
            <section class="booking-table-section" aria-labelledby="bookings-table-heading">
                <div class="form-card" style="padding: 0; overflow: hidden;">
                    <div class="form-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; border-radius: 0;">
                        <h2 id="bookings-table-heading" style="margin:0; font-size: 1.1rem;">All Bookings</h2>
                    </div>

                    <?php if (empty($bookings)): ?>
                        <div style="text-align: center; padding: 48px 24px; color: #64748b;">
                            <div style="font-size: 3rem; margin-bottom: 12px;">📋</div>
                            <p style="font-size: 1.1rem; font-weight: 600; color: #0f172a; margin: 0 0 8px;">No bookings yet</p>
                            <p style="margin: 0;">Once customers book your vehicles, they'll appear here.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="booking-table" style="width: 100%;">
                                <caption class="sr-only">Owner booking records</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Booking #</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Vehicle</th>
                                        <th scope="col">Dates</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $bk): ?>
                                        <?php
                                            $sc = $statusMap[$bk['status']] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => ucfirst($bk['status'])];
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo (int)$bk['id']; ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($bk['customer_name']); ?></div>
                                                <div style="font-size: 0.82rem; color: #64748b;"><?php echo htmlspecialchars($bk['customer_phone']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($bk['make'] . ' ' . $bk['model']); ?></div>
                                                <div style="font-size: 0.82rem; font-family: monospace; color: #475569;"><?php echo htmlspecialchars($bk['license_plate']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo date('d M Y', strtotime($bk['start_date'])); ?></div>
                                                <div style="font-size: 0.82rem; color: #64748b;">to <?php echo date('d M Y', strtotime($bk['end_date'])); ?></div>
                                            </td>
                                            <td>
                                                <strong>LKR <?php echo number_format($bk['total_price'], 0); ?></strong>
                                            </td>
                                            <td>
                                                <span style="font-size: 0.82rem; text-transform: capitalize;">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', $bk['booking_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; background-color: <?php echo $sc['bg']; ?>; color: <?php echo $sc['color']; ?>;">
                                                    <?php echo $sc['label']; ?>
                                                </span>
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