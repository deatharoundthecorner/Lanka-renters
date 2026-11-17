<section class="card vehicle-specifications" aria-labelledby="vehicle-specifications-title">
    <div class="card__header">
        <h2 id="vehicle-specifications-title">Vehicle specifications</h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('car') ?></span>
    </div>
    <dl class="vehicle-spec-list">
        <div><dt>Brand</dt><dd><?= htmlspecialchars((string) ($vehicle['make'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Model</dt><dd><?= htmlspecialchars((string) ($vehicle['model'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Year</dt><dd><?= htmlspecialchars((string) ($vehicle['year'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Vehicle type</dt><dd><?= htmlspecialchars(ucfirst((string) ($vehicle['vehicle_type'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Transmission</dt><dd><?= htmlspecialchars(ucfirst((string) ($vehicle['transmission'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Fuel type</dt><dd><?= htmlspecialchars(ucfirst((string) ($vehicle['fuel_type'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Seating capacity</dt><dd><?= (int) ($vehicle['seating_capacity'] ?? 0) ?> seats</dd></div>
        <div><dt>Air conditioning</dt><dd><?= ($vehicle['air_conditioning'] ?? null) === null ? 'Not specified' : ((bool) $vehicle['air_conditioning'] ? 'Available' : 'Not available') ?></dd></div>
        <div><dt>Vehicle location</dt><dd><?= htmlspecialchars((string) ($vehicle['location'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Supported services</dt><dd><?= ($vehicle['price_with_driver_per_day'] ?? null) !== null ? 'Self-drive and with driver' : 'Self-drive' ?></dd></div>
        <div><dt>Licence plate</dt><dd><?= htmlspecialchars((string) ($vehicle['license_plate'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
    </dl>
</section>
