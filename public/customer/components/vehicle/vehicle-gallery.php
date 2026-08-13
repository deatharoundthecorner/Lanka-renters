<?php
$galleryVehicleName = trim((string) ($vehicle['make'] ?? '') . ' ' . (string) ($vehicle['model'] ?? ''));
?>
<section class="vehicle-gallery" aria-label="<?= htmlspecialchars(($galleryVehicleName !== '' ? $galleryVehicleName : 'Vehicle') . ' gallery', ENT_QUOTES, 'UTF-8') ?>">
    <div class="vehicle-gallery__placeholder">
        <?= customer_icon('car') ?>
        <strong>Vehicle photo not available</strong>
        <span>The current database does not store public vehicle images.</span>
    </div>
</section>
