<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPaymentController.php';
$viewData = (new CustomerPaymentController())->historyPage($_GET);
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<form class="feature-filter" method="get" action="<?= htmlspecialchars(customer_url('payments/index.php'), ENT_QUOTES, 'UTF-8') ?>">
    <label for="payment-status-filter">Payment status</label>
    <select id="payment-status-filter" name="status">
        <?php foreach (['' => 'All statuses', 'pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $value => $label): ?>
            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $viewData['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <button class="button button--primary" type="submit">Apply filter</button>
    <a class="text-link" href="<?= htmlspecialchars(customer_url('payments/index.php'), ENT_QUOTES, 'UTF-8') ?>">Clear</a>
</form>
<p class="result-count"><?= (int) $viewData['total'] ?> payment<?= (int) $viewData['total'] === 1 ? '' : 's' ?></p>
<?php if ($viewData['database_error']): ?>
    <section class="empty-state"><h2>Payments are temporarily unavailable</h2><p>Please try again later.</p></section>
<?php elseif ($viewData['payments'] === []): ?>
    <section class="empty-state"><span class="empty-state__icon" aria-hidden="true"><?= customer_icon('credit-card') ?></span><h2>No payments found</h2><p>Eligible payments begin from Booking Details. No demo payment records are shown here.</p><a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">View bookings</a></section>
<?php else: ?>
    <?php $payments = $viewData['payments']; require dirname(__DIR__) . '/components/payment/payment-history-table.php'; ?>
<?php endif; ?>
<?php if ($viewData['total_pages'] > 1): ?>
    <nav class="pagination" aria-label="Payment pages">
        <?php for ($pageNumber = 1; $pageNumber <= $viewData['total_pages']; $pageNumber++): ?>
            <a href="<?= htmlspecialchars(customer_url('payments/index.php?status=' . rawurlencode($viewData['status']) . '&page=' . $pageNumber), ENT_QUOTES, 'UTF-8') ?>" <?= $pageNumber === $viewData['current_page'] ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
