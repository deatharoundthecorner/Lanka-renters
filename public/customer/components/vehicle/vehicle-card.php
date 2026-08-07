<?php
$vehicleId = (int) ($vehicle['id'] ?? 0);
$vehicleName = trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
$vehicleName = $vehicleName !== '' ? $vehicleName : 'Vehicle';
$vehicleStatus = (string) ($vehicle['status'] ?? 'unavailable');
?>
<article class="vehicle-card">
    <div class="vehicle-card__visual" aria-hidden="true">
        <?= customer_icon('car') ?>
        <span>Vehicle photo not available</span>
    </div>
    <div class="vehicle-card__body">
        <div class="vehicle-card__heading">
            <div>
                <p class="eyebrow"><?= htmlspecialchars(ucfirst((string) ($vehicle['vehicle_type'] ?? 'vehicle')), ENT_QUOTES, 'UTF-8') ?></p>
                <h2><?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <?php require __DIR__ . '/availability-badge.php'; ?>
        </div>

        <ul class="vehicle-card__meta" aria-label="Vehicle features">
            <li><?= htmlspecialchars((string) ($vehicle['year'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= htmlspecialchars(ucfirst((string) ($vehicle['transmission'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= htmlspecialchars(ucfirst((string) ($vehicle['fuel_type'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= (int) ($vehicle['seating_capacity'] ?? 0) ?> seats</li>
        </ul>

        <div class="vehicle-card__price-row">
            <p class="vehicle-price"><strong>Rs. <?= number_format((float) ($vehicle['price_per_day'] ?? 0), 2) ?></strong><span>/ day self-drive</span></p>
            <?php if (($vehicle['price_with_driver_per_day'] ?? null) !== null): ?>
                <p class="vehicle-price vehicle-price--secondary"><strong>Rs. <?= number_format((float) $vehicle['price_with_driver_per_day'], 2) ?></strong><span>/ day with driver</span></p>
            <?php endif; ?>
        </div>

        <div class="vehicle-card__footer">
            <span class="status-badge status-badge--success"><?= customer_icon('check') ?> Approved listing</span>
            <a class="button button--primary button--small" href="<?= htmlspecialchars(customer_url('vehicles/details.php?id=' . $vehicleId), ENT_QUOTES, 'UTF-8') ?>">
                View details <?= customer_icon('arrow-right') ?>
            </a>
        </div>
    </div>
</article>
