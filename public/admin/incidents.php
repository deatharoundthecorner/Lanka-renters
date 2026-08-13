<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminIncidentManagement.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Incident Management";
$flash = $_SESSION['admin_incident_flash'] ?? null;
unset($_SESSION['admin_incident_flash']);

$statusFilter = trim($_GET['status'] ?? '');

try {
    $model = new AdminIncidentManagement();
    $incidents = $model->getIncidents(['status' => $statusFilter]);
} catch (Throwable $e) {
    error_log("Incidents Page Error: " . $e->getMessage());
    $incidents = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Management - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Incident Management</h1>
                <p class="page-subtitle">Track reported vehicle accidents, damages, driver issues, and investigation logs.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Reported Incidents Log</h3>
                    <span class="badge badge-pending"><?= count($incidents) ?> Total Incidents</span>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Incident ID</th>
                                <th>Booking / Vehicle</th>
                                <th>Reported By</th>
                                <th>Severity</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($incidents)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 24px;">No incident records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($incidents as $inc): ?>
                                    <tr>
                                        <td><span class="cell-secondary-text">INC-<?= (int)$inc['id'] ?></span></td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text">BKG-<?= (int)$inc['booking_id'] ?></span>
                                                <span class="cell-secondary-text"><?= $escape($inc['make'] . ' ' . $inc['model'] . ' (' . $inc['license_plate'] . ')') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($inc['reporter_name']) ?></span>
                                                <span class="cell-secondary-text"><?= $escape(ucfirst($inc['reporter_role'])) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $inc['severity'] === 'major' ? 'badge-rejected' : ($inc['severity'] === 'moderate' ? 'badge-pending' : 'badge-blue') ?>">
                                                <?= $escape(ucfirst($inc['severity'])) ?>
                                            </span>
                                        </td>
                                        <td><div style="max-width: 260px; font-size: 13px; color: var(--text-secondary);"><?= $escape($inc['description']) ?></div></td>
                                        <td>
                                            <span class="badge <?= $inc['status'] === 'resolved' ? 'badge-approved' : ($inc['status'] === 'investigating' ? 'badge-blue' : 'badge-pending') ?>">
                                                <?= $escape(ucfirst($inc['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" action="incident_handler.php" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                <input type="hidden" name="incident_id" value="<?= (int)$inc['id'] ?>">
                                                <select name="status" class="form-control" style="padding: 4px 8px; font-size: 12px; height: auto;">
                                                    <option value="reported" <?= $inc['status'] === 'reported' ? 'selected' : '' ?>>Reported</option>
                                                    <option value="investigating" <?= $inc['status'] === 'investigating' ? 'selected' : '' ?>>Investigating</option>
                                                    <option value="resolved" <?= $inc['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                            </form>
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
