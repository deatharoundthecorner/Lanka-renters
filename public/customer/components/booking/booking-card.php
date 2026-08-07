<?php

$bookingId = $bookingId ?? "BK-1001";

$vehicle = $vehicle ?? "Toyota Prius";

$status = $status ?? "Confirmed";

$date = $date ?? "12 Aug 2026";

?>

<div class="booking-card">

    <h3><?= htmlspecialchars($bookingId) ?></h3>

    <p><?= htmlspecialchars($vehicle) ?></p>

    <p><?= htmlspecialchars($date) ?></p>

    <span class="badge badge-success">

        <?= htmlspecialchars($status) ?>

    </span>

</div>