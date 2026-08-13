<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminOwnerVerification.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Vehicle Owners";
$districts = getSriLankanDistricts();
$vehicleTypes = getVehicleTypes();
$defaultView = ($_GET['view'] ?? '') === 'registered' ? 'registered' : 'pending';

$flash = $_SESSION['admin_owner_flash'] ?? null;
unset($_SESSION['admin_owner_flash']);

try {
    $model = new AdminOwnerVerification();
    $pendingOwners = $model->getPendingOwners();
    $reviewedOwners = $model->getReviewedOwners();
    $dbError = false;
} catch (Throwable $e) {
    error_log("Vehicle Owners Error: " . $e->getMessage());
    $pendingOwners = [];
    $reviewedOwners = [];
    $dbError = true;
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Owners - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Vehicle Owners</h1>
                <p class="page-subtitle">Review vehicle owner registrations and verify vehicle ownership documentation.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <!-- Segmented Tab Bar -->
            <div class="nav-tabs">
                <a href="vehicle_owners.php" class="tab-item <?= $defaultView === 'pending' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Pending Approval (<?= count($pendingOwners) ?>)</span>
                </a>
                <a href="vehicle_owners.php?view=registered" class="tab-item <?= $defaultView === 'registered' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Registered Owners (<?= count($reviewedOwners) ?>)</span>
                </a>
            </div>

            <?php if ($defaultView === 'pending'): ?>
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Pending Vehicle Owner Approvals</h3>
                        <span class="badge badge-pending"><?= count($pendingOwners) ?> Pending</span>
                    </div>

                    <?php if (empty($pendingOwners)): ?>
                        <p style="color: var(--text-secondary);">No vehicle owner registration requests are waiting for approval.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Owner ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Owner Type</th>
                                        <th>Bank Info</th>
                                        <th>Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingOwners as $owner): ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">OWN-<?= (int)$owner['id'] ?></span></td>
                                            <td><div class="cell-primary-text"><?= $escape($owner['name']) ?></div></td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-secondary-text"><?= $escape($owner['email']) ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($owner['phone']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-blue"><?= $escape(ucfirst($owner['owner_type'])) ?></span></td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-secondary-text"><?= $escape($owner['bank_name'] ?: 'Not configured') ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($owner['bank_account_no'] ?: '-') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <form method="post" action="owner_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="owner_id" value="<?= (int)$owner['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-approve btn-sm">Approve</button>
                                                    </form>
                                                    <form method="post" action="owner_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="owner_id" value="<?= (int)$owner['id'] ?>">
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
                        <h3 class="card-title-text">Registered Vehicle Owners</h3>
                        <span class="badge badge-approved"><?= count($reviewedOwners) ?> Approved</span>
                    </div>

                    <?php if (empty($reviewedOwners)): ?>
                        <p style="color: var(--text-secondary);">No vehicle owner records found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Owner ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Owner Type</th>
                                        <th>Vehicles Count</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviewedOwners as $owner): ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">OWN-<?= (int)$owner['id'] ?></span></td>
                                            <td><div class="cell-primary-text"><?= $escape($owner['name']) ?></div></td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-secondary-text"><?= $escape($owner['email']) ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($owner['phone']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-blue"><?= $escape(ucfirst($owner['owner_type'])) ?></span></td>
                                            <td><span class="cell-primary-text"><?= (int)$owner['total_vehicles'] ?> Vehicles</span></td>
                                            <td>
                                                <span class="badge <?= $owner['verification_status'] === 'approved' ? 'badge-approved' : 'badge-pending' ?>">
                                                    <?= $escape(ucfirst($owner['verification_status'])) ?>
                                                </span>
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
