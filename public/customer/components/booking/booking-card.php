<?php
$bookingId = (int) ($booking['id'] ?? 0);
$bookingStatus = (string) ($booking['status'] ?? 'pending_payment');
$vehicleName = trim((string) ($booking['make'] ?? '') . ' ' . (string) ($booking['model'] ?? ''));
?>
<article class="booking-card">
    <div class="booking-card__header">
        <div>
            <p class="eyebrow">Booking #<?= $bookingId ?></p>
            <h2><?= htmlspecialchars($vehicleName !== '' ? $vehicleName : 'Vehicle booking', ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
        <?php require __DIR__ . '/booking-status.php'; ?>
    </div>
    <dl class="booking-card__facts">
        <div><dt>Service</dt><dd><?= htmlspecialchars($booking['booking_type'] === 'with_driver' ? 'With driver' : 'Self-drive', ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Rental dates</dt><dd><?= htmlspecialchars(date('d M Y', strtotime((string) $booking['start_date'])) . ' – ' . date('d M Y', strtotime((string) $booking['end_date'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Duration</dt><dd><?= (int) ($booking['rental_days'] ?? 0) ?> days</dd></div>
        <div><dt>Total</dt><dd>Rs. <?= number_format((float) ($booking['total_price'] ?? 0), 2) ?></dd></div>
    </dl>
    <?php if (($booking['driver_name'] ?? null) !== null): ?>
        <p class="booking-card__driver">Driver: <?= htmlspecialchars((string) $booking['driver_name'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <div class="booking-card__footer">
        <span>Created <?= htmlspecialchars(date('d M Y', strtotime((string) $booking['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
        <a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url('bookings/details.php?id=' . $bookingId), ENT_QUOTES, 'UTF-8') ?>">View details</a>
    </div>
</article>
