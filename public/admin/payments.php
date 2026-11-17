<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminPaymentVerification.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Payments Management";
$defaultView = ($_GET['view'] ?? '') === 'reviewed' ? 'reviewed' : 'pending';

$flash = $_SESSION['admin_payment_flash'] ?? null;
unset($_SESSION['admin_payment_flash']);

try {
    $model = new AdminPaymentVerification();
    $pendingPayments = $model->getPendingPayments();
    $reviewedPayments = $model->getReviewedPayments();
} catch (Throwable $e) {
    error_log("Payments Page Error: " . $e->getMessage());
    $pendingPayments = [];
    $reviewedPayments = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Payments Management</h1>
                <p class="page-subtitle">Verify submitted payment evidence, transaction slips, and approve booking receipts.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <!-- Segmented Tab Bar -->
            <div class="nav-tabs">
                <a href="payments.php" class="tab-item <?= $defaultView === 'pending' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Pending Verification (<?= count($pendingPayments) ?>)</span>
                </a>
                <a href="payments.php?view=reviewed" class="tab-item <?= $defaultView === 'reviewed' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Payment Audit History (<?= count($reviewedPayments) ?>)</span>
                </a>
            </div>

            <?php if ($defaultView === 'pending'): ?>
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Pending Payment Slips</h3>
                        <span class="badge badge-pending"><?= count($pendingPayments) ?> Pending</span>
                    </div>

                    <?php if (empty($pendingPayments)): ?>
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            </div>
                            <h3>No pending payment slips</h3>
                            <p>There are currently no customer payment slips awaiting admin review.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Slip Evidence</th>
                                        <th>Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingPayments as $pay): ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">PAY-<?= (int)$pay['id'] ?></span></td>
                                            <td>
                                                <span class="cell-secondary-text">BKG-<?= (int)$pay['booking_id'] ?></span>
                                                <?php if (!empty($pay['invoice_number'])): ?>
                                                    <br><span class="cell-secondary-text" style="font-size: 11px;"><?= $escape($pay['invoice_number']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-primary-text"><?= $escape($pay['customer_name']) ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($pay['customer_email']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="cell-primary-text">Rs. <?= number_format((float)$pay['amount'], 2) ?></span></td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="badge badge-blue"><?= $escape(ucfirst($pay['payment_method'])) ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($pay['payment_type'] ?? 'full') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($pay['payment_slip_path'])): ?>
                                                    <a href="payment_document.php?id=<?= (int)$pay['id'] ?>" target="_blank" class="btn btn-secondary btn-sm">
                                                        View Slip ↗
                                                    </a>
                                                <?php else: ?>
                                                    <span class="cell-secondary-text" style="font-size: 11px; font-style: italic; color: var(--text-muted);">Not uploaded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <form method="post" action="payment_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-approve btn-sm">Approve</button>
                                                    </form>
                                                    <form method="post" action="payment_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" class="btn btn-reject btn-sm">Reject</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Payment Audit History</h3>
                        <span class="badge badge-approved"><?= count($reviewedPayments) ?> Processed</span>
                    </div>

                    <?php if (empty($reviewedPayments)): ?>
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <h3>No payment history records</h3>
                            <p>Processed payment transactions and verification decisions will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Evidence</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviewedPayments as $pay): ?>
                                        <?php
                                        $payStatusBadge = match($pay['payment_status']) {
                                            'completed', 'approved' => 'badge-approved',
                                            'refunded' => 'badge-rejected',
                                            'rejected', 'failed' => 'badge-rejected',
                                            default => 'badge-pending'
                                        };
                                        ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">PAY-<?= (int)$pay['id'] ?></span></td>
                                            <td>
                                                <span class="cell-secondary-text">BKG-<?= (int)$pay['booking_id'] ?></span>
                                                <?php if (!empty($pay['booking_status'])): ?>
                                                    <span class="badge <?= $pay['booking_status'] === 'disputed' ? 'badge-rejected' : 'badge-pending' ?>" style="font-size: 10px; margin-left: 4px;">
                                                        <?= $escape(ucfirst($pay['booking_status'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($pay['invoice_number'])): ?>
                                                    <br><span class="cell-secondary-text" style="font-size: 11px;"><?= $escape($pay['invoice_number']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-primary-text"><?= $escape($pay['customer_name']) ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($pay['customer_email']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="cell-primary-text">Rs. <?= number_format((float)$pay['amount'], 2) ?></span></td>
                                            <td><span class="badge badge-blue"><?= $escape(ucfirst($pay['payment_method'])) ?></span></td>
                                            <td>
                                                <?php if (!empty($pay['payment_slip_path'])): ?>
                                                    <a href="payment_document.php?id=<?= (int)$pay['id'] ?>" target="_blank" class="btn btn-secondary btn-sm">
                                                        View Slip ↗
                                                    </a>
                                                <?php else: ?>
                                                    <span class="cell-secondary-text" style="font-size: 11px; font-style: italic; color: var(--text-muted);">Not uploaded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $payStatusBadge ?>">
                                                    <?= $escape(ucfirst($pay['payment_status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($pay['payment_status'] === 'completed' && in_array($pay['booking_status'] ?? '', ['disputed', 'ongoing'])): ?>
                                                    <form method="post" action="payment_verification.php" onsubmit="return confirm('Process full refund for Payment #<?= (int)$pay['id'] ?> and complete Booking #<?= (int)$pay['booking_id'] ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                                                        <input type="hidden" name="status" value="refunded">
                                                        <button type="submit" class="btn btn-reject btn-sm" title="Refund disputed/ongoing booking">Refund</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="cell-secondary-text" style="font-size: 11px;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
