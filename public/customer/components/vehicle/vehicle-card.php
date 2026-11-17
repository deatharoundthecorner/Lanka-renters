<?php
$vehicleId = (int) ($vehicle['id'] ?? 0);
$vehicleName = trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
$vehicleName = $vehicleName !== '' ? $vehicleName : 'Vehicle';
$vehicleStatus = (string) ($vehicle['status'] ?? 'unavailable');
$detailQuery = isset($catalogueDetailQuery) && is_string($catalogueDetailQuery) ? $catalogueDetailQuery : '';
$detailsPath = 'vehicles/details.php?id=' . $vehicleId . ($detailQuery !== '' ? '&' . $detailQuery : '');
$reviewCount = max(0, (int) ($vehicle['review_count'] ?? 0));
$averageRating = max(0, min(5, (float) ($vehicle['average_rating'] ?? 0)));
$filledStars = $reviewCount > 0 ? (int) round($averageRating) : 0;
?>
<article class="vehicle-card">
    <div class="vehicle-card__visual" aria-hidden="true">
        <?php if (is_string($vehicle['image_url'] ?? null) && trim($vehicle['image_url']) !== ''): ?>
            <img src="<?= htmlspecialchars(customer_url('../' . ltrim((string) $vehicle['image_url'], '/')), ENT_QUOTES, 'UTF-8') ?>" alt="">
        <?php else: ?>
            <?= customer_icon('car') ?>
            <span>Vehicle photo not available</span>
        <?php endif; ?>
    </div>
    <div class="vehicle-card__body">
        <div class="vehicle-card__heading">
            <div>
                <p class="eyebrow"><?= htmlspecialchars(ucfirst((string) ($vehicle['vehicle_type'] ?? 'vehicle')), ENT_QUOTES, 'UTF-8') ?></p>
                <h2><?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if ($reviewCount > 0): ?>
                    <p class="vehicle-card__rating" aria-label="Rated <?= number_format($averageRating, 1) ?> out of 5 from <?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>">
                        <span aria-hidden="true"><?php for ($star = 1; $star <= 5; $star++): ?><span class="<?= $star <= $filledStars ? 'is-filled' : '' ?>">&#9733;</span><?php endfor; ?></span>
                        <strong><?= number_format($averageRating, 1) ?></strong><small>(<?= $reviewCount ?>)</small>
                    </p>
                <?php else: ?>
                    <p class="vehicle-card__rating vehicle-card__rating--empty"><span aria-hidden="true">&#9734;</span> No reviews yet</p>
                <?php endif; ?>
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
            <p class="vehicle-price"><strong>LKR <?= number_format((float) ($vehicle['price_per_day'] ?? 0), 2) ?></strong><span>/ day self-drive</span></p>
            <?php if (($vehicle['price_with_driver_per_day'] ?? null) !== null): ?>
                <p class="vehicle-price vehicle-price--secondary"><strong>LKR <?= number_format((float) $vehicle['price_with_driver_per_day'], 2) ?></strong><span>/ day with driver</span></p>
            <?php endif; ?>
        </div>

        <div class="vehicle-card__footer">
            <span class="status-badge status-badge--success"><?= customer_icon('check') ?> Approved listing</span>
            <div class="vehicle-card__actions">
                <a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url($detailsPath), ENT_QUOTES, 'UTF-8') ?>">View details</a>
                <a class="button button--primary button--small" href="<?= htmlspecialchars(customer_url('bookings/create.php?vehicle_id=' . $vehicleId), ENT_QUOTES, 'UTF-8') ?>">Create Booking</a>
            </div>
        </div>
    </div>
</article>
