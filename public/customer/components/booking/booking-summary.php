<?php
$summaryVehicleName = trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
?>
<section class="card booking-vehicle-summary" aria-labelledby="booking-vehicle-title">
    <div class="card__header">
        <h2 id="booking-vehicle-title">Selected vehicle</h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('car') ?></span>
    </div>
    <h3><?= htmlspecialchars($summaryVehicleName !== '' ? $summaryVehicleName : 'Vehicle', ENT_QUOTES, 'UTF-8') ?></h3>
    <ul class="booking-vehicle-summary__facts">
        <li><?= htmlspecialchars((string) ($vehicle['year'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><?= htmlspecialchars(ucfirst((string) ($vehicle['transmission'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></li>
        <li><?= htmlspecialchars(ucfirst((string) ($vehicle['fuel_type'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></li>
        <li><?= (int) ($vehicle['seating_capacity'] ?? 0) ?> seats</li>
    </ul>
    <div class="booking-vehicle-summary__prices">
        <p><strong>Rs. <?= number_format((float) ($vehicle['price_per_day'] ?? 0), 2) ?></strong><span>/ day self-drive</span></p>
        <?php if (($vehicle['price_with_driver_per_day'] ?? null) !== null): ?>
            <p><strong>Rs. <?= number_format((float) $vehicle['price_with_driver_per_day'], 2) ?></strong><span>/ day with driver</span></p>
        <?php endif; ?>
    </div>
</section>
