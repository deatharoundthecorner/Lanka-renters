<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData = (new CustomerPortalController())->returnDetailsPage($_GET);
$request = $viewData['request'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<p><a class="text-link" href="<?= htmlspecialchars(customer_url('returns/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to returns</a></p>
<?php if ($viewData['database_error']): ?>
    <section class="empty-state"><h2>Return request unavailable</h2><p>Please try again later.</p></section>
<?php elseif (!is_array($request)): ?>
    <section class="empty-state"><h2>Return request not found</h2><p>This request does not exist or does not belong to your account.</p></section>
<?php else: ?>
    <section class="card">
        <div class="card__header">
            <div><p class="eyebrow">Return request #<?= (int) $request['id'] ?></p><h2><?= htmlspecialchars($request['make'] . ' ' . $request['model'], ENT_QUOTES, 'UTF-8') ?></h2></div>
            <span class="status-badge status-badge--warning"><?= htmlspecialchars(ucfirst((string) $request['status']), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <dl class="feature-detail-list">
            <div><dt>Booking</dt><dd>#<?= (int) $request['booking_id'] ?></dd></div>
            <div><dt>Booking status</dt><dd><?= htmlspecialchars(ucfirst((string) $request['booking_status']), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Proposed return</dt><dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime($request['proposed_return_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="feature-detail-list__wide"><dt>Customer note</dt><dd><?= nl2br(htmlspecialchars($request['customer_note'] ?: 'Not provided', ENT_QUOTES, 'UTF-8')) ?></dd></div>
            <?php if (!empty($request['admin_remarks'])): ?><div class="feature-detail-list__wide"><dt>Owner note</dt><dd><?= nl2br(htmlspecialchars($request['admin_remarks'], ENT_QUOTES, 'UTF-8')) ?></dd></div><?php endif; ?>
        </dl>
        <?php if ($request['status'] !== 'completed'): ?><p>The owner completes check-in after recording the post-rental inspection. This request alone does not change the booking or vehicle status.</p><?php endif; ?>
        <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/details.php?id=' . (int) $request['booking_id']), ENT_QUOTES, 'UTF-8') ?>">Booking Details</a>
    </section>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
