<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerReplacementController.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$controller = new OwnerReplacementController();
$actionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionResult = $controller->handleStatusUpdate();
}

$dataResult = $controller->getReplacementData();

$requests          = $dataResult['success'] ? $dataResult['requests'] : [];
$summary           = $dataResult['success'] ? $dataResult['summary'] : ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$availableVehicles = $dataResult['success'] ? $dataResult['availableVehicles'] : [];
$filter            = $dataResult['filter'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replacement Requests - LankaRenters Owner</title>
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-wrapper">
            <?php include 'includes/header.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
                <section class="vehicles-header" style="margin-bottom: 24px;">
                    <div class="vehicles-title">
                        <h1>Replacement Vehicle Requests</h1>
                        <p>Review and respond to replacement vehicle requests submitted for your fleet during incidents.</p>
                    </div>
                </section>

                <!-- Feedback Alert -->
                <?php if ($actionResult): ?>
                    <div style="padding: 14px 20px; border-radius: 14px; margin-bottom: 20px; <?php echo $actionResult['success'] ? 'background-color:#f0fdf4; border:1px solid #86efac; color:#166534;' : 'background-color:#fef2f2; border:1px solid #fca5a5; color:#991b1b;'; ?>">
                        <?php echo htmlspecialchars($actionResult['success'] ? $actionResult['message'] : $actionResult['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Metric Cards -->
                <section class="overview-stats" aria-label="Replacement statistics" style="margin-bottom: 28px;">
                    <div class="stats-grid">
                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Total Requests</p>
                                    <p class="stat-value"><?php echo (int)($summary['total'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-blue">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="23 4 23 10 17 10"></polyline>
                                        <polyline points="1 20 1 14 7 14"></polyline>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Fleet incidents replacement log</p>
                        </article>

                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Pending Action</p>
                                    <p class="stat-value"><?php echo (int)($summary['pending'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-yellow">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Awaiting review</p>
                        </article>

                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Approved & Dispatched</p>
                                    <p class="stat-value"><?php echo (int)($summary['approved'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-green">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Vehicle replaced</p>
                        </article>

                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Rejected / Cancelled</p>
                                    <p class="stat-value"><?php echo (int)($summary['rejected'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-red">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Declined requests</p>
                        </article>
                    </div>
                </section>

                <!-- Requests Table -->
                <section class="requests-list" aria-label="Replacement Requests Table">
                    <div class="form-card" style="padding: 0; overflow: hidden;">
                        <div class="form-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                            <h2 style="margin: 0; font-size: 1.1rem; color: #0f172a;">Replacement Requests Log</h2>
                            <div class="filter-tabs" style="display: flex; gap: 8px;">
                                <a href="replacement-requests.php" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo empty($filter) ? 'background-color:#eff6ff; color:#2563eb; border-color:#93c5fd;' : ''; ?>">All</a>
                                <a href="replacement-requests.php?status=pending" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'pending' ? 'background-color:#fef3c7; color:#92400e; border-color:#fde047;' : ''; ?>">Pending</a>
                                <a href="replacement-requests.php?status=approved" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'approved' ? 'background-color:#dcfce7; color:#166534; border-color:#86efac;' : ''; ?>">Approved</a>
                                <a href="replacement-requests.php?status=rejected" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'rejected' ? 'background-color:#fee2e2; color:#991b1b; border-color:#fca5a5;' : ''; ?>">Rejected</a>
                            </div>
                        </div>

                        <?php if (empty($requests)): ?>
                            <div style="padding: 48px; text-align: center; color: #64748b;">
                                <div style="font-size: 2.8rem; margin-bottom: 12px;">🔄</div>
                                <p style="font-size: 1.05rem; font-weight: 600; color: #0f172a; margin: 0 0 6px;">No Replacement Requests Found</p>
                                <p style="margin: 0; font-size: 0.9rem;">Replacement vehicle requests logged during breakdown or accident incidents will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                                    <thead>
                                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">
                                            <th style="padding: 14px 20px;">ID</th>
                                            <th style="padding: 14px 20px;">Original Vehicle</th>
                                            <th style="padding: 14px 20px;">Incident & Reason</th>
                                            <th style="padding: 14px 20px;">Requester</th>
                                            <th style="padding: 14px 20px;">Assigned Replacement</th>
                                            <th style="padding: 14px 20px;">Status</th>
                                            <th style="padding: 14px 20px; text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $item): ?>
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 14px 20px; font-weight: 600; color: #1e293b;">
                                                    #REQ-<?php echo (int)$item['id']; ?>
                                                    <br><span style="font-size: 0.78rem; color: #64748b; font-weight: normal;">Book #<?php echo (int)$item['booking_ref']; ?></span>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <div style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($item['orig_make'] . ' ' . $item['orig_model']); ?></div>
                                                    <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($item['orig_plate']); ?></div>
                                                </td>
                                                <td style="padding: 14px 20px; max-width: 240px;">
                                                    <div style="font-weight: 600; color: #1e293b; font-size: 0.84rem;">
                                                        Severity: <span style="text-transform: capitalize; color: #dc2626;"><?php echo htmlspecialchars($item['incident_severity']); ?></span>
                                                    </div>
                                                    <div style="font-size: 0.82rem; color: #475569; margin-top: 2px;"><?php echo htmlspecialchars($item['reason']); ?></div>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <div style="color: #1e293b; font-weight: 500;"><?php echo htmlspecialchars($item['requester_name']); ?></div>
                                                    <div style="font-size: 0.78rem; color: #64748b; text-transform: capitalize;"><?php echo htmlspecialchars($item['requester_role']); ?></div>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <?php if (!empty($item['rep_make'])): ?>
                                                        <div style="font-weight: 600; color: #166534;"><?php echo htmlspecialchars($item['rep_make'] . ' ' . $item['rep_model']); ?></div>
                                                        <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($item['rep_plate']); ?></div>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.82rem; color: #94a3b8; italic;">None assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <?php if ($item['status'] === 'pending'): ?>
                                                        <span class="status-badge badge-warning" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background-color: #fef3c7; color: #b45309;">Pending</span>
                                                    <?php elseif (in_array($item['status'], ['approved', 'dispatched', 'delivered'], true)): ?>
                                                        <span class="status-badge badge-success" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background-color: #dcfce7; color: #15803d; text-transform: capitalize;"><?php echo htmlspecialchars($item['status']); ?></span>
                                                    <?php else: ?>
                                                        <span class="status-badge badge-danger" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background-color: #fee2e2; color: #b91c1c; text-transform: capitalize;"><?php echo htmlspecialchars($item['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 14px 20px; text-align: right;">
                                                    <?php if ($item['status'] === 'pending'): ?>
                                                        <form method="POST" action="replacement-requests.php" style="display: inline-flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>" />
                                                            <input type="hidden" name="request_id" value="<?php echo (int)$item['id']; ?>" />
                                                            <?php if (!empty($availableVehicles)): ?>
                                                                <select name="replacement_vehicle_id" style="padding: 4px 8px; font-size: 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                                                                    <option value="">-- Assign Replacement Vehicle --</option>
                                                                    <?php foreach ($availableVehicles as $v): ?>
                                                                        <option value="<?php echo (int)$v['id']; ?>">
                                                                            <?php echo htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['license_plate'] . ')'); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            <?php endif; ?>

                                                            <div style="display: flex; gap: 6px;">
                                                                <button type="submit" name="action" value="approved" class="button button-primary" style="padding: 4px 10px; font-size: 0.78rem; background-color: #16a34a; border-color: #16a34a;">Approve</button>
                                                                <button type="submit" name="action" value="rejected" class="button button-outline" style="padding: 4px 10px; font-size: 0.78rem; color: #dc2626; border-color: #fca5a5;">Reject</button>
                                                            </div>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.8rem; color: #94a3b8;">No actions</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>