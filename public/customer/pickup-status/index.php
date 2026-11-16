<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';

$viewData = (new CustomerPortalController())->pickupStatusPage(
    $_POST,
    strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
);
if (isset($viewData['redirect'])) {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}

$pickupStages = [
    ['key' => 'owner_handover', 'label' => 'Step 1: Owner handed vehicle to driver'],
    ['key' => 'driver_vehicle', 'label' => 'Step 2: Driver picked up vehicle'],
    ['key' => 'driver_on_way', 'label' => 'Step 3: Driver on the way'],
    ['key' => 'driver_customer', 'label' => 'Step 4: Driver picked up customer'],
    ['key' => 'customer_confirmed', 'label' => 'Step 5: Customer confirmed pickup'],
    ['key' => 'rental_started', 'label' => 'Step 6: Rental started'],
];

$stageState = static function (string $pickupStatus, string $stage): string {
    if ($pickupStatus === 'dropped_off') {
        return 'complete';
    }
    $complete = [
        'owner_handover' => true,
        'driver_vehicle' => in_array($pickupStatus, ['dispatched', 'arrived', 'picked_up', 'customer_confirmed_pickup', 'rental_started'], true),
        'driver_on_way' => in_array($pickupStatus, ['arrived', 'picked_up', 'customer_confirmed_pickup', 'rental_started'], true),
        'driver_customer' => in_array($pickupStatus, ['picked_up', 'customer_confirmed_pickup', 'rental_started'], true),
        'customer_confirmed' => in_array($pickupStatus, ['customer_confirmed_pickup', 'rental_started'], true),
        'rental_started' => $pickupStatus === 'rental_started',
    ];
    if ($complete[$stage]) {
        return 'complete';
    }
    $currentByPickupStatus = [
        'pending_pickup' => 'driver_vehicle',
        'dispatched' => 'driver_on_way',
        'arrived' => 'driver_customer',
        'picked_up' => 'customer_confirmed',
        'customer_confirmed_pickup' => 'rental_started',
    ];
    return ($currentByPickupStatus[$pickupStatus] ?? '') === $stage ? 'current' : 'upcoming';
};

require dirname(__DIR__) . '/components/layout/feature-start.php';
?>

<section class="pickup-status-page">
    <?php if ($viewData['database_error']): ?>
        <section class="empty-state"><span class="empty-state__icon" aria-hidden="true"><?= customer_icon('alert') ?></span><h2>Pickup status is temporarily unavailable</h2><p>Please try again later.</p></section>
    <?php elseif ($viewData['bookings'] === []): ?>
        <section class="empty-state"><span class="empty-state__icon" aria-hidden="true"><?= customer_icon('clipboard') ?></span><h2>No active with-driver pickups</h2><p>Confirmed and ongoing bookings with an assigned driver will appear here.</p><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">View bookings</a></section>
    <?php else: ?>
        <?php foreach ($viewData['errors'] as $error): ?><p class="field-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>
        <?php foreach ($viewData['bookings'] as $booking): ?>
            <?php $pickupStatus = (string) $booking['pickup_status']; ?>
            <section class="pickup-status-card" aria-labelledby="pickup-booking-<?= (int) $booking['id'] ?>">
                <div class="pickup-status-card__heading">
                    <div><p class="eyebrow">Booking #<?= (int) $booking['id'] ?></p><h2 id="pickup-booking-<?= (int) $booking['id'] ?>">Pickup Confirmation (With Driver)</h2></div>
                    <span class="status-badge <?= $pickupStatus === 'rental_started' ? 'status-badge--success' : 'status-badge--info' ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $pickupStatus)), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p class="pickup-status-card__meta"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model'] . ' · ' . $booking['license_plate'] . ' · Driver: ' . $booking['driver_name'], ENT_QUOTES, 'UTF-8') ?></p>

                <ol class="pickup-timeline">
                    <?php foreach ($pickupStages as $stage): ?>
                        <?php $state = $stageState($pickupStatus, $stage['key']); ?>
                        <li class="pickup-timeline__item pickup-timeline__item--<?= $state ?>">
                            <span class="pickup-timeline__marker" aria-hidden="true"><?= $state === 'complete' ? '&#10003;' : '' ?></span>
                            <div><strong><?= htmlspecialchars($stage['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ($state === 'current'): ?><span class="pickup-timeline__pending">Pending confirmation</span><?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <?php if ($pickupStatus === 'picked_up'): ?>
                    <form method="post" class="pickup-status-card__action" data-submit-once>
                        <?= CustomerCsrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                        <input type="hidden" name="action" value="confirm_pickup">
                        <p>Confirm that the driver has picked you up to continue to rental start.</p>
                        <button class="button button--primary" type="submit">Confirm Pickup</button>
                    </form>
                <?php elseif ($pickupStatus === 'customer_confirmed_pickup'): ?>
                    <form method="post" class="pickup-status-card__action" data-submit-once>
                        <?= CustomerCsrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                        <input type="hidden" name="action" value="start_rental">
                        <p>Pickup is confirmed. Start the rental when your journey begins.</p>
                        <button class="button button--primary" type="submit">Start Rental</button>
                    </form>
                <?php elseif ($pickupStatus === 'rental_started'): ?>
                    <p class="pickup-status-card__complete">Your rental is active. Contact support or report an incident if you need help during the journey.</p>
                <?php else: ?>
                    <p class="pickup-status-card__waiting">The next driver handover update will appear here automatically. You can confirm pickup once the driver has marked you as picked up.</p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
