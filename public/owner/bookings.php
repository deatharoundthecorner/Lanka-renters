<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerBookingController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$bookingController = new OwnerBookingController();
$actionError = null;
$actionSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!AuthHelper::validateCsrfToken($submittedCsrf)) {
        $actionError = 'Invalid or expired session security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            $actionError = 'Invalid booking identifier specified.';
        } elseif ($action === 'approve') {
            $result = $bookingController->approveBooking($bookingId);
            if (!empty($result['success'])) {
                $actionSuccess = $result['message'] ?? 'Booking approved successfully.';
            } else {
                $actionError = $result['error'] ?? 'Failed to approve booking.';
            }
        } elseif ($action === 'reject') {
            $result = $bookingController->rejectBooking($bookingId);
            if (!empty($result['success'])) {
                $actionSuccess = $result['message'] ?? 'Booking rejected successfully.';
            } else {
                $actionError = $result['error'] ?? 'Failed to reject booking.';
            }
        } elseif ($action === 'handover') {
            $result = $bookingController->handoverVehicle($bookingId);
            if (!empty($result['success'])) {
                $actionSuccess = $result['message'] ?? 'Vehicle handed over successfully.';
            } else {
                $actionError = $result['error'] ?? 'Failed to handover vehicle.';
            }
        } elseif ($action === 'complete_rental') {
            $result = $bookingController->completeRental($bookingId);
            if (!empty($result['success'])) {
                $actionSuccess = $result['message'] ?? 'Vehicle return completed successfully.';
            } else {
                $actionError = $result['error'] ?? 'Failed to complete vehicle return.';
            }
        }
    }
}

$dataResult = $bookingController->getOwnerBookings();
$bookings   = $dataResult['success'] ? $dataResult['bookings'] : [];
$summary    = $bookingController->getBookingSummary();
$csrfToken  = AuthHelper::getCsrfToken();

// Status display map
$statusMap = [
    'pending_approval' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Pending Approval'],
    'pending_payment'  => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'label' => 'Pending Payment'],
    'confirmed'        => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Confirmed'],
    'ongoing'          => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Ongoing'],
    'completed'        => ['bg' => '#f0fdf4', 'color' => '#166534', 'label' => 'Completed'],
    'cancelled'        => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Cancelled'],
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
  <link rel="stylesheet" href="../assets/css/lr-tokens.css">
  <link rel="stylesheet" href="../assets/css/lr-shell.css">
  <link rel="stylesheet" href="../assets/css/lr-components.css">
  <link rel="stylesheet" href="includes/assets/css/owner-canonical.css">
</head>
<body>

