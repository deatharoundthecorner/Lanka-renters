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
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 11l2-5h10l2 5"/></svg>
                            </div>
                            <h3>No pending vehicle verifications</h3>
                            <p>All submitted owner vehicles have been reviewed or no pending submissions exist.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Vehicle ID</th>
                                        <th>Model / Make</th>
                                        <th>License Plate</th>
                                        <th>Documents (CR / Insurance / Emission / Fitness)</th>
                                        <th>Type</th>
                                        <th>Daily Rate</th>
                                        <th>Owner</th>
                                        <th>Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingVehicles as $veh): ?>
                                        <?php
                                        $docMap = [];
                                        $rawDocs = $model->getVehicleDocuments((int)$veh['id']);
                                        foreach ($rawDocs as $d) {
                                            if (!isset($docMap[$d['document_type']])) {
                                                $docMap[$d['document_type']] = $d;
                                            }
                                        }
                                        $vehDocConfig = [
                                            'registration' => 'CR Book',
                                            'insurance' => 'Insurance',
                                            'emission_test' => 'Emission Certificate',
                                            'fitness_certificate' => 'Fitness Certificate'
                                        ];
                                        ?>
                                        <tr>
                                            <td><span class="cell-secondary-text" style="font-family:monospace; font-weight:700;">VEH-<?= (int)$veh['id'] ?></span></td>
                                            <td>
                                                <div class="cell-primary-text" style="font-weight:600;"><?= $escape($veh['make'] . ' ' . $veh['model'] . ' (' . $veh['year'] . ')') ?></div>
                                                <span class="badge badge-pending" style="font-size:10.5px; margin-top:4px;">
                                                    Vehicle: Pending Review
                                                </span>
                                            </td>
                                            <td><span class="badge badge-blue" style="font-family:monospace;"><?= $escape($veh['license_plate']) ?></span></td>
                                            <td>
                                                <div style="display:flex; flex-direction:column; gap:6px; min-width:270px;">
                                                    <?php foreach ($vehDocConfig as $typeKey => $typeLabel): ?>
                                                        <?php
                                                        $doc = $docMap[$typeKey] ?? null;
                                                        $physicalPath = !empty($doc['file_path']) ? dirname(__DIR__, 2) . '/public/' . ltrim($doc['file_path'], '/') : '';
                                                        $fileExists = !empty($physicalPath) && file_exists($physicalPath) && is_file($physicalPath);
                                                        $docStatus = $doc['verification_status'] ?? 'not_uploaded';
                                                        $badgeClass = match($docStatus) {
                                                            'approved' => 'badge-approved',
                                                            'rejected' => 'badge-rejected',
                                                            'pending' => 'badge-pending',
                                                            default => 'badge-suspended'
                                                        };
                                                        ?>
                                                        <div class="doc-card-mini">
                                                            <div class="doc-meta">
                                                                <span class="doc-name"><?= $escape($typeLabel) ?></span>
                                                                <span class="badge <?= $badgeClass ?>" style="padding: 2px 7px; font-size: 10.5px;">
                                                                    <?= $doc ? $escape(ucfirst($doc['verification_status'] ?? 'pending')) : 'Not uploaded' ?>
                                                                </span>
                                                            </div>
                                                            <div class="doc-actions">
                                                                <?php if ($doc && $fileExists): ?>
                                                                    <a href="../<?= $escape($doc['file_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 11px; display:inline-flex; align-items:center; gap:3px;">
                                                                        View Document ↗
                                                                    </a>
                                                                <?php elseif ($doc): ?>
                                                                    <span class="cell-secondary-text" style="font-size: 11px; font-style: italic; color:var(--danger);">[File missing]</span>
                                                                <?php else: ?>
                                                                    <span class="cell-secondary-text" style="font-size: 11px; font-style: italic; color:var(--text-muted);">—</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td><?= $escape(ucfirst($veh['vehicle_type'])) ?></td>
                                            <td><span class="cell-primary-text">Rs. <?= number_format((float)$veh['price_per_day'], 2) ?></span></td>
                                            <td><span class="cell-secondary-text"><?= $escape($veh['owner_name']) ?></span></td>
                                            <td>
                                                <div class="btn-group" style="gap: 6px; flex-wrap: nowrap;">
                                                    <form method="post" action="vehicle_verification.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="vehicle_id" value="<?= (int)$veh['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-approve btn-sm">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                            Approve
                                                        </button>
                                                    </form>
                                                    <form method="post" action="vehicle_verification.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="vehicle_id" value="<?= (int)$veh['id'] ?>">
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
                        <h3 class="card-title-text">Registered Fleet Vehicles</h3>
                        <span class="badge badge-approved"><?= count($registeredVehicles) ?> Active Fleet</span>
                    </div>

                    <?php if (empty($registeredVehicles)): ?>
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 11l2-5h10l2 5"/></svg>
                            </div>
                            <h3>No registered fleet vehicles</h3>
                            <p>No verified fleet vehicles were found in the database.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Vehicle ID</th>
                                        <th>Model / Make</th>
                                        <th>License Plate</th>
                                        <th>Documents</th>
                                        <th>Type</th>
                                        <th>Daily Rate</th>
                                        <th>Availability</th>
                                        <th>Owner</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registeredVehicles as $veh): ?>
                                        <?php
                                        $docMap = [];
                                        $rawDocs = $model->getVehicleDocuments((int)$veh['id']);
                                        foreach ($rawDocs as $d) {
                                            if (!isset($docMap[$d['document_type']])) {
                                                $docMap[$d['document_type']] = $d;
                                            }
                                        }
                                        $vehDocConfig = [
                                            'registration' => 'CR Book',
                                            'insurance' => 'Insurance',
                                            'emission_test' => 'Emission Certificate',
                                            'fitness_certificate' => 'Fitness Certificate'
                                        ];
                                        ?>
                                        <tr>
                                            <td><span class="cell-secondary-text" style="font-family:monospace; font-weight:700;">VEH-<?= (int)$veh['id'] ?></span></td>
                                            <td>
                                                <div class="cell-primary-text" style="font-weight:600;"><?= $escape($veh['make'] . ' ' . $veh['model'] . ' (' . $veh['year'] . ')') ?></div>
                                                <span class="badge badge-approved" style="font-size:10.5px; margin-top:4px;">
                                                    Vehicle: Verified & Approved
                                                </span>
                                            </td>
                                            <td><span class="badge badge-blue" style="font-family:monospace;"><?= $escape($veh['license_plate']) ?></span></td>
                                            <td>
                                                <div style="display:flex; flex-direction:column; gap:6px; min-width:270px;">
                                                    <?php foreach ($vehDocConfig as $typeKey => $typeLabel): ?>
                                                        <?php
                                                        $doc = $docMap[$typeKey] ?? null;
                                                        $physicalPath = !empty($doc['file_path']) ? dirname(__DIR__, 2) . '/public/' . ltrim($doc['file_path'], '/') : '';
                                                        $fileExists = !empty($physicalPath) && file_exists($physicalPath) && is_file($physicalPath);
                                                        $docStatus = $doc['verification_status'] ?? 'not_uploaded';
                                                        $badgeClass = match($docStatus) {
                                                            'approved' => 'badge-approved',
                                                            'rejected' => 'badge-rejected',
                                                            'pending' => 'badge-pending',
                                                            default => 'badge-suspended'
                                                        };
                                                        ?>
                                                        <div class="doc-card-mini">
                                                            <div class="doc-meta">
                                                                <span class="doc-name"><?= $escape($typeLabel) ?></span>
                                                                <span class="badge <?= $badgeClass ?>" style="padding: 2px 7px; font-size: 10.5px;">
                                                                    <?= $doc ? $escape(ucfirst($doc['verification_status'] ?? 'pending')) : 'Not uploaded' ?>
                                                                </span>
                                                            </div>
                                                            <div class="doc-actions">
                                                                <?php if ($doc && $fileExists): ?>
                                                                    <a href="../<?= $escape($doc['file_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 11px; display:inline-flex; align-items:center; gap:3px;">
                                                                        View Document ↗
                                                                    </a>
                                                                <?php elseif ($doc): ?>
                                                                    <span class="cell-secondary-text" style="font-size: 11px; font-style: italic; color:var(--danger);">[File missing]</span>
                                                                <?php else: ?>
                                                                    <span class="cell-secondary-text" style="font-size: 11px; font-style: italic; color:var(--text-muted);">—</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
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
