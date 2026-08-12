<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPaymentController.php';
$viewData = (new CustomerPaymentController())->detailsPage($_GET);
$payment = $viewData['payment'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<p><a class="text-link" href="<?= htmlspecialchars(customer_url('payments/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to payment history</a></p>
<?php if ($viewData['database_error']): ?>
    <section class="empty-state"><h2>Payment details are temporarily unavailable</h2><p>Please try again later.</p></section>
<?php elseif (!is_array($payment)): ?>
    <section class="empty-state"><h2>Payment not found</h2><p>This payment does not exist or does not belong to your account.</p></section>
<?php else: ?>
    <div class="feature-detail-grid">
        <section class="card"><div class="card__header"><div><p class="eyebrow">Payment #<?= (int) $payment['id'] ?></p><h2>Payment details</h2></div><?php $paymentStatus = $payment['payment_status']; require dirname(__DIR__) . '/components/payment/payment-status.php'; ?></div>
            <dl class="feature-detail-list">
                <div><dt>Booking</dt><dd>#<?= (int) $payment['booking_id'] ?></dd></div><div><dt>Vehicle</dt><dd><?= htmlspecialchars($payment['make'] . ' ' . $payment['model'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Amount</dt><dd>Rs. <?= number_format((float) $payment['amount'], 2) ?></dd></div><div><dt>Method</dt><dd><?= htmlspecialchars(ucwords(str_replace('_', ' ', $payment['payment_method'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Transaction reference</dt><dd class="break-text"><?= htmlspecialchars($payment['transaction_reference'] ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Submitted</dt><dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime($payment['created_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Paid date</dt><dd><?= $payment['paid_at'] ? htmlspecialchars(date('d M Y, h:i A', strtotime($payment['paid_at'])), ENT_QUOTES, 'UTF-8') : 'Not confirmed' ?></dd></div><div><dt>Evidence</dt><dd><?= $payment['payment_slip_path'] ? 'Available through secure download' : 'Not available' ?></dd></div>
            </dl>
        </section>
        <aside class="card"><h2>Status meaning</h2><p><?= htmlspecialchars(['pending' => 'Submitted and waiting for authorized review.', 'completed' => 'Verified and accepted.', 'failed' => 'Not accepted.', 'refunded' => 'A refund has been recorded.'][$payment['payment_status']] ?? 'Status recorded.', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="stack-actions"><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/details.php?id=' . (int) $payment['booking_id']), ENT_QUOTES, 'UTF-8') ?>">Booking Details</a><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('payments/summary.php?booking_id=' . (int) $payment['booking_id']), ENT_QUOTES, 'UTF-8') ?>">Payment Summary</a><?php if ($payment['payment_slip_path']): ?><a class="button button--primary" href="<?= htmlspecialchars(customer_url('payments/proof.php?id=' . (int) $payment['id']), ENT_QUOTES, 'UTF-8') ?>">Download evidence</a><?php endif; ?></div>
        </aside>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>

