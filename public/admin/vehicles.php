<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminVehicleVerification.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Vehicles";
$districts = getSriLankanDistricts();
$vehicleTypes = getVehicleTypes();
$defaultView = ($_GET['view'] ?? '') === 'registered' ? 'registered' : 'pending';

$flash = $_SESSION['admin_vehicle_flash'] ?? null;
unset($_SESSION['admin_vehicle_flash']);

try {
    $model = new AdminVehicleVerification();
    $pendingVehicles = $model->getPendingVehicles();
    $registeredVehicles = $model->getRegisteredVehicles();
} catch (Throwable $e) {
    error_log("Vehicles Page Error: " . $e->getMessage());
    $pendingVehicles = [];
    $registeredVehicles = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles Management - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Vehicles Management</h1>
                <p class="page-subtitle">Review vehicle registrations, inspect revenue rates, and manage fleet availability.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <!-- Segmented Tab Bar -->
            <div class="nav-tabs">
                <a href="vehicles.php" class="tab-item <?= $defaultView === 'pending' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Pending Approval (<?= count($pendingVehicles) ?>)</span>
                </a>
                <a href="vehicles.php?view=registered" class="tab-item <?= $defaultView === 'registered' ? 'active' : '' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Fleet Vehicles (<?= count($registeredVehicles) ?>)</span>
                </a>
            </div>

            <?php if ($defaultView === 'pending'): ?>
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Pending Vehicle Approvals</h3>
                        <span class="badge badge-pending"><?= count($pendingVehicles) ?> Pending</span>
                    </div>

                    <?php if (empty($pendingVehicles)): ?>
                        <p style="color: var(--text-secondary);">No vehicles currently pending verification.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Vehicle ID</th>
                                        <th>Model / Make</th>
                                        <th>License Plate</th>
                                        <th>Type</th>
                                        <th>Daily Rate</th>
                                        <th>Owner</th>
                                        <th>Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingVehicles as $veh): ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">VEH-<?= (int)$veh['id'] ?></span></td>
                                            <td><div class="cell-primary-text"><?= $escape($veh['make'] . ' ' . $veh['model'] . ' (' . $veh['year'] . ')') ?></div></td>
                                            <td><span class="badge badge-blue"><?= $escape($veh['license_plate']) ?></span></td>
                                            <td><?= $escape(ucfirst($veh['vehicle_type'])) ?></td>
                                            <td><span class="cell-primary-text">Rs. <?= number_format((float)$veh['price_per_day'], 2) ?></span></td>
                                            <td><?= $escape($veh['owner_name']) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <form method="post" action="vehicle_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="vehicle_id" value="<?= (int)$veh['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-approve btn-sm">Approve</button>
                                                    </form>
                                                    <form method="post" action="vehicle_verification.php">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="vehicle_id" value="<?= (int)$veh['id'] ?>">
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
                        <h3 class="card-title-text">Registered Fleet Vehicles</h3>
                        <span class="badge badge-approved"><?= count($registeredVehicles) ?> Active Fleet</span>
                    </div>

                    <?php if (empty($registeredVehicles)): ?>
                        <p style="color: var(--text-secondary);">No registered fleet vehicles found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Vehicle ID</th>
                                        <th>Model / Make</th>
                                        <th>License Plate</th>
                                        <th>Type</th>
                                        <th>Daily Rate</th>
                                        <th>Availability</th>
                                        <th>Owner</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registeredVehicles as $veh): ?>
                                        <tr>
                                            <td><span class="cell-secondary-text">VEH-<?= (int)$veh['id'] ?></span></td>
                                            <td><div class="cell-primary-text"><?= $escape($veh['make'] . ' ' . $veh['model'] . ' (' . $veh['year'] . ')') ?></div></td>
                                            <td><span class="badge badge-blue"><?= $escape($veh['license_plate']) ?></span></td>
                                            <td><?= $escape(ucfirst($veh['vehicle_type'])) ?></td>
                                            <td><span class="cell-primary-text">Rs. <?= number_format((float)$veh['price_per_day'], 2) ?></span></td>
                                            <td>
                                                <span class="badge <?= $veh['status'] === 'available' ? 'badge-approved' : ($veh['status'] === 'rented' ? 'badge-blue' : 'badge-pending') ?>">
                                                    <?= $escape(ucfirst($veh['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= $escape($veh['owner_name']) ?></td>
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
