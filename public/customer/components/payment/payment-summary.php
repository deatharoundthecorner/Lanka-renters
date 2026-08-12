<section class="card payment-summary" aria-labelledby="payment-summary-title">
    <div class="card__header"><div><p class="eyebrow">Database booking</p><h2 id="payment-summary-title">Payment Summary</h2></div><span class="card__header-icon" aria-hidden="true"><?= customer_icon('credit-card') ?></span></div>
    <dl class="feature-detail-list">
        <div><dt>Booking reference</dt><dd>#<?= (int) ($summary['booking_id'] ?? $summary['id'] ?? 0) ?></dd></div>
        <?php if (isset($summary['customer_name'])): ?><div><dt>Customer</dt><dd><?= htmlspecialchars((string) $summary['customer_name'], ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
        <div><dt>Vehicle</dt><dd><?= htmlspecialchars(trim((string) ($summary['make'] ?? '') . ' ' . (string) ($summary['model'] ?? '')), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Rental period</dt><dd><?= htmlspecialchars(date('d M Y', strtotime((string) $summary['start_date'])) . ' – ' . date('d M Y', strtotime((string) $summary['end_date'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Booking type</dt><dd><?= ($summary['booking_type'] ?? '') === 'with_driver' ? 'With driver' : 'Self-drive' ?></dd></div>
        <div><dt>Stored booking total</dt><dd><strong>Rs. <?= number_format((float) ($summary['total_price'] ?? 0), 2) ?></strong></dd></div>
    </dl>
    <?php if (is_array($summary['payment'] ?? null)): ?><p>Latest payment: #<?= (int) $summary['payment']['id'] ?> — <?= htmlspecialchars(ucfirst($summary['payment']['payment_status']), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</section>
