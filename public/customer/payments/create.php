<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPaymentController.php';
$viewData = (new CustomerPaymentController())->createPage($_GET, $_POST, $_FILES, strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')));
if (isset($viewData['redirect'])) { header('Location: ' . customer_url($viewData['redirect']), true, 303); exit; }
$booking = $viewData['booking'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<p><a class="text-link" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to bookings</a></p>
<?php if ($viewData['database_error']): ?>
    <section class="empty-state"><h2>Payment submission is unavailable</h2><p>Please try again later.</p></section>
<?php elseif (!is_array($booking)): ?>
    <section class="empty-state"><h2>Payable booking not found</h2><p>The booking does not exist, does not belong to you, or is not awaiting payment.</p></section>
<?php else: ?>
    <?php $summary = $booking; require dirname(__DIR__) . '/components/payment/payment-summary.php'; ?>
    <?php if (isset($viewData['errors']['form'])): ?><div class="alert alert--error" role="alert"><p><?= htmlspecialchars($viewData['errors']['form'], ENT_QUOTES, 'UTF-8') ?></p></div><?php endif; ?>
    <?php if (is_array($booking['blocking_payment'])): ?>
        <section class="card"><h2>Another payment cannot be submitted</h2><p>A <?= htmlspecialchars($booking['blocking_payment']['payment_status'], ENT_QUOTES, 'UTF-8') ?> payment already exists.</p><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('payments/details.php?id=' . (int) $booking['blocking_payment']['id']), ENT_QUOTES, 'UTF-8') ?>">View payment</a></section>
    <?php elseif (is_array($booking['latest_payment']) && $booking['latest_payment']['payment_status'] === 'refunded'): ?>
        <section class="card"><h2>Support coordination required</h2><p>A refunded payment cannot be resubmitted automatically. Contact authorized support through Chat.</p><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('chat/index.php'), ENT_QUOTES, 'UTF-8') ?>">Open Chat</a></section>
    <?php else: ?>
        <form class="card feature-form" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars(customer_url('payments/create.php?booking_id=' . (int) $booking['id']), ENT_QUOTES, 'UTF-8') ?>" data-submit-once>
            <?= CustomerCsrf::field() ?><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
            <div><span class="demo-label demo-label--database">Database payment</span><h2>Bank transfer evidence</h2><p>Official bank instructions have not been supplied in the approved Customer files. Use only instructions provided directly by authorized Lanka Renters staff.</p></div>
            <fieldset class="payment-methods"><legend>Payment method</legend>
                <label><input type="radio" name="payment_method" value="bank_transfer" checked> <span><strong>Bank transfer</strong><small>Evidence required; submitted as pending.</small></span></label>
                <label class="is-disabled"><input type="radio" disabled> <span><strong>Card — Not available yet</strong><small>No approved secure gateway exists.</small></span></label>
                <label class="is-disabled"><input type="radio" disabled> <span><strong>Cash — Coordination required</strong><small>No approved cash declaration process exists.</small></span></label>
            </fieldset>
            <label for="transaction-reference">Transaction reference <span class="form-hint">Optional, maximum 100 characters</span></label>
            <input id="transaction-reference" name="transaction_reference" maxlength="100" value="<?= htmlspecialchars($viewData['form']['transaction_reference'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="payment-proof">Payment evidence <span class="form-hint">Required: genuine JPEG, PNG, or PDF; maximum 5 MB</span></label>
            <input id="payment-proof" type="file" name="payment_proof" accept="image/jpeg,image/png,application/pdf" required data-file-input>
            <p class="file-name" data-file-name>No file selected.</p>
            <?php if (isset($viewData['errors']['payment_proof'])): ?><p class="field-error" role="alert"><?= htmlspecialchars($viewData['errors']['payment_proof'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <div class="alert alert--info"><p>Submission does not confirm the booking or complete the payment. Admin verification is required.</p></div>
            <button class="button button--primary" type="submit">Submit for verification</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>

