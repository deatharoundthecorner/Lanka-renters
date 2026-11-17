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
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            <h3>No pending owner approvals</h3>
                            <p>No vehicle owner registration requests are currently waiting for approval.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
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
                                            <td><span class="cell-secondary-text" style="font-family:monospace; font-weight:700;">OWN-<?= (int)$owner['id'] ?></span></td>
                                            <td>
                                                <div class="cell-primary-text" style="font-weight:600;"><?= $escape($owner['name']) ?></div>
                                                <span class="badge badge-pending" style="font-size:10.5px; margin-top:3px;">Account: Pending Approval</span>
                                            </td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-primary-text" style="font-size:12.5px;"><?= $escape($owner['email']) ?></span>
                                                    <span class="cell-secondary-text" style="font-size:12px;"><?= $escape($owner['phone']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-blue"><?= $escape(ucfirst($owner['owner_type'])) ?></span></td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-primary-text" style="font-size:12.5px;"><?= $escape($owner['bank_name'] ?: 'Not configured') ?></span>
                                                    <span class="cell-secondary-text" style="font-family:monospace; font-size:12px;"><?= $escape($owner['bank_account_no'] ?: '-') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" style="gap:6px; flex-wrap:nowrap;">
                                                    <form method="post" action="owner_verification.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="owner_id" value="<?= (int)$owner['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-approve btn-sm">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                            Approve
                                                        </button>
                                                    </form>
                                                    <form method="post" action="owner_verification.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="owner_id" value="<?= (int)$owner['id'] ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" class="btn btn-reject btn-sm">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                            Reject
                                                        </button>
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
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            <h3>No registered vehicle owners</h3>
                            <p>No verified vehicle owner records were found in the database.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
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