<div class="dashboard-layout dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-wrapper">
            <?php include 'includes/header.php'; ?>

            <main class="main-content">
            <!-- Page Header -->
      <section class="booking-header welcome-container">
                <div class="booking-title">
                    <h1>Booking Management</h1>
                    <p>Track all bookings across your vehicle fleet.</p>
                </div>
            </section>

            <!-- Booking Summary Stats -->
            <?php if (!empty($summary)): ?>
            <div class="stats-grid" style="margin-bottom: 28px;">
                <article class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <p class="stat-label">Total Bookings</p>
                            <p class="stat-value"><?php echo (int)($summary['total'] ?? 0); ?></p>
                        </div>
                        <div class="stat-icon-bubble bubble-blue">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                    </div>
                    <p class="stat-meta">All time total</p>
                </article>

                <article class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <p class="stat-label">Confirmed</p>
                            <p class="stat-value"><?php echo (int)($summary['confirmed'] ?? 0); ?></p>
                        </div>
                        <div class="stat-icon-bubble bubble-yellow">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                    <p class="stat-meta">Awaiting pickup</p>
                </article>

                <article class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <p class="stat-label">Ongoing</p>
                            <p class="stat-value"><?php echo (int)($summary['ongoing'] ?? 0); ?></p>
                        </div>
                        <div class="stat-icon-bubble bubble-teal">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                        </div>
                    </div>
                    <p class="stat-meta">Currently active</p>
                </article>

                <article class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <p class="stat-label">Completed</p>
                            <p class="stat-value"><?php echo (int)($summary['completed'] ?? 0); ?></p>
                        </div>
                        <div class="stat-icon-bubble bubble-green">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                    <p class="stat-meta">Finished rentals</p>
                </article>
            </div>
            <?php endif; ?>

            <!-- Alert Notifications -->
            <?php if (!empty($actionError)): ?>
                <div style="background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
                    <?php echo htmlspecialchars($actionError); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($actionSuccess)): ?>
                <div style="background-color: #d1fae5; border: 1px solid #34d399; color: #065f46; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
                    <?php echo htmlspecialchars($actionSuccess); ?>
                </div>
            <?php endif; ?>

            <!-- Bookings Table -->
            <section class="booking-table-section" aria-labelledby="bookings-table-heading">
                <div class="form-card" style="padding: 0; overflow: hidden;">
                    <div class="form-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; border-radius: 0;">
                        <h2 id="bookings-table-heading" style="margin:0; font-size: 1.1rem;">All Bookings</h2>
                    </div>

                    <?php if (empty($bookings)): ?>
                        <div class="empty-state-card" style="margin: 24px;">
                            <h3>No Bookings Yet</h3>
                            <p>Once customers book your vehicles, their reservations and current statuses will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
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
                                        <th scope="col">Actions</th>
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
                                            <td>
                                                <?php if ($bk['status'] === 'pending_approval'): ?>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <form method="POST" action="" style="display: inline; margin: 0;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="booking_id" value="<?php echo (int)$bk['id']; ?>">
                                                            <button type="submit" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600; background-color: #059669; border: none; color: #ffffff; cursor: pointer; border-radius: 6px;">Approve</button>
                                                        </form>
                                                        <form method="POST" action="" style="display: inline; margin: 0;" onsubmit="return confirm('Are you sure you want to reject this booking?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="booking_id" value="<?php echo (int)$bk['id']; ?>">
                                                            <button type="submit" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600; background-color: #dc2626; border: none; color: #ffffff; cursor: pointer; border-radius: 6px;">Reject</button>
                                                        </form>
                                                    </div>
                                                <?php elseif ($bk['status'] === 'confirmed'): ?>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <?php if (($bk['pre_rental_inspection_status'] ?? '') === 'pass'): ?>
                                                            <form method="POST" action="" style="display: inline; margin: 0;" onsubmit="return confirm('Hand over vehicle to customer? This will activate the rental.');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="handover">
                                                                <input type="hidden" name="booking_id" value="<?php echo (int)$bk['id']; ?>">
                                                                <button type="submit" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600; background-color: #1e3a8a; border: none; color: #ffffff; cursor: pointer; border-radius: 6px;">Handover Vehicle</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <a href="inspection.php?booking_id=<?php echo (int)$bk['id']; ?>" style="display: inline-block; padding: 6px 14px; font-size: 0.8rem; font-weight: 600; background-color: #f59e0b; border: none; color: #ffffff; text-decoration: none; border-radius: 6px;">
                                                                Record Inspection
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($bk['status'] === 'ongoing'): ?>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <?php if (!empty($bk['return_request_id'])): ?>
                                                            <div style="font-size:0.8rem; color:#334155;">
                                                                <strong>Customer return request</strong><br>
                                                                Proposed: <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($bk['proposed_return_at']))); ?>
                                                                <?php if (!empty($bk['return_customer_note'])): ?><br>Note: <?php echo htmlspecialchars($bk['return_customer_note']); ?><?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($bk['post_rental_inspection_status'])): ?>
                                                            <form method="POST" action="" style="display: inline; margin: 0;" onsubmit="return confirm('Complete vehicle check-in and mark booking #<?php echo (int)$bk['id']; ?> as completed?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="complete_rental">
                                                                <input type="hidden" name="booking_id" value="<?php echo (int)$bk['id']; ?>">
                                                                <button type="submit" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600; background-color: #059669; border: none; color: #ffffff; cursor: pointer; border-radius: 6px;">Complete Return</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <a href="inspection.php?booking_id=<?php echo (int)$bk['id']; ?>&type=post_rental" style="display: inline-block; padding: 6px 14px; font-size: 0.8rem; font-weight: 600; background-color: #f59e0b; border: none; color: #ffffff; text-decoration: none; border-radius: 6px;">
                                                                Record Post-Rental Inspection
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: #94a3b8; font-size: 0.85rem;">—</span>
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
</div>

</body>
</html>
