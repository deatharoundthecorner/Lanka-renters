<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';

$viewData = (new CustomerPortalController())->driverChangeDetailsPage(
    $_GET,
    $_POST,
    strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'))
);

if (isset($viewData['redirect'])) {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}

$request = $viewData['request'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<p><a class="text-link" href="<?= htmlspecialchars(customer_url('driver-change/index.php'), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to requests</a></p>

<?php if (!is_array($request)): ?>
    <section class="empty-state">
        <h2>Request not found</h2>
        <p>This driver change request is unavailable or does not belong to your account.</p>
    </section>
<?php else: ?>
    <?php
    $badgeClass = match ($request['status']) {
        'approved'  => 'status-badge--success',
        'rejected'  => 'status-badge--danger',
        'cancelled' => 'status-badge--neutral',
        default     => 'status-badge--warning',
    };
    ?>
    <?php if (isset($viewData['errors']['form'])): ?>
        <div class="alert alert--error" style="margin-bottom: 16px;">
            <p><?= htmlspecialchars($viewData['errors']['form'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <section class="card">
        <div class="card__header">
            <div>
                <p class="eyebrow">Request #<?= (int)$request['id'] ?></p>
                <h2><?= htmlspecialchars($request['make'] . ' ' . $request['model'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($request['license_plate'], ENT_QUOTES, 'UTF-8') ?>)</h2>
            </div>
            <span class="status-badge <?= $badgeClass ?>"><?= ucfirst($request['status']) ?></span>
        </div>

        <dl class="feature-detail-list">
            <div>
                <dt>Booking Reference</dt>
                <dd>#<?= (int)$request['booking_id'] ?> (<?= ucfirst($request['booking_status']) ?>)</dd>
            </div>
            <div>
                <dt>Request Date</dt>
                <dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Current Driver</dt>
                <dd><?= htmlspecialchars($request['current_driver_name'], ENT_QUOTES, 'UTF-8') ?> <?php if (!empty($request['current_driver_phone'])): ?>(<?= htmlspecialchars($request['current_driver_phone'], ENT_QUOTES, 'UTF-8') ?>)<?php endif; ?></dd>
            </div>
            <div>
                <dt>Requested Driver</dt>
                <dd><?= htmlspecialchars($request['requested_driver_name'], ENT_QUOTES, 'UTF-8') ?> <?php if (!empty($request['requested_driver_phone'])): ?>(<?= htmlspecialchars($request['requested_driver_phone'], ENT_QUOTES, 'UTF-8') ?>)<?php endif; ?></dd>
            </div>
            <div class="feature-detail-list__wide">
                <dt>Customer Reason</dt>
                <dd><?= nl2br(htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8')) ?></dd>
            </div>
            <?php if (!empty($request['owner_remarks'])): ?>
                <div class="feature-detail-list__wide">
                    <dt>Owner Remarks</dt>
                    <dd><?= nl2br(htmlspecialchars($request['owner_remarks'], ENT_QUOTES, 'UTF-8')) ?></dd>
                </div>
            <?php endif; ?>
            <?php if (!empty($request['reviewed_at'])): ?>
                <div>
                    <dt>Reviewed On</dt>
                    <dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime($request['reviewed_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
            <?php endif; ?>
        </dl>

        <div style="margin-top: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <a class="button button--secondary" href="<?= htmlspecialchars(customer_url('bookings/details.php?id=' . (int)$request['booking_id']), ENT_QUOTES, 'UTF-8') ?>">View Booking Details</a>

            <?php if ($request['status'] === 'pending'): ?>
                <form method="post" action="<?= htmlspecialchars(customer_url('driver-change/details.php?id=' . (int)$request['id']), ENT_QUOTES, 'UTF-8') ?>" style="margin: 0;">
                    <?= CustomerCsrf::field() ?>
                    <input type="hidden" name="action" value="cancel">
                    <button class="button button--secondary" type="submit" onclick="return confirm('Are you sure you want to cancel this driver change request?');">Cancel Request</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
