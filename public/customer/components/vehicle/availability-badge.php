<?php
$availabilityMap = [
    'available' => ['label' => 'Available', 'tone' => 'success'],
    'rented' => ['label' => 'Currently rented', 'tone' => 'info'],
    'maintenance' => ['label' => 'Maintenance', 'tone' => 'warning'],
    'unavailable' => ['label' => 'Unavailable', 'tone' => 'neutral'],
];
$availability = $availabilityMap[$vehicleStatus] ?? $availabilityMap['unavailable'];
?>
<span class="status-badge status-badge--<?= htmlspecialchars($availability['tone'], ENT_QUOTES, 'UTF-8') ?>">
    <span class="status-badge__dot" aria-hidden="true"></span>
    <?= htmlspecialchars($availability['label'], ENT_QUOTES, 'UTF-8') ?>
</span>
