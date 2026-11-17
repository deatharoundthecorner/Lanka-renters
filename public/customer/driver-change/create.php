<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';

$viewData = (new CustomerPortalController())->driverChangeCreatePage($_GET, $_POST, strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')));
if (isset($viewData['redirect'])) {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}

$selectedBookingId = (int)($viewData['form']['booking_id'] ?? 0);
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<p><a class="text-link" href="<?= htmlspecialchars(customer_url('driver-change/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to requests</a></p>

<?php if ($viewData['database_error']): ?>
    <section class="empty-state">
        <h2>Eligibility unavailable</h2>
        <p>Please try again later.</p>
    </section>
<?php elseif ($viewData['eligible_bookings'] === []): ?>
    <section class="empty-state">
        <h2>No eligible booking</h2>
        <p>A confirmed or ongoing with-driver rental with an assigned driver is required to request a driver change.</p>
    </section>
<?php else: ?>
    <form class="card feature-form" method="post" action="<?= htmlspecialchars(customer_url('driver-change/create.php' . ($selectedBookingId > 0 ? '?booking_id=' . $selectedBookingId : '')), ENT_QUOTES, 'UTF-8') ?>" data-submit-once>
        <?= CustomerCsrf::field() ?>

        <?php if (isset($viewData['errors']['form'])): ?>
            <div class="alert alert--error" style="margin-bottom: 16px;">
                <p><?= htmlspecialchars($viewData['errors']['form'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <label for="driver-change-booking">Eligible with-driver booking</label>
        <select id="driver-change-booking" name="booking_id" required onchange="window.location.href='create.php?booking_id=' + this.value;">
            <option value="">Choose booking</option>
            <?php foreach ($viewData['eligible_bookings'] as $booking): ?>
                <option value="<?= (int)$booking['id'] ?>" <?= (string)$booking['id'] === $viewData['form']['booking_id'] ? 'selected' : '' ?>>
                    #<?= (int)$booking['id'] ?> — <?= htmlspecialchars($booking['make'] . ' ' . $booking['model'] . ' (' . $booking['license_plate'] . ')', ENT_QUOTES, 'UTF-8') ?> [<?= htmlspecialchars(ucfirst($booking['status']), ENT_QUOTES, 'UTF-8') ?>]
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($viewData['errors']['booking_id'])): ?>
            <p class="field-error"><?= htmlspecialchars($viewData['errors']['booking_id'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($selectedBookingId > 0): ?>
            <label for="driver-change-driver">Requested replacement driver</label>
            <?php if (empty($viewData['available_drivers'])): ?>
                <div class="alert alert--warning" style="margin-bottom: 16px;">
                    <p>No other replacement drivers from this fleet are currently eligible and available for your rental schedule.</p>
                </div>
            <?php else: ?>
                <select id="driver-change-driver" name="requested_driver_id" required>
                    <option value="">Choose replacement driver</option>
                    <?php foreach ($viewData['available_drivers'] as $drv): ?>
                        <option value="<?= (int)$drv['id'] ?>" <?= (string)$drv['id'] === $viewData['form']['requested_driver_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($drv['name'], ENT_QUOTES, 'UTF-8') ?> (Rating: <?= number_format((float)($drv['rating_avg'] ?? 0), 1) ?> &star;)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($viewData['errors']['requested_driver_id'])): ?>
                    <p class="field-error"><?= htmlspecialchars($viewData['errors']['requested_driver_id'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <p class="form-hint" style="margin-bottom: 16px;">Select a booking above to view eligible replacement drivers from the vehicle's fleet.</p>
        <?php endif; ?>

        <label for="driver-change-reason">Reason for driver change request</label>
        <textarea id="driver-change-reason" name="reason" minlength="10" maxlength="500" required data-character-input placeholder="Please describe why you are requesting a change of driver..."><?= htmlspecialchars($viewData['form']['reason'], ENT_QUOTES, 'UTF-8') ?></textarea>
        <span class="form-hint" data-character-count>0 / 500</span>
        <?php if (isset($viewData['errors']['reason'])): ?>
            <p class="field-error"><?= htmlspecialchars($viewData['errors']['reason'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <button class="button button--primary" type="submit" <?= ($selectedBookingId > 0 && empty($viewData['available_drivers'])) ? 'disabled' : '' ?>>Submit Request</button>
    </form>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
