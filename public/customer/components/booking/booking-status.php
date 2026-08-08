<?php
$bookingStatusMap = [
    'pending_payment' => ['label' => 'Pending payment', 'tone' => 'warning'],
    'confirmed' => ['label' => 'Confirmed', 'tone' => 'success'],
    'ongoing' => ['label' => 'Ongoing', 'tone' => 'info'],
    'completed' => ['label' => 'Completed', 'tone' => 'success'],
    'cancelled' => ['label' => 'Cancelled', 'tone' => 'neutral'],
];
$bookingStatusDisplay = $bookingStatusMap[$bookingStatus] ?? ['label' => 'Unknown', 'tone' => 'neutral'];
?>
<span class="status-badge status-badge--<?= htmlspecialchars($bookingStatusDisplay['tone'], ENT_QUOTES, 'UTF-8') ?>">
    <span class="status-badge__dot" aria-hidden="true"></span>
    <?= htmlspecialchars($bookingStatusDisplay['label'], ENT_QUOTES, 'UTF-8') ?>
</span>
