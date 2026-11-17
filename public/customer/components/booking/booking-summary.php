<?php
$summaryVehicleName = trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
$summaryImagePath = is_string($vehicle['image_url'] ?? null) && trim((string) $vehicle['image_url']) !== ''
    ? ltrim((string) $vehicle['image_url'], '/')
    : 'assets/images/car.svg';
?>
<section class="card booking-vehicle-summary" aria-labelledby="booking-vehicle-title">
    <div class="card__header">
        <h2 id="booking-vehicle-title">Selected vehicle</h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('car') ?></span>
    </div>
    <img class="booking-vehicle-summary__image" src="<?= htmlspecialchars(customer_url('../' . $summaryImagePath), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($summaryVehicleName !== '' ? $summaryVehicleName : 'Vehicle', ENT_QUOTES, 'UTF-8') ?>">
    <h3><?= htmlspecialchars($summaryVehicleName !== '' ? $summaryVehicleName : 'Vehicle', ENT_QUOTES, 'UTF-8') ?></h3>
    <ul class="booking-vehicle-summary__facts">
        <li><?= htmlspecialchars((string) ($vehicle['year'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><?= htmlspecialchars(ucfirst((string) ($vehicle['transmission'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></li>
        <li><?= htmlspecialchars(ucfirst((string) ($vehicle['fuel_type'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></li>
        <li><?= (int) ($vehicle['seating_capacity'] ?? 0) ?> seats</li>
    </ul>
    <dl class="booking-vehicle-summary__details">
        <div><dt>Location</dt><dd><?= htmlspecialchars((string) ($vehicle['location'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Monthly rental</dt><dd><?= ($vehicle['monthly_rental_price'] ?? null) !== null ? 'LKR ' . number_format((float) $vehicle['monthly_rental_price'], 2) : 'Not specified' ?></dd></div>
        <div><dt>Refundable key money</dt><dd><?= ($vehicle['refundable_key_money'] ?? null) !== null ? 'LKR ' . number_format((float) $vehicle['refundable_key_money'], 2) : 'Not specified' ?></dd></div>
        <div><dt>Mileage limit</dt><dd><?= !empty($vehicle['mileage_is_unlimited']) ? 'Unlimited' : (($vehicle['mileage_limit_km'] ?? null) !== null ? number_format((int) $vehicle['mileage_limit_km']) . ' km / month' : 'Not specified') ?></dd></div>
        <div><dt>Available services</dt><dd><?= ($vehicle['price_with_driver_per_day'] ?? null) !== null ? 'Self Drive, With Driver' : 'Self Drive' ?></dd></div>
    </dl>
    <div class="booking-vehicle-summary__prices">
        <p><strong>LKR <?= number_format((float) ($vehicle['price_per_day'] ?? 0), 2) ?></strong><span>/ day self-drive</span></p>
        <?php if (($vehicle['price_with_driver_per_day'] ?? null) !== null): ?>
            <p><strong>LKR <?= number_format((float) $vehicle['price_with_driver_per_day'], 2) ?></strong><span>/ day with driver</span></p>
        <?php endif; ?>
    </div>
</section>
