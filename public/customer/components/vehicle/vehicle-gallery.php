<?php
$galleryVehicleName = trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
?>
<section class="vehicle-gallery" aria-label="<?= htmlspecialchars(($galleryVehicleName !== '' ? $galleryVehicleName : 'Vehicle') . ' gallery', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (is_string($vehicle['image_url'] ?? null) && trim($vehicle['image_url']) !== ''): ?>
        <img class="vehicle-gallery__image" src="<?= htmlspecialchars(customer_url('../' . ltrim((string) $vehicle['image_url'], '/')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($galleryVehicleName !== '' ? $galleryVehicleName : 'Vehicle', ENT_QUOTES, 'UTF-8') ?>">
    <?php else: ?>
        <div class="vehicle-gallery__placeholder">
            <?= customer_icon('car') ?>
            <strong>Vehicle photo not available</strong>
            <span>No public vehicle image has been added.</span>
        </div>
    <?php endif; ?>
</section>
