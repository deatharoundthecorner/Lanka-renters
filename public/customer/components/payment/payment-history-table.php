<div class="feature-card-list">
    <?php foreach ($payments as $payment): ?>
        <article class="card feature-list-card">
            <div><p class="eyebrow">Payment #<?= (int) $payment['id'] ?> · Booking #<?= (int) $payment['booking_id'] ?></p><h2><?= htmlspecialchars($payment['make'] . ' ' . $payment['model'], ENT_QUOTES, 'UTF-8') ?></h2><p>Rs. <?= number_format((float) $payment['amount'], 2) ?> · <?= htmlspecialchars(ucwords(str_replace('_', ' ', $payment['payment_method'])), ENT_QUOTES, 'UTF-8') ?></p><p class="break-text"><?= htmlspecialchars($payment['transaction_reference'] ?: 'No transaction reference', ENT_QUOTES, 'UTF-8') ?></p></div>
            <div class="feature-list-card__actions"><?php $paymentStatus = $payment['payment_status']; require __DIR__ . '/payment-status.php'; ?><time datetime="<?= htmlspecialchars($payment['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('d M Y', strtotime($payment['created_at'])), ENT_QUOTES, 'UTF-8') ?></time><a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url('payments/details.php?id=' . (int) $payment['id']), ENT_QUOTES, 'UTF-8') ?>">View Details</a></div>
        </article>
    <?php endforeach; ?>
</div>
