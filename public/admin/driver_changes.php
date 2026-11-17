<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/DriverChangeRequest.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Driver Change Requests";
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : null;
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
    $statusFilter = null;
}

try {
    $model = new DriverChangeRequest();
    $requests = $model->getAllRequests($statusFilter);
} catch (Throwable $e) {
    error_log("Admin Driver Change Requests Error: " . $e->getMessage());
    $requests = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Change Requests - Lanka Renters Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .filter-tab { padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500; color: #4b5563; background: #f3f4f6; }
        .filter-tab.active { background: #1e3a8a; color: #ffffff; }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Driver Change Requests Oversight</h1>
                <p class="page-subtitle">Platform-wide visibility of customer driver change requests and fleet owner decisions.</p>
            </div>

            <!-- Filter tabs -->
            <div class="filter-tabs">
                <a href="driver_changes.php" class="filter-tab <?= $statusFilter === null ? 'active' : '' ?>">All</a>
                <a href="driver_changes.php?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="driver_changes.php?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
                <a href="driver_changes.php?status=rejected" class="filter-tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
                <a href="driver_changes.php?status=cancelled" class="filter-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
            </div>

            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Requests Log</h3>
                    <span class="badge badge-pending"><?= count($requests) ?> Records</span>
                </div>

                <div class="lr-table-wrap table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Booking / Fleet Owner</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Current Driver</th>
                                <th>Requested Driver</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Review Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="lr-empty-state">
                                            <div class="lr-empty-icon">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                            </div>
                                            <h3>No driver change requests</h3>
                                            <p>No driver change requests match the selected view.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><span class="cell-secondary-text">DCR-<?= (int)$req['id'] ?></span></td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text">BKG-<?= (int)$req['booking_id'] ?></span>
                                                <span class="cell-secondary-text">Owner: <?= $escape($req['owner_name']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="cell-primary-text"><?= $escape($req['customer_name']) ?></span></td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($req['make'] . ' ' . $req['model']) ?></span>
                                                <span class="cell-secondary-text"><?= $escape($req['license_plate']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="cell-secondary-text"><?= $escape($req['current_driver_name']) ?></span></td>
                                        <td><strong style="color: #1e3a8a;"><?= $escape($req['requested_driver_name']) ?></strong></td>
                                        <td style="max-width: 200px;"><span style="font-size: 13px; color: var(--text-secondary);"><?= $escape($req['reason']) ?></span></td>
                                        <td>
                                            <?php
                                            $badgeClass = match ($req['status']) {
                                                'approved'  => 'badge-approved',
                                                'rejected'  => 'badge-rejected',
                                                'cancelled' => 'badge-blue',
                                                default     => 'badge-pending',
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $escape(ucfirst($req['status'])) ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($req['reviewed_at'])): ?>
                                                <div class="cell-stacked" style="font-size: 12px;">
                                                    <span class="cell-secondary-text">By: <?= $escape($req['reviewer_name'] ?? 'Fleet Owner') ?></span>
                                                    <span class="cell-secondary-text"><?= $escape(date('d M Y, h:i A', strtotime($req['reviewed_at']))) ?></span>
                                                    <?php if (!empty($req['owner_remarks'])): ?>
                                                        <span class="cell-secondary-text"><em>"<?= $escape($req['owner_remarks']) ?>"</em></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="cell-secondary-text">Awaiting Owner Review</span>
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

</body>
</html>
