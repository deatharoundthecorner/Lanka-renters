<section class="card vehicle-specifications" aria-labelledby="vehicle-specifications-title">
    <div class="card__header">
        <h2 id="vehicle-specifications-title">Vehicle specifications</h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('car') ?></span>
    </div>
    <dl class="vehicle-spec-list">
        <div><dt>Year</dt><dd><?= htmlspecialchars((string) ($vehicle['year'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Vehicle type</dt><dd><?= htmlspecialchars(ucfirst((string) ($vehicle['vehicle_type'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Transmission</dt><dd><?= htmlspecialchars(ucfirst((string) ($vehicle['transmission'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Fuel type</dt><dd><?= htmlspecialchars(ucfirst((string) ($vehicle['fuel_type'] ?? 'Not specified')), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Seating capacity</dt><dd><?= (int) ($vehicle['seating_capacity'] ?? 0) ?> seats</dd></div>
        <div><dt>Licence plate</dt><dd><?= htmlspecialchars((string) ($vehicle['license_plate'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8') ?></dd></div>
    </dl>
</section>
