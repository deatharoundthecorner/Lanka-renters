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
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <h3>No pending driver applications</h3>
                            <p>There are no driver verification applications currently waiting for review.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Driver ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Documents (NIC / Licence / Police)</th>
                                        <th>Availability</th>
                                        <th>Owner Link</th>
                                        <th>Decision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingDrivers as $drv): ?>
                                        <?php
                                        $docMap = [];
                                        $rawDocs = $model->getDriverDocuments((int)$drv['id']);
                                        foreach ($rawDocs as $d) {
                                            if (!isset($docMap[$d['document_type']]) || (int)($d['is_current'] ?? 0) === 1) {
                                                $docMap[$d['document_type']] = $d;
                                            }
                                        }
                                        $docConfig = [
                                            'nic' => 'NIC',
                                            'driving_license' => 'Driving Licence',
                                            'police_report' => 'Police Report'
                                        ];
                                        ?>
                                        <tr>
                                            <td><span class="cell-secondary-text" style="font-family:monospace; font-weight:700;">DRV-<?= (int)$drv['id'] ?></span></td>
                                            <td>
                                                <div class="cell-primary-text" style="font-weight:600;"><?= $escape($drv['name']) ?></div>
                                                <span class="badge <?= $drv['user_status'] === 'active' ? 'badge-approved' : 'badge-pending' ?>" style="font-size:10.5px; margin-top:3px;">
                                                    Account: <?= $escape(ucfirst($drv['user_status'] ?? 'pending')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-primary-text" style="font-size:12.5px;"><?= $escape($drv['email']) ?></span>
                                                    <span class="cell-secondary-text" style="font-size:12px;"><?= $escape($drv['phone']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display:flex; flex-direction:column; gap:6px; min-width:260px;">
                                                    <?php foreach ($docConfig as $typeKey => $typeLabel): ?>
                                                        <?php
                                                        $doc = $docMap[$typeKey] ?? null;
                                                        $physicalPath = !empty($doc['file_path']) ? dirname(__DIR__, 2) . '/public/' . ltrim($doc['file_path'], '/') : '';
                                                        $fileExists = !empty($physicalPath) && file_exists($physicalPath) && is_file($physicalPath);
                                                        $docStatus = $doc['status'] ?? 'not_uploaded';
                                                        $badgeClass = match($docStatus) {
                                                            'approved' => 'badge-approved',
                                                            'rejected' => 'badge-rejected',
                                                            'resubmission_required', 'resubmission' => 'badge-resubmission_required',
                                                            'pending' => 'badge-pending',
                                                            default => 'badge-suspended'
                                                        };
                                                        ?>
                                                        <div class="doc-card-mini">
                                                            <div class="doc-meta">
                                                                <span class="doc-name"><?= $escape($typeLabel) ?></span>
                                                                <span class="badge <?= $badgeClass ?>" style="padding: 2px 7px; font-size: 10.5px;">
                                                                    <?= $doc ? $escape(ucfirst($doc['status'])) : 'Not uploaded' ?>
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
                                            <td><span class="badge badge-pending"><?= $escape(ucfirst($drv['availability_status'])) ?></span></td>
                                            <td><span class="cell-secondary-text"><?= $escape($drv['owner_name'] ?: 'Independent') ?></span></td>
                                            <td>
                                                <div class="btn-group" style="gap: 6px; flex-wrap: nowrap;">
                                                    <form method="post" action="driver_verification.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="driver_id" value="<?= (int)$drv['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-approve btn-sm">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                            Approve
                                                        </button>
                                                    </form>
                                                    <form method="post" action="driver_verification.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                        <input type="hidden" name="driver_id" value="<?= (int)$drv['id'] ?>">
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
                        <h3 class="card-title-text">Registered Drivers</h3>
                        <span class="badge badge-approved"><?= count($registeredDrivers) ?> Approved</span>
                    </div>

                    <?php if (empty($registeredDrivers)): ?>
                        <div class="lr-empty-state">
                            <div class="lr-empty-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <h3>No registered drivers</h3>
                            <p>No verified driver records were found in the database.</p>
                        </div>
                    <?php else: ?>
                        <div class="lr-table-wrap table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Driver ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Documents</th>
                                        <th>Rating</th>
                                        <th>Availability</th>
                                        <th>Owner Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registeredDrivers as $drv): ?>
                                        <?php
                                        $docMap = [];
                                        $rawDocs = $model->getDriverDocuments((int)$drv['id']);
                                        foreach ($rawDocs as $d) {
                                            if (!isset($docMap[$d['document_type']]) || (int)($d['is_current'] ?? 0) === 1) {
                                                $docMap[$d['document_type']] = $d;
                                            }
                                        }
                                        $docConfig = [
                                            'nic' => 'NIC',
                                            'driving_license' => 'Driving Licence',
                                            'police_report' => 'Police Report'
                                        ];
                                        ?>
                                        <tr>
                                            <td><span class="cell-secondary-text" style="font-family:monospace; font-weight:700;">DRV-<?= (int)$drv['id'] ?></span></td>
                                            <td>
                                                <div class="cell-primary-text" style="font-weight:600;"><?= $escape($drv['name']) ?></div>
                                                <span class="badge <?= $drv['user_status'] === 'active' ? 'badge-approved' : 'badge-pending' ?>" style="font-size:10.5px; margin-top:3px;">
                                                    Account: <?= $escape(ucfirst($drv['user_status'] ?? 'active')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="cell-stacked">
                                                    <span class="cell-primary-text" style="font-size:12.5px;"><?= $escape($drv['email']) ?></span>
                                                    <span class="cell-secondary-text" style="font-size:12px;"><?= $escape($drv['phone']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display:flex; flex-direction:column; gap:6px; min-width:260px;">
                                                    <?php foreach ($docConfig as $typeKey => $typeLabel): ?>
                                                        <?php
                                                        $doc = $docMap[$typeKey] ?? null;
                                                        $physicalPath = !empty($doc['file_path']) ? dirname(__DIR__, 2) . '/public/' . ltrim($doc['file_path'], '/') : '';
                                                        $fileExists = !empty($physicalPath) && file_exists($physicalPath) && is_file($physicalPath);
                                                        $docStatus = $doc['status'] ?? 'not_uploaded';
                                                        $badgeClass = match($docStatus) {
                                                            'approved' => 'badge-approved',
                                                            'rejected' => 'badge-rejected',
                                                            'resubmission_required', 'resubmission' => 'badge-resubmission_required',
                                                            'pending' => 'badge-pending',
                                                            default => 'badge-suspended'
                                                        };
                                                        ?>
                                                        <div class="doc-card-mini">
                                                            <div class="doc-meta">
                                                                <span class="doc-name"><?= $escape($typeLabel) ?></span>
                                                                <span class="badge <?= $badgeClass ?>" style="padding: 2px 7px; font-size: 10.5px;">
                                                                    <?= $doc ? $escape(ucfirst($doc['status'])) : 'Not uploaded' ?>
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
