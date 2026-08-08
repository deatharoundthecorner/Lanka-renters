<?php

require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerBookingController.php';

$controller = new CustomerBookingController();
$viewData = $controller->detailsPage($_GET);
$pageTitle = $viewData['page_title'];
$booking = $viewData['booking'];

require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>
    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>
        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>
            <p><a class="text-link" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to booking history</a></p>

            <?php if (is_array($viewData['flash'])): ?>
                <?php $flashTone = $viewData['flash']['tone'] === 'success' ? 'success' : 'error'; ?>
                <div class="alert alert--<?= $flashTone ?>" role="status">
                    <?= customer_icon($flashTone === 'success' ? 'check' : 'alert') ?>
                    <p><?= htmlspecialchars((string) $viewData['flash']['message'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endif; ?>

            <?php if ($viewData['database_error']): ?>
                <section class="empty-state"><span class="empty-state__icon" aria-hidden="true"><?= customer_icon('alert') ?></span><h2>Booking details are temporarily unavailable</h2><p>Please try again later.</p></section>
            <?php elseif (!is_array($booking)): ?>
                <section class="empty-state" aria-labelledby="booking-not-found-title">
                    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('search') ?></span>
                    <h2 id="booking-not-found-title">Booking not found</h2>
                    <p>This booking does not exist or does not belong to your Customer account.</p>
                    <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">View my bookings</a>
                </section>
            <?php else: ?>
                <div class="booking-detail-layout">
                    <div class="booking-detail-layout__main">
                        <section class="card booking-detail-overview" aria-labelledby="booking-overview-title">
                            <div class="booking-detail-overview__header">
                                <div><p class="eyebrow">Booking #<?= (int) $booking['id'] ?></p><h2 id="booking-overview-title"><?= htmlspecialchars(trim((string) $booking['make'] . ' ' . (string) $booking['model']), ENT_QUOTES, 'UTF-8') ?></h2></div>
                                <?php $bookingStatus = (string) $booking['status']; require dirname(__DIR__) . '/components/booking/booking-status.php'; ?>
                            </div>
                            <dl class="booking-detail-list">
                                <div><dt>Booking type</dt><dd><?= htmlspecialchars($booking['booking_type'] === 'with_driver' ? 'With driver' : 'Self-drive', ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Rental period</dt><dd><?= htmlspecialchars(date('d M Y', strtotime((string) $booking['start_date'])) . ' – ' . date('d M Y', strtotime((string) $booking['end_date'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Rental duration</dt><dd><?= (int) $booking['rental_days'] ?> days</dd></div>
                                <div><dt>Total price</dt><dd>Rs. <?= number_format((float) $booking['total_price'], 2) ?></dd></div>
                                <div><dt>Pickup status</dt><dd><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $booking['pickup_status'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Licence plate</dt><dd><?= htmlspecialchars((string) $booking['license_plate'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Driver</dt><dd><?= htmlspecialchars($booking['driver_name'] !== null ? (string) $booking['driver_name'] : 'Not required', ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Vehicle owner</dt><dd><?= htmlspecialchars((string) $booking['owner_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div class="booking-detail-list__wide"><dt>Delivery address</dt><dd><?= htmlspecialchars(($booking['delivery_address'] ?? '') !== '' ? (string) $booking['delivery_address'] : 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd></div>
                            </dl>
                        </section>
                        <section class="card" aria-labelledby="booking-record-title">
                            <div class="card__header"><h2 id="booking-record-title">Record information</h2><span class="card__header-icon" aria-hidden="true"><?= customer_icon('clock') ?></span></div>
                            <dl class="booking-record-dates">
                                <div><dt>Created</dt><dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime((string) $booking['created_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Last updated</dt><dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime((string) $booking['updated_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                            </dl>
                            <p>No status-history table exists, so this page does not invent a booking timeline.</p>
                        </section>
                    </div>
                    <aside><?php require dirname(__DIR__) . '/components/booking/booking-actions.php'; ?></aside>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
