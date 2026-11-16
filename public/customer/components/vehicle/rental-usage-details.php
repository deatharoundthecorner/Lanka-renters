<?php
$money = static fn (mixed $value): string => $value === null ? 'Not specified' : 'LKR ' . number_format((float) $value, 2);
$mileage = (bool) ($vehicle['mileage_is_unlimited'] ?? false)
    ? 'Unlimited mileage'
    : (($vehicle['mileage_limit_km'] ?? null) !== null ? number_format((int) $vehicle['mileage_limit_km']) . ' km per month' : 'Not specified');
?>
<section class="card vehicle-rental-details" aria-labelledby="rental-usage-details-title">
    <div class="card__header"><h2 id="rental-usage-details-title">Rental and usage details</h2><span class="card__header-icon" aria-hidden="true"><?= customer_icon('calendar') ?></span></div>
    <dl class="vehicle-spec-list">
        <div><dt>Monthly rental</dt><dd><?= htmlspecialchars($money($vehicle['monthly_rental_price'] ?? null), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Refundable key money</dt><dd><?= htmlspecialchars($money($vehicle['refundable_key_money'] ?? null), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Mileage limit</dt><dd><?= htmlspecialchars($mileage, ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Additional mileage charge</dt><dd><?= htmlspecialchars($money($vehicle['additional_mileage_charge_per_km'] ?? null), ENT_QUOTES, 'UTF-8') ?><?= ($vehicle['additional_mileage_charge_per_km'] ?? null) !== null ? ' / km' : '' ?></dd></div>
        <div><dt>Minimum rental period</dt><dd>28 days</dd></div>
        <div><dt>Maximum rental period</dt><dd>6 calendar months</dd></div>
    </dl>
</section>
