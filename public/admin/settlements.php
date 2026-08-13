<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Settlement.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Owner Settlements";
$flash = $_SESSION['admin_set_flash'] ?? null;
unset($_SESSION['admin_set_flash']);

try {
    $model = new Settlement();
    $settlements = $model->getSettlements();
} catch (Throwable $e) {
    error_log("Settlements Page Error: " . $e->getMessage());
    $settlements = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Settlements - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Vehicle Owner Payout Settlements</h1>
                <p class="page-subtitle">Track net owner payouts after deducting the standard 10% platform commission fee.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Settlements & Commission Ledger</h3>
                    <span class="badge badge-approved">10% Platform Commission</span>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Vehicle Owner</th>
                                <th>Bank Details</th>
                                <th>Gross Total</th>
                                <th>10% Commission</th>
                                <th>Net Owner Payout</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($settlements)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 24px;">No completed booking settlements recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($settlements as $set): ?>
                                    <tr>
                                        <td><span class="cell-secondary-text">BKG-<?= (int)$set['booking_id'] ?></span></td>
                                        <td><div class="cell-primary-text"><?= $escape($set['owner_name']) ?></div></td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-secondary-text"><?= $escape($set['bank_name'] ?: 'N/A') ?></span>
                                                <span class="cell-secondary-text"><?= $escape($set['bank_account_no'] ?: '-') ?></span>
                                            </div>
                                        </td>
                                        <td><span class="cell-primary-text">Rs. <?= number_format((float)$set['gross_amount'], 2) ?></span></td>
                                        <td><span class="badge badge-blue">Rs. <?= number_format((float)$set['commission_amount'], 2) ?></span></td>
                                        <td><span class="cell-primary-text" style="color: var(--success); font-weight: 700;">Rs. <?= number_format((float)$set['net_amount'], 2) ?></span></td>
                                        <td>
                                            <span class="badge <?= $set['status'] === 'completed' ? 'badge-approved' : 'badge-pending' ?>">
                                                <?= $escape(ucfirst($set['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($set['status'] !== 'completed'): ?>
                                                <form method="post" action="settlement_handler.php">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                    <input type="hidden" name="owner_id" value="<?= (int)$set['owner_id'] ?>">
                                                    <input type="hidden" name="booking_id" value="<?= (int)$set['booking_id'] ?>">
                                                    <input type="hidden" name="gross_amount" value="<?= (float)$set['gross_amount'] ?>">
                                                    <button type="submit" class="btn btn-approve btn-sm">Mark Paid</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="cell-secondary-text">Settled</span>
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
