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

    <?php if (empty($booking['_can_edit']) && empty($booking['_can_cancel'])): ?>
        <p>No Customer actions are available for this booking in its current status or rental period.</p>
    <?php else: ?>
        <p>Editing and cancellation are rechecked securely when submitted.</p>
    <?php endif; ?>
</section>
