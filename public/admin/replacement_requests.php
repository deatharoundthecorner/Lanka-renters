<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/ReplacementRequest.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Replacement Requests";
$flash = $_SESSION['admin_rep_flash'] ?? null;
unset($_SESSION['admin_rep_flash']);

try {
    $model = new ReplacementRequest();
    $requests = $model->getAllRequests();
    $eligibleVehicles = $model->getEligibleVehicles('');
} catch (Throwable $e) {
    error_log("Replacement Requests Error: " . $e->getMessage());
    $requests = [];
    $eligibleVehicles = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replacement Requests - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Emergency Replacement Requests</h1>
                <p class="page-subtitle">Assign replacement vehicles or drivers for breakdown / accident incidents.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Active Replacement Requests</h3>
                    <span class="badge badge-pending"><?= count($requests) ?> Requests</span>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Booking / Customer</th>
                                <th>Original Vehicle</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Assign Replacement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 24px;">No replacement requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><span class="cell-secondary-text">REP-<?= (int)$req['id'] ?></span></td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($req['customer_name']) ?></span>
                                                <span class="cell-secondary-text">BKG-<?= (int)$req['booking_id'] ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($req['orig_make'] . ' ' . $req['orig_model']) ?></span>
                                                <span class="cell-secondary-text"><?= $escape($req['orig_plate']) ?></span>
                                            </div>
                                        </td>
                                        <td><span style="font-size: 13px; color: var(--text-secondary);"><?= $escape($req['reason']) ?></span></td>
                                        <td>
                                            <span class="badge <?= $req['status'] === 'approved' ? 'badge-approved' : ($req['status'] === 'dispatched' ? 'badge-blue' : 'badge-pending') ?>">
                                                <?= $escape(ucfirst($req['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <form method="post" action="replacement_handler.php" style="display: flex; flex-direction: column; gap: 6px;">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                    <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                                    <select name="replacement_vehicle_id" class="form-control" style="font-size: 12px; padding: 4px 6px;">
                                                        <option value="">Select Replacement Fleet...</option>
                                                        <?php foreach ($eligibleVehicles as $ev): ?>
                                                            <option value="<?= (int)$ev['id'] ?>">
                                                                <?= $escape($ev['make'] . ' ' . $ev['model'] . ' (' . $ev['license_plate'] . ')') ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm">Assign & Approve</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="cell-secondary-text">
                                                    Assigned: <?= $escape(($req['rep_make'] ?? '') . ' ' . ($req['rep_model'] ?? '')) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
