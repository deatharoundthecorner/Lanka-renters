<section class="card booking-actions" aria-labelledby="booking-actions-title">
    <div class="card__header">
        <h2 id="booking-actions-title">Available actions</h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('calendar') ?></span>
    </div>
    <?php if (!empty($booking['_can_edit'])): ?>
        <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/edit.php?id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Edit booking</a>
    <?php endif; ?>

    <?php if (!empty($booking['_can_cancel'])): ?>
        <form class="booking-cancel-form" method="post" action="<?= htmlspecialchars(customer_url('bookings/cancel.php'), ENT_QUOTES, 'UTF-8') ?>">
            <?= CustomerCsrf::field() ?>
            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
            <label class="booking-confirmation">
                <input type="checkbox" name="confirm_cancel" value="yes" required>
                <span>I understand this will cancel the booking while preserving its history.</span>
            </label>
            <button class="button button--danger" type="submit">Cancel booking</button>
        </form>
    <?php endif; ?>

    <?php if (($booking['status'] ?? '') === 'pending_payment'): ?>
        <a class="button button--primary" href="<?= htmlspecialchars(customer_url('payments/create.php?booking_id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Submit or View Payment</a>
    <?php endif; ?>
    <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('payments/summary.php?booking_id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Payment Summary</a>
    <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/inspection.php?id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">View Inspection</a>
    <?php if (in_array(($booking['status'] ?? ''), ['pending_payment', 'confirmed', 'ongoing'], true)): ?>
        <form method="post" action="<?= htmlspecialchars(customer_url('chat/index.php'), ENT_QUOTES, 'UTF-8') ?>">
            <?= CustomerCsrf::field() ?><input type="hidden" name="action" value="start"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><button class="button button--secondary" type="submit">Open Booking Chat</button>
        </form>
    <?php endif; ?>
    <?php if (($booking['status'] ?? '') === 'ongoing'): ?>
        <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('incidents/create.php?booking_id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Report Incident</a>
        <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('returns/create.php?booking_id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Start Demo Return</a>
    <?php endif; ?>
    <?php if (($booking['booking_type'] ?? '') === 'with_driver' && in_array(($booking['status'] ?? ''), ['confirmed', 'ongoing'], true)): ?>
        <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('driver-change/create.php?booking_id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Request Driver Change</a>
    <?php endif; ?>
    <?php if (($booking['status'] ?? '') === 'completed'): ?>
        <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('reviews/create.php?booking_id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>">Write Review</a>
    <?php endif; ?>

    <p>Every action is rechecked against the authenticated Customer and current database status.</p>
</section>
