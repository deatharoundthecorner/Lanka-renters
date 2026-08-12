<section class="card vehicle-owner-card" aria-labelledby="vehicle-owner-title">
    <div class="card__header">
        <h2 id="vehicle-owner-title">Vehicle owner</h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('user') ?></span>
    </div>
    <p class="vehicle-owner-card__name"><?= htmlspecialchars((string) ($vehicle['owner_name'] ?? 'Verified owner'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><span class="status-badge status-badge--success"><?= customer_icon('check') ?> Approved owner</span></p>
    <p>Private owner contact, identity, and payment details are never shown in the public catalogue.</p>
</section>
