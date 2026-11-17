<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData = (new CustomerPortalController())->returnsPage();
$viewData['page_title'] = 'Return Requests';
$viewData['page_description'] = 'Start the return process for an eligible active rental.';
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<?php if (!empty($viewData['database_error'])): ?>
    <section class="empty-state"><h2>Return requests unavailable</h2><p>Please try again later.</p></section>
<?php elseif ($viewData['requests'] === []): ?>
    <section class="empty-state">
        <h2>No return requests yet</h2>
        <p>An ongoing Customer-owned booking is required to start a return.</p>
        <?php if ($viewData['eligible_bookings'] !== []): ?><a class="button button--primary" href="<?= htmlspecialchars(customer_url('returns/create.php'), ENT_QUOTES, 'UTF-8') ?>">Start Return Request</a><?php endif; ?>
    </section>
<?php else: ?>
    <div class="feature-card-list">
        <?php foreach ($viewData['requests'] as $request): ?>
            <article class="card feature-list-card">
                <div>
                    <p class="eyebrow">Return request #<?= (int) $request['id'] ?> · Booking #<?= (int) $request['booking_id'] ?></p>
                    <h2><?= htmlspecialchars($request['make'] . ' ' . $request['model'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p>Proposed <?= htmlspecialchars(date('d M Y, H:i', strtotime($request['proposed_return_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                    <p><?= htmlspecialchars($request['customer_note'] ?: 'No customer note.', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="feature-list-card__actions">
                    <span class="status-badge status-badge--warning"><?= htmlspecialchars(ucfirst((string) $request['status']), ENT_QUOTES, 'UTF-8') ?></span>
                    <a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url('returns/details.php?id=' . (int) $request['id']), ENT_QUOTES, 'UTF-8') ?>">View Details</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
