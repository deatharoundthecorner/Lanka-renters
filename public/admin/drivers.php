<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminDriverVerification.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Drivers";
$districts = getSriLankanDistricts();
$defaultView = ($_GET['view'] ?? '') === 'registered' ? 'registered' : 'pending';

$flash = $_SESSION['admin_driver_flash'] ?? null;
unset($_SESSION['admin_driver_flash']);

try {
    $model = new AdminDriverVerification();
    $pendingDrivers = $model->getPendingDrivers();
    $registeredDrivers = $model->getRegisteredDrivers();
} catch (Throwable $e) {
    error_log("Drivers Page Error: " . $e->getMessage());
    $pendingDrivers = [];
    $registeredDrivers = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drivers - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Drivers</h1>
                <p class="page-subtitle">Review registered driver applications and verify driver license documentation.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <!-- Segmented Tab Bar -->
            <div class="nav-tabs">
                <a href="drivers.php" class="tab-item <?= $defaultView === 'pending' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Pending Approval (<?= count($pendingDrivers) ?>)</span>
                </a>
                <a href="drivers.php?view=registered" class="tab-item <?= $defaultView === 'registered' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Registered Drivers (<?= count($registeredDrivers) ?>)</span>
                </a>
            </div>

            <?php if ($defaultView === 'pending'): ?>
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Pending Driver Approvals</h3>
                        <span class="badge badge-pending"><?= count($pendingDrivers) ?> Pending</span>
                    </div>

                    <?php if (empty($pendingDrivers)): ?>
                        <p style="color: var(--text-secondary);">No driver applications currently pending verification.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Driver ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Availability</th>
                                        <th>Owner Link</th>
                                        <th>Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingDrivers as $drv): ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">DRV-<?= (int)$drv['id'] ?></span></td>
                                            <td><div class="cell-primary-text"><?= $escape($drv['name']) ?></div></td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-secondary-text"><?= $escape($drv['email']) ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($drv['phone']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-pending"><?= $escape(ucfirst($drv['availability_status'])) ?></span></td>
                                            <td><?= $escape($drv['owner_name'] ?: 'Independent') ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <form method="post" action="driver_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="driver_id" value="<?= (int)$drv['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-approve btn-sm">Approve</button>
                                                    </form>
                                                    <form method="post" action="driver_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="driver_id" value="<?= (int)$drv['id'] ?>">
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
                        <h3 class="card-title-text">Registered Drivers</h3>
                        <span class="badge badge-approved"><?= count($registeredDrivers) ?> Approved</span>
                    </div>

                    <?php if (empty($registeredDrivers)): ?>
                        <p style="color: var(--text-secondary);">No registered driver records found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Driver ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Rating</th>
                                        <th>Availability</th>
                                        <th>Owner Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registeredDrivers as $drv): ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">DRV-<?= (int)$drv['id'] ?></span></td>
                                            <td><div class="cell-primary-text"><?= $escape($drv['name']) ?></div></td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-secondary-text"><?= $escape($drv['email']) ?></span>
                                                    <span class="cell-secondary-text"><?= $escape($drv['phone']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-approved">★ <?= number_format((float)$drv['rating_avg'], 1) ?></span></td>
                                            <td><span class="badge badge-blue"><?= $escape(ucfirst($drv['availability_status'])) ?></span></td>
                                            <td><?= $escape($drv['owner_name'] ?: 'Independent') ?></td>
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
