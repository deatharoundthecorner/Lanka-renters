<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminCustomerVerification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = 'Users';
$defaultView = ($_GET['view'] ?? '') === 'registered' ? 'registered' : 'pending';
$flash = $_SESSION['admin_customer_verification_flash'] ?? null;
unset($_SESSION['admin_customer_verification_flash']);

try {
    $verification = new AdminCustomerVerification();
    $pendingCustomers = $verification->pendingCustomers();
    $reviewedCustomers = $verification->reviewedCustomers();
    $databaseError = false;
} catch (Throwable $exception) {
    error_log(sprintf('Admin Customer list error [%s]: %s', get_class($exception), $exception->getMessage()));
    $pendingCustomers = [];
    $reviewedCustomers = [];
    $databaseError = true;
}

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>
        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Users</h1>
                <p class="page-subtitle">Review Customer verification details and record an approval decision.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'The request has been processed.') ?>
                </div>
            <?php endif; ?>

            <?php if ($databaseError): ?>
                <div class="card"><p>Customer verification records are temporarily unavailable. Please try again later.</p></div>
            <?php else: ?>
                <div class="nav-tabs">
                    <a class="tab-item <?= $defaultView === 'pending' ? 'active' : '' ?>" href="users.php">
                        <span>Pending Approval (<?= count($pendingCustomers) ?>)</span>
                    </a>
                    <a class="tab-item <?= $defaultView === 'registered' ? 'active' : '' ?>" href="users.php?view=registered">
                        <span>Reviewed Customers (<?= count($reviewedCustomers) ?>)</span>
                    </a>
                </div>

                <?php if ($defaultView === 'pending'): ?>
                    <div class="card">
                        <div class="card-header-clean">
                            <h3 class="card-title-text">Pending Customer Registrations</h3>
                            <span class="badge badge-pending"><?= count($pendingCustomers) ?> Pending</span>
                        </div>
                        <?php if ($pendingCustomers === []): ?>
                            <p>No Customer verification requests are waiting for review.</p>
                        <?php else: ?>
                            <div class="table-responsive"><table class="custom-table"><thead><tr>
                                <th>Customer ID</th><th>Customer</th><th>Contact</th><th>Verification details</th><th>Decision</th>
                            </tr></thead><tbody>
                            <?php foreach ($pendingCustomers as $customer): ?>
                                <tr>
                                    <td><span class="cell-secondary-text">CUS-<?= (int) $customer['id'] ?></span></td>
                                    <td><div class="cell-primary-text"><?= $escape($customer['name']) ?></div></td>
                                    <td><div class="cell-stacked"><span class="cell-secondary-text"><?= $escape($customer['email']) ?></span><span class="cell-secondary-text"><?= $escape($customer['phone']) ?></span></div></td>
                                    <td><div class="cell-stacked"><span class="cell-secondary-text">NIC: <?= $escape($customer['nic_number'] ?: 'Not provided') ?></span><span class="cell-secondary-text">Licence: <?= $escape($customer['driving_license_number'] ?: 'Not provided') ?></span><span class="cell-secondary-text">District: <?= $escape($customer['district'] ?: 'Not provided') ?></span></div></td>
                                    <td><div class="btn-group">
                                        <form method="post" action="customer_verification.php"><input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>"><input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>"><input type="hidden" name="verification_status" value="approved"><button class="btn btn-approve btn-sm" type="submit">Approve</button></form>
                                        <form method="post" action="customer_verification.php"><input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>"><input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>"><input type="hidden" name="verification_status" value="rejected"><button class="btn btn-reject btn-sm" type="submit">Reject</button></form>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody></table></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header-clean"><h3 class="card-title-text">Reviewed Customers</h3></div>
                        <?php if ($reviewedCustomers === []): ?>
                            <p>No Customer verification decisions have been recorded.</p>
                        <?php else: ?>
                            <div class="table-responsive"><table class="custom-table"><thead><tr><th>Customer ID</th><th>Customer</th><th>Contact</th><th>Decision</th><th>Registered</th></tr></thead><tbody>
                            <?php foreach ($reviewedCustomers as $customer): ?>
                                <tr><td><span class="cell-secondary-text">CUS-<?= (int) $customer['id'] ?></span></td><td><div class="cell-primary-text"><?= $escape($customer['name']) ?></div></td><td><div class="cell-stacked"><span class="cell-secondary-text"><?= $escape($customer['email']) ?></span><span class="cell-secondary-text"><?= $escape($customer['phone']) ?></span></div></td><td><span class="badge <?= $customer['verification_status'] === 'approved' ? 'badge-approved' : 'badge-pending' ?>"><?= $escape(ucfirst($customer['verification_status'])) ?></span></td><td><?= $escape($customer['created_at']) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody></table></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
