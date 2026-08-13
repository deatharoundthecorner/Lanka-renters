<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPaymentController.php';
$viewData = (new CustomerPaymentController())->summaryPage($_GET);
$summary = $viewData['summary'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<p><a class="text-link" href="<?= htmlspecialchars(customer_url('payments/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to payments</a></p>
<?php if (!is_array($summary)): ?><section class="empty-state"><h2>Payment summary not found</h2><p>The booking does not exist or does not belong to you.</p></section>
<?php else: ?><div class="alert alert--info"><p>This Payment Summary is not an official stored invoice. No invoice number, tax, discount, or unsupported charge is generated.</p></div><?php require dirname(__DIR__) . '/components/payment/payment-summary.php'; ?><?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>

