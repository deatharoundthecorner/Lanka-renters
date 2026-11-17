<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData = (new CustomerPortalController())->driverChangesPage();
$viewData['page_title'] = 'Driver Change Requests';
$viewData['page_description'] = 'Request a Driver change for an eligible with-driver booking.';
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<?php if (!empty($viewData['database_error'])): ?>
    <section class="empty-state"><h2>Booking eligibility unavailable</h2><p>Please try again later.</p></section>
<?php elseif ($viewData['requests'] === []): ?>
    <section class="empty-state"><h2>No driver change requests yet</h2><p>Request a replacement driver for an eligible confirmed or ongoing with-driver booking.</p><?php if ($viewData['eligible_bookings'] !== []): ?><a class="button button--primary" href="<?= htmlspecialchars(customer_url('driver-change/create.php'), ENT_QUOTES, 'UTF-8') ?>">Create Driver Change Request</a><?php endif; ?></section>
<?php else: ?>
    <?php if ($viewData['eligible_bookings'] !== []): ?>
        <div style="margin-bottom: 16px;">
            <a class="button button--primary" href="<?= htmlspecialchars(customer_url('driver-change/create.php'), ENT_QUOTES, 'UTF-8') ?>">Create Driver Change Request</a>
        </div>
    <?php endif; ?>
    <div class="feature-card-list">
        <?php foreach ($viewData['requests'] as $request): ?>
            <?php
            $badgeClass = match ($request['status']) {
                'approved'  => 'status-badge--success',
                'rejected'  => 'status-badge--danger',
                'cancelled' => 'status-badge--neutral',
                default     => 'status-badge--warning',
            };
            $statusLabel = ucfirst($request['status']);
            ?>
            <article class="card feature-list-card">
                <div>
                    <p class="eyebrow">Request #<?= (int) $request['id'] ?> &middot; Booking #<?= (int) $request['booking_id'] ?></p>
                    <h2><?= htmlspecialchars($request['make'] . ' ' . $request['model'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($request['license_plate'], ENT_QUOTES, 'UTF-8') ?>)</h2>
                    <p style="margin: 4px 0 8px;"><strong>Current:</strong> <?= htmlspecialchars($request['current_driver_name'], ENT_QUOTES, 'UTF-8') ?> &rarr; <strong>Requested:</strong> <?= htmlspecialchars($request['requested_driver_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><?= htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8') ?></p>
                    <time><?= htmlspecialchars(date('d M Y, h:i A', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></time>
                </div>
                <div class="feature-list-card__actions">
                    <span class="status-badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                    <a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url('driver-change/details.php?id=' . (int) $request['id']), ENT_QUOTES, 'UTF-8') ?>">View Details</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
